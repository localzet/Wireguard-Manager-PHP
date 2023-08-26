<?php

namespace localzet;

use localzet\VPN\{Configuration, Crypto, Dependencies, Exec, Networking, Server, ShadowSocks};

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
        Server,
        ShadowSocks;
}