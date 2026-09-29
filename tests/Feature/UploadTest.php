<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_accepts_valid_image_upload_for_attack(): void
    {
        session()->put('watermark_run', [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'watermarked_image' => 'outputs/watermarked.png',
        ]);

        $file = UploadedFile::fake()->image('watermarked.png', 100, 100);

        $response = $this->post('/attack', [
            'source_type' => 'upload',
            'uploaded_images' => [$file],
            'attack_type' => 'jpeg',
            'quality' => 70,
        ]);

        $response->assertSessionDoesntHaveErrors(['uploaded_images']);
    }

    public function test_rejects_non_image_file_upload_for_attack(): void
    {
        session()->put('watermark_run', [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'watermarked_image' => 'outputs/watermarked.png',
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->post('/attack', [
            'source_type' => 'upload',
            'uploaded_images' => [$file],
            'attack_type' => 'jpeg',
            'quality' => 70,
        ]);

        $response->assertSessionHasErrors(['uploaded_images.0']);
    }

    public function test_rejects_oversized_file_upload_for_attack(): void
    {
        session()->put('watermark_run', [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'watermarked_image' => 'outputs/watermarked.png',
        ]);

        // Buat file yang lebih besar dari 5 MB (5120 KB)
        $file = UploadedFile::fake()->image('large.png')->size(6000);

        $response = $this->post('/attack', [
            'source_type' => 'upload',
            'uploaded_images' => [$file],
            'attack_type' => 'jpeg',
            'quality' => 70,
        ]);

        $response->assertSessionHasErrors(['uploaded_images.0']);
    }

    public function test_accepts_valid_image_upload_for_extraction(): void
    {
        session()->put('watermark_run', [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'metadata_path' => 'outputs/metadata.json',
        ]);

        $file = UploadedFile::fake()->image('attacked.png', 100, 100);

        $response = $this->post('/extraction', [
            'source_type' => 'upload',
            'uploaded_images' => [$file],
            'secret_key' => 'test-key',
            'on_size_mismatch' => 'raise',
        ]);

        $response->assertSessionDoesntHaveErrors(['uploaded_images']);
    }

    public function test_rejects_non_image_file_upload_for_extraction(): void
    {
        session()->put('watermark_run', [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'metadata_path' => 'outputs/metadata.json',
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->post('/extraction', [
            'source_type' => 'upload',
            'uploaded_images' => [$file],
            'secret_key' => 'test-key',
            'on_size_mismatch' => 'raise',
        ]);

        $response->assertSessionHasErrors(['uploaded_images.0']);
    }

    public function test_rejects_oversized_file_upload_for_extraction(): void
    {
        session()->put('watermark_run', [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'metadata_path' => 'outputs/metadata.json',
        ]);

        $file = UploadedFile::fake()->image('large.png')->size(6000);

        $response = $this->post('/extraction', [
            'source_type' => 'upload',
            'uploaded_images' => [$file],
            'secret_key' => 'test-key',
            'on_size_mismatch' => 'raise',
        ]);

        $response->assertSessionHasErrors(['uploaded_images.0']);
    }

    public function test_requires_source_type_selection(): void
    {
        session()->put('watermark_run', [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'watermarked_image' => 'outputs/watermarked.png',
        ]);

        $response = $this->post('/attack', [
            'attack_type' => 'jpeg',
            'quality' => 70,
        ]);

        $response->assertSessionHasErrors(['source_type']);
    }

    public function test_requires_at_least_one_image_when_using_upload_source(): void
    {
        session()->put('watermark_run', [
            'id' => '550e8400-e29b-41d4-a716-446655440000',
            'watermarked_image' => 'outputs/watermarked.png',
        ]);

        $response = $this->post('/attack', [
            'source_type' => 'upload',
            'attack_type' => 'jpeg',
            'quality' => 70,
        ]);

        $response->assertSessionHasErrors(['uploaded_images']);
    }
}
