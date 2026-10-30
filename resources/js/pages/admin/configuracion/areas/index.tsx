import { Head, router, useForm } from '@inertiajs/react';
import { type DragEvent, type FormEvent, useState } from 'react';
import { Armchair, CirclePlus, LayoutGrid, Pencil, Plus, Trash2, Utensils } from 'lucide-react';

type TableStatus = 'AVAILABLE' | 'OCCUPIED' | 'RESERVED' | 'PAYMENT_PENDING' | 'DIRTY' | 'OUT_OF_SERVICE';
type RestaurantTable = { codmov: number; area_id: number; number: number; name: string | null; capacity: number; shape: 'ROUND' | 'RECTANGLE' | 'SQUARE' | 'OVAL'; pos_x: number; pos_y: number; width: number; height: number; rotation: number; status: TableStatus };
type Area = { codmov: number; code: string; name: string; detail: string | null; color: string; tables: RestaurantTable[] };
type Props = { areas: Area[]; tableStatuses: Record<TableStatus, string> };
const statusColors: Record<TableStatus, string> = { AVAILABLE: 'border-emerald-500 bg-emerald-50 text-emerald-800', OCCUPIED: 'border-rose-500 bg-rose-50 text-rose-800', RESERVED: 'border-amber-500 bg-amber-50 text-amber-800', PAYMENT_PENDING: 'border-orange-500 bg-orange-50 text-orange-800', DIRTY: 'border-slate-400 bg-slate-100 text-slate-700', OUT_OF_SERVICE: 'border-gray-400 bg-gray-200 text-gray-700' };

