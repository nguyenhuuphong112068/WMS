<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PrintTestController extends Controller
{
    /**
     * TRANG KIỂM TRA IN NHÃN QUA ZEBRA BROWSER PRINT
     *
     * Trang chẩn đoán độc lập, không nằm trên leftNAV. Nhân viên IT mở trực tiếp
     * bằng URL /print-test trên máy trạm đã cắm máy in Zebra + cài Zebra Browser
     * Print. Trang tự kết nối tới dịch vụ Browser Print chạy nền tại
     * http://localhost:9100, liệt kê máy in tìm thấy và gửi thử một nhãn ZPL khổ
     * đúng bằng nhãn lô vật tư để xác nhận đường in hoạt động, trước khi ghép
     * chức năng in vào màn hình Nhập Vật Tư / Hoá Chất.
     */
    public function index(Request $request)
    {
        session()->put(['title' => 'KIỂM TRA IN NHÃN ZEBRA']);

        return view('pages.printTest', [
            'label' => config('material.label'),
        ]);
    }
}
