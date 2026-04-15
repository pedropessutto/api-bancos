<?php

namespace PedroPessutto\ApiBancos\Pix;

use Exception;
use Throwable;
use Carbon\Carbon;
use Illuminate\Support\Str;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Eduardokum\LaravelBoleto\Util;
use chillerlan\QRCode\Data\QRMatrix;
use Eduardokum\LaravelBoleto\MagicTrait;
use chillerlan\QRCode\Output\QROutputInterface;
use Eduardokum\LaravelBoleto\Contracts\Pessoa as PessoaContract;
use PedroPessutto\ApiBancos\Contracts\Pix as PixContract;
use PedroPessutto\ApiBancos\Exception\ValidationException;

/**
 * Class AbstractPix
 */
abstract class AbstractPix implements PixContract
{
    use MagicTrait;

    const SITUACAO_ATIVO = 'ativo';
    const SITUACAO_CONCLUIDO = 'concluido';
    const SITUACAO_CANCELADO_USUARIO = 'cancelado_usuario';
    const SITUACAO_CANCELADO_BANCO = 'cancelado_banco';

    const TIPO_CHAVEPIX_CPF = 'cpf';
    const TIPO_CHAVEPIX_CNPJ = 'cnpj';
    const TIPO_CHAVEPIX_CELULAR = 'celular';
    const TIPO_CHAVEPIX_EMAIL = 'email';
    const TIPO_CHAVEPIX_ALEATORIA = 'aleatoria';
    
    const QRCODE_ESTILO_QUADRADO = 'square';
    const QRCODE_ESTILO_PONTO = 'dot';
    
    // TODO - ajustar restante da classe

    /**
     * Campos necessários para o pix
     *
     * @var array
     */
    private $camposObrigatorios = [
        'transactionId',
        'chave',
        'tipoChave',
        'valor'
    ];

    protected $protectedFields = [
        // 'transactionId',
        // 'chave'
    ];

    /**
     * @var string
     */
    protected $transactionId; // txid

    /**
     * Data de expiração do pix
     *
     * @var Carbon
     */
    protected $expiresAt;

    /**
     * Devedor
     *
     * @var PessoaContract
     */
    protected $devedor;

    /**
     * Valor do pix
     *
     * @var float
     */
    public $valor;

    /**
     * Chave do pix
     *
     * @var float
     */
    public $chave;

    /**
     * Tipo da chave do pix
     *
     * @var string
     */
    public $tipoChave;

    /**
     * Descrição do pix
     *
     * @var string
     */
    public $descricao;

    /**
     * Situação do pix no banco. Ativo, Pago, Cancelado...
     *
     * @var string
     */
    public $situacao;

    /**
     * Data de criação do pix
     *
     * @var Carbon
     */
    public $createdAt;

    /**
     * Código do banco
     *
     * @var string
     */
    protected $codigoBanco;

    // /**
    //  * Agência
    //  *
    //  * @var string
    //  */
    // protected $agencia;

    // /**
    //  * Dígito da agência
    //  *
    //  * @var string
    //  */
    // protected $agenciaDv;

    // /**
    //  * Conta
    //  *
    //  * @var string
    //  */
    // protected $conta;

    // /**
    //  * Dígito da conta
    //  *
    //  * @var string
    //  */
    // protected $contaDv;

    // /**
    //  * Entidade beneficiária (quem emite o pix)
    //  *
    //  * @var PessoaContract
    //  */
    // public $beneficiario;

    // /**
    //  * Status do pix, se vai criar, cancelar ou alterar.
    //  *
    //  * @var int
    //  */
    // public $status = BoletoContract::STATUS_REGISTRO;

    /**
     * Data da situação
     *
     * @var Carbon
     */
    public $dataSituacao;

    /**
     * Valor Recebido
     */
    public $valorRecebido;

    /**
     * @var string
     */
    private $qrCodeStyle = self::QRCODE_ESTILO_QUADRADO;

    /**
     * Data de processamento do pix
     *
     * @var Carbon
     */
    public $dataProcessamento;

    /**
     * Data de vencimento do pix
     *
     * @var Carbon
     */
    public $dataVencimento;

    /**
     * AbstractPix constructor.
     *
     * @param array $params
     */
    public function __construct($params = [])
    {
        Util::fillClass($this, $params);
    
        // Marca a data de processamento para hoje, caso não especificada
        if (! $this->getDataProcessamento()) {
            $this->setDataProcessamento(new Carbon());
        }
    }

