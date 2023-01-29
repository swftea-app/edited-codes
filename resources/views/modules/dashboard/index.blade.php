@extends("adminlte::page")
@section('title', __('Dashboard'))
@section('content_header')
    <h1>{{__("Dashboard")}}</h1>
@stop
@section('content')
    <div class="row">
        @foreach($charts as $chart)
            <div class="col-md-{{$chart->size}}">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">
                            {{$chart->label}}
                        </h5>
                    </div>
                    <div class="card-body">
                        {!! $chart->content->container() !!}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endsection
@section('js')
    @foreach($charts as $chart)
        {!! $chart->content->script() !!}
    @endforeach
@endsection