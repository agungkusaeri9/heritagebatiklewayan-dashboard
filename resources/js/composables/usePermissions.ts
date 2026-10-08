import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function usePermissions() {
    const page = usePage();

    const user = computed(() => page.props.auth?.user as any);

    const roles = computed<string[]>(() => {
        return user.value?.roles || [];
    });

    const permissions = computed<string[]>(() => {
        return user.value?.permissions || [];
    });

    const isSuperAdmin = computed<boolean>(() => {
        return roles.value.includes('super admin');
    });

    const hasRole = (roleName: string | string[]): boolean => {
        if (!user.value) return false;
        if (isSuperAdmin.value) return true;

        if (Array.isArray(roleName)) {
            return roleName.some(r => roles.value.includes(r));
        }

        return roles.value.includes(roleName);
    };

    const can = (permissionName: string | string[]): boolean => {
        if (!user.value) return false;
        if (isSuperAdmin.value) return true;

        if (Array.isArray(permissionName)) {
            return permissionName.some(p => permissions.value.includes(p));
        }

        return permissions.value.includes(permissionName);
    };

    const canAny = (permissionNames: string[]): boolean => {
        if (!user.value) return false;
        if (isSuperAdmin.value) return true;

        return permissionNames.some(p => permissions.value.includes(p));
    };

    const canAll = (permissionNames: string[]): boolean => {
        if (!user.value) return false;
        if (isSuperAdmin.value) return true;

        return permissionNames.every(p => permissions.value.includes(p));
    };

    return {
        user,
        roles,
        permissions,
        isSuperAdmin,
        hasRole,
        can,
        canAny,
        canAll,
    };
}
