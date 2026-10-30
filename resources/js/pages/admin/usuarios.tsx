import { Head, router, useForm } from '@inertiajs/react';
import { MoreHorizontal, Plus } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { Field } from '@/components/field';
import { FormDialog } from '@/components/form-dialog';
import PasswordInput from '@/components/password-input';
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

type UserRow = {
    id: number;
    code: string;
    name: string;
    email: string;
    state: number;
    role_id: number | null;
    role: string | null;
    profile_id: number | null;
    profile: string | null;
    exclusive: boolean;
    is_self: boolean;
};

type Props = {
    users: Paginated<UserRow>;
    filters: TableFilters;
    options: {
        roles: { id: number; code: string; name: string }[];
        profiles: { id: number; code: string; name: string }[];
    };
};

type UserForm = {
    code: string;
    name: string;
    email: string;
    password: string;
    role_id: string;
    profile_id: string;
};

const URL = '/admin/usuarios';

export default function Usuarios({ users, filters, options }: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<UserRow | null>(null);
    const [detaching, setDetaching] = useState<UserRow | null>(null);

    const userRole = options.roles.find((role) => role.code === 'USER');

    const blank = (): UserForm => ({
        code: '',
        name: '',
        email: '',
        password: '',
        role_id: userRole ? String(userRole.id) : '',
        profile_id: '',
    });

    const form = useForm<UserForm>(blank());

    // Código, nombre, correo y contraseña solo se cambian si el usuario es exclusivo de esta empresa.
    const identityLocked = editing !== null && !editing.exclusive;
    const isUserRole =
        options.roles.find((role) => String(role.id) === form.data.role_id)?.code === 'USER';

    const openCreate = () => {
        setEditing(null);
        form.setData(blank());
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (user: UserRow) => {
        setEditing(user);
        form.setData({
            code: user.code,
            name: user.name,
            email: user.email,
            password: '',
            role_id: user.role_id ? String(user.role_id) : '',
            profile_id: user.profile_id ? String(user.profile_id) : '',
        });
        form.clearErrors();
        setOpen(true);
    };

    const submit = () => {
        form.transform((data) => {
            const base = { role_id: data.role_id, profile_id: isUserRole ? data.profile_id : '' };

            return identityLocked ? base : { ...data, ...base };
        });

        const settings = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(`${URL}/${editing.id}`, settings);
        } else {
            form.post(URL, settings);
        }
    };

    const columns: Column<UserRow>[] = [
        {
            key: 'code',
            header: 'Código',
            sortable: true,
            cell: (row) => <span className="font-mono text-xs">{row.code}</span>,
        },
        {
            key: 'name',
            header: 'Usuario',
            sortable: true,
            cell: (row) => (
                <div className="flex flex-col">
                    <span className="font-medium">
                        {row.name}
                        {row.is_self && (
                            <span className="ml-2 text-xs text-muted-foreground">(tú)</span>
                        )}
                    </span>
                    <span className="text-xs text-muted-foreground">{row.email}</span>
                </div>
            ),
        },
        {
            key: 'role',
            header: 'Rol y perfil',
            cell: (row) => (
                <div className="flex flex-wrap gap-1">
                    <Badge variant={row.role === 'ADMIN' ? 'default' : 'secondary'}>
                        {row.role === 'ADMIN' ? 'Administrador' : 'Usuario'}
                    </Badge>
                    {row.profile && <Badge variant="outline">{row.profile}</Badge>}
                </div>
            ),
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
                        {row.exclusive && !row.is_self && (
                            <DropdownMenuItem
                                onSelect={() =>
                                    router.patch(`${URL}/${row.id}/toggle`, {}, { preserveScroll: true })
                                }
                            >
                                {row.state === 1 ? 'Inactivar' : 'Activar'}
                            </DropdownMenuItem>
                        )}
                        {!row.is_self && (
                            <>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem
                                    className="text-red-600 focus:text-red-600"
                                    onSelect={() => setDetaching(row)}
                                >
                                    Quitar de la empresa
                                </DropdownMenuItem>
                            </>
                        )}
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    return (
        <>
            <Head title="Usuarios" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">Usuarios</h1>
                        <p className="text-sm text-muted-foreground">
                            Personas con acceso a esta empresa, su rol y su perfil.
                        </p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus /> Nuevo usuario
                    </Button>
                </div>

                <DataTable
                    url={URL}
                    data={users}
                    filters={filters}
                    columns={columns}
                    rowKey={(row) => row.id}
                    searchPlaceholder="Buscar por código, nombre o correo…"
                    emptyText="No hay usuarios en esta empresa."
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
                title={editing ? 'Editar usuario' : 'Nuevo usuario'}
                description="El usuario entra con su código y su contraseña."
                processing={form.processing}
                onSubmit={submit}
                wide
            >
                {identityLocked && (
                    <p className="rounded-md border bg-muted/40 p-3 text-sm text-muted-foreground">
                        Este usuario también pertenece a otras empresas, así que sus datos de
                        acceso solo los cambia soporte. Aquí puedes ajustar su rol y su perfil.
                    </p>
                )}

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Código" htmlFor="code" error={form.errors.code}
                        hint="Minúsculas, sin espacios. Ej.: jperez">
                        <Input id="code" value={form.data.code} autoCapitalize="none"
                            disabled={identityLocked}
                            onChange={(e) => form.setData('code', e.target.value.toLowerCase())} />
                    </Field>
                    <Field label="Nombre" htmlFor="name" error={form.errors.name}>
                        <Input id="name" value={form.data.name} disabled={identityLocked}
                            onChange={(e) => form.setData('name', e.target.value)} />
                    </Field>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Correo" htmlFor="email" error={form.errors.email}>
                        <Input id="email" type="email" value={form.data.email} disabled={identityLocked}
                            onChange={(e) => form.setData('email', e.target.value)} />
                    </Field>
                    <Field label="Contraseña" htmlFor="password" error={form.errors.password}
                        hint={editing ? 'Déjala vacía para no cambiarla.' : 'Mínimo 8 caracteres.'}>
                        <PasswordInput id="password" value={form.data.password} disabled={identityLocked}
                            autoComplete="new-password"
                            onChange={(e) => form.setData('password', e.target.value)} />
                    </Field>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Rol" error={form.errors.role_id}>
                        <Select
                            value={form.data.role_id}
                            onValueChange={(value) => form.setData('role_id', value)}
                            disabled={editing?.is_self}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Rol" />
                            </SelectTrigger>
                            <SelectContent>
                                {options.roles.map((role) => (
                                    <SelectItem key={role.id} value={String(role.id)}>
                                        {role.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>

                    {isUserRole && (
                        <Field label="Perfil" error={form.errors.profile_id}
                            hint={options.profiles.length === 0 ? 'Primero crea un perfil en Administración → Perfiles.' : undefined}>
                            <Select
                                value={form.data.profile_id}
                                onValueChange={(value) => form.setData('profile_id', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Perfil" />
                                </SelectTrigger>
                                <SelectContent>
                                    {options.profiles.map((profile) => (
                                        <SelectItem key={profile.id} value={String(profile.id)}>
                                            {profile.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                    )}
                </div>

                {!isUserRole && (
                    <p className="text-xs text-muted-foreground">
                        El administrador no necesita perfil: ve toda la administración de la empresa.
                    </p>
                )}
            </FormDialog>

            <ConfirmDialog
                open={detaching !== null}
                onOpenChange={(value) => !value && setDetaching(null)}
                title="Quitar de la empresa"
                description={`“${detaching?.name ?? ''}” dejará de tener acceso a esta empresa. Su cuenta no se elimina.`}
                confirmLabel="Quitar"
                destructive
                onConfirm={() =>
                    detaching &&
                    router.delete(`${URL}/${detaching.id}`, { preserveScroll: true })
                }
            />
        </>
    );
}

Usuarios.layout = {
    breadcrumbs: [
        { title: 'Administración', href: '/admin/empresa' },
        { title: 'Usuarios', href: '/admin/usuarios' },
    ],
};
