
@extends ('layout.master')

@section('topNAV')
    @include('layout.topNAV')
@endsection

@section('leftNAV')
    @include('layout.leftNAV')
@endsection
 
@section('mainContent')
  @include('pages.user.role.dataTable')
@endsection

@section('model')
  {{-- @include('pages.user.user.create')
  @include('pages.user.user.update')  --}}
@endsection
