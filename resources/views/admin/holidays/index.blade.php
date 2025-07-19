@extends('layouts.theme')
@section('title', 'Manage Holidays')
@section('content')
<div class="d-sm-flex align-items-center justify-content-between mb-4">
    <h1 class="h3 mb-0 text-gray-800">Manage Holidays</h1>
    <a href="{{ route('admin.holidays.create') }}" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus fa-sm text-white-50"></i> Add New Holiday</a>
</div>
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Holiday Name</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($holidays as $holiday)
                        <tr>
                            <td>{{ $holiday->name }}</td>
                            <td>{{ \Carbon\Carbon::parse($holiday->date)->format('d M, Y') }}</td>
                            <td>
                                <a href="{{ route('admin.holidays.edit', $holiday) }}" class="btn btn-warning btn-sm">Edit</a>
                                <form action="{{ route('admin.holidays.destroy', $holiday) }}" method="POST" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">No holidays found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection