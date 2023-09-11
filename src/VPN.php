<?php

namespace localzet;

use localzet\VPN\{Format, Generate, Linux, ShadowSocks, Wg};

/**
 *
 */
class VPN
{
    use Generate,
        Linux,
        Format,
        Wg,
        ShadowSocks;
}