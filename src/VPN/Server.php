<?php

namespace localzet\VPN;

use Exception;

/**
 * Трейт для управления сервером WireGuard
 */
trait Server
{
    /**
     * Метод для запуска сервера WireGuard
     *
     * Сохраняет конфигурацию сервера в файл и запускает службу WireGuard
     *
     * @param string $WireGuard Имя интерфейса WireGuard
     * @param string $Config Строка с конфигурацией сервера
     *
     * @return void
     *
     * @throws Exception Если выполнение команд не удалось
     */
    public static function start(
        string $WireGuard,
        string $Config
    ): void
    {
        // Сохранение конфигурации сервера в файл
        file_put_contents("/etc/wireguard/$WireGuard.conf", $Config);
        // Установка прав доступа к файлу конфигурации
        chmod("/etc/wireguard/$WireGuard.conf", 0600);

        // Включение IP-переадресации
        self::IPForwarding();

        // Включение и запуск службы WireGuard
        self::exec("systemctl enable wg-quick@$WireGuard.service");
        self::exec("systemctl start wg-quick@$WireGuard.service");
    }

    /**
     * Метод для перезапуска сервера WireGuard
     *
     * Перезапускает службу WireGuard
     *
     * @param string $WireGuard Имя интерфейса WireGuard
     *
     * @return void
     *
     * @throws Exception Если выполнение команд не удалось
     */
    public static function restart(
        string $WireGuard
    ): void
    {
        // Перезапуск службы WireGuard
        self::exec("systemctl restart wg-quick@$WireGuard.service");
    }

    /**
     * Метод для остановки сервера WireGuard
     *
     * Останавливает и отключает службу WireGuard
     *
     * @param string $WireGuard Имя интерфейса WireGuard
     *
     * @return void
     *
     * @throws Exception Если выполнение команд не удалось
     */
    public static function stop(
        string $WireGuard
    ): void
    {
        // Отключение и остановка службы WireGuard
        self::exec("systemctl disable wg-quick@$WireGuard.service");
        self::exec("systemctl stop wg-quick@$WireGuard.service");
    }
}
