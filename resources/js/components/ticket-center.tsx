import { Head, router } from '@inertiajs/react';
import { useState, type FormEvent } from 'react';
import { Search, Ticket as TicketIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type TicketRow = {
    id: number;
    subject: string;
    description: string;
    category: string;
    priority: string;
    status: string;
    response: string | null;
    company: { code: string; name: string } | null;
    creator: { name: string; email: string } | null;
    created_at: string | null;
    updated_at: string | null;
};
type PageProps = {
    items: { data: TicketRow[]; links: { url: string | null; label: string; active: boolean }[]; total: number; current_page: number; last_page: number };
    filters: { search?: string | null; status?: string | null };
    isSupport: boolean;
};

const statusLabels: Record<string, string> = {
    open: 'Abierto', in_progress: 'En proceso', waiting_user: 'Esperando usuario', resolved: 'Resuelto', closed: 'Cerrado',
};
const priorityLabels: Record<string, string> = { low: 'Baja', normal: 'Normal', high: 'Alta', urgent: 'Urgente' };
const categoryLabels: Record<string, string> = { general: 'General', technical: 'Técnico', billing: 'Facturación', factus: 'Factus / Factus Pay', access: 'Acceso' };
const dateLabel = (value: string | null) => value ? new Date(value).toLocaleString('es-CO') : '—';

export function TicketCenter({ items, filters, isSupport }: PageProps) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [subject, setSubject] = useState('');
    const [description, setDescription] = useState('');
    const [category, setCategory] = useState('general');
    const [priority, setPriority] = useState('normal');
    const [edits, setEdits] = useState<Record<number, { status: string; response: string }>>({});
    const base = isSupport ? '/soporte/tickets' : '/admin/tickets';

    const applyFilters = (nextSearch = search, status = filters.status ?? 'all') => {
        router.get(base, { search: nextSearch || undefined, status: status === 'all' ? undefined : status }, { preserveState: true, preserveScroll: true, replace: true });
    };

    const createTicket = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        router.post(base, {
            subject, description, category, priority,
        }, {
            preserveScroll: true,
            onSuccess: () => { setSubject(''); setDescription(''); setCategory('general'); setPriority('normal'); },
        });
    };

    const updateTicket = (ticket: TicketRow) => {
        const edit = edits[ticket.id] ?? { status: ticket.status, response: ticket.response ?? '' };
        router.patch(`${base}/${ticket.id}`, { status: edit.status, response: edit.response || null }, { preserveScroll: true });
    };

    return <>
        <Head title="Tickets de soporte" />
        <main className="flex flex-1 flex-col gap-5 p-4 md:p-6">
            <header>
                <h1 className="flex items-center gap-2 text-2xl font-semibold"><TicketIcon className="size-6" />{isSupport ? 'Mesa de ayuda · Tickets' : 'Mis tickets de soporte'}</h1>
                <p className="mt-1 text-sm text-muted-foreground">{isSupport ? 'Atiende las solicitudes registradas por los administradores de las empresas. Responde y actualiza su estado.' : 'Registra solicitudes y consulta las respuestas del equipo de soporte.'}</p>
            </header>

            {isSupport && <div className="rounded-xl border border-dashed p-4 text-sm text-muted-foreground">Cuando una empresa solicite ayuda por teléfono, indícale al administrador que registre el ticket desde su cuenta. Así la solicitud queda asociada al usuario y la empresa correctos.</div>}

            {!isSupport && <form onSubmit={createTicket} className="grid gap-3 rounded-xl border p-4 md:grid-cols-2">
                <h2 className="font-semibold md:col-span-2">Crear ticket</h2>
                <Input required maxLength={180} value={subject} onChange={event => setSubject(event.target.value)} placeholder="Asunto" />
                <Select value={category} onValueChange={setCategory}>
                    <SelectTrigger><SelectValue placeholder="Categoría" /></SelectTrigger>
                    <SelectContent>{Object.entries(categoryLabels).map(([key, label]) => <SelectItem key={key} value={key}>{label}</SelectItem>)}</SelectContent>
                </Select>
                <Select value={priority} onValueChange={setPriority}>
                    <SelectTrigger><SelectValue placeholder="Prioridad" /></SelectTrigger>
                    <SelectContent>{Object.entries(priorityLabels).map(([key, label]) => <SelectItem key={key} value={key}>{label}</SelectItem>)}</SelectContent>
                </Select>
                <Textarea required maxLength={10000} value={description} onChange={event => setDescription(event.target.value)} placeholder="Describe la solicitud y los pasos para reproducirla..." className="md:col-span-2" />
                <div className="md:col-span-2"><Button type="submit">Crear ticket</Button></div>
            </form>}

            <section className="space-y-3">
                <div className="flex flex-col gap-2 sm:flex-row">
                    <form className="flex flex-1 gap-2" onSubmit={event => { event.preventDefault(); applyFilters(search); }}>
                        <Input value={search} onChange={event => setSearch(event.target.value)} placeholder="Buscar por asunto, descripción o número..." />
                        <Button type="submit" variant="secondary"><Search className="mr-2 size-4" />Buscar</Button>
                    </form>
                    <Select value={filters.status ?? 'all'} onValueChange={status => applyFilters(search, status)}>
                        <SelectTrigger className="sm:w-52"><SelectValue placeholder="Estado" /></SelectTrigger>
                        <SelectContent><SelectItem value="all">Todos los estados</SelectItem>{Object.entries(statusLabels).map(([key, label]) => <SelectItem key={key} value={key}>{label}</SelectItem>)}</SelectContent>
                    </Select>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full min-w-[850px] text-sm">
                        <thead className="bg-muted/50"><tr>{['Ticket', 'Empresa / solicitante', 'Categoría', 'Prioridad', 'Estado', 'Fecha', ...(isSupport ? ['Gestión'] : ['Respuesta'])].map(label => <th key={label} className="px-3 py-3 text-left font-medium">{label}</th>)}</tr></thead>
                        <tbody>{items.data.map(ticket => {
                            const edit = edits[ticket.id] ?? { status: ticket.status, response: ticket.response ?? '' };
                            return <tr key={ticket.id} className="border-t align-top">
                                <td className="max-w-xs px-3 py-3"><div className="font-semibold">#{ticket.id} · {ticket.subject}</div><p className="mt-1 whitespace-pre-wrap text-xs text-muted-foreground">{ticket.description}</p></td>
                                <td className="px-3 py-3">{ticket.company ? <><div>{ticket.company.name}</div><div className="text-xs text-muted-foreground">{ticket.company.code}</div></> : <><div>{ticket.creator?.name ?? '—'}</div><div className="text-xs text-muted-foreground">{ticket.creator?.email ?? ''}</div></>}</td>
                                <td className="px-3 py-3">{categoryLabels[ticket.category] ?? ticket.category}</td>
                                <td className="px-3 py-3">{priorityLabels[ticket.priority] ?? ticket.priority}</td>
                                <td className="px-3 py-3">{statusLabels[ticket.status] ?? ticket.status}</td>
                                <td className="whitespace-nowrap px-3 py-3">{dateLabel(ticket.created_at)}</td>
                                <td className="min-w-64 px-3 py-3">{isSupport ? <div className="space-y-2">
                                    <Select value={edit.status} onValueChange={status => setEdits(current => ({ ...current, [ticket.id]: { ...edit, status } }))}>
                                        <SelectTrigger><SelectValue /></SelectTrigger><SelectContent>{Object.entries(statusLabels).map(([key, label]) => <SelectItem key={key} value={key}>{label}</SelectItem>)}</SelectContent>
                                    </Select>
                                    <Textarea value={edit.response} onChange={event => setEdits(current => ({ ...current, [ticket.id]: { ...edit, response: event.target.value } }))} placeholder="Respuesta para el usuario..." rows={3} />
                                    <Button size="sm" onClick={() => updateTicket(ticket)}>Guardar cambios</Button>
                                </div> : <span className="whitespace-pre-wrap">{ticket.response || 'Pendiente de respuesta'}</span>}</td>
                            </tr>;
                        })}{items.data.length === 0 && <tr><td colSpan={7} className="p-8 text-center text-muted-foreground">No hay tickets con esos filtros.</td></tr>}</tbody>
                    </table>
                </div>
                <div className="flex flex-wrap items-center justify-between gap-3"><span className="text-sm text-muted-foreground">{items.total} tickets · página {items.current_page} de {items.last_page}</span><div className="flex flex-wrap gap-1">{items.links.map((link, index) => <Button key={index} size="sm" variant={link.active ? 'default' : 'outline'} disabled={!link.url} onClick={() => link.url && router.visit(link.url, { preserveScroll: true })} dangerouslySetInnerHTML={{ __html: link.label }} />)}</div></div>
            </section>
        </main>
    </>;
}
