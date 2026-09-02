@extends('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.materData.ProductName.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.ProductName.create')
    @include('pages.materData.ProductName.update')
@endsection
