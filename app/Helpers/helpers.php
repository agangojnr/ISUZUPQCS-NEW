<?php
use App\Helpers\uuid;
use Carbon\Carbon as Carbon;
use App\Models\productiontarget\Production_target;
use App\Models\attendance\Attendance;
use App\Models\attendancestatus\Attendance_status;
use App\Models\unitmovement\Unitmovement;
use App\Models\querydefect\Querydefect;
use App\Models\drrtarget\DrrTarget;
use App\Models\drrtargetshop\DrrTargetShop;
use App\Models\shop\Shop;
use App\Models\drr\Drr;
use App\Models\unitsbroughtforward\UnitsBroughtForward;
use App\Models\employee\Employee;
use App\Models\vehicle_units\vehicle_units;
use App\Models\indivtarget\IndivTarget;
use App\Models\gcatarget\GcaTarget;
use App\Models\workschedule\WorkSchedule;

if (!function_exists('numberClean')) {

    /**
     * @return bool
     */
    function numberClean($number)
    {
        $precision_point = config('currency.precision_point');
        $decimal_sep = config('currency.decimal_sep');
        $thousand_sep = config('currency.thousand_sep');
        $number = str_replace($thousand_sep, "", $number);
        $number = str_replace($decimal_sep, ".", $number);
        $format = '%.' . $precision_point . 'f';
        $number = sprintf($format, $number);
        return $number;
    }
}
function date_for_database($input)
{
    $timestamp = strtotime($input);
   if($timestamp) {
       $date = new DateTime($input);
       //$date->modify('+1 day');
       $date = $date->format('Y-m-d');
       return $date;
   }
   else return null;
}

function datetime_for_database($input, $c = true)
{
    $date = new DateTime($input);
    if ($c) $date->modify('+1 day');
    $date = $date->format('Y-m-d H:i:s');
    return $date;
}

function amountFormat($number = 0, $currency = null)
{
    if (!$currency) {
        $precision_point = config('currency.precision_point');
        $decimal_sep = config('currency.decimal_sep');
        $thousand_sep = config('currency.thousand_sep');
        $symbol_position = config('currency.symbol_position');
        $symbol = config('currency.symbol');

    } else {
        $result = \App\Models\currency\Currency::withoutGlobalScopes()->where('id', '=', $currency)->first();

        $precision_point = $result->precision_point;
        $decimal_sep = $result->decimal_sep;
        $thousand_sep = $result->thousand_sep;
        $symbol_position = $result->symbol_position;
        $symbol = $result->symbol;

        if (config('currency.id') != $result->id) {

            $number = $number / config('currency.rate');
        }

    }

    $number = number_format($number, $precision_point, $decimal_sep, $thousand_sep);
    if ($symbol_position) {
        return $symbol . ' ' . $number;
    } else {
        return $number . ' ' . $symbol;
    }
}

function numberFormat($number = 0, $currency = null, $precision_point_off = false)
{
    if (!$currency) {
        $precision_point = config('currency.precision_point');
        $decimal_sep = config('currency.decimal_sep');
        $thousand_sep = config('currency.thousand_sep');
    } else {
        $result = \App\Models\currency\Currency::withoutGlobalScopes()->where('id', '=', $currency)->first();
        $precision_point = $result->precision_point;
        $decimal_sep = $result->decimal_sep;
        $thousand_sep = $result->thousand_sep;
    }
    if ($precision_point_off) $precision_point = 0;
    $number=(float)$number;
    $number = number_format($number, $precision_point, $decimal_sep, $thousand_sep);
    return $number;
}

function dateFormat($date = '', $local = false)
{
    if ($local AND strtotime($date)) return date($local, strtotime($date));
    if (strtotime($date)) return date('d-m-Y', strtotime($date));
    return date('d-m-Y');
}


function dateTimeFormat($date = '', $local = false)
{
    if ($local) return date($local, strtotime($date));
    if ($date) return date('d-m-Y H:i:s', strtotime($date));
}

function timeFormat($date = '')
{
    if ($date) return date('H:i:s', strtotime($date));
}
function encrypt_data($data = '')
{
   return base64_encode(base64_encode(base64_encode(strrev($data))));
}

function decrypt_data($data = '')
{
    return strrev(base64_decode(base64_decode(base64_decode($data))));
}

function this_month()
{
    $thismonth=Carbon::now();
    $thismonth=$thismonth->format("F Y");

    return $thismonth;
}

