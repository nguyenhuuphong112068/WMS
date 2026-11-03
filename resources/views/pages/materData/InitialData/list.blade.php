@extends('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.materData.InitialData.dataTable')
@endsection

@section('model')
    @include('pages.materData.InitialData.upload')
    @include('pages.materData.InitialData.preview')
    @if ($isAdmin)
        @include('pages.materData.InitialData.review')
    @endif
@endsection
