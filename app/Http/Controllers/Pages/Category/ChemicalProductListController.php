<?php

namespace App\Http\Controllers\Pages\Category;

use App\Support\ProductList;

/**
 * DANH MỤC - HOÁ CHẤT, TAB "DANH SÁCH HOÁ CHẤT THEO SẢN PHẨM"
 *
 * Gói Sản phẩm + Chỉ tiêu + các hoá chất của phòng; modal Sử Dụng Hoá Chất chọn gói thì gợi ý
 * một lô FEFO/FIFO cho từng hoá chất. Nghiệp vụ chung ở ProductListController.
 */
class ChemicalProductListController extends ProductListController
{
    protected const TYPE = ProductList::CHEMICAL;
}
