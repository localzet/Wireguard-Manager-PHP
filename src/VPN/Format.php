<?php

namespace localzet\VPN;


/**
 * Trait Format
 *
 * Этот trait предоставляет набор функций для форматирования конфигураций.
 */
trait Format
{
    /**
     * Форматирует конфигурацию сервера WireGuard.
     *
     * @param string $WireGuard Имя интерфейса WireGuard.
     * @param string|array $Address IP-адрес(а) интерфейса.
     * @param int $ListenPort Порт для прослушивания.
     * @param string $PrivateKey Приватный ключ интерфейса.
     * @param array $Peers Массив с информацией о пирах.
     * @return string Отформатированная конфигурация сервера WireGuard.
     */
    public static function format_server_config(
        string       $WireGuard,
        string|array $Address,
        int          $ListenPort,
        string       $PrivateKey,
        array        $Peers,
        ?string      $Endpoint = null,
        ?int         $PersistentKeepalive = null,
    ): string
    {
        // Начинаем формирование конфигурации
        $Configuration = "# Не редактируй файл вручную!!!\n";
        $Configuration .= "# Все изменения будут перезаписаны!\n\n";

        // Добавляем информацию об интерфейсе
        $Configuration .= "[Interface]\n";
        $Configuration .= "Address = " . (is_array($Address) ? implode(', ', $Address) : $Address) . "\n";
        $Configuration .= "ListenPort = $ListenPort\n";
        $Configuration .= "PrivateKey = $PrivateKey\n";

        // Добавляем post-up и post-down скрипты для настройки iptables
        // Эти скрипты выполняются после поднятия и перед опусканием интерфейса соответственно
        // Они настраивают iptables для маскарадинга (NAT) и разрешения трафика через VPN
        $Configuration .= "PostUp = iptables -t nat -A POSTROUTING -o `ip route | awk '/default/ {print $5; exit}'` -j MASQUERADE\n";
        $Configuration .= "PostUp = iptables -A INPUT -p udp -m udp --dport $ListenPort -j ACCEPT\n";
        $Configuration .= "PostUp = iptables -A FORWARD -i $WireGuard -j ACCEPT\n";
        $Configuration .= "PostUp = iptables -A FORWARD -o $WireGuard -j ACCEPT\n";
        $Configuration .= "PostUp = iptables -A FORWARD -i `ip route | awk '/default/ {print $5; exit}'` -o $WireGuard -j ACCEPT\n";

        // Добавляем post-down скрипты для удаления правил iptables, добавленных post-up скриптами
        // Эти скрипты выполняются перед опусканием интерфейса
        // Они удаляют правила iptables, добавленные post-up скриптами, чтобы вернуть систему в исходное состояние
        $Configuration .= "PostDown = iptables -t nat -D POSTROUTING -o `ip route | awk '/default/ {print $5; exit}'` -j MASQUERADE\n";
        $Configuration .= "PostDown = iptables -D INPUT -p udp -m udp --dport $ListenPort -j ACCEPT\n";
        $Configuration .= "PostDown = iptables -D FORWARD -i $WireGuard -j ACCEPT\n";
        $Configuration .= "PostDown = iptables -D FORWARD -o $WireGuard -j ACCEPT\n";
        $Configuration .= "PostDown = iptables -D FORWARD -i `ip route | awk '/default/ {print $5; exit}'` -o $WireGuard -j ACCEPT\n";

        // Добавляем информацию о каждом пире
        foreach ($Peers as $Peer) {
            // Комментарий с идентификатором пира
            $Configuration .= "\n# Клиент: {$Peer['Name']} ({$Peer['id']})\n";
            // Информация о пире
            // Публичный ключ пира, разрешенные IP-адреса, конечная точка (если есть), предварительно разделенный ключ (если есть) и интервал keepalive (если есть)
            $Configuration .= "[Peer]\n";
            $Configuration .= "PublicKey = {$Peer['PublicKey']}\n";
            if (isset($Peer['AllowedIPs'])) {
                $Configuration .= "AllowedIPs = " . (is_array($Peer['Address']) ? implode(', ', $Peers['Address']) : $Peer['Address']) . "\n";
            }
            if (isset($Peer['Endpoint']) || $Endpoint) {
                $Configuration .= "Endpoint = " . ($Peer['Endpoint'] ?? $Endpoint) . "\n";
            }
            if (isset($Peer['PresharedKey'])) {
                $Configuration .= "PresharedKey = {$Peer['PresharedKey']}\n";
            }
            if (isset($Peer['PersistentKeepalive']) || $PersistentKeepalive) {
                $Configuration .= "PersistentKeepalive = " . ($Peer['PersistentKeepalive'] ?? $PersistentKeepalive) . "\n";
            }
        }

        return $Configuration;
    }

