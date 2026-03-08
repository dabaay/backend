<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupController extends Controller
{
    protected $backupRoot;

    public function __construct()
    {
        // Path to backups folder relative to project root
        $this->backupRoot = base_path('../backups');
    }

    public function index()
    {
        $backups = [];
        $folders = ['daily', 'weekly', 'monthly'];

        foreach ($folders as $folder) {
            $path = $this->backupRoot . '/' . $folder;
            if (File::exists($path)) {
                $files = File::files($path);
                foreach ($files as $file) {
                    $backups[] = [
                        'folder' => $folder,
                        'filename' => $file->getFilename(),
                        'size' => round($file->getSize() / 1024 / 1024, 2) . ' MB',
                        'at' => date('Y-m-d H:i:s', $file->getMTime()),
                        'url' => route('backups.download', ['folder' => $folder, 'filename' => $file->getFilename()])
                    ];
                }
            }
        }

        // Sort by date descending
        usort($backups, function ($a, $b) {
            return strcmp($b['at'], $a['at']);
        });

        return response()->json($backups);
    }

    public function download($folder, $filename)
    {
        $path = $this->backupRoot . '/' . $folder . '/' . $filename;

        if (!File::exists($path)) {
            return response()->json(['message' => 'Backup file not found.'], 404);
        }

        return response()->download($path);
    }

    public function runBackup()
    {
        $scriptPath = base_path('../scripts/backup.ps1');
        
        if (!File::exists($scriptPath)) {
            return response()->json(['message' => 'Backup script not found at ' . $scriptPath], 404);
        }

        // Run powershell script
        // Note: This might take a while, so we increase timeout
        $command = "powershell.exe -ExecutionPolicy Bypass -File \"$scriptPath\" manual";
        
        try {
            $output = shell_exec($command);
            return response()->json([
                'message' => 'Backup process initiated.',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error running backup.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
