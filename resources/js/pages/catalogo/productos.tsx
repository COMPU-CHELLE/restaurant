import { Head, router, useForm } from '@inertiajs/react';
import { ImagePlus, MoreHorizontal, Plus, Star, Trash2 } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { Field } from '@/components/field';
import { FormDialog } from '@/components/form-dialog';
import { StateBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { usePermissions } from '@/hooks/use-permissions';
import type { Paginated, TableFilters } from '@/types/pagination';

type ProductRow = {
    id: number;
    code: string;
    barcode: string | null;
    name: string;
    detail: string | null;
    category_id: number;
    category: string | null;
    sale_price: number;
    cost_price: number;
    product_type: 'PRODUCT' | 'SERVICE';
    state: number;
    image_url: string | null;
    images_count: number;
};

type Props = {
    products: Paginated<ProductRow>;
    filters: TableFilters & { category?: number | null };
    options: {
        categories: { id: number; code: string; name: string }[];
        currency: string;
    };
};

type ProductForm = {
    category_id: string;
    code: string;
    barcode: string;
    name: string;
    detail: string;
    product_type: string;
    sale_price: string;
    cost_price: string;
};

type PriceRow = {
    id: number;
    type: 'SALE' | 'COST';
    price: number;
    active: boolean;
    from: string | null;
    to: string | null;
};
type ImageRow = { id: number; url: string; primary: boolean };
type Detail = { prices: PriceRow[]; images: ImageRow[] };

const URL = '/catalogo/productos';

const blank = (): ProductForm => ({
    category_id: '',
    code: '',
    barcode: '',
    name: '',
    detail: '',
    product_type: 'PRODUCT',
    sale_price: '',
    cost_price: '',
});

const formatMoney = (value: number, currency: string) => {
    try {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency,
            maximumFractionDigits: currency === 'COP' ? 0 : 2,
        }).format(value);
    } catch {
        return String(value);
    }
};

const when = (iso: string | null) =>
    iso ? new Date(iso).toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' }) : '—';

