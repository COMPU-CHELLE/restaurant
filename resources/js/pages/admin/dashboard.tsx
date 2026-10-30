import { Head } from '@inertiajs/react';
import { Armchair, IdCard, SlidersHorizontal, Users } from 'lucide-react';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type Props = {
    stats: {
        admins: number;
        operators: number;
        profiles: number;
        profilesActive: number;
        parameters: number;
    };
    recent: { type: string; detail: string; user: string | null; at: string | null }[];
};

const when = (iso: string | null) =>
    iso ? new Date(iso).toLocaleString('es-CO', { dateStyle: 'short', timeStyle: 'short' }) : '—';

function Stat({ title, value, hint, icon: Icon }: { title: string; value: number | string; hint?: string; icon: typeof Users }) {
    return (
        <Card>
            <CardHeader className="flex-row items-center justify-between space-y-0 pb-2">
                <CardDescription>{title}</CardDescription>
                <Icon className="size-4 text-muted-foreground" />
            </CardHeader>
            <CardContent>
                <div className="text-2xl font-semibold">{value}</div>
                {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            </CardContent>
        </Card>
    );
}

export default function AdminDashboard({ stats, recent }: Props) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Dashboard</h1>
                    <p className="text-sm text-muted-foreground">Resumen de tu restaurante.</p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Stat title="Usuarios" value={stats.admins + stats.operators} icon={Users}
                        hint={`${stats.admins} administradores · ${stats.operators} operativos`} />
                    <Stat title="Perfiles" value={stats.profiles} icon={IdCard}
                        hint={`${stats.profilesActive} activos`} />
                    <Stat title="Parámetros" value={stats.parameters} icon={SlidersHorizontal}
                        hint="elementos en tus catálogos" />
                    <Stat title="Mesas ocupadas" value="—" icon={Armchair}
                        hint="disponible con el módulo de operación" />
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Ventas</CardTitle>
                            <CardDescription>
                                Ventas del día, ticket promedio y productos más vendidos aparecerán aquí
                                cuando esté listo el punto de venta.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex h-40 items-center justify-center rounded-md border border-dashed text-sm text-muted-foreground">
                            Sin datos todavía
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Actividad reciente</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {recent.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Sin actividad registrada.</p>
                            ) : (
                                <ul className="grid gap-2 text-sm">
                                    {recent.map((event, index) => (
                                        <li key={index} className="grid">
                                            <span className="truncate">{event.detail}</span>
                                            <span className="text-xs text-muted-foreground">
                                                {event.user ?? '—'} · {when(event.at)}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

AdminDashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }],
};
