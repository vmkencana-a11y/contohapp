<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $permissions = collect([
            ['name' => 'secrets.view', 'description' => 'View secret manager', 'module' => 'secrets'],
            ['name' => 'secrets.manage', 'description' => 'Create, update, and delete secrets', 'module' => 'secrets'],
        ])->map(function (array $permission) {
            return Permission::firstOrCreate(
                ['name' => $permission['name']],
                array_merge($permission, ['created_at' => now()])
            );
        });

        $superAdmin = Role::query()->where('name', 'super_admin')->first();

        if ($superAdmin) {
            $superAdmin->permissions()->syncWithoutDetaching($permissions->pluck('id')->all());
        }
    }

    public function down(): void
    {
        Permission::query()
            ->whereIn('name', ['secrets.view', 'secrets.manage'])
            ->delete();
    }
};
