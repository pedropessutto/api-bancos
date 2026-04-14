<?php

namespace PedroPessutto\ApiBancos\Api;

abstract class BancoClient extends AbstractApi
{
    public function __construct($params = [])
    {
        parent::__construct($params);

        $baseCacheKey = md5(static::class . '|' . serialize($params));
        $this->access_token_cache_key = $this->access_token_cache_key ?: "api_bancos:access_token:{$baseCacheKey}";
        $this->refresh_token_cache_key = $this->refresh_token_cache_key ?: "api_bancos:refresh_token:{$baseCacheKey}";
    }

    public function requestPost($url, array $body, $raw = false)
    {
        return $this->post($url, $body, $raw);
    }

    public function requestPut($url, array $body, $raw = false)
    {
        return $this->put($url, $body, $raw);
    }

    public function requestPatch($url, array $body, $raw = false)
    {
        return $this->patch($url, $body, $raw);
    }

    public function requestGet($url)
    {
        return $this->get($url);
    }
}
