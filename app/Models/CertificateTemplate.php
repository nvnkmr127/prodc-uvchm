<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\WebhookEnabled;

class CertificateTemplate extends Model
{
    use WebhookEnabled;
    use HasFactory;

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
        'filename_format'
    ];
}