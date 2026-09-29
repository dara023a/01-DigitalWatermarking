<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PythonEngineException;
use App\Services\PythonWatermarkEngine;
use App\Services\WatermarkStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

final class ExtractionController extends Controller
{
    public function index(Request $request): View
    {
        $run = $request->session()->get('watermark_run');
        $defaultMismatch = match ($run['attack_type'] ?? null) {
            'resize' => 'resize',
            'pure_crop' => 'centered_crop',
            default => 'raise',
        };

        // Daftar gambar attacked yang tersedia di server (dari session)
        $serverImages = [];
        if (is_array($run) && isset($run['attacked_image'])) {
            $serverImages[] = [
                'name' => 'Hasil Attack',
                'path' => $run['attacked_image'],
            ];
        }
        // Tambahkan dari attacked_images (batch)
        if (is_array($run) && isset($run['attacked_images']) && is_array($run['attacked_images'])) {
            foreach ($run['attacked_images'] as $attacked) {
                if (isset($attacked['output_path']) && $attacked['success']) {
                    $serverImages[] = [
                        'name' => $attacked['name'] ?? basename($attacked['output_path']),
                        'path' => $attacked['output_path'],
                    ];
                }
            }
        }

        // Daftar file upload user yang tersimpan
        $uploadedImages = [];
        try {
            $storage = app(WatermarkStorageService::class);
            $uploadedImages = $storage->listUserUploads('extraction');
        } catch (RuntimeException) {
            // Abaikan error, tampilkan daftar kosong
        }

        return view('extraction', compact('run', 'defaultMismatch', 'serverImages', 'uploadedImages'));
    }

