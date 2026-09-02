@extends('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.materData.Group.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.Group.create')
    @include('pages.materData.Group.update')
@endsection
