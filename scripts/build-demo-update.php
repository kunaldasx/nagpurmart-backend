#!/usr/bin/env php
<?php
declare(strict_types=1);

// Build the demo update ZIP with update.json at the ZIP root.

$projectRoot = __DIR__ . '/..';
$sourceDir = $projectRoot . '/documentation/update-packages/demo-update';
$outputDir = $projectRoot . '/storage/app/updates';
$outputZip = $outputDir . '/demo-update-1.0.1.zip';

if (!is_dir($sourceDir)) {
    fwrite(STDERR, "Source directory not found: $sourceDir\n");
    exit(1);
}

if (!is_dir($outputDir) && !mkdir($outputDir, 0755, true) && !is_dir($outputDir)) {
    fwrite(STDERR, "Failed to create output directory: $outputDir\n");
    exit(1);
}

$zip = new ZipArchive();
if ($zip->open($outputZip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
    fwrite(STDERR, "Cannot open zip for writing: $outputZip\n");
    exit(1);
}

$sourceDirReal = realpath($sourceDir);
$sourceDirLen = strlen($sourceDirReal) + 1; // +1 for trailing slash removal

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($sourceDirReal, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($files as $file) {
    $filePath = $file->getRealPath();
    $localName = substr($filePath, $sourceDirLen); // Make paths relative to sourceDir

    if ($file->isDir()) {
        $zip->addEmptyDir(str_replace('\\', '/', $localName));
    } else {
        $zip->addFile($filePath, str_replace('\\', '/', $localName));
    }
}

$zip->close();

echo "Demo update built: $outputZip\n";
