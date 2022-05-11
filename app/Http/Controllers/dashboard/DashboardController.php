<?php

namespace App\Http\Controllers\dashboard;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\shop\Shop;
use Illuminate\Http\Request;
use App\Models\unitmovement\Unitmovement;

class DashboardController extends Controller
{

	public function dashboard()
    {
            $status=0;
            $shops = Shop::where('check_point', 1)->with(['unitmovement'=> function ($query) use( $status) {
                $query->where('current_shop','>', $status);
            }]) ->get();

            //Metal finish
            $unitsinmf = Unitmovement::where('current_shop',2)->count();
            $sectionsinmf = Shop::where('id',2)->value('no_of_sections');
            $unitsInmf = ($unitsinmf >= $sectionsinmf) ? $sectionsinmf : $unitsinmf;
            $mfbuffer = (($unitsinmf - $sectionsinmf) > 0) ? $unitsinmf - $sectionsinmf : 0;

            //Trimline
            $unitsintr = Unitmovement::where('current_shop',5)->count();
            $sectionsintr = Shop::where('id',5)->value('no_of_sections');
            $unitsIntr = ($unitsintr >= $sectionsintr) ? $sectionsintr : $unitsintr;
            $trbuffer = (($unitsintr - $sectionsintr) > 0) ? $unitsintr - $sectionsintr : 0;

            //Inline Fseries
            $unitsinInf = Unitmovement::where('current_shop',7)->count();
            $sectionsinInf = Shop::where('id',7)->value('no_of_sections');
            $unitsIninf = ($unitsinInf >= $sectionsinInf) ? $sectionsinInf : $unitsinInf;
            $Infbuffer = (($unitsinInf - $sectionsinInf) > 0) ? $unitsinInf - $sectionsinInf : 0;

            //Inline Nseries
            $unitsinInn = Unitmovement::where('current_shop',9)->count();
            $sectionsinInn = Shop::where('id',9)->value('no_of_sections');
            $unitsIninn = ($unitsinInn >= $sectionsinInn) ? $sectionsinInn : $unitsinInn;
            $Innbuffer = (($unitsinInn - $sectionsinInn) > 0) ? $unitsinInn - $sectionsinInn : 0;

            //Fseries
            $outtrimroute1 = Unitmovement::where([['current_shop',0],['shop_id',5],['route_number',1]])->count();
            $outF = Unitmovement::where([['current_shop',0],['shop_id',8]])->count();
            $sectionsinF = Shop::where('id',8)->value('no_of_sections');
            $unitsinF = $outtrimroute1 - $outF;
            $unitsInF = ($unitsinF >= $sectionsinF) ? $sectionsinF : $unitsinF;
            $Fbuffer = (($unitsinF - $sectionsinF) > 0) ? $unitsinF - $sectionsinF : 0;

            //Nseries
            $outtrimroute3 = Unitmovement::where([['current_shop',0],['shop_id',5],['route_number',3]])->count() + Unitmovement::where([['current_shop',0],['shop_id',5],['route_number',2]])->count();
            $outN = Unitmovement::where([['current_shop',0],['shop_id',10]])->count();
            $sectionsinN = Shop::where('id',10)->value('no_of_sections');
            $unitsinlN = $outtrimroute3 - $outN;
            $unitsInN = ($unitsinlN >= $sectionsinN) ? $sectionsinN : $unitsinlN;
            $Nbuffer = (($unitsInN - $sectionsinN) > 0) ? $unitsInN - $sectionsinN : 0;



            $inshop = [2=>$unitsInmf, 5=>$unitsIntr, 7=>$unitsIninf, 8=>$unitsInF, 9=>$unitsIninn, 10=>$unitsInN];
            $buffer = [2=>$mfbuffer, 5=>$trbuffer, 7=>$Infbuffer, 8=>$Fbuffer, 9=>$Innbuffer, 10=>$Nbuffer];

            $data = array(
                'shops'=>$shops,
                'buffer'=>$buffer,
                'inshop'=>$inshop
            );
            return view('dashboard.index')->with($data);

            }



            public function unitspershop($shopid){
                $shopname = Shop::where('id',$shopid)->value('shop_name');
                $units = Unitmovement::where('current_shop',$shopid)->get();
                $data = array(
                    'shopname'=>$shopname,
                    'units'=>$units,
                    'today'=>Carbon::today()->format('Y-m-d'),
                );
                return view('dashboard.unitsinshop')->with($data);
            }
        }
