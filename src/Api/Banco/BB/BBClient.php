<?php

namespace PedroPessutto\ApiBancos\Api\Banco\BB;

use Illuminate\Support\Facades\Cache;
use PedroPessutto\ApiBancos\Api\BancoClient;
use PedroPessutto\ApiBancos\Exception\ValidationException;

class BBClient extends BancoClient
{
    protected $version = 2;

    protected $camposObrigatorios = [
        'client_id',
        'client_secret',
        'api_token',
        'scope',
        'certificado',
        'certificadoChave',
        'certificadoSenha',
    ];

    protected function oAuth2()
    {
        if ($this->getAccessToken()) {
            return $this;
        }

        // Homologação
        // $response = $this->requestPost('https://oauth.hm.bb.com.br/oauth/token', [
        //     'grant_type' => 'client_credentials',
        //     'scope'      => $this->getScope(),
        // ], true)->body;

        // Produção
        $response = $this->requestPost('https://oauth.bb.com.br/oauth/token', [
            'grant_type' => 'client_credentials',
            'scope'      => $this->getScope(),
        ], true)->body;

        if (! isset($response->access_token)) {
            throw new ValidationException('Erro ao localizar access token');
        }

        return $this->setAccessToken($response->access_token, $response->expires_in ?? 0);
    }

    protected function headers()
    {
        return $this->getAccessToken()
            ? [
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
                'Content-Type'  => 'application/json',
            ]
            : [
                'Authorization' => 'Basic ' . base64_encode($this->getClientId() . ':' . $this->getClientSecret()),
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ];
    }

    public function getVersion()
    {
        return $this->version;
    }
}
