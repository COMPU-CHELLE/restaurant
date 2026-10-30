import { Head, router, useForm } from '@inertiajs/react';
import { MoreHorizontal, Plus, PlugZap } from 'lucide-react';
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

type ConfigRow = {
    id: number;
    company_id: number;
    company_code: string | null;
    company_name: string | null;
    environment: 'sandbox' | 'production';
    email: string;
    active: boolean;
    has_token: boolean;
};

type Props = {
    configs: Paginated<ConfigRow>;
    filters: TableFilters;
    options: {
        companies: { id: number; code: string; name: string }[];
        hosts: Record<string, string>;
    };
};

type PayForm = {
    id: number | null;
    codemp: string;
    environment: 'sandbox' | 'production';
    email: string;
    password: string;
    active: boolean;
};

const URL = '/soporte/factus-pay';

const blank = (): PayForm => ({
    id: null,
    codemp: '',
    environment: 'sandbox',
    email: '',
    password: '',
    active: true,
});

export default function FactusPay({ configs, filters, options }: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<ConfigRow | null>(null);
    const form = useForm<PayForm>(blank());

    const openCreate = () => {
        setEditing(null);
        form.setData(blank());
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (row: ConfigRow) => {
        setEditing(row);
        form.setData({
            id: row.id,
            codemp: String(row.company_id),
            environment: row.environment,
            email: row.email,
            password: '',
            active: row.active,
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

    const testForm = () =>
        form.post(`${URL}/test`, { preserveScroll: true, preserveState: true });

    const columns: Column<ConfigRow>[] = [
        {
            key: 'company',
            header: 'Empresa',
            cell: (row) => (
                <div className="flex flex-col">
                    <span className="font-medium">{row.company_name}</span>
                    <span className="font-mono text-xs text-muted-foreground">
                        {row.company_code}
                    </span>
                </div>
            ),
        },
        {
            key: 'environment',
            header: 'Ambiente',
            sortable: true,
            cell: (row) => (
                <Badge variant={row.environment === 'production' ? 'default' : 'secondary'}>
                    {row.environment === 'production' ? 'Producción' : 'Pruebas'}
                </Badge>
            ),
        },
        { key: 'email', header: 'Correo', sortable: true, cell: (row) => row.email },
        {
            key: 'token',
            header: 'Conexión',
            cell: (row) => (
                <Badge variant="outline">{row.has_token ? 'Con token' : 'Sin token'}</Badge>
            ),
        },
        {
            key: 'active',
            header: 'Estado',
            sortable: true,
            cell: (row) => <StateBadge state={row.active ? 1 : 0} />,
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
                        <DropdownMenuItem onSelect={() => openEdit(row)}>Editar</DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() =>
                                router.post(`${URL}/${row.id}/test`, {}, { preserveScroll: true })
                            }
                        >
                            Probar conexión
                        </DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() =>
                                router.patch(`${URL}/${row.id}/toggle`, {}, { preserveScroll: true })
                            }
                        >
                            {row.active ? 'Inactivar' : 'Activar'}
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    return (
        <>
            <Head title="Factus Pay" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">Factus Pay</h1>
                        <p className="text-sm text-muted-foreground">
                            Credenciales para cobrar con QR o Bre-B, por empresa y ambiente. El
                            servidor lo define el sistema según el ambiente.
                        </p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus /> Nueva configuración
                    </Button>
                </div>

                <DataTable
                    url={URL}
                    data={configs}
                    filters={filters}
                    columns={columns}
                    rowKey={(row) => row.id}
                    searchPlaceholder="Buscar por empresa o correo…"
                    emptyText="Aún no hay configuraciones de Factus Pay."
                />
            </div>

            <FormDialog
                open={open}
                onOpenChange={setOpen}
                title={editing ? 'Editar configuración' : 'Nueva configuración'}
                description="La contraseña se guarda cifrada y no se vuelve a mostrar."
                processing={form.processing}
                onSubmit={submit}
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Empresa" error={form.errors.codemp}>
                        <Select
                            value={form.data.codemp}
                            onValueChange={(value) => form.setData('codemp', value)}
                        >
                            <SelectTrigger>
                                <SelectValue placeholder="Selecciona la empresa" />
                            </SelectTrigger>
                            <SelectContent>
                                {options.companies.map((company) => (
                                    <SelectItem key={company.id} value={String(company.id)}>
                                        {company.code} · {company.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>

                    <Field
                        label="Ambiente"
                        error={form.errors.environment}
                        hint={options.hosts[form.data.environment]}
                    >
                        <Select
                            value={form.data.environment}
                            onValueChange={(value) =>
                                form.setData('environment', value as 'sandbox' | 'production')
                            }
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="sandbox">Pruebas (sandbox)</SelectItem>
                                <SelectItem value="production">Producción</SelectItem>
                            </SelectContent>
                        </Select>
                    </Field>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Correo" htmlFor="email" error={form.errors.email}>
                        <Input
                            id="email"
                            type="email"
                            autoComplete="off"
                            value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)}
                        />
                    </Field>
                    <Field
                        label="Contraseña"
                        htmlFor="password"
                        error={form.errors.password}
                        hint={editing ? 'Déjala vacía para conservar la guardada.' : undefined}
                    >
                        <PasswordInput
                            id="password"
                            autoComplete="new-password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                        />
                    </Field>
                </div>

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="active"
                            checked={form.data.active}
                            onCheckedChange={(v) => form.setData('active', v === true)}
                        />
                        <Label htmlFor="active" className="font-normal">
                            Activa
                        </Label>
                    </div>
                    <Button type="button" variant="outline" onClick={testForm} disabled={form.processing}>
                        <PlugZap /> Probar conexión
                    </Button>
                </div>
            </FormDialog>
        </>
    );
}

FactusPay.layout = {
    breadcrumbs: [
        { title: 'Soporte', href: '/soporte/empresas' },
        { title: 'Factus Pay', href: '/soporte/factus-pay' },
    ],
};
