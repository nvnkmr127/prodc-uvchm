<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryComponent extends Model
{
    use HasFactory;
    use WebhookEnabled;

    protected $fillable = ['name', 'type', 'is_taxable', 'is_mandatory', 'calculation_type', 'base_component_id'];

    public function baseComponent()
    {
        return $this->belongsTo(SalaryComponent::class, 'base_component_id');
    }
}
