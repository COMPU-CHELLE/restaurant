import { Head, router, useForm } from '@inertiajs/react';
import { MoreHorizontal, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { Field } from '@/components/field';
import { FormDialog } from '@/components/form-dialog';
import PasswordInput from '@/components/password-input';
import { StateBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Paginated, TableFilters } from '@/types/pagination';

type UserCompany = {
    company_id: number;
    code: string;
    name: string;
    role_id: number;
    role: string;
};

type UserRow = {
    id: number;
    code: string;
    name: string;
    email: string;
    state: number;
    is_support: boolean;
    companies: UserCompany[];
};

type Props = {
    users: Paginated<UserRow>;
    filters: TableFilters;
    options: {
        companies: { id: number; code: string; name: string }[];
        roles: { id: number; code: string; name: string }[];
    };
};

type Assignment = { company_id: string; role_id: string };

type UserForm = {
    code: string;
    name: string;
    email: string;
    password: string;
    state: boolean;
    is_support: boolean;
    companies: Assignment[];
};

const emptyForm: UserForm = {
    code: '',
    name: '',
    email: '',
    password: '',
    state: true,
    is_support: false,
    companies: [],
};

const URL = '/soporte/usuarios';

export default function Usuarios({ users, filters, options }: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<UserRow | null>(null);
    const form = useForm<UserForm>(emptyForm);

    const adminRole = options.roles.find((role) => role.code === 'ADMIN');

    const openCreate = () => {
        setEditing(null);
        form.setData({
            ...emptyForm,
            companies: [
                { company_id: '', role_id: adminRole ? String(adminRole.id) : '' },
            ],
        });
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
            state: user.state === 1,
            is_support: user.is_support,
            companies: user.companies.map((company) => ({
                company_id: String(company.company_id),
                role_id: String(company.role_id),
            })),
        });
        form.clearErrors();
        setOpen(true);
    };

    const setAssignment = (index: number, patch: Partial<Assignment>) =>
        form.setData(
            'companies',
            form.data.companies.map((row, i) =>
                i === index ? { ...row, ...patch } : row,
            ),
        );

    const addAssignment = () =>
        form.setData('companies', [
            ...form.data.companies,
            { company_id: '', role_id: adminRole ? String(adminRole.id) : '' },
        ]);

    const removeAssignment = (index: number) =>
        form.setData(
            'companies',
            form.data.companies.filter((_, i) => i !== index),
        );

    const submit = () => {
        // Las filas sin empresa o sin rol no se envían.
        form.transform((data) => ({
            ...data,
            companies: data.companies.filter(
                (row) => row.company_id && row.role_id,
            ),
        }));

        const options = {
            preserveScroll: true,
            onSuccess: () => setOpen(false),
        };

        if (editing) {
            form.put(`${URL}/${editing.id}`, options);
        } else {
            form.post(URL, options);
        }
    };

    // Errores de las filas: "companies.0.company_id" → texto
    const rowError = (index: number) =>
        (form.errors as Record<string, string>)[`companies.${index}.company_id`] ??
        (form.errors as Record<string, string>)[`companies.${index}.role_id`];

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
                    <span className="font-medium">{row.name}</span>
                    <span className="text-xs text-muted-foreground">
                        {row.email}
                    </span>
                </div>
            ),
        },
        {
            key: 'access',
            header: 'Acceso',
            cell: (row) => (
                <div className="flex max-w-80 flex-wrap gap-1">
                    {row.is_support && <Badge>Soporte</Badge>}
                    {row.companies.map((company) => (
                        <Badge
                            key={company.company_id}
                            variant="outline"
                            title={company.name}
                        >
                            {company.code} · {company.role}
                        </Badge>
                    ))}
                    {!row.is_support && row.companies.length === 0 && (
                        <span className="text-xs text-muted-foreground">
                            Sin empresas
                        </span>
                    )}
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
                        <Button
                            variant="ghost"
                            size="icon"
                            className="size-8"
                            aria-label="Acciones"
                        >
                            <MoreHorizontal className="size-4" />
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem onSelect={() => openEdit(row)}>
                            Editar
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() =>
                                router.patch(
                                    `${URL}/${row.id}/toggle`,
                                    {},
                                    { preserveScroll: true },
                                )
                            }
                        >
                            {row.state === 1 ? 'Inactivar' : 'Activar'}
                        </DropdownMenuItem>
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
                            Crea usuarios y decide a qué empresas entran y con
                            qué rol.
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
                    emptyText="No hay usuarios."
                    toolbar={
                        <Select
                            value={filters.state ?? 'all'}
                            onValueChange={(value) =>
                                router.get(
                                    URL,
                                    {
                                        ...(filters.search
                                            ? { search: filters.search }
                                            : {}),
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
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Código" htmlFor="code" error={form.errors.code}
                        hint="Minúsculas, sin espacios. Ej.: jperez">
                        <Input id="code" value={form.data.code} autoFocus autoCapitalize="none"
                            onChange={(e) => form.setData('code', e.target.value.toLowerCase())} />
                    </Field>
                    <Field label="Nombre" htmlFor="name" error={form.errors.name}>
                        <Input id="name" value={form.data.name}
                            onChange={(e) => form.setData('name', e.target.value)} />
                    </Field>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Correo" htmlFor="email" error={form.errors.email}>
                        <Input id="email" type="email" value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)} />
                    </Field>
                    <Field label="Contraseña" htmlFor="password" error={form.errors.password}
                        hint={editing ? 'Déjala vacía para no cambiarla.' : 'Mínimo 8 caracteres.'}>
                        <PasswordInput id="password" value={form.data.password}
                            autoComplete="new-password"
                            onChange={(e) => form.setData('password', e.target.value)} />
                    </Field>
                </div>

                <div className="flex flex-wrap gap-6">
                    <div className="flex items-center gap-2">
                        <Checkbox id="state" checked={form.data.state}
                            onCheckedChange={(v) => form.setData('state', v === true)} />
                        <Label htmlFor="state" className="font-normal">Activo</Label>
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox id="is_support" checked={form.data.is_support}
                            onCheckedChange={(v) => form.setData('is_support', v === true)} />
                        <Label htmlFor="is_support" className="font-normal">
                            Usuario de soporte (acceso global)
                        </Label>
                    </div>
                </div>
                {(form.errors.state || form.errors.is_support) && (
                    <p className="text-sm text-red-600 dark:text-red-400">
                        {form.errors.state ?? form.errors.is_support}
                    </p>
                )}

                <div className="grid gap-3 rounded-md border p-3">
                    <div className="flex items-center justify-between">
                        <span className="text-sm font-medium">Empresas</span>
                        <Button type="button" variant="outline" size="sm" onClick={addAssignment}>
                            <Plus /> Agregar empresa
                        </Button>
                    </div>

                    {form.data.companies.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            {form.data.is_support
                                ? 'Un usuario de soporte puede entrar a cualquier empresa sin estar asignado.'
                                : 'Este usuario no podrá entrar a ninguna empresa.'}
                        </p>
                    )}

                    {form.data.companies.map((row, index) => (
                        <div key={index} className="grid gap-1">
                            <div className="flex items-center gap-2">
                                <Select
                                    value={row.company_id}
                                    onValueChange={(value) =>
                                        setAssignment(index, { company_id: value })
                                    }
                                >
                                    <SelectTrigger className="flex-1">
                                        <SelectValue placeholder="Empresa" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {options.companies.map((company) => (
                                            <SelectItem key={company.id} value={String(company.id)}>
                                                {company.code} · {company.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <Select
                                    value={row.role_id}
                                    onValueChange={(value) =>
                                        setAssignment(index, { role_id: value })
                                    }
                                >
                                    <SelectTrigger className="w-40">
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

                                <Button type="button" variant="ghost" size="icon"
                                    onClick={() => removeAssignment(index)} aria-label="Quitar empresa">
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                            {rowError(index) && (
                                <p className="text-sm text-red-600 dark:text-red-400">{rowError(index)}</p>
                            )}
                        </div>
                    ))}

                    <p className="text-xs text-muted-foreground">
                        El perfil de un usuario con rol Usuario lo asigna el administrador de cada empresa.
                    </p>
                </div>
            </FormDialog>
        </>
    );
}

Usuarios.layout = {
    breadcrumbs: [
        { title: 'Soporte', href: '/soporte/empresas' },
        { title: 'Usuarios', href: '/soporte/usuarios' },
    ],
};
