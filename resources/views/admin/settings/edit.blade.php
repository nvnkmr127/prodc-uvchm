@extends('layouts.theme')
@section('title', 'Edit Setting')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit Setting</h1>
    <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left mr-1"></i>Back to Settings
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Please fix the following errors:</strong>
        <ul class="mb-0 mt-2">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Edit Individual Setting</h6>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.settings.update') }}" method="POST">
                    @csrf
                    @method('POST')

                    <div class="form-group">
                        <label for="key">Setting Key</label>
                        <input type="text" class="form-control" id="key" name="key" 
                               value="{{ old('key', $setting->key ?? '') }}" 
                               placeholder="e.g., college_name, currency_symbol" required>
                        <small class="text-muted">Unique identifier for this setting</small>
                    </div>

                    <div class="form-group">
                        <label for="value">Setting Value</label>
                        <textarea class="form-control" id="value" name="value" rows="4" 
                                  placeholder="Enter the setting value">{{ old('value', $setting->value ?? '') }}</textarea>
                        <small class="text-muted">The actual value stored for this setting</small>
                    </div>

                    <div class="form-group">
                        <label for="group">Setting Group</label>
                        <select class="form-control" id="group" name="group">
                            <option value="general" {{ (old('group', $setting->group ?? 'general') == 'general') ? 'selected' : '' }}>General</option>
                            <option value="college" {{ (old('group', $setting->group ?? '') == 'college') ? 'selected' : '' }}>College Information</option>
                            <option value="financial" {{ (old('group', $setting->group ?? '') == 'financial') ? 'selected' : '' }}>Financial</option>
                            <option value="api" {{ (old('group', $setting->group ?? '') == 'api') ? 'selected' : '' }}>API & Integration</option>
                            <option value="system" {{ (old('group', $setting->group ?? '') == 'system') ? 'selected' : '' }}>System</option>
                        </select>
                        <small class="text-muted">Group this setting belongs to for organization</small>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i>Save Setting
                        </button>
                        <a href="{{ route('admin.settings.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times mr-1"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-info">
                    <i class="fas fa-info-circle mr-1"></i>Setting Guidelines
                </h6>
            </div>
            <div class="card-body">
                <h6 class="text-primary">Common Setting Keys:</h6>
                <ul class="text-muted small">
                    <li><code>college_name</code> - Name of the institution</li>
                    <li><code>college_email</code> - Official email address</li>
                    <li><code>college_phone</code> - Contact phone number</li>
                    <li><code>college_address</code> - Physical address</li>
                    <li><code>currency_symbol</code> - Currency symbol (₹, $, etc.)</li>
                    <li><code>enrollment_prefix</code> - Student enrollment prefix</li>
                    <li><code>womens_discount_percentage</code> - Discount percentage for female students</li>
                    <li><code>biometric_api_key</code> - API key for biometric devices</li>
                </ul>

                <hr>

                <h6 class="text-primary">Best Practices:</h6>
                <ul class="text-muted small">
                    <li>Use snake_case for setting keys</li>
                    <li>Be descriptive but concise with key names</li>
                    <li>Group related settings together</li>
                    <li>Test changes in a development environment first</li>
                    <li>Document any custom settings you add</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection