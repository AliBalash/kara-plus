<?php

namespace Tests\Feature\Audit;

use App\Livewire\Pages\Panel\Expert\Reports\AuditCenterReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class AuditAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_center_is_super_admin_only(): void
    {
        $normalUser = User::factory()->create();

        $this->actingAs($normalUser);
        try {
            app(AuditCenterReport::class)->mount();
            $this->fail('Expected HttpException was not thrown.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }

        Role::findOrCreate('super-admin');
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->actingAs($admin);
        app(AuditCenterReport::class)->mount();
        $this->assertTrue(true);
    }
}
