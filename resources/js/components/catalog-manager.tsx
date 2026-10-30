import { Head, router, useForm } from '@inertiajs/react';
import { Download, MoreHorizontal, Plus } from 'lucide-react';
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

export type CatalogField = {
    key: string;
    label: string;
    type: 'text' | 'number' | 'select' | 'catalog';
    required?: boolean;
    options?: Record<string, string>;
    source?: string;
    min?: number;
    max?: number;
    step?: number;
    hint?: string;
};

type ItemRow = {
    id: number;
    code: string;
    name: string;
    detail: string | null;
    extra: Record<string, string | number>;
    state: number;
    source_id: number | null;
    imported?: boolean;
};

export type CatalogManagerProps = {
    current: string;
    definition: { key: string; label: string; fields: CatalogField[] };
    items: Paginated<ItemRow>;
    filters: TableFilters;
    references: Record<string, { code: string; name: string }[]>;
    extras: { importable?: number };
};

type FormValues = {
    code: string;
    name: string;
    detail: string;
    extra: Record<string, string>;
};

const NONE = '__none__';

/**
 * Pantalla de parámetros reutilizable. En soporte administra los catálogos globales; en administración,
 * los de la empresa, con importación desde los de soporte.
 */
export function CatalogManager({
    scope,
    current,
    definition,
    items,
    filters,
    references,
    extras,
}: CatalogManagerProps & { scope: 'soporte' | 'admin' }) {
    const baseUrl = `/${scope}/parametros`;
    const itemUrl = `${baseUrl}/${current}`;

    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<ItemRow | null>(null);
    const [deleting, setDeleting] = useState<ItemRow | null>(null);

    const blank = (): FormValues => ({
        code: '',
        name: '',
        detail: '',
        extra: Object.fromEntries(definition.fields.map((field) => [field.key, ''])),
    });

    const form = useForm<FormValues>(blank());

    const openCreate = () => {
        setEditing(null);
        form.setData(blank());
        form.clearErrors();
        setOpen(true);
    };

    const openEdit = (item: ItemRow) => {
        setEditing(item);
        form.setData({
            code: item.code,
            name: item.name,
            detail: item.detail ?? '',
            extra: Object.fromEntries(
                definition.fields.map((field) => [
                    field.key,
                    item.extra[field.key] !== undefined ? String(item.extra[field.key]) : '',
                ]),
            ),
        });
        form.clearErrors();
        setOpen(true);
    };

    const submit = () => {
        const settings = { preserveScroll: true, onSuccess: () => setOpen(false) };

        if (editing) {
            form.put(`${itemUrl}/${editing.id}`, settings);
        } else {
            form.post(itemUrl, settings);
        }
    };

    const fieldError = (key: string) =>
        (form.errors as Record<string, string>)[`extra.${key}`];

    const renderField = (field: CatalogField) => {
        const value = form.data.extra[field.key] ?? '';
        const setValue = (next: string) =>
            form.setData('extra', { ...form.data.extra, [field.key]: next });

        const choices: { value: string; label: string }[] =
            field.type === 'select'
                ? Object.entries(field.options ?? {}).map(([v, label]) => ({ value: v, label }))
                : field.type === 'catalog'
                  ? (references[field.source ?? ''] ?? []).map((ref) => ({
                        value: ref.code,
                        label: `${ref.code} · ${ref.name}`,
                    }))
                  : [];

        return (
            <Field key={field.key} label={field.label + (field.required ? ' *' : '')}
                htmlFor={`extra-${field.key}`} error={fieldError(field.key)} hint={field.hint}>
                {field.type === 'select' || field.type === 'catalog' ? (
                    <Select value={value === '' ? undefined : value}
                        onValueChange={(v) => setValue(v === NONE ? '' : v)}>
                        <SelectTrigger id={`extra-${field.key}`}>
                            <SelectValue placeholder="Selecciona…" />
                        </SelectTrigger>
                        <SelectContent>
                            {!field.required && <SelectItem value={NONE}>— Ninguno —</SelectItem>}
                            {choices.map((choice) => (
                                <SelectItem key={choice.value} value={choice.value}>
                                    {choice.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                ) : (
                    <Input id={`extra-${field.key}`}
                        type={field.type === 'number' ? 'number' : 'text'}
                        min={field.min} max={field.type === 'number' ? field.max : undefined}
                        step={field.step} maxLength={field.type === 'text' ? field.max : undefined}
                        value={value} onChange={(e) => setValue(e.target.value)} />
                )}
            </Field>
        );
    };

    const display = (field: CatalogField, item: ItemRow): string | null => {
        const raw = item.extra[field.key];

        if (raw === undefined || raw === '') {
            return null;
        }

        return field.type === 'select' ? (field.options?.[String(raw)] ?? String(raw)) : String(raw);
    };

    const columns: Column<ItemRow>[] = [
        {
            key: 'code',
            header: 'Código',
            sortable: true,
            cell: (row) => <span className="font-mono text-xs">{row.code}</span>,
        },
        {
            key: 'name',
            header: 'Nombre',
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
        ...(definition.fields.length > 0
            ? [
                  {
                      key: 'extra',
                      header: 'Datos',
                      cell: (row: ItemRow) => (
                          <div className="flex flex-wrap gap-x-3 gap-y-0.5 text-xs text-muted-foreground">
                              {definition.fields.map((field) => {
                                  const text = display(field, row);

                                  return text ? (
                                      <span key={field.key}>
                                          {field.label}: <span className="text-foreground">{text}</span>
                                      </span>
                                  ) : null;
                              })}
                          </div>
                      ),
                  } satisfies Column<ItemRow>,
              ]
            : []),
        ...(scope === 'admin'
            ? [
                  {
                      key: 'origin',
                      header: 'Origen',
                      cell: (row: ItemRow) => (
                          <Badge variant="outline">{row.imported ? 'Soporte' : 'Propio'}</Badge>
                      ),
                  } satisfies Column<ItemRow>,
              ]
            : []),
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
                                router.patch(`${itemUrl}/${row.id}/toggle`, {}, { preserveScroll: true })
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

    const importable = extras.importable ?? 0;

    return (
        <>
            <Head title={definition.label} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">{definition.label}</h1>
                        <p className="text-sm text-muted-foreground">
                            {scope === 'soporte'
                                ? 'Parámetro por defecto de la plataforma. Cada empresa lo importa a los suyos.'
                                : 'Parámetro de esta empresa. Puedes importarlo desde soporte o crear los tuyos.'}
                        </p>
                    </div>

                    <div className="flex flex-wrap gap-2">
                        {scope === 'admin' && (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="outline">
                                        <Download /> Importar desde soporte
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem
                                        onSelect={() =>
                                            router.post(`${itemUrl}/import`, {}, { preserveScroll: true })
                                        }
                                    >
                                        Este catálogo — solo nuevos ({importable})
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        onSelect={() =>
                                            router.post(`${itemUrl}/import`, { update: true }, { preserveScroll: true })
                                        }
                                    >
                                        Este catálogo — nuevos y actualizar importados
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem
                                        onSelect={() =>
                                            router.post(`${baseUrl}/import-all`, {}, { preserveScroll: true })
                                        }
                                    >
                                        Todos los catálogos — solo nuevos
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        )}
                        <Button onClick={openCreate}>
                            <Plus /> Nuevo
                        </Button>
                    </div>
                </div>

                <DataTable
                    url={itemUrl}
                    data={items}
                    filters={filters}
                    columns={columns}
                    rowKey={(row) => row.id}
                    searchPlaceholder={`Buscar en ${definition.label.toLowerCase()}…`}
                    emptyText={
                        scope === 'admin' && importable > 0
                            ? `Este catálogo está vacío. Hay ${importable} elementos en soporte para importar.`
                            : 'Este catálogo está vacío.'
                    }
                    toolbar={
                        <Select
                            value={filters.state ?? 'all'}
                            onValueChange={(value) =>
                                router.get(
                                    itemUrl,
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
                title={`${editing ? 'Editar' : 'Nuevo'} · ${definition.label}`}
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

                {definition.fields.length > 0 && (
                    <div className="grid gap-4 sm:grid-cols-2">
                        {definition.fields.map(renderField)}
                    </div>
                )}
            </FormDialog>

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(value) => !value && setDeleting(null)}
                title="Eliminar elemento"
                description={`¿Eliminar “${deleting?.name ?? ''}”? ${
                    scope === 'soporte'
                        ? 'Las empresas que ya lo importaron conservan su copia.'
                        : 'Podrás volver a importarlo desde soporte.'
                }`}
                confirmLabel="Eliminar"
                destructive
                onConfirm={() =>
                    deleting &&
                    router.delete(`${itemUrl}/${deleting.id}`, { preserveScroll: true })
                }
            />
        </>
    );
}
