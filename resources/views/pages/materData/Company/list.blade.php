@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection

@section('mainContent')
    @include('pages.materData.Company.dataTable')
@endsection

@section('model')
    @include('pages.materData.shared.history')
    @include('pages.materData.Company.create')
    @include('pages.materData.Company.update')
@endsection
