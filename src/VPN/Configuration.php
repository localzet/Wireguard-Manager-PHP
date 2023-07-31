<?php

namespace localzet\VPN;

use Exception;

trait Configuration
{
    /**
     * Server Configuration
     *
     * @param string $address
     * @param int|string $port
     * @param string $interface
     * @param string $wireguard
     * @param string $privatekey
     * @param array $users [Id, Address, PrivateKey, PublicKey, PresharedKey]
     * @return string
     */
    public function serverConfiguration(
        string     $address,
        int|string $port,
        string     $interface,
        string     $wireguard,
        string     $privatekey,
        array      $users,
    ): string
    {
        $IPTables = $this->IPTables(
            $address,
            $port,
            $interface,
            $wireguard,
        );

        $config = <<<EOF
# Не редактируй файл вручную!!!
# Все изменения будут перезаписаны!

[Interface]
PrivateKey = {$privatekey}
Address = {$address}/24
ListenPort = {$port}
PostUp = {$IPTables['add']}
PostDown = {$IPTables['del']}

EOF;
        foreach ($users as $user) {
            $config .= <<<EOF

# Клиент: {$user['Id']}
[Peer]
AllowedIPs = {$user['Address']}/32
PublicKey = {$user['PublicKey']}
PresharedKey = {$user['PresharedKey']}
EOF;
        }

        return $config;
    }

    /**
     * Client Configuration
     *
     * @param string $UserAddress
     * @param string $UserPrivateKey
     * @param string $UserPresharedKey
     * @param string $ServerAddress
     * @param string $ServerPort
     * @param string $ServerPublicKey
     * @return string
     */
    public function clientConfiguration(
        string $UserAddress,
        string $UserPrivateKey,
        string $UserPresharedKey,
        string $ServerAddress,
        string $ServerPort,
        string $ServerPublicKey,
    ): string
    {
        return <<<EOF
[Interface]
PrivateKey = {$UserPrivateKey}
Address = {$UserAddress}/32
DNS = 1.1.1.1

[Peer]
PublicKey = {$ServerPublicKey}
PresharedKey = {$UserPresharedKey}
Endpoint = {$ServerAddress}:{$ServerPort}
AllowedIPs = 0.0.0.0/0, ::/0
PersistentKeepalive = 20
EOF;
    }

    /**
     * @param array $users
     * @param string $template
     * @return string
     * @throws Exception
     */
    public function nextAddress(array $users, string $template = '10.8.0.x'): string
    {
        $Address = null;
        for ($i = 2; $i < 255; $i++) {
            $client = null;
            foreach ($users as $user) {
                if ($user['Address'] === str_replace("x", $i, $template)) {
                    $client = $user;
                    break;
                }
            }

            if (!$client) {
                $Address = str_replace("x", $i, $template);
                break;
            }
        }

        if (!$Address) {
            throw new Exception("Достигнут максимум клиентов.");
        }

        return $Address;
    }
}