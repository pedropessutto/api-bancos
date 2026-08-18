<?php

namespace PedroPessutto\ApiBancos\Api\Banco\BB;

use Carbon\Carbon;
use Illuminate\Support\Arr;
use PedroPessutto\ApiBancos\Api\Contracts\AbstractPix;
use PedroPessutto\ApiBancos\Contracts\Pix as PixContract;
use PedroPessutto\ApiBancos\Pix\Banco\Bb as PixBB;

class BBPix extends AbstractPix
{
    protected BBClient $client;

    // protected $baseUrl = 'https://api-pix.hm.bb.com.br/pix/v2'; // Homologação
    protected $baseUrl = 'https://api-pix.bb.com.br/pix/v2'; // Produção

    public function __construct($params = [])
    {
        $this->client = new BBClient(array_merge($params, [
            'scope' => implode(' ', [
                'cob.read', 'cob.write',
                'cobr.read', 'cobr.write', 
                'cobv.read', 'cobv.write',
                // 'lotecobv.read', 'lotecobv.write',
                // 'webhook.read', 'webhook.write',
                // 'webhookcobr.read', 'webhookcobr.write',
                // 'pix-bb.read', 'pix-bb.write',
                // 'pix.read', 'pix.write',
            ]),
        ]));

        parent::__construct($params);
    }

    protected function oAuth2()
    {
        return $this->client->oAuth2();
    }

    public function createPix(PixContract $pix, string $transactionId = null)
    {     
        $data = $pix->pixToArray();

        $data['calendario'] = [
            'expiracao' => Carbon::now()->diffInSeconds($pix->getExpiresAt())
        ];

        unset($data['expires_at']);
        unset($data['created_at']);
        unset($data['txid']);
        unset($data['pixCopiaECola']);

        return $this->authenticate()->put($this->url('create', $transactionId), $data);
    }

    public function retrieveTransactionId(string $transactionId)
    {
        $retorno = $this->authenticate()->get($this->url('show', $transactionId), [])->body;

        return PixBB::fromAPI($retorno, []);
    }

    public function updateTransactionId(string $transactionId, PixContract $pix)
    {
        // TODO: Implement updateTransactionId() method.
    }

    public function deleteTransactionId(string $transactionId)
    {
        // TODO: Implement deleteTransactionId() method.
    }

    public function qrCodePix(PixContract $pix)
    {
        $this->getQrCodeBase64();
    }

    protected function headers()
    {
        return $this->client->headers();
    }

    public function clearCache()
    {
        $this->client->clearCache();
    }

    private function url($type, $param = null)
    {
        $aUrls = [
            2 => [ // Pix v2
                'create'  => 'cob/' . $param, // PUT
                'show'    => 'cob/' . $param, // GET
                'search'  => 'cob?', // GET
                // 'webhook' => 'cob/webhook',
            ]
        ];

        $path = Arr::get($aUrls, "{$this->client->getVersion()}.$type");
        
        return $this->getBaseUrl() . $path . (str_contains($path, '?') ? '&' : '?') . 'gw-dev-app-key=' . $this->client->getApiToken();
    }
}
