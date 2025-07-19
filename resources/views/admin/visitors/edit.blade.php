@extends('layouts.theme')
@section('title', 'Edit Visitor Entry')
@section('content')
<h1 class="h3 mb-4 text-gray-800">Edit Visitor Entry</h1>
<form action="{{ route('admin.visitors.update', $visitor) }}" method="POST">
    @csrf
    @method('PATCH')
    <div class="row">
        <div class="col-md-6 mb-3"><label>Visitor Name*</label><input type="text" name="visitor_name" class="form-control" value="{{ $visitor->visitor_name }}" required></div>
        <div class="col-md-6 mb-3"><label>Phone Number*</label><input type="text" name="phone_number" class="form-control" value="{{ $visitor->phone_number }}" required></div>
        <div class="col-md-12 mb-3"><label>Purpose of Visit*</label><input type="text" name="purpose_of_visit" class="form-control" value="{{ $visitor->purpose_of_visit }}" required></div>
        <div class="col-md-6 mb-3"><label>Check-in Time*</label><input type="datetime-local" name="check_in_time" value="{{ \Carbon\Carbon::parse($visitor->check_in_time)->format('Y-m-d\TH:i') }}" class="form-control" required></div>
        <div class="col-md-6 mb-3"><label>Check-out Time</label><input type="datetime-local" name="check_out_time" value="{{ $visitor->check_out_time ? \Carbon\Carbon::parse($visitor->check_out_time)->format('Y-m-d\TH:i') : '' }}" class="form-control"></div>
        <div class="col-md-12 mb-3"><label>Notes</label><textarea name="notes" class="form-control" rows="3">{{ $visitor->notes }}</textarea></div>
    </div>
    <button type="submit" class="btn btn-primary">Update Entry</button>
</form>
@endsection