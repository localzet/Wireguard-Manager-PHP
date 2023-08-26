<?php

namespace localzet\VPN;

use Exception;

/**
 * Трейт для управления ShadowSocks
 */
trait ShadowSocks
{
    /**
     * @var string IP-адрес или доменное имя ShadowSocks-сервера
     */
    protected static string $shadowSocksServer = '0.0.0.0';

    /**
     * @var int Порт ShadowSocks-сервера
     */
    protected static int $shadowSocksPort = 8388;

    /**
     * @var string Пароль для подключения к ShadowSocks-серверу
     */
    protected static string $shadowSocksPassword = 'your_password';

    /**
     * @var string Метод шифрования для подключения к ShadowSocks-серверу
     */
    protected static string $shadowSocksMethod = 'aes-256-cfb';

    /**
     * Метод для установки ShadowSocks
     *
     * Устанавливает необходимые пакеты и настраивает службу ShadowSocks
     *
     * @throws Exception Если выполнение команд не удалось
     */
    public static function installShadowSocks(): void
    {
        // Установка необходимых пакетов
        self::exec('apt update');
        self::exec('apt install -y shadowsocks-libev');

        // Создание файла конфигурации ShadowSocks
        $config = [
            'server' => self::$shadowSocksServer,
            'server_port' => self::$shadowSocksPort,
            'local_port' => 1080,
            'password' => self::$shadowSocksPassword,
            'timeout' => 60,
            'method' => self::$shadowSocksMethod,
        ];
        file_put_contents('/etc/shadowsocks-libev/config.json', json_encode($config, JSON_PRETTY_PRINT));

        // Включение и запуск службы ShadowSocks
        self::exec('systemctl enable shadowsocks-libev-local@config.service');
        self::exec('systemctl start shadowsocks-libev-local@config.service');
    }

    /**
     * Метод для настройки маршрутизации трафика через ShadowSocks
     *
     * Настраивает правила iptables для перенаправления трафика через ShadowSocks
     *
     * @param string $interface Интерфейс, который будет использоваться для маршрутизации трафика через ShadowSocks
     *
     * @throws Exception Если выполнение команд не удалось
     */
    public static function routeTrafficThroughShadowSocks(string $interface): void
    {
        // Создание цепочки правил iptables для перенаправления трафика через ShadowSocks
        self::exec('iptables -t nat -N SHADOWSOCKS');
        self::exec("iptables -t nat -A SHADOWSOCKS -d 0.0.0.0/8 -j RETURN");
        self::exec("iptables -t nat -A SHADOWSOCKS -d 10.0.0.0/8 -j RETURN");
        self::exec("iptables -t nat -A SHADOWSOCKS -d 127.0.0.0/8 -j RETURN");
        self::exec("iptables -t nat -A SHADOWSOCKS -d 169.254.0.0/16 -j RETURN");
        self::exec("iptables -t nat -A SHADOWSOCKS -d 172.16.0.0/12 -j RETURN");
        self::exec("iptables -t nat -A SHADOWSOCKS -d 192.168.0.0/16 -j RETURN");
        self::exec("iptables -t nat -A SHADOWSOCKS -d 224.0.0.0/4 -j RETURN");
        self::exec("iptables -t nat -A SHADOWSOCKS -d 240.0.0.0/4 -j RETURN");
        self::exec("iptables -t nat -A SHADOWSOCKS -p tcp --dport 22:1023 -j RETURN");
        self::exec("iptables -t nat -A SHADOWSOCKS -p tcp --dport 1081:65535 -j RETURN");
        self::exec("iptables -t nat -A SHADOWSOCKS -p tcp --dport 80,443,53 -j REDIRECT --to-ports 1080");
        self::exec("iptables -t nat -A PREROUTING -i $interface -p tcp -j SHADOWSOCKS");
    }
}
