<?php

namespace localzet\VPN;

use Exception;
use JetBrains\PhpStorm\ArrayShape;

trait Crypto
{
    /**
     * Generate Keys
     *
     * @return array
     * @throws Exception
     */
    #[ArrayShape([
        'private' => 'string',
        'public' => 'string',
        'preshared' => 'string',
    ])]
    public function generateKeys(): array
    {
        $PrivateKey = $this->exec('wg genkey');
        $PublicKey = $this->exec("echo $PrivateKey | wg pubkey");
        $PresharedKey = $this->exec("wg genpsk");

        return [
            'private' => $PrivateKey,
            'public' => $PublicKey,
            'preshared' => $PresharedKey,
        ];
    }
}