<?php

namespace App\Services\Factus;

use App\Models\Support\FactusPayService as Config;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente de Factus Pay. El host SIEMPRE sale de config('factus.pay_defaults'), nunca de la base de datos.
 * Las rutas de detalle y los nombres de campos de la respuesta deben confirmarse contra la documentación
 * vigente en sandbox (ver normalize()).
 */
class FactusPayService
{
    public const MIN_AMOUNT = 1000;
    public const MAX_AMOUNT = 12100000;

    public function host(Config $config): string
    {
        $host = config('factus.pay_defaults.' . $config->environment);

        if (! $host) {
            throw new RuntimeException('Ambiente de Factus Pay no válido.');
        }

        return rtrim((string) $host, '/');
    }

    /** Autentica y guarda el token. $fresh = ignora el token guardado. */
    public function authenticate(Config $config, bool $fresh = false): string
    {
        if ($config->token && ! $fresh) {
            return $config->token;
        }

        $r = Http::acceptJson()->timeout(20)->post($this->host($config) . '/auth', [
            'email' => $config->email,
            'password' => $config->password,
        ]);

        if (! $r->successful() || ! $r->json('token')) {
            throw new RuntimeException('Factus Pay: autenticación rechazada (HTTP ' . $r->status() . ').');
        }

        $config->forceFill(['token' => $r->json('token')]);

        if ($config->exists) { // una configuración de prueba (formulario sin guardar) no se persiste
            $config->save();
        }

        return $r->json('token');
    }

    /** Prueba de conexión: siempre pide un token nuevo. */
    public function testLogin(Config $config): array
    {
        try {
            $this->authenticate($config, true);

            return ['success' => true];
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function createCollection(Config $config, string $reference, float $amount, string $method = 'QR'): array
    {
        if ($amount < self::MIN_AMOUNT || $amount > self::MAX_AMOUNT) {
            throw new RuntimeException('El recaudo Factus Pay debe estar entre $1.000 y $12.100.000 COP.');
        }

        $r = $this->send($config, fn (PendingRequest $http) => $http->asJson()->post(
            $this->host($config) . '/v1/collections',
            ['reference_code' => $reference, 'amount' => round($amount, 2)],
        ));

        if (! $r->successful()) {
            throw new RuntimeException('Factus Pay no pudo crear el recaudo (HTTP ' . $r->status() . ').');
        }

        return $this->normalize($r->json() ?? []);
    }

    public function showCollection(Config $config, string $reference): array
    {
        $r = $this->send($config, fn (PendingRequest $http) => $http->get(
            $this->host($config) . '/v1/collections/' . rawurlencode($reference),
        ));

        if (! $r->successful()) {
            throw new RuntimeException('Factus Pay no pudo consultar el recaudo (HTTP ' . $r->status() . ').');
        }

        return $this->normalize($r->json() ?? []);
    }

    /** Si el token guardado expiró (401), se renueva una sola vez y se repite la llamada. */
    private function send(Config $config, callable $call): Response
    {
        $make = fn (bool $fresh) => $call(Http::withToken($this->authenticate($config, $fresh))->acceptJson()->timeout(30));

        $response = $make(false);

        return $response->status() === 401 ? $make(true) : $response;
    }

    /**
     * Unifica la respuesta de Factus Pay. Los alias de campos son defensivos: confirma los nombres reales
     * en sandbox y deja solo los que apliquen.
     *
     * @return array{id:?string,status:string,qr:?string,qr_image:?string,expires_at:?string,paid_at:?string,raw:array}
     */
    public function normalize(array $body): array
    {
        $d = $body['data'] ?? $body;
        $raw = strtolower((string) ($d['status'] ?? $d['state'] ?? 'pending'));

        $status = match (true) {
            in_array($raw, ['paid', 'approved', 'completed', 'success', 'succeeded'], true) => 'paid',
            in_array($raw, ['expired'], true) => 'expired',
            in_array($raw, ['failed', 'rejected', 'error'], true) => 'failed',
            in_array($raw, ['cancelled', 'canceled'], true) => 'cancelled',
            default => 'pending',
        };

        return [
            'id' => isset($d['id']) ? (string) $d['id'] : null,
            'status' => $status,
            'qr' => $d['qr'] ?? $d['qr_code'] ?? $d['qr_string'] ?? null,
            'qr_image' => $d['qr_image'] ?? $d['qr_url'] ?? null,
            'expires_at' => $d['expires_at'] ?? $d['expiration_date'] ?? null,
            'paid_at' => $d['paid_at'] ?? null,
            'raw' => $body,
        ];
    }
}
