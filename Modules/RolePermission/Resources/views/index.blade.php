@extends("adminlte::page")
@section('title', __('Role and Permission Management'))
@section('content_header')
    <h1>{{__("Roles and permissions")}}</h1>
@stop
@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">
                        {{__("All Roles and Permissions")}}
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{route('rolepermission.store')}}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-md-12">
                                <div class="table table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            @foreach($headings as $heading)
                                                <td>{{__($heading)}}</td>
                                            @endforeach
                                        </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($bodies as $module_name => $body)
                                                <tr>
                                                    <td colspan="{{count($headings)}}" class="bg-warning">{{$module_name}} {{__("Module")}}</td>
                                                </tr>
                                                @foreach($body as $permission)
                                                    <tr>
                                                        <td>{{ucwords($permission->name)}}</td>
                                                        @foreach($selected_permissions as $key => $selected_permission)
                                                            <td>
                                                                <input
                                                                       name="permissions[{{$key}}][{{$permission->name}}]"
                                                                       type="checkbox"
                                                                       {{in_array($permission->name, $selected_permission) ? "checked" : null}}>
                                                            </td>
                                                        @endforeach
                                                    </tr>
                                                @endforeach
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <button type="submit" class="btn btn-success btn-block">Save</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
@section('js')

@endsection