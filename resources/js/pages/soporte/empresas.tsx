import { Head, router, useForm } from '@inertiajs/react';
import { MoreHorizontal, Plus } from 'lucide-react';
import { useEffect, useState } from 'react';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { Field } from '@/components/field';
import { FormDialog } from '@/components/form-dialog';
import { ProvisionBadge, StateBadge } from '@/components/status-badge';
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

type CompanyRow = {
    id: number;
    code: string;
    name: string;
    nit: string;
    dv: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    country: string;
    database: string | null;
    state: number;
    provision_status: string;
    provision_step: string | null;
    provision_error: string | null;
    provisioned_at: string | null;
};

type Props = {
    companies: Paginated<CompanyRow>;
    filters: TableFilters;
};

type CompanyForm = {
    code: string;
    empnom: string;
    empnit: string;
    empdig: string;
    empadd: string;
    emppho: string;
    empema: string;
    country_code: string;
};

const emptyForm: CompanyForm = {
    code: '',
    empnom: '',
    empnit: '',
    empdig: '',
    empadd: '',
    emppho: '',
    empema: '',
    country_code: 'CO',
};

const URL = '/soporte/empresas';

export default function Empresas({ companies, filters }: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<CompanyRow | null>(null);
    const form = useForm<CompanyForm>(emptyForm);

    // Mientras alguna empresa se aprovisiona, refresca la lista cada 3 segundos.
    const busy = companies.data.some((company) =>
        ['queued', 'running'].includes(company.provision_status),
    );

    useEffect(() => {
        if (!busy) {
            return;
        }

        const timer = setInterval(
            () => router.reload({ only: ['companies'] }),
            3000,
        );

        return () => clearInterval(timer);
    }, [busy]);

    const openCreate = () => {
        setEditing(null);
        form.setData(emptyForm);
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (company: CompanyRow) => {
        setEditing(company);
        form.setData({
            code: company.code,
            empnom: company.name,
            empnit: company.nit,
            empdig: company.dv ?? '',
            empadd: company.address ?? '',
            emppho: company.phone ?? '',
            empema: company.email ?? '',
            country_code: company.country,
        });
        form.clearErrors();
        setOpen(true);
    };

    const submit = () => {
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

    const act = (path: string, method: 'patch' | 'post', data = {}) =>
        router[method](`${URL}/${path}`, data, { preserveScroll: true });

    const columns: Column<CompanyRow>[] = [
        {
            key: 'code',
            header: 'Código',
            sortable: true,
            cell: (row) => <span className="font-mono text-xs">{row.code}</span>,
        },
        {
            key: 'empnom',
            header: 'Empresa',
            sortable: true,
            cell: (row) => (
                <div className="flex flex-col">
                    <span className="font-medium">{row.name}</span>
                    <span className="text-xs text-muted-foreground">
                        NIT {row.nit}
                        {row.dv ? `-${row.dv}` : ''}
                    </span>
                </div>
            ),
        },
        {
            key: 'database',
            header: 'Base de datos',
            cell: (row) =>
                row.database ? (
                    <span className="font-mono text-xs">{row.database}</span>
                ) : (
                    <span className="text-muted-foreground">—</span>
                ),
        },
        {
            key: 'provision',
            header: 'Aprovisionamiento',
            cell: (row) => (
                <div className="flex max-w-56 flex-col gap-1">
                    <ProvisionBadge status={row.provision_status} />
                    {row.provision_status === 'running' && row.provision_step && (
                        <span className="text-xs text-muted-foreground">
                            Etapa: {row.provision_step}
                        </span>
                    )}
                    {row.provision_status === 'failed' && row.provision_error && (
                        <span
                            className="truncate text-xs text-red-600 dark:text-red-400"
                            title={row.provision_error}
                        >
                            {row.provision_error}
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
            cell: (row) => {
                const canProvision = ['pending', 'failed'].includes(
                    row.provision_status,
                );

                return (
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
                                onSelect={() => act(`${row.id}/toggle`, 'patch')}
                            >
                                {row.state === 1 ? 'Inactivar' : 'Activar'}
                            </DropdownMenuItem>
                            {canProvision && (
                                <>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        onSelect={() =>
                                            act(`${row.id}/provision`, 'post')
                                        }
                                    >
                                        {row.provision_status === 'failed'
                                            ? 'Reintentar aprovisionamiento'
                                            : 'Aprovisionar'}
                                    </DropdownMenuItem>
                                    {row.provision_status === 'failed' && (
                                        <DropdownMenuItem
                                            onSelect={() =>
                                                act(`${row.id}/provision`, 'post', {
                                                    adopt: true,
                                                })
                                            }
                                        >
                                            Reintentar usando la base existente
                                        </DropdownMenuItem>
                                    )}
                                </>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                );
            },
        },
    ];

    return (
        <>
            <Head title="Empresas" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">Empresas</h1>
                        <p className="text-sm text-muted-foreground">
                            Restaurantes de la plataforma. Cada uno tiene su
                            propia base de datos.
                        </p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus /> Nueva empresa
                    </Button>
                </div>

                <DataTable
                    url={URL}
                    data={companies}
                    filters={filters}
                    columns={columns}
                    rowKey={(row) => row.id}
                    searchPlaceholder="Buscar por código, nombre o NIT…"
                    emptyText="Aún no hay empresas. Crea la primera."
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
                                <SelectItem value="all">Todas</SelectItem>
                                <SelectItem value="1">Activas</SelectItem>
                                <SelectItem value="0">Inactivas</SelectItem>
                            </SelectContent>
                        </Select>
                    }
                />
            </div>

            <FormDialog
                open={open}
                onOpenChange={setOpen}
                title={editing ? 'Editar empresa' : 'Nueva empresa'}
                description={
                    editing
                        ? 'La base de datos no se modifica desde aquí.'
                        : 'Después de crearla, aprovisiónala para generar su base de datos.'
                }
                processing={form.processing}
                onSubmit={submit}
                wide
            >
                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Código" htmlFor="code" error={form.errors.code}
                        hint="Letras mayúsculas y números. Ej.: REST001">
                        <Input id="code" value={form.data.code} autoFocus
                            onChange={(e) => form.setData('code', e.target.value.toUpperCase())} />
                    </Field>
                    <Field label="País" htmlFor="country_code" error={form.errors.country_code}
                        hint="Código de 2 letras. Ej.: CO">
                        <Input id="country_code" maxLength={2} value={form.data.country_code}
                            onChange={(e) => form.setData('country_code', e.target.value.toUpperCase())} />
                    </Field>
                </div>

                <Field label="Nombre" htmlFor="empnom" error={form.errors.empnom}>
                    <Input id="empnom" value={form.data.empnom}
                        onChange={(e) => form.setData('empnom', e.target.value)} />
                </Field>

                <div className="grid gap-4 sm:grid-cols-3">
                    <Field label="NIT" htmlFor="empnit" error={form.errors.empnit} className="sm:col-span-2">
                        <Input id="empnit" value={form.data.empnit}
                            onChange={(e) => form.setData('empnit', e.target.value)} />
                    </Field>
                    <Field label="Dígito" htmlFor="empdig" error={form.errors.empdig}>
                        <Input id="empdig" maxLength={2} value={form.data.empdig}
                            onChange={(e) => form.setData('empdig', e.target.value)} />
                    </Field>
                </div>

                <Field label="Dirección" htmlFor="empadd" error={form.errors.empadd}>
                    <Input id="empadd" value={form.data.empadd}
                        onChange={(e) => form.setData('empadd', e.target.value)} />
                </Field>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Teléfono" htmlFor="emppho" error={form.errors.emppho}>
                        <Input id="emppho" value={form.data.emppho}
                            onChange={(e) => form.setData('emppho', e.target.value)} />
                    </Field>
                    <Field label="Correo" htmlFor="empema" error={form.errors.empema}>
                        <Input id="empema" type="email" value={form.data.empema}
                            onChange={(e) => form.setData('empema', e.target.value)} />
                    </Field>
                </div>
            </FormDialog>
        </>
    );
}

Empresas.layout = {
    breadcrumbs: [
        { title: 'Soporte', href: '/soporte/empresas' },
        { title: 'Empresas', href: '/soporte/empresas' },
    ],
};
