<?php

namespace PedroPessutto\ApiBancos\Contracts;
use Eduardokum\LaravelBoleto\Contracts\Pessoa as PessoaContract;
use Carbon\Carbon;

interface Pix
{
    const COD_BANCO_BB = '001';

    /**
     * @return string
     */
    public function getTransactionId(); // txid

    /**
     * @return Carbon
     */
    public function getExpiresAt();

    /**
     * @return PessoaContract
     */
    public function getDevedor();

    /**
     * @return float
     */
    public function getValor();

    /**
     * @return string
     */
    public function getChave();

    /**
     * @return string
     */
    public function getTipoChave();

    /**
     * @return string
     */
    public function getDescricao();

}
