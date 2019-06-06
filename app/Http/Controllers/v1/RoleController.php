<?php
/**
 * Created by PhpStorm.
 * User: yuistack
 * Date: 2019-05-19
 * Time: 10:24
 */

namespace App\Http\Controllers\v1;


use App\Http\Controllers\Controller;
use App\Http\Repositories\UserRepository;
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
        $user = $request->user();
        if ($user->cant('control_admin'))
        {
            return $this->resErrRole();
        }

        $role = Role::create(['name' => $request->get('name')]);

        return $this->resOK($role);
    }

    /**
     * 创建一个权限
     */
    public function createPermission(Request $request)
    {
        $user = $request->user();
        if ($user->cant('control_admin'))
        {
            return $this->resErrRole();
        }

        $permission = Permission::create(['name' => $request->get('name')]);

        return $this->resOK($permission);
    }

    /**
     * 操作某个角色的权限
     */
    public function togglePermissionToRole(Request $request)
    {
        $user = $request->user();
        if ($user->cant('control_admin'))
        {
            return $this->resErrRole();
        }

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
        $user = $request->user();
        if ($user->cant('control_admin'))
        {
            return $this->resErrRole();
        }

        $userSlug = $request->get('user_id');
        $roleId = $request->get('role_id');
        $isDelete = $request->get('is_delete');

        $user = User
            ::where('slug', $userSlug)
            ->first();

        $role = Role::findById($roleId);

        if (is_null($user) || is_null($role))
        {
            return $this->resErrNotFound();
        }

        $isDelete
            ? $user->removeRole($role)
            : $user->assignRole($role);

        $userRepository = new UserRepository();
        $userRepository->item($userSlug, true);

        return $this->resOK($user);
    }

    /**
     * 展示所有的角色、权限
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

    /**
     * 获取某一条件的所有用户
     */
    public function getUsersByCondition(Request $request)
    {
        $key = $request->get('key');
        $value = $request->get('value');

        $users = $key === 'role'
            ? User::role($value)->get()
            : User::permission($value)->get();

        return $this->resOK($users);
    }
}
