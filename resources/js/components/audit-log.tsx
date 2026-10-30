import { Head, router } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { useState } from 'react';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { cn } from '@/lib/utils';
import type { Paginated, TableFilters } from '@/types/pagination';

export type AuditRow = {
    id: number;
    tipeve: string;
    detail: string;
    codmod: string;
    codope: string | null;
    host: string | null;
    basdat: string | null;
    company: { code: string; name: string } | null;
    created_at: string | null;
    old_values: Record<string, unknown> | null;
    new_values: Record<string, unknown> | null;
};

export type AuditFilters = TableFilters & {
    tipeve?: string | null;
    codmod?: string | null;
    codemp?: string | null;
    from?: string | null;
    to?: string | null;
};

type Props = {
    title: string;
    description: string;
    url: string;
    entries: Paginated<AuditRow>;
    filters: AuditFilters;
    options: {
        types: string[];
        modules: string[];
        companies: { id: number; code: string; name: string }[];
    };
    showCompany?: boolean;
};

const eventLabels: Record<string, { label: string; className: string }> = {
    CREATE: { label: 'Creó', className: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' },
    UPDATE: { label: 'Modificó', className: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300' },
    DELETE: { label: 'Eliminó', className: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' },
    RESTORE: { label: 'Restauró', className: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300' },
    LOGIN: { label: 'Inició sesión', className: 'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-300' },
    LOGOUT: { label: 'Cerró sesión', className: 'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-300' },
    LOGIN_FAILED: { label: 'Intento fallido', className: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300' },
};


const show = (value: unknown): string => {
    if (value === null || value === undefined) {
        return '—';
    }

    if (typeof value === 'object') {
        return JSON.stringify(value, null, 2);
    }

    return String(value);
};


const when = (iso: string | null) =>
    iso ? new Date(iso).toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'medium' }) : '—';

const stableValue = (value: unknown): string => {
    if (value === undefined) return '__undefined__';
    return JSON.stringify(value) ?? String(value);
};

const changedFields = (row: AuditRow | null): string[] => {
    if (!row) return [];

    const oldValues = row.old_values ?? {};
    const newValues = row.new_values ?? {};
    const keys = new Set([...Object.keys(oldValues), ...Object.keys(newValues)]);

    return [...keys].filter((key) => stableValue(oldValues[key]) !== stableValue(newValues[key]));
};

const hasChanges = (row: AuditRow): boolean => changedFields(row).length > 0;

/** Bitácora de auditoría reutilizable (soporte: global; administración: solo la empresa). */
export function AuditLog({ title, description, url, entries, filters, options, showCompany = false }: Props) {
    const [detail, setDetail] = useState<AuditRow | null>(null);

    const apply = (patch: Partial<AuditFilters>) => {
        const params: Record<string, unknown> = { ...filters, ...patch };
        delete params.page;

        const clean = Object.fromEntries(
            Object.entries(params).filter(([, v]) => v !== null && v !== undefined && v !== ''),
        );

        router.get(url, clean as Record<string, string | number>, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };


    const fieldKeys = changedFields(detail);


    const columns: Column<AuditRow>[] = [
        {
            key: 'created_at',
            header: 'Fecha',
            className: 'whitespace-nowrap text-xs',
            cell: (row) => when(row.created_at),
        },
        {
            key: 'tipeve',
            header: 'Evento',
            cell: (row) => {
                const event = eventLabels[row.tipeve] ?? { label: row.tipeve, className: '' };

                return (
                    <Badge variant="secondary" className={cn(event.className)}>
                        {event.label}
                    </Badge>
                );
            },
        },
        {
            key: 'detail',
            header: 'Detalle',
            className: 'max-w-md whitespace-normal',
            cell: (row) => <span className="text-sm">{row.detail}</span>,
        },
        {
            key: 'codmod',
            header: 'Entidad',
            cell: (row) => <span className="font-mono text-xs">{row.codmod}</span>,
        },
        {
            key: 'codope',
            header: 'Usuario',
            cell: (row) => <span className="font-mono text-xs">{row.codope ?? '—'}</span>,
        },
        ...(showCompany
            ? [
                  {
                      key: 'company',
                      header: 'Empresa',
                      cell: (row: AuditRow) =>
                          row.company ? (
                              <span className="font-mono text-xs">{row.company.code}</span>
                          ) : (
                              <span className="text-muted-foreground">—</span>
                          ),
                  } satisfies Column<AuditRow>,
              ]
            : []),
        {
            key: 'host',
            header: 'IP',
            cell: (row) => <span className="font-mono text-xs">{row.host ?? '—'}</span>,
        },
        {
            key: 'actions',
            header: '',
            className: 'w-10 text-right',
            cell: (row) =>
                hasChanges(row) ? (
                    <Button variant="ghost" size="icon" className="size-8" aria-label="Ver cambios"
                        onClick={() => setDetail(row)}>
                        <Eye className="size-4" />
                    </Button>
                ) : null,
        },
    ];

    return (
        <>
            <Head title={title} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">{title}</h1>
                    <p className="text-sm text-muted-foreground">{description}</p>
                </div>

                <DataTable
                    url={url}
                    data={entries}
                    filters={filters}
                    columns={columns}
                    rowKey={(row) => row.id}
                    searchPlaceholder="Buscar en el detalle o por usuario…"
                    emptyText="No hay registros con esos filtros."
                    toolbar={
                        <div className="flex flex-wrap items-center gap-2">
                            <Select value={filters.tipeve ?? 'all'}
                                onValueChange={(v) => apply({ tipeve: v === 'all' ? null : v })}>
                                <SelectTrigger className="w-40"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todos los eventos</SelectItem>
                                    {options.types.map((type) => (
                                        <SelectItem key={type} value={type}>
                                            {eventLabels[type]?.label ?? type}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            <Select value={filters.codmod ?? 'all'}
                                onValueChange={(v) => apply({ codmod: v === 'all' ? null : v })}>
                                <SelectTrigger className="w-44"><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">Todas las entidades</SelectItem>
                                    {options.modules.map((module) => (
                                        <SelectItem key={module} value={module}>{module}</SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>

                            {showCompany && (
                                <Select value={filters.codemp ?? 'all'}
                                    onValueChange={(v) => apply({ codemp: v === 'all' ? null : v })}>
                                    <SelectTrigger className="w-44"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todas las empresas</SelectItem>
                                        {options.companies.map((company) => (
                                            <SelectItem key={company.id} value={String(company.id)}>
                                                {company.code}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            )}

                            <Input type="date" className="w-40" value={filters.from ?? ''}
                                onChange={(e) => apply({ from: e.target.value || null })} aria-label="Desde" />
                            <Input type="date" className="w-40" value={filters.to ?? ''}
                                onChange={(e) => apply({ to: e.target.value || null })} aria-label="Hasta" />
                        </div>
                    }
                />
            </div>

            <Dialog open={detail !== null && fieldKeys.length > 0} onOpenChange={(open) => !open && setDetail(null)}>
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>{detail?.detail}</DialogTitle>
                        <DialogDescription>
                            {detail?.codope ?? '—'} · {when(detail?.created_at ?? null)}
                            {detail?.basdat ? ` · ${detail.basdat}` : ''}
                        </DialogDescription>
                    </DialogHeader>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Campo</TableHead>
                                    <TableHead>old_values</TableHead>
                                    <TableHead>new_values</TableHead>
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                {fieldKeys.map((key) => (
                                    <TableRow key={key}>
                                        <TableCell className="font-mono text-xs">
                                            {key}
                                        </TableCell>

                                        <TableCell className="break-words whitespace-pre-wrap text-xs text-red-700 dark:text-red-300">
                                            {show(detail?.old_values?.[key])}
                                        </TableCell>

                                        <TableCell className="break-words whitespace-pre-wrap text-xs text-emerald-700 dark:text-emerald-300">
                                            {show(detail?.new_values?.[key])}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                </DialogContent>
            </Dialog>
        </>
    );
}
