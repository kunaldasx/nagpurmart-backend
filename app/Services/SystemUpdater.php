<?php

namespace App\Services;

use App\Models\SystemUpdate;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use ZipArchive;

class SystemUpdater
{
    public const UPDATES_DIR = 'updates';
    public const TEMP_DIR    = 'updates/tmp';
    public const BACKUP_DIR  = 'updates/backups';
    public const LOCK_FILE   = 'updates/update.lock';

    /** ONLY SAFE, SHORT COMMANDS */
    protected array $allowedCommands = [
        'config:clear',
        'config:cache',
        'route:clear',
        'route:cache',
        'view:clear',
        'view:cache',
        'optimize',
        'optimize:clear',
        'storage:link',
    ];

    /**
     * Returns the current application version.
     * Priority: config('app.version') > latest applied SystemUpdate > '0.0.0'
     */
    public static function getCurrentVersion(): string
    {
        // Prefer the last applied update version
        $lastApplied = SystemUpdate::where('status', 'applied')->orderByDesc('id')->first();
        if ($lastApplied && is_string($lastApplied->version) && $lastApplied->version !== '') {
            return $lastApplied->version;
        }
        // Fallback to config value
        $fromConfig = config('app.version');
        if (is_string($fromConfig) && $fromConfig !== '') {
            return $fromConfig;
        }
        return '0.0.0';
    }

    public function apply(UploadedFile $zip, int $userId): SystemUpdate
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');

        $this->ensureDirs();
        $this->acquireLock();

        if (strtolower($zip->getClientOriginalExtension()) !== 'zip') {
            throw new \InvalidArgumentException('Only ZIP files are allowed.');
        }

        $storedPath = $zip->storeAs(
            self::UPDATES_DIR,
            pathinfo($zip->getClientOriginalName(), PATHINFO_FILENAME)
            . '_' . bin2hex(random_bytes(6)) . '.zip'
        );

        $update = SystemUpdate::create([
            'version' => 'unknown',
            'package_name' => basename($storedPath),
            'status' => 'pending',
        ]);

        $log = [];
        $appendLog = function (string $msg) use (&$log, $update) {
            $log[] = '[' . now()->toDateTimeString() . '] ' . $msg;
            $update->update(['log' => implode("\n", $log)]);
            Log::info('[SystemUpdater] ' . $msg);
        };

        $tempDir   = Storage::path(self::TEMP_DIR . '/' . $update->id);
        $backupDir = Storage::path(self::BACKUP_DIR . '/' . $update->id);

