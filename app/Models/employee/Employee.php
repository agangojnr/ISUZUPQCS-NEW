<?php

namespace App\Models\employee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\shop\Shop;
use App\Models\attendance\Attendance;
use App\Models\employeecategory\EmployeeCategory;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'employees';
    protected $fillable = ['unique_no','staff_no','staff_name','Department_Description','Category','Role','shop_id','team_leader','user_id'];

    public function shop(){
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function empattendance(){
        return $this->hasMany(Attendance::class, 'staff_id');
    }







    //protected $fillable = ['staff_no', 'staff_name', 'title_id', 'division_id','department_id','shop_id','empcategory_id','status','team_leader','user_id'];



}
