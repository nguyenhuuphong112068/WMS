@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.materData.Status.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.Status.create')
    @include('pages.materData.Status.update')
@endsection
