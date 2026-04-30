<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'theme',
        'font_family',
        'font_size',
        'language',
        'timezone',
        'date_format',
        'area_unit',
    ];
}
