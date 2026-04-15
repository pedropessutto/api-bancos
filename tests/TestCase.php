<?php

namespace PedroPessutto\ApiBancos\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;

class TestCase extends OrchestraTestCase
{
    protected function vencimento()
    {
        return (new \Carbon\Carbon())->addDays(rand(0, 365));
    }

    protected function valor()
    {
        return mt_rand(100, 30000);
    }

    protected function descricao()
    {
        return 'Teste de pix - ' . rand(10000, 99999);
    }
}
