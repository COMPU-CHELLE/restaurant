import { Head } from '@inertiajs/react';

export default function ModulePlaceholder({ title }: { title: string }) {
    return (
        <>
            <Head title={title} />
            <div className="flex flex-1 flex-col gap-2 p-4">
                <h1 className="text-xl font-semibold">{title}</h1>
                <p className="text-sm text-muted-foreground">
                    Este módulo se construye en las siguientes fases de la
                    migración. Si ves esta página, el menú, el tenant y los
                    permisos ya funcionan.
                </p>
            </div>
        </>
    );
}