function this_year()
{
    $thisyear=Carbon::now();
    $thisyear=$thisyear->format("Y");

    return $thisyear;
}
function this_day()
{
    $today=Carbon::now();
    $today=$today->format("d-m-Y");

    return $today;
}

function unloggedattendance(){
    $today = Carbon::today()->format('Y-m-d');
    $days = Production_target::where('schedule_part','entire')->groupby('date')->whereBetween('date',[23-11-2021,$today])->get('date');
    $shop = Auth()->User()->section;
    if($shop == "" || $shop == "All"){}else{
        $unmarked = 0;
        foreach($days as $day){
            $marked = Attendance::where([['date','=',$day->date],['shop_id','=',$shop]])->first();
            if(empty($marked)){
                $unmarked += 1;
            }
        }
    }

    return $unmarked;
}

function pendingsubmission(){
    $today = Carbon::today()->format('Y-m-d');
    $shop = Auth()->User()->section;
    if($shop == "" || $shop == "All"){}else{
        $saved = Attendance_status::whereBetween('date',[23-11-2021,$today])
                ->where([['status_name','=','saved'],['shop_id','=',$shop]])->count();
    }

    return $saved;
}

function reviewwork(){
    $today = Carbon::today()->format('Y-m-d');
    $shop = Auth()->User()->section;
    if($shop == "" || $shop == "All"){}else{
        $saved = Attendance_status::whereBetween('date',[23-11-2021,$today])
                ->where([['status_name','=','review'],['shop_id','=',$shop]])->count();
    }
    return $saved;
}

function pendingapproval(){
    $today = Carbon::today()->format('Y-m-d');
    $shop = Auth()->User()->section;
    if($shop == "" || $shop == "All"){}else{
        $proddayys = Production_target::groupBy('date')->whereBetween('date',['2021-11-16',Carbon::today()->format('Y-m-d')])->get('date');
        $submitted = 0;
        foreach($proddayys as $pdate){
            $status = Attendance_status::where('date',$pdate->date)
                ->where([['status_name','=','submitted'],['shop_id','=',$shop]])->value('status_name');
            if($status == "submitted"){
                $submitted += 1;
            }
        }

    }
    return $submitted;
}

function shop(){
    $shop = Auth()->User()->section;
    if($shop == "" || $shop == "ALL"){
        $shop = "noshop";
    }
    return $shop;
}

function getuserrole(){
    foreach(Auth()->User()->getRoleNames() as $rol){
        $role = $rol;
    }
   return $role;
}


//PRODUCTION START AND END DATE
function getProductionstartdate($shopid,$today){
    $shopoffdays = [1=>4,2=>3,3=>2,5=>1,6=>1,8=>0,10=>0,11=>1,12=>0,13=>0,14=>0,15=>0,16=>0];

    $firstthismonth = Carbon::parse($today)->startOfMonth()->format('Y-m-d');
    $endthismonth = Carbon::parse($today)->endOfMonth()->format('Y-m-d');
    $monthschdates = Production_target::whereBetween('date', [$firstthismonth, $endthismonth])
            ->where('schedule_part','entire')->groupby('date')->orderBy('date', 'ASC')->get(['date']);
    if(count($monthschdates) > 0){
        foreach($monthschdates as $schdt){ $mnthprodndays[] = $schdt->date; }

        $allschdates = Production_target::where('schedule_part','entire')->groupby('date')->get(['date']);
        foreach($allschdates as $schdt){ $allprodndays[] = $schdt->date; }

        $pos = array_search($mnthprodndays[0], $allprodndays);
        $startpos = $pos - $shopoffdays[$shopid];
        $fstprodndaythismonth = $allschdates[$startpos];

        return $fstprodndaythismonth['date'];
    }else{
        return $today;
    }

}

function getProductionenddate($shopid,$today){
    $shopoffdays = [1=>4,2=>3,3=>2,5=>1,6=>1,8=>0,10=>0,11=>1,12=>0,13=>0,14=>0,15=>0,16=>0];

    $firstthismonth = Carbon::parse($today)->startOfMonth()->format('Y-m-d');
    $endthismonth = Carbon::parse($today)->endOfMonth()->format('Y-m-d');
    $monthschdates = Production_target::whereBetween('date', [$firstthismonth, $endthismonth])
            ->where('schedule_part','entire')->groupby('date')->orderBy('date', 'ASC')->get(['date']);
    foreach($monthschdates as $schdt){ $mnthprodndays[] = $schdt->date; }

    $allschdates = Production_target::where('schedule_part','entire')->groupby('date')->get(['date']);
    foreach($allschdates as $schdt){ $allprodndays[] = $schdt->date; }

    $pos = array_search(end($mnthprodndays), $allprodndays);
    $startpos = $pos - $shopoffdays[$shopid];
    $lstprodndaythismonth = $allschdates[$startpos];

    return $lstprodndaythismonth['date'];
}

