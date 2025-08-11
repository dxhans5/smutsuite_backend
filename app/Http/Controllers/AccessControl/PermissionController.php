<?php

namespace App\Http\Controllers\AccessControl;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function attach(User $user, Permission $permission): JsonResponse
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

    public function detach(User $user, Permission $permission): JsonResponse
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

    public function bulkAssign(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'roles'       => ['sometimes', 'array'],
            'roles.*'     => ['integer', 'exists:roles,id'],
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

    public function bulkRemove(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'roles'       => ['sometimes', 'array'],
            'roles.*'     => ['integer', 'exists:roles,id'],
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
