<?php

namespace App\Services\Factus;

use App\Models\Support\FactusService;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\App;

abstract class FactusHttpService
{
    protected FactusService $factus;
    protected FactusAuthService $auth;

    public function __construct()
    {
        $companyId = Session::get('company_id');
        $this->factus = FactusService::where('codemp', $companyId)
            ->where('active', 1)
            ->firstOrFail();
        $this->auth = App::make(FactusAuthService::class);
    }

    protected function client()
    {
        return Http::withToken(
            $this->auth->getToken($this->factus)
        )
        ->acceptJson()
        ->timeout(30);
    }

    protected function baseUrl(): string
    {
        $baseurl = $this->factus->base_url . '/' . $this->factus->api_version;
        return rtrim($baseurl, '/');
    }

    protected function handleResponse($response)
    {
        if (! $response->successful()) {
            throw new \Exception(json_encode([
                'message' => 'Factus API error',
                'status'  => $response->status(),
                'body'    => $response->json() ?? $response->body(),
            ]));
        }

        return $response->json();
    }
}
