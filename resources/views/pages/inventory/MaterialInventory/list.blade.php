@extends ('layout.master')

@php
    $invNum = fn($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ','), '0'), '.');
    $invDate = fn($value) => $value ? \Carbon\Carbon::parse($value)->format('d/m/Y') : '—';

    $invToday = \Carbon\Carbon::today();
    $invPeriodLabel = \Carbon\Carbon::parse($period['from'])->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($period['to'])->format('d/m/Y');

    // [import_id => [{balancing_amount, balancing_by, balancing_at}]] cho modal lịch sử cân đối
    $invBalancingMap = $balancings->map(fn($rows) => $rows->map(fn($r) => [
        'balancing_amount' => (float) $r->balancing_amount,
        'balancing_by' => $r->balancing_by,
        'balancing_at' => \Carbon\Carbon::parse($r->balancing_at)->format('d/m/Y H:i'),
    ])->values());
@endphp

@section('mainContent')
    @include('pages.inventory.MaterialInventory.dataTable')
@endsection

@section('model')
    @include('pages.inventory.MaterialInventory.balancing')
    @include('pages.inventory.MaterialInventory.balancingHistory')
@endsection
