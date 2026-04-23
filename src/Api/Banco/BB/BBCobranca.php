<?php

namespace PedroPessutto\ApiBancos\Api\Banco\BB;

use PedroPessutto\ApiBancos\Api\Contracts\AbstractCobranca;
use PedroPessutto\ApiBancos\Api\Exception\CurlException;
use PedroPessutto\ApiBancos\Api\Exception\HttpException;
use PedroPessutto\ApiBancos\Api\Exception\UnauthorizedException;
use Eduardokum\LaravelBoleto\Contracts\Boleto\BoletoAPI as BoletoAPIContract;

class BBCobranca extends AbstractCobranca
{
    protected BBClient $client;
    protected $baseUrl = 'https://api.bb.com.br/cobrancas/v2/boletos';

    public function __construct($params = [])
    {
        $this->client = new BBClient(array_merge($params, [
            'scope' => 'cobrancas.boletos-info cobrancas.boletos-requisicao',
        ]));

        parent::__construct($params);
    }

    protected function oAuth2()
    {
        return $this->client->authenticate();
    }

    protected function headers()
    {
        return [];
    }

    /**
     * @throws CurlException
     * @throws HttpException
     * @throws UnauthorizedException
     */
    public function cancelNossoNumero($nossoNumero, $motivo = null)
    {
        $this->client->authenticate();

        $queryParams = http_build_query([
            'gw-dev-app-key' => $this->client->getGwDevAppKey(),
        ]);
        
        $url = "{$nossoNumero}/baixar?{$queryParams}";

        return $this->client->requestPost($url, [
            'numeroConvenio' => $this->client->getConvenioNumero(),
        ])->body;
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
