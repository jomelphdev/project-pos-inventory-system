<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\Manifest;
use App\Models\ManifestItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\UploadedFile;
use JMac\Testing\Traits\AdditionalAssertions;
use Tests\TestCase;

class ManifestControllerTest extends TestCase
{
    use AdditionalAssertions, RefreshDatabase, WithFaker;

    // POST REQUESTS

    /**
     * @test
     */
    public function can_upload_manifest()
    {
        $file = new UploadedFile(
            base_path('resources/files/Test_Manifest.xlsx'), 
            'Test.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->post('/api/manifests/upload', [
            'manifest' => $file,
            'manifest_name' => 'Test Manifest'
        ]);
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'message'
            ]
        ]);

        sleep(5);

        $this->assertDatabaseHas('manifests', ['manifest_name' => 'Test Manifest']);

        $manifest = Manifest::where('manifest_name', 'Test Manifest')->get()->first();
        $manifestItems = ManifestItem::where('manifest_id', $manifest->id)->get()->all();
        
        $this->assertCount(377, $manifestItems);
    }

    /**
     * @test
     */
    public function can_query_manifest_items()
    {
        $manifest = create('Manifest', [
            'organization_id' => $this->organization_id
        ]);
        
        create('ManifestItem', [
            'organization_id' => $this->organization_id,
            'manifest_id' => $manifest->id
        ], 9);
        create('ManifestItem', [
            'organization_id' => $this->organization_id,
            'manifest_id' => $manifest->id,
            'title' => 'The_Weird_Test_Title'
        ]);

        $response = $this->post('/api/manifests/query/' . $manifest->id);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'items'
            ]
        ]);
        $response->assertJsonCount(10, 'data.items');

        $response = $this->post('/api/manifests/query/' . $manifest->id, [
            'query' => 'The_Weird_Test_Title'
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'items'
            ]
        ]);
        $response->assertJsonCount(1, 'data.items');
    }

    // GET REQUESTS

    /**
     * @test
     */
    public function can_get_organization_manifests()
    {
        create('Manifest', [
            'organization_id' => $this->organization_id
        ], 3);

        $response = $this->get('/api/manifests');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'manifests'
            ]
        ]);
        $response->assertJsonCount(3, 'data.manifests');
    }
}
