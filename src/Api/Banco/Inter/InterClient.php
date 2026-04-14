<?php

namespace PedroPessutto\ApiBancos\Api\Banco\Inter;

use Illuminate\Support\Arr;
use PedroPessutto\ApiBancos\Api\BancoClient;

class InterClient extends BancoClient
{
    protected $baseUrl = 'https://apis.bancointer.com.br';

    private $version = 1;

    protected $camposObrigatorios = [
        'conta',
        'certificado',
        'certificadoChave',
    ];

    public function __construct($params = [])
    {
        if (isset($params['versao']) && in_array($params['versao'], [2, 3])) {
            $this->version = $params['versao'];
            $this->camposObrigatorios = [
                'certificado',
                'certificadoChave',
                'client_id',
                'client_secret',
            ];
            $this->baseUrl = 'https://cdpj.partners.bancointer.com.br';
        }

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
            'scope'         => 'boleto-cobranca.read boleto-cobranca.write',
            'grant_type'    => 'client_credentials',
        ], true)->body;

        return $this->setAccessToken('Bearer ' . $grant->access_token);
    }

    protected function headers()
    {
        if ($this->version != 1) {
            return array_filter([
                'Authorization' => $this->getAccessToken(),
            ]);
        }

        return [
            'x-inter-conta-corrente' => $this->getConta(),
        ];
    }

    public function url($type, $param = null)
    {
        $aUrls = [
            1 => [
                'create' => 'openbanking/v1/certificado/boletos',
                'show'   => 'openbanking/v1/certificado/boletos/' . $param,
                'cancel' => 'openbanking/v1/certificado/boletos/' . $param . '/baixas',
                'pdf'    => 'openbanking/v1/certificado/boletos/' . $param . '/pdf',
                'search' => 'openbanking/v1/certificado/boletos?',
            ],
            2 => [
                'create'  => 'cobranca/v2/boletos',
                'show'    => 'cobranca/v2/boletos/' . $param,
                'cancel'  => 'cobranca/v2/boletos/' . $param . '/cancelar',
                'pdf'     => 'cobranca/v2/boletos/' . $param . '/pdf',
                'search'  => 'cobranca/v2/boletos?',
                'auth'    => '/oauth/v2/token',
                'webhook' => 'cobranca/v2/boletos/webhook',
            ],
            3 => [
                'create'  => 'cobranca/v3/cobrancas',
                'show'    => 'cobranca/v3/cobrancas/' . $param,
                'cancel'  => 'cobranca/v3/cobrancas/' . $param . '/cancelar',
                'pdf'     => 'cobranca/v3/cobrancas/' . $param . '/pdf',
                'search'  => 'cobranca/v3/cobrancas?',
                'auth'    => '/oauth/v2/token',
                'webhook' => 'cobranca/v3/cobrancas/webhook',
            ],
        ];

        return Arr::get($aUrls, "$this->version.$type");
    }
}
