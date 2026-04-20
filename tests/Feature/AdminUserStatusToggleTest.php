<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminUserStatusToggleTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::create(['name' => 'view backend']);
        Permission::create(['name' => 'manage settings']);
        Permission::create(['name' => 'manage users']);

        $adminRole = Role::create(['name' => 'admin']);
        $adminRole->givePermissionTo(['view backend', 'manage settings', 'manage users']);

        $managerRole = Role::create(['name' => 'user-manager']);
        $managerRole->givePermissionTo(['view backend', 'manage settings', 'manage users']);

        $this->actor = User::factory()->createOne(['status' => 'active']);
        $this->actor->assignRole('user-manager');
    }

    /** @test */
    public function authorized_user_can_deactivate_a_user()
    {
        $target = User::factory()->createOne(['status' => 'active']);

        $response = $this->actingAs($this->actor)->patchJson("/admin/users/{$target->id}/status", [
            'status' => 'inactive',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'status' => 'inactive',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'status' => 'inactive',
        ]);
    }

    /** @test */
    public function action_is_audited_with_causer_and_subject()
    {
        $target = User::factory()->createOne(['status' => 'active']);

        $this->actingAs($this->actor)->patchJson("/admin/users/{$target->id}/status", [
            'status' => 'inactive',
        ])->assertOk();

        $this->assertDatabaseHas(config('activitylog.table_name'), [
            'description' => 'User status changed',
            'causer_id' => $this->actor->id,
            'subject_id' => $target->id,
            'subject_type' => User::class,
        ]);

        $this->assertSame(
            1,
            Activity::where('description', 'User status changed')
                ->where('causer_id', $this->actor->id)
                ->where('subject_id', $target->id)
                ->count()
        );
    }

    /** @test */
    public function user_without_manage_users_permission_is_forbidden()
    {
        $viewerRole = Role::create(['name' => 'viewer']);
        $viewerRole->givePermissionTo(['view backend', 'manage settings']);

        $viewer = User::factory()->createOne(['status' => 'active']);
        $viewer->assignRole('viewer');
        assert($viewer instanceof User);

        $target = User::factory()->createOne(['status' => 'active']);

        $this->actingAs($viewer)->patchJson("/admin/users/{$target->id}/status", [
            'status' => 'inactive',
        ])->assertForbidden();
    }

    /** @test */
    public function cannot_deactivate_self()
    {
        $this->actingAs($this->actor)->patchJson("/admin/users/{$this->actor->id}/status", [
            'status' => 'inactive',
        ])->assertStatus(422);
    }

    /** @test */
    public function cannot_deactivate_the_last_active_admin_user()
    {
        $adminRole = Role::where('name', 'admin')->firstOrFail();

        $onlyAdmin = User::factory()->createOne(['status' => 'active']);
        $onlyAdmin->assignRole($adminRole);

        $this->actingAs($this->actor)->patchJson("/admin/users/{$onlyAdmin->id}/status", [
            'status' => 'inactive',
        ])->assertStatus(422);
    }

    /** @test */
    public function update_endpoint_rejects_suspended_status()
    {
        $target = User::factory()->createOne(['status' => 'active']);

        $response = $this->actingAs($this->actor)
            ->from(route('admin.users.edit', $target))
            ->put(route('admin.users.update', $target), [
                'name' => $target->name,
                'email' => $target->email,
                'status' => 'suspended',
                'roles' => [],
            ]);

        $response->assertRedirect(route('admin.users.edit', $target));
        $response->assertSessionHasErrors('status');
    }

    /** @test */
    public function update_endpoint_cannot_deactivate_self()
    {
        $response = $this->actingAs($this->actor)
            ->from(route('admin.users.edit', $this->actor))
            ->put(route('admin.users.update', $this->actor), [
                'name' => $this->actor->name,
                'email' => $this->actor->email,
                'status' => 'inactive',
                'roles' => [],
            ]);

        $response->assertRedirect(route('admin.users.edit', $this->actor));

        $this->assertDatabaseHas('users', [
            'id' => $this->actor->id,
            'status' => 'active',
        ]);
    }
}
