import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

type Props = {
    session: {
        id: number;
        register: string | null;
        location: string | null;
        status: 'OPEN' | 'CLOSED';
        opened_by: string;
        closed_by: string | null;
        opened_at: string | null;
        closed_at: string | null;
        opening_amount: number;
        expected_amount: number;
        closing_amount: number | null;
        difference_amount: number | null;
        notes: string | null;
    };
    summary: {
        methods: { name: string; count: number; amount: number; surcharge: number; total: number; cash: boolean }[];
        total_sales: number;
        cash_payments: number;
        incomes: number;
        expenses: number;
        expected: number;
    };
    movements: { id: number; type: 'INCOME' | 'EXPENSE'; amount: number; concept: string; detail: string | null; by: string | null; at: string | null }[];
    currency: string;
};

const when = (iso: string | null) =>
    iso ? new Date(iso).toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' }) : '—';

export default function CajaArqueo({ session, summary, movements, currency }: Props) {
    const money = (value: number | null) => {
        if (value === null) {
            return '—';
        }

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

    const closed = session.status === 'CLOSED';

    const lines: [string, string, boolean?][] = [
        ['Base de apertura', money(session.opening_amount)],
        ['+ Cobros en efectivo', money(summary.cash_payments)],
        ['+ Ingresos', money(summary.incomes)],
        ['− Egresos', money(summary.expenses)],
        ['Efectivo esperado', money(session.expected_amount), true],
    ];

    return (
        <>
            <Head title={`Arqueo de caja #${session.id}`} />

            {/* Al imprimir se ocultan el menú lateral y la barra superior. */}
            <style>{`@media print {
                [data-slot='sidebar'], [data-slot='sidebar-gap'], [data-slot='sidebar-container'], header, .no-print { display: none !important; }
            }`}</style>

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="flex items-center gap-2 text-xl font-semibold">
                            Arqueo de caja #{session.id}
                            {closed ? <Badge variant="outline">Cerrada</Badge> : <Badge className="bg-emerald-600">Abierta</Badge>}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {session.register}
                            {session.location ? ` · ${session.location}` : ''}
                        </p>
                    </div>
                    <div className="no-print flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href="/operacion/caja"><ArrowLeft /> Volver</Link>
                        </Button>
                        <Button onClick={() => window.print()}>
                            <Printer /> Imprimir
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Sesión</CardTitle>
                            <CardDescription>
                                Abierta por {session.opened_by} · {when(session.opened_at)}
                                {closed && (
                                    <>
                                        <br />
                                        Cerrada por {session.closed_by ?? '—'} · {when(session.closed_at)}
                                    </>
                                )}
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-1.5 text-sm">
                            {lines.map(([label, value, strong]) => (
                                <div key={label} className={cn('flex justify-between', strong && 'border-t pt-1.5 font-semibold')}>
                                    <span className={strong ? '' : 'text-muted-foreground'}>{label}</span>
                                    <span>{value}</span>
                                </div>
                            ))}

                            {closed && (
                                <>
                                    <div className="flex justify-between">
                                        <span className="text-muted-foreground">Efectivo contado</span>
                                        <span>{money(session.closing_amount)}</span>
                                    </div>
                                    <div
                                        className={cn(
                                            'flex justify-between font-semibold',
                                            (session.difference_amount ?? 0) < 0 && 'text-red-600 dark:text-red-400',
                                            (session.difference_amount ?? 0) > 0 && 'text-amber-600 dark:text-amber-400',
                                        )}
                                    >
                                        <span>Diferencia</span>
                                        <span>{money(session.difference_amount)}</span>
                                    </div>
                                </>
                            )}

                            {session.notes && (
                                <p className="mt-2 whitespace-pre-line rounded-md border bg-muted/40 p-2 text-xs">
                                    {session.notes}
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Cobros por método de pago</CardTitle>
                            <CardDescription>Ventas totales {money(summary.total_sales)}</CardDescription>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-6">Método</TableHead>
                                        <TableHead>Pagos</TableHead>
                                        <TableHead>Recargo</TableHead>
                                        <TableHead className="pr-6 text-right">Total</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {summary.methods.length === 0 ? (
                                        <TableRow>
                                            <TableCell colSpan={4} className="h-16 text-center text-muted-foreground">
                                                Sin cobros en esta sesión.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        summary.methods.map((method) => (
                                            <TableRow key={method.name}>
                                                <TableCell className="pl-6">
                                                    {method.name}
                                                    {method.cash && (
                                                        <Badge variant="outline" className="ml-2">Efectivo</Badge>
                                                    )}
                                                </TableCell>
                                                <TableCell>{method.count}</TableCell>
                                                <TableCell>{money(method.surcharge)}</TableCell>
                                                <TableCell className="pr-6 text-right font-medium">{money(method.total)}</TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Ingresos y egresos</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Hora</TableHead>
                                    <TableHead>Tipo</TableHead>
                                    <TableHead>Concepto</TableHead>
                                    <TableHead>Registró</TableHead>
                                    <TableHead className="pr-6 text-right">Valor</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {movements.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={5} className="h-16 text-center text-muted-foreground">
                                            Sin movimientos manuales.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    movements.map((movement) => (
                                        <TableRow key={movement.id}>
                                            <TableCell className="pl-6 text-xs">{when(movement.at)}</TableCell>
                                            <TableCell>
                                                <Badge variant={movement.type === 'INCOME' ? 'secondary' : 'outline'}>
                                                    {movement.type === 'INCOME' ? 'Ingreso' : 'Egreso'}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {movement.concept}
                                                {movement.detail && (
                                                    <span className="block text-xs text-muted-foreground">{movement.detail}</span>
                                                )}
                                            </TableCell>
                                            <TableCell className="text-xs">{movement.by ?? '—'}</TableCell>
                                            <TableCell className="pr-6 text-right font-medium">
                                                {movement.type === 'EXPENSE' ? '− ' : ''}
                                                {money(movement.amount)}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

CajaArqueo.layout = {
    breadcrumbs: [
        { title: 'Operación', href: '/operacion/caja' },
        { title: 'Caja', href: '/operacion/caja' },
        { title: 'Arqueo', href: '/operacion/caja' },
    ],
};
