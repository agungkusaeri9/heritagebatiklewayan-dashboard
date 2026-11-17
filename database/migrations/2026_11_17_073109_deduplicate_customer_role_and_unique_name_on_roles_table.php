<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $rolesTable = $tableNames['roles'] ?? 'roles';
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $rolePivotKey = $columnNames['role_pivot_key'] ?? 'role_id';
        $permissionPivotKey = $columnNames['permission_pivot_key'] ?? 'permission_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';

        $duplicateNames = DB::table($rolesTable)
            ->select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        foreach ($duplicateNames as $roleName) {
            $roles = DB::table($rolesTable)
                ->where('name', $roleName)
                ->orderBy('id')
                ->get();

            $preferredGuard = $roleName === 'customer' ? 'api' : 'web';
            $keptRole = $roles->firstWhere('guard_name', $preferredGuard) ?? $roles->first();

            if (! $keptRole) {
                continue;
            }

            $duplicateIds = $roles
                ->where('id', '!=', $keptRole->id)
                ->pluck('id')
                ->all();

            if ($duplicateIds === []) {
                continue;
            }

            $modelLinks = DB::table($modelHasRolesTable)
                ->whereIn($rolePivotKey, $duplicateIds)
                ->get();

            foreach ($modelLinks as $link) {
                $payload = [
                    $rolePivotKey => $keptRole->id,
                    'model_type' => $link->model_type,
                    $modelMorphKey => $link->{$modelMorphKey},
                ];

                if (property_exists($link, 'team_id')) {
                    $payload['team_id'] = $link->team_id;
                }

                DB::table($modelHasRolesTable)->insertOrIgnore($payload);
            }

            DB::table($modelHasRolesTable)->whereIn($rolePivotKey, $duplicateIds)->delete();

            $permissionLinks = DB::table($roleHasPermissionsTable)
                ->whereIn($rolePivotKey, $duplicateIds)
                ->get();

            foreach ($permissionLinks as $link) {
                if ($keptRole->guard_name === 'web') {
                    DB::table($roleHasPermissionsTable)->insertOrIgnore([
                        $permissionPivotKey => $link->{$permissionPivotKey},
                        $rolePivotKey => $keptRole->id,
                    ]);
                }
            }

            DB::table($roleHasPermissionsTable)->whereIn($rolePivotKey, $duplicateIds)->delete();
            DB::table($rolesTable)->whereIn('id', $duplicateIds)->delete();
        }

        Schema::table($rolesTable, static function (Blueprint $table): void {
            $table->unique('name', 'roles_name_unique');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $rolesTable = $tableNames['roles'] ?? 'roles';

        Schema::table($rolesTable, static function (Blueprint $table): void {
            $table->dropUnique('roles_name_unique');
        });
    }
};
