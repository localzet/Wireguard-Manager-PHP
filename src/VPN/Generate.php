<?php

namespace localzet\VPN;

use Exception;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Trait Generate
 *
 * Этот trait предоставляет набор функций для генерации ключей и IP-адресов.
 */
trait Generate
{
    /**
     * Генерирует приватный ключ, публичный ключ и предварительно разделенный ключ для WireGuard.
     *
     * @return array Массив с приватным ключом, публичным ключом и предварительно разделенным ключом.
     */
    #[ArrayShape([
        'PrivateKey' => 'string',
        'PublicKey' => 'string',
        'PresharedKey' => 'string',
    ])]
    public static function generate_keys(): array
    {
        // Генерируем приватный ключ с помощью команды wg genkey
        $PrivateKey = str_replace("\n", "", shell_exec('wg genkey'));
        // Генерируем публичный ключ из приватного ключа с помощью команды wg pubkey
        $PublicKey = str_replace("\n", "", shell_exec('echo ' . $PrivateKey . ' | wg pubkey'));
        // Генерируем предварительно разделенный ключ с помощью команды wg genpsk
        $PresharedKey = str_replace("\n", "", shell_exec('wg genpsk'));

        return [
            'PrivateKey' => $PrivateKey,
            'PublicKey' => $PublicKey,
            'PresharedKey' => $PresharedKey,
        ];
    }

    /**
     * Генерирует IP-адрес для нового клиента на основе заданного шаблона и списка уже используемых IP-адресов.
     *
     * @param array $IPs Массив с уже используемыми IP-адресами.
     * @param string $Template Шаблон для генерации IP-адреса. По умолчанию используется шаблон '10.66.66.x'.
     * @return string Сгенерированный IP-адрес.
     * @throws Exception Если достигнут максимум клиентов.
     */
    public static function generate_ip(array $IPs, string $Template = '10.66.66.x'): string
    {
        // Инициализируем переменную для хранения сгенерированного адреса
        $Address = null;

        // Проверяем каждый возможный IP-адрес в диапазоне от 2 до 254
        for ($i = 2; $i < 255; $i++) {
            // Фильтруем список IP-адресов, исключая адреса, которые уже используются или не соответствуют шаблону
            $Client = array_filter($IPs, function ($IP) use ($Template, $i) {
                return $IP === str_replace('x', $i, $Template) || !str_starts_with($IP, str_replace('x', '', $Template));
            });

            // Если такого адреса еще нет в списке, используем его
            if (!$Client) {
                $Address = str_replace("x", $i, $Template);
                break;
            }
        }

        // Если все адреса уже заняты, выбрасываем исключение
        if (!$Address) {
            throw new Exception("Достигнут максимум клиентов.");
        }

        return $Address;
    }
}