/** Historial de precios e imágenes de un producto; se pide al servidor al abrir el diálogo. */
function useProductDetail(productId: number | null) {
    const [data, setData] = useState<Detail | null>(null);

    const load = useCallback(() => {
        if (productId === null) {
            return;
        }

        fetch(`${URL}/${productId}/detail`, {
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((response) => (response.ok ? response.json() : Promise.reject()))
            .then((json: Detail) => setData(json))
            .catch(() => setData(null));
    }, [productId]);

    useEffect(() => {
        setData(null);
        load();
    }, [load]);

    return { data, load };
}

function PricesDialog({
    product,
    currency,
    canManage,
    onClose,
}: {
    product: ProductRow | null;
    currency: string;
    canManage: boolean;
    onClose: () => void;
}) {
    const { data, load } = useProductDetail(product?.id ?? null);
    const form = useForm<{ price_type: string; price: string }>({ price_type: 'SALE', price: '' });

    const submit = () => {
        if (!product) {
            return;
        }

        form.post(`${URL}/${product.id}/prices`, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset('price');
                load();
            },
        });
    };

    return (
        <Dialog open={product !== null} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Precios · {product?.name}</DialogTitle>
                    <DialogDescription>
                        Venta vigente {product ? formatMoney(product.sale_price, currency) : ''} · costo vigente{' '}
                        {product ? formatMoney(product.cost_price, currency) : ''}
                    </DialogDescription>
                </DialogHeader>

                {canManage && (
                    <div className="grid items-end gap-3 sm:grid-cols-[150px_1fr_auto]">
                        <Field label="Tipo" error={form.errors.price_type}>
                            <Select value={form.data.price_type}
                                onValueChange={(value) => form.setData('price_type', value)}>
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="SALE">Venta</SelectItem>
                                    <SelectItem value="COST">Costo</SelectItem>
                                </SelectContent>
                            </Select>
                        </Field>
                        <Field label="Nuevo precio" htmlFor="new-price" error={form.errors.price}>
                            <Input id="new-price" type="number" min={0} step="0.01" value={form.data.price}
                                onChange={(e) => form.setData('price', e.target.value)} />
                        </Field>
                        <Button onClick={submit} disabled={form.processing || form.data.price === ''}>
                            Registrar
                        </Button>
                    </div>
                )}

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Tipo</TableHead>
                            <TableHead>Precio</TableHead>
                            <TableHead>Desde</TableHead>
                            <TableHead>Hasta</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {(data?.prices ?? []).map((price) => (
                            <TableRow key={price.id}>
                                <TableCell>
                                    <Badge variant={price.active ? 'default' : 'outline'}>
                                        {price.type === 'SALE' ? 'Venta' : 'Costo'}
                                    </Badge>
                                </TableCell>
                                <TableCell className="font-medium">{formatMoney(price.price, currency)}</TableCell>
                                <TableCell className="text-xs">{when(price.from)}</TableCell>
                                <TableCell className="text-xs">{price.active ? 'Vigente' : when(price.to)}</TableCell>
                            </TableRow>
                        ))}
                        {data && data.prices.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={4} className="h-16 text-center text-muted-foreground">
                                    Sin historial todavía.
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </DialogContent>
        </Dialog>
    );
}

function ImagesDialog({
    product,
    canManage,
    onClose,
}: {
    product: ProductRow | null;
    canManage: boolean;
    onClose: () => void;
}) {
    const { data, load } = useProductDetail(product?.id ?? null);
    const input = useRef<HTMLInputElement>(null);

    const upload = (files: FileList | null) => {
        if (!product || !files || files.length === 0) {
            return;
        }

        router.post(
            `${URL}/${product.id}/images`,
            { images: Array.from(files) },
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: load,
                onFinish: () => {
                    if (input.current) {
                        input.current.value = '';
                    }
                },
            },
        );
    };

    return (
        <Dialog open={product !== null} onOpenChange={(open) => !open && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Imágenes · {product?.name}</DialogTitle>
                    <DialogDescription>
                        PNG, JPG o WEBP de hasta 2 MB. Máximo 6 por producto. La estrella marca la principal.
                    </DialogDescription>
                </DialogHeader>

                {canManage && (
                    <div>
                        <input ref={input} type="file" multiple className="hidden"
                            accept="image/png,image/jpeg,image/webp"
                            onChange={(e) => upload(e.target.files)} />
                        <Button variant="outline" onClick={() => input.current?.click()}>
                            <ImagePlus /> Subir imágenes
                        </Button>
                    </div>
                )}

                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    {(data?.images ?? []).map((image) => (
                        <div key={image.id} className="group relative overflow-hidden rounded-md border">
                            <img src={image.url} alt={product?.name ?? ''} className="aspect-square w-full object-cover" />
                            {image.primary && (
                                <Badge className="absolute top-2 left-2">Principal</Badge>
                            )}
                            {canManage && (
                                <div className="absolute inset-x-0 bottom-0 flex justify-end gap-1 bg-background/80 p-1">
                                    {!image.primary && (
                                        <Button variant="ghost" size="icon" className="size-7" aria-label="Hacer principal"
                                            onClick={() =>
                                                product &&
                                                router.patch(`${URL}/${product.id}/images/${image.id}/primary`, {},
                                                    { preserveScroll: true, onSuccess: load })
                                            }>
                                            <Star className="size-4" />
                                        </Button>
                                    )}
                                    <Button variant="ghost" size="icon" className="size-7" aria-label="Eliminar imagen"
                                        onClick={() =>
                                            product &&
                                            router.delete(`${URL}/${product.id}/images/${image.id}`,
                                                { preserveScroll: true, onSuccess: load })
                                        }>
                                        <Trash2 className="size-4" />
                                    </Button>
                                </div>
                            )}
                        </div>
                    ))}
                </div>

                {data && data.images.length === 0 && (
                    <p className="text-center text-sm text-muted-foreground">Este producto aún no tiene imágenes.</p>
                )}
            </DialogContent>
        </Dialog>
    );
}

export default function Productos({ products, filters, options }: Props) {
    const { can } = usePermissions();
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<ProductRow | null>(null);
    const [deleting, setDeleting] = useState<ProductRow | null>(null);
    const [pricing, setPricing] = useState<ProductRow | null>(null);
    const [imaging, setImaging] = useState<ProductRow | null>(null);
    const form = useForm<ProductForm>(blank());
    const money = (value: number) => formatMoney(value, options.currency);

    const openCreate = () => {
        setEditing(null);
        form.setData(blank());
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (row: ProductRow) => {
        setEditing(row);
        form.setData({
            category_id: String(row.category_id),
            code: row.code,
            barcode: row.barcode ?? '',
            name: row.name,
            detail: row.detail ?? '',
            product_type: row.product_type,
            sale_price: String(row.sale_price),
            cost_price: String(row.cost_price),
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
        if (filters.category) next.category = filters.category;
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

    const columns: Column<ProductRow>[] = [
        {
            key: 'image',
            header: '',
            className: 'w-14',
            cell: (row) =>
                row.image_url ? (
                    <img src={row.image_url} alt={row.name} className="size-10 rounded-md border object-cover" />
                ) : (
                    <div className="flex size-10 items-center justify-center rounded-md border bg-muted/40 text-muted-foreground">
                        <ImagePlus className="size-4" />
                    </div>
                ),
        },
        {
            key: 'code',
            header: 'Código',
            sortable: true,
            cell: (row) => <span className="font-mono text-xs">{row.code}</span>,
        },
        {
            key: 'name',
            header: 'Producto',
            sortable: true,
            cell: (row) => (
                <div className="flex flex-col">
                    <span className="font-medium">{row.name}</span>
                    <span className="text-xs text-muted-foreground">
                        {row.category ?? 'Sin categoría'}
                        {row.barcode ? ` · ${row.barcode}` : ''}
                    </span>
                </div>
            ),
        },
        {
            key: 'sale_price',
            header: 'Precio',
            sortable: true,
            cell: (row) => <span className="font-medium">{money(row.sale_price)}</span>,
        },
        {
            key: 'cost',
            header: 'Costo',
            cell: (row) => <span className="text-muted-foreground">{money(row.cost_price)}</span>,
        },
        {
            key: 'type',
            header: 'Tipo',
            cell: (row) => (
                <Badge variant="outline">{row.product_type === 'PRODUCT' ? 'Producto' : 'Servicio'}</Badge>
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
                        {can('catalogo.productos.update') && (
                            <DropdownMenuItem onSelect={() => openEdit(row)}>Editar</DropdownMenuItem>
                        )}
                        <DropdownMenuItem onSelect={() => setPricing(row)}>Precios</DropdownMenuItem>
                        <DropdownMenuItem onSelect={() => setImaging(row)}>
                            Imágenes ({row.images_count})
                        </DropdownMenuItem>
                        {can('catalogo.productos.toggle') && (
                            <DropdownMenuItem
                                onSelect={() =>
                                    router.patch(`${URL}/${row.id}/toggle`, {}, { preserveScroll: true })
                                }
                            >
                                {row.state === 1 ? 'Inactivar' : 'Activar'}
                            </DropdownMenuItem>
                        )}
                        {can('catalogo.productos.delete') && (
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
            <Head title="Productos" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">Productos</h1>
                        <p className="text-sm text-muted-foreground">
                            La carta del restaurante: precios, imágenes y categorías.
                        </p>
                    </div>
                    {can('catalogo.productos.store') && (
                        <Button onClick={openCreate}>
                            <Plus /> Nuevo producto
                        </Button>
                    )}
                </div>

                <DataTable
                    url={URL}
                    data={products}
                    filters={filters}
                    columns={columns}
                    rowKey={(row) => row.id}
                    searchPlaceholder="Buscar por código, código de barras o nombre…"
                    emptyText="Aún no hay productos. Crea el primero."
                    toolbar={
                        <div className="flex flex-wrap items-center gap-2">
                            <Select value={filters.category ? String(filters.category) : 'all'}
                                onValueChange={(value) => refilter({ category: value === 'all' ? null : value })}>
                                <SelectTrigger className="w-48"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas las categorías</SelectItem>
                                    {options.categories.map((category) => (
                                        <SelectItem key={category.id} value={String(category.id)}>
                                            {category.name}
                                        </SelectItem>
                                    ))}
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
                title={editing ? 'Editar producto' : 'Nuevo producto'}
                description={
                    editing
                        ? 'Si cambias el precio o el costo, el cambio queda en el historial.'
                        : 'Después de crearlo puedes subirle imágenes.'
                }
                processing={form.processing}
                onSubmit={submit}
                wide
            >
                {options.categories.length === 0 && (
                    <p className="rounded-md border bg-muted/40 p-3 text-sm text-muted-foreground">
                        Primero crea al menos una categoría en Catálogo → Categorías.
                    </p>
                )}

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
                    <Field label="Categoría" error={form.errors.category_id}>
                        <Select value={form.data.category_id || undefined}
                            onValueChange={(value) => form.setData('category_id', value)}>
                            <SelectTrigger><SelectValue placeholder="Elige" /></SelectTrigger>
                            <SelectContent>
                                {options.categories.map((category) => (
                                    <SelectItem key={category.id} value={String(category.id)}>
                                        {category.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </Field>
                    <Field label="Tipo" error={form.errors.product_type}>
                        <Select value={form.data.product_type}
                            onValueChange={(value) => form.setData('product_type', value)}>
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="PRODUCT">Producto</SelectItem>
                                <SelectItem value="SERVICE">Servicio</SelectItem>
                            </SelectContent>
                        </Select>
                    </Field>
                    <Field label="Código de barras" htmlFor="barcode" error={form.errors.barcode}>
                        <Input id="barcode" value={form.data.barcode}
                            onChange={(e) => form.setData('barcode', e.target.value)} />
                    </Field>
                </div>

                <Field label="Descripción" htmlFor="detail" error={form.errors.detail}>
                    <Input id="detail" value={form.data.detail}
                        onChange={(e) => form.setData('detail', e.target.value)} />
                </Field>

                <div className="grid gap-4 sm:grid-cols-2">
                    <Field label={`Precio de venta (${options.currency})`} htmlFor="sale_price" error={form.errors.sale_price}>
                        <Input id="sale_price" type="number" min={0} step="0.01" value={form.data.sale_price}
                            onChange={(e) => form.setData('sale_price', e.target.value)} />
                    </Field>
                    <Field label={`Costo (${options.currency})`} htmlFor="cost_price" error={form.errors.cost_price}>
                        <Input id="cost_price" type="number" min={0} step="0.01" value={form.data.cost_price}
                            onChange={(e) => form.setData('cost_price', e.target.value)} />
                    </Field>
                </div>
            </FormDialog>

            <PricesDialog product={pricing} currency={options.currency}
                canManage={can('catalogo.productos.prices')} onClose={() => setPricing(null)} />
            <ImagesDialog product={imaging}
                canManage={can('catalogo.productos.images')} onClose={() => setImaging(null)} />

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(value) => !value && setDeleting(null)}
                title="Eliminar producto"
                description={`¿Eliminar “${deleting?.name ?? ''}”? Si ya se vendió, solo puedes inactivarlo.`}
                confirmLabel="Eliminar"
                destructive
                onConfirm={() =>
                    deleting && router.delete(`${URL}/${deleting.id}`, { preserveScroll: true })
                }
            />
        </>
    );
}

Productos.layout = {
    breadcrumbs: [
        { title: 'Catálogo', href: '/catalogo/productos' },
        { title: 'Productos', href: '/catalogo/productos' },
    ],
};
