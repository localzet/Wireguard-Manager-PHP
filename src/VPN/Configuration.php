<?php

namespace localzet\VPN;

use Exception;

trait Configuration
{
    /**
     * Server Configuration
     *
     * @param string $IPv4 10.66.66.1
     * @param int|string $ListenPort
     * @param string $Interface
     * @param string $WireGuard
     * @param string $PrivateKey
     * @param array $Users [Id, IPv4, IPv6, PrivateKey, PublicKey, PresharedKey]
     * @return string
     */
    public function serverConfiguration(
        string     $IPv4,
        int|string $ListenPort,
        string     $Interface,
        string     $WireGuard,
        string     $PrivateKey,
        array      $Users,
    ): string
    {
        $IPTables = $this->IPTables(
            $IPv4,
            $ListenPort,
            $Interface,
            $WireGuard,
        );

        $config = <<<EOF
# Не редактируй файл вручную!!!
# Все изменения будут перезаписаны!

[Interface]
PrivateKey = {$PrivateKey}
Address = {$IPv4}/24
ListenPort = {$ListenPort}
PostUp = {$IPTables['add']}
PostDown = {$IPTables['del']}

EOF;
        foreach ($Users as $User) {
            $config .= <<<EOF

# Клиент: {$User['Id']}
[Peer]
AllowedIPs = {$User['IPv4']}/32
PublicKey = {$User['PublicKey']}
PresharedKey = {$User['PresharedKey']}
EOF;
        }

        return $config;
    }

    /**
     * Client Configuration
     *
     * @param string $UserIPv4
     * @param string $UserIPv6
     * @param string $UserPrivateKey
     * @param string $UserPresharedKey
     * @param string $ServerAddress
     * @param string $ServerPort
     * @param string $ServerPublicKey
     * @return string
     */
    public function clientConfiguration(
        string $UserIPv4,
        string $UserIPv6,
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
Address = {$UserIPv4}/32
DNS = 1.1.1.1, 8.8.8.8

[Peer]
PublicKey = {$ServerPublicKey}
PresharedKey = {$UserPresharedKey}
Endpoint = {$ServerAddress}:{$ServerPort}
AllowedIPs = 0.0.0.0/0, ::/0
EOF;
    }

    /**
     * @param array $Users
     * @param string $Template
     * @return string
     * @throws Exception
     */
    public function nextIPv4(array $Users, string $Template = '10.66.66.x'): string
    {
        $Address = null;
        for ($i = 2; $i < 255; $i++) {
            $Client = null;
            foreach ($Users as $User) {
                if ($User['Address'] === str_replace("x", $i, $Template)) {
                    $Client = $User;
                    break;
                }
            }

            if (!$Client) {
                $Address = str_replace("x", $i, $Template);
                break;
            }
        }

        if (!$Address) {
            throw new Exception("Достигнут максимум клиентов.");
        }

        return $Address;
    }
}