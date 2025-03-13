<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class City extends Model
{

    protected $hidden = ['branch_id'];

    protected $fillable = ['city_name', 'city_name_ar', 'branch_id', 'location_id'];

    protected $visible = ['id', 'state_id', 'city_name', 'city_name_ar', 'min_order', 'delivery_fee', 'branch_id', 'delivery_fee_outside'];

    protected $casts = [
        'min_order' => 'float',
        'delivery_fee' => 'float',
        'delivery_fee_outside' => 'float',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function blocks()
    {
        return $this->hasMany(Block::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function getDeliveryCharge()
    {
        return Branch::where('city_id', $this->id)->first() ? $this->delivery_fee : $this->delivery_fee_outside;
    }
}
