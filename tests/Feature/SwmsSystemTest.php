<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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

        $response->assertSessionHasErrors([
            'email' => 'Alamat email salah.',
        ]);
        $this->assertGuest();
    }

    public function test_new_user_can_register(): void
    {
        Notification::fake();

        $response = $this->post('/register', [
            'name' => 'Warga Baru',
            'email' => 'wargabaru@swms.com',
            'phone' => '087799881122',
            'address' => 'Jl. Kebersihan No. 10',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/email/verify');
        $this->assertIsInt(session('email_verification_expires_at'));
        $this->assertGreaterThan(
            now()->timestamp,
            session('email_verification_expires_at')
        );
        $this->assertDatabaseHas('users', [
            'email' => 'wargabaru@swms.com',
            'phone' => '087799881122',
            'role' => 'user',
            'email_verified_at' => null,
        ]);
        $this->assertAuthenticated();
        $this->get('/dashboard')->assertRedirect('/email/verify');
        $this->get('/email/verify')->assertOk()->assertSee('Verifikasi email Anda');
        Notification::assertSentTo(
            User::where('email', 'wargabaru@swms.com')->first(),
            VerifyEmailNotification::class
        );
    }

    public function test_signed_email_verification_activates_web_account(): void
    {
        $user = User::factory()->unverified()->create();
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $this->get($verificationUrl)
            ->assertRedirect('/login')
            ->assertSessionHas('success', 'Email berhasil diverifikasi. Silakan masuk ke akun Anda.');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect('/dashboard');
    }

    public function test_unverified_api_account_can_resend_verification_but_cannot_access_reports(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();
        $token = $user->createToken('test-device')->plainTextToken;
        $headers = ['Authorization' => 'Bearer ' . $token, 'Accept' => 'application/json'];

        $this->withHeaders($headers)->getJson('/api/v1/reports')
            ->assertForbidden();

        $this->withHeaders($headers)->postJson('/api/v1/auth/email/verification-notification')
            ->assertStatus(202)
            ->assertJsonPath('success', true);

        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_api_verification_resend_reports_log_mailer_as_not_sent(): void
    {
        Notification::fake();
        config(['mail.default' => 'log']);
        $user = User::factory()->unverified()->create();
        $token = $user->createToken('test-device')->plainTextToken;

        $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/auth/email/verification-notification')
            ->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('email_sent', false);
    }

    public function test_verification_page_explains_that_log_mailer_does_not_send_email(): void
    {
        config(['mail.default' => 'log']);
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/email/verify')
            ->assertOk()
            ->assertSee('tidak dikirim ke inbox')
            ->assertSee('storage/logs/laravel.log');
    }

    public function test_verification_page_does_not_show_log_warning_when_smtp_is_configured(): void
    {
        config(['mail.default' => 'smtp']);
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/email/verify')
            ->assertOk()
            ->assertSee('Tautan verifikasi dikirim ke')
            ->assertDontSee('Mailer aplikasi masih menggunakan mode log')
            ->assertDontSee('tidak dikirim ke inbox');
    }

    public function test_verification_page_shows_a_five_minute_countdown(): void
    {
        $user = User::factory()->unverified()->create();
        $expiresAt = now()->addMinutes(5)->timestamp;

        $this->actingAs($user)
            ->withSession(['email_verification_expires_at' => $expiresAt])
            ->get('/email/verify')
            ->assertOk()
            ->assertSee('role="timer"', false)
            ->assertSee('Tautan verifikasi kedaluwarsa dalam')
            ->assertSee('Tautan berlaku selama 5 menit.');
    }

    public function test_verification_page_shows_expired_state_after_countdown_finishes(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->withSession(['email_verification_expires_at' => now()->subSecond()->timestamp])
            ->get('/email/verify')
            ->assertOk()
            ->assertSee('Tautan verifikasi telah kedaluwarsa.');
    }

    public function test_api_registration_returns_verification_link_and_api_account_is_activated_by_link(): void
    {
        Notification::fake();
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Pendaftar API',
            'email' => 'api.warga@example.com',
            'phone' => '087700001122',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertCreated()
            ->assertJsonPath('data.email_verified', false);

        $user = User::where('email', 'api.warga@example.com')->firstOrFail();
        $verificationUrl = $response->json('data.verification_url');

        $this->assertNotEmpty($verificationUrl);
        $query = [];
        parse_str((string) parse_url($verificationUrl, PHP_URL_QUERY), $query);
        $this->assertSame(
            strtotime($response->json('verification_expires_at')),
            (int) ($query['expires'] ?? 0)
        );
        $this->assertSame(5, config('auth.verification.expire'));
        Notification::assertSentTo($user, VerifyEmailNotification::class);
        $notification = Notification::sent($user, VerifyEmailNotification::class)->first();
        $message = $notification->toMail($user);
        $notificationQuery = [];
        parse_str(
            (string) parse_url($message->actionUrl, PHP_URL_QUERY),
            $notificationQuery
        );
        $this->assertSame($query['expires'], $notificationQuery['expires']);
        $this->assertContains(
            'Tautan ini berlaku selama 5 menit. Jika Anda tidak membuat akun, abaikan email ini.',
            $message->outroLines
        );
        $this->get($verificationUrl)->assertRedirect('/login');
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_changing_email_requires_verification_of_the_new_address(): void
    {
        Notification::fake();
        $user = User::where('email', 'user@swms.com')->firstOrFail();

        $this->actingAs($user)->put('/profile', [
            'name' => $user->name,
            'email' => 'new.address@example.com',
            'phone' => $user->phone,
            'address' => $user->address,
        ])->assertRedirect('/email/verify');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new.address@example.com',
            'email_verified_at' => null,
        ]);
        Notification::assertSentTo($user->fresh(), VerifyEmailNotification::class);
    }

    public function test_email_verification_link_rejects_tampered_and_expired_urls(): void
    {
        $user = User::factory()->unverified()->create();
        $validUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(30),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );

        $this->get(str_replace(sha1($user->email), sha1('other@example.com'), $validUrl))
            ->assertForbidden();
        $this->assertFalse($user->fresh()->hasVerifiedEmail());

        $expiredUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->subMinutes(1),
            ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())]
        );
        $this->get($expiredUrl)->assertForbidden();
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

    public function test_api_documentation_lists_authentication_and_registered_endpoints(): void
    {
        $user = User::where('email', 'user@swms.com')->first();

        $this->actingAs($user)->get('/api-docs')
            ->assertOk()
            ->assertSee('Authorization: Bearer')
            ->assertSee('/auth/profile')
            ->assertSee('/auth/logout')
            ->assertSee('/reports/{report}/status')
            ->assertSee('Tidak ada urutan transisi yang diwajibkan');
    }

    public function test_refreshing_reports_page_does_not_change_saved_reports(): void
    {
        $user = User::where('email', 'user@swms.com')->first();
        $report = Report::where('user_id', $user->id)->firstOrFail();
        $before = [
            'count' => Report::count(),
            'title' => $report->title,
            'status' => $report->status,
            'admin_notes' => $report->admin_notes,
        ];

        $this->actingAs($user)->get('/reports')->assertOk();
        $this->get('/reports')->assertOk();

        $this->assertSame($before['count'], Report::count());
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'title' => $before['title'],
            'status' => $before['status'],
            'admin_notes' => $before['admin_notes'],
        ]);
    }

    public function test_seeding_again_does_not_duplicate_or_overwrite_reports(): void
    {
        $report = Report::where('title', 'Timbunan Botol Plastik dan Gelas Mineral di Dekat Halte')
            ->firstOrFail();
        $report->update(['status' => 'pending', 'admin_notes' => 'Catatan yang sudah disimpan.']);
        $beforeCount = Report::count();
        $beforeLogCount = $report->statusLogs()->count();

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($beforeCount, Report::count());
        $this->assertSame($beforeLogCount, $report->fresh()->statusLogs()->count());
        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'pending',
            'admin_notes' => 'Catatan yang sudah disimpan.',
        ]);
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
        $this->fakeMlPrediction();

        $user = User::where('email', 'user@swms.com')->first();
        $file = UploadedFile::fake()->image('plastic_bottle.jpg', 400, 400);

        $response = $this->actingAs($user)->post('/reports', [
            'title' => 'Botol Plastik di Selokan',
            'description' => 'Banyak botol plastik menyumbat got depan rumah.',
            'waste_category' => 'plastic',
            'ai_detected_category' => 'paper',
            'ai_confidence' => 99,
            'image' => $file,
            'latitude' => -6.2000,
            'longitude' => 106.8166,
            'location_address' => 'Jl. Sudirman, Jakarta',
        ]);
        $response->assertSessionHas('clear_report_draft', true);

        $this->assertDatabaseHas('reports', [
            'title' => 'Botol Plastik di Selokan',
            'waste_category' => 'plastic',
            'status' => 'pending',
        ]);

        $report = Report::where('title', 'Botol Plastik di Selokan')->first();
        $this->assertNotNull($report);
        $this->assertNotNull($report->ai_confidence);
        $this->assertSame('plastic', $report->ai_detected_category);
        $this->assertSame(92.5, (float) $report->ai_confidence);
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
        $this->fakeMlPrediction();
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

    public function test_report_is_not_saved_when_ml_service_is_unavailable(): void
    {
        Storage::fake('public');
        Http::fake([
            '*' => Http::response(['detail' => 'Model is not loaded.'], 503),
        ]);

        $user = User::where('email', 'user@swms.com')->first();
        $response = $this->actingAs($user)->post('/reports', [
            'title' => 'Laporan tanpa layanan AI',
            'description' => 'Laporan ini tidak boleh tersimpan tanpa prediksi.',
            'waste_category' => 'plastic',
            'image' => UploadedFile::fake()->image('plastic.jpg'),
        ]);

        $response->assertSessionHasErrors('image');
        $this->assertDatabaseMissing('reports', [
            'title' => 'Laporan tanpa layanan AI',
        ]);
        $this->assertSame([], Storage::disk('public')->allFiles('reports'));
    }

    public function test_api_report_is_not_saved_when_ml_service_is_unavailable(): void
    {
        Storage::fake('public');
        Http::fake([
            '*' => Http::response(['detail' => 'Model is not loaded.'], 503),
        ]);

        $user = User::where('email', 'user@swms.com')->first();
        $token = $user->createToken('classifier-test')->plainTextToken;
        $response = $this->withToken($token)
            ->withHeader('Accept', 'application/json')
            ->post('/api/v1/reports', [
                'title' => 'Laporan API tanpa layanan AI',
                'description' => 'Laporan ini tidak boleh tersimpan tanpa prediksi.',
                'waste_category' => 'plastic',
                'image' => UploadedFile::fake()->image('plastic.jpg'),
            ]);

        $response->assertStatus(503)
            ->assertJsonPath('success', false);
        $this->assertDatabaseMissing('reports', [
            'title' => 'Laporan API tanpa layanan AI',
        ]);
        $this->assertSame([], Storage::disk('public')->allFiles('reports'));
    }

    private function fakeMlPrediction(): void
    {
        Http::fake([
            '*' => Http::response([
                'success' => true,
                'category' => 'plastic',
                'confidence' => 92.5,
                'breakdown' => [
                    'organic' => 2.5,
                    'plastic' => 92.5,
                    'paper' => 5.0,
                ],
                'recommendation' => [],
            ], 200),
        ]);
    }

    public function test_ai_lab_displays_the_saved_evaluation_report(): void
    {
        $evaluationPath = base_path('machine-learning/artifacts/evaluation.json');
        if (!is_file($evaluationPath)) {
            $this->markTestSkipped('Train the model to generate its evaluation report.');
        }

        $evaluation = json_decode(
            file_get_contents($evaluationPath),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        $admin = User::where('email', 'admin@swms.com')->first();

        $this->actingAs($admin)
            ->get('/ai-lab')
            ->assertOk()
            ->assertSee(number_format($evaluation['test_accuracy'] * 100, 2) . '%')
            ->assertSee('Confusion matrix')
            ->assertSee('Macro average / akurasi');
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

    public function test_api_login_returns_requested_invalid_credentials_message(): void
    {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@swms.com',
            'password' => 'wrong-password',
        ])->assertUnauthorized()
            ->assertJsonPath('message', 'Alamat email salah.');
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