function broughtfoward($shopid,$date){
    $shopids = [1,2,3,5,6];
    if(in_array($shopid,$shopids)){

        $today = carbon::today()->format('Y-m-d');
        $broughts = UnitsBroughtForward::where('shop_id',$shopid)->first();
        $unitsBF =  $broughts->noofunits;
        $startdate =  $broughts->begindate;

        $sumalltargets = Production_target::whereBetween('shop'.$shopid.'', [$startdate, $date])->sum('noofunits');
        $sumalltargets += $unitsBF;
        $sumactuals = Unitmovement::where('shop_id','=',$shopid)
                    ->whereBetween('datetime_out',[$startdate, $date])->count();
        $balance = $sumactuals - $sumalltargets;
        //$balance =  $unitsBF;
    }else{
        $balance = 0;
    }
    return $balance;
}

function getplantTLAtarget(){
    $shops = Shop::where('check_shop','=','1')->get(['id','report_name']);
    $normemps = 0; $noteaml = 0;
    foreach($shops as $sp){
        $normemps += Employee::where([['team_leader','no'],['shop_id',$sp->id],['status','Active']])->count();
        $noteaml += Employee::where([['team_leader','yes'],['shop_id',$sp->id],['status','Active']])->count();
    }
    $tlavailability = ($normemps/($normemps+$noteaml)) * 100;
    return $tlavailability;
}


function getshopTLAtarget($shopid){
    $normemps = Employee::where([['attachee','no'],['team_leader','no'],['shop_id',$shopid],['status','Active']])->count();
    $noteaml = Employee::where([['attachee','no'],['team_leader','yes'],['shop_id',$shopid],['status','Active']])->count();
    $tlavailability = ($normemps+$noteaml == 0) ? 0 : ($normemps/($normemps+$noteaml))*100;
    return $tlavailability;
}

function getTarget($date){
    $start = carbon::parse($date)->startOfMonth()->format('Y-m-d');
    $end = carbon::parse($date)->endOfMonth()->format('Y-m-d');
    $targets = IndivTarget::whereBetween('month',[$start,$end])->first();
    $targets = ($targets == "") ? 0 : $targets;

    return $targets;
}

function getPlantEfficiency($start, $end){
    $efshops = Shop::where('check_shop','=','1')->pluck('id')->all();

    $employees = Employee::withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'direct_hrs')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'othours')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'loaned_hrs')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'otloaned_hrs')->where('attachee','no')->get();


    $spMTDinput = 0;
    foreach($employees as $emp){
        $spMTDinput += (($emp->empattendance_sum_direct_hrs + $emp->empattendance_sum_othours) * 0.97875) + ($emp->empattendance_sum_loaned_hrs + $emp->empattendance_sum_otloaned_hrs);
    }

    $spMTDoutput = Unitmovement::whereBetween('datetime_out', [$start, $end])->sum('std_hrs');
    $spMTDplant_eff = ($spMTDinput > 0) ? round(($spMTDoutput/$spMTDinput)*100, 2) : 0;
    return $spMTDplant_eff;
}

function getshopEfficiency($start,$end,$shopid){
    $employees = Employee::withSum(['empattendance'=>function($query) use ($start,$end,$shopid) {
        $query->whereBetween('date', [$start, $end])->where('shop_id',$shopid);
    } ],'direct_hrs')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$shopid) {
        $query->whereBetween('date', [$start, $end])->where('shop_id',$shopid);
    } ],'othours')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$shopid) {
        $query->whereBetween('date', [$start, $end])->where('shop_loaned_to',$shopid);
    } ],'loaned_hrs')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$shopid) {
        $query->whereBetween('date', [$start, $end])->where('shop_loaned_to',$shopid);
    } ],'otloaned_hrs')->where([['attachee','no'],['shop_id',$shopid]])->get();


    $spMTDinput = 0;
    foreach($employees as $emp){
        $spMTDinput += (($emp->empattendance_sum_direct_hrs + $emp->empattendance_sum_othours) * 0.97875) + ($emp->empattendance_sum_loaned_hrs + $emp->empattendance_sum_otloaned_hrs);
    }

    $spMTDoutput = Unitmovement::whereBetween('datetime_out', [$start, $end])->where('shop_id',$shopid)->sum('std_hrs');
    $spMTDplant_eff = ($spMTDinput > 0) ? round(($spMTDoutput/$spMTDinput)*100, 2) : 0;
    return $spMTDplant_eff;
}

