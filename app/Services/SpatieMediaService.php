<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SpatieMediaService
{
    public static function upload(object $model, string $media): mixed
    {
        // Try to get the uploaded file from the current request to derive a slugged filename
        $file = request()->file($media);
        if ($file) {
            $original = $file->getClientOriginalName();
            $basename = pathinfo($original, PATHINFO_FILENAME);
            $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            $slug = Str::slug($basename);
            $sluggedName = $extension ? ($slug . '.' . $extension) : $slug;

            return $model
                ->addMediaFromRequest($media)
                ->usingFileName($sluggedName)
                ->toMediaCollection($media);
        }

        // Fallback if file is not available for any reason
        return $model->addMediaFromRequest($media)->toMediaCollection($media);
    }

    public static function uploadFromRequest($model, $file, $collectionName)
    {
        $original = $file->getClientOriginalName();
        $basename = pathinfo($original, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
        $slug = Str::slug($basename);
        $sluggedName = $extension ? ($slug . '.' . $extension) : $slug;

        return $model
            ->addMedia($file)
            ->usingFileName($sluggedName)
            ->toMediaCollection($collectionName);
    }

    public static function update($request, $model, $media)
    {
        if ($request->hasFile($media)) {
            $newImageFile = $request->file($media);
            $existingImage = $model->getFirstMedia($media);

            $original = $newImageFile->getClientOriginalName();
            $basename = pathinfo($original, PATHINFO_FILENAME);
            $extension = strtolower(pathinfo($original, PATHINFO_EXTENSION));
            $slug = Str::slug($basename);
            $newImageName = $extension ? ($slug . '.' . $extension) : $slug;

            if (!$existingImage || $existingImage->file_name !== $newImageName) {
                return $model
                    ->addMedia($newImageFile)
                    ->usingFileName($newImageName)
                    ->toMediaCollection($media);
            }
        }
        return null;
    }

    public static function uploadFromUrl(object $model, string $url, string $collectionName): mixed
    {
        $basename = pathinfo($url, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($url, PATHINFO_EXTENSION));
        $slug = Str::slug($basename);
        $sluggedName = $extension ? ($slug . '.' . $extension) : $slug;

        // Validate extension and remote size/content-type for images
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $maxBytes = 2 * 1024 * 1024; // 2 MB

        if ($extension && !in_array($extension, $allowedExtensions, true)) {
            throw new \InvalidArgumentException('Invalid image extension. Allowed: jpg, jpeg, png, webp');
        }

        try {
            $response = Http::withoutVerifying()->head($url);
            if ($response->ok()) {
                // Validate content type if available
                $contentType = $response->header('Content-Type');
                if ($contentType && is_string($contentType)) {
                    $contentType = strtolower($contentType);
                    // Only enforce image types
                    if (!str_starts_with($contentType, 'image/')) {
                        throw new \InvalidArgumentException('URL does not point to an image resource');
                    }
                    // If extension is missing, infer from content type
                    if (!$extension) {
                        $map = [
                            'image/jpeg' => 'jpg',
                            'image/jpg' => 'jpg',
                            'image/png' => 'png',
                            'image/webp' => 'webp',
                        ];
                        if (isset($map[$contentType])) {
                            $extension = $map[$contentType];
                            $sluggedName = $slug . '.' . $extension;
                        }
                    }
                }

                // Validate size if header present
                $lengthHeader = $response->header('Content-Length');
                if ($lengthHeader) {
                    // For multiple header values, keep the last
                    $length = is_array($lengthHeader) ? (int) end($lengthHeader) : (int) $lengthHeader;
                    if ($length > $maxBytes) {
                        throw new \InvalidArgumentException('Image exceeds maximum allowed size of 2 MB');
                    }
                }
            }
        } catch (\Throwable $e) {
            // Do not block if HEAD fails; just log and continue, size will be enforced by storage limits
            Log::warning('uploadFromUrl HEAD check failed: '.$e->getMessage());
        }

        return $model
            ->addMediaFromUrl($url)
            ->usingFileName($sluggedName)
            ->toMediaCollection($collectionName);
    }
}
