<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\WebhookEnabled;

class AssetCategory extends Model
{
    use WebhookEnabled;
    use HasFactory;
    protected $fillable = ['name'];
}