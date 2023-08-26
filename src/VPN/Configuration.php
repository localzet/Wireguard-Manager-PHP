<?php

namespace localzet\VPN;

use Exception;

/**
 * Трейт для создания конфигурации сервера и клиента
 */
trait Configuration
{
    /**
     * Метод для создания конфигурации сервера
     *
     * @param string $IPv4 IP-адрес сервера
     * @param int|string $ListenPort Порт для прослушивания
     * @param string $Interface Интерфейс для настройки правил межсетевого экрана
     * @param string $WireGuard Имя интерфейса WireGuard
     * @param string $PrivateKey Приватный ключ сервера
     * @param array $Users Массив с информацией о пользователях
     *
     * @return string Строка с конфигурацией сервера
     */
    public static function serverConfiguration(
        string     $IPv4,
        int|string $ListenPort,
        string     $Interface,
        string     $WireGuard,
        string     $PrivateKey,
        array      $Users,
    ): string
    {
        // Получение правил межсетевого экрана для добавления и удаления
        $IPTables = self::IPTables(
            $IPv4,
            $ListenPort,
            $Interface,
            $WireGuard,
        );

        // Создание строки с конфигурацией сервера
        $config = <<<EOF
# Не редактируй файл вручную!!!
# Все изменения будут перезаписаны!

[Interface]
PrivateKey = {$PrivateKey}
Address = {$IPv4}/24
ListenPort = {$ListenPort}
PostUp = {$IPTables['add']}
PostDown = {$IPTables['del']}

EOF;
        // Добавление информации о пользователях в конфигурацию сервера
        foreach ($Users as $User) {
            $config .= <<<EOF

# Клиент: {$User['Id']}
[Peer]
AllowedIPs = {$User['IPv4']}/32
PublicKey = {$User['PublicKey']}
PresharedKey = {$User['PresharedKey']}
EOF;
        }

        return $config;
    }

    /**
     * Метод для создания конфигурации клиента
     *
     * @param string $UserIPv4 IP-адрес клиента
     * @param string $UserIPv6 IPv6-адрес клиента (не используется)
     * @param string $UserPrivateKey Приватный ключ клиента
     * @param string $UserPresharedKey Предварительно согласованный ключ клиента
     * @param string $ServerAddress IP-адрес или доменное имя сервера
     * @param string $ServerPort Порт сервера для подключения
     * @param string $ServerPublicKey Публичный ключ сервера
     *
     * @return string Строка с конфигурацией клиента
     */
    public static function clientConfiguration(
        string $UserIPv4,
        string $UserIPv6,
        string $UserPrivateKey,
        string $UserPresharedKey,
        string $ServerAddress,
        string $ServerPort,
        string $ServerPublicKey,
    ): string
    {
        return <<<EOF
[Interface]
PrivateKey = {$UserPrivateKey}
Address = {$UserIPv4}/32
DNS = 1.1.1.1, 8.8.8.8

[Peer]
PublicKey = {$ServerPublicKey}
PresharedKey = {$UserPresharedKey}
Endpoint = {$ServerAddress}:{$ServerPort}
AllowedIPs = 0.0.0.0/0, ::/0
EOF;
    }

    /**
     * Метод для получения следующего свободного IP-адреса для клиента
     *
     * Использует шаблон IP-адреса и список текущих пользователей для определения следующего свободного IP-адреса
     *
     * @param array $Users Массив с информацией о текущих пользователях
     * @param string $Template Шаблон IP-адреса с заменяемым символом "x"
     *
     * @return string Следующий свободный IP-адрес
     *
     * @throws Exception Если достигнут максимум клиентов
     */
    public static function nextIPv4(array $Users, string $Template = '10.66.66.x'): string
    {
        $Address = null;
        // Поиск свободного IP-адреса, заменяя символ "x" в шаблоне на числа от 2 до 254
        for ($i = 2; $i < 255; $i++) {
            $Client = null;
            // Проверка, используется ли текущий IP-адрес одним из текущих пользователей
            foreach ($Users as $User) {
                if ($User['Address'] === str_replace("x", $i, $Template)) {
                    $Client = $User;
                    break;
                }
            }

            // Если текущий IP-адрес не используется, присваиваем его в качестве следующего свободного
            if (!$Client) {
                $Address = str_replace("x", $i, $Template);
                break;
            }
        }

        // Если свободный IP-адрес не найден, выбрасываем исключение
        if (!$Address) {
            throw new Exception("Достигнут максимум клиентов.");
        }

        return $Address;
    }
}
