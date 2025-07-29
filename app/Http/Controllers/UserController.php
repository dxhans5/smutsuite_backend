<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function me(Request $request): JsonResponse
    {
        $user = User::with('roles.permissions', 'permissions')->find($request->user()->id);
        $user->setRelation('all_permissions', $user->all_permissions);

        return response()->json([
            'success' => true,
            'data' => new UserResource($user),
        ]);
    }

    public function attachRole(Request $request, User $user, Role $role): JsonResponse
    {
        $user->roles()->syncWithoutDetaching([$role->id]);

        return response()->json([
            'success' => true,
            'message' => __('permissionsroles.role_attach_success'),
        ]);
    }

    public function detachRole(User $user, Role $role): JsonResponse
    {
        $user->roles()->detach($role->id);

        return response()->json([
            'success' => true,
            'message' => __('permissionsroles.role_detach_success'),
        ]);
    }

    public function attachPermission(User $user, Permission $permission): JsonResponse
    {
        if ($user->permissions->contains($permission)) {
            return response()->json([
                'success' => false,
                'message' => __('permissionsroles.permission_already_attached'),
            ], 409);
        }

        $user->permissions()->attach($permission);

        return response()->json([
            'success' => true,
            'message' => __('permissionsroles.permission_attach_success'),
        ]);
    }

    public function detachPermission(User $user, Permission $permission): JsonResponse
    {
        if (! $user->permissions->contains($permission)) {
            return response()->json([
                'success' => false,
                'message' => __('permissionsroles.permission_not_attached'),
            ], 404);
        }

        $user->permissions()->detach($permission);

        return response()->json([
            'success' => true,
            'message' => __('permissionsroles.permission_detach_success'),
        ]);
    }

    public function assignRolesAndPermissions(Request $request, User $user): JsonResponse
    {
        if (! $request->user()) {
            abort(401);
        }

        $validated = $request->validate([
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        if (!empty($validated['roles'])) {
            $user->roles()->syncWithoutDetaching($validated['roles']);
        }

        if (!empty($validated['permissions'])) {
            $user->permissions()->syncWithoutDetaching($validated['permissions']);
        }

        return response()->json([
            'message' => __('permissionsroles.bulk_assign_success'),
        ]);
    }

    public function removeRolesAndPermissions(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        if (!empty($validated['roles'])) {
            $user->roles()->detach($validated['roles']);
        }

        if (!empty($validated['permissions'])) {
            $user->permissions()->detach($validated['permissions']);
        }

        return response()->json([
            'message' => __('permissionsroles.bulk_remove_success'),
        ]);
    }


}
