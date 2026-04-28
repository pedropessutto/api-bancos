<?php

namespace PedroPessutto\ApiBancos\Api;

use Eduardokum\LaravelBoleto\Pessoa as LaravelBoletoPessoa;
use Illuminate\Support\Facades\Cache;
use stdClass;
use Illuminate\Support\Str;
use PedroPessutto\ApiBancos\Util;
use PedroPessutto\ApiBancos\Api\Exception\CurlException;
use PedroPessutto\ApiBancos\Api\Exception\HttpException;
use PedroPessutto\ApiBancos\Api\Exception\MissingDataException;
use PedroPessutto\ApiBancos\Contracts\Pessoa as PessoaContract;
use PedroPessutto\ApiBancos\Api\Exception\UnauthorizedException;

abstract class AbstractApi
{
    protected $baseUrl = null;

    protected $conta = null;

    protected $certificado = null;

    protected $certificadoChave = null;

    protected $certificadoSenha = null;

    protected $identificador = null;

    protected $client_id = null;

    protected $client_secret = null;

    protected $scope = null;

    protected $agencia = null;

    protected $posto = null;

    protected $codigo_acesso_beneficiario = null;

    protected $beneficiario_numero = null;

    protected $api_token = null;

    protected $senha = null;

    protected $cnpj = null;

    protected $access_token = null;

    protected $refresh_token = null;

    protected $debug = false;

    protected $log = null;

    protected $beneficiario;

    protected $access_token_cache_key;

    protected $refresh_token_cache_key;

    private $curl = null;

    private $responseHttpCode = null;

    private $requestInfo = null;

    private $temps = [];

    /**
     * Campos necessários para o boleto
     *
     * @var array
     */
    protected $camposObrigatorios = [
        'conta',
        'cnpj',
        'certificado',
        'certificadoChave',
        'certificadoSenha',
        'identificador',
        'senha',
    ];

    /**
     * AbstractAPI constructor.
     *
     * @param array $params
     *
     * @throws MissingDataException
     */
    public function __construct($params = [])
    {
        Util::fillClass($this, $params);
        $missing = [];
        foreach ($this->camposObrigatorios as $campo) {
            $test = call_user_func([$this, 'get' . Str::camel($campo)]);
            if ($test === '' || is_null($test)) {
                $missing[] = $campo;
            }
        }
        if (count($missing) > 0) {
            throw new MissingDataException($missing);
        }
    }

    public function __destruct()
    {
        foreach ($this->temps as $temp) {
            @unlink($temp);
        }
    }

    abstract protected function headers();

    abstract protected function oAuth2();

    public function authenticate()
    {
        // Caso necessário, sobrescrever este método para implementar a autenticação individual de cada banco
        return $this->oAuth2();
    }

    /**
     * Get API Base URL
     *
     * @return string|null
     */
    public function getBaseUrl()
    {
        return rtrim($this->baseUrl, '/') . '/';
    }

    /**
     * @return string|null
     */
    public function getConta()
    {
        return $this->conta;
    }

    /**
     * @param $conta
     *
     * @return $this
     */
    public function setConta($conta)
    {
        $this->conta = $conta;

        return $this;
    }

    /**
     * @return null
     */
    public function getCertificado()
    {
        return $this->certificado;
    }

    /**
     * @param null $certificado
     *
     * @return AbstractAPI
     */
    public function setCertificado($certificado)
    {
        $this->certificado = $certificado;

        return $this;
    }

    /**
     * @return null
     */
    public function getCertificadoChave()
    {
        return $this->certificadoChave;
    }

    /**
     * @param null $certificadoChave
     *
     * @return AbstractAPI
     */
    public function setCertificadoChave($certificadoChave)
    {
        $this->certificadoChave = $certificadoChave;

        return $this;
    }

    /**
     * @return null
     */
    public function getCertificadoSenha()
    {
        return $this->certificadoSenha;
    }

    /**
     * @param null $certificadoSenha
     *
     * @return AbstractAPI
     */
    public function setCertificadoSenha($certificadoSenha)
    {
        $this->certificadoSenha = $certificadoSenha;

        return $this;
    }

    /**
     * @return null
     */
    public function getIdentificador()
    {
        return $this->identificador;
    }

    /**
     * @param null $identificador
     *
     * @return AbstractAPI
     */
    public function setIdentificador($identificador)
    {
        $this->identificador = $identificador;

        return $this;
    }

    /**
     * @return null
     */
    public function getSenha()
    {
        return $this->senha;
    }

    /**
     * @param null $senha
     *
     * @return AbstractAPI
     */
    public function setSenha($senha)
    {
        $this->senha = $senha;

        return $this;
    }

