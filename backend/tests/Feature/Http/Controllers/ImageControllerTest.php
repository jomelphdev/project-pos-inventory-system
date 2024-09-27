<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

class ImageControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    /**
     * @test
     */
    public function can_upload_image()
    {
        $image = UploadedFile::fake()->image('test.png');
        $response = $this->post('/api/images/upload', [
            'image' => $image
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'image_url'
        ]);

        $data = $response->json();
        $imagePath = substr($data['image_url'], strpos($data['image_url'], 'images'));


        $this->assertTrue(Storage::disk('s3')->exists($imagePath));

        Storage::disk('s3')->delete($imagePath);
    }
}
