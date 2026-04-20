<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;
    use WebhookEnabled;

    protected $fillable = ['name', 'start_date', 'end_date', 'is_current', 'auto_switch_enabled'];

    public function batches()
    {
        return $this->hasMany(Batch::class);
    }

    public function students()
    {
        return $this->hasManyThrough(
            Student::class,
            Batch::class,
            'academic_year_id', // Foreign key on batches table
            'batch_id',         // Foreign key on students table
            'id',               // Local key on academic_years table
            'id'                // Local key on batches table
        );
    }

    public static function checkOverlap($startDate, $endDate, $excludeId = null)
    {
        $query = self::where(function ($query) use ($startDate, $endDate) {
            $query->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function ($q) use ($startDate, $endDate) {
                    $q->where('start_date', '<=', $startDate)
                        ->where('end_date', '>=', $endDate);
                });
        });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
