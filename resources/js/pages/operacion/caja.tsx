import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowDownCircle, ArrowUpCircle, Lock, LockOpen, Receipt } from 'lucide-react';
import { useState } from 'react';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { Field } from '@/components/field';
import { FormDialog } from '@/components/form-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { usePermissions } from '@/hooks/use-permissions';
import { cn } from '@/lib/utils';
import type { Paginated, TableFilters } from '@/types/pagination';

type SessionCard = {
    id: number;
    opened_by: string;
    mine: boolean;
    opened_at: string | null;
    opening_amount: number;
    total_sales: number;
    cash_payments: number;
    incomes: number;
    expenses: number;
    expected: number;
};

type RegisterCard = {
    id: number;
    code: string;
    name: string;
    location: string | null;
    session: SessionCard | null;
};

type SessionRow = {
    id: number;
    register: string | null;
    opened_by: string;
    opened_at: string | null;
    closed_at: string | null;
    opening_amount: number;
    expected_amount: number | null;
    closing_amount: number | null;
    difference_amount: number | null;
    status: 'OPEN' | 'CLOSED';
};

type Props = {
    registers: RegisterCard[];
    sessions: Paginated<SessionRow>;
    filters: TableFilters & { status?: string | null; register?: number | null };
    options: { registers: { id: number; name: string }[]; currency: string };
};

type MoveTarget = { session: SessionCard; register: RegisterCard; type: 'INCOME' | 'EXPENSE' };
type CloseTarget = { session: SessionCard; register: RegisterCard };

const URL = '/operacion/caja';

const when = (iso: string | null) =>
    iso ? new Date(iso).toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' }) : '—';

