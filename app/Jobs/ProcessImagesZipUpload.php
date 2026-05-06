<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessImagesZipUpload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $token;
    public string $zipPath;
    public ?int $uploaderId = null;
    public bool $isSeller = false;

    /**
     * Create a new job instance.
     */
    public function __construct(string $token, string $zipPath, ?int $uploaderId = null, bool $isSeller = false)
    {
        $this->token = $token;
        $this->zipPath = $zipPath;
        $this->uploaderId = $uploaderId;
        $this->isSeller = $isSeller;
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(ProductService $productService): void
    {
        $this->updateProgress(['status' => 'processing', 'message' => 'Processing ZIP...']);

        try {
            if (!is_file($this->zipPath)) {
                $this->updateProgress(['status' => 'failed', 'message' => 'ZIP file missing on server']);
                return;
            }

            // Extract and index
            [$imagesIndex, $tmpDir] = $this->prepareBulkImagesIndex($this->zipPath);

            // Collect targets: name-based keys only (product title)
            $nameKeys = array_unique(array_merge(
                array_keys($imagesIndex['main'] ?? []),
                array_keys($imagesIndex['additional'] ?? []),
                array_keys($imagesIndex['variant'] ?? [])
            ));

            // Build product map by normalized name for efficient lookup
            $query = Product::query()->select(['id','title','seller_id']);
            // If uploader is a seller, restrict to their own products only
            if ($this->isSeller && !empty($this->uploaderId)) {
                $query->where('seller_id', $this->uploaderId);
            }
            $allForMap = $query->get();
            $normalize = function (string $name): string {
                $name = trim($name);
                $name = preg_replace('/\s+/u', ' ', $name);
                $name = mb_strtolower($name);
                return $name;
            };
            // Map normalized name => list of product IDs (handle duplicate titles)
            $nameToProducts = [];
            foreach ($allForMap as $p) {
                $key = $normalize((string)($p->title ?? ''));
                if ($key === '') { continue; }
                $nameToProducts[$key] = $nameToProducts[$key] ?? [];
                $nameToProducts[$key][] = (int) $p->id;
            }

            // Prepare processing list as [rowKey (normalized name), productId, label]
            $targets = [];
            foreach ($nameKeys as $nk) {
                $pids = $nameToProducts[$nk] ?? [];
                if (empty($pids)) {
                    // Keep a placeholder so we can report not found for this name
                    $targets[] = ['row' => $nk, 'productId' => null, 'label' => 'Product "'.$nk.'"'];
                    continue;
                }
                foreach ($pids as $pid) {
                    $targets[] = [
                        'row' => $nk,
                        'productId' => $pid,
                        'label' => 'Product "'.$nk.'" (ID #'.$pid.')',
                    ];
                }
            }

            $total = count($targets);
            $processed = 0;
            $successCount = 0;
            $failed = [];

            $this->updateProgress(compact('total', 'processed', 'successCount') + ['failed_rows' => $failed, 'status' => 'processing']);

            foreach ($targets as $t) {
                try {
                    $pid = $t['productId'];
                    if ($pid === null) {
                        $failed[] = ['row' => $t['row'], 'title' => $t['label'], 'error' => 'Product not found'];
                    } else {
                        $product = Product::find($pid);
                        if (!$product) {
                            $failed[] = ['row' => $t['row'], 'title' => $t['label'], 'error' => 'Product not found'];
                        } else {
                            // Double check ownership just in case
                            if ($this->isSeller && !empty($this->uploaderId) && (int)$product->seller_id !== (int)$this->uploaderId) {
                                $failed[] = ['row' => $t['row'], 'title' => $t['label'], 'error' => 'Unauthorized: cannot modify other seller\'s product'];
                            } else {
                                $this->attachBulkImagesForProduct($productService, $product, $imagesIndex);
                                $successCount++;
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $failed[] = ['row' => $t['row'], 'title' => $t['label'], 'error' => $e->getMessage()];
                }
                $processed++;
                $this->updateProgress(compact('total', 'processed', 'successCount') + ['failed_rows' => $failed, 'status' => 'processing']);
            }

            // Cleanup
            if (!empty($tmpDir) && is_dir($tmpDir)) {
                try { $this->rrmdir($tmpDir); } catch (\Throwable) {}
            }
            try { @unlink($this->zipPath); } catch (\Throwable) {}

            $this->updateProgress([
                'status' => 'completed',
                'message' => 'Completed',
                'total' => $total,
                'processed' => $processed,
                'successCount' => $successCount,
                'failed_rows' => $failed,
            ]);
        } catch (\Throwable $e) {
            Log::error('ProcessImagesZipUpload failed: '.$e->getMessage());
            $this->updateProgress(['status' => 'failed', 'message' => $e->getMessage()]);
        }
    }

    private function cacheKey(): string
    {
        return 'images_upload:'.$this->token;
    }

    private function updateProgress(array $data): void
    {
        $key = $this->cacheKey();
        $current = Cache::get($key, []);
        Cache::put($key, array_merge($current, $data), now()->addHours(6));
    }

    /**
     * Extract images ZIP to a temp directory and index by product/variant patterns.
     * Accepts path instead of UploadedFile.
     * @return array [index, tempDir]
     */
    private function prepareBulkImagesIndex(string $zipPath): array
    {
        $tmpBase = storage_path('app/tmp');
        if (!is_dir($tmpBase)) {
            @mkdir($tmpBase, 0777, true);
        }
        $tmpDir = $tmpBase . '/bulk-images-' . uniqid();
        @mkdir($tmpDir, 0777, true);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException('Unable to open images ZIP');
        }
        $zip->extractTo($tmpDir);
        $zip->close();

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        // Index by product name (normalized) only
        $index = [
            'main' => [],          // normalizedName => filepath
            'additional' => [],    // normalizedName => [filepaths]
            'variant' => [],       // normalizedName => [variantId => filepath]
        ];

        $normalize = function (string $name): string {
            $name = trim($name);
            // collapse all whitespace to single spaces
            $name = preg_replace('/\s+/u', ' ', $name);
            // lowercase for case-insensitive matching
            $name = mb_strtolower($name);
            return $name;
        };

        $rii = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($tmpDir));
        Log::debug('ZIP contents: '.json_encode(iterator_to_array($rii)));
        foreach ($rii as $file) {
            if ($file->isDir()) continue;
            $path = $file->getPathname();
            $name = $file->getFilename();
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed, true)) continue;

            // Patterns by product title only. Order matters: check specific (additional/variant) before main
            // additional: {productName}-{n}.{ext}
            if (preg_match('/^(.+)-(\d+)\.(jpg|jpeg|png|webp)$/i', $name, $m)) {
                $pname = $normalize($m[1]);
                $index['additional'][$pname] = $index['additional'][$pname] ?? [];
                $index['additional'][$pname][] = $path;
                continue;
            }
            // variant: {productName}_{variantId}.{ext}
            if (preg_match('/^(.+)_([0-9]+)\.(jpg|jpeg|png|webp)$/i', $name, $m)) {
                $pname = $normalize($m[1]);
                $vid = (int)$m[2];
                $index['variant'][$pname] = $index['variant'][$pname] ?? [];
                $index['variant'][$pname][$vid] = $path;
                continue;
            }
            // main: {productName}.{ext}
            if (preg_match('/^(.+)\.(jpg|jpeg|png|webp)$/i', $name, $m)) {
                $pname = $normalize($m[1]);
                $index['main'][$pname] = $path;
                continue;
            }
        }

        return [$index, $tmpDir];
    }

    private function attachBulkImagesForProduct(ProductService $service, Product $product, array $imagesIndex): void
    {
        $normalize = function (string $name): string {
            $name = trim($name);
            $name = preg_replace('/\s+/u', ' ', $name);
            $name = mb_strtolower($name);
            return $name;
        };

        $pname = $normalize((string)($product->title ?? ''));

        // Name-based mapping only
        if (!empty($imagesIndex['main'][$pname])) {
            $service->setMainImageFromPath($product, $imagesIndex['main'][$pname]);
        }

        if (!empty($imagesIndex['additional'][$pname]) && is_array($imagesIndex['additional'][$pname])) {
            $service->setAdditionalImagesFromPaths($product, $imagesIndex['additional'][$pname]);
        }

        // Variant images (only one per variant)
        $variantMap = null;
        if (!empty($imagesIndex['variant'][$pname]) && is_array($imagesIndex['variant'][$pname])) {
            $variantMap = $imagesIndex['variant'][$pname];
        }

        if ($variantMap !== null) {
            $product->loadMissing('variants');
            foreach ($product->variants as $variant) {
                $vid = (int)$variant->id;
                if (!empty($variantMap[$vid])) {
                    $service->setVariantImageFromPath($variant, $variantMap[$vid]);
                }
            }
        }
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        $items = scandir($dir);
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $this->rrmdir($path);
            } else {
                @unlink($path);
            }
        }
        @rmdir($dir);
    }
}