    /**
     * @return array
     */
    public function getProtectedFields()
    {
        return $this->protectedFields;
    }

    /**
     * Seta os campos obrigatórios
     *
     * @return $this
     */
    protected function setCamposObrigatorios()
    {
        $args = func_get_args();
        $this->camposObrigatorios = [];
        foreach ($args as $arg) {
            $this->addCampoObrigatorio($arg);
        }

        return $this;
    }

    /**
     * Adiciona os campos obrigatórios
     *
     * @return $this
     */
    protected function addCampoObrigatorio()
    {
        $args = func_get_args();
        foreach ($args as $arg) {
            ! is_array($arg) || call_user_func_array([$this, __FUNCTION__], $arg);
            ! is_string($arg) || array_push($this->camposObrigatorios, $arg);
        }

        return $this;
    }

    /**
     * @param $transactionId
     * @return AbstractPix
     * @throws ValidationException
     */
    public function setTransactionId($transactionId)
    {
        // $this->transactionId = $this->validateTransactionId($transactionId);
        $this->transactionId = $transactionId;

        return $this;
    }

    /**
     * @return string
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * Define a data de expiração do pix
     *
     * @param Carbon $expiresAt
     *
     * @return AbstractPix
     */
    public function setExpiresAt(Carbon $expiresAt)
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    /**
     * Retorna a data de expiração do pix
     *
     * @return Carbon
     */
    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    /**
     * Define o devedor do pix
     *
     * @param PessoaContract $devedor
     *
     * @return AbstractPix
     */
    public function setDevedor(PessoaContract $devedor)
    {
        $this->devedor = $devedor;

        return $this;
    }

    /**
     * Retorna o devedor do pix
     *
     * @return PessoaContract
     */
    public function getDevedor()
    {
        return $this->devedor;
    }

    /**
     * Define o valor do pix
     *
     * @param float $valor
     *
     * @return AbstractPix
     * @throws ValidationException
     */
    public function setValor($valor)
    {
        $this->valor = Util::nFloat($valor, 2, false);

        return $this;
    }

    /**
     * Retorna o valor do pix
     *
     * @return float
     */
    public function getValor()
    {
        return Util::nFloat($this->valor, 2, false);
    }

    /**
     * Define a chave do pix
     *
     * @param string $chave
     *
     * @return AbstractPix
     */
    public function setChave($chave)
    {
        $this->chave = $chave;

        return $this;
    }

    /**
     * Retorna a chave do pix
     *
     * @return string
     */
    public function getChave()
    {
        return $this->chave;
    }

    /**
     * Define o tipo da chave do pix
     *
     * @param string $tipoChave
     *
     * @return AbstractPix
     */
    public function setTipoChave($tipoChave)
    {
        $this->tipoChave = $tipoChave;

        return $this;
    }

    /**
     * Retorna o tipo da chave do pix
     *
     * @return string
     */
    public function getTipoChave()
    {
        return $this->tipoChave;

        return $this;
    }

    /**
     * Define a descrição do pix
     *
     * @param string $descricao
     *
     * @return AbstractPix
     */
    public function setDescricao($descricao)
    {
        $this->descricao = $descricao;

        return $this;
    }

    /**
     * Retorna a descrição do pix
     *
     * @return string
     */
    public function getDescricao()
    {
        return $this->descricao;
    }

    /**
     * Define a situação do pix
     *
     * @param string $situacao
     *
     * @return AbstractPix
     */
    public function setSituacao($situacao)
    {
        $this->situacao = $situacao;

        return $this;
    }

    /**
     * Retorna a situação do pix
     *
     * @return string
     */
    public function getSituacao()
    {
        return $this->situacao;
    }