    /**
     * @return null
     */
    public function getCnpj()
    {
        return $this->cnpj;
    }

    /**
     * @param null $cnpj
     *
     * @return AbstractAPI
     */
    public function setCnpj($cnpj)
    {
        $this->cnpj = $cnpj;

        return $this;
    }

    /**
     * @return null
     */
    public function getClientId()
    {
        return $this->client_id;
    }

    /**
     * @param null $client_id
     *
     * @return AbstractAPI
     */
    public function setClientId($client_id)
    {
        $this->client_id = $client_id;

        return $this;
    }

    /**
     * @return null
     */
    public function getClientSecret()
    {
        return $this->client_secret;
    }

    /**
     * @param null $client_secret
     *
     * @return AbstractAPI
     */
    public function setClientSecret($client_secret)
    {
        $this->client_secret = $client_secret;

        return $this;
    }

    /**
     * @return null
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param null $scope
     *
     * @return AbstractAPI
     */
    public function setScope($scope)
    {
        $this->scope = $scope;

        return $this;
    }

    /**
     * @return null
     */
    public function getAgencia()
    {
        return $this->agencia;
    }

    /**
     * @param null $agencia
     *
     * @return AbstractAPI
     */
    public function setAgencia($agencia)
    {
        $this->agencia = $agencia;

        return $this;
    }

    /**
     * @return null
     */
    public function getPosto()
    {
        return $this->posto;
    }

    /**
     * @param null $posto
     *
     * @return AbstractAPI
     */
    public function setPosto($posto)
    {
        $this->posto = $posto;

        return $this;
    }

    /**
     * @return null
     */
    public function getCodigoAcessoBeneficiario()
    {
        return $this->codigo_acesso_beneficiario;
    }

    /**
     * @param null $codigo_acesso_beneficiario
     *
     * @return AbstractAPI
     */
    public function setCodigoAcessoBeneficiario($codigo_acesso_beneficiario)
    {
        $this->codigo_acesso_beneficiario = $codigo_acesso_beneficiario;

        return $this;
    }

    /**
     * @return null
     */
    public function getBeneficiarioNumero()
    {
        return $this->beneficiario_numero;
    }

    /**
     * @param null $beneficiario_numero
     *
     * @return AbstractAPI
     */
    public function setBeneficiarioNumero($beneficiario_numero)
    {
        $this->beneficiario_numero = $beneficiario_numero;

        return $this;
    }

    /**
     * @return null
     */
    public function getApiToken()
    {
        return $this->api_token;
    }

    /**
     * @param null $api_token
     *
     * @return AbstractAPI
     */
    public function setApiToken($api_token)
    {
        $this->api_token = $api_token;
        return $this;
    }

    /**
     * @return null
     */
    public function getAccessToken()
    {
        $accessTokenCache = Cache::get($this->getAccessTokenCacheKey());

        if ($accessTokenCache) {
            return $accessTokenCache;
        }

        return $this->access_token;
    }

    /**
     * @param $access_token
     *
     * @return AbstractAPI
     */
    public function setAccessToken($access_token, $expires_in = null)
    {
        $this->access_token = $access_token;
        $this->access_token = ltrim($this->access_token, 'Bearer ');

        Cache::forget($this->getAccessTokenCacheKey());

        if ($expires_in > 0 && $this->getAccessTokenCacheKey()) {
            Cache::put($this->getAccessTokenCacheKey(), $this->access_token, $expires_in * 0.5);
        }

        return $this;
    }

    /**
     * @return null
     */
    public function getRefreshToken()
    {
        $refreshTokenCache = Cache::get($this->getRefreshTokenCacheKey());

        if ($refreshTokenCache) {
            return $refreshTokenCache;
        }

        return $this->refresh_token;
    }

    /**
     * @param null $refresh_token
     *
     * @return AbstractAPI
     */
    public function setRefreshToken($refresh_token, $expires_in = null)
    {
        $this->refresh_token = $refresh_token;

        Cache::forget($this->getRefreshTokenCacheKey());

        if ($expires_in > 0 && $this->getRefreshTokenCacheKey()) {
            Cache::put($this->getRefreshTokenCacheKey(), $this->refresh_token, $expires_in * 0.5);
        }

        return $this;
    }

    /**
     * @return PessoaContract
     */
    public function getBeneficiario()
    {
        return is_array($this->beneficiario) || is_null($this->beneficiario) ? new LaravelBoletoPessoa() : $this->beneficiario;
    }

    /**
     * @param array $beneficiario
     *
     * @return AbstractAPI
     * @throws ValidationException
     */
    public function setBeneficiario($beneficiario)
    {
        Util::addPessoa($this->beneficiario, $beneficiario);

        return $this;
    }

