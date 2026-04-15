<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiDocumentEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_validation_returns_json_for_api_requests_without_accept_header(): void
    {
        $response = $this->post('/api/auth/login', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'The given data was invalid.',
            ])
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_upload_requires_a_file_and_returns_json_validation_errors(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->post('/api/upload', [
            'title' => 'Missing file',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'The given data was invalid.',
            ])
            ->assertJsonValidationErrors(['file']);
    }

    public function test_create_document_requires_category_or_category_id_instead_of_failing_with_server_error(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/documents', [
            'title' => 'Metadata only document',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category', 'category_id']);
    }

    public function test_create_document_returns_validation_error_for_invalid_category_id(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/documents', [
            'title' => 'Invalid category',
            'category_id' => 9999,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_put_document_supports_partial_updates(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Original']);
        $document = Document::create([
            'title' => 'Original title',
            'description' => 'Original description',
            'file_path' => 'documents/example.txt',
            'user_id' => $user->user_id,
            'category_id' => $category->category_id,
            'author_name' => 'Author',
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/documents/{$document->document_id}", [
            'title' => 'Updated title',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.title', 'Updated title')
            ->assertJsonPath('data.description', 'Original description');
    }

    public function test_put_document_with_empty_body_returns_validation_feedback(): void
    {
        $user = User::factory()->create();
        $category = Category::create(['name' => 'Reports']);
        $document = Document::create([
            'title' => 'Needs body',
            'description' => null,
            'file_path' => 'documents/example.txt',
            'user_id' => $user->user_id,
            'category_id' => $category->category_id,
            'author_name' => null,
        ]);

        Sanctum::actingAs($user);

        $response = $this->putJson("/api/documents/{$document->document_id}", []);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'The given data was invalid.',
            ])
            ->assertJsonValidationErrors(['body']);
    }
}
