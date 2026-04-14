<?php

namespace PedroPessutto\ApiBancos\Api\Banco;

use PedroPessutto\ApiBancos\Api\Exception\CurlException;
use PedroPessutto\ApiBancos\Api\Exception\HttpException;
use PedroPessutto\ApiBancos\Api\Exception\UnauthorizedException;
use Illuminate\Support\Facades\Cache;
use PedroPessutto\ApiBancos\Api\AbstractAPI;
use PedroPessutto\ApiBancos\Exception\ValidationException;
use PedroPessutto\ApiBancos\Contracts\Boleto\BoletoAPI as BoletoAPIContract;

class Bb extends AbstractAPI
{
    protected $gw_dev_app_key;

    protected $convenio_numero;

    protected $camposObrigatorios = [
        'client_id',
        'client_secret',
        'gw_dev_app_key',
        'convenio_numero',
    ];

    public function __construct($params = [])
    {
        parent::__construct($params);
    }

    /**
     * @return mixed
     */
    public function getGwDevAppKey()
    {
        return $this->gw_dev_app_key;
    }

    /**
     * @return mixed
     */
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

        $url = 'https://oauth.bb.com.br/oauth/token';

        $body = [
            'grant_type' => 'client_credentials',
            'scope'      => 'cobrancas.boletos-info cobrancas.boletos-requisicao',
        ];

        $response = $this->post($url, $body, true)->body;

        if (isset($response->access_token)) {
            if (isset($response->expires_in)) {
                Cache::put($this->getAccessTokenCacheKey(), $response->access_token, $response->expires_in);
            }

            return $this->setAccessToken('Bearer ' . $response->access_token);
        } else {
            throw new ValidationException('Erro ao localizar access token');
        }
    }

    /**
     * @return array
     */
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

    /**
     * @param        $nossoNumero
     * @param string $motivo
     *
     * @return mixed
     * @throws CurlException
     * @throws HttpException
     * @throws UnauthorizedException
     * @throws ValidationException
     */
    public function cancelNossoNumero($nossoNumero, $motivo = null)
    {
        $this->oAuth2();

        $baseUrl = "https://api.bb.com.br/cobrancas/v2/boletos/{$nossoNumero}/baixar";

        $queryParams = http_build_query([
            'gw-dev-app-key' => $this->getGwDevAppKey(),
        ]);

        $url = "{$baseUrl}?{$queryParams}";

        $body = [
            'numeroConvenio' => $this->getConvenioNumero(),
        ];

        return $this->post($url, $body)->body;
    }

    public function createBoleto(BoletoAPIContract $boleto)
    {
        // TODO: Implement createBoleto() method.
    }

    public function retrieveNossoNumero($nossoNumero)
    {
        // TODO: Implement retrieveNossoNumero() method.
    }

    public function retrieveID($id)
    {
        // TODO: Implement retrieveID() method.
    }

    public function cancelID($id, $motivo)
    {
        // TODO: Implement cancelID() method.
    }

    public function retrieveList($inputedParams = [])
    {
        // TODO: Implement retrieveList() method.
    }

    public function getPdfNossoNumero($nossoNumero)
    {
        // TODO: Implement getPdfNossoNumero() method.
    }

    public function getPdfID($id)
    {
        // TODO: Implement getPdfID() method.
    }
}