function getshopAbsenteeism($start,$end,$shopid){
    $employees = Employee::withSum(['empattendance'=>function($query) use ($start,$end,$shopid) {
        $query->whereBetween('date', [$start, $end])->where('shop_id',$shopid);
    } ],'direct_hrs')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$shopid) {
        $query->whereBetween('date', [$start, $end])->where('shop_id',$shopid);
    } ],'indirect_hrs')
    ->withCount(['empattendance'=>function($query) use ($start,$end,$shopid) {
        $query->whereBetween('date', [$start, $end])->where('shop_id',$shopid);
    } ])->where([['attachee','no'],['shop_id',$shopid]])->get();

    $workedhrs = 0; $expectedhrs = 0;
    foreach($employees as $emp){
        $workedhrs += $emp->empattendance_sum_direct_hrs + $emp->empattendance_sum_indirect_hrs;
        $expectedhrs += $emp->empattendance_count * 8;
    }
    $absenthrs = $expectedhrs - $workedhrs;
    ($absenthrs > 0) ? $MTDabsentiesm = round((($absenthrs/$expectedhrs)*100),2) : $MTDabsentiesm = 0;
    return $MTDabsentiesm;
}

function getAbsenteeism($start,$end){
    $abshops = [1,2,3,5,6,8,10,11,12,13,16,17,18,22,23,24,25]; //except   30,29,27,21,20,19
    $employees = Employee::withSum(['empattendance'=>function($query) use ($start,$end,$abshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$abshops);
    } ],'direct_hrs')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$abshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$abshops);
    } ],'indirect_hrs')
    ->withCount(['empattendance'=>function($query) use ($start,$end,$abshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$abshops);
    } ])->where('attachee','no')->get();


    $allattendance = Attendance::whereBetween('date', [$start, $end])->get('date');
    $workingdays = 0;
    foreach($allattendance as $attend){
        if((Carbon::parse($attend->date)->dayOfWeek == 0) || (Carbon::parse($attend)->dayOfWeek == 6)){
         }else{
            $workingdays += 8;
         }
    }

    $expectedhrs = $workingdays;

    $workedhours = Attendance::whereBetween('date', [$start, $end])
            ->sum(DB::raw('direct_hrs + indirect_hrs'));


    $absenthrs = $expectedhrs - $workedhours;

    ($absenthrs > 0) ? $MTDabsentiesm = round((($absenthrs/$expectedhrs)*100),2) : $MTDabsentiesm = 0;
    return  $MTDabsentiesm;
}

function getshopTLavailability($start,$end,$shopid){
$employees = Employee::withSum(['empattendance'=>function($query) use ($start,$end,$shopid) {
    $query->whereBetween('date', [$start, $end])->where('shop_id',$shopid);
} ],'direct_hrs')
->withSum(['empattendance'=>function($query) use ($start,$end,$shopid) {
    $query->whereBetween('date', [$start, $end])->where('shop_id',$shopid);
} ],'othours')
->withSum(['empattendance'=>function($query) use ($start,$end,$shopid) {
    $query->whereBetween('date', [$start, $end])->where('shop_id',$shopid);
} ],'indirect_hrs')
->withSum(['empattendance'=>function($query) use ($start,$end,$shopid) {
    $query->whereBetween('date', [$start, $end])->where('shop_id',$shopid);
} ],'indirect_othours')
->where([['team_leader','=','yes'],['status','=','Active']])->get();

$ttindirect = 0; $tthrs = 0;
foreach($employees as $emp){
    $ttindirect += $emp->empattendance_sum_indirect_othours + $emp->empattendance_sum_indirect_hrs;
    $tthrs += $emp->empattendance_sum_direct_hrs + $emp->empattendance_sum_othours + $emp->empattendance_sum_indirect_othours + $emp->empattendance_sum_indirect_hrs;
}
$tlavail = ($ttindirect > 0) ? round(($ttindirect/$tthrs)*100,2) : 0;
return $tlavail;

}