        try {
            /* ------------------ EXTRACT ------------------ */
            $appendLog('Extracting update package...');
            File::makeDirectory($tempDir, 0755, true);

            $zipper = new ZipArchive();
            if ($zipper->open(Storage::path($storedPath)) !== true) {
                throw new \RuntimeException('Failed to open ZIP');
            }
            $zipper->extractTo($tempDir);
            $zipper->close();

            /* ------------------ MANIFEST ------------------ */
            $manifestPath = $tempDir . '/update.json';
            if (!File::exists($manifestPath)) {
                throw new FileNotFoundException('update.json missing');
            }

            $manifest = json_decode(File::get($manifestPath), true, 512, JSON_THROW_ON_ERROR);
            $this->validateManifest($manifest);

            $vendorUpdate = (bool)($manifest['vendor_update'] ?? false);


            if ($vendorUpdate) {
                $this->validateVendorUpdate($tempDir, $appendLog);
            }

            $update->update([
                'version' => $manifest['version'],
                'notes'   => $manifest['notes'] ?? '',
            ]);

            /* ------------------ PREPARE ------------------ */
            File::makeDirectory($backupDir, 0755, true);
            $projectRoot = base_path();

            DB::beginTransaction();

            foreach ($manifest['actions'] as $action) {
                $this->applyAction($action, $tempDir, $backupDir, $projectRoot, $appendLog);
            }

            if (!empty($manifest['run_migrations'])) {
                $this->handleMigrations($tempDir, $appendLog);
            }

            if (!empty($manifest['run_seeders'])) {
                $this->handleSeeders($tempDir, $appendLog);
            }

            DB::commit();

            /* ------------------ POST TASKS ------------------ */
            foreach ($manifest['commands'] ?? [] as $cmd) {
                $this->runSafeArtisan($cmd, $appendLog);
            }

            $this->runSafeArtisan('optimize:clear', $appendLog);

            if ($vendorUpdate) {
                $this->handleVendorUpdate($tempDir, $appendLog);
            }

            /* ------------------ FINALIZE ------------------ */
            $update->update([
                'status'     => 'applied',
                'applied_at' => now(),
                'applied_by' => $userId,
            ]);

            $appendLog('✅ Update applied successfully');

            return $update;
        } catch (\Throwable $e) {
            DB::rollBack();
            $appendLog('❌ FAILED: ' . $e->getMessage());
            $appendLog('Restoring backup...');
            $this->restoreBackup($backupDir, base_path());

            $update->update([
                'status' => 'failed',
                'applied_at' => now(),
                'applied_by' => $userId,
            ]);

            throw $e;
        } finally {
            File::deleteDirectory($tempDir);
            $this->releaseLock();
        }
    }

    /* ========================================================= */

    protected function runSafeArtisan(string $command, callable $log): void
    {
        if (!in_array($command, $this->allowedCommands, true)) {
            $log("Skipped unsafe artisan command: {$command}");
            return;
        }

        $log("Running artisan: {$command}");

        // Find the correct PHP CLI binary
        $phpBinary = $this->getPhpCliBinary();

        $process = new Process([
            $phpBinary,
            base_path('artisan'),
            ...explode(' ', $command),
        ]);

        $process->setTimeout(60);
        $process->run();

        if (!$process->isSuccessful()) {
            $error = $process->getErrorOutput();
            $output = $process->getOutput();

            $fullError = trim($error ?: $output);

            if (empty($fullError)) {
                $fullError = "Command failed with exit code: " . $process->getExitCode();
            }

            $log("ERROR: {$fullError}");
            throw new \RuntimeException($fullError);
        }

        $output = $process->getOutput();
        if ($output) {
            $log($output);
        }

        $log("Completed artisan: {$command}");
    }

    /**
     * Get the correct PHP CLI binary path
     */
    protected function getPhpCliBinary(): string
    {
        // If PHP_BINARY points to php-fpm, find the CLI version
        if (str_contains(PHP_BINARY, 'php-fpm')) {
            // Extract version (e.g., "8.3" from "php-fpm8.3")
            preg_match('/php-fpm(\d+\.\d+)/', PHP_BINARY, $matches);
            $version = $matches[1] ?? '';

            // Try common CLI paths
            $possiblePaths = [
                "/usr/bin/php{$version}",
                "/usr/bin/php",
                "/usr/local/bin/php{$version}",
                "/usr/local/bin/php",
            ];

            foreach ($possiblePaths as $path) {
                if (file_exists($path) && is_executable($path)) {
                    return $path;
                }
            }
        }

        // If PHP_BINARY is already CLI or as fallback
        return PHP_BINARY;
    }
    protected function applyAction(array $action, string $tempDir, string $backupDir, string $root, callable $log): void
    {
        $type   = $action['type'];
        $source = $tempDir . '/' . ltrim($action['source'] ?? '', '/');

        $relativeTarget = ltrim($action['target'], '/');
        // Validate target path safety and allowed roots
        if ($relativeTarget === '' || str_contains($relativeTarget, '..') || str_starts_with($relativeTarget, '/')) {
            throw new \RuntimeException('Invalid target path in update.json');
        }
        $firstSegment = explode('/', $relativeTarget)[0];
        $allowedRoots = ['app', 'routes', 'config', 'resources', 'public', 'database', 'packages', 'bootstrap'];
        if (!in_array($firstSegment, $allowedRoots, true)) {
            throw new \RuntimeException("Target '{$relativeTarget}' is outside allowed roots.");
        }
        if ($type === 'delete') {
            $protectedFiles = ['.env'];
            $protectedRoots = ['vendor', 'storage'];
            if (in_array($relativeTarget, $protectedFiles, true) || in_array($firstSegment, $protectedRoots, true)) {
                throw new \RuntimeException("Deletion of '{$relativeTarget}' is not allowed.");
            }
        }

        $target = $root . '/' . $relativeTarget;

        if (in_array($type, ['replace', 'delete']) && File::exists($target)) {
            $backup = $backupDir . '/' . ltrim($action['target'], '/');
            File::ensureDirectoryExists(dirname($backup));
            File::copyDirectory(dirname($target), dirname($backup));
        }

        if ($type === 'delete') {
            if (File::isDirectory($target)) {
                File::deleteDirectory($target);
                $log("Deleted directory {$target}");
            } else {
                File::delete($target);
                $log("Deleted file {$target}");
            }
            return;
        }

        File::ensureDirectoryExists(dirname($target));
        File::isDirectory($source)
            ? File::copyDirectory($source, $target)
            : File::copy($source, $target);

        $log(ucfirst($type) . " {$target}");
    }

    protected function handleMigrations(string $tempDir, callable $log): void
    {
        $dir = $tempDir . '/database/migrations';
        if (File::isDirectory($dir)) {
            File::copyDirectory($dir, database_path('migrations'));
            Artisan::call('migrate', ['--force' => true]);
            $log('Migrations executed');
        }
    }

    protected function handleSeeders(string $tempDir, callable $log): void
    {
        $dir = $tempDir . '/database/seeders';
        if (File::isDirectory($dir)) {
            File::copyDirectory($dir, database_path('seeders'));
            Artisan::call('db:seed', ['--force' => true]);
            $log('Seeders executed');
        }
    }

    protected function restoreBackup(string $backupDir, string $root): void
    {
        if (File::isDirectory($backupDir)) {
            File::copyDirectory($backupDir, $root);
        }
    }

    protected function validateManifest(array $m): void
    {
        if (empty($m['version']) || empty($m['actions'])) {
            throw new \InvalidArgumentException('Invalid update.json');
        }

        $current = $this->getCurrentVersion();
        $newVersion = (string) $m['version'];
        $minApp = isset($m['min_app_version']) ? (string) $m['min_app_version'] : null;

        if ($minApp !== null && version_compare($current, $minApp, '<')) {
            throw new \RuntimeException("This update requires app version {$minApp} or higher. Current version is {$current}.");
        }

        if (version_compare($newVersion, $current, '<=')) {
            throw new \RuntimeException("Update version {$newVersion} must be greater than current version {$current}.");
        }
    }

    protected function ensureDirs(): void
    {
        foreach ([self::UPDATES_DIR, self::TEMP_DIR, self::BACKUP_DIR] as $dir) {
            File::ensureDirectoryExists(Storage::path($dir));
        }
    }

    protected function acquireLock(): void
    {
        $lock = Storage::path(self::LOCK_FILE);
//        if (File::exists($lock)) {
//            throw new \RuntimeException('Another update is already running.');
//        }
//        File::put($lock, getmypid());
    }

    protected function releaseLock(): void
    {
        @unlink(Storage::path(self::LOCK_FILE));
    }

    protected function validateVendorUpdate(string $tempDir, callable $appendLog): void
    {
        $requiredFiles = [
            'composer.json',
            'composer.lock',
            'vendor.zip',
        ];

        foreach ($requiredFiles as $file) {
            if (!File::exists($tempDir . '/' . $file)) {
                throw new \RuntimeException(
                    "Vendor update enabled but missing required file: {$file}"
                );
            }
        }

        $appendLog('Vendor update validated');
    }

    protected function handleVendorUpdate(string $tempDir, callable $appendLog): void
    {
        $appendLog('Applying vendor update');

        // Replace composer files
        File::copy(
            $tempDir . '/composer.json',
            base_path('composer.json')
        );

        File::copy(
            $tempDir . '/composer.lock',
            base_path('composer.lock')
        );

        // Remove old vendor.zip if exists
        $vendorZipPath = base_path('vendor.zip');
        if (File::exists($vendorZipPath)) {
            File::delete($vendorZipPath);
        }

        // Copy new vendor.zip
        File::copy(
            $tempDir . '/vendor.zip',
            $vendorZipPath
        );

        $appendLog('composer.json, composer.lock and vendor.zip updated');
    }
}
