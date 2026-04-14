<?php

namespace PedroPessutto\ApiBancos\Api\Banco;

use Illuminate\Support\Facades\Cache;
use PedroPessutto\ApiBancos\Api\AbstractAPI;
use PedroPessutto\ApiBancos\Api\Exception\CurlException;
use PedroPessutto\ApiBancos\Api\Exception\HttpException;
use PedroPessutto\ApiBancos\Exception\ValidationException;
use PedroPessutto\ApiBancos\Api\Exception\UnauthorizedException;
use PedroPessutto\ApiBancos\Contracts\Boleto\BoletoAPI as BoletoAPIContract;

class Sicredi extends AbstractAPI
{
    protected $agencia;

    protected $posto;

    protected $codigo_acesso_beneficiario;

    protected $beneficiario_numero;

    protected $api_token;

    protected $camposObrigatorios = [
        'agencia',
        'posto',
        'codigo_acesso_beneficiario',
        'beneficiario_numero',
        'api_token',
    ];

    public function __construct($params = [])
    {
        parent::__construct($params);
    }

    /**
     * @return mixed
     */
    public function getAgencia()
    {
        return $this->agencia;
    }

    /**
     * @return mixed
     */
    public function getPosto()
    {
        return $this->posto;
    }

    /**
     * @return mixed
     */
    public function getCodigoAcessoBeneficiario()
    {
        return $this->codigo_acesso_beneficiario;
    }

    /**
     * @return mixed
     */
    public function getBeneficiarioNumero()
    {
        return $this->beneficiario_numero;
    }

    /**
     * @return mixed
     */
    public function getApiToken()
    {
        return $this->api_token;
    }

    protected function oAuth2()
    {
        $url = 'https://api-parceiro.sicredi.com.br/auth/openapi/token';

        $refreshTokenCache = Cache::get($this->getRefreshTokenCacheKey());

        // Renova accessToken via refreshToken, se existir
        if ($refreshTokenCache){
            $body = [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $refreshTokenCache,
            ];
        }
        // Se não, renova accessToken via usuário e senha
        else {
            $body = [
                'grant_type' => 'password',
                'username'   => $this->getBeneficiarioNumero() . $this->getAgencia(), // Código do beneficiário + Código da Cooperativa
                'password'   => $this->getCodigoAcessoBeneficiario(), // Código de Acesso gerado no Internet Banking
                'scope'      => 'cobranca',
            ];
        }

        $response = $this->post($url, $body, true)->body;

        if (isset($response->refresh_token) && isset($response->refresh_expires_in)) {
            Cache::put($this->getRefreshTokenCacheKey(), $response->refresh_token, $response->refresh_expires_in);
        }

        if (isset($response->access_token)) {
            if (isset($response->expires_in)) {
                Cache::put($this->getAccessTokenCacheKey(), $response->access_token, $response->expires_in);
            }

            return $this->setAccessToken('Bearer ' . $response->access_token);
        } else {
            throw new ValidationException('Erro ao localizar access token');
        }
    }

    /**
     * @return array
     */
    protected function headers()
    {
        return $this->getAccessToken()
            ? [
                'x-api-key'          => $this->getApiToken(),
                'Authorization'      => $this->getAccessToken(),
                'Content-Type'       => 'application/json',
                'cooperativa'        => $this->getAgencia(),
                'posto'              => $this->getPosto(),
                'codigoBeneficiario' => $this->getBeneficiarioNumero(),
            ]
            : [
                'x-api-key'    => $this->getApiToken(),
                'Content-Type' => 'application/x-www-form-urlencoded',
                'context'      => 'COBRANCA',
            ];
    }

    /**
     * @param        $nossoNumero
     * @param string $motivo
     *
     * @return mixed
     * @throws CurlException
     * @throws HttpException
     * @throws UnauthorizedException
     * @throws ValidationException
     */
    public function cancelNossoNumero($nossoNumero, $motivo = null)
    {
        $this->oAuth2();

        $url = "https://api-parceiro.sicredi.com.br/cobranca/boleto/v1/boletos/{$nossoNumero}/baixa";

        return $this->patch($url, [])->body;
    }

    /**
     * @param        $nossoNumero
     *
     * @return mixed
     * @throws CurlException
     * @throws HttpException
     * @throws UnauthorizedException
     * @throws ValidationException
     */
    public function cancelNossoNumeroProtesto($nossoNumero)
    {
        $this->oAuth2();

        $url = "https://api-parceiro.sicredi.com.br/cobranca/boleto/v1/boletos/{$nossoNumero}/sustar-protesto-baixar-titulo";

        return $this->patch($url, [])->body;
    }

    public function createBoleto(BoletoAPIContract $boleto)
    {
        // TODO: Implement createBoleto() method.
    }

    public function retrieveNossoNumero($nossoNumero)
    {
        // TODO: Implement retrieveNossoNumero() method.
    }

    public function retrieveID($id)
    {
        // TODO: Implement retrieveID() method.
    }

    public function cancelID($id, $motivo)
    {
        // TODO: Implement cancelID() method.
    }

    public function retrieveList($inputedParams = [])
    {
        // TODO: Implement retrieveList() method.
    }

    public function getPdfNossoNumero($nossoNumero)
    {
        // TODO: Implement getPdfNossoNumero() method.
    }

    public function getPdfID($id)
    {
        // TODO: Implement getPdfID() method.
    }
}
