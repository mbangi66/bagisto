<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $table = 'branchs';

    protected $fillable = [
        'branch_name',
        'branch_name_ar',
        'is_available',
        'is_deleted',
        'address',
        'address_ar',
        'longitude',
        'latitude',
        'phone',
        'debit_bank_id',
        'debit_cash_id',
        'city_id',
        'payment_gateway_id',
    ];

    public function debit_bank()
    {
        return $this->belongsTo(Account::class, 'debit_bank_id');
    }

    public function debit_cash()
    {
        return $this->belongsTo(Account::class, 'debit_cash_id');
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    // public function payment_gateway()
    // {
    //     return $this->belongsTo(PaymentGateway::class);
    // }

    // public function printer()
    // {
    //     return $this->hasOne(Printer::class);
    // }
}
