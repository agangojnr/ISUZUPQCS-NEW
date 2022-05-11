<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Summary</title>

    @include('layouts.header.header')
    @yield('after-styles')
</head>
<body>

    <!-- ============================================================== -->
    <!-- Bread crumb and right sidebar toggle -->
    <!-- ============================================================== -->
    <div class="row page-titles" style="background-color:#da251c;">
        <div class="col-md-5 col-12 align-self-center">
            <h5 class="text-white mb-0">GRAPHICAL SUMMARY REPORTS</h5>
            <ol class="breadcrumb mb-0 p-0 bg-transparent">
                <li class="breadcrumb-item"><a href="javascript:void(0)">Home</a></li>
                <li class="breadcrumb-item active text-white" style="text-transform:uppercase;">{{$shopname}} MTD OFFLINE REPORTS</li>
            </ol>

        </div>
        <div class="col-md-7">
            <div class="row float-left w-100">
                <div class="col-lg-7">
                    <span  class="btn waves-effect waves-light btn-lg"
                    style="background-color: #DAF7A6; opacity: 0.9; font-familiy:Times New Roman;">

                    <h6 class="float-right mt-2">{{\Carbon\Carbon::today()->format('j M Y')}}</h6></span>
                </div>
                <div class="col-5">
                    <a href="/home" id="btn-add-contact" class="btn btn-primary float-right"
               ><i class="mdi mdi-arrow-left font-16"></i> Back to Home</a>
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
    @include('summary.responseheader')

    <!-- Individual column searching (select inputs) -->
       <div class="row">
        <div class="col-md-12">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body analytics-info">
                            {{ Form::open(['route' => 'shopresponsesummary', 'method' => 'GET'])}}
                            @csrf
                            <div class="row mb-2">
                                <div class="col-md-2">
                                    <label>Choose Section:</label>
                                    <select name="shopid" id="" class="form-control select2" required>
                                        <option value="">Select Shop</option>
                                        @foreach ($shops as $shop)
                                            <option value="{{$shop->id}}">{{$shop->shop_name}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Choose Month:</label>
                                    <div class="input-group">
                                        <input type="text" required name="month" class="form-control form-control-1 input-sm from bg-white" readonly
                                        value="{{$selectedmonth}}" autocomplete="off" />
                                        <div class="input-group-append">
                                            <span class="input-group-text">
                                                <span class="ti-calendar"></span>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-2 mt-4">
                                    <button class="btn btn-warning ml-3"><i class="mdi mdi-filter"></i> Filter Data</button>
                                </div>
                                <div class="col-md-6">
                                    <h4 class="card-title" style="color: indigo; text-transform:uppercase;"><u><b>MTD {{$shopname}} OFFLINE BARS - {{$selectedmonth}}</b></u></h4>
                                </div>
                            </div>
                            {{ Form::close() }}

                            <div id="basic-bar" style="height:400px;"></div>
                        </div>
                    </div>


                </div> <!-- end card-body-->
            </div> <!-- end card-->
        </div> <!-- end col -->
    </div>


    @section('after-scripts')
    {{ Html::script('assets/libs/jquery/dist/jquery.min.js') }}
    {{ Html::script('js/jquery-1.11.0.min.js') }}
    {{ Html::style('assets/libs/sweetalert2/dist/sweetalert2.min.css') }}
    {{ Html::style('assets/extra-libs/toastr/dist/build/toastr.min.css') }}
    {{ Html::style('assets/libs/select2/dist/css/select2.min.css') }}
    {{ Html::style('assets/extra-libs/prism/prism.css') }}
    {{ Html::style('assets/libs/bootstrap/dist/css/bootstrap.min.css') }}
    {{ Html::style('assets/libs/bootstrap-datepicker/dist/css/bootstrap-datepicker.min.css') }}

    {{ Html::script('dist/js/app.min.js') }}

    {{ Html::script('assets/libs/echarts/dist/echarts-en.min.js') }}

    {{ Html::script('assets/extra-libs/prism/prism.js') }}
    {{ Html::script('assets/libs/popper.js/dist/umd/popper.min.js') }}
    {{ Html::script('assets/libs/bootstrap/dist/js/bootstrap.min.js') }}
    {{ Html::script('assets/libs/select2/dist/js/select2.full.min.js') }}
    {{ Html::script('assets/libs/select2/dist/js/select2.min.js') }}
    {{ Html::script('assets/libs/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}

    <script type="text/javascript">
        $(".select2").select2();

        $('.from').datepicker({
            autoclose: true,
            minViewMode: 1,
            format: "MM yyyy",
        });
    </script>


<script>
    $(function() {
    "use strict";
    // ------------------------------
    // Basic bar chart
    // ------------------------------
    // based on prepared DOM, initialize echarts instance
        var myChart = echarts.init(document.getElementById('basic-bar'));
        var daysoffline = <?php echo $daysoffline; ?>;
        var offtargets = <?php echo $offtargets; ?>;
        var dates = <?php echo $dates; ?>;

        // specify chart configuration item and data
        var option = {
                // Setup grid
                grid: {
                    left: '1%',
                    right: '2%',
                    bottom: '3%',
                    containLabel: true
                },

                // Add Tooltip
                tooltip : {
                    trigger: 'axis'
                },

                legend: {
                    data:['Offline','Target']
                },
                toolbox: {
                    show : true,
                    feature : {

                        magicType : {show: true, type: ['line', 'bar']},
                        restore : {show: true},
                        saveAsImage : {show: true}
                    }
                },
                color: ["#E74C3C ", "#DAF7A6"],
                calculable : true,
                xAxis : [
                    {
                        type : 'category',
                        data : dates,//['Jan','Feb','Mar','Apr','May','Jun','July','Aug','Sept','Oct','Nov','Dec']
                    }
                ],
                yAxis : [
                    {
                        type : 'value'
                    }
                ],
                series : [
                    {
                        name:'Offline',
                        type:'bar',
                        data:daysoffline,//[2.0, 4.9, 7.0, 23.2, 25.6, 76.7, 135.6, 162.2, 32.6, 20.0, 6.4, 3.3],

                    },
                    {
                        name:'Target',
                        type:'bar',
                        data:offtargets,//[2.6, 5.9, 9.0, 26.4, 28.7, 70.7, 175.6, 182.2, 48.7, 18.8, 6.0, 2.3],

                    }
                ]
            };
        // use configuration item and data specified to show chart
        myChart.setOption(option);

       //------------------------------------------------------
       // Resize chart on menu width change and window resize
       //------------------------------------------------------
        $(function () {

                // Resize chart on menu width change and window resize
                $(window).on('resize', resize);
                $(".sidebartoggler").on('click', resize);

                // Resize function
                function resize() {
                    setTimeout(function() {

                        // Resize chart
                        myChart.resize();
                        stackedChart.resize();
                        stackedbarcolumnChart.resize();
                        barbasicChart.resize();
                    }, 200);
                }
            });
});

</script>
