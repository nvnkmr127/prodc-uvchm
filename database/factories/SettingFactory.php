<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Setting>
 */
class SettingFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Setting::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $types = ['text', 'textarea', 'email', 'url', 'number', 'toggle', 'select', 'file', 'password'];
        $groups = ['general', 'college', 'academic', 'financial', 'attendance', 'notifications', 'security', 'backup'];

        $type = $this->faker->randomElement($types);

        return [
            'key' => $this->faker->unique()->slug(2).'_setting',
            'value' => $this->generateValueForType($type),
            'group' => $this->faker->randomElement($groups),
            'type' => $type,
            'description' => $this->faker->sentence(),
            'is_public' => $this->faker->boolean(30), // 30% chance of being public
            'is_encrypted' => $type === 'password' ? true : $this->faker->boolean(10), // 10% chance of being encrypted
            'validation_rules' => $this->generateValidationRules($type),
        ];
    }

    /**
     * Generate appropriate value for the given type
     */
    protected function generateValueForType(string $type): string
    {
        switch ($type) {
            case 'text':
                return $this->faker->words(3, true);

            case 'textarea':
                return $this->faker->paragraph();

            case 'email':
                return $this->faker->safeEmail();

            case 'url':
                return $this->faker->url();

            case 'number':
                return (string) $this->faker->numberBetween(1, 1000);

            case 'toggle':
                return $this->faker->boolean() ? '1' : '0';

            case 'select':
                return $this->faker->randomElement(['option1', 'option2', 'option3']);

            case 'file':
                return 'uploads/test-file.jpg';

            case 'password':
                return bcrypt('password');

            default:
                return $this->faker->word();
        }
    }

    /**
     * Generate validation rules for the given type
     */
    protected function generateValidationRules(string $type): ?array
    {
        switch ($type) {
            case 'email':
                return ['email', 'max:255'];

            case 'url':
                return ['url', 'max:500'];

            case 'number':
                return ['numeric', 'min:0'];

            case 'toggle':
                return ['boolean'];

            case 'password':
                return ['string', 'min:6'];

            case 'text':
                return ['string', 'max:255'];

            case 'textarea':
                return ['string', 'max:5000'];

            default:
                return null;
        }
    }

    /**
     * Create a text setting
     */
    public function text(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'text',
            'value' => $this->faker->words(3, true),
            'validation_rules' => ['string', 'max:255'],
        ]);
    }

    /**
     * Create an email setting
     */
    public function email(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'email',
            'value' => $this->faker->safeEmail(),
            'validation_rules' => ['email', 'max:255'],
        ]);
    }

    /**
     * Create a toggle/boolean setting
     */
    public function toggle(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'toggle',
            'value' => $this->faker->boolean() ? '1' : '0',
            'validation_rules' => ['boolean'],
        ]);
    }

    /**
     * Create a number setting
     */
    public function number(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'number',
            'value' => (string) $this->faker->numberBetween(1, 1000),
            'validation_rules' => ['numeric', 'min:0'],
        ]);
    }

    /**
     * Create a public setting
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Create an encrypted setting
     */
    public function encrypted(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_encrypted' => true,
            'type' => 'password',
            'value' => encrypt($this->faker->password()),
        ]);
    }

    /**
     * Create setting for specific group
     */
    public function forGroup(string $group): static
    {
        return $this->state(fn (array $attributes) => [
            'group' => $group,
        ]);
    }

    /**
     * Create general settings
     */
    public function general(): static
    {
        return $this->forGroup('general');
    }

    /**
     * Create college settings
     */
    public function college(): static
    {
        return $this->forGroup('college');
    }

    /**
     * Create academic settings
     */
    public function academic(): static
    {
        return $this->forGroup('academic');
    }

    /**
     * Create financial settings
     */
    public function financial(): static
    {
        return $this->forGroup('financial');
    }

    /**
     * Create notification settings
     */
    public function notifications(): static
    {
        return $this->forGroup('notifications');
    }

    /**
     * Create security settings
     */
    public function security(): static
    {
        return $this->forGroup('security');
    }

    /**
     * Create system settings
     */
    public function system(): static
    {
        return $this->forGroup('system');
    }

    /**
     * Create required setting
     */
    public function required(): static
    {
        return $this->state(function (array $attributes) {
            $rules = $attributes['validation_rules'] ?? [];
            if (is_array($rules)) {
                $rules[] = 'required';
            } else {
                $rules = ['required'];
            }

            return [
                'validation_rules' => $rules,
            ];
        });
    }

    /**
     * Create setting with specific key
     */
    public function withKey(string $key): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
        ]);
    }

    /**
     * Create setting with specific value
     */
    public function withValue(string $value): static
    {
        return $this->state(fn (array $attributes) => [
            'value' => $value,
        ]);
    }

    /**
     * Create setting with description
     */
    public function withDescription(string $description): static
    {
        return $this->state(fn (array $attributes) => [
            'description' => $description,
        ]);
    }

    /**
     * Create common application settings
     */
    public function appName(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'app_name',
            'value' => $this->faker->company().' Management System',
            'group' => 'general',
            'type' => 'text',
            'description' => 'Application name displayed throughout the system',
            'is_public' => true,
            'validation_rules' => ['required', 'string', 'max:255'],
        ]);
    }

    /**
     * Create college name setting
     */
    public function collegeName(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'college_name',
            'value' => $this->faker->company().' College',
            'group' => 'college',
            'type' => 'text',
            'description' => 'Official college name',
            'is_public' => true,
            'validation_rules' => ['required', 'string', 'max:255'],
        ]);
    }

    /**
     * Create timezone setting
     */
    public function timezone(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'timezone',
            'value' => $this->faker->randomElement(['Asia/Kolkata', 'UTC', 'America/New_York', 'Europe/London']),
            'group' => 'general',
            'type' => 'select',
            'description' => 'Default timezone for the application',
            'is_public' => false,
            'validation_rules' => ['required', 'string'],
        ]);
    }

    /**
     * Create currency symbol setting
     */
    public function currencySymbol(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'currency_symbol',
            'value' => $this->faker->randomElement(['₹', '$', '€', '£']),
            'group' => 'financial',
            'type' => 'text',
            'description' => 'Currency symbol for amounts',
            'is_public' => true,
            'validation_rules' => ['required', 'string', 'max:5'],
        ]);
    }

    /**
     * Create maintenance mode setting
     */
    public function maintenanceMode(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'maintenance_mode',
            'value' => '0',
            'group' => 'general',
            'type' => 'toggle',
            'description' => 'Put application in maintenance mode',
            'is_public' => false,
            'validation_rules' => ['boolean'],
        ]);
    }

    /**
     * Create email notification setting
     */
    public function emailNotifications(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'email_notifications',
            'value' => '1',
            'group' => 'notifications',
            'type' => 'toggle',
            'description' => 'Enable email notifications',
            'is_public' => false,
            'validation_rules' => ['boolean'],
        ]);
    }

    /**
     * Create minimum attendance setting
     */
    public function minimumAttendance(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'minimum_attendance_percentage',
            'value' => '75',
            'group' => 'attendance',
            'type' => 'number',
            'description' => 'Minimum attendance required for exam eligibility',
            'is_public' => false,
            'validation_rules' => ['required', 'numeric', 'between:0,100'],
        ]);
    }

    /**
     * Create API key setting (encrypted)
     */
    public function apiKey(): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => 'api_key',
            'value' => encrypt($this->faker->sha256()),
            'group' => 'system',
            'type' => 'password',
            'description' => 'API key for external services',
            'is_public' => false,
            'is_encrypted' => true,
            'validation_rules' => ['required', 'string'],
        ]);
    }
}
