<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Block extends Model
{

    protected $fillable = ['state_id', 'city_id', 'branch_id', 'name_en', 'name_ar', 'lng', 'lat'];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public static function scopeList(Builder $query)
    {
        return $query->select('id', 'name_en', 'name_ar');
    }

    public static function setting(bool $return_boolean = false): bool|array
    {
        $value = Setting::ofCategory('general', 'blocks_dropdown') == 1 ? true : false;

        if ($return_boolean) {
            return $value;
        }

        return ['blocks_dropdown' => $value];
    }
}
