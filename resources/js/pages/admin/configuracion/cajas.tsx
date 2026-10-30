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

type RegisterRow = {
    id: number;
    code: string;
    name: string;
    detail: string | null;
    location: string | null;
    printer_id: number | null;
    printer: string | null;
    state: number;
    open_by: string | null;
};

type Props = {
    registers: Paginated<RegisterRow>;
    filters: TableFilters;
    options: { printers: { id: number; code: string; name: string; purpose: string }[] };
};

type RegisterForm = {
    code: string;
    name: string;
    detail: string;
    location: string;
    printer_id: string;
};

const URL = '/admin/configuracion/cajas';
const NONE = '__none__';
const emptyForm: RegisterForm = { code: '', name: '', detail: '', location: '', printer_id: '' };

export default function Cajas({ registers, filters, options }: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<RegisterRow | null>(null);
    const [deleting, setDeleting] = useState<RegisterRow | null>(null);
    const form = useForm<RegisterForm>(emptyForm);

    const openCreate = () => {
        setEditing(null);
        form.setData(emptyForm);
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (row: RegisterRow) => {
        setEditing(row);
        form.setData({
            code: row.code,
            name: row.name,
            detail: row.detail ?? '',
            location: row.location ?? '',
            printer_id: row.printer_id ? String(row.printer_id) : '',
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

    const columns: Column<RegisterRow>[] = [
        {
            key: 'code',
            header: 'Código',
            sortable: true,
            cell: (row) => <span className="font-mono text-xs">{row.code}</span>,
        },
        {
            key: 'name',
            header: 'Caja',
            sortable: true,
            cell: (row) => (
                <div className="flex flex-col">
                    <span className="font-medium">{row.name}</span>
                    <span className="text-xs text-muted-foreground">
                        {row.location ?? row.detail ?? ''}
                    </span>
                </div>
            ),
        },
        {
            key: 'printer',
            header: 'Impresora de recibos',
            cell: (row) => row.printer ?? <span className="text-muted-foreground">—</span>,
        },
        {
            key: 'session',
            header: 'Sesión',
            cell: (row) =>
                row.open_by ? (
                    <Badge className="bg-emerald-600">Abierta · {row.open_by}</Badge>
                ) : (
                    <span className="text-xs text-muted-foreground">Cerrada</span>
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

    return (
        <>
            <Head title="Cajas registradoras" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">Cajas registradoras</h1>
                        <p className="text-sm text-muted-foreground">
                            Puntos de cobro del restaurante. Cada caja tiene una sola sesión abierta a la vez.
                        </p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus /> Nueva caja
                    </Button>
                </div>

                <DataTable
                    url={URL}
                    data={registers}
                    filters={filters}
                    columns={columns}
                    rowKey={(row) => row.id}
                    searchPlaceholder="Buscar por código, nombre o ubicación…"
                    emptyText="Aún no hay cajas. Crea la primera."
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
                title={editing ? 'Editar caja' : 'Nueva caja'}
                processing={form.processing}
                onSubmit={submit}
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

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Ubicación" htmlFor="location" error={form.errors.location}>
                        <Input id="location" value={form.data.location}
                            onChange={(e) => form.setData('location', e.target.value)} />
                    </Field>
                    <Field label="Impresora de recibos" error={form.errors.printer_id}>
                        <Select value={form.data.printer_id || NONE}
                            onValueChange={(value) => form.setData('printer_id', value === NONE ? '' : value)}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NONE}>— Ninguna —</SelectItem>
                                {options.printers.map((printer) => (
                                    <SelectItem key={printer.id} value={String(printer.id)}>
                                        {printer.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                </div>

                <Field label="Descripción" htmlFor="detail" error={form.errors.detail}>
                    <Input id="detail" value={form.data.detail}
                        onChange={(e) => form.setData('detail', e.target.value)} />
                </Field>
            </FormDialog>

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(value) => !value && setDeleting(null)}
                title="Eliminar caja"
                description={`¿Eliminar “${deleting?.name ?? ''}”? Si ya tiene sesiones, solo puedes inactivarla.`}
                confirmLabel="Eliminar"
                destructive
                onConfirm={() =>
                    deleting && router.delete(`${URL}/${deleting.id}`, { preserveScroll: true })
                }
            />
        </>
    );
}

Cajas.layout = {
    breadcrumbs: [
        { title: 'Configuración', href: '/admin/configuracion/cajas' },
        { title: 'Cajas registradoras', href: '/admin/configuracion/cajas' },
    ],
};
