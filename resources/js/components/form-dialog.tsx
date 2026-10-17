import type { FormEvent, ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: string;
    processing?: boolean;
    submitLabel?: string;
    onSubmit: (event: FormEvent<HTMLFormElement>) => void;
    wide?: boolean;
    children: ReactNode;
};

/** Ventana con formulario (crear / editar) reutilizable para todos los maestros. */
export function FormDialog({
    open,
    onOpenChange,
    title,
    description,
    processing = false,
    submitLabel = 'Guardar',
    onSubmit,
    wide = false,
    children,
}: Props) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent
                className={cn(
                    'max-h-[90vh] overflow-y-auto',
                    wide ? 'sm:max-w-2xl' : 'sm:max-w-lg',
                )}
            >
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        onSubmit(event);
                    }}
                    className="grid gap-5"
                >
                    <DialogHeader>
                        <DialogTitle>{title}</DialogTitle>
                        {description && (
                            <DialogDescription>{description}</DialogDescription>
                        )}
                    </DialogHeader>

                    <div className="grid gap-4">{children}</div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => onOpenChange(false)}
                        >
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            {submitLabel}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
