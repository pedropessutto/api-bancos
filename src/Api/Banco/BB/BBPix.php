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

    protected $baseUrl = 'https://api-pix.bb.com.br/pix/v2';

    public function __construct($params = [])
    {
        $this->client = new BBClient(array_merge($params, [
            'scope' => 'pix-bb.read pix.write pix.read',
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

        return $this->authenticate()->put($this->url('create', $transactionId), $data)->body;
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

        return $this->getBaseUrl() . Arr::get($aUrls, "{$this->client->getVersion()}.$type");
    }
}
