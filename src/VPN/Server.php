<?php

namespace localzet\VPN;

use Exception;

trait Server
{
    /**
     * @param string $WireGuard
     * @param string $Config
     * @return void
     * @throws Exception
     */
    public function start(
        string $WireGuard,
        string $Config
    ): void
    {
        file_put_contents("/etc/wireguard/$WireGuard.conf", $Config);
        chmod("/etc/wireguard/$WireGuard.conf", 0600);

        $this->IPForwarding();

        $this->exec("systemctl enable wg-quick@$WireGuard.service");
        $this->exec("systemctl start wg-quick@$WireGuard.service");

    }

    /**
     * @param string $WireGuard
     * @return void
     * @throws Exception
     */
    public function restart(
        string $WireGuard
    ): void
    {
        $this->exec("systemctl restart wg-quick@$WireGuard.service");
    }

    /**
     * @param string $WireGuard
     * @return void
     * @throws Exception
     */
    public function stop(
        string $WireGuard
    ): void
    {
        $this->exec("systemctl disable wg-quick@$WireGuard.service");
        $this->exec("systemctl stop wg-quick@$WireGuard.service");
    }
}