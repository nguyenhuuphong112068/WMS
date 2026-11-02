<?php

namespace App\Http\Controllers\Pages\Category;

use App\Support\ProductList;

/**
 * DANH MỤC - CHẤT CHUẨN, TAB "DANH SÁCH CHẤT CHUẨN THEO SẢN PHẨM"
 *
 * Gói Sản phẩm + Chỉ tiêu + các chất chuẩn của phòng; modal Đề nghị cấp phát chuẩn nội bộ chọn gói
 * thì thêm sẵn các dòng đề nghị. Nghiệp vụ chung ở ProductListController.
 */
class StandardProductListController extends ProductListController
{
    protected const TYPE = ProductList::STANDARD;
}
