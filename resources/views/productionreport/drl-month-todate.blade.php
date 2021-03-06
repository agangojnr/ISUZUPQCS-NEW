
@extends('layouts.app')
@section('title','DRL Report')

@section('content')


    <!-- ============================================================== -->
    <!-- Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <div class="row page-titles">
        <div class="col-md-5 col-12 align-self-center">
            <h3 class="text-themecolor mb-0">DIRECT RUN LOSS REPORT</h3>
            <ol class="breadcrumb mb-0 p-0 bg-transparent">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">Drl Report</li>
            </ol>
        </div>

        <div class="col-md-7 col-12 align-self-center d-none d-md-block">
            <div class="d-flex mt-2 justify-content-end">
                {{$heading}}
            </div>
        </div>
    </div>
    <!-- ============================================================== -->
    <!-- End Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <!-- ============================================================== -->
    <!-- Container fluid  -->
    <!-- ============================================================== -->
    <div class="container-fluid">





        <div class="row">


            <div class="col-lg-4  mb-4">
                <div class="btn-list">
                    <a type="button" class="btn btn-outline-primary btn-rounded" href=""><i
                        class="fas fa-check"></i> TODAY </a>

                        <a type="button" class="btn btn-outline-secondary btn-rounded" href=""><i
                            class="fas fa-check"></i> MTD </a>




                </div>
            </div>

            <div class="col-lg-4  mb-6">
                <div class="btn-list">
                    <button type="button" class="btn btn-outline-primary btn-rounded"><i
                            class="fas fa-check"></i> Plant</button>
                    <button type="button" class="btn btn-outline-secondary btn-rounded"><i
                            class="fas fa-check"></i> CV</button>
                    <button type="button" class="btn btn-outline-success btn-rounded"><i
                            class="fas fa-check"></i> Lcv</button>

                </div>
            </div>


            <div class="col-lg-4  mb-2">
                <div class="btn-list">
                    <button type="button" class="btn btn-outline-success btn-rounded"><i
                        class="fas fa-check"></i> Export</button>

                </div>
            </div>


        </div>


        <div class="row">

            <div class="col-12">
        {{ Form::open(['route' => 'drrfiltertoday', 'class' => 'form-horizontal', 'role' => 'form', 'method' => 'post', 'id' => 'create-user'])}}
        {!! Form::hidden('section', 'today'); !!}
        <div class="row">
        <div class="col-4">
        <div class="form-group">
            <label for="date">Choose Day</label>
            <input class="form-control from_custom_date" type="text" id="today"
                required="" name="month_date" value="{{decrypt_data($date)}}"  data-toggle="datepicker" autocomplete="off"  >
        </div>
        </div>





    <div class="col-4">
        <button type="submit" class="btn btn-success mt-4">Filter By Date</button>
    </div>
</div>
</div>

