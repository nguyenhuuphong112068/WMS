<?php

namespace App\Http\Controllers\Pages\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
         public function index(){

                $deparments = DB::table('deparments')->where('active', true)->get();
                $roles = DB::table('roles')->get();
               
                $datas = DB::table('user_management')
                    ->where ('isActive',1)
                    ->orderBy('created_at','desc')
                    ->get()
                    ->map(function($user) {
                        // Lấy danh sách roles cho mỗi user
                        $roles = DB::table('roles')
                            ->join('user_role', 'roles.id', '=', 'user_role.role_id')
                            ->where('user_id', $user->id)
                            ->get();
                        
                        $user->role_ids = $roles->pluck('role_id')->toArray();
                        $user->role_names = $roles->pluck('name')->join(', ');
                        return $user;
                    });
               
                session()->put(['title'=> 'DANH SÁCH NGƯỜI DÙNG']);
           
                return view('pages.User.user.list',[
                        'datas' => $datas,
                        'deparments' => $deparments,
                        'roles' => $roles]);
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
               

                if ($validator->fails()) {
                        return redirect()->back()->withErrors($validator, 'createErrors')->withInput();
                }

                $userGroups = $request->userGroup; // Mảng role IDs
                $primaryRoleName = DB::table('roles')->where('id', $userGroups[0])->value('name');

                $user_id = DB::table('user_management')->insertGetId([
                        'userName' => $request->userName,
                        'passWord' => Hash::make($request->passWord),
                        'fullName' => $request->fullName,
                        'userGroup' => $primaryRoleName, // Lưu role đầu tiên làm primary
                        'deparment' => $request->deparment,
                        'mail' => $request->mail,
                        'changePWdate' => today()->addDays(90),
                        'prepareBy' => session('user')['fullName'] ?? 'Admin',
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

                return redirect()->back()->with('success', 'Đã thêm thành công!');    
        }

        public function update(Request $request){
               
                $rules = [
                    'fullName' => 'required|string|max:255|min:5',
                    'userGroup' => 'required|array',
                    'deparment' => 'required',
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
                
                if ($validator->fails()) {
                    return redirect()->back()->withErrors($validator, 'updateErrors')->withInput();
                } 
                
                $userGroups = $request->userGroup;
                $primaryRoleName = DB::table('roles')->where('id', $userGroups[0])->value('name');

                $updateData = [
                    'fullName' => $request->fullName,
                    'userGroup' => $primaryRoleName,
                    'deparment' => $request->deparment,
                    'mail' => $request->mail,
                    'prepareBy' => session('user')['fullName'] ?? 'Admin',
                    'updated_at' => now(),
                ];

                // Chỉ cập nhật mật khẩu nếu có nhập mới
                if ($request->filled('passWord')) {
                    $updateData['passWord'] = Hash::make($request->passWord);
                }

                DB::table('user_management')->where('id', $request->id)->update($updateData);

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

                return redirect()->back()->with('success', 'Đã cập nhật thành công!');   
        }

        public function deActive(string|int $id){
                
               DB::table('user_management')->where('id', $id)->update([
                        'isActive' => 0,
                        'prepareBy' => session('user')['fullName'],
                        'updated_at' => now(), 
                ]);
                return redirect()->back()->with('success', 'Vô Hiệu Hóa thành công!');
        }
}
