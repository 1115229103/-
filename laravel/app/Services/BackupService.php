<?php

namespace App\Services;

use App\Models\Backup;
use Illuminate\Support\Facades\Log;

class BackupService
{
    protected string $storagePath;

    public function __construct()
    {
        $this->storagePath = storage_path('backups');
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function create(string $type = 'db'): Backup
    {
        $backup = Backup::create([
            'type'   => $type,
            'status' => 'processing',
        ]);

        try {
            $filename = sprintf('backup-%s-%s.sql', $type, now()->format('Ymd-His'));
            $filepath = $this->storagePath . DIRECTORY_SEPARATOR . $filename;

            $this->runMysqldump($filepath);

            $size = filesize($filepath);
            $backup->update([
                'file_path'       => $filename,
                'file_size_bytes' => $size,
                'status'          => 'completed',
            ]);

            Log::info('Backup completed', ['id' => $backup->id, 'size' => $size]);
        } catch (\Throwable $e) {
            $backup->update([
                'status' => 'failed',
                'error'  => $e->getMessage(),
            ]);
            Log::error('Backup failed', ['id' => $backup->id, 'error' => $e->getMessage()]);
        }

        return $backup->fresh();
    }

    protected function runMysqldump(string $outputPath): void
    {
        $config = config('database.connections.' . config('database.default'));
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';

        $password = $config['password'] ?? '';
        $password = is_string($password) ? $password : '';

        // On Windows, Symfony Process can't inherit the socket environment
        // from the PHP built-in server. Use shell_exec instead.
        $cmd = sprintf(
            '"D:\xampp\mysql\bin\mysqldump.exe" --host=%s --port=%s --user=%s --password=%s --single-transaction --routines --triggers --no-tablespaces --result-file="%s" %s 2>&1',
            escapeshellarg($host),
            escapeshellarg((string)$port),
            escapeshellarg($config['username']),
            escapeshellarg($password),
            $outputPath,
            escapeshellarg($config['database']),
        );

        $error = '';
        $code = 0;
        exec($cmd, result_code: $code);

        if ($code !== 0) {
            $error = file_exists($outputPath) ? file_get_contents($outputPath) : '';
            throw new \RuntimeException('mysqldump failed (code ' . $code . '): ' . ($error ?: 'unknown error'));
        }
    }
}
