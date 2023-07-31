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
        $this->exec('echo "net.ipv4.ip_forward=1" >> /etc/sysctl.conf');
        $this->exec('echo "net.ipv4.conf.all.forwarding=1" >> /etc/sysctl.conf');
        $this->exec('sysctl -p');
    }

    /**
     * IP Tables
     *
     * @param string $address
     * @param int|string $port
     * @param string $interface
     * @param string $wireguard
     * @return array
     */
    public function IPTables(
        string     $address,
        int|string $port,
        string     $interface,
        string     $wireguard
    ): array
    {
        $add = [
            "iptables -t nat -A POSTROUTING -s $address/24 -o $interface -j MASQUERADE;",
            "iptables -A INPUT -p udp -m udp --dport $port -j ACCEPT;",
            "iptables -A FORWARD -i $wireguard -j ACCEPT;",
            "iptables -A FORWARD -o $wireguard -j ACCEPT;"
        ];

        $del = [
            "iptables -t nat -D POSTROUTING -s $address/24 -o $interface -j MASQUERADE;",
            "iptables -D INPUT -p udp -m udp --dport $port -j ACCEPT;",
            "iptables -D FORWARD -i $wireguard -j ACCEPT;",
            "iptables -D FORWARD -o $wireguard -j ACCEPT;"
        ];

        return [
            'add' => implode(" ", $add),
            'del' => implode(" ", $del),
        ];
    }

    /**
     * @param string $address
     * @param string $wireguard
     * @return void
     * @throws Exception
     */
    public function addRoute(string $address, string $wireguard): void
    {
        $this->exec("ip -4 route add $address/32 dev $wireguard || true");
    }

    /**
     * @param string $address
     * @param string $wireguard
     * @return void
     * @throws Exception
     */
    public function delRoute(string $address, string $wireguard): void
    {
        $this->exec("ip -4 route del $address/32 dev $wireguard || true");
    }
}