    /**
     * Форматирует конфигурацию клиента WireGuard.
     *
     * @param string $Address IP-адрес клиента.
     * @param string $PrivateKey Приватный ключ клиента.
     * @param array $Peers Массив с информацией о пирах.
     * @param string|array|null $DNS DNS-сервер(ы) для использования клиентом.
     * @param int|null $MTU MTU (Maximum Transmission Unit) для интерфейса клиента.
     * @param string|null $Endpoint Конечная точка (endpoint) для подключения клиента.
     * @param string|null $PresharedKey Предварительно разделенный ключ для дополнительной безопасности.
     * @param int|null $PersistentKeepalive Интервал отправки keepalive-пакетов для поддержания соединения.
     * @return string Отформатированная конфигурация клиента WireGuard.
     */
    public static function format_client_config(
        string            $Address,
        string            $PrivateKey,
        array             $Peers,
        string|array|null $DNS = null,
        ?int              $MTU = null,
        ?string           $Endpoint = null,
        ?string           $PresharedKey = null,
        ?int              $PersistentKeepalive = null,
    ): string
    {
        // Начинаем формирование конфигурации
        $Configuration = "# Конфигурация сгенерирована в Localzet Config Generator\n";
        $Configuration .= "[Interface]\n";
        $Configuration .= "Address = $Address\n";
        $Configuration .= "PrivateKey = $PrivateKey\n";

        // Добавляем DNS-серверы, если они указаны
        if ($DNS !== null) {
            $Configuration .= "DNS = " . (is_array($DNS) ? implode(', ', $DNS) : $DNS) . "\n";
        }

        // Добавляем MTU, если он указан
        if ($MTU !== null) {
            $Configuration .= "MTU = $MTU\n";
        }

        // Добавляем информацию о каждом пире
        foreach ($Peers as $Peer) {
            // Добавляем комментарий с идентификатором пира
            $Configuration .= "\n# Сервер: {$Peer['id']}\n";
            // Добавляем информацию о пире
            $Configuration .= "[Peer]\n";
            // Публичный ключ пира
            $Configuration .= "PublicKey = {$Peer['PublicKey']}\n";
            // Разрешенные IP-адреса для пира
            if (isset($Peer['AllowedIPs'])) {
                $Configuration .= "AllowedIPs = " . (is_array($Peer['Address']) ? implode(', ', $Peer['Address']) : $Peer['Address']) . "\n";
            }
            // Конечная точка для подключения к пиру
            if (isset($Peer['Endpoint']) || $Endpoint) {
                $Configuration .= "Endpoint = " . ($Peer['Endpoint'] ?? $Endpoint) . "\n";
            }
            // Предварительно разделенный ключ для дополнительной безопасности
            if (isset($Peer['PresharedKey']) || $PresharedKey) {
                $Configuration .= "PresharedKey = " . ($Peer['PresharedKey'] ?? $PresharedKey) . "\n";
            }
            // Интервал отправки keepalive-пакетов для поддержания соединения
            if (isset($Peer['PersistentKeepalive']) || $PersistentKeepalive) {
                $Configuration .= "PersistentKeepalive = " . ($Peer['PersistentKeepalive'] ?? $PersistentKeepalive) . "\n";
            }
        }

        return $Configuration;
    }
}
