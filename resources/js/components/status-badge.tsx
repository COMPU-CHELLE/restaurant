import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

export function StateBadge({ state }: { state: number }) {
    return state === 1 ? (
        <Badge
            variant="secondary"
            className="bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
        >
            Activo
        </Badge>
    ) : (
        <Badge variant="outline" className="text-muted-foreground">
            Inactivo
        </Badge>
    );
}

const provision: Record<string, { label: string; className: string }> = {
    pending: {
        label: 'Pendiente',
        className: 'bg-slate-100 text-slate-700 dark:bg-slate-900 dark:text-slate-300',
    },
    queued: {
        label: 'En cola',
        className: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
    },
    running: {
        label: 'Aprovisionando',
        className: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
    },
    ready: {
        label: 'Lista',
        className: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
    },
    failed: {
        label: 'Con error',
        className: 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
    },
};

export function ProvisionBadge({ status }: { status: string }) {
    const item = provision[status] ?? provision.pending;

    return (
        <Badge variant="secondary" className={cn(item.className)}>
            {item.label}
        </Badge>
    );
}
