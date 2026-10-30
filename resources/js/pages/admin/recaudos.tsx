import { Head, router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { Paginated, TableFilters } from '@/types/pagination';

type Row = {
    id: number;
    reference: string;
    order_id: number;
    order_code: string | null;
    amount: number;
    method: 'QR' | 'BREB';
    status: 'pending' | 'paid' | 'expired' | 'failed' | 'cancelled';
    payment_id: number | null;
    final: boolean;
    created_at: string | null;
    paid_at: string | null;
    expires_at: string | null;
    last_checked_at: string | null;
};

type Filters = TableFilters & { status?: string | null };

type Props = {
    collections: Paginated<Row>;
    filters: Filters;
    totals: { paid: number; pending: number };
};

const URL = '/admin/recaudos';

const statusLabel: Record<Row['status'], { text: string; className: string }> = {
    pending: { text: 'Pendiente', className: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' },
    paid: { text: 'Pagado', className: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' },
    expired: { text: 'Vencido', className: 'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-300' },
    failed: { text: 'Fallido', className: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' },
    cancelled: { text: 'Cancelado', className: 'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-300' },
};

const money = (value: number) =>
    new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value);

const date = (value: string | null) =>
    value ? new Date(value).toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' }) : '—';

export default function Recaudos({ collections, filters, totals }: Props) {
    const columns: Column<Row>[] = [
        {
            key: 'reference_code',
            header: 'Referencia',
            sortable: true,
            cell: (row) => <span className="font-mono text-xs">{row.reference}</span>,
        },
        { key: 'order', header: 'Orden', cell: (row) => row.order_code ?? `#${row.order_id}` },
        { key: 'amount', header: 'Valor', sortable: true, cell: (row) => money(row.amount) },
        { key: 'method', header: 'Método', cell: (row) => (row.method === 'BREB' ? 'Bre-B' : 'QR') },
        {
            key: 'status',
            header: 'Estado',
            sortable: true,
            cell: (row) => (
                <div className="flex flex-col gap-1">
                    <Badge variant="secondary" className={statusLabel[row.status].className}>
                        {statusLabel[row.status].text}
                    </Badge>
                    {row.status === 'paid' && !row.payment_id && (
                        <span className="text-xs text-amber-600">Falta registrar en caja</span>
                    )}
                </div>
            ),
        },
        { key: 'created_at', header: 'Creado', sortable: true, cell: (row) => date(row.created_at) },
        { key: 'paid_at', header: 'Pagado', sortable: true, cell: (row) => date(row.paid_at) },
        {
            key: 'actions',
            header: '',
            className: 'w-10 text-right',
            cell: (row) =>
                row.final ? null : (
                    <Button
                        variant="ghost"
                        size="icon"
                        className="size-8"
                        aria-label="Actualizar estado"
                        onClick={() => router.post(`${URL}/${row.id}/refresh`, {}, { preserveScroll: true })}
                    >
                        <RefreshCw className="size-4" />
                    </Button>
                ),
        },
    ];

    const filterStatus = (status: string) =>
        router.get(
            URL,
            { ...filters, status: status === 'all' ? undefined : status, page: undefined },
            { preserveState: true, replace: true },
        );

    return (
        <>
            <Head title="Recaudos" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-end justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">Recaudos Factus Pay</h1>
                        <p className="text-sm text-muted-foreground">
                            Cobros por QR o Bre-B. Un recaudo pagado en Factus aún debe registrarse en caja.
                        </p>
                    </div>
                    <div className="flex gap-6 text-sm">
                        <div>
                            <div className="text-muted-foreground">Recaudado</div>
                            <div className="text-lg font-semibold">{money(totals.paid)}</div>
                        </div>
                        <div>
                            <div className="text-muted-foreground">Pendientes</div>
                            <div className="text-lg font-semibold">{totals.pending}</div>
                        </div>
                    </div>
                </div>

                <DataTable
                    url={URL}
                    data={collections}
                    filters={filters}
                    columns={columns}
                    rowKey={(row) => row.id}
                    searchPlaceholder="Buscar por referencia u orden…"
                    emptyText="Aún no hay recaudos."
                    toolbar={
                        <Select value={filters.status ?? 'all'} onValueChange={filterStatus}>
                            <SelectTrigger className="w-40">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Todos</SelectItem>
                                {Object.entries(statusLabel).map(([value, { text }]) => (
                                    <SelectItem key={value} value={value}>
                                        {text}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    }
                />
            </div>
        </>
    );
}

Recaudos.layout = {
    breadcrumbs: [
        { title: 'Administración', href: '/admin/empresa' },
        { title: 'Recaudos', href: '/admin/recaudos' },
    ],
};