    /**
     * @return string
     */
    public function getAccessTokenCacheKey()
    {
        return $this->access_token_cache_key;
    }

    /**
     * @return string
     */
    protected function getRefreshTokenCacheKey()
    {
        return $this->refresh_token_cache_key;
    }

    /**
     * @return $this
     */
    public function setDebug()
    {
        $this->debug = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function unsetDebug()
    {
        $this->debug = false;

        return $this;
    }

    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * @return null
     */
    public function getLog()
    {
        return $this->log .
            print_r($this->getRequestInfo(), true);
    }

    /**
     * @return $this
     */
    public function clearLog()
    {
        $this->log = null;

        return $this;
    }

    /**
     * @return null
     */
    protected function getResponseHttpCode()
    {
        return $this->responseHttpCode;
    }

    /**
     * @param null $responseHttpCode
     *
     * @return AbstractAPI
     */
    protected function setResponseHttpCode($responseHttpCode)
    {
        $this->responseHttpCode = $responseHttpCode;

        return $this;
    }

    /**
     * @return null
     */
    protected function getRequestInfo()
    {
        return $this->requestInfo;
    }

    /**
     * @param null $requestInfo
     *
     * @return AbstractAPI
     */
    protected function setRequestInfo($requestInfo)
    {
        $this->requestInfo = $requestInfo;

        return $this;
    }

    /**
     * @throws HttpException
     * @throws UnauthorizedException
     * @throws CurlException
     */
    protected function post($url, array $post, $raw = false)
    {
        $url = ltrim($url, '/');
        $this->init()
            ->setHeaders(array_filter([
                'Accept'       => $raw ? null : 'application/json',
                'Content-Type' => $raw ? 'application/x-www-form-urlencoded' : 'application/json',
            ]));

        // clean string
        $post = $this->arrayMapRecursive(function ($data) {
            return Util::normalizeChars($data);
        }, $post);

        if ( ! Str::startsWith($url, 'http')) {
            $url = $this->getBaseUrl() . $url;
        }

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $raw ? http_build_query($post) : json_encode($post));

        return $this->execute();
    }

    /**
     * @throws HttpException
     * @throws UnauthorizedException
     * @throws CurlException
     */
    protected function put($url, array $post, $raw = false)
    {
        $url = ltrim($url, '/');
        $this->init()
            ->setHeaders(array_filter([
                'Accept'       => $raw ? null : 'application/json',
                'Content-Type' => $raw ? 'application/x-www-form-urlencoded' : 'application/json',
            ]));

        // clean string
        $post = $this->arrayMapRecursive(function ($data) {
            return Util::normalizeChars($data);
        }, $post);

        if ( ! Str::startsWith($url, 'http')) {
            $url = $this->getBaseUrl() . $url;
        }

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $raw ? http_build_query($post) : json_encode($post));

