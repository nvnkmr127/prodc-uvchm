<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\WebhookEnabled;

class Visitor extends Model
{
    use WebhookEnabled;
    use HasFactory;
    protected $fillable = ['visitor_name', 'phone_number', 'purpose_of_visit', 'check_in_time', 'check_out_time', 'notes'];
}