</div>
    <!-- Individual column searching (select inputs) -->


    <div class="row">
        <div class="col-12">
            <div class="card">


                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered" >
                            <thead>

                                   <tr>
                                    <th colspan = "{{(count($shops)*2)+1}}" ></th>


                                </tr>


                                <tr>
                                    <th  rowspan = "2" >Models&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; </th>
                                      @foreach ($shops as $item)
                                    <th colspan = "2">{{$item->shop_name}}</th>
                                    @endforeach



                                </tr>

                                <tr>
                                    @foreach ($shops as $item)
                                    <th style="background-color: {{$item->color_code}}" >Units</th>
                                    <th style="background-color: {{$item->color_code}}" >Defects</th>
                                    @endforeach



                                </tr>



                            </thead>

                            <tbody>
                                @if(count($vehicles) > 0)
                                @foreach($vehicles as $vehicle)
                                    <tr >
                                        <td>
                                            {{$vehicle->model->model_name}} LOT {{$vehicle->lot_no}}
                                        </td>
                                        @foreach ($shops as $shop)
                                          <td style="background-color: {{$shop->color_code}}">{{$drl_arr[$vehicle->model_id][$vehicle->lot_no][$shop->id]['units']}}</td>
                                           <td style="background-color: {{$shop->color_code}}">{{$drl_arr[$vehicle->model_id][$vehicle->lot_no][$shop->id]['defects']}}</td>
                                    @endforeach



                                    </tr>
                                @endforeach
                                @endif
                             </tbody>

                             <tfoot>
                                <tr class="table-primary">
                                    <th><strong>TOTAL</strong></th>
                                     @foreach ($shops as $item)
                        <th  >{{   drl_per_shop_today($item->id, decrypt_data($date))['total_offlined_units'] }}</th>
                        <th  >{{   drl_per_shop_today($item->id, decrypt_data($date))['defects'] }}</th>
                        @endforeach
                                </tr>


                                  <tr class="table-warning">
                                    <th><strong>ACTUAL MTD SCORE</strong></th>
                                     @foreach ($shops as $item)
                        <th class=" text-center" colspan="2"  >{{   drl_per_shop_today($item->id, decrypt_data($date))['drl'] }}</th>

                        @endforeach
                                </tr>

                                 <tr class="table-success">
                                    <th ><strong>TARGET</strong></th>
                                     @foreach ($shops as $item)
                        <th class=" text-center" colspan="2" >{{ drl_per_shop_today($item->id, decrypt_data($date))['drl_target_value']  }}</th>

                        @endforeach
                                </tr>

                                <tr >

                                 <th colspan = "{{(count($shops))+1}}"" >PLANT DRL : <strong>{{ drl_today(decrypt_data($date))['drl'] }}</strong></th>

                                 <th colspan = "{{(count($shops))}}"" >PLANT TARGET : <strong>{{ drl_today(decrypt_data($date))['drl_target_value'] }} </strong></th>


                    </tr>

                            </tfoot>





                        </table>

                    </div>
                </div>
            </div>
        </div>
    </div>


 <!-- Signup modal content -->
                                <div id="daily-modal" class="modal fade" tabindex="-1" role="dialog"
                                    aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                             <div class="modal-header">
                                                <h4 class="modal-title" id="fullWidthModalLabel">Select Dily  Date</h4>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-hidden="true">??</button>
                                            </div>

                                            <div class="modal-body">


                                                 {{ Form::open(['route' => 'filtertoday', 'class' => 'form-horizontal', 'role' => 'form', 'method' => 'post', 'id' => 'create-user'])}}

                                                       {!! Form::hidden('select_id', 1); !!}




                                                    <div class="form-group">
                                                        <label for="date">Date</label>
                                                        <input class="form-control from_date" type="text" id="from"
                                                            required="" name="from_date_single" data-toggle="datepicker" autocomplete="off"  >
                                                    </div>











                                                    <div class="form-group text-center">
                                                        <button class="btn btn-primary" type="submit">Filter Record      </button>
                                                    </div>

                                                 {{ Form::close() }}

                                            </div>
                                        </div><!-- /.modal-content -->
                                    </div><!-- /.modal-dialog -->
                                </div><!-- /.modal -->



                                 <!-- Custom content -->
                                <div id="custom" class="modal fade" tabindex="-1" role="dialog"
                                    aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                             <div class="modal-header">
                                                <h4 class="modal-title" id="fullWidthModalLabel">Select Custom Date</h4>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-hidden="true">??</button>
                                            </div>

                                            <div class="modal-body">


                                                 {{ Form::open(['route' => 'filtertoday', 'class' => 'form-horizontal', 'role' => 'form', 'method' => 'post', 'id' => 'create-user'])}}

                                                      {!! Form::hidden('select_id', 2); !!}




                                                    <div class="form-group">
                                                        <label for="date">From</label>
                                                        <input class="form-control from_custom_date" type="text" id="from"
                                                            required="" name="from_custom_date_single" data-toggle="datepicker" autocomplete="off"  >
                                                    </div>

                                                        <div class="form-group">
                                                        <label for="date">To</label>
                                                        <input class="form-control to_custom__date" type="text" id="to"
                                                            required="" name="to_custom_date_single" data-toggle="datepicker" autocomplete="off"  >
                                                    </div>











                                                    <div class="form-group text-center">
                                                        <button class="btn btn-primary" type="submit">Filter Record      </button>
                                                    </div>

                                                 {{ Form::close() }}

                                            </div>
                                        </div><!-- /.modal-content -->
                                    </div><!-- /.modal-dialog -->
                                </div><!-- /.modal -->

@endsection
@section('after-styles')
   {{ Html::style('assets/libs/datepicker/datepicker.min.css') }}
      {{ Html::style('assets/extra-libs/toastr/dist/build/toastr.min.css') }}
    {{ Html::style('assets/libs/sweetalert2/dist/sweetalert2.min.css') }}


@endsection



@section('after-scripts')

{{ Html::script('assets/libs/datepicker/datepicker.min.js') }}

{{ Html::script('assets/extra-libs/toastr/dist/build/toastr.min.js') }}
{{ Html::script('assets/extra-libs/toastr/toastr-init.js') }}
{{ Html::script('assets/libs/sweetalert2/dist/sweetalert2.all.min.js') }}

<script type="text/javascript">




      $('#daily-modal').on('shown.bs.modal', function () {

           $('[data-toggle="datepicker"]').datepicker({
            autoHide: true,
            format: 'dd-mm-yyyy',


        });
      $('.from_date').datepicker('setDate', 'today');

         });

    $('#custom').on('shown.bs.modal', function () {

           $('[data-toggle="datepicker"]').datepicker({
            autoHide: true,
            format: 'dd-mm-yyyy',


        });
      $('.from_custom_date').datepicker('setDate', 'today');
       $('.to_custom__date').datepicker('setDate', 'today');

         });


         $(function(){


var today = new Date();
$("#datepicker").datepicker({
    showDropdowns: true,
    format: "MM yyyy",
    viewMode: "years",
    minViewMode: "months",
    maxDate: today,
    }).on('changeDate', function(e){
$(this).datepicker('hide');
});

$("#today").datepicker({
    showDropdowns: true,
    format: "dd-mm-yyyy",
    viewMode: "days",
    minViewMode: "days",
    maxDate: today,
    }).on('changeDate', function(e){
$(this).datepicker('hide');
})
$("#year_datepicker").datepicker({
    showDropdowns: true,
    format: "yyyy",
    viewMode: "years",
    minViewMode: "years",
    maxDate: today,
    }).on('changeDate', function(e){
$(this).datepicker('hide');
})




});

</script>
    @endsection
