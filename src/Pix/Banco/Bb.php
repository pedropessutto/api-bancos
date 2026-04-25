<?php

namespace PedroPessutto\ApiBancos\Pix\Banco;

use Carbon\Carbon;
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
        $createdAt = Carbon::parse($pix['calendario']['criacao']);
        $expiresAt = clone $createdAt;
        $expiresAt->addSeconds($pix['calendario']['expiracao']);

        $aSituacao = [
            'ATIVA' => AbstractPix::SITUACAO_ATIVA,
            'CONCLUIDA' => AbstractPix::SITUACAO_CONCLUIDA,
            'REMOVIDA_PELO_USUARIO_RECEBEDOR' => AbstractPix::SITUACAO_CANCELADA,
            'REMOVIDA_PELO_PSP' => AbstractPix::SITUACAO_CANCELADA,
        ];

        $situacao = Arr::get($aSituacao, $pix['status'], $pix['status']);

        if ($expiresAt->isPast() && $situacao != AbstractPix::SITUACAO_CONCLUIDA) {
            $situacao = AbstractPix::SITUACAO_EXPIRADA;
        }

        return new self(array_merge(array_filter([
            'expiresAt' => $expiresAt,
            'createdAt' => $createdAt,
            'transactionId' => $pix['txid'],
            'valor' => $pix['valor']['original'],
            'chave' => $pix['chave'],
            'descricao' => $pix['solicitacaoPagador'],
            'pixCopiaECola' => $pix['pixCopiaECola'],
            'devedor' => [
                'nome' => $pix['devedor']['nome'],
                'cpf' => Util::onlyNumbers($pix['devedor']['cpf']),
                'cnpj' => Util::onlyNumbers($pix['devedor']['cnpj']),
            ],
            'situacao' => $situacao,
        ]), $appends));
    }

    public function pixToArray()
    {
        if ($this->getDevedor()) {
            $devedor = [
                'cpf' => Util::onlyNumbers($this->getDevedor()->getDocumento()),
                'cnpj' => Util::onlyNumbers($this->getDevedor()->getDocumento()),
                'nome' => $this->getDevedor()->getNome(),
            ];
        }
        else {
            $devedor = [
                'cpf' => null,
                'cnpj' => null,
                'nome' => null,
            ];
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
