
@extends('layouts.app')
@section('title','DRR Management')

@section('content')


    <!-- ============================================================== -->
    <!-- Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <div class="row page-titles">
        <div class="col-md-5 col-12 align-self-center">
            <h3 class="text-themecolor mb-0">DRR Management</h3>
            <ol class="breadcrumb mb-0 p-0 bg-transparent">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active">Drr Management</li>
            </ol>
        </div>
        <div class="col-md-7 col-12 align-self-center d-none d-md-block">
            <div class="d-flex mt-2 justify-content-end">
                <div class="d-flex mr-3 ml-2">
                    <div class="chart-text mr-2">
                        <h6 class="mb-0"><small>THIS MONTH</small></h6>
                        <h4 class="mt-0 text-info">$58,356</h4>
                    </div>
                    <div class="spark-chart">
                        <div id="monthchart"></div>
                    </div>
                </div>
                <div class="d-flex ml-2">
                    <div class="chart-text mr-2">
                        <h6 class="mb-0"><small>LAST MONTH</small></h6>
                        <h4 class="mt-0 text-primary">$48,356</h4>
                    </div>
                    <div class="spark-chart">
                        <div id="lastmonthchart"></div>
                    </div>
                </div>
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

          <!-- Row -->
          <div class="row">
            <div class="col-lg-3">
                <a href="{{route('drrlist',[''.encrypt_data(''.this_month().'').'',''.encrypt_data('this_month').''])}}">
                <div class="card bg-inverse text-white">
                    <div class="card-body">
                        <div class="d-flex no-block align-items-center">
                           <i class="display-6 cc DASH text-white" title="DASH"></i>
                            <div class="ml-3 mt-2">
                                <h4 class="font-weight-medium mb-0 text-white">Month To Date</h4>
                             
                            </div>
                        </div>
                    </div>
                </div>
                </a>
            </div>
            <div class="col-lg-3">

                <a href="{{route('drrlist',[''.encrypt_data(''.this_day().'').'',''.encrypt_data('today').''])}}">
                <div class="card bg-cyan text-white">
                    <div class="card-body">
                        <div class="d-flex no-block align-items-center">
                            <i class="display-6 cc DASH-alt text-white" title="LTC"></i>
                            <div class="ml-3 mt-2">
                                <h4 class="font-weight-medium mb-0 text-white">Today</h4>
                               
                            </div>
                        </div>
                    </div>
                </div>
                </a>
            </div>
            <div class="col-lg-3">

                <a href="{{route('drrlist',[''.encrypt_data(''.this_year().'').'',''.encrypt_data('this_year').''])}}">
                <div class="card bg-orange text-white">
                    <div class="card-body">
                        <div class="d-flex no-block align-items-center">
                            <i class="display-6 cc DASH-alt text-white" title="DASH"></i>
                            <div class="ml-3 mt-2">
                                <h4 class="font-weight-medium mb-0 text-white">Year To date</h4>
                                
                            </div>
                        </div>
                    </div>
                </div>
                </a>
            </div>
            <div class="col-lg-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex no-block align-items-center">
                            <a href="JavaScript: void(0);"><i class="display-6 cc DASH-alt text-white" title="DASH"></i></a>
                            <div class="ml-3 mt-2">
                                <h4 class="font-weight-medium mb-0 text-white">Export</h4>
                               
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        


            @php
                
               if($record=='this_month'){
            @endphp

                {{ Form::open(['route' => 'filterdrrdefect', 'class' => 'form-horizontal', 'role' => 'form', 'method' => 'post', 'id' => 'create-user'])}}
                {!! Form::hidden('record', 'this_month'); !!}
                <div class="row">
                <div class="col-4">
                <div class="form-group">
                    <label for="date">Choose Month</label>
                    <input class="form-control from_custom_date" type="text" id="datepicker"
                        required="" name="month_date" value="{{$date}}"  data-toggle="datepicker" autocomplete="off"  >
                </div>
                </div>




               
            <div class="col-4">
                <button type="submit" class="btn btn-success mt-4">Filter By Month</button>
            </div>
        </div>
            {{ Form::close() }}

            @php
    }else if($record=='this_year'){
                @endphp
                {{ Form::open(['route' => 'filterdrrdefect', 'class' => 'form-horizontal', 'role' => 'form', 'method' => 'post', 'id' => 'create-user'])}}
                {!! Form::hidden('record', 'this_year'); !!}
                <div class="row">
                <div class="col-4">
                <div class="form-group">
                    <label for="date">Choose Year</label>
                    <input class="form-control from_custom_date" type="text" id="year_datepicker"
                        required="" name="month_date" value="{{$date}}"  data-toggle="datepicker" autocomplete="off"  >
                </div>
                </div>
               
            <div class="col-4">
                <button type="submit" class="btn btn-success mt-4">Filter By Year</button>
            </div>
        </div>
            {{ Form::close() }}


                @php
    }else if($record=='today'){
                @endphp

                {{ Form::open(['route' => 'filterdrrdefect', 'class' => 'form-horizontal', 'role' => 'form', 'method' => 'post', 'id' => 'create-user'])}}
                {!! Form::hidden('record', 'today'); !!}
                <div class="row">
                <div class="col-4">
                <div class="form-group">
                    <label for="date">Choose Day</label>
                    <input class="form-control from_custom_date" type="text" id="today"
                        required="" name="month_date" value="{{$date}}"  data-toggle="datepicker" autocomplete="off"  >
                </div>
                </div>
               
            <div class="col-4">
                <button type="submit" class="btn btn-success mt-4">Filter By Date</button>
            </div>
        </div>
            {{ Form::close() }}


                @php
    }
                @endphp

    <!-- Individual column searching (select inputs) -->


    <div class="row">
        <div class="col-12">
            <div class="card">


                <div class="card-body">
                   
                  

                    <div class="row">
                        <div class="col-8">
                            <h4 class="card-title">{{$heading}}</h4>
                        </div>
                      
                    </div>
                    
                       <div class="table-responsive">
                                    <table id="mainTable" class="table editable-table table-bordered table-striped m-b-0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Lot&Job </th>
                                                <th>Vin</th>
                                                <th>Query  Name</th>
                                                <th>Defect</th>
                                                <th>Shop Captured</th>
                                                <th>Captured By</th>
                                                <th>Corrected</th>
                                               
                                            
                                                <th>MPA</th>
                                                <th>MPB</th>
                                                <th>MPC</th>
                                   
                                            </tr>
                                        </thead>
                                        <tbody id="defectsummary">

                                            @foreach($defects as $defect)
                                            @php
                                            $drra_display="Yes";
                                            $drrb_display="Yes";
                                            $drrc_display="Yes";
                                            if($defect->mpa_drr==0){
                                                $drra_display="No";

                                            }
                                           
                                            if($defect->mpb_drr==0){
                                                $drrb_display="No";

                                            }
                                            if($defect->mpc_drr==0){
                                                $drrc_display="No";

                                            }
											if($defect->done_by){
												$done_byname=$defect->getqueryanswer->doneby->name;
											}else{
												$done_byname="";
												
											}
                                             
                                            @endphp
                                           
                                            <tr>
                                                    <td>{{dateFormat($defect->created_at)}}</td>
                                                    <td>{{$defect->getqueryanswer->vehicle->lot_no}} - {{$defect->getqueryanswer->vehicle->job_no}}</td>
                                                  
                                                    <td>{{$defect->getqueryanswer->vehicle->vin_no}}</td>
                                                   <td class="edit-disabled">{{$defect->getqueryanswer->routing->query_name}}</td>
                                                   
                                                   <td>{{$defect->defect_name}} </td>
                                                    <td >{{$defect->getqueryanswer->shop->shop_name}}</td>
                                                    <td>{{$done_byname}}</td>
                                                    <td  >{{$defect->repaired}}</td>
                                                  
                                                    <td data-name="mpa_drr" class="mpa_drr" id="mpa_drr" data-type="select" data-source="[{'value': '1', 'text': 'Yes'}, {'value': '0', 'text': 'No'}]" data-value="{{$defect->mpa_drr}}"  data-pk="{{$defect->id}}" data-url="{{route('updatedrr',[encrypt_data('defect_category')])}}" data-title="Update Corrected" >{{$drra_display}}</td>
                                                    <td data-name="mpb_drr" class="mpb_drr" id="mpb_drr" data-type="select" data-source="[{'value': '1', 'text': 'Yes'}, {'value': '0', 'text': 'No'}]" data-value="{{$defect->mpb_drr}}"  data-pk="{{$defect->id}}" data-url="{{route('updatedrr',[encrypt_data('defect_category')])}}" data-title="Update Corrected" >{{$drrb_display}}</td>
                                                    <td data-name="mpc_drr" class="mpc_drr" id="mpc_drr" data-type="select" data-source="[{'value': '1', 'text': 'Yes'}, {'value': '0', 'text': 'No'}]" data-value="{{$defect->mpc_drr}}"  data-pk="{{$defect->id}}" data-url="{{route('updatedrr',[encrypt_data('defect_category')])}}" data-title="Update Corrected" >{{$drrc_display}}</td>
    
                                                   
                                                   
                                                   
                                                  
                                                   
                                                </tr>

                                           @endforeach
                                        </tbody>
                                       
                                        
                                    </table>
                                </div>
                    
                    
                </div>
            </div>
        </div>
    </div>


 <!-- Custom content -->
                                <div id="custom" class="modal fade" tabindex="-1" role="dialog"
                                    aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                             <div class="modal-header">
                                                <h4 class="modal-title" id="fullWidthModalLabel">Select Custom Date</h4>
                                                <button type="button" class="close" data-dismiss="modal"
                                                    aria-hidden="true">×</button>
                                            </div>

                                            <div class="modal-body">
                                              

                                                 {{ Form::open(['route' => 'filterdrrdefect', 'class' => 'form-horizontal', 'role' => 'form', 'method' => 'post', 'id' => 'create-user'])}}

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
    {{ Html::style('assets/libs/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}
    
@endsection



@section('after-scripts')
{{ Html::script('assets/libs/datepicker/datepicker.min.js') }}

{{ Html::script('assets/extra-libs/toastr/dist/build/toastr.min.js') }}
{{ Html::script('assets/extra-libs/toastr/toastr-init.js') }}
{{ Html::script('assets/libs/sweetalert2/dist/sweetalert2.all.min.js') }}
{{ Html::script('assets/libs/x-editable/dist/js/bootstrap-editable.js') }}





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





// capital_account_table
       var defect = $('#defect').DataTable({
                        processing: true,
                        serverSide: true,
                        ajax: {
                            url: '{{ route("drrlist",[''.encrypt_data($date).'',''.encrypt_data($record).'']) }}',
                            data: function(d){
                               // d.account_status = $('#account_status').val();
                            }
                        },
                        columnDefs:[{
                                "targets": 6,
                                "orderable": false,
                                "searchable": false
                            }],
                        columns: [
                        
                            {data: 'created_at', name: 'created_at'},
                            {data: 'vin_no', name: 'vin_no'},
                            {data: 'lot_no', name: 'lot_no'},
                            {data: 'job_no', name: 'job_no'},
                            {data: 'shop_name', name: 'shop_name'},
                            {data: 'doneby', name: 'doneby'},
                            {data: 'action', name: 'action'}
                        ],
                        
                    });



  



$('table#defect tbody').on('click', 'a.delete-defect', function(e){
                e.preventDefault();
                Swal.fire({
                    type: 'warning',
                  title: "Are You Sure",
                  showCancelButton: true,
                  buttons: true,
                  dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete.value) {
                        var href = $(this).attr('href');
                        $.ajax({
                            method: "DELETE",
                            url: href,
                            dataType: "json",
                              data:{
       
                             '_token': '{{ csrf_token() }}',
                                   },
                            success: function(result){
                                if(result.success == true){
                                    toastr.success(result.msg);
                                    defect.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }else{

                      Swal.fire('Record not deleted', '', 'info')
                    }
                });
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





        $('#defectsummary').editable({
        container: 'body',
        selector: 'td.vs',
        value: 2,    
        source: [
              {value: 1, text: 'Active'},
              {value: 2, text: 'Blocked'},
              {value: 3, text: 'Deleted'}
           ]
    });



        $(document).ready(function () {

            $.ajaxSetup({
                    headers: {
                        'X-CSRF-TOKEN': '{{csrf_token()}}'
                    }
                });

        

        $('#defectsummary').editable({
  container: 'body',
  selector: 'td.mpa_drr',
  validate: function(value){
   if($.trim(value) == '')
   {
    return 'This field is required';
   }
  }, success: function (response, newValue) {
                        console.log('Updated', response)
                    }
 });

 $('#defectsummary').editable({
  container: 'body',
  selector: 'td.mpb_drr',
  validate: function(value){
   if($.trim(value) == '')
   {
    return 'This field is required';
   }
  }, success: function (response, newValue) {
                        console.log('Updated', response)
                    }
 });



 $('#defectsummary').editable({
  container: 'body',
  selector: 'td.mpc_drr',
  validate: function(value){
   if($.trim(value) == '')
   {
    return 'This field is required';
   }
  }, success: function (response, newValue) {
                        console.log('Updated', response)
                    }
 });


})

 // capital_account_table
var defect = $('#mainTable').DataTable({

});       

        

        
</script>
    @endsection