function getTLavailability($start,$end){
    $efshops = Shop::where('check_shop','=','1')->pluck('id')->all();
    $employees = Employee::withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'direct_hrs')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'othours')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'indirect_hrs')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'indirect_othours')
    ->where([['team_leader','=','yes'],['status','=','Active']])->get();

    $ttindirect = 0; $tthrs = 0;
    foreach($employees as $emp){
        $ttindirect += $emp->empattendance_sum_indirect_othours + $emp->empattendance_sum_indirect_hrs;
        $tthrs += $emp->empattendance_sum_direct_hrs + $emp->empattendance_sum_othours + $emp->empattendance_sum_indirect_othours + $emp->empattendance_sum_indirect_hrs;
    }
    $tlavail = ($ttindirect > 0) ? round(($ttindirect/$tthrs)*100,2) : 0;
    return $tlavail;
}


function getCVLCVEfficiency($start, $end,$section){
    if($section == 'cv'){
        $efshops = Shop::where('lcvcv_share','cv')->orwhere('lcvcv_share','share')->pluck('id')->all();
    }else{
         $efshops = Shop::where('lcvcv_share','lcv')->pluck('id')->all();
    }

    $employees = Employee::withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'direct_hrs')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'othours')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'loaned_hrs')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'otloaned_hrs')->where('attachee','no')->get();


    $spMTDinput = 0;
    foreach($employees as $emp){
        $spMTDinput += (($emp->empattendance_sum_direct_hrs + $emp->empattendance_sum_othours) * 0.97875) + ($emp->empattendance_sum_loaned_hrs + $emp->empattendance_sum_otloaned_hrs);
    }

    $spMTDoutput = Unitmovement::whereBetween('datetime_out', [$start, $end])->whereIn('shop_id',$efshops)->sum('std_hrs');
    $spMTDplant_eff = ($spMTDinput > 0) ? round(($spMTDoutput/$spMTDinput)*100, 2) : 0;
    return $spMTDplant_eff;
}

function getCVLCVAbsenteeism($start,$end,$section){
    if($section == 'cv'){
        $efshops = Shop::where('lcvcv_share','cv')->orwhere('lcvcv_share','share')->pluck('id')->all();
    }else{
         $efshops = Shop::where('lcvcv_share','lcv')->pluck('id')->all();
    }

    $employees = Employee::withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'direct_hrs')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'indirect_hrs')
    ->withCount(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ])->where('attachee','no')->get();

    $workedhrs = 0; $expectedhrs = 0;
    foreach($employees as $emp){
        $workedhrs += $emp->empattendance_sum_direct_hrs + $emp->empattendance_sum_indirect_hrs;
        $expectedhrs += $emp->empattendance_count * 8;
    }
    $absenthrs = $expectedhrs - $workedhrs;
    ($absenthrs > 0) ? $MTDabsentiesm = round((($absenthrs/$expectedhrs)*100),2) : $MTDabsentiesm = 0;
    return $MTDabsentiesm;
}

function getCVLCVTLavailability($start,$end,$section){
    if($section == 'cv'){
        $efshops = Shop::where('lcvcv_share','cv')->orwhere('lcvcv_share','share')->pluck('id')->all();
    }else{
         $efshops = Shop::where('lcvcv_share','lcv')->pluck('id')->all();
    }

    $employees = Employee::withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'direct_hrs')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'othours')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'indirect_hrs')
    ->withSum(['empattendance'=>function($query) use ($start,$end,$efshops) {
        $query->whereBetween('date', [$start, $end])->whereIn('shop_id',$efshops);
    } ],'indirect_othours')
    ->where([['team_leader','=','yes'],['status','=','Active']])->get();

    $ttindirect = 0; $tthrs = 0;
    foreach($employees as $emp){
        $ttindirect += $emp->empattendance_sum_indirect_othours + $emp->empattendance_sum_indirect_hrs;
        $tthrs += $emp->empattendance_sum_direct_hrs + $emp->empattendance_sum_othours + $emp->empattendance_sum_indirect_othours + $emp->empattendance_sum_indirect_hrs;
    }
    $tlavail = ($ttindirect > 0) ? round(($ttindirect/$tthrs)*100,2) : 0;
    return $tlavail;
}

