<?php

namespace App\Services;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

/**
 * Zips every uploaded file (Spatie Media Library's storage/app/public — property/project photos,
 * brochures, review photos, header/logo uploads, chat attachments, everything under the `public` disk)
 * into a single downloadable archive. This is the other half of a full migration alongside
 * DatabaseBackupService's SQL dump: the database only stores *paths* to these files, never the file
 * bytes themselves, so a DB restore alone leaves every image/PDF on the new system broken until this
 * archive is extracted back into the same storage/app/public location.
 */
class MediaBackupService
{
    public function zipToFile(string $zipPath): void
    {
        $source = storage_path('app/public');

        if (! is_dir($source)) {
            throw new RuntimeException("Nothing to back up — {$source} doesn't exist.");
        }

        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Could not create zip archive at {$zipPath}.");
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                continue;
            }

            $realPath = $file->getRealPath();
            // Store paths relative to storage/app/public so extracting the zip on the new system just
            // means unpacking it straight into that same folder — no path rewriting needed.
            $relativePath = 'public/'.substr($realPath, strlen($source) + 1);

            $zip->addFile($realPath, str_replace('\\', '/', $relativePath));
        }

        $zip->close();
    }

    public function estimatedSizeBytes(): int
    {
        $source = storage_path('app/public');

        if (! is_dir($source)) {
            return 0;
        }

        $size = 0;
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
