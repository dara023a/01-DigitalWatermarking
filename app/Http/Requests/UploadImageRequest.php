<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'source_type' => ['required', Rule::in(['server', 'upload'])],
        ];

        // Validasi untuk source_type = server
        if ($this->input('source_type') === 'server') {
            $rules['server_images'] = ['required', 'array', 'min:1'];
            $rules['server_images.*'] = ['required', 'string'];
        }

        // Validasi untuk source_type = upload
        if ($this->input('source_type') === 'upload') {
            $rules['uploaded_images'] = ['required', 'array', 'min:1', 'max:10'];
            $rules['uploaded_images.*'] = [
                'required',
                'file',
                'image',
                'mimes:png,jpg,jpeg',
                'max:5120', // 5 MB per file
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'source_type.required' => 'Pilih sumber gambar (server atau upload).',
            'source_type.in' => 'Sumber gambar harus "server" atau "upload".',
            'server_images.required' => 'Pilih minimal satu gambar dari server.',
            'server_images.*.required' => 'Path gambar server tidak valid.',
            'uploaded_images.required' => 'Upload minimal satu gambar.',
            'uploaded_images.max' => 'Maksimal 10 file per request.',
            'uploaded_images.*.image' => 'File harus berupa gambar yang valid.',
            'uploaded_images.*.mimes' => 'Format file harus PNG, JPG, atau JPEG.',
            'uploaded_images.*.max' => 'Ukuran file maksimal 5 MB.',
        ];
    }
}
