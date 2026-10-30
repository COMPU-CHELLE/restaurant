import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

export type CatalogPermission = { id: number; action: string };
export type CatalogLevel = {
    id: number;
    name: string;
    permissions: CatalogPermission[];
};
export type CatalogModule = {
    code: string;
    name: string;
    levels: CatalogLevel[];
};

const ACTION_LABELS: Record<string, string> = {
    view: 'Ver',
    store: 'Crear',
    update: 'Editar',
    toggle: 'Activar/inactivar',
    delete: 'Eliminar',
    restore: 'Restaurar',
    layout: 'Diseñar plano',
    pay: 'Cobrar',
    move: 'Mover mesa',
    split: 'Dividir cuenta',
    open: 'Abrir caja',
    close: 'Cerrar caja',
    movement: 'Ingresos/egresos',
    prices: 'Precios',
    images: 'Imágenes',
    print: 'Imprimir',
};

type Props = {
    catalog: CatalogModule[];
    value: number[];
    onChange: (ids: number[]) => void;
};

type Check = boolean | 'indeterminate';

/**
 * Matriz de permisos por módulo y pantalla. Regla: cualquier acción exige "Ver"
 * (el servidor también lo garantiza); quitar "Ver" quita todas las acciones de esa pantalla.
 */
export function PermissionMatrix({ catalog, value, onChange }: Props) {
    const selected = new Set(value);

    const state = (ids: number[]): Check => {
        const count = ids.filter((id) => selected.has(id)).length;

        return count === 0 ? false : count === ids.length ? true : 'indeterminate';
    };

    const togglePermission = (
        level: CatalogLevel,
        permission: CatalogPermission,
        checked: boolean,
    ) => {
        const next = new Set(selected);
        const view = level.permissions.find((item) => item.action === 'view');

        if (checked) {
            next.add(permission.id);

            if (view) {
                next.add(view.id);
            }
        } else {
            next.delete(permission.id);

            if (permission.action === 'view') {
                level.permissions.forEach((item) => next.delete(item.id));
            }
        }

        onChange(Array.from(next));
    };

    const toggleMany = (ids: number[], checked: boolean) => {
        const next = new Set(selected);
        ids.forEach((id) => (checked ? next.add(id) : next.delete(id)));
        onChange(Array.from(next));
    };

    return (
        <div className="grid gap-3">
            {catalog.map((module) => {
                const moduleIds = module.levels.flatMap((level) =>
                    level.permissions.map((permission) => permission.id),
                );

                return (
                    <div key={module.code} className="rounded-md border">
                        <div className="flex items-center gap-2 border-b bg-muted/40 px-3 py-2">
                            <Checkbox
                                id={`module-${module.code}`}
                                checked={state(moduleIds)}
                                onCheckedChange={(v) => toggleMany(moduleIds, v === true)}
                            />
                            <Label
                                htmlFor={`module-${module.code}`}
                                className="font-semibold"
                            >
                                {module.name}
                            </Label>
                        </div>

                        <div className="divide-y">
                            {module.levels.map((level) => {
                                const levelIds = level.permissions.map((p) => p.id);

                                return (
                                    <div
                                        key={level.id}
                                        className="grid gap-2 px-3 py-2 sm:grid-cols-[180px_1fr]"
                                    >
                                        <div className="flex items-center gap-2">
                                            <Checkbox
                                                id={`level-${level.id}`}
                                                checked={state(levelIds)}
                                                onCheckedChange={(v) =>
                                                    toggleMany(levelIds, v === true)
                                                }
                                            />
                                            <Label
                                                htmlFor={`level-${level.id}`}
                                                className="text-sm"
                                            >
                                                {level.name}
                                            </Label>
                                        </div>

                                        <div className="flex flex-wrap gap-x-4 gap-y-1">
                                            {level.permissions.map((permission) => (
                                                <div
                                                    key={permission.id}
                                                    className="flex items-center gap-1.5"
                                                >
                                                    <Checkbox
                                                        id={`perm-${permission.id}`}
                                                        checked={selected.has(permission.id)}
                                                        onCheckedChange={(v) =>
                                                            togglePermission(
                                                                level,
                                                                permission,
                                                                v === true,
                                                            )
                                                        }
                                                    />
                                                    <Label
                                                        htmlFor={`perm-${permission.id}`}
                                                        className="text-xs font-normal"
                                                    >
                                                        {ACTION_LABELS[permission.action] ??
                                                            permission.action}
                                                    </Label>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
