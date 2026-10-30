import { Head, router } from '@inertiajs/react';
import { KeyRound, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

type ModuleOption = { codmov: number; code: string; name: string };
type LevelOption = { codmov: number; code: string; name: string; parent: string; type: 'LEVEL' | 'ITEM' };
type PermissionRow = {
    codmov: number; code: string; name: string; detail: string | null; codmod: number; codniv: number | null; codite: number | null; state: number;
    module?: ModuleOption | null; level?: LevelOption | null; item?: LevelOption | null;
};
type Page<T> = { data: T[]; links: { url: string | null; label: string; active: boolean }[]; total: number; current_page: number; last_page: number };
type Props = { items: Page<PermissionRow>; modules: ModuleOption[]; levels: LevelOption[]; filters: { search?: string | null } };
type Values = { code: string; name: string; detail: string; codmod: string; codniv: string; codite: string; state: string };
const blank: Values = { code: '', name: '', detail: '', codmod: '', codniv: 'none', codite: 'none', state: '1' };

export default function Permisos({ items, modules, levels, filters }: Props) {
    const [search, setSearch] = useState(String(filters.search ?? ''));
    const [open, setOpen] = useState(false);
    const [editing, setEditing] = useState<PermissionRow | null>(null);
    const [values, setValues] = useState<Values>(blank);
    const levelsForModule = levels.filter(level => level.type === 'LEVEL' && modules.find(module => module.codmov.toString() === values.codmod)?.code === level.parent);
    const itemsForLevel = levels.filter(level => level.type === 'ITEM' && levels.find(parent => parent.type === 'LEVEL' && parent.codmov.toString() === values.codniv)?.code === level.parent);

    const create = () => { setEditing(null); setValues(blank); setOpen(true); };
    const edit = (row: PermissionRow) => {
        setEditing(row);
        setValues({ code: row.code ?? '', name: row.name ?? '', detail: row.detail ?? '', codmod: String(row.codmod), codniv: row.codniv ? String(row.codniv) : 'none', codite: row.codite ? String(row.codite) : 'none', state: String(row.state ?? 1) });
        setOpen(true);
    };
    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const payload = { ...values, codniv: values.codniv === 'none' ? '' : values.codniv, codite: values.codite === 'none' ? '' : values.codite };
        const options = { preserveScroll: true, onSuccess: () => setOpen(false) };
        if (editing) router.put(`/soporte/parametros/permisos/${editing.codmov}`, payload, options);
        else router.post('/soporte/parametros/permisos', payload, options);
    };
    const set = (key: keyof Values, value: string) => setValues(current => ({ ...current, [key]: value, ...(key === 'codmod' ? { codniv: 'none', codite: 'none' } : {}), ...(key === 'codniv' ? { codite: 'none' } : {}) }));

    return <>
        <Head title="Permisos" />
        <main className="flex flex-1 flex-col gap-5 p-4 md:p-6">
            <header className="flex flex-wrap items-center justify-between gap-3">
                <div className="flex items-start gap-3"><div className="rounded-xl border p-3"><KeyRound className="size-5" /></div><div><h1 className="text-2xl font-semibold">Permisos</h1><p className="mt-1 text-sm text-muted-foreground">Administra permisos y sus relaciones con módulos, submódulos y opciones de menú.</p></div></div>
                <Button onClick={create}><Plus className="mr-2 size-4" />Nuevo permiso</Button>
            </header>
            <form className="flex max-w-xl gap-2" onSubmit={event => { event.preventDefault(); router.get('/soporte/parametros/permisos', { search: search || undefined }, { preserveState: true, preserveScroll: true, replace: true }); }}>
                <Input value={search} onChange={event => setSearch(event.target.value)} placeholder="Buscar por código, nombre o detalle..." />
                <Button type="submit" variant="secondary"><Search className="mr-2 size-4" />Buscar</Button>
            </form>
            <div className="overflow-x-auto rounded-xl border"><table className="w-full text-sm"><thead className="bg-muted/50"><tr>{['Código', 'Permiso', 'Módulo', 'Submódulo / opción', 'Estado', 'Acciones'].map(title => <th key={title} className="px-3 py-3 text-left font-medium">{title}</th>)}</tr></thead><tbody>
                {items.data.map(row => <tr key={row.codmov} className="border-t">
                    <td className="px-3 py-3 font-mono text-xs">{row.code}</td><td className="px-3 py-3"><div className="font-medium">{row.name}</div>{row.detail && <div className="mt-1 max-w-sm text-xs text-muted-foreground">{row.detail}</div>}</td>
                    <td className="px-3 py-3">{row.module?.name ?? `#${row.codmod}`}</td><td className="px-3 py-3">{row.level?.name ?? '—'}{row.item ? <div className="mt-1 text-xs text-muted-foreground">Opción: {row.item.name}</div> : null}</td>
                    <td className="px-3 py-3"><span className={Number(row.state) === 1 ? 'rounded-full bg-green-100 px-2 py-1 text-xs text-green-800 dark:bg-green-900 dark:text-green-200' : 'rounded-full bg-muted px-2 py-1 text-xs'}>{Number(row.state) === 1 ? 'Activo' : 'Inactivo'}</span></td>
                    <td className="whitespace-nowrap px-3 py-3 text-right"><div className="flex justify-end gap-1"><Button size="icon" variant="ghost" aria-label="Editar" onClick={() => edit(row)}><Pencil className="size-4" /></Button><Button size="sm" variant="outline" onClick={() => router.patch(`/soporte/parametros/permisos/${row.codmov}/toggle`, {}, { preserveScroll: true })}>{Number(row.state) === 1 ? 'Inactivar' : 'Activar'}</Button><Button size="icon" variant="ghost" aria-label="Eliminar" onClick={() => { if (window.confirm(`¿Eliminar el permiso ${row.name}?`)) router.delete(`/soporte/parametros/permisos/${row.codmov}`, { preserveScroll: true }); }}><Trash2 className="size-4 text-destructive" /></Button></div></td>
                </tr>)}
                {items.data.length === 0 && <tr><td colSpan={6} className="p-8 text-center text-muted-foreground">No hay permisos para mostrar.</td></tr>}
            </tbody></table></div>
            <div className="flex flex-wrap items-center justify-between gap-3"><span className="text-sm text-muted-foreground">{items.total} permisos · página {items.current_page} de {items.last_page}</span><div className="flex flex-wrap gap-1">{items.links.map((link, index) => <Button key={index} size="sm" variant={link.active ? 'default' : 'outline'} disabled={!link.url} onClick={() => link.url && router.visit(link.url, { preserveScroll: true })} dangerouslySetInnerHTML={{ __html: link.label }} />)}</div></div>
        </main>
        <Dialog open={open} onOpenChange={setOpen}><DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl"><DialogHeader><DialogTitle>{editing ? 'Editar permiso' : 'Crear permiso'}</DialogTitle><DialogDescription>Relaciona el permiso con el módulo y, si corresponde, con un submódulo u opción específica.</DialogDescription></DialogHeader>
            <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
                <div className="space-y-1.5"><label className="text-sm font-medium" htmlFor="permission-code">Código único *</label><Input id="permission-code" required maxLength={255} value={values.code} onChange={event => set('code', event.target.value)} placeholder="PERM001" /></div>
                <div className="space-y-1.5"><label className="text-sm font-medium" htmlFor="permission-name">Nombre único *</label><Input id="permission-name" required maxLength={255} value={values.name} onChange={event => set('name', event.target.value)} placeholder="catalogo.productos.view" /></div>
                <div className="space-y-1.5 sm:col-span-2"><label className="text-sm font-medium" htmlFor="permission-detail">Descripción</label><Input id="permission-detail" value={values.detail} onChange={event => set('detail', event.target.value)} maxLength={255} /></div>
                <div className="space-y-1.5"><label className="text-sm font-medium">Módulo *</label><Select value={values.codmod} onValueChange={value => set('codmod', value)}><SelectTrigger><SelectValue placeholder="Seleccionar módulo" /></SelectTrigger><SelectContent>{modules.map(module => <SelectItem key={module.codmov} value={String(module.codmov)}>{module.name} ({module.code})</SelectItem>)}</SelectContent></Select></div>
                <div className="space-y-1.5"><label className="text-sm font-medium">Submódulo</label><Select value={values.codniv} onValueChange={value => set('codniv', value)}><SelectTrigger><SelectValue placeholder="Sin submódulo" /></SelectTrigger><SelectContent><SelectItem value="none">Sin submódulo</SelectItem>{levelsForModule.map(level => <SelectItem key={level.codmov} value={String(level.codmov)}>{level.name}</SelectItem>)}</SelectContent></Select></div>
                <div className="space-y-1.5"><label className="text-sm font-medium">Opción de menú</label><Select value={values.codite} onValueChange={value => set('codite', value)}><SelectTrigger><SelectValue placeholder="Sin opción específica" /></SelectTrigger><SelectContent><SelectItem value="none">Sin opción específica</SelectItem>{itemsForLevel.map(item => <SelectItem key={item.codmov} value={String(item.codmov)}>{item.name}</SelectItem>)}</SelectContent></Select></div>
                <div className="space-y-1.5"><label className="text-sm font-medium">Estado</label><Select value={values.state} onValueChange={value => set('state', value)}><SelectTrigger><SelectValue /></SelectTrigger><SelectContent><SelectItem value="1">Activo</SelectItem><SelectItem value="0">Inactivo</SelectItem></SelectContent></Select></div>
                <DialogFooter className="sm:col-span-2"><Button type="button" variant="outline" onClick={() => setOpen(false)}>Cancelar</Button><Button type="submit">{editing ? 'Guardar cambios' : 'Crear permiso'}</Button></DialogFooter>
            </form>
        </DialogContent></Dialog>
    </>;
}

Permisos.layout = { breadcrumbs: [{ title: 'Soporte', href: '/soporte/parametros' }, { title: 'Parámetros', href: '/soporte/parametros' }, { title: 'Permisos', href: '/soporte/parametros/permisos' }] };