export default function Caja({ registers, sessions, filters, options }: Props) {
    const { can } = usePermissions();
    const money = (value: number | null) => {
        if (value === null) {
            return '—';
        }

        try {
            return new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: options.currency,
                maximumFractionDigits: options.currency === 'COP' ? 0 : 2,
            }).format(value);
        } catch {
            return String(value);
        }
    };

    const [opening, setOpening] = useState<RegisterCard | null>(null);
    const [moving, setMoving] = useState<MoveTarget | null>(null);
    const [closing, setClosing] = useState<CloseTarget | null>(null);

    const openForm = useForm({ cash_register_id: '', opening_amount: '', notes: '' });
    const moveForm = useForm({ cash_session_id: '', movement_type: 'INCOME', amount: '', concept: '', detail: '' });
    const closeForm = useForm({ cash_session_id: '', closing_amount: '', notes: '' });

    const startOpen = (register: RegisterCard) => {
        openForm.setData({ cash_register_id: String(register.id), opening_amount: '', notes: '' });
        openForm.clearErrors();
        setOpening(register);
    };

    const startMove = (target: MoveTarget) => {
        moveForm.setData({
            cash_session_id: String(target.session.id),
            movement_type: target.type,
            amount: '',
            concept: '',
            detail: '',
        });
        moveForm.clearErrors();
        setMoving(target);
    };

    const startClose = (target: CloseTarget) => {
        closeForm.setData({ cash_session_id: String(target.session.id), closing_amount: '', notes: '' });
        closeForm.clearErrors();
        setClosing(target);
    };

    const counted = closeForm.data.closing_amount === '' ? null : Number(closeForm.data.closing_amount);
    const difference = closing && counted !== null ? Math.round((counted - closing.session.expected) * 100) / 100 : null;

    const refilter = (patch: Record<string, string | null>) => {
        const next: Record<string, string | number> = {};

        if (filters.search) next.search = filters.search;
        if (filters.status) next.status = filters.status;
        if (filters.register) next.register = filters.register;
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

    const columns: Column<SessionRow>[] = [
        {
            key: 'register',
            header: 'Caja',
            cell: (row) => (
                <div className="flex flex-col">
                    <span className="font-medium">{row.register ?? '—'}</span>
                    <span className="text-xs text-muted-foreground">{row.opened_by}</span>
                </div>
            ),
        },
        {
            key: 'opened_at',
            header: 'Apertura',
            sortable: true,
            cell: (row) => <span className="text-xs">{when(row.opened_at)}</span>,
        },
        {
            key: 'closed_at',
            header: 'Cierre',
            sortable: true,
            cell: (row) => <span className="text-xs">{when(row.closed_at)}</span>,
        },
        { key: 'opening', header: 'Base', cell: (row) => money(row.opening_amount) },
        { key: 'expected', header: 'Esperado', cell: (row) => money(row.expected_amount) },
        { key: 'counted', header: 'Contado', cell: (row) => money(row.closing_amount) },
        {
            key: 'difference',
            header: 'Diferencia',
            cell: (row) =>
                row.difference_amount === null ? (
                    '—'
                ) : (
                    <span
                        className={cn(
                            'font-medium',
                            row.difference_amount < 0 && 'text-red-600 dark:text-red-400',
                            row.difference_amount > 0 && 'text-amber-600 dark:text-amber-400',
                        )}
                    >
                        {money(row.difference_amount)}
                    </span>
                ),
        },
        {
            key: 'status',
            header: 'Estado',
            sortable: true,
            cell: (row) =>
                row.status === 'OPEN' ? (
                    <Badge className="bg-emerald-600">Abierta</Badge>
                ) : (
                    <Badge variant="outline">Cerrada</Badge>
                ),
        },
        {
            key: 'actions',
            header: '',
            className: 'w-10 text-right',
            cell: (row) => (
                <Button variant="ghost" size="icon" className="size-8" asChild aria-label="Ver arqueo">
                    <Link href={`${URL}/${row.id}`}>
                        <Receipt className="size-4" />
                    </Link>
                </Button>
            ),
        },
    ];

    return (
        <>
            <Head title="Caja" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Caja</h1>
                    <p className="text-sm text-muted-foreground">
                        Abre tu caja con una base, registra ingresos y egresos, y ciérrala con el arqueo.
                    </p>
                </div>

                {registers.length === 0 ? (
                    <Card>
                        <CardContent className="py-8 text-center text-sm text-muted-foreground">
                            No hay cajas activas. El administrador las crea en Configuración → Cajas registradoras.
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {registers.map((register) => (
                            <Card key={register.id}>
                                <CardHeader className="flex-row items-start justify-between space-y-0">
                                    <div>
                                        <CardTitle className="text-base">{register.name}</CardTitle>
                                        <CardDescription>
                                            {register.location ?? register.code}
                                        </CardDescription>
                                    </div>
                                    {register.session ? (
                                        <Badge className="bg-emerald-600">Abierta</Badge>
                                    ) : (
                                        <Badge variant="outline">Cerrada</Badge>
                                    )}
                                </CardHeader>

                                <CardContent className="grid gap-3">
                                    {register.session ? (
                                        <>
                                            <p className="text-xs text-muted-foreground">
                                                {register.session.opened_by} · desde {when(register.session.opened_at)}
                                            </p>

                                            <div className="grid grid-cols-2 gap-2 text-sm">
                                                <span className="text-muted-foreground">Base</span>
                                                <span className="text-right">{money(register.session.opening_amount)}</span>
                                                <span className="text-muted-foreground">Cobros en efectivo</span>
                                                <span className="text-right">{money(register.session.cash_payments)}</span>
                                                <span className="text-muted-foreground">Ingresos</span>
                                                <span className="text-right">{money(register.session.incomes)}</span>
                                                <span className="text-muted-foreground">Egresos</span>
                                                <span className="text-right">− {money(register.session.expenses)}</span>
                                                <span className="font-medium">Efectivo esperado</span>
                                                <span className="text-right font-semibold">{money(register.session.expected)}</span>
                                                <span className="text-xs text-muted-foreground">Ventas totales</span>
                                                <span className="text-right text-xs text-muted-foreground">
                                                    {money(register.session.total_sales)}
                                                </span>
                                            </div>

                                            <div className="flex flex-wrap gap-2">
                                                {can('operacion.caja.movement') && (
                                                    <>
                                                        <Button size="sm" variant="outline"
                                                            onClick={() => startMove({ session: register.session!, register, type: 'INCOME' })}>
                                                            <ArrowDownCircle /> Ingreso
                                                        </Button>
                                                        <Button size="sm" variant="outline"
                                                            onClick={() => startMove({ session: register.session!, register, type: 'EXPENSE' })}>
                                                            <ArrowUpCircle /> Egreso
                                                        </Button>
                                                    </>
                                                )}
                                                <Button size="sm" variant="ghost" asChild>
                                                    <Link href={`${URL}/${register.session.id}`}>Ver arqueo</Link>
                                                </Button>
                                                {can('operacion.caja.close') && (
                                                    <Button size="sm"
                                                        onClick={() => startClose({ session: register.session!, register })}>
                                                        <Lock /> Cerrar caja
                                                    </Button>
                                                )}
                                            </div>
                                        </>
                                    ) : (
                                        can('operacion.caja.open') && (
                                            <Button onClick={() => startOpen(register)}>
                                                <LockOpen /> Abrir caja
                                            </Button>
                                        )
                                    )}
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                <div className="flex flex-col gap-2">
                    <h2 className="text-base font-semibold">Historial de sesiones</h2>
                    <DataTable
                        url={URL}
                        data={sessions}
                        filters={filters}
                        columns={columns}
                        rowKey={(row) => row.id}
                        searchPlaceholder="Buscar por cajero u observación…"
                        emptyText="Aún no hay sesiones de caja."
                        toolbar={
                            <div className="flex flex-wrap items-center gap-2">
                                <Select value={filters.register ? String(filters.register) : 'all'}
                                    onValueChange={(value) => refilter({ register: value === 'all' ? null : value })}>
                                    <SelectTrigger className="w-44"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todas las cajas</SelectItem>
                                        {options.registers.map((register) => (
                                            <SelectItem key={register.id} value={String(register.id)}>
                                                {register.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <Select value={filters.status ?? 'all'}
                                    onValueChange={(value) => refilter({ status: value === 'all' ? null : value })}>
                                    <SelectTrigger className="w-36"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Todas</SelectItem>
                                        <SelectItem value="OPEN">Abiertas</SelectItem>
                                        <SelectItem value="CLOSED">Cerradas</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        }
                    />
                </div>
            </div>

            {/* Abrir caja */}
            <FormDialog
                open={opening !== null}
                onOpenChange={(value) => !value && setOpening(null)}
                title={`Abrir ${opening?.name ?? 'caja'}`}
                description="Cuenta el efectivo con el que empieza la caja (la base)."
                submitLabel="Abrir caja"
                processing={openForm.processing}
                onSubmit={() =>
                    openForm.post(`${URL}/open`, { preserveScroll: true, onSuccess: () => setOpening(null) })
                }
            >
                <Field label="Base de apertura" htmlFor="opening_amount"
                    error={openForm.errors.opening_amount ?? openForm.errors.cash_register_id}>
                    <Input id="opening_amount" type="number" min={0} step="0.01" autoFocus
                        value={openForm.data.opening_amount}
                        onChange={(e) => openForm.setData('opening_amount', e.target.value)} />
                </Field>
                <Field label="Observación" htmlFor="open_notes" error={openForm.errors.notes}>
                    <Input id="open_notes" value={openForm.data.notes}
                        onChange={(e) => openForm.setData('notes', e.target.value)} />
                </Field>
            </FormDialog>

            {/* Ingreso / egreso */}
            <FormDialog
                open={moving !== null}
                onOpenChange={(value) => !value && setMoving(null)}
                title={moving?.type === 'INCOME' ? 'Registrar ingreso' : 'Registrar egreso'}
                description={
                    moving
                        ? `${moving.register.name} · efectivo en caja ${money(moving.session.expected)}`
                        : undefined
                }
                submitLabel="Registrar"
                processing={moveForm.processing}
                onSubmit={() =>
                    moveForm.post(`${URL}/movement`, { preserveScroll: true, onSuccess: () => setMoving(null) })
                }
            >
                <Field label="Valor" htmlFor="amount" error={moveForm.errors.amount ?? moveForm.errors.cash_session_id}>
                    <Input id="amount" type="number" min={0} step="0.01" autoFocus value={moveForm.data.amount}
                        onChange={(e) => moveForm.setData('amount', e.target.value)} />
                </Field>
                <Field label="Concepto" htmlFor="concept" error={moveForm.errors.concept}
                    hint={moving?.type === 'INCOME' ? 'Ej.: aporte de la gerencia' : 'Ej.: compra de hielo, pago a proveedor'}>
                    <Input id="concept" value={moveForm.data.concept}
                        onChange={(e) => moveForm.setData('concept', e.target.value)} />
                </Field>
                <Field label="Detalle" htmlFor="detail" error={moveForm.errors.detail}>
                    <Input id="detail" value={moveForm.data.detail}
                        onChange={(e) => moveForm.setData('detail', e.target.value)} />
                </Field>
            </FormDialog>

            {/* Cerrar caja */}
            <FormDialog
                open={closing !== null}
                onOpenChange={(value) => !value && setClosing(null)}
                title={`Cerrar ${closing?.register.name ?? 'caja'}`}
                description={closing ? `Efectivo esperado: ${money(closing.session.expected)}` : undefined}
                submitLabel="Cerrar caja"
                processing={closeForm.processing}
                onSubmit={() =>
                    closeForm.post(`${URL}/close`, { preserveScroll: true, onSuccess: () => setClosing(null) })
                }
            >
                <Field label="Efectivo contado" htmlFor="closing_amount"
                    error={closeForm.errors.closing_amount ?? closeForm.errors.cash_session_id}>
                    <Input id="closing_amount" type="number" min={0} step="0.01" autoFocus
                        value={closeForm.data.closing_amount}
                        onChange={(e) => closeForm.setData('closing_amount', e.target.value)} />
                </Field>

                {difference !== null && (
                    <p
                        className={cn(
                            'rounded-md border p-3 text-sm',
                            difference === 0 && 'border-emerald-600/40 text-emerald-700 dark:text-emerald-300',
                            difference < 0 && 'border-red-600/40 text-red-700 dark:text-red-300',
                            difference > 0 && 'border-amber-600/40 text-amber-700 dark:text-amber-300',
                        )}
                    >
                        {difference === 0
                            ? 'La caja cuadra: sin diferencia.'
                            : difference < 0
                              ? `Faltante de ${money(Math.abs(difference))}.`
                              : `Sobrante de ${money(difference)}.`}
                    </p>
                )}

                <Field label="Observación" htmlFor="close_notes" error={closeForm.errors.notes}
                    hint={difference !== null && difference !== 0 ? 'Obligatoria cuando hay diferencia.' : undefined}>
                    <Input id="close_notes" value={closeForm.data.notes}
                        onChange={(e) => closeForm.setData('notes', e.target.value)} />
                </Field>
            </FormDialog>
        </>
    );
}

Caja.layout = {
    breadcrumbs: [
        { title: 'Operación', href: '/operacion/caja' },
        { title: 'Caja', href: '/operacion/caja' },
    ],
};
