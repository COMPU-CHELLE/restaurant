import { Form, Head, usePage } from '@inertiajs/react';
import { UtensilsCrossed } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';

type Props = {
    status?: string;
};

export default function Login({ status }: Props) {
    const { name } = usePage().props;

    return (
        <>
            <Head title="Iniciar sesión" />

            <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted/40 p-6">
                <div className="flex items-center gap-2 text-lg font-semibold">
                    <div className="flex size-9 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <UtensilsCrossed className="size-5" />
                    </div>
                    {name}
                </div>

                <Card className="w-full max-w-sm">
                    <CardHeader className="text-center">
                        <CardTitle className="text-xl">
                            Iniciar sesión
                        </CardTitle>
                        <CardDescription>
                            Ingresa tu código de usuario y tu contraseña
                        </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <Form
                            {...store.form()}
                            resetOnSuccess={['password']}
                            className="grid gap-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {status && (
                                        <p className="text-center text-sm font-medium text-green-600">
                                            {status}
                                        </p>
                                    )}

                                    <div className="grid gap-2">
                                        <Label htmlFor="code">Código</Label>
                                        <Input
                                            id="code"
                                            name="code"
                                            required
                                            autoFocus
                                            tabIndex={1}
                                            autoComplete="username"
                                            autoCapitalize="none"
                                            spellCheck={false}
                                            placeholder="Tu código de usuario"
                                        />
                                        <InputError message={errors.code} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password">
                                            Contraseña
                                        </Label>
                                        <PasswordInput
                                            id="password"
                                            name="password"
                                            required
                                            tabIndex={2}
                                            autoComplete="current-password"
                                            placeholder="Contraseña"
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="remember"
                                            name="remember"
                                            tabIndex={3}
                                        />
                                        <Label
                                            htmlFor="remember"
                                            className="font-normal"
                                        >
                                            Mantener la sesión iniciada
                                        </Label>
                                    </div>

                                    <Button
                                        type="submit"
                                        className="w-full"
                                        tabIndex={4}
                                        disabled={processing}
                                        data-test="login-button"
                                    >
                                        {processing && <Spinner />}
                                        Entrar
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
