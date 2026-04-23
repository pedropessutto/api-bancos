<?php

namespace PedroPessutto\ApiBancos\Api\Banco\Sicredi;

use PedroPessutto\ApiBancos\Api\Contracts\AbstractCobranca;
use Eduardokum\LaravelBoleto\Contracts\Boleto\BoletoAPI as BoletoAPIContract;
use Illuminate\Support\Arr;

class SicrediCobranca extends AbstractCobranca
{
    protected SicrediClient $client;

    protected $baseUrl = 'https://api-parceiro.sicredi.com.br/cobranca/boleto/v1/boletos';

    public function __construct($params = [])
    {
        $this->client = new SicrediClient(array_merge($params, [
            'scope' => 'cobranca',
        ]));
        
        parent::__construct($params);
    }

    protected function oAuth2()
    {
        return $this->client->oAuth2();
    }

    protected function headers()
    {
        return [];
    }

    public function cancelNossoNumero($nossoNumero, $motivo = null)
    {
        return $this->authenticate()->requestPatch($this->url('baixa', $nossoNumero), [])->body;
    }

    public function cancelNossoNumeroProtesto($nossoNumero)
    {
        return $this->authenticate()->requestPatch($this->url('sustar-protesto-baixar-titulo', $nossoNumero), [])->body;
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

    public function url($type, $param = null)
    {
        $aUrls = [
            1 => [
                'baixa' => $param . '/baixa',
                'sustar-protesto-baixar-titulo' => $param . '/sustar-protesto-baixar-titulo',
            ]
        ];

        return Arr::get($aUrls, "{$this->client->getVersion()}.$type");
    }
}
