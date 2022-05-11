<?php

namespace App\Models\shop;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\shop\Traits\ShopQueryRelationship;
use App\Models\employee\Employee;
use App\Models\attendance\Attendance;

class Shop extends Model
{
    use HasFactory,
    ShopQueryRelationship;

    protected $table = 'shops';

     protected $fillable = [
        'shop_name', 'shop_no', 'report_name','check_shop', 'check_point', 'group_shop', 'color_code',
    ];

    public function employees(){
        return $this->hasMany(Employee::class, 'shop_id');
    }

    public function attendance(){
        return $this->hasManyThrough(Attendance::class, Employee::class,'shop_id','staff_id');
    }

}
