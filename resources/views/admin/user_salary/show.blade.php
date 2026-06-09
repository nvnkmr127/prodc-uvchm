@extends('layouts.theme')
@section('title', 'Salary Structure for ' . $user->name)

@section('content')
<h1 class="h3 mb-4 text-gray-800">Salary Structure for: <strong>{{ $user->name }}</strong></h1>

<div class="row">
    <div class="col-md-6">
        {{-- Assign Template Form --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Assign Salary Template</h6></div>
            <div class="card-body">
                <form action="{{ route('admin.faculty.salary.assign-template', $user) }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label>Select Template</label>
                        <select name="salary_template_id" class="form-control">
                            <option value="">-- No Template (Custom Only) --</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}" @if($user->salary_template_id == $template->id) selected @endif>
                                    {{ $template->name }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-2">Assigning a template will apply its base components. You can add overrides below.</small>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">Assign Template</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        {{-- Add Override Form --}}
        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Add Component Override</h6></div>
            <div class="card-body">
                <form action="{{ route('admin.faculty.salary.store', $user) }}" method="POST">
                    @csrf
                    <div class="form-group mb-2">
                        <label>Salary Component</label>
                        <select name="salary_component_id" class="form-control" required>
                            <option value="">-- Select --</option>
                            @foreach($components as $component)
                                <option value="{{ $component->id }}">{{ $component->name }} ({{$component->type}})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group mb-2">
                        <label>Override Amount</label>
                        <input type="number" step="0.01" name="amount" class="form-control" required>
                        <small class="text-muted">This amount will replace the template value for this component.</small>
                    </div>
                    <button type="submit" class="btn btn-success mt-2">Add Override</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Current Template Components --}}
@if($user->salaryTemplate)
<div class="card shadow mb-4">
    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-info">Template Components ({{ $user->salaryTemplate->name }})</h6></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead><tr><th>Component</th><th>Type</th><th>Calculation</th><th>Base Component</th><th>Template Value</th></tr></thead>
                <tbody>
                    @forelse($user->salaryTemplate->components as $tc)
                        <tr>
                            <td>{{ $tc->salaryComponent->name }}</td>
                            <td><span class="badge badge-{{ $tc->salaryComponent->type == 'Earning' ? 'success' : 'danger' }}">{{ $tc->salaryComponent->type }}</span></td>
                            <td>{{ ucfirst($tc->salaryComponent->calculation_type) }}</td>
                            <td>{{ $tc->salaryComponent->baseComponent ? $tc->salaryComponent->baseComponent->name : '-' }}</td>
                            <td>{{ $tc->value }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center">No components in template.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- List of Overrides --}}
<div class="card shadow mb-4 border-left-warning">
    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-warning">Custom Overrides (Specific to {{ $user->name }})</h6></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm table-bordered">
                <thead><tr><th>Component</th><th>Type</th><th class="text-right">Override Amount</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($salaryStructure as $structure)
                        <tr>
                            <td>{{ $structure->salaryComponent->name }}</td>
                            <td><span class="badge badge-{{ $structure->salaryComponent->type == 'Earning' ? 'success' : 'danger' }}">{{ $structure->salaryComponent->type }}</span></td>
                            <td class="text-right">{{ number_format($structure->amount, 2) }}</td>
                            <td>
                                <form action="{{ route('admin.faculty.salary.destroy-override', $structure) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Remove override?')"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                    <tr><td colspan="4" class="text-center text-muted">No custom overrides assigned. Template defaults apply.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection