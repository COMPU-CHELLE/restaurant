import { Head, router } from '@inertiajs/react';
import { Building2, Plug, ScrollText, Users, Globe, Map, MapPinned } from 'lucide-react';
import { ProvisionBadge, StateBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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

type CompanyRow = {
    id: number;
    code: string;
    name: string;
    state: number;
    provision_status: string;
    users: number;
    factus: { environment: string; token: 'none' | 'valid' | 'expired' } | null;
    last_activity: string | null;
};

type Detail = {
    id: number;
    code: string;
    name: string;
    nit: string;
    database: string | null;
    admins: number;
    operators: number;
    profiles: number | null;
    catalog_items: number | null;
    recent: { type: string; detail: string; user: string | null; at: string | null }[];
};

type Props = {
    stats: {
        companies: { total: number; active: number; ready: number; attention: number };
        users: { total: number; active: number };
        factus: { active: number };
        events24h: number;
    };
    companies: CompanyRow[];
    selected: Detail | null;
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

export default function SoporteDashboard({ stats, companies, selected }: Props) {
    return (
        <>
            <Head title="Dashboard" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Dashboard de soporte</h1>
                    <p className="text-sm text-muted-foreground">Estado de la plataforma y de cada empresa.</p>
                </div>

                <div className="grid gap-3 sm:grid-cols-3">
                    {[
                        { title: 'Países', description: 'Catálogo de países e IDs Factus', href: '/soporte/paises', icon: Globe },
                        { title: 'Zonas', description: 'Departamentos y regiones por país', href: '/soporte/zonas', icon: Map },
                        { title: 'Subzonas', description: 'Municipios y localidades por zona', href: '/soporte/subzonas', icon: MapPinned },
                    ].map((item) => (
                        <button key={item.href} type="button" onClick={() => router.visit(item.href)} className="flex items-center gap-3 rounded-lg border p-4 text-left transition-colors hover:bg-muted/50">
                            <item.icon className="size-5 text-muted-foreground" />
                            <span><span className="block font-medium">{item.title}</span><span className="block text-xs text-muted-foreground">{item.description}</span></span>
                        </button>
                    ))}
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Stat title="Empresas" value={stats.companies.total} icon={Building2}
                        hint={`${stats.companies.active} activas · ${stats.companies.ready} listas · ${stats.companies.attention} por atender`} />
                    <Stat title="Usuarios" value={stats.users.total} icon={Users}
                        hint={`${stats.users.active} activos`} />
                    <Stat title="Factus" value={stats.factus.active} icon={Plug}
                        hint="configuraciones activas" />
                    <Stat title="Eventos (24 h)" value={stats.events24h} icon={ScrollText}
                        hint="en la bitácora global" />
                </div>

                <Card>
                    <CardHeader className="flex-row flex-wrap items-center justify-between gap-2 space-y-0">
                        <div>
                            <CardTitle>Datos por empresa</CardTitle>
                            <CardDescription>Elige una empresa para ver su detalle.</CardDescription>
                        </div>
                        <Select
                            value={selected ? String(selected.id) : undefined}
                            onValueChange={(value) =>
                                router.get('/dashboard', { company: value }, { preserveScroll: true, preserveState: true })
                            }
                        >
                            <SelectTrigger className="w-64">
                                <SelectValue placeholder="Empresa" />
                            </SelectTrigger>
                            <SelectContent>
                                {companies.map((company) => (
                                    <SelectItem key={company.id} value={String(company.id)}>
                                        {company.code} · {company.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </CardHeader>

                    {selected ? (
                        <CardContent className="grid gap-4 lg:grid-cols-2">
                            <div className="grid gap-3">
                                <div>
                                    <p className="font-medium">{selected.name}</p>
                                    <p className="text-xs text-muted-foreground">
                                        NIT {selected.nit} · {selected.database ?? 'sin base de datos'}
                                    </p>
                                </div>
                                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    {[
                                        ['Administradores', selected.admins],
                                        ['Usuarios', selected.operators],
                                        ['Perfiles', selected.profiles ?? '—'],
                                        ['Parámetros', selected.catalog_items ?? '—'],
                                    ].map(([label, value]) => (
                                        <div key={label} className="rounded-md border p-3">
                                            <div className="text-xl font-semibold">{value}</div>
                                            <div className="text-xs text-muted-foreground">{label}</div>
                                        </div>
                                    ))}
                                </div>
                            </div>

                            <div>
                                <p className="mb-2 text-sm font-medium">Actividad reciente</p>
                                {selected.recent.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">Sin actividad registrada.</p>
                                ) : (
                                    <ul className="grid gap-1.5 text-sm">
                                        {selected.recent.map((event, index) => (
                                            <li key={index} className="flex justify-between gap-3">
                                                <span className="truncate">{event.detail}</span>
                                                <span className="shrink-0 text-xs text-muted-foreground">
                                                    {event.user ?? '—'} · {when(event.at)}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>
                        </CardContent>
                    ) : (
                        <CardContent className="text-sm text-muted-foreground">
                            Aún no hay empresas.
                        </CardContent>
                    )}
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Empresas</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Empresa</TableHead>
                                    <TableHead>Estado</TableHead>
                                    <TableHead>Aprovisionamiento</TableHead>
                                    <TableHead>Usuarios</TableHead>
                                    <TableHead>Factus</TableHead>
                                    <TableHead className="pr-6">Última actividad</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {companies.map((company) => (
                                    <TableRow key={company.id} className="cursor-pointer"
                                        onClick={() => router.get('/dashboard', { company: company.id }, { preserveScroll: true, preserveState: true })}>
                                        <TableCell className="pl-6">
                                            <span className="font-medium">{company.name}</span>{' '}
                                            <span className="font-mono text-xs text-muted-foreground">{company.code}</span>
                                        </TableCell>
                                        <TableCell><StateBadge state={company.state} /></TableCell>
                                        <TableCell><ProvisionBadge status={company.provision_status} /></TableCell>
                                        <TableCell>{company.users}</TableCell>
                                        <TableCell>
                                            {company.factus ? (
                                                <Badge variant="outline">
                                                    {company.factus.environment === 'production' ? 'Producción' : 'Pruebas'}
                                                    {company.factus.token === 'valid' ? ' · conectado' : ''}
                                                </Badge>
                                            ) : (
                                                <span className="text-xs text-muted-foreground">Sin configurar</span>
                                            )}
                                        </TableCell>
                                        <TableCell className="pr-6 text-xs">{when(company.last_activity)}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

SoporteDashboard.layout = {
    breadcrumbs: [{ title: 'Dashboard', href: '/dashboard' }],
};
