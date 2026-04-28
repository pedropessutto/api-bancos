<?php

namespace PedroPessutto\ApiBancos\Api;

use Illuminate\Support\Facades\Cache;

abstract class BancoClient extends AbstractApi
{
    public function __construct($params = [])
    {
        parent::__construct($params);

        if (isset($params['base_cache_key']) && $params['base_cache_key']) {
            $baseCacheKey = $params['base_cache_key'];
        } else {
            $baseCacheKey = md5(static::class . '|' . serialize($params));
        }

        $this->access_token_cache_key = $this->access_token_cache_key ?: "api_bancos:access_token:{$baseCacheKey}";
        $this->refresh_token_cache_key = $this->refresh_token_cache_key ?: "api_bancos:refresh_token:{$baseCacheKey}";
    }

    public function requestPost($url, array $body, $raw = false)
    {
        return $this->retry(fn() => $this->post($url, $body, $raw));
    }

    public function requestPut($url, array $body, $raw = false)
    {
        return $this->retry(fn() => $this->put($url, $body, $raw));
    }

    public function requestPatch($url, array $body, $raw = false)
    {
        return $this->retry(fn() => $this->patch($url, $body, $raw));
    }

    public function requestGet($url)
    {
        return $this->retry(fn() => $this->get($url));
    }

    private function retry(callable $callback)
    {
        $tentativas = 0;
        $max = 3;

        do {
            try {
                return $callback();
            } catch (\Throwable $e) {
                $tentativas++;

                $mensagem = strtolower($e->getMessage());

                $isAuthError =
                    str_contains($mensagem, '401') ||
                    str_contains($mensagem, 'unauthorized') ||
                    str_contains($mensagem, 'credencial invalida') ||
                    str_contains($mensagem, 'nao autorizado');

                if ($isAuthError && $tentativas < $max) {
                    Cache::forget($this->access_token_cache_key);
                    Cache::forget($this->refresh_token_cache_key);

                    $this->setAccessToken(null);
                    $this->setRefreshToken(null);
                    continue;
                }

                throw $e;
            }

        } while ($tentativas < $max);

        throw new \Exception('Falha após múltiplas tentativas');
    }
}