    public function run(
        Request $request,
        PythonWatermarkEngine $engine,
        WatermarkStorageService $storage,
    ): RedirectResponse {
        $run = $request->session()->get('watermark_run');
        if (! is_array($run)
            || ! isset($run['id'], $run['metadata_path'])) {
            return redirect()->route('embedding.index')
                ->withErrors(['engine' => 'Selesaikan embedding dan attack sebelum extraction.']);
        }

        // Validasi input dasar
        $validated = $request->validate([
            'source_type' => ['required', 'in:server,upload'],
            'secret_key' => ['required', 'string', 'min:1', 'max:4096'],
            'on_size_mismatch' => ['required', 'in:raise,resize,centered_crop'],
            'watermark_image' => ['nullable', 'file', 'image', 'mimes:png,jpg,jpeg', 'max:5120'],
        ]);

        // Validasi tambahan berdasarkan source_type
        if ($validated['source_type'] === 'server') {
            $request->validate([
                'server_images' => ['required', 'array', 'min:1'],
                'server_images.*' => ['required', 'string'],
            ]);
        } else {
            $request->validate([
                'uploaded_images' => ['required', 'array', 'min:1', 'max:10'],
                'uploaded_images.*' => ['required', 'file', 'image', 'mimes:png,jpg,jpeg', 'max:5120'],
            ]);
        }

        $sourceType = $validated['source_type'];
        $secretKey = $validated['secret_key'];
        $onSizeMismatch = $validated['on_size_mismatch'];

        // Kumpulkan semua gambar yang akan diproses
        $imagesToProcess = [];
        
        if ($sourceType === 'server') {
            // Gunakan gambar dari server (perilaku lama)
            foreach ($request->input('server_images', []) as $imagePath) {
                $imagesToProcess[] = [
                    'name' => basename($imagePath),
                    'path' => $storage->path($run['id'], $imagePath),
                    'source' => 'server',
                ];
            }
        } else {
            // Gunakan gambar upload user
            $uploadedFiles = $request->file('uploaded_images', []);
            foreach ($uploadedFiles as $file) {
                try {
                    $relativePath = $storage->storeUserUpload($file, 'extraction');
                    $imagesToProcess[] = [
                        'name' => $file->getClientOriginalName() ?? 'upload',
                        'path' => $storage->userUploadPath($relativePath),
                        'source' => 'upload',
                        'upload_path' => $relativePath,
                    ];
                } catch (RuntimeException $e) {
                    return back()->withErrors(['upload' => $e->getMessage()])->withInput();
                }
            }
        }

        // Upload watermark asli (opsional) untuk perhitungan NC/BER
        $watermarkPath = null;
        $hasOriginalWatermark = false;
        if ($request->hasFile('watermark_image')) {
            try {
                $watermarkRelative = $storage->storeUserUpload($request->file('watermark_image'), 'extraction');
                $watermarkPath = $storage->userUploadPath($watermarkRelative);
                $hasOriginalWatermark = true;
            } catch (RuntimeException $e) {
                return back()->withErrors(['upload' => $e->getMessage()])->withInput();
            }
        }

        // Proses setiap gambar
        $results = [];
        $hasErrors = false;

        foreach ($imagesToProcess as $image) {
            $extractedName = 'outputs/extracted_'.uniqid().'.png';
            
            try {
                // Extraction (BLIND - tidak pakai original image)
                $engine->call('extract', [
                    'image_path' => $image['path'],
                    'metadata_path' => $storage->path($run['id'], $run['metadata_path']),
                    'secret_key' => $secretKey,
                    'extracted_image_path' => $storage->path($run['id'], $extractedName),
                    'on_size_mismatch' => $onSizeMismatch,
                ]);

                $result = [
                    'name' => $image['name'],
                    'source' => $image['source'],
                    'extracted_path' => $extractedName,
                    'success' => true,
                ];

                // Hitung NC/BER jika watermark asli tersedia
                // Catatan: PSNR/SSIM tidak dihitung karena extraction bersifat blind
                if ($hasOriginalWatermark && $watermarkPath !== null) {
                    try {
                        // Load watermark asli dan extracted untuk perhitungan NC/BER
                        $evaluation = $engine->call('evaluate', [
                            'original_image_path' => $image['path'],
                            'watermarked_image_path' => $image['path'],
                            'watermark_image_path' => $watermarkPath,
                            'extracted_image_path' => $storage->path($run['id'], $extractedName),
                            'metadata_path' => $storage->path($run['id'], $run['metadata_path']),
                            'threshold' => 127,
                            'attack_type' => $run['attack_type'] ?? 'none',
                            'parameter' => $run['parameter'] ?? null,
                        ]);

                        $metrics = $evaluation['metrics'];
                        $result['ncc'] = $metrics['ncc'];
                        $result['ber'] = $metrics['ber'];
                        $result['psnr'] = null; // Tidak dihitung dalam mode blind
                        $result['ssim'] = null; // Tidak dihitung dalam mode blind
                    } catch (PythonEngineException $e) {
                        $result['ncc'] = null;
                        $result['ber'] = null;
                        $result['psnr'] = null;
                        $result['ssim'] = null;
                        $result['metrics_error'] = $e->getMessage();
                    }
                } else {
                    $result['ncc'] = null;
                    $result['ber'] = null;
                    $result['psnr'] = null;
                    $result['ssim'] = null;
                }

                $results[] = $result;
            } catch (PythonEngineException $exception) {
                $results[] = [
                    'name' => $image['name'],
                    'source' => $image['source'],
                    'success' => false,
                    'error' => $exception->getMessage(),
                ];
                $hasErrors = true;
            } catch (RuntimeException $exception) {
                $results[] = [
                    'name' => $image['name'],
                    'source' => $image['source'],
                    'success' => false,
                    'error' => $exception->getMessage(),
                ];
                $hasErrors = true;
            }
        }

        // Simpan hasil ke session
        $run['extracted_images'] = $results;
        $run['has_original_watermark'] = $hasOriginalWatermark;
        $request->session()->put('watermark_run', $run);

        // Simpan metrics untuk halaman evaluation
        $metricsRows = [];
        foreach ($results as $result) {
            if (! $result['success']) {
                $metricsRows[] = [
                    'created_at' => now()->toIso8601String(),
                    'file_name' => $result['name'],
                    'attack_type' => $run['attack_type'] ?? 'none',
                    'parameter' => $run['parameter'] ?? '—',
                    'ncc' => 'N/A',
                    'ber' => 'N/A',
                    'psnr' => 'N/A',
                    'ssim' => 'N/A',
                    'status' => 'Gagal',
                    'status_class' => 'err',
                    'error' => $result['error'] ?? 'Unknown error',
                ];
                continue;
            }

            $nc = $result['ncc'] ?? null;
            $ber = $result['ber'] ?? null;
            
            if ($nc === null) {
                $status = 'N/A';
                $statusClass = 'warn';
            } else {
                $nc = (float) $nc;
                $threshDetected = (float) config('watermark.nc_threshold.detected', 0.75);
                $threshWeak = (float) config('watermark.nc_threshold.weak', 0.40);
                
                if ($nc >= $threshDetected) {
                    $status = 'Terdeteksi';
                    $statusClass = 'ok';
                } elseif ($nc >= $threshWeak) {
                    $status = 'Melemah';
                    $statusClass = 'warn';
                } else {
                    $status = 'Gagal';
                    $statusClass = 'err';
                }
            }

            $metricsRows[] = [
                'created_at' => now()->toIso8601String(),
                'file_name' => $result['name'],
                'attack_type' => $run['attack_type'] ?? 'none',
                'parameter' => $run['parameter'] ?? '—',
                'ncc' => $nc !== null ? number_format((float) $nc, 4) : 'N/A',
                'ber' => $ber !== null ? number_format((float) $ber, 4) : 'N/A',
                'psnr' => isset($result['psnr']) ? number_format((float) $result['psnr'], 2) : 'N/A',
                'ssim' => isset($result['ssim']) ? number_format((float) $result['ssim'], 4) : 'N/A',
                'status' => $status,
                'status_class' => $statusClass,
            ];
        }
        $request->session()->put('watermark_metrics', $metricsRows);

        if ($hasErrors) {
            $successCount = count(array_filter($results, fn ($r) => $r['success']));
            return redirect()->route('extraction.index')
                ->with('warning', "Extraction selesai dengan beberapa kesalahan. {$successCount}/".count($results)." gambar berhasil diproses.");
        }

        return redirect()->route('extraction.index')
            ->with('success', 'Blind extraction selesai. NC, BER, PSNR, dan SSIM dihitung oleh engine Python.');
    }

    /**
     * Hapus file upload user.
     */
    public function deleteUpload(
        Request $request,
        WatermarkStorageService $storage,
    ): RedirectResponse {
        $path = $request->input('path');
        
        if (! is_string($path) || empty($path)) {
            return back()->withErrors(['error' => 'Path file tidak valid.']);
        }

        try {
            if ($storage->deleteUserUpload($path)) {
                return back()->with('success', 'File upload berhasil dihapus.');
            }
            return back()->withErrors(['error' => 'File tidak ditemukan.']);
        } catch (RuntimeException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
