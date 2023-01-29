@extends('custompage::layouts.master')

@section('content')
    <div class="header">
        <h2><?=$data->title;?></h2>
    </div>

    <div class="row">
        <div class="leftcolumn">
            @if($data->image != null)
                <div class="card">
                    <img src="{{getImageUrl($data->image)}}"/>
                </div>
            @endif
            <div class="card">
                {!! $data->description !!}
            </div>
        </div>
        <div class="rightcolumn">
            <div class="card">
                <h2>About us</h2>
                <div class="fakeimg" style="height:100px;">Image</div>
                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Ab alias aliquid asperiores dolorem, illum iusto molestiae mollitia porro quas reiciendis repellat, saepe voluptatem voluptatibus? Corporis ipsa iusto nam praesentium quis!</p>
            </div>
        </div>
    </div>

    <div class="footer">
        <h2>Copyright &copy; <?=date('Y');?>. All right reserved.</h2>
    </div>
@endsection
