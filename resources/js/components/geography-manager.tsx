import { Head, router } from '@inertiajs/react';
import { Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState, type FormEvent, type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

type Field = { key: string; label: string; required?: boolean; type?: 'text' | 'select'; options?: { value: string; label: string }[]; maxLength?: number };
type Row = Record<string, any> & { codmov: number; state: number };
type Props = {
    title: string;
    base: string;
    items: { data: Row[]; links: { url: string | null; label: string; active: boolean }[]; total: number; current_page: number; last_page: number };
    filters: { search?: string | null; [key: string]: unknown };
    fields: Field[];
    columns: { label: string; render: (row: Row) => ReactNode }[];
};

export function GeographyManager({ title, base, items, filters, fields, columns }: Props) {
    const [search, setSearch] = useState(String(filters.search ?? ''));
    const [dialogOpen, setDialogOpen] = useState(false);
    const [editing, setEditing] = useState<Row | null>(null);
    const [values, setValues] = useState<Record<string, string>>({});

    const openCreate = () => {
        setEditing(null);
        setValues(Object.fromEntries(fields.map(field => [field.key, field.key === 'state' ? '1' : ''])));
        setDialogOpen(true);
    };
    const openEdit = (row: Row) => {
        setEditing(row);
        setValues(Object.fromEntries(fields.map(field => [field.key, row[field.key] === null || row[field.key] === undefined ? '' : String(row[field.key])] )));
        setDialogOpen(true);
    };
    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const payload = { ...values };
        const options = { preserveScroll: true, onSuccess: () => setDialogOpen(false) };
        if (editing) {
            router.put(`${base}/${editing.codmov}`, payload, options);
        } else {
            router.post(base, payload, options);
        }
    };

    return <>
        <Head title={title} />
        <main className="flex flex-1 flex-col gap-5 p-4 md:p-6">
            <header className="flex flex-wrap items-center justify-between gap-3">
                <div><h1 className="text-2xl font-semibold">{title}</h1><p className="mt-1 text-sm text-muted-foreground">Catálogo geográfico de soporte, con relaciones y códigos Factus.</p></div>
                <Button onClick={openCreate}><Plus className="mr-2 size-4" />Nuevo registro</Button>
            </header>
            <form className="flex max-w-xl gap-2" onSubmit={event => { event.preventDefault(); router.get(base, { ...filters, search: search || undefined }, { preserveState: true, preserveScroll: true, replace: true }); }}>
                <Input value={search} onChange={event => setSearch(event.target.value)} placeholder="Buscar por código o nombre..." />
                <Button type="submit" variant="secondary"><Search className="mr-2 size-4" />Buscar</Button>
            </form>
            <div className="overflow-x-auto rounded-xl border">
                <table className="w-full text-sm"><thead className="bg-muted/50"><tr>{columns.map(column => <th key={column.label} className="px-3 py-3 text-left font-medium">{column.label}</th>)}<th className="px-3 py-3 text-right font-medium">Acciones</th></tr></thead>
                    <tbody>{items.data.map(row => <tr key={row.codmov} className="border-t">
                        {columns.map(column => <td key={column.label} className="px-3 py-3">{column.render(row) ?? '—'}</td>)}
                        <td className="whitespace-nowrap px-3 py-3 text-right"><div className="flex justify-end gap-1">
                            <Button size="icon" variant="ghost" aria-label="Editar" onClick={() => openEdit(row)}><Pencil className="size-4" /></Button>
                            <Button size="sm" variant="outline" onClick={() => router.patch(`${base}/${row.codmov}/toggle`, {}, { preserveScroll: true })}>{Number(row.state) === 1 ? 'Inactivar' : 'Activar'}</Button>
                            <Button size="icon" variant="ghost" aria-label="Eliminar" onClick={() => { if (window.confirm(`¿Eliminar ${row.name ?? row.code ?? row.codmov}?`)) router.delete(`${base}/${row.codmov}`, { preserveScroll: true }); }}><Trash2 className="size-4 text-destructive" /></Button>
                        </div></td>
                    </tr>)}{items.data.length === 0 && <tr><td colSpan={columns.length + 1} className="p-8 text-center text-muted-foreground">No hay registros para mostrar.</td></tr>}</tbody>
                </table>
            </div>
            <div className="flex flex-wrap items-center justify-between gap-3"><span className="text-sm text-muted-foreground">{items.total} registros · página {items.current_page} de {items.last_page}</span><div className="flex flex-wrap gap-1">{items.links.map((link, index) => <Button key={index} size="sm" variant={link.active ? 'default' : 'outline'} disabled={!link.url} onClick={() => link.url && router.visit(link.url, { preserveScroll: true })} dangerouslySetInnerHTML={{ __html: link.label }} />)}</div></div>
        </main>
        <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader><DialogTitle>{editing ? `Editar ${title.toLowerCase()}` : `Nuevo registro · ${title.toLowerCase()}`}</DialogTitle><DialogDescription>Completa los datos y guarda los cambios.</DialogDescription></DialogHeader>
                <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
                    {fields.map(field => <div key={field.key} className="space-y-1.5"><label className="text-sm font-medium" htmlFor={`geo-${field.key}`}>{field.label}{field.required ? ' *' : ''}</label>
                        {field.type === 'select' ? <Select value={values[field.key] ?? ''} onValueChange={value => setValues(current => ({ ...current, [field.key]: value }))}>
                            <SelectTrigger id={`geo-${field.key}`}><SelectValue placeholder={`Seleccionar ${field.label.toLowerCase()}`} /></SelectTrigger><SelectContent>{(field.options ?? []).map(option => <SelectItem key={option.value} value={option.value}>{option.label}</SelectItem>)}</SelectContent>
                        </Select> : <Input id={`geo-${field.key}`} required={field.required} maxLength={field.maxLength} value={values[field.key] ?? ''} onChange={event => setValues(current => ({ ...current, [field.key]: ['iso2', 'iso3'].includes(field.key) ? event.target.value.toUpperCase() : event.target.value }))} />}
                    </div>)}
                    <DialogFooter className="sm:col-span-2"><Button type="button" variant="outline" onClick={() => setDialogOpen(false)}>Cancelar</Button><Button type="submit">{editing ? 'Guardar cambios' : 'Crear registro'}</Button></DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </>;
}
