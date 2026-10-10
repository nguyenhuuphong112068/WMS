@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.database.style')
    @include('pages.database.dataTable')
    @include('pages.database.script')
@endsection
