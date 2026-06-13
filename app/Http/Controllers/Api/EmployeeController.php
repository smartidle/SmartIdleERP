<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class EmployeeController extends Controller
{
    /**
     * 员工列表
     */
    public function index(Request $request)
    {
        $query = Employee::with(['department', 'role']);

        if ($request->has('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->input('search')}%")
                    ->orWhere('email', 'like', "%{$request->input('search')}%")
                    ->orWhere('code', 'like', "%{$request->input('search')}%");
            });
        }

        if ($request->has('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $employees = $query->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->success($employees);
    }

    /**
     * 创建员工
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:32|unique:employees,code',
            'name' => 'required|string|max:64',
            'email' => 'required|email|unique:employees,email',
            'password' => 'required|string|min:6',
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string|max:64',
            'role_id' => 'nullable|exists:roles,id',
        ]);

        $employee = Employee::create([
            'code' => $request->input('code'),
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'department_id' => $request->input('department_id'),
            'position' => $request->input('position'),
            'role_id' => $request->input('role_id'),
            'status' => $request->input('status', 1),
        ]);

        return $this->success($employee, 'Employee created', 201);
    }

    /**
     * 更新员工
     */
    public function update(Request $request, Employee $employee)
    {
        $rules = [
            'name' => 'sometimes|string|max:64',
            'department_id' => 'nullable|exists:departments,id',
            'position' => 'nullable|string|max:64',
            'role_id' => 'nullable|exists:roles,id',
            'status' => 'nullable|in:0,1',
        ];

        if ($request->has('password')) {
            $rules['password'] = 'string|min:6';
        }

        $request->validate($rules);

        $data = $request->only(['name', 'department_id', 'position', 'role_id', 'status']);
        
        if ($request->has('password')) {
            $data['password'] = Hash::make($request->input('password'));
        }

        $employee->update($data);

        return $this->success($employee, 'Employee updated');
    }

    /**
     * 删除员工
     */
    public function destroy(Employee $employee)
    {
        if ($employee->id === 1) {
            return $this->error('Cannot delete default admin', 400);
        }

        $employee->delete();
        return $this->success(null, 'Employee deleted');
    }

    /**
     * 修改密码
     */
    public function changePassword(Request $request, Employee $employee)
    {
        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        // 验证旧密码
        if (!Hash::check($request->input('old_password'), $employee->password)) {
            return $this->error('Old password is incorrect', 400);
        }

        $employee->update([
            'password' => Hash::make($request->input('new_password')),
        ]);

        return $this->success(null, 'Password changed successfully');
    }

    /**
     * 部门列表
     */
    public function departments()
    {
        $departments = Department::where('status', 1)->get();
        return $this->success($departments);
    }

    /**
     * 创建部门
     */
    public function createDepartment(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:64',
            'code' => 'nullable|string|max:32',
            'parent_id' => 'nullable|exists:departments,id',
        ]);

        $department = Department::create([
            'name' => $request->input('name'),
            'code' => $request->input('code'),
            'parent_id' => $request->input('parent_id'),
            'status' => 1,
        ]);

        return $this->success($department, 'Department created', 201);
    }

    /**
     * 角色列表
     */
    public function roles()
    {
        $roles = Role::with('permissions')->get();
        return $this->success($roles);
    }

    /**
     * 创建角色
     */
    public function createRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:64',
            'code' => 'required|string|max:32|unique:roles,code',
            'description' => 'nullable|string|max:255',
        ]);

        $role = Role::create([
            'name' => $request->input('name'),
            'code' => $request->input('code'),
            'description' => $request->input('description'),
            'status' => 1,
        ]);

        // 分配权限
        if ($request->has('permission_ids')) {
            $role->permissions()->sync($request->input('permission_ids'));
        }

        return $this->success($role, 'Role created', 201);
    }

    /**
     * 分配角色权限
     */
    public function assignPermissions(Request $request, Role $role)
    {
        $request->validate([
            'permission_ids' => 'required|array',
        ]);

        $role->permissions()->sync($request->input('permission_ids'));

        return $this->success(null, 'Permissions assigned');
    }
}
