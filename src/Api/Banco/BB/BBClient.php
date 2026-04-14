<?php

namespace PedroPessutto\ApiBancos\Api\Banco\BB;

use Illuminate\Support\Facades\Cache;
use PedroPessutto\ApiBancos\Api\BancoClient;
use PedroPessutto\ApiBancos\Exception\ValidationException;

class BBClient extends BancoClient
{
    protected $gw_dev_app_key;

    protected $convenio_numero;

    protected $camposObrigatorios = [
        'client_id',
        'client_secret',
        'gw_dev_app_key',
        'convenio_numero',
    ];

    public function getGwDevAppKey()
    {
        return $this->gw_dev_app_key;
    }

    public function getConvenioNumero()
    {
        return $this->convenio_numero;
    }

    protected function oAuth2()
    {
        $accessTokenCache = Cache::get($this->getAccessTokenCacheKey());

        if ($accessTokenCache) {
            return $this->setAccessToken('Bearer ' . $accessTokenCache);
        }

        $response = $this->requestPost('https://oauth.bb.com.br/oauth/token', [
            'grant_type' => 'client_credentials',
            'scope'      => 'cobrancas.boletos-info cobrancas.boletos-requisicao',
        ], true)->body;

        if (! isset($response->access_token)) {
            throw new ValidationException('Erro ao localizar access token');
        }

        if (isset($response->expires_in)) {
            Cache::put($this->getAccessTokenCacheKey(), $response->access_token, $response->expires_in);
        }

        return $this->setAccessToken('Bearer ' . $response->access_token);
    }

    protected function headers()
    {
        return $this->getAccessToken()
            ? [
                'Authorization' => $this->getAccessToken(),
                'Content-Type'  => 'application/json',
            ]
            : [
                'Authorization' => 'Basic ' . base64_encode($this->getClientId() . ':' . $this->getClientSecret()),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ];
    }
}
