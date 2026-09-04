<?php

namespace App\Http\Controllers\Pages\User;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Pages\AuditTrail\AuditTrialController;
use App\Support\Signer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
         public function index(){

                // Ô chọn Phòng Ban / Tổ giới hạn trong công ty đang làm việc (suy từ phòng ban đang chọn)
                $companyId = \App\Support\CompanyContext::currentId();

                $deparments = DB::table('deparments')
                    ->leftJoin('companies', 'deparments.company_id', '=', 'companies.id')
                    ->where('deparments.isActive', true)
                    ->when($companyId, fn ($q) => $q->where('deparments.company_id', $companyId))
                    ->select('deparments.*', 'companies.name as company_name', 'companies.short_name as company_short')
                    ->get();
                $roles = DB::table('roles')->get();
                $groups = DB::table('groups')
                    ->leftJoin('deparments', 'groups.department_id', '=', 'deparments.id')
                    ->where('groups.status_id', 1)
                    ->when($companyId, fn ($q) => $q->where('deparments.company_id', $companyId))
                    ->orderBy('groups.name', 'asc')
                    ->get(['groups.id', 'groups.name', 'groups.department_id']);

                $datas = DB::table('user_management')
                    ->leftJoin('companies', 'user_management.company_id', '=', 'companies.id')
                    ->leftJoin('deparments', 'user_management.deparment_id', '=', 'deparments.id')
                    ->leftJoin('roles', 'user_management.role_id', '=', 'roles.id')
                    ->where('user_management.isActive', 1)
                    ->orderBy('user_management.created_at','desc')
                    ->select(
                        'user_management.*',
                        'companies.name as company_name',
                        'companies.short_name as company_short',
                        'deparments.shortName as deparment',
                        'deparments.name as deparment_name',
                        'roles.name as primary_role_name'
                    )
                    ->get()
                    ->map(function($user) {
                        // Lấy danh sách roles cho mỗi user
                        $roles = DB::table('roles')
                            ->join('user_role', 'roles.id', '=', 'user_role.role_id')
                            ->where('user_id', $user->id)
                            ->get();

                        $user->role_ids = $roles->pluck('role_id')->toArray();
                        $user->role_names = $roles->pluck('name')->join(', ');

                        // Tổ của user (một user có thể ở nhiều tổ)
                        $user->group_ids = DB::table('user_group')
                            ->where('user_id', $user->id)
                            ->pluck('group_id')
                            ->toArray();

                        return $user;
                    });
               
                session()->put(['title'=> 'DANH SÁCH NGƯỜI DÙNG']);
           
                return view('pages.user.user.list',[
                        'datas' => $datas,
                        'deparments' => $deparments,
                        'roles' => $roles,
                        'groups' => $groups]);
        }
    

        public function store (Request $request) {
                $validator = Validator::make($request->all(), [
                'userName' => 'required|string|max:10|min:5|unique:user_management,userName',
                'passWord' => [
                        'required','string','min:6','max:255',
                        'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',],
                'fullName' => 'required|string|max:255|min:5',
                'userGroup' => 'required|array', // Chấp nhận mảng
                'deparment' => 'required',
                'group_id' => 'nullable|array',
                'group_id.*' => 'integer|exists:groups,id',
                'mail' => 'required',

                ], [
                'userName.required' => 'Vui lòng nhập tên đăng nhập.',
                'userName.unique' => 'Tên đăng nhập đã tồn tại.',
                'userName.min' => 'Tên đăng nhập phải có ít nhất :min ký tự.',
                'userName.max' => 'Tên đăng nhập không vượt quá :max ký tự.',

                'passWord.required' => 'Vui lòng nhập mật khẩu.',
                'passWord.min' => 'Mật khẩu phải có ít nhất :min ký tự.',
                'passWord.regex' => 'Mật khẩu phải chứa ít nhất 1 chữ hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt.',
                
                'fullName.required' => 'Vui lòng nhập tên đăng nhập.',
                'fullName.min' => 'Tên người dùng phải có ít nhất :min ký tự.',
                'fullName.max' => 'Tên người dùng không vượt quá :max ký tự.',
                
                'userGroup.required' => 'Vui lòng chọn ít nhất một phân quyền',

                'deparment.required' => 'Vui chọn Phòng Ban',

                'mail.required' => 'Nếu Không Có Mail Vui Lòng Nhập NA',

                ]);

                // Tổ phải thuộc đúng phòng ban đã chọn
                $validator->after(function ($v) use ($request) {
                        $this->assertGroupsMatchDepartment($v, $request);
                });

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }

                $userGroups = $request->userGroup; // Mảng role IDs
                $primaryRoleId = $userGroups[0] ?? null; // Role đầu tiên làm role chính

                // Phòng ban + công ty suy từ shortName gửi lên
                $department = DB::table('deparments')
                        ->where('shortName', $request->deparment)
                        ->first(['id', 'company_id']);
                $departmentId = $department->id ?? null;
                $companyId = $department->company_id ?? null;

                // Tổ chính = tổ đầu tiên được chọn (đa tổ vẫn lưu ở user_group)
                $primaryGroupId = collect($request->group_id ?? [])
                        ->filter(fn ($id) => is_numeric($id))
                        ->map(fn ($id) => (int) $id)
                        ->first();

                $pwHash = Hash::make($request->passWord);

                $user_id = DB::table('user_management')->insertGetId([
                        'userName' => $request->userName,
                        'passWord' => $pwHash,
                        'fullName' => $request->fullName,
                        'role_id' => $primaryRoleId,
                        'deparment_id' => $departmentId,
                        'group_id' => $primaryGroupId,
                        'company_id' => $companyId,
                        'mail' => $request->mail,
                        'changePWdate' => today()->addDays(90),
                        'prepareBy' => \App\Support\Signer::actor(),
                        'created_at' => now(),
                ]);

                // §11.300(b) - lưu hash để chặn dùng lại mật khẩu cũ
                DB::table('password_histories')->insert([
                        'user_id' => $user_id,
                        'password_hash' => $pwHash,
                        'created_by' => Signer::actor(),
                        'created_at' => now(),
                ]);

                $rolesToInsert = [];
                foreach ($userGroups as $role_id) {
                    $rolesToInsert[] = [
                        'user_id' => $user_id,
                        'role_id' => $role_id
                    ];
                }
                DB::table('user_role')->insert($rolesToInsert);

                $this->syncUserGroups($user_id, $request->group_id);

                AuditTrialController::log('Thêm mới', 'user_management', $user_id, 'NA', 'Tạo tài khoản: '.$request->userName.' ('.$request->fullName.')');

                return redirect()->back()->with('success', 'Đã thêm thành công!');
        }

        public function update(Request $request){
               
                $rules = [
                    'fullName' => 'required|string|max:255|min:5',
                    'userGroup' => 'required|array',
                    'deparment' => 'required',
                    'group_id' => 'nullable|array',
                    'group_id.*' => 'integer|exists:groups,id',
                    'mail' => 'required',
                ];

                $messages = [
                    'fullName.required' => 'Vui lòng nhập tên người dùng.',
                    'userGroup.required' => 'Vui lòng chọn ít nhất một phân quyền',
                    'passWord.min' => 'Mật khẩu mới phải có ít nhất 6 ký tự',
                    'passWord.regex' => 'Mật khẩu phải chứa ít nhất 1 chữ hoa, 1 chữ thường, 1 số và 1 ký tự đặc biệt.',
                ];

                // Nếu có nhập mật khẩu mới thì mới validate mật khẩu
                if ($request->filled('passWord')) {
                    $rules['passWord'] = [
                        'required', 'string', 'min:6', 'max:255',
                        'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
                    ];
                }

                $validator = Validator::make($request->all(), $rules, $messages);

                // Tổ phải thuộc đúng phòng ban đã chọn
                $validator->after(function ($v) use ($request) {
                    $this->assertGroupsMatchDepartment($v, $request);
                });

                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                }

                $userGroups = $request->userGroup;
                $primaryRoleId = $userGroups[0] ?? null;

                // Phòng ban + công ty suy từ shortName gửi lên
                $department = DB::table('deparments')
                    ->where('shortName', $request->deparment)
                    ->first(['id', 'company_id']);
                $departmentId = $department->id ?? null;
                $companyId = $department->company_id ?? null;

                $updateData = [
                    'fullName' => $request->fullName,
                    'role_id' => $primaryRoleId,
                    'deparment_id' => $departmentId,
                    'company_id' => $companyId,
                    'mail' => $request->mail,
                    'prepareBy' => \App\Support\Signer::actor(),
                    'updated_at' => now(),
                ];

                // Chỉ cập nhật mật khẩu nếu có nhập mới
                $pwChanged = false;
                if ($request->filled('passWord')) {
                    $pwHash = Hash::make($request->passWord);
                    $updateData['passWord'] = $pwHash;
                    // §11.300(b) - đặt lại hạn dùng mật khẩu + mở khoá tài khoản
                    $updateData['changePWdate'] = today()->addDays(90);
                    $updateData['failed_login_attempts'] = 0;
                    $updateData['locked_until'] = null;
                    $pwChanged = true;
                }

                DB::table('user_management')->where('id', $request->id)->update($updateData);

                if ($pwChanged) {
                    DB::table('password_histories')->insert([
                        'user_id' => $request->id,
                        'password_hash' => $pwHash,
                        'created_by' => Signer::actor(),
                        'created_at' => now(),
                    ]);
                }

                // Sync roles
                DB::table('user_role')->where('user_id', $request->id)->delete();
                $rolesToInsert = [];
                foreach ($userGroups as $role_id) {
                    $rolesToInsert[] = [
                        'user_id' => $request->id,
                        'role_id' => $role_id
                    ];
                }
                DB::table('user_role')->insert($rolesToInsert);

                // Sync tổ (một user có thể ở nhiều tổ)
                $this->syncUserGroups($request->id, $request->group_id);

                AuditTrialController::log(
                    'Cập nhật', 'user_management', $request->id, 'NA',
                    'Sửa tài khoản: '.$request->fullName.($pwChanged ? ' (có đặt lại mật khẩu)' : '')
                );

                return redirect()->back()->with('success', 'Đã cập nhật thành công!');
        }

        /**
         * Ghi lại danh sách tổ của user vào bảng trung gian user_group.
         */
        private function syncUserGroups($userId, $groupIds): void
        {
                DB::table('user_group')->where('user_id', $userId)->delete();

                $groupIds = collect($groupIds ?? [])
                        ->filter(fn($id) => is_numeric($id))
                        ->map(fn($id) => (int) $id)
                        ->unique()
                        ->values();

                // Tổ chính trên user_management.group_id = tổ đầu tiên (hoặc null nếu bỏ hết)
                DB::table('user_management')->where('id', $userId)->update([
                        'group_id' => $groupIds->first(),
                ]);

                if ($groupIds->isEmpty()) {
                        return;
                }

                DB::table('user_group')->insert(
                        $groupIds->map(fn($id) => [
                                'user_id' => $userId,
                                'group_id' => $id,
                                'created_at' => now(),
                                'updated_at' => now(),
                        ])->all()
                );
        }

        /**
         * Bắt buộc mọi tổ được chọn phải thuộc phòng ban đã chọn.
         * Không chọn phòng ban thì không được chọn tổ.
         */
        private function assertGroupsMatchDepartment($validator, Request $request): void
        {
                $groupIds = collect($request->group_id ?? [])->filter(fn($id) => is_numeric($id));

                if ($groupIds->isEmpty()) {
                        return;
                }

                $departmentId = DB::table('deparments')
                        ->where('shortName', $request->deparment)
                        ->value('id');

                if (!$departmentId) {
                        $validator->errors()->add('group_id', 'Vui lòng chọn phòng ban trước khi chọn tổ.');
                        return;
                }

                $valid = DB::table('groups')
                        ->where('department_id', $departmentId)
                        ->whereIn('id', $groupIds->all())
                        ->count();

                if ($valid !== $groupIds->unique()->count()) {
                        $validator->errors()->add('group_id', 'Tổ được chọn không thuộc phòng ban đã chọn.');
                }
        }

        public function deActive(string|int $id){

               $target = DB::table('user_management')->where('id', $id)->first();

               DB::table('user_management')->where('id', $id)->update([
                        'isActive' => 0,
                        'prepareBy' => \App\Support\Signer::actor(),
                        'updated_at' => now(),
                ]);

               AuditTrialController::log(
                    'Vô hiệu hoá', 'user_management', $id, 'isActive: 1',
                    'Vô hiệu hoá tài khoản: '.($target->userName ?? $id)
               );

                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }
}
