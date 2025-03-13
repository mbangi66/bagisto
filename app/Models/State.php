<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $fillable = ['state_name', 'state_name_ar'];

    public function cities()
    {
        return $this->hasMany(City::class, 'state_id', 'id')->orderBy('city_name_ar');
    }

    public function city()
    {
        return $this->hasMany(City::class, 'state_id', 'id')->orderBy('city_name_ar');
    }
}
