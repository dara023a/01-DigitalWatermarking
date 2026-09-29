<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\PythonEngineException;
use App\Services\PythonWatermarkEngine;
use App\Services\WatermarkStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

final class AttackController extends Controller
{
    public function index(Request $request): View
    {
        $run = $request->session()->get('watermark_run');
        
        // Daftar gambar watermarked yang tersedia di server (dari session)
        $serverImages = [];
        if (is_array($run) && isset($run['watermarked_image'])) {
            $serverImages[] = [
                'name' => 'Hasil Embedding',
                'path' => $run['watermarked_image'],
            ];
        }

        // Daftar file upload user yang tersimpan
        $uploadedImages = [];
        try {
            $storage = app(WatermarkStorageService::class);
            $uploadedImages = $storage->listUserUploads('attack');
        } catch (RuntimeException) {
            // Abaikan error, tampilkan daftar kosong
        }

        return view('attack', [
            'run' => $run,
            'serverImages' => $serverImages,
            'uploadedImages' => $uploadedImages,
        ]);
    }

    public function run(
        Request $request,
        PythonWatermarkEngine $engine,
        WatermarkStorageService $storage,
    ): RedirectResponse {
        $run = $request->session()->get('watermark_run');
        if (! is_array($run) || ! isset($run['id'], $run['watermarked_image'])) {
            return redirect()->route('embedding.index')
                ->withErrors(['engine' => 'Jalankan embedding terlebih dahulu.']);
        }

        // Validasi input
        $validated = $request->validate([
            'source_type' => ['required', Rule::in(['server', 'upload'])],
            'attack_type' => ['required', 'in:jpeg,resize,pure_crop,crop_resize_back,gaussian_noise,brightness_contrast'],
            'quality' => ['required_if:attack_type,jpeg', 'nullable', 'integer', 'min:1', 'max:100'],
            'scale' => ['required_if:attack_type,resize', 'nullable', 'numeric', 'gt:0'],
            'crop_percent' => ['required_if:attack_type,pure_crop,crop_resize_back', 'nullable', 'numeric', 'between:0,99.99'],
            'sigma' => ['required_if:attack_type,gaussian_noise', 'nullable', 'numeric', 'gt:0'],
            'seed' => ['nullable', 'integer'],
            'brightness_alpha' => ['required_if:attack_type,brightness_contrast', 'nullable', 'numeric', 'gt:0'],
            'brightness_beta' => ['required_if:attack_type,brightness_contrast', 'nullable', 'numeric'],
        ]);

        // Validasi tambahan berdasarkan source_type
        if ($validated['source_type'] === 'server') {
            // Validasi server_images jika ada yang dipilih
            $serverImages = $request->input('server_images', []);
            if (empty($serverImages)) {
                return back()->withErrors(['server_images' => 'Pilih minimal satu gambar dari server.'])->withInput();
            }
        } else {
            // Validasi uploaded_images
            $uploadedFiles = $request->file('uploaded_images', []);
            if (empty($uploadedFiles)) {
                return back()->withErrors(['uploaded_images' => 'Upload minimal satu gambar.'])->withInput();
            }
            foreach ($uploadedFiles as $file) {
                if (! $file->isValid()) {
                    return back()->withErrors(['uploaded_images' => 'File upload tidak valid.'])->withInput();
                }
            }
        }

        $attackType = $validated['attack_type'];
        
        // Tentukan parameter attack
        $parameter = match ($attackType) {
            'jpeg' => (int) $validated['quality'],
            'resize' => (float) $validated['scale'],
            'gaussian_noise' => 'sigma=' . $validated['sigma'] . ', seed=' . ($validated['seed'] ?? 'none'),
            'brightness_contrast' => 'alpha=' . $validated['brightness_alpha'] . ', beta=' . $validated['brightness_beta'],
            default => (float) $validated['crop_percent'],
        };

        // Kumpulkan semua gambar yang akan diproses
        $imagesToProcess = [];
        
        if ($validated['source_type'] === 'server') {
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
                    $relativePath = $storage->storeUserUpload($file, 'attack');
                    $imagesToProcess[] = [
                        'name' => $file->getClientOriginName() ?? 'upload',
                        'path' => $storage->userUploadPath($relativePath),
                        'source' => 'upload',
                        'upload_path' => $relativePath,
                    ];
                } catch (RuntimeException $e) {
                    return back()->withErrors(['upload' => $e->getMessage()])->withInput();
                }
            }
        }

        // Proses setiap gambar
        $results = [];
        $hasErrors = false;

        foreach ($imagesToProcess as $image) {
            $outputName = 'outputs/attacked_'.uniqid().'.png';
            
            try {
                $engine->call('attack', [
                    'image_path' => $image['path'],
                    'attack_type' => $attackType,
                    'quality' => $attackType === 'jpeg' ? (int) $validated['quality'] : null,
                    'scale' => $attackType === 'resize' ? (float) $validated['scale'] : null,
                    'crop_percent' => in_array($attackType, ['pure_crop', 'crop_resize_back'], true) ? (float) $validated['crop_percent'] : null,
                    'sigma' => $attackType === 'gaussian_noise' ? (float) $validated['sigma'] : null,
                    'seed' => $attackType === 'gaussian_noise' && isset($validated['seed']) ? (int) $validated['seed'] : null,
                    'brightness_alpha' => $attackType === 'brightness_contrast' ? (float) $validated['brightness_alpha'] : null,
                    'brightness_beta' => $attackType === 'brightness_contrast' ? (float) $validated['brightness_beta'] : null,
                    'output_path' => $storage->path($run['id'], $outputName),
                ]);

                $results[] = [
                    'name' => $image['name'],
                    'source' => $image['source'],
                    'output_path' => $outputName,
                    'success' => true,
                ];
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
        $run['attacked_images'] = $results;
        $run['attack_type'] = $attackType;
        $run['parameter'] = $parameter;
        unset($run['extracted_image']);
        $request->session()->put('watermark_run', $run);

        if ($hasErrors) {
            $successCount = count(array_filter($results, fn ($r) => $r['success']));
            return redirect()->route('attack.index')
                ->with('warning', "Attack selesai dengan beberapa kesalahan. {$successCount}/".count($results)." gambar berhasil diproses.");
        }

        return redirect()->route('attack.index')
            ->with('success', 'Attack selesai. Citra hasilnya siap untuk blind extraction.');
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
