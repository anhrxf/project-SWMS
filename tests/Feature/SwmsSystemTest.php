<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SwmsSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/dashboard');

        $dashResponse = $this->get('/dashboard');
        $dashResponse->assertRedirect('/login');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@swms.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@swms.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_new_user_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Warga Baru',
            'email' => 'wargabaru@swms.com',
            'phone' => '087799881122',
            'address' => 'Jl. Kebersihan No. 10',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', [
            'email' => 'wargabaru@swms.com',
            'phone' => '087799881122',
            'role' => 'user',
        ]);
        $this->assertAuthenticated();
    }

    public function test_cannot_register_with_duplicate_phone(): void
    {
        $existing = User::where('email', 'user@swms.com')->first();

        $response = $this->post('/register', [
            'name' => 'Warga Kloning HP',
            'email' => 'kloninghp@swms.com',
            'phone' => $existing->phone, // Same phone as user@swms.com
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'phone' => 'Nomor telah terdaftar.',
        ]);
        $this->assertDatabaseMissing('users', [
            'email' => 'kloninghp@swms.com',
        ]);
    }

    public function test_cannot_register_with_duplicate_email(): void
    {
        $existing = User::where('email', 'user@swms.com')->first();

        $response = $this->post('/register', [
            'name' => 'Warga Kloning Email',
            'email' => $existing->email, // Same email
            'phone' => '089911223344',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_cannot_register_with_invalid_email(): void
    {
        $response = $this->post('/register', [
            'name' => 'Format Email Rusak',
            'email' => 'bukan-email-valid',
            'phone' => '089911223399',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Email tidak valid.',
        ]);
    }

    public function test_admin_can_access_dashboard_and_users(): void
    {
        $admin = User::where('email', 'admin@swms.com')->first();
        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertStatus(200);

        $usersResponse = $this->actingAs($admin)->get('/users');
        $usersResponse->assertStatus(200);
    }

    public function test_regular_user_cannot_access_user_management(): void
    {
        $user = User::where('email', 'user@swms.com')->first();
        $response = $this->actingAs($user)->get('/users');
        $response->assertRedirect('/dashboard');
        $response->assertSessionHas('error');
    }

    public function test_user_can_create_report_with_image_and_ai_detection(): void
    {
        Storage::fake('public');

        $user = User::where('email', 'user@swms.com')->first();
        $file = UploadedFile::fake()->image('plastic_bottle.jpg', 400, 400);

        $response = $this->actingAs($user)->post('/reports', [
            'title' => 'Botol Plastik di Selokan',
            'description' => 'Banyak botol plastik menyumbat got depan rumah.',
            'waste_category' => 'plastic',
            'image' => $file,
            'latitude' => -6.2000,
            'longitude' => 106.8166,
            'location_address' => 'Jl. Sudirman, Jakarta',
        ]);

        $this->assertDatabaseHas('reports', [
            'title' => 'Botol Plastik di Selokan',
            'waste_category' => 'plastic',
            'status' => 'pending',
        ]);

        $report = Report::where('title', 'Botol Plastik di Selokan')->first();
        $this->assertNotNull($report);
        $this->assertNotNull($report->ai_confidence);
        $this->assertDatabaseHas('report_status_logs', [
            'report_id' => $report->id,
            'new_status' => 'pending',
        ]);
    }

    public function test_admin_can_update_report_status_with_notes(): void
    {
        $admin = User::where('email', 'admin@swms.com')->first();
        $report = Report::where('status', 'pending')->first();

        $response = $this->actingAs($admin)->post("/reports/{$report->id}/status", [
            'status' => 'completed',
            'admin_notes' => 'Sampah telah diangkut oleh tim kebersihan.',
        ]);

        $response->assertRedirect("/reports/{$report->id}");
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'completed',
            'admin_notes' => 'Sampah telah diangkut oleh tim kebersihan.',
        ]);

        $this->assertDatabaseHas('report_status_logs', [
            'report_id' => $report->id,
            'user_id' => $admin->id,
            'new_status' => 'completed',
        ]);
    }

    public function test_ai_classify_endpoint(): void
    {
        $file = UploadedFile::fake()->image('leaves.jpg', 300, 300);

        $response = $this->post('/api/v1/ai/classify', [
            'image' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'category',
                'confidence',
                'label',
                'breakdown',
                'recommendation',
            ],
        ]);
    }

    public function test_api_v1_auth_and_reports(): void
    {
        // Login via API
        $loginRes = $this->postJson('/api/v1/auth/login', [
            'email' => 'user@swms.com',
            'password' => 'password',
        ]);

        $loginRes->assertStatus(200);
        $token = $loginRes->json('data.token');
        $this->assertNotEmpty($token);

        // Fetch reports via API
        $reportsRes = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/reports');

        $reportsRes->assertStatus(200);
        $reportsRes->assertJsonStructure([
            'success',
            'data' => [
                'data',
            ],
        ]);
    }

    public function test_admin_cannot_create_report(): void
    {
        Storage::fake('public');

        $admin = User::where('email', 'admin@swms.com')->first();
        
        // Admin gets redirected if visiting create form
        $createPageResponse = $this->actingAs($admin)->get('/reports/create');
        $createPageResponse->assertRedirect('/reports');
        $createPageResponse->assertSessionHas('error');

        // Admin gets blocked if attempting to POST report
        $file = UploadedFile::fake()->image('waste.jpg', 300, 300);
        $postResponse = $this->actingAs($admin)->post('/reports', [
            'title' => 'Admin Report Test',
            'description' => 'Admin trying to create report',
            'waste_category' => 'plastic',
            'image' => $file,
        ]);
        $postResponse->assertRedirect('/reports');
        $postResponse->assertSessionHas('error');

        $this->assertDatabaseMissing('reports', [
            'title' => 'Admin Report Test',
        ]);
    }

    public function test_admin_cannot_edit_citizen_report(): void
    {
        $admin = User::where('email', 'admin@swms.com')->first();
        $citizenReport = Report::where('status', 'pending')->first();

        $response = $this->actingAs($admin)->get("/reports/{$citizenReport->id}/edit");
        $response->assertStatus(403);
    }
}
