@extends('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.materData.Purpose.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.Purpose.create')
    @include('pages.materData.Purpose.update')
@endsection
