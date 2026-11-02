<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiCorsTest extends TestCase
{
    public function test_flutter_web_origin_can_call_the_api(): void
    {
        $this->withHeaders([
            'Origin' => 'http://localhost:5000',
            'Access-Control-Request-Method' => 'POST',
            'Access-Control-Request-Headers' => 'authorization,content-type',
        ])
            ->options('/api/v1/auth/login')
            ->assertStatus(204)
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5000');
    }

    public function test_unconfigured_web_origin_is_not_allowed(): void
    {
        $this->withHeaders([
            'Origin' => 'https://untrusted.example',
            'Access-Control-Request-Method' => 'POST',
        ])
            ->options('/api/v1/auth/login')
            ->assertStatus(204)
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }
}
