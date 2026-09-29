<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

final class WatermarkStorageService
{
    public function createRun(): array
    {
        $runId = (string) Str::uuid();
        $directory = $this->directory($runId);
        foreach (['inputs', 'outputs'] as $child) {
            if (! is_dir($directory.DIRECTORY_SEPARATOR.$child)
                && ! mkdir($directory.DIRECTORY_SEPARATOR.$child, 0700, true)
                && ! is_dir($directory.DIRECTORY_SEPARATOR.$child)) {
                throw new RuntimeException('Tidak dapat menyiapkan penyimpanan run watermark.');
            }
        }

        return ['id' => $runId, 'directory' => $directory];
    }

    public function directory(string $runId): string
    {
        if (! Str::isUuid($runId)) {
            throw new RuntimeException('Run watermark tidak valid atau sudah kedaluwarsa.');
        }

        return storage_path('app/private/watermark-runs/'.$runId);
    }

    public function path(string $runId, string $relativePath): string
    {
        if (! preg_match('/\A[a-zA-Z0-9_-]+(?:\/[a-zA-Z0-9_-]+)*\.[a-zA-Z0-9]+\z/', $relativePath)) {
            throw new RuntimeException('Nama artefak watermark tidak valid.');
        }

        return $this->directory($runId).DIRECTORY_SEPARATOR
            .str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
    }

    public function storeUpload(UploadedFile $file, string $runId, string $name): string
    {
        $relativePath = 'inputs/'.$name.'.'.$file->extension();
        $path = $this->path($runId, $relativePath);
        $directory = dirname($path);

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Tidak dapat menyimpan file upload secara privat.');
        }

        $file->move($directory, basename($path));

        return $relativePath;
    }

    /**
     * Simpan file upload user ke folder terpisah di luar watermark-runs.
     * Nama file menggunakan UUID untuk mencegah path traversal dan konflik nama.
     * 
     * @return string Path relatif terhadap storage/app/uploads/
     */
    public function storeUserUpload(UploadedFile $file, string $category): string
    {
        if (! in_array($category, ['attack', 'extraction'], true)) {
            throw new RuntimeException('Kategori upload tidak valid.');
        }

        $extension = strtolower($file->extension());
        if (! in_array($extension, ['png', 'jpg', 'jpeg'], true)) {
            throw new RuntimeException('Format file tidak didukung. Gunakan PNG, JPG, atau JPEG.');
        }

        $filename = (string) Str::uuid().'.'.$extension;
        $relativePath = $category.'/'.$filename;
        $directory = storage_path('app/uploads/'.$category);

        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Tidak dapat menyimpan file upload.');
        }

        $file->move($directory, $filename);

        return $relativePath;
    }

    /**
     * Dapatkan path absolut untuk file upload user.
     */
    public function userUploadPath(string $relativePath): string
    {
        // Validasi path untuk mencegah path traversal
        if (! preg_match('/\A(attack|extraction)\/[a-f0-9-]{36}\.(png|jpg|jpeg)\z/i', $relativePath)) {
            throw new RuntimeException('Path file upload tidak valid.');
        }

        return storage_path('app/uploads/'.$relativePath);
    }

    /**
     * Daftar semua file upload user dalam kategori tertentu.
     * 
     * @return array<int, array{path: string, name: string, size: int, modified: int}>
     */
    public function listUserUploads(string $category): array
    {
        if (! in_array($category, ['attack', 'extraction'], true)) {
            throw new RuntimeException('Kategori upload tidak valid.');
        }

        $directory = storage_path('app/uploads/'.$category);
        if (! is_dir($directory)) {
            return [];
        }

        $files = [];
        foreach (glob($directory.'/*') as $file) {
            if (is_file($file)) {
                $files[] = [
                    'path' => $category.'/'.basename($file),
                    'name' => basename($file),
                    'size' => filesize($file),
                    'modified' => filemtime($file),
                ];
            }
        }

        // Urutkan berdasarkan waktu modifikasi (terbaru dulu)
        usort($files, fn ($a, $b) => $b['modified'] <=> $a['modified']);

        return $files;
    }

    /**
     * Hapus file upload user.
     */
    public function deleteUserUpload(string $relativePath): bool
    {
        $path = $this->userUploadPath($relativePath);
        
        if (! is_file($path)) {
            return false;
        }

        return unlink($path);
    }

    /**
     * Bersihkan file upload yang lebih lama dari batas waktu tertentu.
     * Dipanggil oleh scheduler harian.
     * 
     * @return int Jumlah file yang dihapus
     */
    public function cleanupOldUploads(int $maxAgeHours = 24): int
    {
        $deleted = 0;
        $now = time();

        foreach (['attack', 'extraction'] as $category) {
            $directory = storage_path('app/uploads/'.$category);
            if (! is_dir($directory)) {
                continue;
            }

            foreach (glob($directory.'/*') as $file) {
                if (is_file($file) && ($now - filemtime($file)) > ($maxAgeHours * 3600)) {
                    if (unlink($file)) {
                        $deleted++;
                    }
                }
            }
        }

        return $deleted;
    }
}
