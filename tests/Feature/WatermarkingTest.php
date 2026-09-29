<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;

it('can display the homepage', function () {
    $response = $this->get('/');
    $response->assertStatus(200);
    $response->assertSee('Spectra');
});

it('can display the embedding page', function () {
    $response = $this->get('/embedding');
    $response->assertStatus(200);
    $response->assertSee('Embedding');
});

it('can display the attack page', function () {
    $response = $this->get('/attack');
    $response->assertStatus(200);
    $response->assertSee('Attack');
});

it('can display the extraction page', function () {
    $response = $this->get('/extraction');
    $response->assertStatus(200);
    $response->assertSee('Extraction');
});

it('can display the evaluation page', function () {
    $response = $this->get('/evaluation');
    $response->assertStatus(200);
    $response->assertSee('Evaluation');
});

it('requires original image for embedding', function () {
    $response = $this->post('/embedding', [
        'watermark_image' => UploadedFile::fake()->image('watermark.png'),
        'secret_key' => 'test-key',
        'alpha' => 5,
    ]);
    
    $response->assertSessionHasErrors(['original_image']);
});

it('requires watermark image for embedding', function () {
    $response = $this->post('/embedding', [
        'original_image' => UploadedFile::fake()->image('photo.jpg'),
        'secret_key' => 'test-key',
        'alpha' => 5,
    ]);
    
    $response->assertSessionHasErrors(['watermark_image']);
});

it('requires secret key for embedding', function () {
    $response = $this->post('/embedding', [
        'original_image' => UploadedFile::fake()->image('photo.jpg'),
        'watermark_image' => UploadedFile::fake()->image('watermark.png'),
        'alpha' => 5,
    ]);
    
    $response->assertSessionHasErrors(['secret_key']);
});

it('requires alpha greater than 0', function () {
    $response = $this->post('/embedding', [
        'original_image' => UploadedFile::fake()->image('photo.jpg'),
        'watermark_image' => UploadedFile::fake()->image('watermark.png'),
        'secret_key' => 'test-key',
        'alpha' => 0,
    ]);
    
    $response->assertSessionHasErrors(['alpha']);
});

it('redirects to embedding when accessing attack without run', function () {
    $response = $this->post('/attack', [
        'attack_type' => 'jpeg',
        'quality' => 70,
    ]);
    
    $response->assertRedirect('/embedding');
});

it('redirects to embedding when accessing extraction without run', function () {
    $response = $this->post('/extraction', [
        'secret_key' => 'test-key',
        'on_size_mismatch' => 'raise',
    ]);
    
    $response->assertRedirect('/embedding');
});
