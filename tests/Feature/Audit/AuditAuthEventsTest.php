<?php

namespace Tests\Feature\Audit;

use App\Livewire\Components\Panel\Header;
use App\Livewire\Pages\Panel\Auth\Login;
use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditAuthEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_success_and_failure_and_logout_are_logged(): void
    {
        $user = User::factory()->create([
            'phone' => '+989121234567',
            'password' => Hash::make('secret123'),
        ]);

        $login = app(Login::class);
        $login->phone = '+989121234567';
        $login->password = 'secret123';
        $login->remember = true;
        $login->login();

        $this->assertDatabaseHas('audit_events', [
            'action' => 'auth_login_success',
            'actor_user_id' => $user->id,
        ]);

        $failedLogin = app(Login::class);
        $failedLogin->phone = '+989121234567';
        $failedLogin->password = 'wrong-pass';
        $failedLogin->login();

        $this->assertDatabaseHas('audit_events', [
            'action' => 'auth_login_failed',
        ]);

        $this->actingAs($user);
        app(Header::class)->logout();

        $this->assertDatabaseHas('audit_events', [
            'action' => 'auth_logout',
            'actor_user_id' => $user->id,
        ]);

        $this->assertGreaterThanOrEqual(3, AuditEvent::count());
    }
}
