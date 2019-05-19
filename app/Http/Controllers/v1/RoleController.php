<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-05-19
 * Time: 10:24
 */

namespace App\Http\Controllers\v1;


use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * 创建一个角色
     */
    public function createRole(Request $request)
    {
        $role = Role::create(['name' => $request->get('name')]);

        return $this->resOK($role);
    }

    /**
     * 创建一个权限
     */
    public function createPermission(Request $request)
    {
        $permission = Permission::create(['name' => $request->get('name')]);

        return $this->resOK($permission);
    }

    /**
     * 操作某个角色的权限
     */
    public function togglePermissionToRole(Request $request)
    {
        $permissionId = $request->get('permission_id');
        $roleId = $request->get('role_id');
        $isDelete = $request->get('is_delete');

        $role = Role::findById($roleId);
        $permission = Permission::findById($permissionId);

        if (is_null($role) || is_null($permission))
        {
            return $this->resErrNotFound();
        }

        $result = $isDelete
            ? $role->revokePermissionTo($permission)
            : $role->givePermissionTo($permission);

        return $this->resOK($result);
    }

    /**
     * 操作某个用户的角色
     */
    public function toggleRoleToUser(Request $request)
    {
        $userId = $request->get('user_id');
        $roleId = $request->get('role_id');
        $isDelete = $request->get('is_delete');

        $user = User
            ::where('id', $userId)
            ->first();

        $role = Role::findById($roleId);

        if (is_null($user) || is_null($role))
        {
            return $this->resErrNotFound();
        }

        $result = $isDelete
            ? $user->removeRole($role)
            : $user->assignRole($role);

        return $this->resOK($result);
    }

    /**
     * 展示所有的角色、权限、用户
     */
    public function showAllRoles()
    {
        $roles = Role
            ::with('permissions:id,name')
            ->select('id', 'name')
            ->get();

        return $this->resOK([
            'roles' => $roles,
            'permissions' => Permission
                ::select('id', 'name')
                ->get()
        ]);
    }
}
