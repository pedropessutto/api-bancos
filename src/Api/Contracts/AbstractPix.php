<?php

namespace PedroPessutto\ApiBancos\Api\Contracts;

use PedroPessutto\ApiBancos\Api\AbstractApi;
use PedroPessutto\ApiBancos\Contracts\Pix as PixContract;
use PedroPessutto\ApiBancos\Exception\ValidationException;

abstract class AbstractPix extends AbstractApi
{
    protected $camposObrigatorios = [];

    abstract public function createPix(PixContract $pix, string $transactionId = null);

    abstract public function retrieveTransactionId(string $transactionId);

    abstract public function updateTransactionId(string $transactionId, PixContract $pix);

    abstract public function deleteTransactionId(string $transactionId);
    
    abstract public function qrCodePix(PixContract $pix);

    public function retrieve(PixContract $pix)
    {
        return $this->retrieveTransactionId($pix->getTransactionId());
    }   

    public function update(PixContract $pix)
    {
        return $this->updateTransactionId($pix->getTransactionId(), $pix);
    }

    public function delete(PixContract $pix)
    {
        return $this->deleteTransactionId($pix->getTransactionId());
    }

    // TODO - Implementar
    // public function createWebhook($url, $type = 'all')
    // {
    //     throw new ValidationException('Método não disponível no banco');
    // }
}
