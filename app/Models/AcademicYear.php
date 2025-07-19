<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\WebhookEnabled;

class AcademicYear extends Model
{
    use WebhookEnabled;
    use HasFactory;
    protected $fillable = ['name', 'start_date', 'end_date', 'is_current'];
}