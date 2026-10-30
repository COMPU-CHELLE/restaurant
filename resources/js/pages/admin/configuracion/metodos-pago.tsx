import { Head, router, useForm } from '@inertiajs/react';
import { MoreHorizontal, Plus } from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { Field } from '@/components/field';
import { FormDialog } from '@/components/form-dialog';
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

type MethodRow = {
    id: number;
    code: string;
    name: string;
    detail: string | null;
    surcharge_percentage: number;
    payment_means_code: string | null;
    payment_form_code: string | null;
    state: number;
};

type Dian = { code: string; name: string }[];

type Props = {
    methods: Paginated<MethodRow>;
    filters: TableFilters;
    options: { means: Dian; forms: Dian };
};

type MethodForm = {
    code: string;
    name: string;
    detail: string;
    surcharge_percentage: string;
    payment_means_code: string;
    payment_form_code: string;
};

const URL = '/admin/configuracion/metodos-pago';
const NONE = '__none__';
const emptyForm: MethodForm = {
    code: '',
    name: '',
    detail: '',
    surcharge_percentage: '0',
    payment_means_code: '',
    payment_form_code: '1',
};

export default function MetodosPago({ methods, filters, options }: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<MethodRow | null>(null);
    const [deleting, setDeleting] = useState<MethodRow | null>(null);
    const form = useForm<MethodForm>(emptyForm);

    const nameOf = (list: Dian, code: string | null) =>
        code ? (list.find((item) => item.code === code)?.name ?? code) : null;

    const openCreate = () => {
        setEditing(null);
        form.setData(emptyForm);
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (row: MethodRow) => {
        setEditing(row);
        form.setData({
            code: row.code,
            name: row.name,
            detail: row.detail ?? '',
            surcharge_percentage: String(row.surcharge_percentage),
            payment_means_code: row.payment_means_code ?? '',
            payment_form_code: row.payment_form_code ?? '1',
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

    const columns: Column<MethodRow>[] = [
        {
            key: 'code',
            header: 'Código',
            sortable: true,
            cell: (row) => <span className="font-mono text-xs">{row.code}</span>,
        },
        {
            key: 'name',
            header: 'Método de pago',
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
            key: 'surcharge_percentage',
            header: 'Recargo',
            sortable: true,
            cell: (row) =>
                row.surcharge_percentage > 0 ? (
                    <Badge variant="secondary">{row.surcharge_percentage}%</Badge>
                ) : (
                    <span className="text-xs text-muted-foreground">Sin recargo</span>
                ),
        },
        {
            key: 'dian',
            header: 'Factura electrónica',
            cell: (row) => (
                <div className="flex flex-col text-xs">
                    <span>{nameOf(options.means, row.payment_means_code) ?? '—'}</span>
                    <span className="text-muted-foreground">
                        {nameOf(options.forms, row.payment_form_code) ?? ''}
                    </span>
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
                        <DropdownMenuItem onSelect={() => openEdit(row)}>Editar</DropdownMenuItem>
                        <DropdownMenuItem
                            onSelect={() =>
                                router.patch(`${URL}/${row.id}/toggle`, {}, { preserveScroll: true })
                            }
                        >
                            {row.state === 1 ? 'Inactivar' : 'Activar'}
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem className="text-red-600 focus:text-red-600"
                            onSelect={() => setDeleting(row)}>
                            Eliminar
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            ),
        },
    ];

    const dianField = (
        label: string,
        key: 'payment_means_code' | 'payment_form_code',
        list: Dian,
        required: boolean,
    ) => (
        <Field label={label} error={form.errors[key]}>
            {list.length > 0 ? (
                <Select value={form.data[key] || NONE}
                    onValueChange={(value) => form.setData(key, value === NONE ? '' : value)}>
                    <SelectTrigger><SelectValue /></SelectTrigger>
                    <SelectContent>
                        {!required && <SelectItem value={NONE}>— Sin definir —</SelectItem>}
                        {list.map((item) => (
                            <SelectItem key={item.code} value={item.code}>
                                {item.code} · {item.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            ) : (
                <Input value={form.data[key]} maxLength={10}
                    onChange={(e) => form.setData(key, e.target.value)} />
            )}
        </Field>
    );

    return (
        <>
            <Head title="Métodos de pago" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">Métodos de pago</h1>
                        <p className="text-sm text-muted-foreground">
                            Cómo paga el cliente. El recargo (por ejemplo, el de datáfono) se suma a la cuenta.
                        </p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus /> Nuevo método
                    </Button>
                </div>

                <DataTable
                    url={URL}
                    data={methods}
                    filters={filters}
                    columns={columns}
                    rowKey={(row) => row.id}
                    searchPlaceholder="Buscar por código o nombre…"
                    emptyText="Aún no hay métodos de pago. Crea el primero (por ejemplo, Efectivo)."
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
                title={editing ? 'Editar método de pago' : 'Nuevo método de pago'}
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

                <div className="grid gap-4 sm:grid-cols-3">
                    <Field label="Recargo (%)" htmlFor="surcharge" error={form.errors.surcharge_percentage}
                        hint="0 = sin recargo">
                        <Input id="surcharge" type="number" min={0} max={100} step="0.01"
                            value={form.data.surcharge_percentage}
                            onChange={(e) => form.setData('surcharge_percentage', e.target.value)} />
                    </Field>
                    <div className="sm:col-span-2">
                        <Field label="Descripción" htmlFor="detail" error={form.errors.detail}>
                            <Input id="detail" value={form.data.detail}
                                onChange={(e) => form.setData('detail', e.target.value)} />
                        </Field>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                    {dianField('Medio de pago (DIAN)', 'payment_means_code', options.means, false)}
                    {dianField('Forma de pago (DIAN)', 'payment_form_code', options.forms, true)}
                </div>

                {(options.means.length === 0 || options.forms.length === 0) && (
                    <p className="text-xs text-muted-foreground">
                        Para elegir de una lista, importa «Medios de pago» y «Formas de pago» en Administración → Parámetros.
                    </p>
                )}
            </FormDialog>

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(value) => !value && setDeleting(null)}
                title="Eliminar método de pago"
                description={`¿Eliminar “${deleting?.name ?? ''}”? Si ya hay pagos con este método, solo puedes inactivarlo.`}
                confirmLabel="Eliminar"
                destructive
                onConfirm={() =>
                    deleting && router.delete(`${URL}/${deleting.id}`, { preserveScroll: true })
                }
            />
        </>
    );
}

MetodosPago.layout = {
    breadcrumbs: [
        { title: 'Configuración', href: '/admin/configuracion/metodos-pago' },
        { title: 'Métodos de pago', href: '/admin/configuracion/metodos-pago' },
    ],
};