function getGCATarget($date){
    $start = carbon::parse($date)->startOfMonth()->format('Y-m-d');
    $end = carbon::parse($date)->endOfMonth()->format('Y-m-d');
    $targets = GcaTarget::whereBetween('month',[$start,$end])->first();

    $targets = ($targets == "") ? 0 : $targets;

    return $targets;
}


//drl calculcations

function month_to_date_drl(){
    $startDate = Carbon::now(); //returns current day
    $endDate = Carbon::now(); //returns current day
    $firstDay = $startDate->firstOfMonth();
   $endDay = $endDate->endOfMonth();
    $start=$firstDay->format("Y-m-d");
    $end=$endDay->format("Y-m-d");

    //target
    $today=Carbon::now();
    $today=$today->format("Y-m-d");

    $start_date = date('Y-m-d 00:00:00', strtotime($start));

     $end_date = date('Y-m-d 23:59:59', strtotime($end));


    $drl_target=DrrTarget::where('target_type','Drl')->where('fromdate', '<', $today)->where('todate', '>', $today)->first();
    $drl_target_value=0;
    if(isset($drl_target)){
        $drl_target_value=$drl_target->plant_target;

    }

    $offlined_unit = Unitmovement::whereBetween('datetime_out',[$start,$end])->where('current_shop',0)->where('shop_id', 8)
    ->orWhere(function ($query) use ($start,$end) {
           $query->where('shop_id', 10)
		   ->whereBetween('datetime_out',[$start,$end])
		   ->where('current_shop',0);
 })->orWhere(function ($query) use ($start,$end) {
           $query->where('shop_id', 13)
		   ->whereBetween('datetime_out',[$start,$end])
		   ->where('current_shop',0);
 })->count();



   /*$defects_array = Unitmovement::whereBetween('datetime_out',[$start,$end])->where('current_shop',0)->where('shop_id', 8)
    ->orWhere(function ($query) use ($start,$end) {
           $query->where('shop_id', 10)
		   ->whereBetween('datetime_out',[$start,$end])
		   ->where('current_shop',0);
 })->orWhere(function ($query) use ($start,$end) {
           $query->where('shop_id', 13)
		   ->whereBetween('datetime_out',[$start,$end])
		   ->where('current_shop',0);
 })->pluck('vehicle_id')->all();*/


   $total_defects = Querydefect::whereBetween('created_at',[$start_date,$end_date])->where('is_defect','Yes')->where('is_complete','Yes')->count();

$master=array();
$drl=0;
if($offlined_unit>0){
 $drl=round(($total_defects/$offlined_unit)*100);
}
$master['total_offlined_units']= $offlined_unit;
$master['defects']= $total_defects;
$master['drl_target_value']=round($drl_target_value) ;
$master['drl']=$drl ;

   return $master;
}



function drl_today($date=NULL){

    if($date){

        $start=date_for_database($date);



    }else{


	 $today=Carbon::now();
     $start=$today->format("Y-m-d");


    }


    $current_shop=0;
    $vehicles = vehicle_units::groupBy('lot_no','model_id')->whereHas('unitmovement',function ($query) use( $current_shop,$start) {
     $query->where('current_shop', $current_shop);
     $query->where('datetime_out', $start);

     return $query;
})->get([ 'lot_no','model_id']);


  $drl_target=DrrTarget::where('target_type','Drl')->where('fromdate', '<', $start)->where('todate', '>', $start)->first();
    $drl_target_value=0;
    if(isset($drl_target)){
        $drl_target_value=$drl_target->plant_target;

    }


    $offlined_unit = Unitmovement::where('datetime_out',$start)->where('current_shop',0)->where('shop_id', 8)
    ->orWhere(function ($query) use ($start) {
           $query->where('shop_id', 10)
		   ->where('datetime_out',$start)
		   ->where('current_shop',0);
 })->orWhere(function ($query) use ($start) {
           $query->where('shop_id', 13)
		   ->where('datetime_out',$start)
		   ->where('current_shop',0);
 })->count();



$shops = Shop::where('check_point','=','1')->orderBy('group_order','asc')->get();
unset($shops[0],$shops[5],$shops[7],$shops[10]);



$drl_arr = [];
$total_defects=0;
foreach($vehicles as $vehicle){
 $modelid = $vehicle->model_id;
 $lot_no = $vehicle->lot_no;
 $shop_id_array=array();
 foreach($shops as $shop){
     $shopid = $shop->id;

     $shop_array=Shop::where('group_shop',$shopid)->pluck('id')->all();
     $wq = compact('modelid', 'lot_no');
       $units = Unitmovement::where([['shop_id', '=', $shopid], ['current_shop', '=', 0]])->where('datetime_out',$start)->whereHas('vehicle',function ($query) use( $wq) {
        $query->where('model_id', $wq['modelid'])->where('lot_no', $wq['lot_no']);
})->count();

$vehicle_array=Unitmovement::where([['shop_id', '=', $shopid], ['current_shop', '=', 0]])->where('datetime_out',$start)->whereHas('vehicle',function ($query) use( $wq) {
$query->where('model_id', $wq['modelid'])->where('lot_no', $wq['lot_no']);
})->pluck('vehicle_id')->all();

  $defects= Querydefect::whereIn('shop_id',$shop_array)->whereIn('vehicle_id',$vehicle_array)->where([['is_defect', '=', 'Yes']])->count();

  $total_defects+=$defects;

 }

}


$master=array();
$drl=0;
if($offlined_unit>0){
 $drl=round( (($total_defects/$offlined_unit)*100),2 );
}
$master['total_offlined_units']= $offlined_unit;
$master['defects']= $total_defects;
$master['drl_target_value']=round($drl_target_value);


$master['drl']=$drl ;


   return $master;
}


