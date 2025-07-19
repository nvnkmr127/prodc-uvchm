@extends('layouts.theme')
@section('title', 'Add Visitor Entry')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Add New Visitor Entry</h1>
<form action="{{ route('admin.visitors.store') }}" method="POST">
    @csrf
    <div class="row">
        <div class="col-md-6 mb-3"><label>Visitor Name*</label><input type="text" name="visitor_name" class="form-control" required></div>
        <div class="col-md-6 mb-3"><label>Phone Number*</label><input type="text" name="phone_number" class="form-control" required></div>
        <div class="col-md-12 mb-3"><label>Purpose of Visit*</label><input type="text" name="purpose_of_visit" class="form-control" required></div>
        <div class="col-md-6 mb-3"><label>Check-in Time*</label><input type="datetime-local" name="check_in_time" value="{{ now()->format('Y-m-d\TH:i') }}" class="form-control" required></div>
        <div class="col-md-12 mb-3"><label>Notes</label><textarea name="notes" class="form-control" rows="3"></textarea></div>
    </div>
    <button type="submit" class="btn btn-primary">Save Entry</button>
</form>
@endsection