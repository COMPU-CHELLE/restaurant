import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type Props = {
    label: string;
    htmlFor?: string;
    error?: string;
    hint?: string;
    className?: string;
    children: ReactNode;
};

/** Etiqueta + control + ayuda + error de validación. */
export function Field({ label, htmlFor, error, hint, className, children }: Props) {
    return (
        <div className={cn('grid gap-2', className)}>
            <Label htmlFor={htmlFor}>{label}</Label>
            {children}
            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
            <InputError message={error} />
        </div>
    );
}
