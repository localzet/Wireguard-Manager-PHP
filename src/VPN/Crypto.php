<?php

namespace localzet\VPN;

use Exception;
use JetBrains\PhpStorm\ArrayShape;

/**
 * Трейт для генерации ключей шифрования
 */
trait Crypto
{
    /**
     * Метод для генерации ключей шифрования
     *
     * Генерирует приватный, публичный и предварительно согласованный ключи
     *
     * @return array Массив с ключами шифрования
     *
     * @throws Exception Если выполнение команд не удалось
     */
    #[ArrayShape([
        'private' => 'string',
        'public' => 'string',
        'preshared' => 'string',
    ])]
    public static function generateKeys(): array
    {
        // Генерация приватного ключа
        $PrivateKey = self::exec('wg genkey');
        // Генерация публичного ключа из приватного
        $PublicKey = self::exec("echo $PrivateKey | wg pubkey");
        // Генерация предварительно согласованного ключа
        $PresharedKey = self::exec("wg genpsk");

        return [
            'private' => $PrivateKey,
            'public' => $PublicKey,
            'preshared' => $PresharedKey,
        ];
    }
}
