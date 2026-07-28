@extends('layout.master')

{{-- Sổ có nhiều cột, cần bề ngang: mặc định thu nhỏ menu trái (bấm ☰ để mở lại) --}}
@section('bodyClass', 'sidebar-collapse')

@section('mainContent')
    @include('pages.DocumentStorage.Reissue.dataTable')
    @include('pages.DocumentStorage.Reissue.create')
    @include('pages.DocumentStorage.Reissue.update')
    @include('pages.DocumentStorage.Reissue.pmSign')
    @include('pages.DocumentStorage.Reissue.qaReview')
    @include('pages.DocumentStorage.Reissue.issue')
@endsection
