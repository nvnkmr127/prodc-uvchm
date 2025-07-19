@extends('layouts.theme')
@section('title', 'Manage Widgets')

@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Widget Library</h1>
    <div>
        <form action="{{ route('admin.widgets.sync') }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-info shadow-sm"><i class="fas fa-sync-alt fa-sm text-white-50"></i> Discover & Sync Widgets</button>
        </form>
        <a href="{{ route('admin.widgets.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add Manually</a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
    </div>
@endif

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Available Dashboard Widgets</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Widget Name</th>
                        <th>Description</th>
                        <th>View Path</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($widgets as $widget)
                        @php
                            // Determine if the widget was auto-discovered by checking if the view file exists
                            $isDiscovered = view()->exists($widget->view_path);
                        @endphp
                        <tr>
                            <td>{{ $widget->name }}</td>
                            <td>{{ $widget->description }}</td>
                            <td><code>{{ $widget->view_path }}</code></td>
                            <td>
                                @if($isDiscovered)
                                    <span class="badge badge-success">Auto-Discovered</span>
                                @else
                                    <span class="badge badge-warning">Manual</span>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('admin.widgets.edit', $widget) }}" class="btn btn-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('admin.widgets.destroy', $widget) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete" onclick="return confirm('Are you sure?')" @if($isDiscovered) disabled @endif>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center">No widgets found. Click "Discover & Sync Widgets" to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