        return $this->execute();
    }

    /**
     * @throws HttpException
     * @throws UnauthorizedException
     * @throws CurlException
     */
    protected function patch($url, array $post, $raw = false)
    {
        $url = ltrim($url, '/');
        $this->init()
            ->setHeaders(array_filter([
                'Accept'       => $raw ? null : 'application/json',
                'Content-Type' => $raw ? 'application/x-www-form-urlencoded' : 'application/json',
            ]));

        // clean string
        $post = $this->arrayMapRecursive(function ($data) {
            return Util::normalizeChars($data);
        }, $post);

        if ( ! Str::startsWith($url, 'http')) {
            $url = $this->getBaseUrl() . $url;
        }

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_POST, 1);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $raw ? http_build_query($post) : json_encode($post));

        return $this->execute();
    }

    /**
     * @param $url
     *
     * @return stdClass
     * @throws HttpException
     * @throws UnauthorizedException
     * @throws CurlException
     */
    protected function get($url)
    {
        $url = ltrim($url, '/');
        $this->init()
            ->setHeaders([
                'Accept' => 'application/json',
            ]);

        if ( ! Str::startsWith($url, 'http')) {
            $url = $this->getBaseUrl() . $url;
        }

        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'GET');

        return $this->execute();
    }

    /**
     * @return $this
     */
    private function init()
    {
        $cert = $this->getCertificado();
        $key  = $this->getCertificadoChave();
    
        // Se for conteúdo (não path), transforma em arquivo
        if ($cert && !file_exists($cert)) {
            $cert = $this->tempFile($cert);
            $this->setCertificado($cert);
        }
    
        if ($key && !file_exists($key)) {
            $key = $this->tempFile($key);
            $this->setCertificadoChave($key);
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_HEADER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    
        if ($cert) {
            curl_setopt($curl, CURLOPT_SSLCERT, $cert);
        }
    
        if ($key) {
            curl_setopt($curl, CURLOPT_SSLKEY, $key);
        }

        if ($senha = $this->getCertificadoSenha()) {
            curl_setopt($curl, CURLOPT_KEYPASSWD, $senha);
        }
    
        curl_setopt($curl, CURLOPT_CAPATH, '/etc/ssl/certs/');
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    
        $this->curl = $curl;
    
        return $this;
    }

    /**
     * @param array $headers
     *
     * @return $this
     */
    private function setHeaders($headers = [])
    {
        $headers = array_unique(array_merge($this->convertHeaders($headers), $this->convertHeaders($this->headers())));

        curl_setopt($this->curl, CURLOPT_HTTPHEADER, $headers);

        return $this;
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    private function convertHeaders(array $headers)
    {
        $compiledHeader = [];
        foreach ($headers as $param => $value) {
            if (is_integer($param) && preg_match('/([\w-]+): ?(.*)/g', $value)) {
                $compiledHeader[] = $value;
            } else {
                $compiledHeader[] = "$param: $value";
            }
        }

        return $compiledHeader;
    }

    /**
     * @param $response
     *
     * @return stdClass
     */
    private function parseResponse($response)
    {
        $retorno = new stdClass();
        $retorno->headers_text = substr($response, 0, strpos($response, "\r\n\r\n"));
        $retorno->body_text = substr($response, strpos($response, "\r\n\r\n"));

        $retorno->headers = [];
        foreach (explode("\r\n", $retorno->headers_text) as $i => $line) {
            if ($i === 0) {
                $retorno->headers['http_code'] = $line;
            } else {
                [$key, $value] = explode(': ', $line);
                $retorno->headers[$key] = $value;
            }
        }
        $retorno->body = json_decode($retorno->body_text);

        return $retorno;
    }

    /**
     * @param $retorno
     *
     * @throws HttpException
     * @throws UnauthorizedException
     */
    private function handleException($retorno)
    {
        if ($this->getResponseHttpCode() < 200 || $this->getResponseHttpCode() > 299) {
            if (in_array($this->getResponseHttpCode(), [401, 403]) && empty($retorno->body_text)) {
                throw new UnauthorizedException($this->getBaseUrl(), $this->getCertificado(), $this->getCertificadoChave(), $this->getCertificadoSenha());
            }

            throw new HttpException($this->getResponseHttpCode(), $this->getRequestInfo(), $retorno->body_text);
        }
    }

    /**
     * @return false|stdClass
     * @throws CurlException
     * @throws HttpException
     * @throws UnauthorizedException
     */
    private function execute()
    {
        $loop = 0;
        if ($this->isDebug()) {
            ob_start();
            $this->log = fopen('php://output', 'w');
            curl_setopt($this->curl, CURLOPT_VERBOSE, true);
            curl_setopt($this->curl, CURLOPT_STDERR, $this->log);
        }
        do {
            if ($exec = curl_exec($this->curl)) {
                $this->setResponseHttpCode(curl_getinfo($this->curl, CURLINFO_HTTP_CODE));
                $this->setRequestInfo(curl_getinfo($this->curl));
                curl_close($this->curl);
                $this->curl = null;

                if ($this->isDebug()) {
                    fclose($this->log);
                    $this->log = ob_get_clean();
                }
                $retorno = $this->parseResponse($exec);
                $this->handleException($retorno);

                return $retorno;
            }

            if ($this->isDebug()) {
                fclose($this->log);
                $this->log = ob_get_clean();
            }

            if ($this->getResponseHttpCode() == 503 && $loop < 5) {
                $keep = true;
                usleep(200000);  // 0.2 segundos
            } else {
                $keep = false;
            }
            $loop++;
        } while ($keep == true);

        $error = curl_error($this->curl);
        curl_close($this->curl);
        $this->curl = null;
        if (! $this->getResponseHttpCode() && $error) {
            throw new CurlException($error);
        }

        return false;
    }

    /**
     * @param $content
     *
     * @return false|string
     */
    private function tempFile($content)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'certificate');
        $this->temps[] = $tmpFile;
        file_put_contents($tmpFile, $content);

        return $tmpFile;
    }

    /**
     * @param $callback
     * @param $input
     *
     * @return array
     */
    private function arrayMapRecursive($callback, $input)
    {
        $output = [];
        foreach ($input as $key => $data) {
            if (is_array($data)) {
                $output[$key] = $this->arrayMapRecursive($callback, $data);
            } else {
                $output[$key] = $callback($data);
            }
        }

        return $output;
    }
}
