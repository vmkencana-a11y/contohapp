<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ResolvesCurrentAdmin;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\LoggingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RoleController extends Controller
{
    use ResolvesCurrentAdmin;

    public function __construct(
        private LoggingService $logger
    ) {}

    /**
     * Display a listing of roles.
     */
    public function index(): View
    {
        $roles = Role::withCount(['permissions', 'admins'])
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new role.
     */
    public function create(): View
    {
        // Group permissions by module for UI
        $permissions = Permission::all()->groupBy('module');

        return view('admin.roles.create', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): RedirectResponse
    {
        $adminId = $this->currentAdminId($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles'],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        DB::transaction(function () use ($validated, $adminId) {
            $role = Role::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            if (!empty($validated['permissions'])) {
                $role->permissions()->attach($validated['permissions']);
            }

            $this->logger->logAdminActivity(
                $adminId,
                'role.create',
                'Role',
                (string)$role->id,
                'Created new role'
            );
        });

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role berhasil dibuat.');
    }

    /**
     * Show the form for editing the role.
     */
    public function edit(Role $role): View
    {
        $role->load('permissions');
        $permissions = Permission::all()->groupBy('module');

        return view('admin.roles.edit', [
            'role' => $role,
            'permissions' => $permissions,
            'rolePermissions' => $role->permissions->pluck('id')->toArray(),
        ]);
    }

    /**
     * Update the role.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $adminId = $this->currentAdminId($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name,' . $role->id],
            'description' => ['nullable', 'string', 'max:500'],
            'permissions' => ['array'],
            'permissions.*' => ['exists:permissions,id'],
        ]);

        DB::transaction(function () use ($role, $validated, $adminId) {
            $role->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ]);

            $role->permissions()->sync($validated['permissions'] ?? []);

            $this->logger->logAdminActivity(
                $adminId,
                'role.update',
                'Role',
                (string)$role->id,
                'Updated role details and permissions'
            );
        });

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role berhasil diperbarui.');
    }

    /**
     * Remove the role using soft delete logic (or hard delete if specified).
     * Actually tables don't have soft deletes in migration.
     * So hard delete, but prevent if assigned to admins.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $adminId = $this->currentAdminId();

        if (in_array($role->name, ['super_admin', 'Super Admin'])) {
            return back()->with('error', 'Super Admin role tidak bisa dihapus.');
        }

        if ($role->admins()->exists()) {
            return back()->with('error', 'Role sedang digunakan oleh admin. Unassign terlebih dahulu.');
        }

        $role->delete();

        $this->logger->logAdminActivity(
            $adminId,
            'role.delete',
            'Role',
            (string)$role->id,
            'Deleted role'
        );

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role berhasil dihapus.');
    }
}
