<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Role;
use App\Services\LoggingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminManagementController extends Controller
{
    public function __construct(
        private LoggingService $logger
    ) {}

    public function index(): View
    {
        $admins = Admin::with('roles')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.admins.index', [
            'admins' => $admins,
        ]);
    }

    public function create(): View
    {
        $roles = Role::orderBy('name')->get();
        return view('admin.admins.create', [
            'roles' => $roles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:12', 'confirmed',
                \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()->symbols()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
            'working_days' => ['nullable', 'array'],
            'working_days.*' => ['integer', 'between:1,7'],
            'work_start_time' => ['nullable', 'date_format:H:i'],
            'work_end_time' => ['nullable', 'date_format:H:i', 'after:work_start_time'],
        ]);

        // Check email uniqueness using email_hash (since email is encrypted)
        $emailHash = hash('sha256', strtolower($validated['email']));
        if (Admin::where('email_hash', $emailHash)->exists()) {
            return back()->withErrors(['email' => 'Email sudah digunakan.'])->withInput();
        }

        DB::transaction(function () use ($validated) {
            $admin = Admin::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'], // Mutator hashes this
                'status' => 'active',
                'working_days' => $validated['working_days'] ?? null,
                'work_start_time' => isset($validated['work_start_time']) ? $validated['work_start_time'] . ':00' : null,
                'work_end_time' => isset($validated['work_end_time']) ? $validated['work_end_time'] . ':00' : null,
            ]);

            // SECURITY: Prevent non-super-admins from assigning super_admin role
            $rolesToAssign = $validated['roles'];
            $currentAdmin = Auth::guard('admin')->user();
            if (!$currentAdmin->hasRole('super_admin')) {
                $superAdminRoleId = \App\Models\Role::where('name', 'super_admin')->value('id');
                $rolesToAssign = array_filter($rolesToAssign, fn($id) => (int)$id !== (int)$superAdminRoleId);
                if (empty($rolesToAssign)) {
                    throw new \RuntimeException('Tidak ada role valid yang dapat di-assign.');
                }
            }

            $admin->roles()->sync($rolesToAssign);

            $this->logger->logAdminActivity(
                Auth::guard('admin')->id(),
                'admin.create',
                'Admin',
                (string)$admin->id,
                'Created new admin'
            );
        });

        return redirect()->route('admin.admins.index')
            ->with('success', 'Admin berhasil dibuat.');
    }

    public function edit(Admin $admin): View
    {
        $roles = Role::orderBy('name')->get();
        $adminRoles = $admin->roles->pluck('id')->toArray();

        return view('admin.admins.edit', [
            'admin' => $admin,
            'roles' => $roles,
            'adminRoles' => $adminRoles,
        ]);
    }

    public function update(Request $request, Admin $admin): RedirectResponse
    {
        if ($admin->id === Auth::guard('admin')->id()) {
            return back()->with('error', 'Gunakan halaman Profil untuk mengubah data sendiri.');
        }

        if ($admin->hasRole('super_admin')) {
            return back()->with('error', 'Tidak dapat mengubah data Super Admin.');
        }

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['exists:roles,id'],
            'working_days' => ['nullable', 'array'],
            'working_days.*' => ['integer', 'between:1,7'],
            'work_start_time' => ['nullable', 'date_format:H:i'],
            'work_end_time' => ['nullable', 'date_format:H:i', 'after:work_start_time'],
        ];

        if ($request->filled('password')) {
            $rules['password'] = ['string', 'min:12', 'confirmed',
                \Illuminate\Validation\Rules\Password::min(12)->mixedCase()->numbers()->symbols()];
        }

        $validated = $request->validate($rules);

        // Check email uniqueness using email_hash (since email is encrypted)
        $emailHash = hash('sha256', strtolower($validated['email']));
        $existingAdmin = Admin::where('email_hash', $emailHash)
            ->where('id', '!=', $admin->id)
            ->first();
        
        if ($existingAdmin) {
            return back()->withErrors(['email' => 'Email sudah digunakan.'])->withInput();
        }

        DB::transaction(function () use ($admin, $validated, $request) {
            $data = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'working_days' => $validated['working_days'] ?? null,
                'work_start_time' => isset($validated['work_start_time']) ? $validated['work_start_time'] . ':00' : null,
                'work_end_time' => isset($validated['work_end_time']) ? $validated['work_end_time'] . ':00' : null,
            ];

            if ($request->filled('password')) {
                $data['password'] = $validated['password'];
            }

            $admin->update($data);
            // SECURITY: Prevent non-super-admins from assigning super_admin role
            $rolesToAssign = $validated['roles'];
            $currentAdmin = Auth::guard('admin')->user();
            if (!$currentAdmin->hasRole('super_admin')) {
                $superAdminRoleId = \App\Models\Role::where('name', 'super_admin')->value('id');
                $rolesToAssign = array_filter($rolesToAssign, fn($id) => (int)$id !== (int)$superAdminRoleId);
                if (empty($rolesToAssign)) {
                    throw new \RuntimeException('Tidak ada role valid yang dapat di-assign.');
                }
            }

            $admin->roles()->sync($rolesToAssign);

            $this->logger->logAdminActivity(
                Auth::guard('admin')->id(),
                'admin.update',
                'Admin',
                (string)$admin->id,
                'Updated admin profile'
            );
        });

        return redirect()->route('admin.admins.index')
            ->with('success', 'Admin berhasil diperbarui.');
    }

    public function toggleStatus(Admin $admin): RedirectResponse
    {
        if ($admin->id === Auth::guard('admin')->id()) {
            return back()->with('error', 'Tidak dapat mengubah status sendiri.');
        }

        if ($admin->hasRole('super_admin')) {
            return back()->with('error', 'Tidak dapat mengubah status Super Admin.');
        }

        $newStatus = $admin->isActive() ? 'suspended' : 'active';
        
        $admin->update(['status' => $newStatus]);

        $this->logger->logAdminActivity(
            Auth::guard('admin')->id(),
            "admin.{$newStatus}",
            'Admin',
            (string)$admin->id,
            "Changed status to {$newStatus}"
        );

        return back()->with('success', "Status admin diubah menjadi {$newStatus}.");
    }

    public function destroy(Admin $admin): RedirectResponse
    {
        if ($admin->id === Auth::guard('admin')->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        if ($admin->hasRole('super_admin')) {
            return back()->with('error', 'Tidak dapat menghapus Super Admin.');
        }

        $admin->delete();

        $this->logger->logAdminActivity(
            Auth::guard('admin')->id(),
            'admin.delete',
            'Admin',
            (string)$admin->id,
            'Deleted admin account'
        );

        return redirect()->route('admin.admins.index')
            ->with('success', 'Admin berhasil dihapus.');
    }
}