    /**
     * Define a data de criação do pix
     *
     * @param Carbon $createdAt
     *
     * @return AbstractPix
     */
    public function setCreatedAt(Carbon $createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Retorna a data de criação do pix
     *
     * @return Carbon
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Retorna o código do banco
     *
     * @return string
     */
    public function getCodigoBanco()
    {
        return $this->codigoBanco;
    }

    // /**
    //  * Define a entidade beneficiária (quem emite o pix)
    //  *
    //  * @param PessoaContract $beneficiario
    //  *
    //  * @return AbstractPix
    //  */
    // public function setBeneficiario(PessoaContract $beneficiario)
    // {
    //     $this->beneficiario = $beneficiario;

    //     return $this;
    // }

    // /**
    //  * Retorna a entidade beneficiária (quem emite o pix)
    //  *
    //  * @return PessoaContract
    //  */
    // public function getBeneficiario()
    // {
    //     return $this->beneficiario;
    // }

    /**
     * Define a data da situação do pix
     *
     * @param Carbon $dataSituacao
     *
     * @return AbstractPix
     */
    public function setDataSituacao(Carbon $dataSituacao)
    {
        $this->dataSituacao = $dataSituacao;

        return $this;
    }

    /**
     * Retorna a data da situação do pix
     *
     * @return Carbon
     */
    public function getDataSituacao()
    {
        return $this->dataSituacao;
    }

    /**
     * Define o valor recebido do pix
     *
     * @param float $valorRecebido
     *
     * @return AbstractPix
     */
    public function setValorRecebido($valorRecebido)
    {
        $this->valorRecebido = $valorRecebido;

        return $this;
    }

    /**
     * Retorna o valor recebido do pix
     *
     * @return float
     */
    public function getValorRecebido()
    {
        return $this->valorRecebido;
    }

    /**
     * Define o estilo do QR Code
     *
     * @param string $qrCodeStyle
     *
     * @return AbstractPix
     */
    public function setQrCodeStyle($qrCodeStyle)
    {
        $this->qrCodeStyle = $qrCodeStyle;

        return $this;
    }

    /**
     * Retorna o estilo do QR Code
     *
     * @return string
     */
    public function getQrCodeStyle()
    {
        return $this->qrCodeStyle;
    }

    /**
     * Define a data de processamento do pix
     *
     * @param Carbon $dataProcessamento
     *
     * @return AbstractPix
     */
    public function setDataProcessamento(Carbon $dataProcessamento)
    {
        $this->dataProcessamento = $dataProcessamento;

        return $this;
    }

    /**
     * Retorna a data de processamento do pix
     *
     * @return Carbon
     */
    public function getDataProcessamento()
    {
        return $this->dataProcessamento;
    }

    /**
     * Define a data de vencimento
     *
     * @param Carbon $dataVencimento
     *
     * @return AbstractPix
     */
    public function setDataVencimento(Carbon $dataVencimento)
    {
        $this->dataVencimento = $dataVencimento;

        return $this;
    }

    /**
     * Retorna a data de vencimento
     *
     * @return Carbon
     */
    public function getDataVencimento()
    {
        return $this->dataVencimento;
    }
   
    /**
     * Método que valida se o banco tem todos os campos obrigatórios preenchidos
     *
     * @param $messages
     *
     * @return bool
     */
    public function isValid(&$messages)
    {
        foreach ($this->camposObrigatorios as $campo) {
            $test = call_user_func([$this, 'get' . Str::camel($campo)]);
            if ($test === '' || is_null($test)) {
                $messages .= "Campo $campo está em branco";

                return false;
            }
        }

        if (empty($this->getTransactionId())) {
            $messages .= 'Transaction ID está em branco';

            return false;
        }

        if (empty($this->getChave())) {
            $messages .= 'Chave PIX está em branco';

            return false;
        }

        return true;
    }

    // /**
    //  * @return ?string
    //  */
    // public function getQrCodeBase64()
    // {
    //     if ($this->getQrCode() == null) {
    //         return null;
    //     }
    //     if (Util::isBase64($this->getPixQrCode())) {
    //         return 'data://text/plain;base64,' . $this->getPixQrCode();
    //     }

    //     if (Str::startsWith($this->getPixQrCode(), 'data:')) {
    //         return $this->getPixQrCode();
    //     }

    //     $options = new QROptions;

    //     if (defined('\chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG')) {
    //         $options->outputType = QRCode::OUTPUT_IMAGE_PNG;
    //         $options->eccLevel = QRCode::ECC_L;
    //     } else {
    //         $options->outputType = QROutputInterface::GDIMAGE_PNG;
    //         $options->addQuietzone = true;
    //     }

    //     $options->scale = 20;
    //     $options->quietzoneSize = 1;
    //     $options->drawLightModules = false;

    //     if ($this->getQrCodeStyle() == self::QRCODE_ESTILO_PONTO) {
    //         $options->drawCircularModules = true;
    //         $options->circleRadius = .5;
    //         $options->keepAsSquare = [
    //             QRMatrix::M_FINDER_DOT,
    //             QRMatrix::M_FINDER_DARK,
    //         ];
    //     }
    //     $qrCode = new QRCode($options);

    //     return $qrCode->render($this->getPixQrCode());
    // }

    // /**
    //  * @param string $pixQrCode
    //  */
    // public function setPixQrCode($pixQrCode)
    // {
    //     $this->pixQrCode = $pixQrCode;
    // }
    
    /**
     * @param $situacao
     *
     * @return bool
     */
    public function isSituacao($situacao)
    {
        return $this->situacao == $situacao;
    }

    /**
     * @return bool
     */
    public function isCancelado()
    {
        return $this->isSituacao(self::SITUACAO_CANCELADO_USUARIO) || $this->isSituacao(self::SITUACAO_CANCELADO_BANCO);
    }

    /**
     * @return bool
     */
    public function isAtivo()
    {
        return $this->isSituacao(self::SITUACAO_ATIVO);
    }

    /**
     * @return bool
     */
    public function isConcluido()
    {
        return $this->isSituacao(self::SITUACAO_CONCLUIDO);
    }

    /**
     * @return bool
     * 
     * @throws ValidationException
     */
    public function validar()
    {
        if ($this->getChave() || $this->getTipoChave()) {
            if (! $this->getChave()) {
                throw new ValidationException('Informado tipo de chave de Pix porém não foi informado a chave');
            }
            if (! $this->getTipoChave()) {
                throw new ValidationException('Informado tipo de chave de Pix porém não foi informado a chave');
            }

            switch ($this->getTipoChave()) {
                case self::TIPO_CHAVEPIX_CPF:
                    if (! Util::validarCpf($this->getChave())) {
                        throw new ValidationException(sprintf('Chave do tipo CPF é invalida: %s', $this->getChave()));
                    }
                    break;
                case self::TIPO_CHAVEPIX_CNPJ:
                    if (! Util::validarCnpj($this->getChave())) {
                        throw new ValidationException(sprintf('Chave do tipo CNPJ é invalida: %s', $this->getChave()));
                    }
                    break;
                case self::TIPO_CHAVEPIX_EMAIL:
                    if (! filter_var($this->getChave(), FILTER_VALIDATE_EMAIL)) {
                        throw new ValidationException(sprintf('Chave do tipo EMAIL é invalida: %s', $this->getChave()));
                    }
                    break;
                case self::TIPO_CHAVEPIX_CELULAR:
                    if (! preg_match('/^(\+\d{2}\s?)?[-.\s]?\(?\d{2}\)?[-.\s]?(\d\s?)?\d{4}[-.\s]?\d{4}$/', $this->getChave())) {
                        throw new ValidationException(sprintf('Chave do tipo CELULAR é invalida: %s', $this->getChave()));
                    }
                    break;
                case self::TIPO_CHAVEPIX_ALEATORIA:
                    if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $this->getChave())) {
                        throw new ValidationException(sprintf('Chave do tipo ALEATÓRIA é invalida: %s', $this->getChave()));
                    }
                    break;
            }

            return true;
        }

