<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PayslipItem extends Model
{
    protected $fillable = ['payslip_id', 'salary_component_id', 'name', 'type', 'amount'];

    public function payslip()
    {
        return $this->belongsTo(Payslip::class);
    }

    public function component()
    {
        return $this->belongsTo(SalaryComponent::class, 'salary_component_id');
    }
}
