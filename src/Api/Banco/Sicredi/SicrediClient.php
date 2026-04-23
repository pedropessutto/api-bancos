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
        'scope',
    ];

    protected function oAuth2()
    {
        if ($this->getAccessToken()) {
            return $this;
        }

        if ($this->getRefreshToken()) {
            $body = [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->getRefreshToken(),
                'scope'         => $this->getScope(),
            ];
        }
        else {
            $body = [
                'grant_type' => 'password',
                'username'   => $this->getBeneficiarioNumero() . $this->getAgencia(),
                'password'   => $this->getCodigoAcessoBeneficiario(),
                'scope'      => $this->getScope(),
            ];
        }

        $response = $this->requestPost('https://api-parceiro.sicredi.com.br/auth/openapi/token', $body, true)->body;

        if (! isset($response->access_token)) {
            throw new ValidationException('Erro ao localizar access token');
        }

        if (isset($response->refresh_token) && isset($response->refresh_expires_in)) {
            $this->setRefreshToken($response->refresh_token, $response->refresh_expires_in);
        }

        return $this->setAccessToken($response->access_token, $response->expires_in ?? 0);
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
