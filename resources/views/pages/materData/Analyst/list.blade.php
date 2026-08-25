@extends('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.materData.Analyst.dataTable')
@endsection

@section('model')
    @include('pages.materData.Analyst.create')
    @include('pages.materData.Analyst.update')
@endsection
