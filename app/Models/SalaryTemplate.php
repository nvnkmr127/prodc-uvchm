<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryTemplate extends Model
{
    protected $fillable = ['name', 'description'];

    public function components()
    {
        return $this->hasMany(SalaryTemplateComponent::class);
    }
}
