<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\WebhookEnabled;

class PracticalGroup extends Model
{
    use WebhookEnabled;
    use HasFactory;
protected $fillable = ['name', 'batch_id', 'classroom_id', 'academic_period'];


    public function batch(): BelongsTo { return $this->belongsTo(Batch::class); }
    public function classroom(): BelongsTo { return $this->belongsTo(Classroom::class); }
    public function students(): BelongsToMany { return $this->belongsToMany(Student::class, 'practical_group_student'); }
}