<?php

namespace PedroPessutto\ApiBancos\Api\Banco\Inter;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use PedroPessutto\ApiBancos\Util;
use PedroPessutto\ApiBancos\Api\Contracts\AbstractCobranca;
use PedroPessutto\ApiBancos\Exception\ValidationException;
use Eduardokum\LaravelBoleto\Contracts\Boleto\BoletoAPI as BoletoAPIContract;
use Eduardokum\LaravelBoleto\Boleto\Banco\Inter as BoletoInter;

class InterCobranca extends AbstractCobranca
{
    protected InterClient $client;

    protected $camposObrigatorios = [
        'conta',
        'certificado',
        'certificadoChave',
    ];

    protected $baseUrl = 'https://apis.bancointer.com.br';

    public function __construct($params = [])
    {
        $this->client = new InterClient(array_merge($params, [
            'scope' => 'boleto-cobranca.read boleto-cobranca.write',
        ]));

        if (in_array($this->client->getVersion(), [2, 3])) {
            $this->baseUrl = 'https://cdpj.partners.bancointer.com.br';
          
            $this->camposObrigatorios = [
                'certificado',
                'certificadoChave',
                'client_id',
                'client_secret',
            ];
        }

        parent::__construct($params);
    }

    protected function oAuth2()
    {
        return $this->client->oAuth2();
    }

    protected function headers()
    {
        return [];
    }

