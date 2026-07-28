@extends('layout.master')
@section('mainContent')
    @include('pages.DocumentStorage.Routing.dataTable')
    @include('pages.DocumentStorage.Routing.create')
    @include('pages.DocumentStorage.Routing.forward')
    @include('pages.DocumentStorage.Routing.finish')
@endsection
