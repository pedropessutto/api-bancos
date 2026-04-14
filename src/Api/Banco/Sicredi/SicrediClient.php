<?php

namespace PedroPessutto\ApiBancos\Api\Banco\Sicredi;

use Illuminate\Support\Facades\Cache;
use PedroPessutto\ApiBancos\Api\BancoClient;
use PedroPessutto\ApiBancos\Exception\ValidationException;

class SicrediClient extends BancoClient
{
    protected $agencia;
    protected $posto;
    protected $codigo_acesso_beneficiario;
    protected $beneficiario_numero;
    protected $api_token;

    protected $camposObrigatorios = [
        'agencia',
        'posto',
        'codigo_acesso_beneficiario',
        'beneficiario_numero',
        'api_token',
    ];

    public function getAgencia()
    {
        return $this->agencia;
    }

    public function getPosto()
    {
        return $this->posto;
    }

    public function getCodigoAcessoBeneficiario()
    {
        return $this->codigo_acesso_beneficiario;
    }

    public function getBeneficiarioNumero()
    {
        return $this->beneficiario_numero;
    }

    public function getApiToken()
    {
        return $this->api_token;
    }

    protected function oAuth2()
    {
        $url = 'https://api-parceiro.sicredi.com.br/auth/openapi/token';
        $refreshTokenCache = Cache::get($this->getRefreshTokenCacheKey());

        if ($refreshTokenCache) {
            $body = [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshTokenCache,
            ];
        } else {
            $body = [
                'grant_type' => 'password',
                'username'   => $this->getBeneficiarioNumero() . $this->getAgencia(),
                'password'   => $this->getCodigoAcessoBeneficiario(),
                'scope'      => 'cobranca',
            ];
        }

        $response = $this->requestPost($url, $body, true)->body;

        if (isset($response->refresh_token) && isset($response->refresh_expires_in)) {
            Cache::put($this->getRefreshTokenCacheKey(), $response->refresh_token, $response->refresh_expires_in);
        }

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
                'x-api-key'          => $this->getApiToken(),
                'Authorization'      => $this->getAccessToken(),
                'Content-Type'       => 'application/json',
                'cooperativa'        => $this->getAgencia(),
                'posto'              => $this->getPosto(),
                'codigoBeneficiario' => $this->getBeneficiarioNumero(),
            ]
            : [
                'x-api-key'    => $this->getApiToken(),
                'Content-Type' => 'application/x-www-form-urlencoded',
                'context'      => 'COBRANCA',
            ];
    }
}
