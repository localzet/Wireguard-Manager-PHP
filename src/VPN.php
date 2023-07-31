<?php

namespace localzet;

use localzet\VPN\{Configuration, Crypto, Dependencies, Exec, Networking, Server};

/**
 *
 */
class VPN
{
    use Exec,
        Dependencies,
        Crypto,
        Networking,
        Configuration,
        Server;
}