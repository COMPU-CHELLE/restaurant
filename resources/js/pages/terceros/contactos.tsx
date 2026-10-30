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
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
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
import { usePermissions } from '@/hooks/use-permissions';
import type { Paginated, TableFilters } from '@/types/pagination';

type ContactRow = {
    id: number;
    code: string;
    document_type: string;
    document_number: string;
    business_name: string | null;
    first_name: string | null;
    last_name: string | null;
    full_name: string;
    email: string | null;
    phone: string | null;
    mobile: string | null;
    address: string | null;
    customer: boolean;
    supplier: boolean;
    state: number;
};

type Props = {
    contacts: Paginated<ContactRow>;
    filters: TableFilters & { kind?: string | null };
    options: { documentTypes: { code: string; name: string }[] };
};

type ContactForm = {
    document_type: string;
    document_number: string;
    business_name: string;
    first_name: string;
    last_name: string;
    email: string;
    phone: string;
    mobile: string;
    address: string;
    customer: boolean;
    supplier: boolean;
};

const URL = '/terceros/contactos';

export default function Contactos({ contacts, filters, options }: Props) {
    const { can } = usePermissions();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<ContactRow | null>(null);
    const [deleting, setDeleting] = useState<ContactRow | null>(null);

    const blank = (): ContactForm => ({
        document_type: options.documentTypes.find((type) => type.code === '13')?.code ??
            options.documentTypes[0]?.code ?? '',
        document_number: '',
        business_name: '',
        first_name: '',
        last_name: '',
        email: '',
        phone: '',
        mobile: '',
        address: '',
        customer: true,
        supplier: false,
    });

    const form = useForm<ContactForm>(blank());

    const openCreate = () => {
        setEditing(null);
        form.setData(blank());
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (row: ContactRow) => {
        setEditing(row);
        form.setData({
            document_type: row.document_type,
            document_number: row.document_number,
            business_name: row.business_name ?? '',
            first_name: row.first_name ?? '',
            last_name: row.last_name ?? '',
            email: row.email ?? '',
            phone: row.phone ?? '',
            mobile: row.mobile ?? '',
            address: row.address ?? '',
            customer: row.customer,
            supplier: row.supplier,
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

    const refilter = (patch: Record<string, string | null>) => {
        const next: Record<string, string | number> = {};

        if (filters.search) next.search = filters.search;
        if (filters.state) next.state = filters.state;
        if (filters.kind) next.kind = filters.kind;
        if (filters.per_page) next.per_page = filters.per_page;

        Object.entries(patch).forEach(([key, value]) => {
            if (value === null) {
                delete next[key];
            } else {
                next[key] = value;
            }
        });

        router.get(URL, next, { preserveState: true, replace: true });
    };

    const typeName = (code: string) =>
        options.documentTypes.find((type) => type.code === code)?.name ?? code;

    const columns: Column<ContactRow>[] = [
        {
            key: 'code',
            header: 'Código',
            sortable: true,
            cell: (row) => <span className="font-mono text-xs">{row.code}</span>,
        },
        {
            key: 'business_name',
            header: 'Tercero',
            sortable: true,
            cell: (row) => (
                <div className="flex flex-col">
                    <span className="font-medium">{row.full_name}</span>
                    <span className="text-xs text-muted-foreground">
                        {typeName(row.document_type)} {row.document_number}
                    </span>
                </div>
            ),
        },
        {
            key: 'contact',
            header: 'Contacto',
            cell: (row) => (
                <div className="flex flex-col text-xs">
                    <span>{row.email ?? '—'}</span>
                    <span className="text-muted-foreground">{row.mobile || row.phone || ''}</span>
                </div>
            ),
        },
        {
            key: 'kind',
            header: 'Tipo',
            cell: (row) => (
                <div className="flex gap-1">
                    {row.customer && <Badge variant="secondary">Cliente</Badge>}
                    {row.supplier && <Badge variant="outline">Proveedor</Badge>}
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
                        {can('terceros.contactos.update') && (
                            <DropdownMenuItem onSelect={() => openEdit(row)}>Editar</DropdownMenuItem>
                        )}
                        {can('terceros.contactos.toggle') && (
                            <DropdownMenuItem
                                onSelect={() =>
                                    router.patch(`${URL}/${row.id}/toggle`, {}, { preserveScroll: true })
                                }
                            >
                                {row.state === 1 ? 'Inactivar' : 'Activar'}
                            </DropdownMenuItem>
                        )}
                        {can('terceros.contactos.delete') && (
                            <>
                                <DropdownMenuSeparator />
                                <DropdownMenuItem className="text-red-600 focus:text-red-600"
                                    onSelect={() => setDeleting(row)}>
                                    Eliminar
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
            <Head title="Clientes y proveedores" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">Clientes y proveedores</h1>
                        <p className="text-sm text-muted-foreground">
                            Terceros con los que trabaja el restaurante.
                        </p>
                    </div>
                    {can('terceros.contactos.store') && (
                        <Button onClick={openCreate}>
                            <Plus /> Nuevo tercero
                        </Button>
                    )}
                </div>

                <DataTable
                    url={URL}
                    data={contacts}
                    filters={filters}
                    columns={columns}
                    rowKey={(row) => row.id}
                    searchPlaceholder="Buscar por nombre, documento, correo o teléfono…"
                    emptyText="Aún no hay terceros. Crea el primero."
                    toolbar={
                        <div className="flex flex-wrap items-center gap-2">
                            <Select value={filters.kind ?? 'all'}
                                onValueChange={(value) => refilter({ kind: value === 'all' ? null : value })}>
                                <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
                                    <SelectItem value="customer">Clientes</SelectItem>
                                    <SelectItem value="supplier">Proveedores</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select value={filters.state ?? 'all'}
                                onValueChange={(value) => refilter({ state: value === 'all' ? null : value })}>
                                <SelectTrigger className="w-36"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos</SelectItem>
                                    <SelectItem value="1">Activos</SelectItem>
                                    <SelectItem value="0">Inactivos</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    }
                />
            </div>

            <FormDialog
                open={open}
                onOpenChange={setOpen}
                title={editing ? `Editar tercero · ${editing.code}` : 'Nuevo tercero'}
                processing={form.processing}
                onSubmit={submit}
                wide
            >
                {options.documentTypes.length === 0 && (
                    <p className="rounded-md border bg-muted/40 p-3 text-sm text-muted-foreground">
                        Aún no has importado los tipos de documento. Hazlo en Administración → Parámetros →
                        Clases de documento → Importar desde soporte.
                    </p>
                )}

                <div className="grid gap-4 sm:grid-cols-3">
                    <Field label="Tipo de documento" error={form.errors.document_type}>
                        {options.documentTypes.length > 0 ? (
                            <Select value={form.data.document_type || undefined}
                                onValueChange={(value) => form.setData('document_type', value)}>
                                <SelectTrigger><SelectValue placeholder="Elige" /></SelectTrigger>
                                <SelectContent>
                                    {options.documentTypes.map((type) => (
                                        <SelectItem key={type.code} value={type.code}>
                                            {type.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        ) : (
                            <Input value={form.data.document_type} maxLength={10}
                                onChange={(e) => form.setData('document_type', e.target.value)} />
                        )}
                    </Field>
                    <Field label="Número de documento" htmlFor="document_number" error={form.errors.document_number}
                        className="sm:col-span-2">
                        <Input id="document_number" value={form.data.document_number} autoFocus
                            onChange={(e) => form.setData('document_number', e.target.value)} />
                    </Field>
                </div>

                <Field label="Razón social" htmlFor="business_name" error={form.errors.business_name}
                    hint="Para empresas. Si es una persona, deja este campo vacío y escribe su nombre.">
                    <Input id="business_name" value={form.data.business_name}
                        onChange={(e) => form.setData('business_name', e.target.value)} />
                </Field>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label="Nombres" htmlFor="first_name" error={form.errors.first_name}>
                        <Input id="first_name" value={form.data.first_name}
                            onChange={(e) => form.setData('first_name', e.target.value)} />
                    </Field>
                    <Field label="Apellidos" htmlFor="last_name" error={form.errors.last_name}>
                        <Input id="last_name" value={form.data.last_name}
                            onChange={(e) => form.setData('last_name', e.target.value)} />
                    </Field>
                </div>

                <div className="grid gap-4 sm:grid-cols-3">
                    <Field label="Correo" htmlFor="email" error={form.errors.email}>
                        <Input id="email" type="email" value={form.data.email}
                            onChange={(e) => form.setData('email', e.target.value)} />
                    </Field>
                    <Field label="Teléfono" htmlFor="phone" error={form.errors.phone}>
                        <Input id="phone" value={form.data.phone}
                            onChange={(e) => form.setData('phone', e.target.value)} />
                    </Field>
                    <Field label="Celular" htmlFor="mobile" error={form.errors.mobile}>
                        <Input id="mobile" value={form.data.mobile}
                            onChange={(e) => form.setData('mobile', e.target.value)} />
                    </Field>
                </div>

                <Field label="Dirección" htmlFor="address" error={form.errors.address}>
                    <Input id="address" value={form.data.address}
                        onChange={(e) => form.setData('address', e.target.value)} />
                </Field>

                <div className="flex flex-wrap gap-6">
                    <div className="flex items-center gap-2">
                        <Checkbox id="customer" checked={form.data.customer}
                            onCheckedChange={(v) => form.setData('customer', v === true)} />
                        <Label htmlFor="customer" className="font-normal">Cliente</Label>
                    </div>
                    <div className="flex items-center gap-2">
                        <Checkbox id="supplier" checked={form.data.supplier}
                            onCheckedChange={(v) => form.setData('supplier', v === true)} />
                        <Label htmlFor="supplier" className="font-normal">Proveedor</Label>
                    </div>
                </div>
                {form.errors.customer && (
                    <p className="text-sm text-red-600 dark:text-red-400">{form.errors.customer}</p>
                )}
            </FormDialog>

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(value) => !value && setDeleting(null)}
                title="Eliminar tercero"
                description={`¿Eliminar a “${deleting?.full_name ?? ''}”? Si tiene ventas, solo puedes inactivarlo.`}
                confirmLabel="Eliminar"
                destructive
                onConfirm={() =>
                    deleting && router.delete(`${URL}/${deleting.id}`, { preserveScroll: true })
                }
            />
        </>
    );
}

Contactos.layout = {
    breadcrumbs: [
        { title: 'Terceros', href: '/terceros/contactos' },
        { title: 'Clientes y proveedores', href: '/terceros/contactos' },
    ],
};
