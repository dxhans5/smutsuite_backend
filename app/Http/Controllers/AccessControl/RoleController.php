<?php

namespace App\Http\Controllers\AccessControl;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    // Single role attach
    public function attach(User $user, Role $role): JsonResponse
    {
        $user->roles()->syncWithoutDetaching([$role->id]);

        return response()->json([
            'success' => true,
            'message' => __('permissionsroles.role_attach_success'),
        ]);
    }

    // Single role detach
    public function detach(User $user, Role $role): JsonResponse
    {
        $user->roles()->detach($role->id);

        return response()->json([
            'success' => true,
            'message' => __('permissionsroles.role_detach_success'),
        ]);
    }

    public function assignRolesAndPermissions(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'roles'            => ['nullable','array'],
            'roles.*'          => ['integer','exists:roles,id'],        // <-- key line
            'permissions'      => ['nullable','array'],
            'permissions.*'    => ['integer','exists:permissions,id'],  // <-- key line
        ]);

        $roles = $validated['roles'] ?? [];
        $perms = $validated['permissions'] ?? [];

        if (empty($roles) && empty($perms)) {
            return response()->json([
                'message' => __('permissionsroles.bulk_assign_success'),
                'applied' => ['roles' => [], 'permissions' => []],
            ]);
        }

        // If Spatie is present, you can map by names; here we sync by IDs safely.
        if (!empty($roles)) {
            $user->roles()->sync($roles, false); // additive; use sync() if you want replace
        }
        if (!empty($perms) && method_exists($user, 'permissions')) {
            $user->permissions()->syncWithoutDetaching($perms);
        }

        return response()->json([
            'message' => __('permissionsroles.bulk_assign_success'),
            'applied' => ['roles' => $roles, 'permissions' => $perms],
        ]);
    }

    public function removeRolesAndPermissions(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'roles'            => ['nullable','array'],
            'roles.*'          => ['integer','exists:roles,id'],        // keep symmetry
            'permissions'      => ['nullable','array'],
            'permissions.*'    => ['integer','exists:permissions,id'],
        ]);

        $roles = $validated['roles'] ?? [];
        $perms = $validated['permissions'] ?? [];

        if (empty($roles) && empty($perms)) {
            return response()->json([
                'message' => __('permissionsroles.bulk_remove_success'),
                'removed' => ['roles' => [], 'permissions' => []],
            ]);
        }

        if (!empty($roles)) {
            $user->roles()->detach($roles);
        }
        if (!empty($perms) && method_exists($user, 'permissions')) {
            $user->permissions()->detach($perms);
        }

        return response()->json([
            'message' => __('permissionsroles.bulk_remove_success'),
            'removed' => ['roles' => $roles, 'permissions' => $perms],
        ]);
    }

}
