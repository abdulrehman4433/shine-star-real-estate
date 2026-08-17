<?php

namespace App\Livewire\Admin\Settings;

use App\Livewire\Concerns\Notifies;
use App\Services\DatabaseBackupService;
use App\Services\MediaBackupService;
use Illuminate\Support\Facades\File;
use Livewire\Component;

class BackupManager extends Component
{
    use Notifies;

    public function mount(): void
    {
        // Backups are generated on demand into a private, non-web-accessible scratch folder and streamed
        // straight back as a download — nothing is left sitting in public/ or served by a plain URL.
        File::ensureDirectoryExists(storage_path('app/backups'));
    }

    public function mediaSizeLabel(): string
    {
        $bytes = app(MediaBackupService::class)->estimatedSizeBytes();

        if ($bytes === 0) {
            return 'no uploaded files yet';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1).' '.$units[$i].' (approx.)';
    }

    public function downloadDatabase()
    {
        $this->raiseResourceLimitsForBackup();

        $filename = 'shine-star-marketing-db-'.now()->format('Y-m-d_His').'.sql';
        $path = storage_path('app/backups/'.$filename);

        try {
            app(DatabaseBackupService::class)->dumpToFile($path);
        } catch (\Throwable $e) {
            $this->notifyError('Backup failed: '.$e->getMessage());

            return;
        }

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function downloadMedia()
    {
        $this->raiseResourceLimitsForBackup();

        $filename = 'shine-star-marketing-media-'.now()->format('Y-m-d_His').'.zip';
        $path = storage_path('app/backups/'.$filename);

        try {
            app(MediaBackupService::class)->zipToFile($path);
        } catch (\Throwable $e) {
            $this->notifyError('Media backup failed: '.$e->getMessage());

            return;
        }

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    /** A real web request already has Livewire/Blade/session framework overhead eating into PHP's
     *  default 128M memory_limit before either backup service even starts — that headroom doesn't exist
     *  in a bare CLI/tinker script, which is exactly why this shipped once already (verified via tinker,
     *  which had the whole 128M to itself, then failed for real users hitting it through the actual admin
     *  page). PHP's "Allowed memory size exhausted" fatal is NOT a catchable Throwable — no try/catch
     *  around the dump/zip calls below can recover from it after the fact, so the limit has to be raised
     *  *before* calling into either service. Also raises the execution time limit — dumping/zipping a
     *  large, real production database/media folder can legitimately take longer than the default 30s.
     *  ini_set()/set_time_limit() are silently no-ops (return false, don't throw) on hosts that disable
     *  them, so this is always safe to call even if it doesn't take effect everywhere. */
    private function raiseResourceLimitsForBackup(): void
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(300);
    }

    public function render()
    {
        return view('livewire.admin.settings.backup-manager')
            ->extends('admin.layouts.app')
            ->section('content');
    }
}
