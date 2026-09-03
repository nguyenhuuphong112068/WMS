@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.user.user.dataTable')
@endsection

@section('model')
    @include('pages.user.user.create')
    @include('pages.user.user.update')
    @include('pages.user.user.permission')
@endsection
