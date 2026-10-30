import { Head, useForm } from '@inertiajs/react';
import { ImagePlus, Trash2 } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { ChangeEvent, FormEvent } from 'react';
import { Field } from '@/components/field';
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
import { Spinner } from '@/components/ui/spinner';

type CompanyData = {
    restaurant_name: string;
    business_name: string | null;
    tax_id: string | null;
    invoice_prefix: string | null;
    phone: string | null;
    email: string | null;
    address: string | null;
    city: string | null;
    country: string | null;
    currency: string;
    timezone: string;
    tax_percentage: number;
    logo_url: string | null;
};

type Props = {
    company: CompanyData;
    timezones: string[];
};

type EmpresaForm = {
    restaurant_name: string;
    business_name: string;
    tax_id: string;
    invoice_prefix: string;
    phone: string;
    email: string;
    address: string;
    city: string;
    country: string;
    currency: string;
    timezone: string;
    tax_percentage: string;
    logo: File | null;
    remove_logo: boolean;
};

export default function Empresa({ company, timezones }: Props) {
    const form = useForm<EmpresaForm>({
        restaurant_name: company.restaurant_name ?? '',
        business_name: company.business_name ?? '',
        tax_id: company.tax_id ?? '',
        invoice_prefix: company.invoice_prefix ?? '',
        phone: company.phone ?? '',
        email: company.email ?? '',
        address: company.address ?? '',
        city: company.city ?? '',
        country: company.country ?? '',
        currency: company.currency ?? 'COP',
        timezone: company.timezone ?? 'America/Bogota',
        tax_percentage: String(company.tax_percentage ?? 0),
        logo: null,
        remove_logo: false,
    });

    const fileInput = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(company.logo_url);

    // Tras guardar, el servidor devuelve la URL nueva del logo.
    useEffect(() => setPreview(company.logo_url), [company.logo_url]);

    const pickLogo = (event: ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0] ?? null;

        form.setData((data) => ({ ...data, logo: file, remove_logo: false }));

        if (file) {
            setPreview(URL.createObjectURL(file));
        }
    };

    const removeLogo = () => {
        form.setData((data) => ({ ...data, logo: null, remove_logo: true }));
        setPreview(null);

        if (fileInput.current) {
            fileInput.current.value = '';
        }
    };

    const submit = (event: FormEvent) => {
        event.preventDefault();

        form.post('/admin/empresa', {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () =>
                form.setData((data) => ({ ...data, logo: null, remove_logo: false })),
        });
    };

    const set = (key: keyof EmpresaForm) => (event: ChangeEvent<HTMLInputElement>) =>
        form.setData(key, event.target.value as never);

    return (
        <>
            <Head title="Datos de la empresa" />

            <form onSubmit={submit} className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h1 className="text-xl font-semibold">Datos de la empresa</h1>
                        <p className="text-sm text-muted-foreground">
                            Información del restaurante: identidad, datos fiscales y regionales.
                        </p>
                    </div>
                    <Button type="submit" disabled={form.processing}>
                        {form.processing && <Spinner />}
                        Guardar cambios
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Identidad</CardTitle>
                        <CardDescription>Nombre y logo del restaurante.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-[160px_1fr]">
                        <div className="flex flex-col items-center gap-2">
                            <div className="flex size-32 items-center justify-center overflow-hidden rounded-lg border bg-muted/40">
                                {preview ? (
                                    <img src={preview} alt="Logo" className="size-full object-contain" />
                                ) : (
                                    <ImagePlus className="size-8 text-muted-foreground" />
                                )}
                            </div>
                            <input
                                ref={fileInput}
                                type="file"
                                accept="image/png,image/jpeg,image/webp"
                                className="hidden"
                                onChange={pickLogo}
                            />
                            <div className="flex gap-1">
                                <Button type="button" variant="outline" size="sm"
                                    onClick={() => fileInput.current?.click()}>
                                    {preview ? 'Cambiar' : 'Subir logo'}
                                </Button>
                                {preview && (
                                    <Button type="button" variant="ghost" size="icon" className="size-8"
                                        onClick={removeLogo} aria-label="Quitar logo">
                                        <Trash2 className="size-4" />
                                    </Button>
                                )}
                            </div>
                            <p className="text-center text-xs text-muted-foreground">
                                PNG, JPG o WEBP. Máximo 2 MB.
                            </p>
                            {form.errors.logo && (
                                <p className="text-center text-sm text-red-600 dark:text-red-400">
                                    {form.errors.logo}
                                </p>
                            )}
                        </div>

                        <div className="grid content-start gap-4">
                            <Field label="Nombre del restaurante" htmlFor="restaurant_name"
                                error={form.errors.restaurant_name}>
                                <Input id="restaurant_name" value={form.data.restaurant_name}
                                    onChange={set('restaurant_name')} />
                            </Field>
                            <Field label="Razón social" htmlFor="business_name" error={form.errors.business_name}>
                                <Input id="business_name" value={form.data.business_name}
                                    onChange={set('business_name')} />
                            </Field>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Fiscal</CardTitle>
                        <CardDescription>Datos para facturación e impuestos.</CardDescription>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-3">
                        <Field label="NIT" htmlFor="tax_id" error={form.errors.tax_id}>
                            <Input id="tax_id" value={form.data.tax_id} onChange={set('tax_id')} />
                        </Field>
                        <Field label="Prefijo de facturación" htmlFor="invoice_prefix"
                            error={form.errors.invoice_prefix}>
                            <Input id="invoice_prefix" value={form.data.invoice_prefix} maxLength={10}
                                onChange={(e) => form.setData('invoice_prefix', e.target.value.toUpperCase())} />
                        </Field>
                        <Field label="Impuesto (%)" htmlFor="tax_percentage" error={form.errors.tax_percentage}>
                            <Input id="tax_percentage" type="number" min={0} max={100} step="0.01"
                                value={form.data.tax_percentage} onChange={set('tax_percentage')} />
                        </Field>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Contacto y ubicación</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <Field label="Teléfono" htmlFor="phone" error={form.errors.phone}>
                            <Input id="phone" value={form.data.phone} onChange={set('phone')} />
                        </Field>
                        <Field label="Correo" htmlFor="email" error={form.errors.email}>
                            <Input id="email" type="email" value={form.data.email} onChange={set('email')} />
                        </Field>
                        <Field label="Dirección" htmlFor="address" error={form.errors.address} className="sm:col-span-2">
                            <Input id="address" value={form.data.address} onChange={set('address')} />
                        </Field>
                        <Field label="Ciudad" htmlFor="city" error={form.errors.city}>
                            <Input id="city" value={form.data.city} onChange={set('city')} />
                        </Field>
                        <Field label="País" htmlFor="country" error={form.errors.country}>
                            <Input id="country" value={form.data.country} onChange={set('country')} />
                        </Field>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Regional</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <Field label="Moneda" htmlFor="currency" error={form.errors.currency}>
                            <Input id="currency" value={form.data.currency} maxLength={3}
                                onChange={(e) => form.setData('currency', e.target.value.toUpperCase())} />
                        </Field>
                        <Field label="Zona horaria" error={form.errors.timezone}>
                            <Select value={form.data.timezone}
                                onValueChange={(value) => form.setData('timezone', value)}>
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent className="max-h-72">
                                    {timezones.map((zone) => (
                                        <SelectItem key={zone} value={zone}>
                                            {zone}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </Field>
                    </CardContent>
                </Card>
            </form>
        </>
    );
}

Empresa.layout = {
    breadcrumbs: [
        { title: 'Administración', href: '/admin/empresa' },
        { title: 'Empresa', href: '/admin/empresa' },
    ],
};
