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
    base_url: string;
    api_version: string;
    client_id: string;
    username: string;
    active: boolean;
    token_status: 'none' | 'valid' | 'expired';
    token_expires_at: string | null;
};

type Props = {
    configs: Paginated<ConfigRow>;
    filters: TableFilters;
    options: {
        companies: { id: number; code: string; name: string }[];
        defaults: Record<string, string>;
    };
};

type FactusForm = {
    id: number | null;
    codemp: string;
    environment: 'sandbox' | 'production';
    base_url: string;
    api_version: string;
    client_id: string;
    client_secret: string;
    username: string;
    password: string;
    active: boolean;
};

const URL = '/soporte/factus';

const tokenLabel = {
    none: { text: 'Sin token', variant: 'outline' as const },
    valid: { text: 'Token vigente', variant: 'secondary' as const },
    expired: { text: 'Token vencido', variant: 'outline' as const },
};

export default function Factus({ configs, filters, options }: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<ConfigRow | null>(null);

    const blank = (): FactusForm => ({
        id: null,
        codemp: '',
        environment: 'sandbox',
        base_url: options.defaults.sandbox ?? '',
        api_version: 'v1',
        client_id: '',
        client_secret: '',
        username: '',
        password: '',
        active: true,
    });

    const form = useForm<FactusForm>(blank());

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
            base_url: row.base_url,
            api_version: row.api_version,
            client_id: row.client_id,
            client_secret: '',
            username: row.username,
            password: '',
            active: row.active,
        });
        form.clearErrors();
        setOpen(true);
    };

    // Al cambiar de ambiente se sugiere la URL oficial de ese ambiente.
    const changeEnvironment = (environment: 'sandbox' | 'production') => {
        const known = Object.values(options.defaults);
        form.setData({
            ...form.data,
            environment,
            base_url:
                !form.data.base_url || known.includes(form.data.base_url)
                    ? (options.defaults[environment] ?? form.data.base_url)
                    : form.data.base_url,
        });
    };

    const submit = () => {
        const settings = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(`${URL}/${editing.id}`, settings);
        } else {
            form.post(URL, settings);
        }
    };

    // Prueba las credenciales del formulario sin cerrar la ventana.
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
                <Badge
                    variant={row.environment === 'production' ? 'default' : 'secondary'}
                >
                    {row.environment === 'production' ? 'Producción' : 'Pruebas'}
                </Badge>
            ),
        },
        {
            key: 'username',
            header: 'Usuario',
            sortable: true,
            cell: (row) => (
                <div className="flex flex-col">
                    <span>{row.username}</span>
                    <span className="text-xs text-muted-foreground">
                        {row.api_version.toUpperCase()}
                    </span>
                </div>
            ),
        },
        {
            key: 'token',
            header: 'Conexión',
            cell: (row) => (
                <Badge variant={tokenLabel[row.token_status].variant}>
                    {tokenLabel[row.token_status].text}
                </Badge>
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
                        <DropdownMenuItem onSelect={() => openEdit(row)}>
                            Editar
                        </DropdownMenuItem>
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
            <Head title="Factus" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">Factus</h1>
                        <p className="text-sm text-muted-foreground">
                            Credenciales de facturación electrónica por empresa y
                            ambiente. Solo una configuración activa por ambiente.
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
                    searchPlaceholder="Buscar por empresa, usuario o client id…"
                    emptyText="Aún no hay configuraciones de Factus."
                />
            </div>

            <FormDialog
                open={open}
                onOpenChange={setOpen}
                title={editing ? 'Editar configuración' : 'Nueva configuración'}
                description="Las claves se guardan cifradas y no se vuelven a mostrar."
                processing={form.processing}
                onSubmit={submit}
                wide
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

                    <Field label="Ambiente" error={form.errors.environment}>
                        <Select
                            value={form.data.environment}
                            onValueChange={(value) =>
                                changeEnvironment(value as 'sandbox' | 'production')
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

                <div className="grid gap-4 sm:grid-cols-3">
                    <Field label="URL de Factus" htmlFor="base_url" error={form.errors.base_url}
                        className="sm:col-span-2">
                        <Input id="base_url" value={form.data.base_url}
                            onChange={(e) => form.setData('base_url', e.target.value)} />
                    </Field>
                    <Field label="Versión" error={form.errors.api_version}>
                        <Select
                            value={form.data.api_version}
                            onValueChange={(value) => form.setData('api_version', value)}
                        >
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="v1">V1</SelectItem>
                                <SelectItem value="v2">V2</SelectItem>
                            </SelectContent>
                        </Select>
                    </Field>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Client ID" htmlFor="client_id" error={form.errors.client_id}>
                        <Input id="client_id" value={form.data.client_id} autoComplete="off"
                            onChange={(e) => form.setData('client_id', e.target.value)} />
                    </Field>
                    <Field label="Client secret" htmlFor="client_secret" error={form.errors.client_secret}
                        hint={editing ? 'Déjalo vacío para conservar el guardado.' : undefined}>
                        <PasswordInput id="client_secret" value={form.data.client_secret}
                            autoComplete="off"
                            onChange={(e) => form.setData('client_secret', e.target.value)} />
                    </Field>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Usuario" htmlFor="username" error={form.errors.username}>
                        <Input id="username" value={form.data.username} autoComplete="off"
                            onChange={(e) => form.setData('username', e.target.value)} />
                    </Field>
                    <Field label="Contraseña" htmlFor="password" error={form.errors.password}
                        hint={editing ? 'Déjala vacía para conservar la guardada.' : undefined}>
                        <PasswordInput id="password" value={form.data.password}
                            autoComplete="new-password"
                            onChange={(e) => form.setData('password', e.target.value)} />
                    </Field>
                </div>

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <Checkbox id="active" checked={form.data.active}
                            onCheckedChange={(v) => form.setData('active', v === true)} />
                        <Label htmlFor="active" className="font-normal">
                            Activa (desactiva la otra de este ambiente)
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

Factus.layout = {
    breadcrumbs: [
        { title: 'Soporte', href: '/soporte/empresas' },
        { title: 'Factus', href: '/soporte/factus' },
    ],
};
