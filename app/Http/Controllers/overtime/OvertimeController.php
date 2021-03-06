<?php

namespace App\Http\Controllers\overtime;

use App\Models\overtime\Overtime;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\shop\Shop;
use App\Models\attendance\Attendance;
use App\Models\attendancepreview\AttendancePreview;
use App\Models\overtimepreview\Overtimepreview;
use App\Models\overtimeremarks\Overtimeremarks;
use App\Models\employee\Employee;
use App\Models\defaultattendance\DefaultAttendanceHRS;
use App\Models\productiontarget\Production_target;
use App\Models\workschedule\WorkSchedule;
use App\Models\attendancestatus\Attendance_status;
use App\Models\reviewconversation\Review_conversation;

use App\Models\authorisedhrs\AuthorisedHrs;
use App\Models\vehicle_units\vehicle_units;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\Validator;

use App\Exports\OvertimeExport;
use App\Exports\OvertimeExportView;
use Excel;

class OvertimeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

     //Permission::create(['name' => 'overtime-mark']);
        //Permission::create(['name' => 'overtime-preview']);
        //Permission::create(['name' => 'overtime-report']);
    function __construct()
    {
         $this->middleware('permission:overtime-mark', ['only' => ['index','markovertime','store']]);

         $this->middleware('permission:overtime-report', ['only' => ['overtimereport']]);

         $this->middleware('permission:overtime-preview', ['only' => ['otpreview','checkovertime','confirmovertime','previewstore']]);

         $this->middleware('permission:auth-hrs', ['only' => ['authorisedhrs','saveauthhrs','destroyauthhrs','previewstore']]);
    }


    public function index(){
    $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        $shops = Shop::where('overtime','=','1')->get(['report_name','id']);
        $selectshops = Shop::where('overtime','=','1')->pluck('report_name','id');
        foreach($shops as $sp){
            $names[] = $sp->report_name;

            $check = Overtimepreview::where([['date', '=', $today], ['shop_id', '=', $sp->id]])->first();
            $checky = Overtimepreview::where([['date', '=', $yesterday], ['shop_id', '=', $sp->id]])->first();
            $confirmedtoday[] = ($check != null) ? "check" : "";
            $confirmedyesterday[] = ($checky != null) ? "check" : "";

            $mkd = Overtime::where([['date', '=', $today], ['shop_id', '=', $sp->id]])->first('id');
            $colord[] = ($mkd != null) ? "warning" : "cyan";

            $mkdy = Overtime::where([['date', '=', $yesterday], ['shop_id', '=', $sp->id]])->first('id');
            $colory[] = ($mkdy != null) ? "warning" : "cyan";

            $count_TT[] = Employee::where([['shop_id', '=', $sp->id],['status','=','Active']])->count('id');

            $empids = Employee::where([['shop_id','=',$sp->id],['status','=','Active']])->get('id');
            $presenttoday = 0; $presentyesterday = 0;
            foreach($empids as $empid){
                $hrs = Overtime::Where([['date', '=', $today],['emp_id','=',$empid->id]])
                        ->sum(DB::raw('othours'));
                ($hrs > 0) ? $presenttoday = $presenttoday + 1 : $presenttoday = $presenttoday;

                $hrs1 = Overtime::Where([['date', '=', $yesterday],['emp_id','=',$empid->id]])
                        ->sum(DB::raw('othours'));
                ($hrs1 > 0) ? $presentyesterday = $presentyesterday + 1 : $presentyesterday = $presentyesterday;
            }
            $count_presenttoday[] = $presenttoday;
            $count_presentyesterday[] = $presentyesterday;

        }
        //return $count_presenttoday;
        $data = array(
            'shops' => $selectshops,
            'names' =>$names,
            'colord'=>$colord,
            'colory'=>$colory,
            'count_TT'=>$count_TT,
            'count_presenttoday'=>$count_presenttoday,
            'count_presentyesterday'=>$count_presentyesterday,
            'confirmedtoday'=>$confirmedtoday,
            'confirmedyesterday'=>$confirmedyesterday,

        );

        return view('overtime.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function markovertime(Request $request)
    {
        $mdate =$request->input('mdate');
        $shopid = $request->input('shop');

        $empexist = Employee::where('shop_id','=',$shopid)->first();
        if(empty($empexist)){
            Toastr::error('Sorry, No employee in that shop/section.','Whooops!');
            return back();
        }

        $date = Carbon::createFromFormat('m/d/Y', $mdate)->format('Y-m-d');

        $checkconfirmed = Overtimepreview::where([['shop_id','=',$shopid],['date','=',$date]])->first();
        if($checkconfirmed != null){
            Toastr::error('Attendance already confirmed.','Access denied!');
            return back();
        }

        $shopname = Shop::where([['id', $shopid],['overtime','=','1']])->value('report_name');
        $allshops = Shop::where('overtime','=','1')->get(['id','report_name']);
        $shopno = 0;
        foreach($allshops as $one){
            if($one->report_name == $shopname){
                break;
            }
            $shopno++;
        }

        unset($allshops[$shopno]);

            $marked = Overtime::where([['date', '=', $date], ['shop_id', '=', $shopid]])->first();

            if($marked != null){$marked = "Marked";
                //Attendance::where([['date', '=', $date], ['shop_id', '=', $shopid]])->get('id');

                $staffs = Employee::leftJoin('overtimes', function($join){
                    $join->on('employees.id', '=', 'overtimes.emp_id');
                    })
                    ->where([['overtimes.date', '=', $date], ['overtimes.shop_id', '=', $shopid],['employees.status', '=', 'Active']])
                    ->get(['employees.id','employees.staff_no','employees.staff_name','employees.team_leader','employees.status',
                    'overtimes.othours','overtimes.shop_loaned_to','overtimes.loaned_hrs']);

                    $id = DefaultAttendanceHRS::orderBy('id', 'desc')->take(1)->value('id');

                    $hrslimit = DefaultAttendanceHRS::where('id','=',$id)->value('hrslimit');

                    $data = array(
                        'num' => 1, 'direct'=> 0, 'indirect'=> 0, 'hrslimit'=>$hrslimit,
                         'i'=>0,
                        'staffs'=>$staffs,

                        'shop' => $shopname,
                        'shopid' => $shopid,
                        'shops' => $allshops,
                        'date' => $date,
                        'marked' => $marked,
                        'btncolor' => 'warning', 'btntext' => 'Update'
                    );
                    return view('overtime.mark')->with($data);

            }else{
                $marked = "Not Marked";
                $id = DefaultAttendanceHRS::orderBy('id', 'desc')->take(1)->value('id');

                $hrslimit = DefaultAttendanceHRS::where('id','=',$id)->value('hrslimit');
                $overtime = DefaultAttendanceHRS::where('id','=',$id)->value('overtime');
                $data = array(
                    'num' => 1,'hrslimit'=>$hrslimit, 'overtime'=>$overtime,
                    'staffs' => Employee::where([['shop_id', $shopid],['status','=','Active']])->get(['id','staff_no','staff_name','team_leader']),
                    'shops' => $allshops,
                    'set' => DefaultAttendanceHRS::All(),
                    'shop' => $shopname,
                    'shopid' => $shopid,
                    'date' => $date,
                    'marked' => $marked,
                    'btncolor' => 'info', 'btntext' => 'Save'
                );


                return view('overtime.mark')->with($data);

            }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $shop_id = $request->input('shop_id');
        $date = $request->input('date');
        $empid =  $request->input('staff_id');
        $overtime =  $request->input('overtime');
        $shoptoid =  $request->input('shoptoid');
        $loaned =  $request->input('loaned');

        try{
            $markedid = Overtime::where([['shop_id', $shop_id],['date','=',$date]])->get('id');
            DB::beginTransaction();
            if(count($markedid) > 0){
                for($i = 0; $i < count($empid); $i++){
                    $ot = Overtime::find($markedid[$i]->id);
                    $ot->emp_id = $empid[$i];
                    $ot->othours = ($overtime[$i] == null)? 0 : $overtime[$i];
                    $ot->shop_id = $shop_id;
                    $ot->loaned_hrs = ($loaned[$i] == null)? 0 : $loaned[$i];
                    $ot->shop_loaned_to = ($shoptoid[$i] == null)? 0 : $shoptoid[$i];

                    $ot->user_id = auth()->user()->id;
                    $ot->date = $date;

                    $ot->save();
                }
            }else{
                for($i = 0; $i < count($empid); $i++){
                    $ot = new Overtime;
                    $ot->emp_id = $empid[$i];
                    $ot->othours = ($overtime[$i] == null)? 0 : $overtime[$i];
                    $ot->shop_id = $shop_id;
                    $ot->loaned_hrs = ($loaned[$i] == null)? 0 : $loaned[$i];
                    $ot->shop_loaned_to = ($shoptoid[$i] == null)? 0 : $shoptoid[$i];
                    $ot->user_id = auth()->user()->id;
                    $ot->date = $date;

                    $ot->save();
                }

            }

            DB::commit();

            Toastr::success('Success, Overtime saved successfully.','Saved!');
            return redirect('overtime');
        }

        catch(\Exception $e){
            DB::rollback();
            Toastr::error('Sorry, An error occured.','Whooops!');
            return redirect('overtime');
        }


    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Overtime  $overtime
     * @return \Illuminate\Http\Response
     */
    public function overtimereport(Request $request)
    {
        $selectauth = AuthorisedHrs::get(['id','datefrom','dateto']);
        $selectshops = Shop::where('overtime','=','1')->pluck('report_name','id');
        if($request->input()){ //return  $request->input('mdate');
            $daterange = $request->input('daterange');
            $datearr = explode('-',$daterange);
            $datefrom = Carbon::parse($datearr[0])->format('Y-m-d');
            $dateto = Carbon::parse($datearr[1])->format('Y-m-d');
            $shopid = $request->input('shop');
        }else{
            $dateto = Carbon::now()->format('Y-m-d');
            $datefrom = Carbon::parse($dateto)->subDays(30)->format('Y-m-d');
            if(shop() == "noshop"){
                $shopid = 1;
            }else{
                $shopid = Auth()->User()->section;
            }
        }

        $weekMap = [0 => 'Sun',1 => 'Mon',2 => 'Tue',3 => 'Wed',4 => 'Thu',5 => 'Fri',6 => 'Sat'];

        $empexist = Employee::where('shop_id','=',$shopid)->first();
        if(empty($empexist)){
            Toastr::error('Sorry, No employee in that shop/section.','Whooops!');
            return back();
        }
        $shopname = Shop::where('id','=',$shopid)->value('report_name');

		if($shopid ==17){
			$emps = Employee::where('shop_id','=',$shopid)->get(['id','staff_no','staff_name','status']);
		}else{
			$emps = Employee::where([['shop_id','=',$shopid],['outsource','no']])->get(['id','staff_no','staff_name','status']);
		}
        $empcount = Employee::where('shop_id','=',$shopid)->count();

        $ttsumhrs = 0;
        $unauth = 1;

        foreach($emps as $emp){
            //Check OT for inactive
            $sumOT = Attendance::whereBetween('date',[$datefrom,$dateto])
                    ->where([['staff_id',$emp->id],['shop_id',$shopid]])->sum(DB::raw('othours + indirect_othours + otloaned_hrs'));
            if($sumOT == 0 && $emp->status == 'Inactive'){
                $removestaff[$emp->id] = 1;
            }else{
                $removestaff[$emp->id] = 0;
            }
                    $sato = 0; $sun = 0;
            $datefrom1 = $datefrom; $dates = []; $wkdys = []; $authhrs = []; $sumhrs= 0;
            while ($datefrom1 <= $dateto) {
                $dates[] = Carbon::createFromFormat('Y-m-d', $datefrom1)->format('jS');
                $hrs = Attendance::where([['date','=',$datefrom1],['staff_id','=',$emp->id]])
                                ->sum(DB::raw('othours + indirect_othours + otloaned_hrs'));
                $othrs[$emp->id][] = ($hrs == null) ? 0 : $hrs;
                $sumhrs += ($hrs == null) ? 0 : $hrs;


                $dayname = Carbon::parse($datefrom1)->format('l');
                $dayOfTheWeek = Carbon::parse($datefrom1)->dayOfWeek;
                $wkdys[] = $weekMap[$dayOfTheWeek];

                $authhrs[] = Attendance::where([['date','=',$datefrom1],['staff_id','=',$emp->id]])->value('auth_othrs');

                //CHECK APPROVAL
                $checkloan = Attendance_status::where([['shop_id','=',$shopid],['date','=',$datefrom1]])->value('status_name');

                if($checkloan === "approved"){
                    $unauth = 1;
                    $authshow[$emp->id][] = 0;
                }else{
					if($checkloan == null){
						$authshow[$emp->id][] = 0;
					}else{
						$unauth = 0;
						$authshow[$emp->id][] = 1;
					}
                }

				$empauth[$emp->id][] = $unauth;

                 //Totals per employee
                 $empauthhrs = Attendance::where([['date','=',$datefrom1],['staff_id','=',$emp->id]])->value('auth_othrs');
                 $holiday = WorkSchedule::where('date','=',$datefrom1)->first();
                 if(!empty($empauthhrs)){
                    $holiday = WorkSchedule::where('date','=',$datefrom1)->first();
                    $dayno = Carbon::parse($datefrom1)->dayOfWeek;
                     if($dayno == 0 || !empty($holiday)){

                         $sun += Attendance::where([['date','=',$datefrom1],['staff_id','=',$emp->id]])
                                     ->sum(DB::raw('othours + indirect_othours + otloaned_hrs'));
                     }else{
                        $sato += Attendance::where([['date','=',$datefrom1],['staff_id','=',$emp->id]])
                                ->sum(DB::raw('othours + indirect_othours + otloaned_hrs'));
                     }
                 }else{

                 }

                $datefrom1 = Carbon::parse($datefrom1)->addDays(1)->format('Y-m-d');
            }

			$empunauth[] = in_array(0,$empauth[$emp->id]);

            $emptthrs[$emp->id] = $sumhrs;
            $ttsumhrs += $sumhrs;

            $saturday[$emp->id] = $sato;
            $sunday[$emp->id] = $sun;
        }

		$otunauth = in_array(true, $empunauth);




        //TOTALS
        $datefrom2 = $datefrom; $tt = 0; $ttauthhrs = 0;
        while ($datefrom2 <= $dateto) {
            $tthrs = Attendance::where([['date','=',$datefrom2],['shop_id','=',$shopid]])
                        ->sum(DB::raw('othours + indirect_othours + otloaned_hrs'));
            $ttauthhrs = Attendance::where([['date','=',$datefrom2],['shop_id','=',$shopid]])->sum('auth_othrs');
            $cumauthhrs[] = $ttauthhrs;
            $ttothrs[] = ($tthrs == null) ? 0 : $tthrs;
             $tt += ($tthrs == null) ? 0 : $tthrs;
             $cumttothrs[] = $tt;

              $holiday1 = WorkSchedule::where('date','=',$datefrom2)->first();
             if($holiday1 != ""){
                $hhh = "SU_H";
             }else{
                $dayno = Carbon::parse($datefrom2)->dayOfWeek;
                if($dayno == 0){
                    $hhh = "SU_H";
                }elseif($dayno == 6){
                    $hhh = "SAT";
                }else{
                    $hhh = "-";
                }

             }

             $holi[] = $hhh;
            $datefrom2 = Carbon::parse($datefrom2)->addDays(1)->format('Y-m-d');
        }

        $range = Carbon::createFromFormat('Y-m-d', $datefrom)->format('jS M Y').' To '.Carbon::createFromFormat('Y-m-d', $dateto)->format('jS M Y');

        //return $removestaff;
        $data = array(
			'otunauth'=>$otunauth,
            'saturday'=>$saturday, 'sunday'=>$sunday, 'holi'=>$holi,
            'shopname'=>$shopname,'selectauth'=>$selectauth, 'shopid'=>$shopid, 'daterange'=>$datefrom.'+'.$dateto,
            'range'=>$range, 'unauth'=>$unauth, 'authshow'=>$authshow,
            'selectshops'=>$selectshops, 'empcount'=>$empcount,
            'dates'=>$dates, 'authhrs'=>$authhrs, 'wkdys'=>$wkdys,
            'emps'=>$emps, 'cumttothrs'=>$cumttothrs, 'cumauthhrs'=>$cumauthhrs,
            'othrs'=>$othrs, 'emptthrs'=>$emptthrs,
            'ttothrs'=>$ttothrs, 'ttsumhrs'=>$ttsumhrs,
            'tlname'=>Employee::where([['shop_id','=',$shopid],['team_leader','=','yes']])->value('staff_name'),
            'removestaff'=>$removestaff,
        );
        return view('overtime.otreport')->with($data);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Overtime  $overtime
     * @return \Illuminate\Http\Response
     */
    public function authorisedhrs()
    {
        $activeauth = AuthorisedHrs::max('id');
        if(empty($activeauth)){
            $from = Carbon::today()->format('Y-m-d H:i:s');
            $to = Carbon::today()->format('Y-m-d H:i:s');
        }else{
            $from =  AuthorisedHrs::where('id','=',$activeauth)->value('datefrom');
            $to =  AuthorisedHrs::where('id','=',$activeauth)->value('dateto');
        }


        $range = Carbon::createFromFormat('Y-m-d H:i:s', $from)->format('jS M Y').' To '.Carbon::createFromFormat('Y-m-d H:i:s', $to)->format('jS M Y');
         $plantauthhrs = AuthorisedHrs::where('id','=',$activeauth)->get(['id','datefrom','dateto','weekdayhrs','wknd_hdayhrs']);

        $authhrs = AuthorisedHrs::get(['id','datefrom','dateto','weekdayhrs','wknd_hdayhrs']);
        $data = array(
            'plantauthhrs'=>$plantauthhrs,
            'authhrs'=>$authhrs,

        );
        return view('overtime.authorisedindex')->with($data);
    }

    public function saveauthhrs(Request $request){
        $validator = Validator::make($request->all(), [
            'daterange' => 'required',
            'weekday' => 'required',
            'weekend' => 'required',

        ]);

        if ($validator->fails()) {
            Toastr::error('Sorry! All fields are required.');
            return back();
        }
            $daterange = $request->input('daterange');
            $datearr = explode('-',$daterange);
            $datefrom = Carbon::parse($datearr[0])->format('Y-m-d');
            $dateto = Carbon::parse($datearr[1])->format('Y-m-d');

            try{
                DB::beginTransaction();
                $autho = new AuthorisedHrs;
                $autho->datefrom = $datefrom;
                $autho->dateto = $dateto;
                $autho->weekdayhrs = $request->input('weekday');
                $autho->wknd_hdayhrs = $request->input('weekend');
                $autho->user_id = Auth()->User()->id;
                $autho->save();
                DB::commit();

                Toastr::success('Attendance saved successfully','Saved');
                return back();

            }
            catch(\Exception $e){
                DB::rollBack();
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
                Toastr::error('Sorry! An error occured hours not saved.','Error');
            }
    }


    public function destroyauthhrs($id){
        if (request()->ajax()) {
            try {
                $can_be_deleted = true;
                $error_msg = '';

                //Check if any routing has been done
               //do logic here
               $othrs = AuthorisedHrs::where('id', $id)->first();

                if ($can_be_deleted) {
                    if (!empty($othrs)) {
                        DB::beginTransaction();
                        //Delete Query  details
                        AuthorisedHrs::where('id', $id)->delete();
                        $othrs->delete();
                        DB::commit();

                        $output = ['success' => true,
                                'msg' => "Target Deleted Successfully"
                            ];
                    }else{
                        $output = ['success' => false,
                                'msg' => "Could not be deleted, Child record exist."
                            ];
                    }
                } else {
                    $output = ['success' => false,
                                'msg' => $error_msg
                            ];
                }
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                $output = ['success' => false,
                                'msg' => "Something Went Wrong"
                            ];
            }
            return $output;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Overtime  $overtime
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Overtime $overtime)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Overtime  $overtime
     * @return \Illuminate\Http\Response
     */
    public function destroy(Overtime $overtime)
    {
        //
    }

    //AUTHORIZE OVERTIME INDEX PAGE
    public function otpreview(){
        $today = Carbon::today()->format('Y-m-d');
        $yesterday = Carbon::yesterday()->format('Y-m-d');

        $shops = Shop::where('overtime','=','1')->get(['id','report_name']);
        $selectshops = Shop::pluck('report_name','id');
        foreach($shops as $sp){
            $names[] = $sp->report_name;

            $check1 = Overtimepreview::where([['date', '=', $today], ['shop_id', '=', $sp->id]])->first();
            $check = Attendance::where([['date', '=', $today], ['shop_id', '=', $sp->id]])->first();
            $confirmedtoday[] = ($check1 != null) ? "check" : "";
            $colord[] = ($check != null) ? "warning" : "success";

            $checky1 = Overtimepreview::where([['date', '=', $yesterday], ['shop_id', '=', $sp->id]])->first();
            $checky = Attendance::where([['date', '=', $yesterday], ['shop_id', '=', $sp->id]])->first();
            $confirmedyesterday[] = ($checky1 != null) ? "check" : "";
            $colory[] = ($checky != null) ? "warning" : "success";

            $count_TTtd[] = Employee::where([['shop_id', '=', $sp->id],['status','=','Active']])->count('id');
            $count_TTy[] = Employee::where([['shop_id', '=', $sp->id],['status','=','Active']])->count('id');

            $empids = Employee::where([['shop_id','=',$sp->id],['status','=','Active']])->get('id');
            $presenttoday = 0; $presentyesterday = 0;
            foreach($empids as $empid){
                $hrs = Attendance::Where([['date', '=', $today],['staff_id','=',$empid->id]])
                        ->sum(DB::raw('othours + indirect_othours + otloaned_hrs'));
                ($hrs > 0) ? $presenttoday = $presenttoday + 1 : $presenttoday = $presenttoday;

                $hrs1 = Attendance::Where([['date', '=', $yesterday],['staff_id','=',$empid->id]])
                        ->sum(DB::raw('othours + indirect_othours + otloaned_hrs'));
                ($hrs1 > 0) ? $presentyesterday = $presentyesterday + 1 : $presentyesterday = $presentyesterday;
            }
            $count_presenttoday[] = $presenttoday;
            $count_presentyesterday[] = $presentyesterday;
        }
        //return $count_presenttoday;
        $data = array(
            'shops' => Shop::where('overtime','=','1')->pluck('report_name','id'),
            'names' =>$names,
            'colord'=>$colord,
            'colory'=>$colory,
            'count_TTtd'=>$count_TTtd, 'count_TTy'=>$count_TTy,
            'count_presenttoday'=>$count_presenttoday,
            'count_presentyesterday'=>$count_presentyesterday,
            'confirmedtoday'=>$confirmedtoday,
            'confirmedyesterday'=>$confirmedyesterday,
        );
        return view('overtime.otpreview')->with($data);
    }

    //AUTHORIZE OVERTIME HOURS
    public function checkovertime(Request $request){
            $mdate =$request->input('mdate');
            $shopid = $request->input('shop');
         $date = Carbon::createFromFormat('m/d/Y', $mdate)->format('Y-m-d');

         $prodday = Production_target::where('date','=',$date)->first();
        $holi = WorkSchedule::where('date','=',$date)->value('holidayname');
        if(!empty($holi)){
            $dayname = $holi;
        }else{
            $dayOfTheWeek = Carbon::parse($mdate)->dayOfWeek;
            $weekMap = [0 => 'Sun',1 => 'Mon',2 => 'Tue',3 => 'Wed',4 => 'Thu',5 => 'Fri',6 => 'Sat'];
            $dayname = $weekMap[$dayOfTheWeek];
        }
        //return $date;
        $remarks = Overtimeremarks::where([['date', '=', $date], ['shop_id', '=', $shopid]])->value('remarks');
        $marked = Attendance::where([['date', '=', $date], ['shop_id', '=', $shopid]])->first();
        $mk = ($marked) ? "Marked" : "Not Marked";
        //$indirectshop = Shop::where('id','=',$shopid)->value('check_shop');
        if($marked != null){
            //$shopname = Shop::where('id', $shopid)->value('shop_name');
             $shopname = Shop::where([['id', $shopid],['overtime','=','1']])->value('report_name');
             $allshops = Shop::where('overtime','=','1')->get(['id','report_name']);
             $shopno = 0;
             foreach($allshops as $one){
                 if($one->report_name == $shopname){
                     break;
                 }
                 $shopno++;
             }

             unset($allshops[$shopno]);
             $indirectshop = Shop::where('id','=',$shopid)->value('check_shop');
            //SUBMISSION STATUS
            $attstatus = Attendance_status::where([['shop_id','=',$shopid],['date','=',$date]])->first();
                if($attstatus == "" || $attstatus->ot_status_name == "saved"){
                    Toastr::error('Sorry! Attendance Not Yet Marked','Not Marked');
                    return back();

                }
            $date = Carbon::createFromFormat('m/d/Y', $mdate)->format('Y-m-d');

                $staffs = Attendance::where([['date', '=', $date], ['shop_id', '=', $shopid]])->get();

                    //$confirm = Overtimepreview::where([['date', '=', $date], ['shop_id', '=', $shopid]])->first();
                    //$icon = $confirm ? 'check' : 'window-minimize';
                    //$color = $confirm ? 'success' : 'danger';
                    //$disabled = $confirm ? 'disabled' : 'enabled';
                    //$text = $confirm ? 'Overtime Confirmed' : 'Confirm Overtime';

                    $id = DefaultAttendanceHRS::orderBy('id', 'desc')->take(1)->value('id');
                    $hrslimit = DefaultAttendanceHRS::where('id','=',$id)->value('hrslimit');
                    $overtime = DefaultAttendanceHRS::where('id','=',$id)->value('overtime');

                    //CHECK LOANEES
                    $loanee = Attendance::where([['date', '=', $date], ['otshop_loaned_to', '=', $shopid]])->first();
                    $check = Attendance::where([['loan_confirm', '=', 1],['date', '=', $date], ['otshop_loaned_to', '=', $shopid]])
                                            ->first();

                    $data = array(
                        'num' => 1, 'i'=>0, 'hrslimit'=>$hrslimit, 'overtime'=>$overtime,
                        'staffs'=>$staffs,'text'=>$text, 'shops'=>$allshops,
                        'icon'=>$icon,'color'=>$color,'disabled'=>$disabled,
                        'shop' => $shopname, 'loanee'=>$loanee,
                        'shopid' => $shopid, 'dayname'=>$dayname, 'prodday'=>$prodday,
                        'date' => Carbon::createFromFormat('Y-m-d', $date)->format('d M Y'),
                        'remarks'=>$remarks,
                        'marked' => $mk,'attstatus'=>$attstatus,
                        'btncolor' => 'warning', 'btntext' => 'Update',

                        'color1'=>($check) ? 'success' : 'danger',
                        'text1'=>($check) ? 'View Loaned' : 'Approve Loaned',
                        'icon1'=>($check) ? 'check' : 'window-minimize',
                    );
                    return view('overtime.previewindex')->with($data);


        }else{
            Toastr::error('Sorry! Overtime Not Yet Marked','Not Marked');
            return back();
        }
    }


    //CONFIRM OVERTIME
    public function confirmovertime(Request $request){
        $preview = new Overtimepreview;
        $date =  $request->input('date');
        $date = Carbon::createFromFormat('d F Y', $date)->format('Y-m-d');
        $shopid = $request->input('shopid');

        $confirmed = Overtimepreview::where([['date', '=', $date], ['shop_id', '=', $shopid]])->first();
        if($confirmed == null){
            $preview->date = $date;
            $preview->shop_id = $shopid;
            $preview->user_id = auth()->user()->id;
            $preview->save();
        }

        Toastr::success('Overtime confirmed successfully','Confirmed');
        return back();
    }

    public function previewstore(Request $request){
        $shop_id = $request->input('shop_id');
        $date = $request->input('date');
        $date = Carbon::createFromFormat('d F Y', $date)->format('Y-m-d');
        $empid =  $request->input('staff_id');
        $overtime =  $request->input('overtime');
        $authhrs =  $request->input('authhrs');
        $indovertime =  $request->input('indovertime');
        $workdescription =  $request->input('workdescription');
        $txtremarks = $request->input('remarks');
        $shoptoid =  $request->input('shoptoid');
        $loaned =  $request->input('loaned');


        try{
            $markedid = Attendance::where([['shop_id', $shop_id],['date','=',$date]])->get('id');

            DB::beginTransaction();
            if(count($markedid) > 0){
                for($i = 0; $i < count($empid); $i++){
                     $ot = Attendance::find($markedid[$i]->id);
                    //$ot->emp_id = $empid[$i];
                    $overtim = ($overtime[$i] == null)? 0 : $overtime[$i];
                    $ot->othours = $overtim;
                    $indovertim = ($indovertime[$i] == null)? 0 : $indovertime[$i];
                    $ot->indirect_othours = $indovertim;
                     //$ot->shop_id = $shop_id;
                     $ot->otloaned_hrs = ($loaned[$i] == null)? 0 : $loaned[$i];
                     $ot->otshop_loaned_to = ($shoptoid[$i] == null)? 0 : $shoptoid[$i];
                     $ot->user_id = auth()->user()->id;
                     $ot->date = $date;
                     $ot->auth_othrs = $authhrs[$i];
                     $ot->workdescription = $workdescription[$i];

                    $hours = Attendance::Where('id', '=', $markedid[$i]->id)
                                    ->sum(DB::raw('direct_hrs + indirect_hrs'));
                    $ot->efficiencyhrs = ($hours * 0.97875) + $overtim + $indovertim;

                    $ot->save();
                }
            }

            $remarkid = Overtimeremarks::where([['date', '=', $date], ['shop_id', '=', $shop_id]])->value('id');
            if($remarkid == null){
                $remark = new Overtimeremarks;
                $remark->date = $date;
                $remark->shop_id = $shop_id;
                $remark->remarks = ($txtremarks == null)? "" : $txtremarks;
                $remark->user_id = auth()->user()->id;
                $remark->save();
            }else{
                $remark = Overtimeremarks::find($remarkid);
                $remark->remarks = $txtremarks;
                $remark->save();
            }

            DB::commit();

            Toastr::success('Success, Overtime saved successfully.','Saved!');
            return back();
        }

        catch(\Exception $e){
            DB::rollback();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            Toastr::error('Sorry, An error occured.','Whooops!');
            return $e->getMessage();
        }
    }


    public function checkloaned(Request $request){
         $mdate = $request->input('date');
         $date = Carbon::createFromFormat('Y-m-d', $mdate)->format('d M Y');
        return $shopid = $request->input('shopid');

        $lonees = Attendance::where([['date', '=', $mdate], ['otshop_loaned_to', '=', $shopid]])
                    ->get(['id','staff_id','otloaned_hrs','shop_id']);

        $check = Attendance::where([['loan_confirm', '=', 1],['date', '=', $mdate], ['otshop_loaned_to', '=', $shopid]])
                    ->first();

        $shopname = Shop::where([['id', $shopid],['overtime','=','1']])->value('report_name');
        $data = array(
            'shopname'=>$shopname, 'date'=>$date,'shopid'=>$shopid,
            'lonees'=>$lonees,
            'color'=>($check) ? 'success' : 'danger',
            'text'=>($check) ? 'Approved' : 'Approve',
            'disabled'=>($check) ? 'disabled' : 'enabled',
            'icon'=>($check) ? 'check' : 'window-minimize',
        );
        return view('overtime.checkloaned')->with($data);
    }

    //APPROVE OVERTIME HOURS
    public function approveloaned(Request $request){
        $attendanceids = $request->input('overtimeid');

        try{

            DB::beginTransaction();
            if(count($attendanceids) > 0){
                for($i = 0; $i < count($attendanceids); $i++){
                    $ot = Attendance::find($attendanceids[$i]);
                    $ot->loan_confirm = 1;

                    $ot->save();
                }
            }
            DB::commit();
            Toastr::success('Overtime approved successfully.','Confirmed!');
            return back();
        }
        catch(\Exception $e){
            DB::rollback();
            Toastr::error('Sorry, An error occured.','Whooops!');
            return back();
        }
    }

    /*public static function exporttoexcel(){
        return view(''); //$records = Attendance::get(['date','shop_id','staff_id'])->get()->toArray();
    }*/


    //EXPORT OVETIME REPOT
    public function exporttoexcel(Request $request)
    {
        $selectauth = AuthorisedHrs::get(['id','datefrom','dateto']);
        $selectshops = Shop::where('overtime','=','1')->pluck('report_name','id');
        if($request->input()){ //return  $request->input('mdate');
            return $daterange = $request->input('daterange');
            $datearr = explode('-',$daterange);
            $datefrom = Carbon::parse($datearr[0])->format('Y-m-d');
            $dateto = Carbon::parse($datearr[1])->format('Y-m-d');
            $shopid = $request->input('shop');
        }else{
            $dateto = Carbon::now()->format('Y-m-d');
            $datefrom = Carbon::parse($dateto)->subDays(30)->format('Y-m-d');
            if(shop() == "noshop"){
                $shopid = 1;
            }else{
                $shopid = Auth()->User()->section;
            }
        }

        $weekMap = [0 => 'Sun',1 => 'Mon',2 => 'Tue',3 => 'Wed',4 => 'Thu',5 => 'Fri',6 => 'Sat'];

        $empexist = Employee::where('shop_id','=',$shopid)->first();
        if(empty($empexist)){
            Toastr::error('Sorry, No employee in that shop/section.','Whooops!');
            return back();
        }
        $shopname = Shop::where('id','=',$shopid)->value('report_name');

		if($shopid ==17){
			$emps = Employee::where('shop_id','=',$shopid)->get(['id','staff_no','staff_name','status']);
		}else{
			$emps = Employee::where([['shop_id','=',$shopid],['outsource','no']])->get(['id','staff_no','staff_name','status']);
		}
        $empcount = Employee::where('shop_id','=',$shopid)->count();

        $ttsumhrs = 0;
        $unauth = 1;

        foreach($emps as $emp){
            //Check OT for inactive
            $sumOT = Attendance::whereBetween('date',[$datefrom,$dateto])
                    ->where([['staff_id',$emp->id],['shop_id',$shopid]])->sum(DB::raw('othours + indirect_othours + otloaned_hrs'));
            if($sumOT == 0 && $emp->status == 'Inactive'){
                $removestaff[$emp->id] = 1;
            }else{
                $removestaff[$emp->id] = 0;
            }
                    $sato = 0; $sun = 0;
            $datefrom1 = $datefrom; $dates = []; $wkdys = []; $authhrs = []; $sumhrs= 0;
            while ($datefrom1 <= $dateto) {
                $dates[] = Carbon::createFromFormat('Y-m-d', $datefrom1)->format('jS');
                $hrs = Attendance::where([['date','=',$datefrom1],['staff_id','=',$emp->id]])
                                ->sum(DB::raw('othours + indirect_othours + otloaned_hrs'));
                $othrs[$emp->id][] = ($hrs == null) ? 0 : $hrs;
                $sumhrs += ($hrs == null) ? 0 : $hrs;


                $dayname = Carbon::parse($datefrom1)->format('l');
                $dayOfTheWeek = Carbon::parse($datefrom1)->dayOfWeek;
                $wkdys[] = $weekMap[$dayOfTheWeek];

                $authhrs[] = Attendance::where([['date','=',$datefrom1],['staff_id','=',$emp->id]])->value('auth_othrs');

                //CHECK APPROVAL
                $checkloan = Attendance_status::where([['shop_id','=',$shopid],['date','=',$datefrom1]])->value('status_name');

                if($checkloan === "approved"){
                    $unauth = 1;
                    $authshow[$emp->id][] = 0;
                }else{
					if($checkloan == null){
						$authshow[$emp->id][] = 0;
					}else{
						$unauth = 0;
						$authshow[$emp->id][] = 1;
					}
                }

				$empauth[$emp->id][] = $unauth;

                 //Totals per employee
                 $empauthhrs = Attendance::where([['date','=',$datefrom1],['staff_id','=',$emp->id]])->value('auth_othrs');
                 $holiday = WorkSchedule::where('date','=',$datefrom1)->first();
                 if(!empty($empauthhrs)){
                    $holiday = WorkSchedule::where('date','=',$datefrom1)->first();
                    $dayno = Carbon::parse($datefrom1)->dayOfWeek;
                     if($dayno == 0 || !empty($holiday)){

                         $sun += Attendance::where([['date','=',$datefrom1],['staff_id','=',$emp->id]])
                                     ->sum(DB::raw('othours + indirect_othours + otloaned_hrs'));
                     }else{
                        $sato += Attendance::where([['date','=',$datefrom1],['staff_id','=',$emp->id]])
                                ->sum(DB::raw('othours + indirect_othours + otloaned_hrs'));
                     }
                 }else{

                 }

                $datefrom1 = Carbon::parse($datefrom1)->addDays(1)->format('Y-m-d');
            }

			$empunauth[] = in_array(0,$empauth[$emp->id]);

            $emptthrs[$emp->id] = $sumhrs;
            $ttsumhrs += $sumhrs;

            $saturday[$emp->id] = $sato;
            $sunday[$emp->id] = $sun;
        }

		$otunauth = in_array(true, $empunauth);




        //TOTALS
        $datefrom2 = $datefrom; $tt = 0; $ttauthhrs = 0;
        while ($datefrom2 <= $dateto) {
            $tthrs = Attendance::where([['date','=',$datefrom2],['shop_id','=',$shopid]])
                        ->sum(DB::raw('othours + indirect_othours + otloaned_hrs'));
            $ttauthhrs = Attendance::where([['date','=',$datefrom2],['shop_id','=',$shopid]])->sum('auth_othrs');
            $cumauthhrs[] = $ttauthhrs;
            $ttothrs[] = ($tthrs == null) ? 0 : $tthrs;
             $tt += ($tthrs == null) ? 0 : $tthrs;
             $cumttothrs[] = $tt;

              $holiday1 = WorkSchedule::where('date','=',$datefrom2)->first();
             if($holiday1 != ""){
                $hhh = "SU_H";
             }else{
                $dayno = Carbon::parse($datefrom2)->dayOfWeek;
                if($dayno == 0){
                    $hhh = "SU_H";
                }elseif($dayno == 6){
                    $hhh = "SAT";
                }else{
                    $hhh = "-";
                }

             }

             $holi[] = $hhh;
            $datefrom2 = Carbon::parse($datefrom2)->addDays(1)->format('Y-m-d');
        }

        $range = Carbon::createFromFormat('Y-m-d', $datefrom)->format('jS M Y').' To '.Carbon::createFromFormat('Y-m-d', $dateto)->format('jS M Y');

        //return $removestaff;
        $data = array(
			'otunauth'=>$otunauth,
            'saturday'=>$saturday, 'sunday'=>$sunday, 'holi'=>$holi,
            'shopname'=>$shopname,'selectauth'=>$selectauth, 'shopid'=>$shopid, 'daterange'=>$datefrom.'+'.$dateto,
            'range'=>$range, 'unauth'=>$unauth, 'authshow'=>$authshow,
            'selectshops'=>$selectshops, 'empcount'=>$empcount,
            'dates'=>$dates, 'authhrs'=>$authhrs, 'wkdys'=>$wkdys,
            'emps'=>$emps, 'cumttothrs'=>$cumttothrs, 'cumauthhrs'=>$cumauthhrs,
            'othrs'=>$othrs, 'emptthrs'=>$emptthrs,
            'ttothrs'=>$ttothrs, 'ttsumhrs'=>$ttsumhrs,
            'tlname'=>Employee::where([['shop_id','=',$shopid],['team_leader','=','yes']])->value('staff_name'),
            'removestaff'=>$removestaff,
        );

        return Excel::download(new OvertimeExportView($data), 'overtime.xlsx');
    }
   public function bulkauth(Request $request){
        if(Auth()->User()->section == ""){
            Toastr::error('Whooops!, No section is assigned for the user.');
            return back();
        }

        $shops = Shop::where('overtime',1)->get();
        $shopid = ($request->input()) ? $request->input('shopid') : Auth()->User()->section;
        if($shopid != "ALL"){
            $shopname = Shop::where('id',$shopid)->value('report_name');
            $unapproveds = Attendance_status::where([['status_name','submitted'],['shop_id',$shopid]])->orderby('date')->get();
        }else{
            $shopname = "";
            $unapproveds = [];
        }

        if(count($unapproveds) > 0){
            foreach($unapproveds as $unapp){
                $headcount[$unapp->date] = Attendance::where([['date',$unapp->date],['shop_id',$shopid]])->count();
                $attendancehrs[$unapp->date] = Attendance::where([['date',$unapp->date],['shop_id',$shopid]])->sum(DB::raw('direct_hrs + indirect_hrs'));
                $overtimehrs[$unapp->date] = Attendance::where([['date',$unapp->date],['shop_id',$shopid]])->sum(DB::raw('othours + indirect_othours'));
                $authhrs[$unapp->date] = Attendance::where([['date',$unapp->date],['shop_id',$shopid]])->sum(DB::raw('auth_othrs'));
            }
        }else{
                $headcount[] = 0;
                $attendancehrs[] = 0;
                $overtimehrs[] = 0;
                $authhrs[] = 0;
        }


        $data = array(
            'shops1' => Shop::where('overtime','=','1')->pluck('report_name','id'),
            'shops'=>$shops,
            'shopname'=>$shopname,
            'shopid'=>$shopid,
            'unapproveds'=>$unapproveds,
            'headcount'=>$headcount,
            'attendancehrs'=>$attendancehrs,
            'overtimehrs'=>$overtimehrs,
            'authhrs'=>$authhrs,
        );
        return view('overtime.bulkauth')->with($data);
    }

    public function saveapprovals(Request $request){
        $validator = Validator::make($request->all(), [
            'checkapprovals' => 'required',
            'shopid'=> 'required',
        ]);

        if ($validator->fails()) {
            Toastr::error('Sorry! Check at least one box.');
            return back();
        }

        $shopid = $request->shopid;
        $checkapprovals = $request->checkapprovals;

        try{
            //return count($loandir);
            DB::beginTransaction();

            for($i = 0; $i < count($checkapprovals); $i++){
                $date = $checkapprovals[$i];
                $statusid = Attendance_status::where([['date',$checkapprovals[$i]],['shop_id',$shopid]])->first();
                $statusid->status_name = "approved";
                $statusid->user_id = Auth()->User()->id;
                $statusid->save();
            }

            DB::commit();
            Toastr::success('Bulk approval saved successfully','Saved');
            return back();
        }
        catch(\Exception $e){
            DB::rollBack();
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            Toastr::error('Sorry! Error occured approval failed.','Error');
            return $e->getMessage();
        }

    }
}
