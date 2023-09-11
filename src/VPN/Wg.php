<?php

namespace localzet\VPN;

use DateTime;
use Exception;

/**
 * Trait Wg
 *
 * Этот trait предоставляет набор функций для управления сервером WireGuard.
 * WireGuard - это простой, быстрый и современный VPN, который использует передовые криптографические протоколы.
 * @see https://ru.wikipedia.org/wiki/WireGuard
 */
trait Wg
{
    /**
     * Возвращает статус интерфейса WireGuard.
     *
     * @param string $WireGuard Имя интерфейса WireGuard.
     * @return string Статус интерфейса WireGuard.
     */
    public static function wg_status(string $WireGuard): string
    {
        // Выполняем команду wg show и возвращаем результат
        return shell_exec("wg show $WireGuard");
    }

    /**
     * Возвращает дамп конфигурации интерфейса WireGuard.
     *
     * @param string $WireGuard Имя интерфейса WireGuard.
     * @param string|null $TargetPublicKey Публичный ключ целевого пира (если есть).
     * @return array Массив с информацией о пирах интерфейса WireGuard.
     * @throws Exception
     */
    public static function wg_dump(string $WireGuard, ?string $TargetPublicKey = null): array
    {
        // Выполняем команду wg show dump и разбиваем результат на строки
        $dump = shell_exec("wg show $WireGuard dump");
        $lines = explode("\n", trim($dump));
        $lines = array_slice($lines, 1);

        $return = [];

        foreach ($lines as $line) {
            list(
                $PublicKey,
                $PresharedKey,
                $Endpoint,
                $AllowedIPs,
                $Online,
                $TransferRx,
                $TransferTx,
                $PersistentKeepalive
                ) = explode("\t", $line);

            if ($TargetPublicKey && $PublicKey !== $TargetPublicKey) {
                continue;
            }

            // Создаем массив с информацией о пире
            $result = [];
            $result['PresharedKey'] = $PresharedKey;
            $result['Endpoint'] = $Endpoint;
            $result['AllowedIPs'] = $AllowedIPs;
            $result['PersistentKeepalive'] = $PersistentKeepalive;
            // Преобразуем время последнего приема в объект DateTime или null, если пир не в сети
            $result['Online'] = $Online === '0' ? null : new DateTime('@' . (int)$Online);
            // Преобразуем объемы передачи данных в целые числа
            $result['TransferRx'] = (int)$TransferRx;
            $result['TransferTx'] = (int)$TransferTx;

            if ($TargetPublicKey) {
                // Если указан TargetPublicKey, возвращаем информацию только об этом пире
                return $result;
            } else {
                // Иначе добавляем информацию о пире в массив
                $return[] = $result;
            }
        }

        return $return;
    }

    /**
     * Синхронизирует конфигурацию интерфейса WireGuard с текущим состоянием системы.
     *
     * @param string $WireGuard Имя интерфейса WireGuard.
     */
    public static function wg_sync_config(
        string $WireGuard
    ): void
    {
        // Создаем временный файл для хранения текущей конфигурации интерфейса
        // Выполняем команду wg-quick strip для получения текущей конфигурации интерфейса без приватных ключей
        // Затем выполняем команду wg syncconf для применения этой конфигурации к интерфейсу
        // Наконец, удаляем временный файл
        $tempFile = tempnam(sys_get_temp_dir(), 'wg');
        shell_exec('wg-quick strip ' . escapeshellarg($WireGuard) . ' > ' . escapeshellarg($tempFile));
        shell_exec('wg syncconf ' . escapeshellarg($WireGuard) . ' ' . escapeshellarg($tempFile));
        unlink($tempFile);
    }

    /**
     * Сохраняет конфигурацию интерфейса WireGuard.
     *
     * @param string $WireGuard Имя интерфейса WireGuard.
     * @param string|array $Address IP-адрес(а) интерфейса.
     * @param int $ListenPort Порт для прослушивания.
     * @param string $PrivateKey Приватный ключ интерфейса.
     * @param array $Peers Массив с информацией о пирах.
     * @return false|int Количество байт, записанных в файл, или false в случае ошибки.
     */
    public static function wg_save_config(
        string       $WireGuard,
        string|array $Address,
        int          $ListenPort,
        string       $PrivateKey,
        array        $Peers,
        ?string      $Endpoint = null,
        ?int         $PersistentKeepalive = null,
    ): false|int
    {
        // Форматируем конфигурацию сервера
        $data = self::format_server_config($WireGuard, $Address, $ListenPort, $PrivateKey, $Peers, $Endpoint, $PersistentKeepalive);
        // Записываем конфигурацию в файл и возвращаем результат
        return self::wg_put_config($WireGuard, $data);
    }

    /**
     * Возвращает текущую конфигурацию интерфейса WireGuard.
     *
     * @param string $WireGuard Имя интерфейса WireGuard.
     * @return string Текущая конфигурация интерфейса WireGuard.
     */
    public static function wg_get_config(string $WireGuard): string
    {
        // Читаем и возвращаем содержимое файла конфигурации
        return file_get_contents("/etc/wireguard/$WireGuard.conf");
    }

    /**
     * Записывает данные в файл конфигурации интерфейса WireGuard.
     *
     * @param string $WireGuard Имя интерфейса WireGuard.
     * @param mixed $data Данные для записи.
     * @return false|int Количество байт, записанных в файл, или false в случае ошибки.
     */
    public static function wg_put_config(string $WireGuard, mixed $data): false|int
    {
        // Записываем данные в файл и возвращаем результат
        return file_put_contents("/etc/wireguard/$WireGuard.conf", $data, 0600);
    }

    /**
     * Запускает службу WireGuard для указанного интерфейса.
     *
     * @param string $WireGuard Имя интерфейса WireGuard.
     */
    public static function wg_start(string $WireGuard): void
    {
        // Включаем и запускаем службу WireGuard для указанного интерфейса
        shell_exec("systemctl start wg-quick@$WireGuard.service");
        shell_exec("systemctl enable wg-quick@$WireGuard.service");
    }

    /**
     * Перезапускает службу WireGuard для указанного интерфейса.
     *
     * @param string $WireGuard Имя интерфейса WireGuard.
     */
    public static function wg_restart(string $WireGuard): void
    {
        // Перезапускаем службу WireGuard для указанного интерфейса
        shell_exec("systemctl restart wg-quick@$WireGuard.service");
    }

    /**
     * Останавливает службу WireGuard для указанного интерфейса.
     *
     * @param string $WireGuard Имя интерфейса WireGuard.
     */
    public static function wg_stop(string $WireGuard): void
    {
        // Останавливаем и отключаем службу WireGuard для указанного интерфейса
        shell_exec("systemctl stop wg-quick@$WireGuard.service");
        shell_exec("systemctl disable wg-quick@$WireGuard.service");
    }
}
