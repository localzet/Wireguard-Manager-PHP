<?php

namespace localzet\VPN;

use Exception;

/**
 * Трейт для установки и удаления зависимостей
 */
trait Dependencies
{
    /**
     * Метод для установки зависимостей
     *
     * Выполняет обновление списка пакетов и устанавливает необходимые пакеты
     *
     * @throws Exception Если выполнение команд не удалось
     */
    public static function installDependencies(): void
    {
        // Обновление списка пакетов
        self::exec('apt update');
        // Установка необходимых пакетов
        self::exec('apt install -y wireguard wireguard-tools iproute2 iptables');
    }

    /**
     * Метод для удаления зависимостей
     *
     * Удаляет установленные пакеты и обновляет список пакетов
     *
     * @throws Exception Если выполнение команд не удалось
     */
    public static function removeDependencies(): void
    {
        // Удаление установленных пакетов
        self::exec('apt purge -y wireguard wireguard-tools');
        // Обновление списка пакетов
        self::exec('apt update');
    }
}
