@extends('layouts.theme')
@section('title', 'Generate Payslips')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Generate Payslips</h1>
<div class="card shadow mb-4">
    <div class="card-body">
        <p>Select a month and year to generate payslips for all staff members who have a salary structure defined.</p>
        <form action="{{ route('admin.payslips.store') }}" method="POST">
            @csrf
            <div class="row">
                <div class="col-md-5 mb-3"><label>Month</label><select name="month" class="form-control" required>@for($m=1; $m<=12; ++$m)<option value="{{date('F', mktime(0, 0, 0, $m, 1))}}">{{date('F', mktime(0, 0, 0, $m, 1))}}</option>@endfor</select></div>
                <div class="col-md-5 mb-3"><label>Year</label><input type="number" name="year" class="form-control" value="{{ date('Y') }}" required></div>
            </div>
            <button type="submit" class="btn btn-primary">Generate Payslips</button>
        </form>
    </div>
</div>
@endsection