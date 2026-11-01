<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_without_email_validation(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'prenoms' => 'Platform',
            'email' => 'admin@test.bj',
            'password' => Hash::make('secret123'),
            'telephone' => '+22997000001',
            'role' => 'admin',
            'is_verified' => true,
            'is_valide' => false, // deliberately false to test auto-bypass
            'statut_verification' => 'valide',
            'profile_completed' => true,
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@test.bj',
            'password' => 'secret123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['access_token', 'user']);

        $admin->refresh();
        $this->assertTrue((bool) $admin->is_valide);
    }
}
