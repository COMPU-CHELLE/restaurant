import { router } from '@inertiajs/react';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    ChevronLeft,
    ChevronRight,
    Search,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
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

export type Column<T> = {
    key: string;
    header: string;
    sortable?: boolean;
    className?: string;
    cell: (row: T) => ReactNode;
};

type Props<T> = {
    /** URL de la lista: ahí se envían búsqueda, orden y página. */
    url: string;
    data: Paginated<T>;
    filters: TableFilters;
    columns: Column<T>[];
    rowKey: (row: T) => string | number;
    searchPlaceholder?: string;
    /** Filtros extra (por ejemplo, el de estado) a la derecha de la búsqueda. */
    toolbar?: ReactNode;
    emptyText?: string;
};

type Visit = Partial<TableFilters> & { page?: number };

/**
 * Tabla con búsqueda, orden y paginación hechas en el servidor (Inertia).
 * Reutilizable para todos los maestros.
 */
export function DataTable<T>({
    url,
    data,
    filters,
    columns,
    rowKey,
    searchPlaceholder = 'Buscar…',
    toolbar,
    emptyText = 'No hay registros.',
}: Props<T>) {
    const [search, setSearch] = useState(filters.search ?? '');
    const first = useRef(true);

    const visit = (extra: Visit) => {
        const params: Record<string, unknown> = { ...filters, ...extra };
        const clean = Object.fromEntries(
            Object.entries(params).filter(
                ([, value]) =>
                    value !== null && value !== undefined && value !== '',
            ),
        );

        router.get(url, clean as Record<string, string | number>, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    // Búsqueda con pequeña espera para no consultar en cada tecla.
    useEffect(() => {
        if (first.current) {
            first.current = false;

            return;
        }

        const timer = setTimeout(() => {
            if ((filters.search ?? '') !== search) {
                visit({ search, page: 1 });
            }
        }, 350);

        return () => clearTimeout(timer);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const toggleSort = (key: string) => {
        const direction =
            filters.sort === key && filters.direction === 'asc'
                ? 'desc'
                : 'asc';

        visit({ sort: key, direction, page: 1 });
    };

    return (
        <div className="flex flex-col gap-4">
            <div className="flex flex-wrap items-center gap-2">
                <div className="relative w-full sm:max-w-xs">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder={searchPlaceholder}
                        className="pl-9"
                    />
                </div>
                {toolbar}
            </div>

            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            {columns.map((column) => {
                                const active = filters.sort === column.key;
                                const Icon = !active
                                    ? ArrowUpDown
                                    : filters.direction === 'asc'
                                      ? ArrowUp
                                      : ArrowDown;

                                return (
                                    <TableHead
                                        key={column.key}
                                        className={column.className}
                                    >
                                        {column.sortable ? (
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    toggleSort(column.key)
                                                }
                                                className="inline-flex items-center gap-1 hover:text-foreground/80"
                                            >
                                                {column.header}
                                                <Icon
                                                    className={cn(
                                                        'size-3.5',
                                                        !active &&
                                                            'text-muted-foreground',
                                                    )}
                                                />
                                            </button>
                                        ) : (
                                            column.header
                                        )}
                                    </TableHead>
                                );
                            })}
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {data.data.length === 0 ? (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="h-24 text-center text-muted-foreground"
                                >
                                    {emptyText}
                                </TableCell>
                            </TableRow>
                        ) : (
                            data.data.map((row) => (
                                <TableRow key={rowKey(row)}>
                                    {columns.map((column) => (
                                        <TableCell
                                            key={column.key}
                                            className={column.className}
                                        >
                                            {column.cell(row)}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>

            <div className="flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground">
                <span>
                    {data.total === 0
                        ? 'Sin resultados'
                        : `Mostrando ${data.from}–${data.to} de ${data.total}`}
                </span>

                <div className="flex items-center gap-3">
                    <div className="flex items-center gap-2">
                        <span className="hidden sm:inline">Por página</span>
                        <Select
                            value={String(filters.per_page ?? 15)}
                            onValueChange={(value) =>
                                visit({ per_page: Number(value), page: 1 })
                            }
                        >
                            <SelectTrigger className="h-8 w-[70px]">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {[10, 15, 25, 50].map((size) => (
                                    <SelectItem key={size} value={String(size)}>
                                        {size}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="flex items-center gap-1">
                        <Button
                            variant="outline"
                            size="icon"
                            className="size-8"
                            disabled={data.current_page <= 1}
                            onClick={() =>
                                visit({ page: data.current_page - 1 })
                            }
                            aria-label="Página anterior"
                        >
                            <ChevronLeft className="size-4" />
                        </Button>
                        <span className="px-2">
                            {data.current_page} / {Math.max(data.last_page, 1)}
                        </span>
                        <Button
                            variant="outline"
                            size="icon"
                            className="size-8"
                            disabled={data.current_page >= data.last_page}
                            onClick={() =>
                                visit({ page: data.current_page + 1 })
                            }
                            aria-label="Página siguiente"
                        >
                            <ChevronRight className="size-4" />
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}
