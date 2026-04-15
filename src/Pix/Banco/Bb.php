<?php

namespace PedroPessutto\ApiBancos\Pix\Banco;

use PedroPessutto\ApiBancos\Pix\AbstractPix;
use PedroPessutto\ApiBancos\Contracts\Pix as PixContract;

class Bb extends AbstractPix implements PixContract
{
    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->setCamposObrigatorios('transactionId', 'chave', 'tipoChave', 'valor');
    }

    /**
     * Código do banco
     *
     * @var string
     */
    protected $codigoBanco = self::COD_BANCO_BB;
}
