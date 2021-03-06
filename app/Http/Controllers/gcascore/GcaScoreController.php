<?php

namespace App\Http\Controllers\gcascore;

use App\Models\gcascore\GcaScore;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Brian2694\Toastr\Facades\Toastr;

use App\Models\vehicle_units\vehicle_units;
use App\Models\productiontarget\Production_target;
use App\Models\gcatarget\GcaTarget;

class GcaScoreController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if($request->input()){
            $daterange = $request->input('daterange');
            $datearr = explode('-',$daterange);
            $datefrom = Carbon::parse($datearr[0])->format('Y-m-d');
            $dateto = Carbon::parse($datearr[1])->format('Y-m-d');
        }else{
            $datefrom = Carbon::now()->startOfMonth()->format('Y-m-d');
            $dateto = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        $weekMap = [0 => 'S',1 => 'M',2 => 'T',3 => 'W',4 => 'T',5 => 'F',6 => 'S'];
        $allschdates = Production_target::whereBetween('date', [$datefrom, $dateto])
                            ->groupby('date')->get(['date']);
        foreach($allschdates as $schdt){
            $prodndays[] = $schdt->date;
        }

        $datefrom1 = $datefrom;
        while($datefrom1 <= $dateto){
            $dates[] = Carbon::parse($datefrom1)->format('jS');
            $dayname[] = $weekMap[Carbon::parse($datefrom1)->dayOfWeek];

            //CV
            $cv_sumdefects = GcaScore::where([['lcv_cv','=','cv'],['date','=',$datefrom1]])->sum(DB::raw('defectcar1 + defectcar2'));
            $cv_samplsz = GcaScore::where([['lcv_cv','=','cv'],['date','=',$datefrom1]])->value('units_sampled');
            $cv_dpvscore[] = ($cv_samplsz == 0) ? 0 : $cv_sumdefects/$cv_samplsz;
            $cv_samplesize[] = $cv_samplsz;
            $cv_wdpvscore[] = GcaScore::where([['lcv_cv','=','cv'],['date','=',$datefrom1]])->value('mtdwdpv');

            //LCV
            $lcv_sumdefects = GcaScore::where([['lcv_cv','=','lcv'],['date','=',$datefrom1]])->sum(DB::raw('defectcar1 + defectcar2'));
            $lcv_samplsz = GcaScore::where([['lcv_cv','=','lcv'],['date','=',$datefrom1]])->value('units_sampled');
            $lcv_dpvscore[] = ($lcv_samplsz == 0) ? 0 : $lcv_sumdefects/$lcv_samplsz;
            $lcv_samplesize[] = $lcv_samplsz;
            $lcv_wdpvscore[] = GcaScore::where([['lcv_cv','=','lcv'],['date','=',$datefrom1]])->value('mtdwdpv');

            $datefrom1 = Carbon::parse($datefrom1)->addDays(1)->format('Y-m-d');
        }

        $today = Carbon::today()->format('Y-m-d');
            //CV
            $cv_MTDsamplesize = GcaScore::whereBetween('date',[$datefrom, $dateto])
                            ->where('lcv_cv','=','cv')->sum('units_sampled');
            $cv_MTDwdpv = GcaScore::where('lcv_cv','cv')->orderBy('date','desc')->value('mtdwdpv');

            $cv_sumdef = GcaScore::whereBetween('date',[$datefrom, $dateto])
                        ->where('lcv_cv','=','cv')->sum(DB::raw('defectcar1 + defectcar2'));
            $cv_MTDdpv = ($cv_MTDsamplesize == 0) ? 0 : $cv_sumdef/$cv_MTDsamplesize;

            //LCV
            $lcv_MTDsamplesize = GcaScore::whereBetween('date',[$datefrom, $dateto])
                            ->where('lcv_cv','=','lcv')->sum('units_sampled');

            $lcv_MTDwdpv = GcaScore::where('lcv_cv','lcv')->orderBy('date','desc')->value('mtdwdpv');

           $lcv_sumdef = GcaScore::whereBetween('date',[$datefrom, $dateto])
                        ->where('lcv_cv','=','lcv')->sum(DB::raw('defectcar1 + defectcar2'));
            $lcv_MTDdpv = ($lcv_MTDsamplesize == 0) ? 0 : $lcv_sumdef/$lcv_MTDsamplesize;


        $data = array(
            'dates'=>$dates,
            'dayname'=>$dayname,

            'cv_dpvscore'=>$cv_dpvscore, 'cv_MTDdpv'=>$cv_MTDdpv,
            'cv_samplesize'=>$cv_samplesize, 'cv_MTDsamplesize'=>$cv_MTDsamplesize,
            'cv_wdpvscore'=>$cv_wdpvscore,'cv_MTDwdpv'=>$cv_MTDwdpv,

            'lcv_dpvscore'=>$lcv_dpvscore, 'lcv_MTDdpv'=>$lcv_MTDdpv,
            'lcv_samplesize'=>$lcv_samplesize, 'lcv_MTDsamplesize'=>$lcv_MTDsamplesize,
            'lcv_wdpvscore'=>$lcv_wdpvscore,'lcv_MTDwdpv'=>$lcv_MTDwdpv,
        );
        return view('gcascore.index')->with($data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response  ll4D  11  ll
     */
    public function create()
    {
        $datefrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $dateto = Carbon::now()->endOfMonth()->format('Y-m-d');

        $allschdates = Production_target::whereBetween('date', [$datefrom, $dateto])
                                ->groupby('date')->get(['date']);
        foreach($allschdates as $schdt){
            $prodndays[] = $schdt->date;
        }

        $datefrom1 = $datefrom;
        while($datefrom1 <= $dateto){
            $dates[] = Carbon::parse($datefrom1)->format('j');
            $cvscore = GcaScore::where([['date', '=',$datefrom1],['lcv_cv','=','cv']])->value('id');
            $lcvscore = GcaScore::where([['date', '=',$datefrom1],['lcv_cv','=','lcv']])->value('id');
            $checkcv[] = ($cvscore > 0) ? 'success' : 'warning';
            $checklcv[] = ($lcvscore > 0) ? 'success' : 'warning';

            $datefrom1 = Carbon::parse($datefrom1)->addDays(1)->format('Y-m-d');
        }
        //for($i = 0; $i < count($prodndays); $i++){}
        //return $score;
        $data = array(
            'dates'=>$dates, 'checkcv'=>$checkcv, 'checklcv'=>$checklcv,
        );
        return view('gcascore.create')->with($data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mdate' => 'required',
            'lcvcv' => 'required',
            'ttdefectsc1' => 'required|numeric',
            'ttdefectsc2' => 'required|numeric',
            'mtdwdpv' => 'required|numeric',
            'samplesize' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            Toastr::error('Sorry! Only numerics are allowed. Fill all fields');
            return back();
        }
        $date = Carbon::createFromFormat('m/d/Y', $request->input('mdate'))->format('Y-m-d');
        $checkschd = Production_target::where('date',$date)->first();
        if(empty($checkschd)){
            Toastr::error('Sorry! No production scheduled for date selected.');
            return back();
        }

        try{
            DB::beginTransaction();
                $date = $date;
                $cvlcv = $request->input('lcvcv');
                $GCAxist = GcaScore::where([['date',$date],['lcv_cv',$cvlcv]])->first();
                if($GCAxist == ""){
                    $gca = new GcaScore;
                    $gca->date = $date;
                    $gca->lcv_cv = $cvlcv;
                    $gca->defectcar1 = $request->input('ttdefectsc1');
                    $gca->defectcar2 = $request->input('ttdefectsc2');
                    $gca->mtdwdpv = $request->input('mtdwdpv');
                    $gca->units_sampled = $request->input('samplesize');

                    $gca->save();
                }else{
                    $GCAxist->defectcar1 = $request->input('ttdefectsc1');
                    $GCAxist->defectcar2 = $request->input('ttdefectsc2');
                    $GCAxist->mtdwdpv = $request->input('mtdwdpv');
                    $GCAxist->units_sampled = $request->input('samplesize');

                    $GCAxist->save();
                }


            DB::commit();

            Toastr::success('GCA Score saved successfully!','Saved');
            return back();

        }
        catch(\Exception $e){
            \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());
            DB::Rollback();

            Toastr::error($e->getMessage());
            return back();
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\GcaScore  $gcaScore
     * @return \Illuminate\Http\Response
     */
    public function gcalist()
    {
        $gcas = GcaScore:: all()->sortDesc();
        $data = array(
            'gcas'=>$gcas,
        );
        return view('gcascore.gcalist')->with($data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\GcaScore  $gcaScore
     * @return \Illuminate\Http\Response
     */
    public function edit(GcaScore $gcaScore, $id)
    {
        $datefrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $dateto = Carbon::now()->endOfMonth()->format('Y-m-d');

        $allschdates = vehicle_units::whereBetween('offline_date', [$datefrom, $dateto])
                        ->groupby('offline_date')->get(['offline_date']);
        foreach($allschdates as $schdt){
            $prodndays[] = $schdt->offline_date;
        }

        $datefrom1 = $datefrom;
        while($datefrom1 <= $dateto){
            $dates[] = Carbon::parse($datefrom1)->format('j');
            $cvscore = GcaScore::where([['date', '=',$datefrom1],['lcv_cv','=','cv']])->value('id');
            $lcvscore = GcaScore::where([['date', '=',$datefrom1],['lcv_cv','=','lcv']])->value('id');
            $checkcv[] = ($cvscore > 0) ? 'success' : 'warning';
            $checklcv[] = ($lcvscore > 0) ? 'success' : 'warning';

            $datefrom1 = Carbon::parse($datefrom1)->addDays(1)->format('Y-m-d');
        }

        $gcas = GcaScore::find($id);
        $data = array(
            'gcas'=>$gcas, 'dates'=>$dates, 'checkcv'=>$checkcv, 'checklcv'=>$checklcv,
        );
        return view('gcascore.edit')->with($data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\GcaScore  $gcaScore
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, GcaScore $gcaScore)
    {
        $validator = Validator::make($request->all(), [
            'mdate' => 'required',
            'lcvcv' => 'required',
            'ttdefectsc1' => 'required|numeric',
            'ttdefectsc2' => 'required|numeric',
            'mtdwdpv' => 'required|numeric',
            'samplesize' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            Toastr::error('Sorry! Only numerics are allowed. Fill all fields');
            return back();
        }
        $date = Carbon::createFromFormat('m/d/Y', $request->input('mdate'))->format('Y-m-d');
        $id = $request->input('gcaid');

        try{
            DB::beginTransaction();

            $gca1 = GcaScore::find($id);
            $gca1->date = $date;
            $gca1->lcv_cv = $request->input('lcvcv');
            $gca1->defectcar1 = $request->input('ttdefectsc1');
            $gca1->defectcar2 = $request->input('ttdefectsc2');
            $gca1->mtdwdpv = $request->input('mtdwdpv');
            $gca1->units_sampled = $request->input('samplesize');

            $gca1->save();
            DB::commit();

            Toastr::success('GCA Score updated successfully!','Saved');
            return redirect('gcalist');

        }
        catch(\Exception $e){
            DB::Rollback();

            Toastr::error('Oops! An error occured, GCA Score not updated.');
            return back();
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\GcaScore  $gcaScore
     * @return \Illuminate\Http\Response
     */
    public function destroy(GcaScore $gcaScore, $id)
    {
        if (request()->ajax()) {
            try {
                $can_be_deleted = true;
                $error_msg = '';

                //Check if any routing has been done
               //do logic here
               $gca = GcaScore::where('id', $id)->first();

                if ($can_be_deleted) {
                    if (!empty($gca)) {
                        DB::beginTransaction();
                        //Delete Query  details
                        GcaScore::where('id', $id)->delete();

                        DB::commit();

                        $output = ['success' => true,
                                'msg' => "GCA score Deleted Successfully"
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

    public function gcatarget(Request $request){
        if($request->input()){
            $validator = Validator::make($request->all(), [
                'cvdpv' => 'required',
                'cvwdpv' => 'required',
                'lcvdpv' => 'required',
                'lcvwdpv' => 'required',
                'month' => 'required'
            ]);

            if ($validator->fails()) {
                Toastr::error('Sorry! Fill all fields');
                return back();
            }
            $month = Carbon::createFromFormat('F Y', $request->input('month'))->format('Y-m-d');

            $start = carbon::parse($month)->startOfMonth()->format('Y-m-d');
            $end = carbon::parse($month)->endOfMonth()->format('Y-m-d');

            try{
                DB::beginTransaction();
                    $gca = GcaTarget::whereBetween('month',[$start,$end])->first();
                    if($gca == ""){
                        $gca = new GcaTarget;
                    }
                        $gca->month = $month;
                        $gca->user_id = Auth()->User()->id;
                        $gca->cvdpv = $request->input('cvdpv');
                        $gca->cvwdpv = $request->input('cvwdpv');
                        $gca->lcvdpv = $request->input('lcvdpv');
                        $gca->lcvwdpv = $request->input('lcvwdpv');

                        $gca->save();
                    DB::commit();

                    Toastr::success('GCA Score target updated successfully!','Saved');
                    return back();

                }catch (\Exception $e) {
                    DB::rollBack();
                    \Log::emergency("File:" . $e->getFile(). "Line:" . $e->getLine(). "Message:" . $e->getMessage());

                    Toastr::error($e->getMessage());
                    return back();
                }
        }


        $startofyr = Carbon::now()->startOfYear()->format('Y-m-d');
        $endofyr = Carbon::now()->endOfYear()->format('Y-m-d');

        $selectedmonth = Carbon::parse()->format('F Y');
        $gcatargts =  GcaTarget::whereBetween('month',[$startofyr,$endofyr])->get();
        $data = array(
            'selectedmonth'=>$selectedmonth,
            'gcatargts'=>$gcatargts,
        );
        return view('gcascore.target')->with($data);
    }



    public function mangcatarget(){
        $targets =  GcaTarget::All();

        $data = array(
            'targets'=>$targets,

        );
        return view('gcascore.mangcatarget')->with($data);
    }

    public function destroygcatag($id){
        if (request()->ajax()) {
            try {
                $can_be_deleted = true;
                $error_msg = '';

                //Check if any routing has been done
               //do logic here
               $tag = GcaTarget::where('id', $id)->first();

                if ($can_be_deleted) {
                    if (!empty($tag)) {
                        DB::beginTransaction();
                        //Delete Query  details
                        GcaTarget::where('id', $id)->delete();
                        $tag->delete();
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

}
