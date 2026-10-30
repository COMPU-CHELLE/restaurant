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

type PrinterRow = {
    id: number;
    code: string;
    name: string;
    detail: string | null;
    printer_type: string;
    connection_type: string;
    ip_address: string | null;
    port: number | null;
    paper_width: string;
    purpose: string;
    state: number;
    registers: number;
};

type Option = { value: string; label: string };

type Props = {
    printers: Paginated<PrinterRow>;
    filters: TableFilters;
    options: { types: Option[]; connections: Option[]; paper: Option[]; purposes: Option[] };
};

type PrinterForm = {
    code: string;
    name: string;
    detail: string;
    printer_type: string;
    connection_type: string;
    ip_address: string;
    port: string;
    paper_width: string;
    purpose: string;
};

const URL = '/admin/impresiones/impresoras';
const emptyForm: PrinterForm = {
    code: '',
    name: '',
    detail: '',
    printer_type: 'THERMAL',
    connection_type: 'NETWORK',
    ip_address: '',
    port: '9100',
    paper_width: 'MM80',
    purpose: 'KITCHEN',
};

export default function Impresoras({ printers, filters, options }: Props) {
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<PrinterRow | null>(null);
    const [deleting, setDeleting] = useState<PrinterRow | null>(null);
    const form = useForm<PrinterForm>(emptyForm);

    const label = (list: Option[], value: string) =>
        list.find((item) => item.value === value)?.label ?? value;

    const openCreate = () => {
        setEditing(null);
        form.setData(emptyForm);
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (row: PrinterRow) => {
        setEditing(row);
        form.setData({
            code: row.code,
            name: row.name,
            detail: row.detail ?? '',
            printer_type: row.printer_type,
            connection_type: row.connection_type,
            ip_address: row.ip_address ?? '',
            port: row.port ? String(row.port) : '',
            paper_width: row.paper_width,
            purpose: row.purpose,
        });
        form.clearErrors();
        setOpen(true);
    };

    const submit = () => {
        // Sin conexión de red no se envían IP ni puerto.
        form.transform((data) =>
            data.connection_type === 'NETWORK' ? data : { ...data, ip_address: '', port: '' },
        );

        const settings = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(`${URL}/${editing.id}`, settings);
        } else {
            form.post(URL, settings);
        }
    };

    const select = (
        id: keyof PrinterForm,
        text: string,
        list: Option[],
    ) => (
        <Field label={text} error={form.errors[id]}>
            <Select value={form.data[id]} onValueChange={(value) => form.setData(id, value)}>
                <SelectTrigger><SelectValue /></SelectTrigger>
                <SelectContent>
                    {list.map((item) => (
                        <SelectItem key={item.value} value={item.value}>{item.label}</SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </Field>
    );

    const columns: Column<PrinterRow>[] = [
        {
            key: 'code',
            header: 'Código',
            sortable: true,
            cell: (row) => <span className="font-mono text-xs">{row.code}</span>,
        },
        {
            key: 'name',
            header: 'Impresora',
            sortable: true,
            cell: (row) => (
                <div className="flex flex-col">
                    <span className="font-medium">{row.name}</span>
                    <span className="text-xs text-muted-foreground">
                        {label(options.types, row.printer_type)} · {label(options.paper, row.paper_width)}
                    </span>
                </div>
            ),
        },
        {
            key: 'purpose',
            header: 'Uso',
            sortable: true,
            cell: (row) => <Badge variant="secondary">{label(options.purposes, row.purpose)}</Badge>,
        },
        {
            key: 'connection',
            header: 'Conexión',
            cell: (row) => (
                <div className="flex flex-col text-xs">
                    <span>{label(options.connections, row.connection_type)}</span>
                    {row.ip_address && (
                        <span className="font-mono text-muted-foreground">
                            {row.ip_address}:{row.port}
                        </span>
                    )}
                </div>
            ),
        },
        {
            key: 'registers',
            header: 'Cajas',
            cell: (row) => <Badge variant="outline">{row.registers}</Badge>,
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
            <Head title="Impresoras" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">Impresoras</h1>
                        <p className="text-sm text-muted-foreground">
                            Impresoras de comandas (cocina y barra) y de recibos de caja.
                        </p>
                    </div>
                    <Button onClick={openCreate}>
                        <Plus /> Nueva impresora
                    </Button>
                </div>

                <DataTable
                    url={URL}
                    data={printers}
                    filters={filters}
                    columns={columns}
                    rowKey={(row) => row.id}
                    searchPlaceholder="Buscar por código, nombre o IP…"
                    emptyText="Aún no hay impresoras. Crea la primera."
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
                title={editing ? 'Editar impresora' : 'Nueva impresora'}
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
                    {select('purpose', 'Uso', options.purposes)}
                    {select('printer_type', 'Tipo', options.types)}
                    {select('paper_width', 'Papel', options.paper)}
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    {select('connection_type', 'Conexión', options.connections)}
                    {form.data.connection_type === 'NETWORK' && (
                        <>
                            <Field label="Dirección IP" htmlFor="ip_address" error={form.errors.ip_address}>
                                <Input id="ip_address" value={form.data.ip_address} placeholder="192.168.1.50"
                                    onChange={(e) => form.setData('ip_address', e.target.value)} />
                            </Field>
                            <Field label="Puerto" htmlFor="port" error={form.errors.port}>
                                <Input id="port" type="number" min={1} max={65535} value={form.data.port}
                                    onChange={(e) => form.setData('port', e.target.value)} />
                            </Field>
                        </>
                    )}
                </div>

                <Field label="Descripción" htmlFor="detail" error={form.errors.detail}>
                    <Input id="detail" value={form.data.detail}
                        onChange={(e) => form.setData('detail', e.target.value)} />
                </Field>
            </FormDialog>

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(value) => !value && setDeleting(null)}
                title="Eliminar impresora"
                description={`¿Eliminar “${deleting?.name ?? ''}”? Si está asignada a una caja, primero quítala de allí.`}
                confirmLabel="Eliminar"
                destructive
                onConfirm={() =>
                    deleting && router.delete(`${URL}/${deleting.id}`, { preserveScroll: true })
                }
            />
        </>
    );
}

Impresoras.layout = {
    breadcrumbs: [
        { title: 'Impresiones', href: '/admin/impresiones/impresoras' },
        { title: 'Impresoras', href: '/admin/impresiones/impresoras' },
    ],
};
