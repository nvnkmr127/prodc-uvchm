<!DOCTYPE html>
<html lang="en"><head><title>Payslip</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body>
    <div class="container border p-4 mt-4">
        <h2 class="text-center">{{ setting('college_name', 'My College') }}</h2>
        <p class="text-center">Payslip for the month of {{ $payslip->month }}, {{ $payslip->year }}</p><hr>
        <p><strong>Faculty Name:</strong> {{ $payslip->user->name }}</p>
        <div class="row"><div class="col-6">
            <table class="table table-sm table-bordered"><thead><tr><th colspan="2">Earnings</th></tr></thead>
            <tbody>
                @foreach($earnings as $item)<tr><td>{{$item->salaryComponent->name}}</td><td class="text-end">{{number_format($item->amount, 2)}}</td></tr>@endforeach
                <tr class="table-light"><th>Gross Salary</th><th class="text-end">{{number_format($payslip->gross_salary, 2)}}</th></tr>
            </tbody></table></div>
            <div class="col-6">
            <table class="table table-sm table-bordered"><thead><tr><th colspan="2">Deductions</th></tr></thead>
            <tbody>
                @foreach($deductions as $item)<tr><td>{{$item->salaryComponent->name}}</td><td class="text-end">{{number_format($item->amount, 2)}}</td></tr>@endforeach
                <tr class="table-light"><th>Total Deductions</th><th class="text-end">{{number_format($payslip->total_deductions, 2)}}</th></tr>
            </tbody></table></div></div>
        <h4 class="text-end mt-3">Net Salary: <span class="text-success">{{ setting('currency_symbol', '₹') }} {{ number_format($payslip->net_salary, 2) }}</span></h4>
    </div>
</body></html>