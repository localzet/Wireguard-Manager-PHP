<?php

namespace localzet\VPN;

/**
 * Trait ShadowSocks
 *
 * Этот trait предоставляет набор функций для управления сервером ShadowSocks.
 * ShadowSocks - это защищенный Socks5-прокси, предназначенный для обеспечения интернет-приватности.
 * @see https://ru.wikipedia.org/wiki/Shadowsocks
 */
trait ShadowSocks
{
    /**
     * Начинает маршрутизацию трафика через ShadowSocks.
     *
     * @param string $WireGuard Имя интерфейса WireGuard.
     * @param int $WireGuardPort Порт WireGuard для перенаправления трафика.
     */
    public static function ss_start_routing_traffic(string $WireGuard, int $WireGuardPort = 17968): void
    {
        // Создаем новую цепочку iptables под названием SHADOWSOCKS
        shell_exec('iptables -t nat -N SHADOWSOCKS');

        // Массив адресов, для которых не нужно применять проксирование
        $returnAddresses = [
            "0.0.0.0/8",
            "10.0.0.0/8",
            "127.0.0.0/8",
            "169.254.0.0/16",
            "172.16.0.0/12",
            "192.168.0.0/16",
            "224.0.0.0/4",
            "240.0.0.0/4"
        ];

        // Добавляем правила RETURN для каждого адреса в массиве
        foreach ($returnAddresses as $address) {
            shell_exec("iptables -t nat -A SHADOWSOCKS -d $address -j RETURN");
        }

        // Добавляем правила для исключения определенных портов из проксирования
        shell_exec("iptables -t nat -A SHADOWSOCKS -p tcp --dport 22:1023 -j RETURN");
        shell_exec("iptables -t nat -A SHADOWSOCKS -p tcp --dport 1081:65535 -j RETURN");

        // Добавляем правило для перенаправления трафика через ShadowSocks
        shell_exec("iptables -t nat -A SHADOWSOCKS -p tcp --dport 80,443,53,$WireGuardPort -j REDIRECT --to-ports 1080");

        // Применяем цепочку SHADOWSOCKS к интерфейсу WireGuard
        shell_exec("iptables -t nat -A PREROUTING -i $WireGuard -p tcp -j SHADOWSOCKS");
    }

    /**
     * Останавливает маршрутизацию трафика через ShadowSocks.
     *
     * @param string $WireGuard Имя интерфейса WireGuard.
     */
    public static function ss_stop_routing_traffic(string $WireGuard): void
    {
        // Удаляем правило, применяющее цепочку SHADOWSOCKS к интерфейсу WireGuard
        shell_exec("iptables -t nat -D PREROUTING -i $WireGuard -p tcp -j SHADOWSOCKS");

        // Очищаем и удаляем цепочку SHADOWSOCKS
        shell_exec('iptables -t nat -F SHADOWSOCKS');
        shell_exec('iptables -t nat -X SHADOWSOCKS');
    }

    /**
     * Сохраняет конфигурацию ShadowSocks.
     *
     * @param string $server IP-адрес сервера ShadowSocks.
     * @param int $server_port Порт сервера ShadowSocks.
     * @param int $local_port Локальный порт для прослушивания.
     * @param string $password Пароль для аутентификации на сервере ShadowSocks.
     * @param int $timeout Таймаут в секундах.
     * @param string $method Метод шифрования.
     */
    public static function ss_save_config(
        string $server = '0.0.0.0',
        int    $server_port = 8388,
        int    $local_port = 8388,
        string $password = 'your_password',
        int    $timeout = 60,
        string $method = 'aes-256-cfb',
    ): void
    {
        // Создаем массив с конфигурацией
        $config = [
            'server' => $server,
            'server_port' => $server_port,
            'local_port' => $local_port,
            'password' => $password,
            'timeout' => $timeout,
            'method' => $method,
        ];

        // Сохраняем конфигурацию в файл
        self::ss_put_config(json_encode($config, JSON_PRETTY_PRINT));
    }

    /**
     * Возвращает текущую конфигурацию ShadowSocks.
     *
     * @return string Текущая конфигурация ShadowSocks в формате JSON.
     */
    public static function ss_get_config(): string
    {
        // Читаем и возвращаем содержимое файла конфигурации
        return file_get_contents("/etc/shadowsocks-libev/config.json");
    }

    /**
     * Записывает данные в файл конфигурации ShadowSocks.
     *
     * @param mixed $data Данные для записи.
     * @return false|int Количество байт, записанных в файл, или false в случае ошибки.
     */
    public static function ss_put_config(mixed $data): false|int
    {
        // Записываем данные в файл и возвращаем результат
        return file_put_contents("/etc/shadowsocks-libev/config.json", $data, 0600);
    }

    /**
     * Запускает службу ShadowSocks.
     */
    public static function ss_start(): void
    {
        // Включаем и запускаем службу ShadowSocks
        shell_exec('systemctl enable shadowsocks-libev-local@config.service');
        shell_exec('systemctl start shadowsocks-libev-local@config.service');
    }

    /**
     * Перезапускает службу ShadowSocks.
     */
    public static function ss_restart(): void
    {
        // Перезапускаем службу ShadowSocks
        shell_exec('systemctl restart shadowsocks-libev-local@config.service');
    }

    /**
     * Останавливает службу ShadowSocks.
     */
    public static function ss_stop(): void
    {
        // Останавливаем и отключаем службу ShadowSocks
        shell_exec('systemctl stop shadowsocks-libev-local@config.service');
        shell_exec('systemctl disable shadowsocks-libev-local@config.service');
    }
}
