<?php

namespace PedroPessutto\ApiBancos\Pix\Banco;

use Carbon\Carbon;
use Eduardokum\LaravelBoleto\Pessoa;
use Illuminate\Support\Arr;
use PedroPessutto\ApiBancos\Pix\AbstractPix;
use PedroPessutto\ApiBancos\Contracts\Pix as PixContract;
use PedroPessutto\ApiBancos\Util;

class Bb extends AbstractPix implements PixContract
{
    public function __construct(array $params = [])
    {
        parent::__construct($params);
        $this->setCamposObrigatorios('transactionId', 'chave', 'valor');
    }

    /**
     * Código do banco
     *
     * @var string
     */
    protected $codigoBanco = self::COD_BANCO_BB;

    // Classe para métodos específicos do BB
    // Exemplo:
    // Gerar algum código único, validar retorno do banco, etc.

    public static function fromAPI($pix, $appends = [])
    {
        $pixData = json_decode(json_encode($pix), true);

        $devedor = new Pessoa([
            'nome' => isset($pixData['devedor']['nome']) ? $pixData['devedor']['nome'] : null,
            'cpf' => isset($pixData['devedor']['cpf']) ? Util::onlyNumbers($pixData['devedor']['cpf']) : null,
            'cnpj' => isset($pixData['devedor']['cnpj']) ? Util::onlyNumbers($pixData['devedor']['cnpj']) : null,
        ]);

        $createdAt = Carbon::parse($pixData['calendario']['criacao']);
        $expiresAt = clone $createdAt;
        $expiresAt->addSeconds($pixData['calendario']['expiracao']);

        $aSituacao = [
            'ATIVA' => AbstractPix::SITUACAO_ATIVA,
            'CONCLUIDA' => AbstractPix::SITUACAO_CONCLUIDA,
            'REMOVIDA_PELO_USUARIO_RECEBEDOR' => AbstractPix::SITUACAO_CANCELADA,
            'REMOVIDA_PELO_PSP' => AbstractPix::SITUACAO_CANCELADA,
        ];

        $situacao = Arr::get($aSituacao, $pixData['status'], $pixData['status']);

        if ($expiresAt->isPast() && $situacao != AbstractPix::SITUACAO_CONCLUIDA) {
            $situacao = AbstractPix::SITUACAO_EXPIRADA;
        }

        return new self(array_merge(array_filter([
            'expiresAt' => $expiresAt,
            'createdAt' => $createdAt,
            'transactionId' => $pixData['txid'],
            'valor' => $pixData['valor']['original'],
            'chave' => $pixData['chave'],
            'descricao' => $pixData['solicitacaoPagador'],
            'pixCopiaECola' => $pixData['pixCopiaECola'],
            'devedor' => $devedor,
            'situacao' => $situacao,
        ]), $appends));
    }

    public function pixToArray()
    {
        $devedor = [];
       
        if ($this->getDevedor()) {
            if (strlen($cpf = Util::onlyNumbers($this->getDevedor()->getDocumento())) == 11) {
                $devedor['cpf'] = $cpf;
            } else {
                $devedor['cnpj'] = Util::onlyNumbers($this->getDevedor()->getDocumento());
            }

            $devedor['nome'] = $this->getDevedor()->getNome();
        }

        return array_filter([
            'expires_at' => $this->getExpiresAt(),
            'created_at' => $this->getCreatedAt(),
            'devedor' => $devedor,
            'valor' => [
                'original' => $this->getValor(),
                'modalidadeAlteracao' => 0,
                // 'retirada' => []
            ],
            'chave' => $this->getChave(),
            'txid' => $this->getTransactionId(),
            'pixCopiaECola' => $this->getPixCopiaECola(),
            'solicitacaoPagador' => $this->getDescricao(),
        ]);
    }
}
