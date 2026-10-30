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
import { usePermissions } from '@/hooks/use-permissions';
import type { Paginated, TableFilters } from '@/types/pagination';

type CategoryRow = {
    id: number;
    code: string;
    name: string;
    detail: string | null;
    state: number;
    products_count: number;
};

type Props = {
    categories: Paginated<CategoryRow>;
    filters: TableFilters;
};

type CategoryForm = { code: string; name: string; detail: string };

const emptyForm: CategoryForm = { code: '', name: '', detail: '' };
const URL = '/catalogo/categorias';

export default function Categorias({ categories, filters }: Props) {
    const { can } = usePermissions();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<CategoryRow | null>(null);
    const [deleting, setDeleting] = useState<CategoryRow | null>(null);
    const form = useForm<CategoryForm>(emptyForm);

    const openCreate = () => {
        setEditing(null);
        form.setData(emptyForm);
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (row: CategoryRow) => {
        setEditing(row);
        form.setData({ code: row.code, name: row.name, detail: row.detail ?? '' });
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

    const columns: Column<CategoryRow>[] = [
        {
            key: 'code',
            header: 'Código',
            sortable: true,
            cell: (row) => <span className="font-mono text-xs">{row.code}</span>,
        },
        {
            key: 'name',
            header: 'Categoría',
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
            key: 'products',
            header: 'Productos',
            cell: (row) => <Badge variant="outline">{row.products_count}</Badge>,
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
            cell: (row) =>
                can('catalogo.categorias.update') ||
                can('catalogo.categorias.toggle') ||
                can('catalogo.categorias.delete') ? (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="icon" className="size-8" aria-label="Acciones">
                                <MoreHorizontal className="size-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            {can('catalogo.categorias.update') && (
                                <DropdownMenuItem onSelect={() => openEdit(row)}>Editar</DropdownMenuItem>
                            )}
                            {can('catalogo.categorias.toggle') && (
                                <DropdownMenuItem
                                    onSelect={() =>
                                        router.patch(`${URL}/${row.id}/toggle`, {}, { preserveScroll: true })
                                    }
                                >
                                    {row.state === 1 ? 'Inactivar' : 'Activar'}
                                </DropdownMenuItem>
                            )}
                            {can('catalogo.categorias.delete') && (
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
                ) : null,
        },
    ];

    return (
        <>
            <Head title="Categorías" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">Categorías</h1>
                        <p className="text-sm text-muted-foreground">
                            Agrupan los productos de la carta (entradas, bebidas, postres…).
                        </p>
                    </div>
                    {can('catalogo.categorias.store') && (
                        <Button onClick={openCreate}>
                            <Plus /> Nueva categoría
                        </Button>
                    )}
                </div>

                <DataTable
                    url={URL}
                    data={categories}
                    filters={filters}
                    columns={columns}
                    rowKey={(row) => row.id}
                    searchPlaceholder="Buscar por código o nombre…"
                    emptyText="Aún no hay categorías. Crea la primera."
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
                title={editing ? 'Editar categoría' : 'Nueva categoría'}
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
                <Field label="Descripción" htmlFor="detail" error={form.errors.detail}>
                    <Input id="detail" value={form.data.detail}
                        onChange={(e) => form.setData('detail', e.target.value)} />
                </Field>
            </FormDialog>

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(value) => !value && setDeleting(null)}
                title="Eliminar categoría"
                description={`¿Eliminar “${deleting?.name ?? ''}”? No se puede si tiene productos.`}
                confirmLabel="Eliminar"
                destructive
                onConfirm={() =>
                    deleting && router.delete(`${URL}/${deleting.id}`, { preserveScroll: true })
                }
            />
        </>
    );
}

Categorias.layout = {
    breadcrumbs: [
        { title: 'Catálogo', href: '/catalogo/categorias' },
        { title: 'Categorías', href: '/catalogo/categorias' },
    ],
};
