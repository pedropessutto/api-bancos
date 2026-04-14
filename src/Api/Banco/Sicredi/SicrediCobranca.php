<?php

namespace PedroPessutto\ApiBancos\Api\Banco\Sicredi;

use PedroPessutto\ApiBancos\Api\Contracts\AbstractCobranca;
use Eduardokum\LaravelBoleto\Contracts\Boleto\BoletoAPI as BoletoAPIContract;

class SicrediCobranca extends AbstractCobranca
{
    protected SicrediClient $client;

    protected $baseUrl = 'https://api-parceiro.sicredi.com.br/cobranca/boleto/v1/boletos';

    public function __construct($params = [])
    {
        $this->client = new SicrediClient($params);
        parent::__construct([]);
    }

    protected function oAuth2()
    {
        return $this->client->authenticate();
    }

    protected function headers()
    {
        return [];
    }

    public function cancelNossoNumero($nossoNumero, $motivo = null)
    {
        $this->client->authenticate();

        return $this->client->requestPatch("{$nossoNumero}/baixa", [])->body;
    }

    public function cancelNossoNumeroProtesto($nossoNumero)
    {
        $this->client->authenticate();

        return $this->client->requestPatch("{$nossoNumero}/sustar-protesto-baixar-titulo", [])->body;
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
