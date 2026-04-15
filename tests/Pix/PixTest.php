<?php

namespace PedroPessutto\ApiBancos\Tests\Pix;

use Carbon\Carbon;
use Eduardokum\LaravelBoleto\Pessoa;
use Illuminate\Support\Str;
use PHPUnit\Framework\Constraint\IsType;
use PedroPessutto\ApiBancos\Tests\TestCase;
use PedroPessutto\ApiBancos\Pix\Banco as Pix;
use PedroPessutto\ApiBancos\Pix\AbstractPix;

class PixTest extends TestCase
{
    protected static $devedor;

    public static function setUpBeforeClass(): void
    {
        self::$devedor = new Pessoa([
            'nome' => 'ACME',
            'endereco' => 'Rua um, 123',
            'cep' => '99999-999',
            'uf' => 'UF',
            'cidade' => 'CIDADE',
            'documento' => '99.999.999/9999-99',
        ]);
    }

    public function validatePixBB()
    {
        $valor = $this->valor();

        $pix = new Pix\Bb([
            'transactionId' => (string) Str::uuid(),
            'devedor' => self::$devedor,
            'valor' => $valor,
            'chave' => '95127446000198',
            'tipoChave' => AbstractPix::TIPO_CHAVEPIX_CNPJ,
            'descricao' => $this->descricao(),
            'expiresAt' => Carbon::now()->addDays(rand(1, 30)),
        ]);

        $this->assertThat($pix->toArray(), (new IsType(IsType::TYPE_ARRAY)));

        $this->assertThat($pix->getTransactionId(), (new IsType(IsType::TYPE_STRING)));
        $this->assertThat($pix->getChave(), (new IsType(IsType::TYPE_STRING)));
        $this->assertThat($pix->getTipoChave(), (new IsType(IsType::TYPE_STRING)));
        $this->assertThat($pix->getDescricao(), (new IsType(IsType::TYPE_STRING)));
        $this->assertThat($pix->getDevedor(), (new IsType(IsType::TYPE_OBJECT)));
        $this->assertThat($pix->getExpiresAt(), (new IsType(IsType::TYPE_OBJECT)));
        $this->assertEquals($valor, $pix->getValor());        
    }

    public function testPixBB()
    {
        $valor = $this->valor();

        $pix = new Pix\Bb([
            'transactionId' => (string) Str::uuid(),
            'devedor' => self::$devedor,
            'valor' => $valor,
            'chave' => '95127446000198',
            'tipoChave' => AbstractPix::TIPO_CHAVEPIX_CNPJ,
            'descricao' => $this->descricao(),
            'expiresAt' => Carbon::now()->addDays(rand(1, 30)),
        ]);

        // TODO - gerar PIX, consultar, gerar QRCode e verificar se o pix foi criado com sucesso


    }
}