        return false;
    }

    /**
     * Return Pix Array.
     *
     * @return array
     * @throws ValidationException
     */
    public function toArray()
    {
        $this->validar();

        return [
            'transaction_id'        => $this->getTransactionId(),
            'chave'                 => $this->getChave(),
            'valor'                 => $this->getValor(),
            'expires_at'            => $this->getExpiresAt(),
            'descricao'             => $this->getDescricao(),
            'situacao'              => $this->getSituacao(),
            'data_situacao'         => $this->getDataSituacao(),
            'valor_recebido'        => $this->getValorRecebido(),
            'data_processamento'    => $this->getDataProcessamento(),
            'data_vencimento'       => $this->getDataVencimento(),
            'created_at'            => $this->getCreatedAt(),
            'devedor'               => [
                'nome'              => $this->getDevedor()->getNome(),
                'endereco'          => $this->getDevedor()->getEndereco(),
                'bairro'            => $this->getDevedor()->getBairro(),
                'cep'               => $this->getDevedor()->getCep(),
                'uf'                => $this->getDevedor()->getUf(),
                'cidade'            => $this->getDevedor()->getCidade(),
                'documento'         => $this->getDevedor()->getDocumento(),
                'nome_documento'    => $this->getDevedor()->getNomeDocumento(),
                'endereco2'         => $this->getDevedor()->getCepCidadeUf(),
                'endereco_completo' => $this->getDevedor()->getEnderecoCompleto(),
                'fone'              => $this->getDevedor()->getFone(),
                'email'             => $this->getDevedor()->getEmail(),
            ],
            // 'qrcode'                => $this->getQrCode(),
            // 'qrcode_image'          => $this->getQrCodeBase64(),
        ];
    }
}
