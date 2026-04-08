<?php

// app/Http/Requests/Dashboard/SaveDashboardRequest.php

namespace App\Http\Requests\Dashboard;

use Illuminate\Foundation\Http\FormRequest;

class SaveDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('edit dashboards');
    }

    public function rules(): array
    {
        return [
            'dashboard_id' => 'required|exists:dashboards,id',
            'widgets' => 'array|max:50', // Limit number of widgets
            'widgets.*.id' => 'required|exists:widgets,id',
            'widgets.*.x' => 'required|integer|min:0|max:11',
            'widgets.*.y' => 'required|integer|min:0|max:100',
            'widgets.*.w' => 'required|integer|min:1|max:12',
            'widgets.*.h' => 'required|integer|min:1|max:20',
            'widgets.*.config' => 'array',
            'widgets.*.config.*' => 'string|max:1000', // Prevent XSS
        ];
    }

    protected function prepareForValidation(): void
    {
        // Sanitize config data
        if ($this->has('widgets')) {
            $widgets = $this->input('widgets');
            foreach ($widgets as &$widget) {
                if (isset($widget['config'])) {
                    $widget['config'] = $this->sanitizeConfig($widget['config']);
                }
            }
            $this->merge(['widgets' => $widgets]);
        }
    }

    private function sanitizeConfig(array $config): array
    {
        // Remove potentially dangerous keys
        $dangerousKeys = ['script', 'javascript', 'eval', 'function'];

        return array_filter($config, function ($key) use ($dangerousKeys) {
            return ! in_array(strtolower($key), $dangerousKeys);
        }, ARRAY_FILTER_USE_KEY);
    }
}