function month_to_date_drl_per_shop($shop_id){

    $startDate = Carbon::now(); //returns current day
			$endDate = Carbon::now(); //returns current day
    $firstDay = $startDate->firstOfMonth();
   $endDay = $endDate->endOfMonth();
    $start=$firstDay->format("Y-m-d");
    $end=$endDay->format("Y-m-d");

    //target
    $today=Carbon::now();
    $today=$today->format("Y-m-d");

    $shop_array=Shop::where('group_shop',$shop_id)->pluck('id')->all();


     $start_date = date('Y-m-d 00:00:00', strtotime($start));

     $end_date = date('Y-m-d 23:59:59', strtotime($end));






    $drl_target=DrrTarget::where('target_type','Drl')->where('fromdate', '<', $today)->where('todate', '>', $today)->first();
    $drl_target_value=0;
    if(isset($drl_target)){

        $drl_shop_target=DrrTargetShop::where('shop_id',$shop_id)->where('target_id', $drl_target->id)->first();

        $drl_target_value=$drl_shop_target->target_value;

    }

    $offlined_unit = Unitmovement::whereBetween('datetime_out',[$start,$end])->where('current_shop',0)->where('shop_id', $shop_id)->count();



   $defects_array = Unitmovement::whereBetween('datetime_out',[$start,$end])->where('current_shop',0)->where('shop_id', $shop_id)->pluck('vehicle_id')->all();



   $total_defects = Querydefect::wherein('vehicle_id', $defects_array)->where('is_defect','Yes')->wherein('shop_id', $shop_array)->where('is_complete','Yes')->count();

$master=array();
$drl=0;
if($offlined_unit>0){
 $drl=round(($total_defects/$offlined_unit)*100);
}
$master['total_offlined_units']= $offlined_unit;
$master['defects']= $total_defects;
$master['drl_target_value']=round($drl_target_value) ;
$master['drl']=$drl ;

   return $master;
}




function drl_per_shop_today($shop_id,$date=NULL){
    if($date){

        $start=date_for_database($date);




    }else{

        $today=Carbon::now();
        $start=$today->format("Y-m-d");

      /* $startDate = Carbon::now();
       $firstDay = $startDate->firstOfMonth();
       $start=$firstDay->format("Y-m-d");




       $start_date = date('Y-m-d 00:00:00', strtotime($end));

      $end_date = date('Y-m-d 23:59:59', strtotime($end));*/

    }





    $shop_array=Shop::where('group_shop',$shop_id)->pluck('id')->all();


 $drl_target=DrrTarget::where('target_type','Drl')->where('fromdate', '<', $start)->where('todate', '>', $start)->first();
   $drl_target_value=0;
   if(isset($drl_target)){
    $drl_shop_target=DrrTargetShop::where('shop_id',$shop_id)->where('target_id', $drl_target->id)->first();

    $drl_target_value=$drl_shop_target->target_value;

   }


   $offlined_unit = Unitmovement::where('datetime_out',$start)->where('current_shop',0)->where('shop_id', $shop_id)->count();


  $defects_array = Unitmovement::where('datetime_out',$start)->where('current_shop',0)->where('shop_id', $shop_id)->pluck('vehicle_id')->all();



  $total_defects = Querydefect::wherein('vehicle_id', $defects_array)->where('is_defect','Yes')->where('is_defect','Yes')->wherein('shop_id', $shop_array)->count();

$master=array();
$drl=0;
if($offlined_unit>0){
$drl=round(  (($total_defects/$offlined_unit)*100 ),2  );
}
$master['total_offlined_units']= $offlined_unit;
$master['defects']= $total_defects;
$master['drl_target_value']=round($drl_target_value);


$master['drl']=$drl ;

  return $master;
}

