<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use App\Models\Setting;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

class SettingsTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $admin;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create permissions
        Permission::create(['name' => 'manage settings']);
        Permission::create(['name' => 'view backend']);

        // Create roles
        $adminRole = Role::create(['name' => 'admin']);
        $userRole = Role::create(['name' => 'user']);

        // Assign permissions to roles
        $adminRole->givePermissionTo(['manage settings', 'view backend']);

        // Create users
        $this->admin = User::factory()->create();
        $this->user = User::factory()->create();

        // Assign roles
        $this->admin->assignRole('admin');
        $this->user->assignRole('user');
    }

    /** @test */
    public function admin_can_view_settings_page()
    {
        $response = $this->actingAs($this->admin)
                         ->get(route('admin.settings.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings.index');
        $response->assertViewHas(['settingGroups', 'settings', 'activeTab']);
    }

    /** @test */
    public function non_admin_cannot_view_settings_page()
    {
        $response = $this->actingAs($this->user)
                         ->get(route('admin.settings.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_settings()
    {
        Setting::create([
            'key' => 'app_name',
            'value' => 'Old Name',
            'group' => 'general',
            'type' => 'text'
        ]);

        $response = $this->actingAs($this->admin)
                         ->post(route('admin.settings.update'), [
                             'app_name' => 'New College Name',
                             'active_tab' => 'general'
                         ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('settings', [
            'key' => 'app_name',
            'value' => 'New College Name'
        ]);
    }

    /** @test */
    public function setting_validation_works()
    {
        $response = $this->actingAs($this->admin)
                         ->post(route('admin.settings.update'), [
                             'college_email' => 'invalid-email',
                             'active_tab' => 'college'
                         ]);

        $response->assertSessionHasErrors('college_email');
    }

    /** @test */
    public function admin_can_upload_logo()
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo.jpg', 200, 200);

        $response = $this->actingAs($this->admin)
                         ->post(route('admin.settings.update'), [
                             'college_logo' => $file,
                             'active_tab' => 'college'
                         ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $setting = Setting::where('key', 'college_logo')->first();
        $this->assertNotNull($setting);
        Storage::disk('public')->assertExists($setting->value);
    }

    /** @test */
    public function admin_can_export_settings()
    {
        Setting::create([
            'key' => 'test_setting',
            'value' => 'test_value',
            'group' => 'general',
            'type' => 'text'
        ]);

        $response = $this->actingAs($this->admin)
                         ->get(route('admin.settings.export'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'exported_at',
            'app_version',
            'settings'
        ]);
    }

    /** @test */
    public function admin_can_import_settings()
    {
        $settingsData = [
            'settings' => [
                [
                    'key' => 'imported_setting',
                    'value' => 'imported_value',
                    'group' => 'general',
                    'type' => 'text'
                ]
            ]
        ];

        $file = UploadedFile::fake()->createWithContent(
            'settings.json',
            json_encode($settingsData)
        );

        $response = $this->actingAs($this->admin)
                         ->post(route('admin.settings.import'), [
                             'settings_file' => $file
                         ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('settings', [
            'key' => 'imported_setting',
            'value' => 'imported_value'
        ]);
    }

    /** @test */
    public function admin_can_reset_settings_to_defaults()
    {
        Setting::create([
            'key' => 'app_name',
            'value' => 'Modified Name',
            'group' => 'general',
            'type' => 'text'
        ]);

        $response = $this->actingAs($this->admin)
                         ->post(route('admin.settings.reset-defaults'), [
                             'group' => 'general'
                         ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    /** @test */
    public function admin_can_test_email_configuration()
    {
        $response = $this->actingAs($this->admin)
                         ->postJson(route('admin.settings.test-email'), [
                             'test_email' => 'test@example.com'
                         ]);

        $response->assertJsonStructure(['success', 'message']);
    }

    /** @test */
    public function admin_can_clear_cache()
    {
        Cache::put('test_key', 'test_value', 60);

        $response = $this->actingAs($this->admin)
                         ->postJson(route('admin.settings.clear-cache'));

        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function admin_can_toggle_maintenance_mode()
    {
        Setting::create([
            'key' => 'maintenance_mode',
            'value' => '0',
            'group' => 'general',
            'type' => 'toggle'
        ]);

        $response = $this->actingAs($this->admin)
                         ->postJson(route('admin.settings.toggle-maintenance'));

        $response->assertJson(['success' => true]);
    }

    /** @test */
    public function admin_can_create_backup()
    {
        Setting::create([
            'key' => 'test_setting',
            'value' => 'test_value',
            'group' => 'general',
            'type' => 'text'
        ]);

        $response = $this->actingAs($this->admin)
                         ->postJson(route('admin.settings.backup'));

        $response->assertJson(['success' => true]);
        $response->assertJsonStructure(['success', 'message', 'file']);
    }

    /** @test */
    public function admin_can_view_system_info()
    {
        $response = $this->actingAs($this->admin)
                         ->get(route('admin.settings.system-info'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.settings.system-info');
        $response->assertViewHas('systemInfo');
    }

    /** @test */
    public function admin_can_run_health_check()
    {
        $response = $this->actingAs($this->admin)
                         ->getJson(route('admin.settings.health-check'));

        $response->assertJsonStructure([
            'status',
            'checks',
            'timestamp'
        ]);
    }

    /** @test */
    public function setting_helper_function_works()
    {
        Setting::create([
            'key' => 'test_setting',
            'value' => 'test_value',
            'group' => 'general',
            'type' => 'text'
        ]);

        $value = setting('test_setting');
        $this->assertEquals('test_value', $value);

        $defaultValue = setting('non_existent_setting', 'default');
        $this->assertEquals('default', $defaultValue);
    }

    /** @test */
    public function setting_helper_type_casting_works()
    {
        Setting::create([
            'key' => 'boolean_setting',
            'value' => '1',
            'group' => 'general',
            'type' => 'toggle'
        ]);

        Setting::create([
            'key' => 'number_setting',
            'value' => '42',
            'group' => 'general',
            'type' => 'number'
        ]);

        $boolValue = setting('boolean_setting', false, 'bool');
        $this->assertTrue($boolValue);

        $intValue = setting('number_setting', 0, 'int');
        $this->assertEquals(42, $intValue);
    }

    /** @test */
    public function settings_are_cached()
    {
        Setting::create([
            'key' => 'cached_setting',
            'value' => 'cached_value',
            'group' => 'general',
            'type' => 'text'
        ]);

        // First call should hit database
        $value1 = setting('cached_setting');
        
        // Second call should hit cache
        $value2 = setting('cached_setting');
        
        $this->assertEquals($value1, $value2);
        $this->assertTrue(Cache::has('all_settings'));
    }

    /** @test */
    public function encrypted_settings_work()
    {
        Setting::create([
            'key' => 'encrypted_setting',
            'value' => encrypt('secret_value'),
            'group' => 'security',
            'type' => 'password',
            'is_encrypted' => true
        ]);

        $value = setting('encrypted_setting');
        $this->assertEquals('secret_value', $value);
    }

    /** @test */
    public function update_setting_helper_works()
    {
        $result = update_setting('new_setting', 'new_value');
        
        $this->assertTrue($result);
        $this->assertDatabaseHas('settings', [
            'key' => 'new_setting',
            'value' => 'new_value'
        ]);
    }

    /** @test */
    public function get_settings_by_group_works()
    {
        Setting::create([
            'key' => 'group_setting_1',
            'value' => 'value_1',
            'group' => 'test_group',
            'type' => 'text'
        ]);

        Setting::create([
            'key' => 'group_setting_2',
            'value' => 'value_2',
            'group' => 'test_group',
            'type' => 'text'
        ]);

        $groupSettings = get_settings_by_group('test_group');
        
        $this->assertCount(2, $groupSettings);
        $this->assertEquals('value_1', $groupSettings['group_setting_1']);
        $this->assertEquals('value_2', $groupSettings['group_setting_2']);
    }

    /** @test */
    public function public_settings_work()
    {
        Setting::create([
            'key' => 'public_setting',
            'value' => 'public_value',
            'group' => 'general',
            'type' => 'text',
            'is_public' => true
        ]);

        Setting::create([
            'key' => 'private_setting',
            'value' => 'private_value',
            'group' => 'general',
            'type' => 'text',
            'is_public' => false
        ]);

        $publicSettings = get_public_settings();
        
        $this->assertArrayHasKey('public_setting', $publicSettings);
        $this->assertArrayNotHasKey('private_setting', $publicSettings);
        $this->assertEquals('public_value', $publicSettings['public_setting']);
    }

    /** @test */
    public function setting_validation_function_works()
    {
        Setting::create([
            'key' => 'email_setting',
            'value' => '',
            'group' => 'general',
            'type' => 'email',
            'validation_rules' => json_encode(['email', 'required'])
        ]);

        $validResult = validate_setting('email_setting', 'valid@email.com');
        $this->assertTrue($validResult['valid']);

        $invalidResult = validate_setting('email_setting', 'invalid-email');
        $this->assertFalse($invalidResult['valid']);
    }

    /** @test */
    public function settings_backup_and_restore_works()
    {
        Setting::create([
            'key' => 'backup_test_setting',
            'value' => 'backup_test_value',
            'group' => 'general',
            'type' => 'text'
        ]);

        // Create backup
        $backupPath = backup_settings();
        $this->assertNotNull($backupPath);
        $this->assertFileExists($backupPath);

        // Modify setting
        update_setting('backup_test_setting', 'modified_value');

        // Restore backup
        $restoreResult = restore_settings($backupPath);
        $this->assertTrue($restoreResult);

        // Verify restoration
        $restoredValue = setting('backup_test_setting');
        $this->assertEquals('backup_test_value', $restoredValue);

        // Cleanup
        if (file_exists($backupPath)) {
            unlink($backupPath);
        }
    }

    /** @test */
    public function api_endpoints_work_for_authorized_users()
    {
        // Test get groups
        $response = $this->actingAs($this->admin)
                         ->getJson(route('admin.api.settings.groups'));
        $response->assertStatus(200);

        // Test get public settings
        $response = $this->actingAs($this->admin)
                         ->getJson(route('admin.api.settings.public'));
        $response->assertStatus(200);

        // Test get statistics
        $response = $this->actingAs($this->admin)
                         ->getJson(route('admin.api.settings.statistics'));
        $response->assertStatus(200);
    }

    /** @test */
    public function api_endpoints_require_authorization()
    {
        $response = $this->actingAs($this->user)
                         ->getJson(route('admin.api.settings.groups'));
        $response->assertStatus(403);

        $response = $this->getJson(route('admin.api.settings.groups'));
        $response->assertStatus(302); // Redirect to login
    }

    /** @test */
    public function setting_model_typed_value_casting_works()
    {
        $setting = Setting::create([
            'key' => 'typed_setting',
            'value' => '1',
            'group' => 'general',
            'type' => 'toggle'
        ]);

        $typedValue = $setting->getTypedValue();
        $this->assertTrue($typedValue);

        $setting->update(['value' => '42', 'type' => 'number']);
        $typedValue = $setting->getTypedValue();
        $this->assertEquals(42, $typedValue);

        $setting->update(['value' => '["item1", "item2"]', 'type' => 'array']);
        $typedValue = $setting->getTypedValue();
        $this->assertEquals(['item1', 'item2'], $typedValue);
    }

    /** @test */
    public function setting_model_validation_works()
    {
        $setting = Setting::create([
            'key' => 'validated_setting',
            'value' => '',
            'group' => 'general',
            'type' => 'email',
            'validation_rules' => json_encode(['email', 'required'])
        ]);

        $validResult = $setting->validateValue('valid@email.com');
        $this->assertTrue($validResult['valid']);

        $invalidResult = $setting->validateValue('invalid-email');
        $this->assertFalse($invalidResult['valid']);
        $this->assertNotEmpty($invalidResult['message']);
    }

    /** @test */
    public function setting_model_display_value_works()
    {
        $setting = Setting::create([
            'key' => 'display_setting',
            'value' => '1',
            'group' => 'general',
            'type' => 'toggle'
        ]);

        $displayValue = $setting->getDisplayValue();
        $this->assertStringContainsString('badge-success', $displayValue);
        $this->assertStringContainsString('Enabled', $displayValue);

        $setting->update(['value' => '0']);
        $displayValue = $setting->getDisplayValue();
        $this->assertStringContainsString('badge-secondary', $displayValue);
        $this->assertStringContainsString('Disabled', $displayValue);
    }

    /** @test */
    public function setting_model_export_import_works()
    {
        Setting::create([
            'key' => 'export_setting_1',
            'value' => 'value_1',
            'group' => 'test_group',
            'type' => 'text'
        ]);

        Setting::create([
            'key' => 'export_setting_2',
            'value' => 'value_2',
            'group' => 'test_group',
            'type' => 'text'
        ]);

        // Test export
        $exported = Setting::exportToArray('test_group');
        $this->assertCount(2, $exported);

        // Clear settings
        Setting::where('group', 'test_group')->delete();

        // Test import
        $result = Setting::importFromArray($exported);
        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['imported']);

        // Verify import
        $this->assertDatabaseHas('settings', [
            'key' => 'export_setting_1',
            'value' => 'value_1'
        ]);
    }

    /** @test */
    public function system_info_helper_works()
    {
        $systemInfo = get_system_info();
        
        $this->assertIsArray($systemInfo);
        $this->assertArrayHasKey('app_name', $systemInfo);
        $this->assertArrayHasKey('php_version', $systemInfo);
        $this->assertArrayHasKey('laravel_version', $systemInfo);
        $this->assertArrayHasKey('environment', $systemInfo);
        $this->assertArrayHasKey('database', $systemInfo);
    }

    /** @test */
    public function format_bytes_helper_works()
    {
        $this->assertEquals('1 KB', format_bytes(1024));
        $this->assertEquals('1 MB', format_bytes(1024 * 1024));
        $this->assertEquals('1 GB', format_bytes(1024 * 1024 * 1024));
        $this->assertEquals('500 B', format_bytes(500));
    }

    /** @test */
    public function settings_cache_is_cleared_on_model_changes()
    {
        // Put something in cache
        Cache::put('all_settings', ['cached' => 'data'], 60);
        $this->assertTrue(Cache::has('all_settings'));

        // Create a setting (should clear cache)
        Setting::create([
            'key' => 'cache_test_setting',
            'value' => 'cache_test_value',
            'group' => 'general',
            'type' => 'text'
        ]);

        // Cache should be cleared
        $this->assertFalse(Cache::has('all_settings'));
    }

    /** @test */
    public function multiple_settings_helper_works()
    {
        Setting::create([
            'key' => 'setting_1',
            'value' => 'value_1',
            'group' => 'general',
            'type' => 'text'
        ]);

        Setting::create([
            'key' => 'setting_2',
            'value' => 'value_2',
            'group' => 'general',
            'type' => 'text'
        ]);

        $result = settings(['setting_1', 'setting_2', 'non_existent'], [
            'non_existent' => 'default_value'
        ]);

        $this->assertEquals('value_1', $result['setting_1']);
        $this->assertEquals('value_2', $result['setting_2']);
        $this->assertEquals('default_value', $result['non_existent']);
    }

    /** @test */
    public function clear_settings_cache_helper_works()
    {
        // Put something in cache
        Cache::put('all_settings', ['cached' => 'data'], 60);
        Cache::put('public_settings', ['public' => 'data'], 60);
        Cache::put('settings_group_general', ['group' => 'data'], 60);

        $this->assertTrue(Cache::has('all_settings'));
        $this->assertTrue(Cache::has('public_settings'));
        $this->assertTrue(Cache::has('settings_group_general'));

        // Clear cache
        clear_settings_cache();

        $this->assertFalse(Cache::has('all_settings'));
        $this->assertFalse(Cache::has('public_settings'));
        $this->assertFalse(Cache::has('settings_group_general'));
    }

    /** @test */
    public function bulk_setting_operations_work()
    {
        $settings = [
            'bulk_setting_1' => 'bulk_value_1',
            'bulk_setting_2' => 'bulk_value_2',
            'bulk_setting_3' => 'bulk_value_3',
        ];

        $result = Setting::bulkUpsert($settings);
        
        $this->assertEquals(3, $result['success']);
        $this->assertEquals(0, $result['failed']);

        foreach ($settings as $key => $value) {
            $this->assertDatabaseHas('settings', [
                'key' => $key,
                'value' => $value
            ]);
        }
    }

    protected function tearDown(): void
    {
        // Clean up any created backup files
        $backupPath = storage_path('app/backups');
        if (is_dir($backupPath)) {
            $files = glob($backupPath . '/settings-backup-*.json');
            foreach ($files as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }

        parent::tearDown();
    }
}