export default function AreasIndex({ areas, tableStatuses }: Props) {
    const [selectedId, setSelectedId] = useState<number | null>(areas[0]?.codmov ?? null);
    const [editingArea, setEditingArea] = useState<number | null>(null);
    const [draggedTable, setDraggedTable] = useState<number | null>(null);
    const [showAreaForm, setShowAreaForm] = useState(false);
    const [showTableForm, setShowTableForm] = useState(false);
    const activeArea = areas.find((area) => area.codmov === selectedId) ?? areas[0] ?? null;
    const areaForm = useForm({ name: '', detail: '', color: '#0d9488' });
    const editForm = useForm({ name: '', detail: '', color: '#0d9488' });
    const tableForm = useForm({ number: '', name: '', capacity: '4', shape: 'ROUND' as RestaurantTable['shape'] });

    function submitArea(event: FormEvent) {
        event.preventDefault();
        if (editingArea) editForm.put(`/admin/configuracion/areas/${editingArea}`, { onSuccess: () => { setEditingArea(null); setShowAreaForm(false); } });
        else areaForm.post('/admin/configuracion/areas', { onSuccess: () => { areaForm.reset(); setShowAreaForm(false); } });
    }
    function editArea(area: Area) { setEditingArea(area.codmov); editForm.setData({ name: area.name, detail: area.detail ?? '', color: area.color }); setShowAreaForm(true); }
    function submitTable(event: FormEvent) { event.preventDefault(); if (activeArea) tableForm.post(`/admin/configuracion/areas/${activeArea.codmov}/mesas`, { onSuccess: () => { tableForm.reset(); setShowTableForm(false); } }); }
    function moveTable(event: DragEvent<HTMLDivElement>) {
        event.preventDefault();
        if (!activeArea || draggedTable === null) return;
        const rect = event.currentTarget.getBoundingClientRect();
        const table = activeArea.tables.find((item) => item.codmov === draggedTable);
        if (!table) return;
        router.put(`/admin/configuracion/areas/${activeArea.codmov}/mesas/${table.codmov}`, { pos_x: Math.max(0, Math.round(event.clientX - rect.left - table.width / 2)), pos_y: Math.max(0, Math.round(event.clientY - rect.top - table.height / 2)) }, { preserveScroll: true });
        setDraggedTable(null);
    }
    function deleteTable(table: RestaurantTable) { if (activeArea && window.confirm(`¿Eliminar la mesa ${table.name || table.number}?`)) router.delete(`/admin/configuracion/areas/${activeArea.codmov}/mesas/${table.codmov}`); }

    return <>
        <Head title="Salones y áreas" />
        <div className="flex flex-col gap-5 p-4 md:p-6">
            <header className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <div className="flex items-center gap-2 text-sm text-muted-foreground">
                        <LayoutGrid size={17}/> Administración / Configuración
                    </div>
                    <h1 className="mt-1 text-2xl font-semibold">Salones y áreas</h1>
                        <p className="mt-1 text-sm text-muted-foreground">Diseña la distribución del restaurante y organiza las mesas para la pantalla de venta.</p>
                </div>
                <button onClick={() => { setEditingArea(null); areaForm.reset(); setShowAreaForm(!showAreaForm); setShowTableForm(false); }} className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm text-primary-foreground">
                    <CirclePlus size={17}/> Crear zona
                </button>
            </header>
            {showAreaForm && <form onSubmit={submitArea} className="grid gap-3 rounded-xl border bg-card p-4 md:grid-cols-[1fr_1fr_100px_auto] md:items-end">
                <label className="grid gap-1 text-sm">Nombre<input required value={editingArea ? editForm.data.name : areaForm.data.name} onChange={e => editingArea ? editForm.setData('name', e.target.value) : areaForm.setData('name', e.target.value)} className="rounded-md border bg-background px-3 py-2" placeholder="Salón principal"/></label><label className="grid gap-1 text-sm">Descripción<input value={editingArea ? editForm.data.detail : areaForm.data.detail} onChange={e => editingArea ? editForm.setData('detail', e.target.value) : areaForm.setData('detail', e.target.value)} className="rounded-md border bg-background px-3 py-2" placeholder="Opcional"/></label><label className="grid gap-1 text-sm">Color<input type="color" value={editingArea ? editForm.data.color : areaForm.data.color} onChange={e => editingArea ? editForm.setData('color', e.target.value) : areaForm.setData('color', e.target.value)} className="h-10 w-full rounded-md border bg-background p-1"/></label><div className="flex gap-2"><button className="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground">Guardar</button><button type="button" onClick={() => { setShowAreaForm(false); setEditingArea(null); }} className="rounded-md border px-3 py-2 text-sm">Cancelar</button></div></form>}
            <div className="grid gap-3 sm:grid-cols-3"><div className="rounded-xl border bg-card p-4"><p className="text-sm text-muted-foreground">Zonas creadas</p><p className="mt-1 text-2xl font-semibold">{areas.length}</p></div><div className="rounded-xl border bg-card p-4"><p className="text-sm text-muted-foreground">Mesas configuradas</p><p className="mt-1 text-2xl font-semibold">{areas.reduce((n,a)=>n+a.tables.length,0)}</p></div><div className="rounded-xl border bg-card p-4"><p className="text-sm text-muted-foreground">Mesas no disponibles</p><p className="mt-1 text-2xl font-semibold">{areas.reduce((n,a)=>n+a.tables.filter(t=>t.status!=='AVAILABLE').length,0)}</p></div></div>
            <nav className="flex flex-wrap gap-2 border-b pb-3">{areas.map(area => <button key={area.codmov} onClick={() => { setSelectedId(area.codmov); setShowTableForm(false); }} className={`rounded-lg border px-4 py-2 text-sm font-medium ${activeArea?.codmov===area.codmov?'border-primary bg-primary text-primary-foreground':'bg-card hover:bg-muted'}`}><span className="mr-2 inline-block size-2 rounded-full" style={{backgroundColor:area.color}}/>{area.name}<span className="ml-2 opacity-70">{area.tables.length}</span></button>)}{!areas.length && <p className="text-sm text-muted-foreground">Todavía no hay zonas. Crea la primera para empezar.</p>}</nav>
            {activeArea && <><div className="flex flex-wrap items-center justify-between gap-3"><div><h2 className="text-lg font-semibold">Plano: {activeArea.name}</h2><p className="text-sm text-muted-foreground">Arrastra una mesa para cambiar su ubicación; se guardará automáticamente.</p></div><div className="flex flex-wrap gap-2"><button onClick={()=>editArea(activeArea)} className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm"><Pencil size={15}/> Editar zona</button><button onClick={()=>{if(window.confirm(`¿Eliminar la zona ${activeArea.name}? Debe estar vacía.`))router.delete(`/admin/configuracion/areas/${activeArea.codmov}`)}} className="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm text-destructive"><Trash2 size={15}/> Eliminar zona</button><button onClick={()=>setShowTableForm(!showTableForm)} className="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground"><Plus size={16}/> Agregar mesa</button></div></div>
            {showTableForm && <form onSubmit={submitTable} className="grid gap-3 rounded-xl border bg-card p-4 sm:grid-cols-2 lg:grid-cols-5 lg:items-end"><label className="grid gap-1 text-sm">Número<input required type="number" min="1" value={tableForm.data.number} onChange={e=>tableForm.setData('number',e.target.value)} className="rounded-md border bg-background px-3 py-2"/></label><label className="grid gap-1 text-sm">Nombre opcional<input value={tableForm.data.name} onChange={e=>tableForm.setData('name',e.target.value)} className="rounded-md border bg-background px-3 py-2"/></label><label className="grid gap-1 text-sm">Capacidad<input required type="number" min="1" max="100" value={tableForm.data.capacity} onChange={e=>tableForm.setData('capacity',e.target.value)} className="rounded-md border bg-background px-3 py-2"/></label><label className="grid gap-1 text-sm">Forma<select value={tableForm.data.shape} onChange={e=>tableForm.setData('shape',e.target.value as RestaurantTable['shape'])} className="rounded-md border bg-background px-3 py-2"><option value="ROUND">Redonda</option><option value="SQUARE">Cuadrada</option><option value="RECTANGLE">Rectangular</option><option value="OVAL">Ovalada</option></select></label><button className="rounded-md bg-primary px-4 py-2 text-sm text-primary-foreground">Guardar mesa</button></form>}
            <div onDragOver={e=>e.preventDefault()} onDrop={moveTable} className="relative min-h-[560px] overflow-auto rounded-xl border-2 border-dashed bg-muted/30" style={{backgroundImage:'radial-gradient(currentColor 0.7px, transparent 0.7px)',backgroundSize:'20px 20px'}}>{activeArea.tables.map(table=><div key={table.codmov} draggable onDragStart={()=>setDraggedTable(table.codmov)} title={`${tableStatuses[table.status]} · Capacidad ${table.capacity}`} className={`absolute flex cursor-grab flex-col items-center justify-center gap-1 border-2 shadow-sm hover:shadow-md ${statusColors[table.status]} ${table.shape==='ROUND'||table.shape==='OVAL'?'rounded-full':'rounded-xl'}`} style={{left:table.pos_x,top:table.pos_y,width:table.width,height:table.height,transform:`rotate(${table.rotation}deg)`}}><Armchair size={22}/><span className="max-w-full truncate px-1 text-xs font-semibold">{table.name||`Mesa ${table.number}`}</span><span className="text-[10px]">{table.capacity} puestos</span><button draggable={false} onClick={e=>{e.stopPropagation();deleteTable(table)}} title="Eliminar mesa" className="absolute -right-2 -top-2 rounded-full border bg-background p-1 text-destructive shadow"><Trash2 size={12}/></button></div>)}{!activeArea.tables.length&&<div className="absolute inset-0 flex flex-col items-center justify-center gap-2 text-center text-muted-foreground"><Utensils size={34}/><p className="font-medium">Esta zona aún no tiene mesas</p><p className="text-sm">Usa “Agregar mesa” para diseñar el salón.</p></div>}</div><div className="flex flex-wrap gap-3 text-xs text-muted-foreground"><span className="inline-flex items-center gap-1"><i className="size-3 rounded-sm border-2 border-emerald-500 bg-emerald-50"/> Disponible</span><span className="inline-flex items-center gap-1"><i className="size-3 rounded-sm border-2 border-rose-500 bg-rose-50"/> Ocupada</span><span className="inline-flex items-center gap-1"><i className="size-3 rounded-sm border-2 border-amber-500 bg-amber-50"/> Reservada</span></div></>}
        </div>
    </>;
}
