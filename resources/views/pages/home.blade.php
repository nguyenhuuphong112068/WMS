@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection


@section('mainContent')
    <div class="content-wrapper">
        <div class="container-fluid">
            <div style="height: 60px;  position: relative;">
                <img src="{{ asset('img/Stella_Icon_Main.jpg') }}"
                    style="width: 100%;
                                            height: 100vh;">

            </div>
        </div>

    </div>
@endsection
