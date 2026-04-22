<?php

namespace Tests\Feature\Livewire\Profile;

use App\Livewire\Pages\Panel\Expert\Profile\Profile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_save_updates_authenticated_user_profile(): void
    {
        Storage::fake('myimage');

        $user = User::factory()->create([
            'first_name' => 'Original',
            'last_name' => 'User',
            'email' => 'original@example.com',
        ]);

        $this->actingAs($user);

        $component = Mockery::mock(Profile::class)->makePartial();
        $component->mount();

        $component->first_name = 'Updated';
        $component->last_name = 'Tester';
        $component->email = 'updated@example.com';
        $component->phone = '+971500000123';
        $component->national_code = '1234567890';
        $component->address = 'Dubai';

        $component->shouldReceive('validate')->once()->andReturn([
            'first_name' => 'Updated',
            'last_name' => 'Tester',
            'email' => 'updated@example.com',
            'phone' => '+971500000123',
            'new_avatar' => null,
            'national_code' => '1234567890',
            'address' => 'Dubai',
        ]);

        $component->save();

        $user->refresh();
        $this->assertEquals('Updated', $user->first_name);
        $this->assertEquals('Tester', $user->last_name);
        $this->assertEquals('updated@example.com', $user->email);
        $this->assertEquals('Profile updated successfully.', session('message'));
    }

    public function test_remove_avatar_clears_database_value_and_deletes_file(): void
    {
        Storage::fake('myimage');

        $user = User::factory()->create([
            'avatar' => 'avatars/existing-avatar.webp',
        ]);

        Storage::disk('myimage')->put('avatars/existing-avatar.webp', 'avatar-content');

        $this->actingAs($user);

        Livewire::test(Profile::class)
            ->call('removeAvatar')
            ->assertSet('avatar', null)
            ->assertSet('fileInputVersion', 1);

        $this->assertNull($user->fresh()->avatar);
        Storage::disk('myimage')->assertMissing('avatars/existing-avatar.webp');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
