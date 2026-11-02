{{--
| Tab "Danh sách ... theo sản phẩm" của Danh Mục Hoá Chất / Chất Chuẩn.
| Biến vào: $plType (App\Support\ProductList::CHEMICAL | STANDARD), $lists (ProductList::rowsOfDepartment -
| mỗi gói kèm ->items). Thêm / sửa ở pages.category.shared.productListForm.
--}}
@include('pages.category.shared.assets')

@php
    $plConfig = \App\Support\ProductList::config($plType);
    $plRoute = 'pages.category.' . $plType . 'ProductList.';
    $plNoun = $plConfig['noun'];
@endphp

<style>
    .pl-item-list {
        display: flex;
        flex-direction: column;
        gap: 4px;
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .pl-item-list li {
        display: flex;
        align-items: baseline;
        gap: 8px;
    }

    .pl-item-list .md-tag {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .pl-item-list .pl-item-name {
        flex: 1 1 auto;
        min-width: 0;
    }

    .pl-item-list .pl-item-amount {
        white-space: nowrap;
        font-weight: 700;
        color: var(--primary-dark);
    }
</style>

<div class="card md-card">
    <div class="card-body">

        <div class="md-toolbar">
            @perm($plConfig['permission'])
                <button type="button" class="btn btn-primary btn-md-create btn-pl-create" data-modal="#plCreateModal">
                    <i class="fas fa-plus mr-1"></i> Thêm sản phẩm - chỉ tiêu
                </button>
            @endperm
        </div>

        <div class="table-responsive">
            <table id="plTable" class="table table-bordered table-hover w-100 md-table">
                <thead>
                    <tr>
                        <th class="text-center" style="width: 55px">STT</th>
                        <th style="width: 220px">Tên Sản Phẩm</th>
                        <th style="width: 180px">Chỉ Tiêu</th>
                        <th style="min-width: 320px">{{ $plConfig['title'] }} Cần Dùng</th>
                        <th style="width: 180px">Ghi Chú</th>
                        <th class="text-center" style="width: 100px">Sử Dụng</th>
                        <th class="text-center" style="width: 110px">Thao Tác</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($lists as $row)
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td class="font-weight-bold">{{ $row->product_name }}</td>
                            <td>{{ $row->purpose_name ?: '—' }}</td>
                            <td data-order="{{ $row->items->count() }}"
                                data-search="{{ $row->items->map(fn($item) => $item->category_code . ' ' . $item->item_name)->implode(' ') }}">
                                <ul class="pl-item-list">
                                    @foreach ($row->items as $item)
                                        <li>
                                            <span class="md-tag">{{ $item->category_code ?: '—' }}</span>
                                            <span class="pl-item-name">
                                                {{ $item->item_name ?: '—' }}
                                                @if ($plType === \App\Support\ProductList::STANDARD && $item->manufacturer_name)
                                                    <span class="md-sub">· {{ $item->manufacturer_name }}</span>
                                                @endif
                                            </span>
                                            @if ($item->amount !== null)
                                                <span class="pl-item-amount">{{ \App\Support\ProductList::amount($item->amount) }} {{ $item->unit_short_name }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>{{ $row->note ?: '—' }}</td>
                            <td class="text-center">
                                @if ($row->status_id == 1)
                                    <span class="badge badge-success">Đang dùng</span>
                                @else
                                    <span class="badge badge-danger">Đã khoá</span>
                                @endif
                            </td>
                            <td>
                                <div class="md-actions">
                                    @perm($plConfig['permission'])
                                        @if ($row->status_id == 1)
                                            <button type="button" class="btn btn-sm btn-warning btn-md-edit btn-pl-edit" title="Sửa"
                                                data-modal="#plUpdateModal"
                                                data-row="{{ json_encode([
                                                    'id' => $row->id,
                                                    'product_name' => $row->product_name,
                                                    'purpose_id' => $row->purpose_id,
                                                    'note' => $row->note,
                                                    'items' => $row->items->map(fn($item) => [
                                                        'category_id' => $item->category_id,
                                                        'amount' => \App\Support\ProductList::amount($item->amount),
                                                    ])->values(),
                                                ]) }}">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        @endif

                                        <form class="form-md-confirm d-inline" data-require-reason="1" action="{{ route($plRoute . 'deActive') }}"
                                            method="POST"
                                            data-title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }} danh sách {{ $plNoun }} theo sản phẩm?"
                                            data-text="{{ $row->product_name }} - {{ $row->purpose_name }}: {{ $row->status_id == 1 ? 'sau khi khoá sẽ không còn dùng để gợi ý ' . $plNoun . '.' : 'sau khi mở khoá sẽ dùng lại để gợi ý ' . $plNoun . '.' }}"
                                            data-danger="{{ $row->status_id == 1 ? '1' : '' }}">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $row->id }}">
                                            <button type="submit"
                                                class="btn btn-sm btn-{{ $row->status_id == 1 ? 'secondary' : 'primary' }}"
                                                title="{{ $row->status_id == 1 ? 'Khoá' : 'Mở khoá' }}">
                                                <i class="fas fa-{{ $row->status_id == 1 ? 'lock' : 'unlock' }}"></i>
                                            </button>
                                        </form>
                                    @endperm
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
