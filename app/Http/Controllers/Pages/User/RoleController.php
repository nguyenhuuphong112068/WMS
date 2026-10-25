<?php

namespace App\Http\Controllers\Pages\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\PermissionMatrix;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{
    public function index()
    {
        session()->put(['title' => 'DANH SÁCH NHÓM QUYỀN']);

        // roles, tree (model -> nhóm chức năng -> quyền), assigned ("roleId-permissionId")
        return view('pages.user.role.list', PermissionMatrix::build());
    }

    /**
     * Bật / tắt một quyền cho một nhóm quyền (checkbox trên ma trận).
     */
    public function store_or_update(Request $request)
    {
        try {
            $roleId = $request->input('role_id');
            $permissionId = $request->input('permission_id');
            $checked = filter_var($request->input('checked'), FILTER_VALIDATE_BOOLEAN);

            if (!$roleId || !$permissionId) {
                return response()->json(['error' => 'Thiếu dữ liệu nhóm quyền hoặc quyền'], 400);
            }

            if ($checked) {
                DB::table('role_permission')->updateOrInsert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ], []);
            } else {
                // Nhóm quyền Admin (id = 1) luôn giữ toàn quyền, không cho gỡ
                if ($roleId == 1) {
                    return response()->json(['error' => 'Không thể gỡ quyền của nhóm Admin'], 400);
                }

                DB::table('role_permission')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->delete();
            }

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Thêm mới hoặc đổi tên / diễn giải một nhóm quyền.
     * Có 'id' -> cập nhật, không có -> tạo mới.
     */
    public function saveRole(Request $request)
    {
        $id = $request->input('id');

        if ($id && (int) $id === 1) {
            return redirect()->back()
                ->withErrors(['name' => 'Không thể chỉnh sửa nhóm quyền Admin.'], 'roleErrors')
                ->withInput();
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'required', 'string', 'max:100',
                $id ? 'unique:roles,name,' . (int) $id : 'unique:roles,name',
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Vui lòng nhập tên nhóm quyền.',
            'name.unique' => 'Tên nhóm quyền này đã tồn tại.',
            'name.max' => 'Tên nhóm quyền không vượt quá :max ký tự.',
            'description.max' => 'Diễn giải không vượt quá :max ký tự.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator, 'roleErrors')->withInput();
        }

        if ($id) {
            $old = DB::table('roles')->where('id', $id)->first();

            DB::table('roles')->where('id', $id)->update([
                'name' => $request->name,
                'description' => $request->description,
                'updated_at' => now(),
            ]);

            AuditTrialController::log(
                'Cập nhật', 'roles', $id,
                $old->name ?? '',
                'Sửa nhóm quyền: ' . $request->name
            );

            return redirect()->back()->with('success', 'Đã cập nhật nhóm quyền!');
        }

        $newId = DB::table('roles')->insertGetId([
            'name' => $request->name,
            'description' => $request->description,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        AuditTrialController::log('Thêm mới', 'roles', $newId, 'NA', 'Tạo nhóm quyền: ' . $request->name);

        return redirect()->back()->with('success', 'Đã thêm nhóm quyền!');
    }

    /**
     * Xoá một nhóm quyền chưa được gán cho người dùng nào.
     */
    public function deleteRole($id)
    {
        $id = (int) $id;

        if ($id === 1) {
            return redirect()->back()->withErrors(['name' => 'Không thể xoá nhóm quyền Admin.'], 'roleErrors');
        }

        $role = DB::table('roles')->where('id', $id)->first();

        if (!$role) {
            return redirect()->back()->withErrors(['name' => 'Nhóm quyền không tồn tại.'], 'roleErrors');
        }

        $inUse = DB::table('user_role')->where('role_id', $id)->exists()
            || DB::table('user_management')->where('role_id', $id)->exists();

        if ($inUse) {
            return redirect()->back()->withErrors(
                ['name' => 'Nhóm quyền "' . $role->name . '" đang được gán cho người dùng, không thể xoá.'],
                'roleErrors'
            );
        }

        DB::table('role_permission')->where('role_id', $id)->delete();
        DB::table('roles')->where('id', $id)->delete();

        AuditTrialController::log('Xoá', 'roles', $id, $role->name, 'Xoá nhóm quyền');

        return redirect()->back()->with('success', 'Đã xoá nhóm quyền!');
    }
}
