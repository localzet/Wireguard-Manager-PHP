<?php

namespace localzet\VPN;

use Exception;

trait Networking
{
    /**
     * Метод для определения сетевого интерфейса по умолчанию
     *
     * Использует команду `ip` для определения сетевого интерфейса по умолчанию
     *
     * @return string Имя сетевого интерфейса по умолчанию
     *
     * @throws Exception Если выполнение команды не удалось
     */
    public function detectInterface(): string
    {
        // Выполнение команды `ip` для определения сетевого интерфейса по умолчанию
        return self::exec("ip -4 route ls | grep default | grep -Po '(?<=dev )(\S+)' | head -1") ?? 'eth0';
    }

    /**
     * Метод для включения IP-переадресации
     *
     * Добавляет соответствующие параметры в файл /etc/sysctl.conf и применяет изменения
     *
     * @return void
     *
     * @throws Exception Если выполнение команд не удалось
     */
    public static function IPForwarding(): void
    {
        // Включение IP-переадресации для IPv4
        self::exec('echo "net.ipv4.ip_forward = 1" >> /etc/sysctl.conf');

        // Настройка параметров IPv4 по умолчанию
        self::exec('echo "net.ipv4.conf.default.forwarding = 1" >> /etc/sysctl.conf');
        self::exec('echo "net.ipv4.conf.default.proxy_arp = 0" >> /etc/sysctl.conf');
        self::exec('echo "net.ipv4.conf.default.send_redirects = 1" >> /etc/sysctl.conf');

        // Настройка параметров IPv4 для всех интерфейсов
        self::exec('echo "net.ipv4.conf.all.forwarding = 1" >> /etc/sysctl.conf');
        self::exec('echo "net.ipv4.conf.all.rp_filter = 1" >> /etc/sysctl.conf');
        self::exec('echo "net.ipv4.conf.all.send_redirects = 0" >> /etc/sysctl.conf');

        // Применение изменений
        self::exec('sysctl -p');
    }

    /**
     * Метод для создания правил межсетевого экрана iptables
     *
     * Создает правила для добавления и удаления маскарадинга и переадресации трафика через интерфейс WireGuard
     *
     * @param string $IPv4 IP-адрес сервера
     * @param int|string $port Порт сервера для прослушивания
     * @param string $Interface Интерфейс для настройки правил межсетевого экрана
     * @param string $WireGuard Имя интерфейса WireGuard
     *
     * @return array Массив с правилами для добавления и удаления
     */
    public static function IPTables(
        string     $IPv4,
        int|string $port,
        string     $Interface,
        string     $WireGuard
    ): array
    {
        // Создание правил для добавления маскарадинга и переадресации трафика через интерфейс WireGuard
        $add = [
            // Добавление маскарадинга для трафика из подсети сервера через указанный интерфейс
            "iptables -t nat -A POSTROUTING -s $IPv4/24 -o $Interface -j MASQUERADE;",

            // Разрешение входящего трафика на указанный порт UDP
            "iptables -A INPUT -p udp -m udp --dport $port -j ACCEPT;",

            // Разрешение трафика через интерфейс WireGuard в обоих направлениях
            "iptables -A FORWARD -i $WireGuard -j ACCEPT;",
            "iptables -A FORWARD -o $WireGuard -j ACCEPT;",

            // Разрешение трафика между указанным интерфейсом и интерфейсом WireGuard
            "iptables -A FORWARD -i $Interface -o $WireGuard -j ACCEPT;",
        ];

        // Создание правил для удаления маскарадинга и переадресации трафика через интерфейс WireGuard
        $del = [
            // Удаление маскарадинга для трафика из подсети сервера через указанный интерфейс
            "iptables -t nat -D POSTROUTING -s $IPv4/24 -o $Interface -j MASQUERADE;",

            // Запрет входящего трафика на указанный порт UDP
            "iptables -D INPUT -p udp -m udp --dport $port -j ACCEPT;",

            // Запрет трафика через интерфейс WireGuard в обоих направлениях
            "iptables -D FORWARD -i $WireGuard -j ACCEPT;",
            "iptables -D FORWARD -o $WireGuard -j ACCEPT;",

            // Запрет трафика между указанным интерфейсом и интерфейсом WireGuard
            "iptables -D FORWARD -i $Interface -o $WireGuard -j ACCEPT;",
        ];

        return [
            'add' => implode(" ", $add),
            'del' => implode(" ", $del),
        ];
    }


    /**
     * Метод для добавления маршрута
     *
     * Добавляет маршрут для IP-адреса через интерфейс WireGuard
     *
     * @param string $IPv4 IP-адрес для добавления маршрута
     * @param string $WireGuard Имя интерфейса WireGuard
     *
     * @return void
     *
     * @throws Exception Если выполнение команды не удалось
     */
    public static function addRoute(
        string $IPv4,
        string $WireGuard
    ): void
    {
        // Добавление маршрута для IPv4-адреса через интерфейс WireGuard
        self::exec("ip -4 route add $IPv4/32 dev $WireGuard");
    }

    /**
     * Метод для удаления маршрута
     *
     * Удаляет маршрут для IP-адреса через интерфейс WireGuard
     *
     * @param string $IPv4 IP-адрес для удаления маршрута
     * @param string $WireGuard Имя интерфейса WireGuard
     *
     * @return void
     *
     * @throws Exception Если выполнение команды не удалось
     */
    public static function delRoute(
        string $IPv4,
        string $WireGuard
    ): void
    {
        // Удаление маршрута для IPv4-адреса через интерфейс WireGuard
        self::exec("ip -4 route del $IPv4/32 dev $WireGuard");
    }

}