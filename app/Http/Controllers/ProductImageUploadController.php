<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Jobs\ProcessImagesZipUpload;
use App\Services\ProductService;
use App\Traits\PanelAware;
use App\Types\Api\ApiResponseType;
use App\Enums\DefaultSystemRolesEnum;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ProductImageUploadController extends Controller
{
    use PanelAware, AuthorizesRequests;
    /**
     * Show the page for uploading only an images ZIP for existing products.
     */
    public function imagesUploadPage(): View
    {
        $this->authorize('create', Product::class);
        return view($this->panelView('products.bulk_upload_images'));
    }

    /**
     * Handle images ZIP upload and attach images to existing products/variants.
     */
    public function imagesUpload(Request $request, ProductService $productService): JsonResponse
    {
        try {
            $this->authorize('create', Product::class);

            $request->validate([
                'images-zip' => 'required|file|mimes:zip|max:204800', // up to 200 MB
            ]);

            // Store ZIP to a temp location for background processing
            $storedDir = storage_path('app/tmp/uploads');
            if (!is_dir($storedDir)) { @mkdir($storedDir, 0777, true); }
            $token = (string) Str::uuid();
            $zipPath = $storedDir . DIRECTORY_SEPARATOR . 'images-' . $token . '.zip';
            $request->file('images-zip')->move($storedDir, basename($zipPath));

            // Initialize cache progress and dispatch job
            $cacheKey = 'images_upload:' . $token;
            Cache::put($cacheKey, [
                'status' => 'queued',
                'message' => 'Queued for processing',
                'total' => 0,
                'processed' => 0,
                'successCount' => 0,
                'failed_rows' => [],
            ], now()->addHours(6));

            // Prefer processing after response to avoid blocking even if queue driver is sync
            $user = Auth::user();
            $seller = $user?->seller();
            $sellerId = $seller?->id ?? 0;
            $isSeller = $sellerId > 0 || $user?->hasRole(DefaultSystemRolesEnum::SELLER());
            ProcessImagesZipUpload::dispatchAfterResponse($token, $zipPath, $sellerId, $isSeller);

            return ApiResponseType::sendJsonResponse(true, 'labels.upload_started' ?? 'Upload started', [
                'token' => $token,
                'status' => 'queued',
            ], 202);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return ApiResponseType::sendJsonResponse(false, 'labels.validation_failed', $e->errors(), 422);
        } catch (\Throwable $e) {
            Log::error('Images ZIP upload failed: ' . $e->getMessage());
            return ApiResponseType::sendJsonResponse(false, 'labels.something_went_wrong', ['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Check async images upload status by token.
     */
    public function imagesUploadStatus(string $token): JsonResponse
    {
        try {
            $this->authorize('create', Product::class);
            $data = Cache::get('images_upload:' . $token);
            if (!$data) {
                return ApiResponseType::sendJsonResponse(false, 'labels.invalid_token' ?? 'Invalid or expired token', [], 404);
            }
            // Normalize payload to previous result shape when completed
            $normalized = [
                'status' => $data['status'] ?? 'processing',
                'message' => $data['message'] ?? null,
                'total' => $data['total'] ?? 0,
                'processed' => $data['processed'] ?? 0,
                'success_count' => $data['successCount'] ?? 0,
                'failed_count' => isset($data['failed_rows']) ? count($data['failed_rows']) : 0,
                'failed_rows' => $data['failed_rows'] ?? [],
            ];
            return ApiResponseType::sendJsonResponse(true, 'labels.status' ?? 'Status', $normalized, 200);
        } catch (AuthorizationException) {
            return ApiResponseType::sendJsonResponse(false, 'labels.permission_denied', [], 403);
        }
    }
}
