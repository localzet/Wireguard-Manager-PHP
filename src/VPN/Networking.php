<?php

namespace localzet\VPN;

use Exception;

trait Networking
{
    /**
     * Detect Interface
     *
     * @return string
     * @throws Exception
     */
    public function detectInterface(): string
    {
        return $this->exec("ip -4 route ls | grep default | grep -Po '(?<=dev )(\S+)' | head -1") ?? 'eth0';
    }

    /**
     * IP Forwarding
     *
     * @return void
     * @throws Exception
     */
    public function IPForwarding(): void
    {
        // IPv4
        $this->exec('echo "net.ipv4.ip_forward = 1" >> /etc/sysctl.conf');

        // IPv4: Default
        $this->exec('echo "net.ipv4.conf.default.forwarding = 1" >> /etc/sysctl.conf');
        $this->exec('echo "net.ipv4.conf.default.proxy_arp = 0" >> /etc/sysctl.conf');
        $this->exec('echo "net.ipv4.conf.default.send_redirects = 1" >> /etc/sysctl.conf');

        // IPv4: All
        $this->exec('echo "net.ipv4.conf.all.forwarding = 1" >> /etc/sysctl.conf');
        $this->exec('echo "net.ipv4.conf.all.rp_filter = 1" >> /etc/sysctl.conf');
        $this->exec('echo "net.ipv4.conf.all.send_redirects = 0" >> /etc/sysctl.conf');

        // IPv6
//        $this->exec('echo "net.ipv6.ip_forward = 1" >> /etc/sysctl.conf');

        // IPv6: Default
//        $this->exec('echo "net.ipv6.conf.default.forwarding = 1" >> /etc/sysctl.conf');

        // IPv6: All
//        $this->exec('echo "net.ipv6.conf.all.forwarding = 1" >> /etc/sysctl.conf');

        $this->exec('sysctl -p');
    }

    /**
     * IP Tables
     *
     * @param string $IPv4 10.66.66.1
     * @param int|string $port
     * @param string $Interface
     * @param string $WireGuard
     * @return array
     */
    public function IPTables(
        string     $IPv4,
        int|string $port,
        string     $Interface,
        string     $WireGuard
    ): array
    {
        $add = [
            "iptables -t nat -A POSTROUTING -s $IPv4/24 -o $Interface -j MASQUERADE;",
//            "ip6tables -t nat -A POSTROUTING -s $IPv6/64 -o $Interface -j MASQUERADE;",

            "iptables -A INPUT -p udp -m udp --dport $port -j ACCEPT;",

            "iptables -A FORWARD -i $WireGuard -j ACCEPT;",
//            "ip6tables -A FORWARD -i $WireGuard -j ACCEPT;",

            "iptables -A FORWARD -i $Interface -o $WireGuard -j ACCEPT;",
//            "ip6tables -A FORWARD -i $Interface -o $WireGuard -j ACCEPT;",

            "iptables -A FORWARD -o $WireGuard -j ACCEPT;",
//            "ip6tables -A FORWARD -o $WireGuard -j ACCEPT;",
        ];

        $del = [
            "iptables -t nat -D POSTROUTING -s $IPv4/24 -o $Interface -j MASQUERADE;",
//            "ip6tables -t nat -D POSTROUTING -s $IPv6/64 -o $Interface -j MASQUERADE;",

            "iptables -D INPUT -p udp -m udp --dport $port -j ACCEPT;",

            "iptables -D FORWARD -i $WireGuard -j ACCEPT;",
//            "ip6tables -D FORWARD -i $WireGuard -j ACCEPT;",

            "iptables -D FORWARD -i $Interface -o $WireGuard -j ACCEPT;",
//            "ip6tables -D FORWARD -i $Interface -o $WireGuard -j ACCEPT;",

            "iptables -D FORWARD -o $WireGuard -j ACCEPT;",
//            "ip6tables -D FORWARD -o $WireGuard -j ACCEPT;",
        ];

        return [
            'add' => implode(" ", $add),
            'del' => implode(" ", $del),
        ];
    }

    /**
     * @param string $IPv4 10.66.66.1
     * @param string $WireGuard
     * @return void
     * @throws Exception
     */
    public function addRoute(
        string $IPv4,
        string $WireGuard
    ): void
    {
        $this->exec("ip -4 route add $IPv4/32 dev $WireGuard");
//        $this->exec("ip -6 route add $IPv6/128 dev $WireGuard");
    }

    /**
     * @param string $IPv4 10.66.66.1
     * @param string $WireGuard
     * @return void
     * @throws Exception
     */
    public function delRoute(
        string $IPv4,
        string $WireGuard
    ): void
    {
        $this->exec("ip -4 route del $IPv4/32 dev $WireGuard");
//        $this->exec("ip -6 route del $IPv6/128 dev $WireGuard");
    }
}