//end drl calculcations

function month_to_date_drr(){

    $startDate = Carbon::now(); //returns current day
    $endDate = Carbon::now(); //returns current day
    $firstDay = $startDate->firstOfMonth();
   $endDay = $endDate->endOfMonth();
    $start=$firstDay->format("Y-m-d");
    $end=$endDay->format("Y-m-d");

    //target
    $today=Carbon::now();
    $today=$today->format("Y-m-d");
    $shops_array=array(28,15,16);
   $drr_array=array();

   $drr_target=DrrTarget::where('target_type','Drr')->where('fromdate', '<', $today)->where('todate', '>', $today)->first();
   $drr_target_value=0;
   if(isset($drr_target)){
       $drr_target_value=$drr_target->plant_target;

   }


    foreach($shops_array as $val){

        $units_through_abc = Unitmovement::whereBetween('datetime_out',[$start,$end])->where('current_shop',0)->where('shop_id', $val)->count();
        $units_array = Unitmovement::whereBetween('datetime_out',[$start,$end])->where('current_shop',0)->where('shop_id', $val)->pluck('vehicle_id')->all();

        $total_units_with_defects = Drr::where('use_drr','1')->wherein('vehicle_id', $units_array)->where('shop_id', $val)->count();
        $ok_units=  $units_through_abc -$total_units_with_defects ;


       $drr_pershop=0;

       if($units_through_abc >0){

        $drr_pershop= round((($ok_units/$units_through_abc)*100),2);

       }

       $drr_array[] = $drr_pershop;


    }

   $plant_drr=round((($drr_array[0]*$drr_array[1]*$drr_array[2])/1000000)*100);

    $master=array();
    $master['plant_drr']=$plant_drr;
    $master['plant_mpa']=$drr_array[0];
    $master['plant_mpb']=$drr_array[1];
    $master['care']=round($drr_array[2],1);
    $master['drr_target_value']=round($drr_target_value);
    $master['care_target_value']=100;

    return $master;



}





function today_drr(){

    $today=Carbon::now();
    $end=$today->format("Y-m-d");

    //target
    $today=Carbon::now();
    $today=$today->format("Y-m-d");
    $shops_array=array(28,15,16);
   $drr_array=array();

   $drr_target=DrrTarget::where('target_type','Drr')->where('fromdate', '<', $today)->where('todate', '>', $today)->first();
   $drr_target_value=0;
   if(isset($drr_target)){
       $drr_target_value=$drr_target->plant_target;

   }


    foreach($shops_array as $val){

        $units_through_abc = Unitmovement::where('datetime_out',$end)->where('current_shop',0)->where('shop_id', $val)->count();
        $units_array = Unitmovement::where('datetime_out',$end)->where('current_shop',0)->where('shop_id', $val)->pluck('vehicle_id')->all();

        $total_units_with_defects = Drr::where('use_drr','1')->wherein('vehicle_id', $units_array)->where('shop_id', $val)->count();
        $ok_units=  $units_through_abc -$total_units_with_defects ;


       $drr_pershop=0;

       if($units_through_abc >0){

        $drr_pershop= round((($ok_units/$units_through_abc)*100),2);

       }

       $drr_array[] = $drr_pershop;


    }

   $plant_drr=round( (($drr_array[0]*$drr_array[1]*$drr_array[2])/1000000)*100 );

    $master=array();
    $master['plant_drr']=$plant_drr;
    $master['plant_drr']=$plant_drr;
    $master['plant_mpa']=$drr_array[0];
    $master['plant_mpb']=$drr_array[1];
    $master['care']=round($drr_array[2],1);
    $master['drr_target_value']=round($drr_target_value);

    return $master;
}


 function check_units_complete($shop,$vehicle_id)
{

    return Unitmovement::where('shop_id', $shop)->where('vehicle_id', $vehicle_id)->where('current_shop', '0')->exists();

}


