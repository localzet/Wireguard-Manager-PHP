<?php

namespace localzet\VPN;

use Exception;

trait Server
{
    /**
     * @param string $wireguard
     * @param string $config
     * @return void
     * @throws Exception
     */
    public function start(
        string $wireguard,
        string $config
    ): void
    {
        file_put_contents("/etc/wireguard/$wireguard.conf", $config);
        chmod("/etc/wireguard/$wireguard.conf", 0600);

//        $this->exec("wg syncconf $wireguard $config");
//        $this->exec("wg syncconf $wireguard <(wg-quick strip $wireguard)");

        $this->IPForwarding();

        $this->exec("systemctl enable wg-quick@$wireguard.service");
        $this->exec("systemctl start wg-quick@$wireguard.service");
//        $this->exec("wg-quick up $wireguard || true");
    }

    /**
     * @param string $wireguard
     * @return void
     * @throws Exception
     */
    public function restart(string $wireguard): void
    {
//        $this->exec("wg syncconf $this->wireguard <(wg-quick strip $this->wireguard)");
        $this->exec("systemctl restart wg-quick@$wireguard.service");
    }

    /**
     * @param string $wireguard
     * @return void
     * @throws Exception
     */
    public function stop(string $wireguard): void
    {
        $this->exec("systemctl stop wg-quick@$wireguard.service");
    }
}