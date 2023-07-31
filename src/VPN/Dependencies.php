<?php

namespace localzet\VPN;

use Exception;

trait Dependencies
{
    /**
     * Install Dependencies
     *
     * @throws Exception
     */
    public function installDependencies(): void
    {
        $this->exec('apt update');
        $this->exec('apt upgrade -y');
        $this->exec('apt install -y wireguard wireguard-tools iproute2 iptables');
    }
}