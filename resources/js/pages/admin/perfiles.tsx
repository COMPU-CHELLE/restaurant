import { Head, router, useForm } from '@inertiajs/react';
import { MoreHorizontal, Plus } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { Field } from '@/components/field';
import { FormDialog } from '@/components/form-dialog';
import { PermissionMatrix } from '@/components/permission-matrix';
import type { CatalogModule } from '@/components/permission-matrix';
import { StateBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Paginated, TableFilters } from '@/types/pagination';

type ProfileRow = {
    id: number;
    code: string;
    name: string;
    detail: string | null;
    state: number;
    permission_ids: number[];
    users_count: number;
};

type Props = {
    profiles: Paginated<ProfileRow>;
    filters: TableFilters;
    catalog: CatalogModule[];
};

type ProfileForm = {
    code: string;
    name: string;
    detail: string;
    permissions: number[];
};

const emptyForm: ProfileForm = { code: '', name: '', detail: '', permissions: [] };
const URL = '/admin/perfiles';

export default function Perfiles({ profiles, filters, catalog }: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<ProfileRow | null>(null);
    const [deleting, setDeleting] = useState<ProfileRow | null>(null);
    const form = useForm<ProfileForm>(emptyForm);

    const openCreate = () => {
        setEditing(null);
        form.setData(emptyForm);
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (profile: ProfileRow) => {
        setEditing(profile);
        form.setData({
            code: profile.code,
            name: profile.name,
            detail: profile.detail ?? '',
            permissions: profile.permission_ids,
        });
        form.clearErrors();
        setOpen(true);
    };

    const submit = () => {
        const settings = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(`${URL}/${editing.id}`, settings);
        } else {
            form.post(URL, settings);
        }
    };

    const columns: Column<ProfileRow>[] = [
        {
            key: 'code',
            header: 'Código',
            sortable: true,
            cell: (row) => <span className="font-mono text-xs">{row.code}</span>,
        },
        {
            key: 'name',
            header: 'Perfil',
            sortable: true,
            cell: (row) => (
                <div className="flex flex-col">
                    <span className="font-medium">{row.name}</span>
                    {row.detail && (
                        <span className="text-xs text-muted-foreground">{row.detail}</span>
                    )}
                </div>
            ),
        },
        {
            key: 'permissions',
            header: 'Permisos',
            cell: (row) => <Badge variant="outline">{row.permission_ids.length}</Badge>,
        },
        {
            key: 'users',
            header: 'Usuarios',
            cell: (row) => <Badge variant="outline">{row.users_count}</Badge>,
        },
        {
            key: 'state',
            header: 'Estado',
            sortable: true,
            cell: (row) => <StateBadge state={row.state} />,
        },
        {
            key: 'actions',
            header: '',
            className: 'w-10 text-right',
            cell: (row) => (
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon" className="size-8" aria-label="Acciones">
                            <MoreHorizontal className="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem onSelect={() => openEdit(row)}>
                            Editar
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() =>
                                router.patch(`${URL}/${row.id}/toggle`, {}, { preserveScroll: true })
                            }
                        >
                            {row.state === 1 ? 'Inactivar' : 'Activar'}
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            className="text-red-600 focus:text-red-600"
                            onSelect={() => setDeleting(row)}
                        >
                            Eliminar
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    return (
        <>
            <Head title="Perfiles" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">Perfiles</h1>
                        <p className="text-sm text-muted-foreground">
                            Un perfil define qué puede hacer un usuario en esta empresa
                            (por ejemplo: mesero, cajero, gerente).
                        </p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus /> Nuevo perfil
                    </Button>
                </div>

                <DataTable
                    url={URL}
                    data={profiles}
                    filters={filters}
                    columns={columns}
                    rowKey={(row) => row.id}
                    searchPlaceholder="Buscar por código o nombre…"
                    emptyText="Aún no hay perfiles. Crea el primero."
                    toolbar={
                        <Select
                            value={filters.state ?? 'all'}
                            onValueChange={(value) =>
                                router.get(
                                    URL,
                                    {
                                        ...(filters.search ? { search: filters.search } : {}),
                                        ...(value !== 'all' ? { state: value } : {}),
                                        per_page: filters.per_page,
                                    },
                                    { preserveState: true, replace: true },
                                )
                            }
                        >
                            <SelectTrigger className="w-36">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                <SelectItem value="1">Activos</SelectItem>
                                <SelectItem value="0">Inactivos</SelectItem>
                            </SelectContent>
                        </Select>
                    }
                />
            </div>

            <FormDialog
                open={open}
                onOpenChange={setOpen}
                title={editing ? 'Editar perfil' : 'Nuevo perfil'}
                description="Marca lo que podrá hacer quien tenga este perfil."
                processing={form.processing}
                onSubmit={submit}
                wide
            >
                <div className="grid gap-4 sm:grid-cols-3">
                    <Field label="Código" htmlFor="code" error={form.errors.code}>
                        <Input id="code" value={form.data.code} autoFocus
                            onChange={(e) => form.setData('code', e.target.value.toUpperCase())} />
                    </Field>
                    <Field label="Nombre" htmlFor="name" error={form.errors.name} className="sm:col-span-2">
                        <Input id="name" value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)} />
                    </Field>
                </div>

                <Field label="Descripción" htmlFor="detail" error={form.errors.detail}>
                    <Input id="detail" value={form.data.detail}
                        onChange={(e) => form.setData('detail', e.target.value)} />
                </Field>

                <div className="grid gap-2">
                    <span className="text-sm font-medium">Permisos</span>
                    <PermissionMatrix
                        catalog={catalog}
                        value={form.data.permissions}
                        onChange={(ids) => form.setData('permissions', ids)}
                    />
                </div>
            </FormDialog>

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(value) => !value && setDeleting(null)}
                title="Eliminar perfil"
                description={`¿Eliminar el perfil “${deleting?.name ?? ''}”? No se puede si tiene usuarios asignados.`}
                confirmLabel="Eliminar"
                destructive
                onConfirm={() =>
                    deleting &&
                    router.delete(`${URL}/${deleting.id}`, { preserveScroll: true })
                }
            />
        </>
    );
}

Perfiles.layout = {
    breadcrumbs: [
        { title: 'Administración', href: '/admin/empresa' },
        { title: 'Perfiles', href: '/admin/perfiles' },
    ],
};
