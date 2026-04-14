<?php

namespace PedroPessutto\ApiBancos\Api\Banco\BB;

use PedroPessutto\ApiBancos\Api\Contracts\AbstractPix;

class BBPix extends AbstractPix
{
    protected BBClient $client;
    protected $baseUrl = 'https://api-pix.hm.bb.com.br/pix/v2'; // Homologação
    // protected $baseUrl = 'https://api-pix.bb.com.br/pix/v2'; // Produção

    public function __construct($params = [])
    {
        $this->client = new BBClient($params);
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

    public function criarCobranca(array $dados)
    {

    }

    public function consultarCobranca(string $id)
    {

    }

    public function listarCobrancas(array $filtros = [])
    {

    }
    
    public function gerarQrCode(string $id)
    {

    }

    public function cancelarCobranca(string $id)
    {

    }
}