    public function createWebhook($url, $type = 'all')
    {
        if ($this->client->getVersion() == 1) {
            throw new ValidationException('Somente versão 2 e 3 da API permite criação de webhooks');
        }
        try {
            $this->authenticate()->requestPut($this->client->getBaseUrl() . $this->url('webhook'), ['webhookUrl' => $url]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function createBoleto(BoletoAPIContract $boleto)
    {
        $data = $boleto->toAPI();
        if ($this->client->getVersion() != 1) {
            unset($data['dataEmissao'], $data['dataLimite']);
            $data['numDiasAgenda'] = (int) $boleto->getDiasBaixaAutomatica();
            $data['pagador']['cpfCnpj'] = $data['pagador']['cnpjCpf'];
            unset($data['pagador']['cnpjCpf']);
        }
        if ($this->client->getVersion() == 3 && isset($data['desconto'])) {
            $data['desconto1'] = $data['desconto'];
            $data['desconto1']['codigoDesconto'] = $data['desconto']['codigo'];
            unset($data['desconto1']['codigo'], $data['desconto']);
        }
        if ($this->client->getVersion() == 2 && isset($data['desconto1'])) {
            $data['desconto'] = $data['desconto1'];
            $data['desconto']['codigo'] = $data['desconto1']['codigoDesconto'];
            unset($data['desconto']['codigoDesconto'], $data['desconto1']);
        }

        $retorno = $this->authenticate()->requestPost($this->url('create'), $data);

        if ($this->client->getVersion() == 3) {
            $retorno = $this->authenticate()->requestGet($this->url('show', $retorno->body->codigoCobranca));
            $boleto->setID($retorno->body->codigoCobranca);
            $boleto->setNossoNumero($retorno->body->boleto->nossoNumero);
            $boleto->setPixQrCode($retorno->body->pix->pixCopiaECola);
        } else {
            $boleto->setNossoNumero($retorno->body->nossoNumero);
        }

        return $boleto;
    }

    public function retrieveList($inputedParams = [])
    {
        $version = $this->client->getVersion();
        $params = array_filter([
            'situacao'       => $version == 1 ? null : Arr::get($inputedParams, 'situacao', 'EXPIRADO,PAGO,EMABERTO,VENCIDO,CANCELADO'),
            'filtrarPor'     => $version == 2 ? null : Arr::get($inputedParams, 'filtrarPor', 'TODOS'),
            'filtrarDataPor' => Arr::get($inputedParams, 'filtrarDataPor', 'VENCIMENTO'),
            'dataInicial'    => Arr::get($inputedParams, 'dataInicial', Carbon::now()->startOfMonth()->format('Y-m-d')),
            'dataFinal'      => Arr::get($inputedParams, 'dataFinal', Carbon::now()->endOfMonth()->format('Y-m-d')),
            'ordenarPor'     => Arr::get($inputedParams, 'ordenarPor', 'NOSSONUMERO'),
            'page'           => $version == 1 ? 0 : null,
            'size'           => $version == 1 ? 100 : null,
            'paginaAtual'    => $version == 2 ? 0 : null,
            'itensPorPagina' => $version == 2 ? 1000 : null,
            'paginacao'      => $version == 3 ? ['paginaAtual' => 0, 'itensPorPagina' => 1000] : null,
        ], function ($v) {
            return ! is_null($v);
        });

        $aRetorno = [];
        if (in_array($version, [1, 2])) {
            do {
                $retorno = $this->authenticate()->requestGet($this->url('search') . http_build_query($params));
                array_push($aRetorno, ...$retorno->body->content);
                if ($version == 1) {
                    $params['page'] += 1;
                } else {
                    $params['paginaAtual'] += 1;
                }
            } while (! $retorno->body->last);
        } else {
            do {
                $retorno = $this->authenticate()->requestGet($this->url('search') . http_build_query($params));
                array_push($aRetorno, ...$retorno->body->cobrancas);
                $params['paginacao']['paginaAtual'] += 1;
            } while (! $retorno->body->ultimaPagina);
        }

        return array_map([$this, 'arrayToBoleto'], $aRetorno);
    }

    public function retrieveNossoNumero($nossoNumero)
    {
        $version = $this->client->getVersion();
        if ($version == 3) {
            throw new ValidationException('Versão 3 da API somente recupera boleto pelo ID da cobrança');
        }
        $response = $this->authenticate()->requestGet($this->url('show', $nossoNumero));
        return $version == 1 ? $response : $response->body;
    }

    public function retrieveID($id)
    {
        if ($this->client->getVersion() != 3) {
            throw new ValidationException('Versão 1 e 2 da API somente recupera boleto pelo nosso número');
        }
        return $this->authenticate()->requestGet($this->url('show', $id))->body;
    }

    public function cancelNossoNumero($nossoNumero, $motivo = 'ACERTOS')
    {
        $version = $this->client->getVersion();
        if ($version == 3) {
            throw new ValidationException('Versão 3 da API somente cancela boleto pelo ID da cobrança');
        }

        $motivosValidos = [
            'ACERTOS',
            'PAGODIRETOAOCLIENTE',
            'SUBSTITUICAO',
            'FALTADESOLUCAO',
            'APEDIDODOCLIENTE',
        ];
        if ($version == 2) {
            $motivosValidos = [
                'ACERTOS',
                'APEDIDODOCLIENTE',
                'DEVOLUCAO',
                'PAGODIRETOAOCLIENTE',
                'SUBSTITUICAO',
            ];
        }

        if (! in_array(Util::upper($motivo), $motivosValidos)) {
            $motivo = 'ACERTOS';
        }

        return $this->authenticate()->requestPost(
            $this->url('cancel', $nossoNumero),
            $version == 1 ? ['codigoBaixa' => $motivo] : ['motivoCancelamento' => $motivo]
        )->body;
    }

    public function cancelID($id, $motivo)
    {
        if ($this->client->getVersion() != 3) {
            throw new ValidationException('Versão 1 e 2 da API somente cancela boleto pelo nosso número');
        }
        return $this->authenticate()->requestPost($this->url('cancel', $id), ['motivoCancelamento' => $motivo])->body;
    }

    public function getPdfNossoNumero($nossoNumero)
    {
        if ($this->client->getVersion() == 3) {
            throw new ValidationException('Versão 3 da API somente recupera PDF pelo ID da cobrança');
        }
        return $this->authenticate()->requestGet($this->url('pdf', $nossoNumero))->body;
    }

    public function getPdfID($id)
    {
        if ($this->client->getVersion() == 3) {
            throw new ValidationException('Versão 1, 2 da API somente recupera PDF pelo nosso número');
        }
        return $this->authenticate()->requestGet($this->url('pdf', $id))->body;
    }

    private function arrayToBoleto($boleto)
    {
        return BoletoInter::fromAPI($boleto, [
            'conta'        => $this->client->getConta(),
            'beneficiario' => $this->client->getBeneficiario(),
        ]);
    }

    public function url($type, $param = null)
    {
        $aUrls = [
            1 => [
                'create' => 'openbanking/v1/certificado/boletos',
                'show'   => 'openbanking/v1/certificado/boletos/' . $param,
                'cancel' => 'openbanking/v1/certificado/boletos/' . $param . '/baixas',
                'pdf'    => 'openbanking/v1/certificado/boletos/' . $param . '/pdf',
                'search' => 'openbanking/v1/certificado/boletos?',
            ],
            2 => [
                'create'  => 'cobranca/v2/boletos',
                'show'    => 'cobranca/v2/boletos/' . $param,
                'cancel'  => 'cobranca/v2/boletos/' . $param . '/cancelar',
                'pdf'     => 'cobranca/v2/boletos/' . $param . '/pdf',
                'search'  => 'cobranca/v2/boletos?',
                'auth'    => '/oauth/v2/token',
                'webhook' => 'cobranca/v2/boletos/webhook',
            ],
            3 => [
                'create'  => 'cobranca/v3/cobrancas',
                'show'    => 'cobranca/v3/cobrancas/' . $param,
                'cancel'  => 'cobranca/v3/cobrancas/' . $param . '/cancelar',
                'pdf'     => 'cobranca/v3/cobrancas/' . $param . '/pdf',
                'search'  => 'cobranca/v3/cobrancas?',
                'auth'    => '/oauth/v2/token',
                'webhook' => 'cobranca/v3/cobrancas/webhook',
            ],
        ];

        return Arr::get($aUrls, "{$this->client->getVersion()}.$type");
    }
}
