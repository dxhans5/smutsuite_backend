<?php
declare(strict_types=1);

namespace App\Http\Controllers\AccessControl;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Manage direct user ⇄ permission links.
 *
 * Response shape (Vixen Bible):
 * {
 *   "data": {...},                // resource payload
 *   "meta": { "timestamp": "...", // always present via Response::envelope macro
 *             "success": true,
 *             "message": "..." }
 * }
 */
class PermissionController extends Controller
{
    /**
     * Attach a permission to a user.
     *
     * Status:
     * - 200 on success
     * - 409 if already attached
     */
    public function attach(User $user, Permission $permission): JsonResponse
    {
        $already = $user->permissions()
            ->whereKey($permission->id)
            ->exists(); // avoids loading entire collection

        if ($already) {
            return response()->envelope(
                data: [],
                meta: [
                    'success'        => false,
                    'message'        => __('permissionsroles.permission_already_attached'),
                    'user_id'        => $user->id,
                    'permission_id'  => $permission->id,
                ],
                status: 409
            );
        }

        $user->permissions()->attach($permission->id);

        return response()->envelope(
            data: [
                'user_id'       => $user->id,
                'permission_id' => $permission->id,
                'attached'      => true,
            ],
            meta: [
                'success' => true,
                'message' => __('permissionsroles.permission_attach_success'),
            ]
        );
    }

    /**
     * Detach a permission from a user.
     *
     * Status:
     * - 200 on success
     * - 404 if not attached
     */
    public function detach(User $user, Permission $permission): JsonResponse
    {
        $attached = $user->permissions()
            ->whereKey($permission->id)
            ->exists();

        if (! $attached) {
            return response()->envelope(
                data: [],
                meta: [
                    'success'        => false,
                    'message'        => __('permissionsroles.permission_not_attached'),
                    'user_id'        => $user->id,
                    'permission_id'  => $permission->id,
                ],
                status: 404
            );
        }

        $user->permissions()->detach($permission->id);

        return response()->envelope(
            data: [
                'user_id'       => $user->id,
                'permission_id' => $permission->id,
                'detached'      => true,
            ],
            meta: [
                'success' => true,
                'message' => __('permissionsroles.permission_detach_success'),
            ]
        );
    }

    /**
     * Assign multiple roles/permissions to a user without removing existing links.
     * Empty payloads are allowed (no-ops).
     *
     * Payload:
     * - roles: int[] (optional)
     * - permissions: int[] (optional)
     */
    public function bulkAssign(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'roles'          => ['sometimes', 'array'],
            'roles.*'        => ['integer', 'exists:roles,id'],
            'permissions'    => ['sometimes', 'array'],
            'permissions.*'  => ['integer', 'exists:permissions,id'],
        ]);

        $roleIds = array_values(array_unique($validated['roles'] ?? []));
        $permIds = array_values(array_unique($validated['permissions'] ?? []));

        if ($roleIds) {
            $user->roles()->syncWithoutDetaching($roleIds);
        }
        if ($permIds) {
            $user->permissions()->syncWithoutDetaching($permIds);
        }

        return response()->envelope(
            data: [
                'user_id'                => $user->id,
                'requested_role_ids'     => $roleIds,
                'requested_permission_ids'=> $permIds,
            ],
            meta: [
                'success'    => true,
                'message'    => __('permissionsroles.bulk_assign_success'),
                'roles_total'=> $user->roles()->count(),
                'permissions_total' => $user->permissions()->count(),
            ]
        );
    }

    /**
     * Remove multiple roles/permissions from a user.
     * Empty payloads are allowed (no-ops).
     *
     * Payload:
     * - roles: int[] (optional)
     * - permissions: int[] (optional)
     */
    public function bulkRemove(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'roles'          => ['sometimes', 'array'],
            'roles.*'        => ['integer', 'exists:roles,id'],
            'permissions'    => ['sometimes', 'array'],
            'permissions.*'  => ['integer', 'exists:permissions,id'],
        ]);

        $roleIds = array_values(array_unique($validated['roles'] ?? []));
        $permIds = array_values(array_unique($validated['permissions'] ?? []));

        $rolesDetached = $roleIds ? $user->roles()->detach($roleIds) : 0;
        $permsDetached = $permIds ? $user->permissions()->detach($permIds) : 0;

        return response()->envelope(
            data: [
                'user_id'                 => $user->id,
                'removed_role_ids'        => $roleIds,
                'removed_permission_ids'  => $permIds,
                'roles_detached_count'    => $rolesDetached,
                'permissions_detached_count' => $permsDetached,
            ],
            meta: [
                'success' => true,
                'message' => __('permissionsroles.bulk_remove_success'),
            ]
        );
    }
}
