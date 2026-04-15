<?php

namespace PedroPessutto\ApiBancos\Api\Contracts;

use PedroPessutto\ApiBancos\Api\AbstractApi;

abstract class AbstractPix extends AbstractApi
{
    protected $camposObrigatorios = [];

    // /**
    //  * Cria uma cobrança Pix (imediata ou com vencimento)
    //  */
    // abstract public function criarCobranca(array $dados);

    // /**
    //  * Consulta uma cobrança pelo id
    //  */
    // abstract public function consultarCobranca(string $id);

    // /**
    //  * Lista cobranças com filtros
    //  */
    // abstract public function listarCobrancas(array $filtros = []);

    // /**
    //  * Gera QR Code (ou retorna payload EMV)
    //  */
    // abstract public function gerarQrCode(string $id);

    // /**
    //  *  Cancelar / remover cobrança
    //  */
    // abstract public function cancelarCobranca(string $id);
}
