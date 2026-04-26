<?php

namespace PedroPessutto\ApiBancos\Api\Banco\Inter;

use Illuminate\Support\Arr;
use PedroPessutto\ApiBancos\Api\BancoClient;

class InterClient extends BancoClient
{
    private $version = 1;

    protected $camposObrigatorios = [
        'client_id',
        'client_secret',
        'scope',
    ];

    public function __construct($params = [])
    {
        $this->version = $params['versao'] ?? 1;

        parent::__construct($params);
    }

    public function getVersion()
    {
        return $this->version;
    }

    protected function oAuth2()
    {
        if ($this->version == 1 || $this->getAccessToken()) {
            return $this;
        }

        $grant = $this->requestPost($this->url('auth'), [
            'client_id'     => $this->getClientId(),
            'client_secret' => $this->getClientSecret(),
            'scope'         => $this->getScope(),
            'grant_type'    => 'client_credentials',
        ], true)->body;

        return $this->setAccessToken($grant->access_token, $grant->expires_in ?? 0);
    }

    protected function headers()
    {
        if ($this->version != 1) {
            return array_filter([
                'Authorization' => 'Bearer ' . $this->getAccessToken(),
            ]);
        }

        return [
            'x-inter-conta-corrente' => $this->getConta(),
        ];
    }
}
