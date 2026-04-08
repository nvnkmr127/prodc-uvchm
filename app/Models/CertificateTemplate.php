<?php

namespace App\Models;

use App\Traits\WebhookEnabled;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CertificateTemplate extends Model
{
    use HasFactory;
    use WebhookEnabled;

    protected $fillable = [
        'name',
        'body',
        'description',
        'paper_size',
        'orientation',
        'background_image',
        'content_type',
        'margin_top',
        'margin_right',
        'margin_bottom',
        'margin_left',
        'filename_format',
    ];
}
