<?php

namespace PedroPessutto\ApiBancos\Contracts;
use Eduardokum\LaravelBoleto\Contracts\Pessoa as PessoaContract;

interface Pix
{
    const COD_BANCO_BB = '001';

    /**
     * @return mixed
     */
    public function getExpiresAt();

    /**
     * @return PessoaContract
     */
    public function getDevedor();

    /**
     * @return mixed
     */
    public function getValor();

    /**
     * @return mixed
     */
    public function getChave();

    /**
     * @return mixed
     */
    public function getDescricao();

}
