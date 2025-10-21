<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $table = 'site_settings';

    protected $fillable = [
        'tenant_id','brand','primary','accent',
        'banner_title','banner_sub','banner_image'
    ];
}
