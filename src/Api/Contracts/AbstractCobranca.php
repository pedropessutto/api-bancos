<?php

namespace PedroPessutto\ApiBancos\Api\Contracts;

use Eduardokum\LaravelBoleto\Contracts\Boleto\BoletoAPI as BoletoAPIContract;
use PedroPessutto\ApiBancos\Api\AbstractApi;
use PedroPessutto\ApiBancos\Exception\ValidationException;

abstract class AbstractCobranca extends AbstractApi
{
    protected $camposObrigatorios = [];

    abstract public function createBoleto(BoletoAPIContract $boleto);

    abstract public function retrieveNossoNumero($nossoNumero);

    abstract public function retrieveID($id);

    abstract public function cancelNossoNumero($nossoNumero, $motivo);

    abstract public function cancelID($id, $motivo);

    abstract public function retrieveList($inputedParams = []);

    abstract public function getPdfNossoNumero($nossoNumero);

    abstract public function getPdfID($id);

    public function createWebhook($url, $type = 'all')
    {
        throw new ValidationException('Método não disponível no banco');
    }

    public function retrieve(BoletoAPIContract $boleto)
    {
        return $this->retrieveNossoNumero($boleto->getNossoNumero());
    }

    public function cancel(BoletoAPIContract $boleto, $motivo)
    {
        return $this->cancelNossoNumero($boleto->getNossoNumero(), $motivo);
    }

    public function getPdf(BoletoAPIContract $boleto)
    {
        return $this->getPdfNossoNumero($boleto->getNossoNumero());
    }
}
