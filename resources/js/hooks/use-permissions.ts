import { usePage } from '@inertiajs/react';

/**
 * can('operacion.caja.open') → ¿puede el usuario hacerlo?
 * Solo sirve para mostrar u ocultar botones: la autorización real la hace el backend.
 */
export function usePermissions() {
    const { permissions } = usePage().props;

    const can = (permission: string): boolean =>
        permissions === null || permissions.includes(permission.toLowerCase());

    return { can, unrestricted: permissions === null };
}
