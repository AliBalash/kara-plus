<?php

namespace Tests\Feature\Livewire\Auth;

use App\Livewire\Pages\Panel\Auth\Login;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_authenticates_user_with_normalized_phone(): void
    {
        $user = User::factory()->create([
            'phone' => '+971500000021',
            'password' => Hash::make('secret123'),
        ]);

        $component = Mockery::mock(Login::class)->makePartial();
        $component->shouldAllowMockingProtectedMethods();

        $component->phone = '0971500000021';
        $component->password = 'secret123';

        $component->shouldReceive('validate')->once()->andReturn([
            'phone' => '0971500000021',
            'password' => 'secret123',
        ]);

        $response = $component->login();

        $this->assertAuthenticatedAs($user);
        $this->assertEquals(route('expert.dashboard'), $response->getTargetUrl());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
