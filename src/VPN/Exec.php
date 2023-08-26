<?php

namespace localzet\VPN;

use Exception;
use InvalidArgumentException;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Net\SSH2;

/**
 * Трейт для отправки команд в терминал
 */
trait Exec
{
    /**
     * @var array|null Конфигурация SSH
     */
    protected static ?array $ssh_config = null;

    /**
     * @var SSH2|null Экземпляр SSH2 для подключения к удаленному серверу
     */
    private static ?SSH2 $ssh = null;

    /**
     * Метод для выполнения команды в терминале
     *
     * @param string $exec Команда для выполнения
     *
     * @return string Результат выполнения команды
     *
     * @throws Exception Если выполнение команды не удалось
     */
    protected static function exec(string $exec): string
    {
        if (self::ssh()) {
            // Выполнение команды через SSH
            $output = self::ssh()->exec($exec);
        } else {
            // Выполнение команды локально
            $output = exec($exec, $output, $result_code);
            if ($result_code) {
                $output = implode("\n", $output);
                throw new Exception("Вызов команды не удался.\nКоманда: {$exec}\n\n{$output}");
            }
        }

        // Замена символа новой строки на пробел
        return str_replace("\n", " ", $output);
    }

    /**
     * Метод для получения экземпляра SSH2 для подключения к удаленному серверу
     *
     * @return SSH2|null Экземпляр SSH2 или null, если подключение не удалось
     *
     * @throws InvalidArgumentException Если конфигурация SSH недостаточна
     * @throws Exception Если авторизация не удалась
     */
    protected static function ssh(): ?SSH2
    {
        if (class_exists('\phpseclib3\Net\SSH2') && self::$ssh_config) {
            if (!self::$ssh) {
                // Проверка наличия необходимых параметров конфигурации SSH
                if (
                    !isset(self::$ssh_config["ip"]) ||
                    !isset(self::$ssh_config["port"]) ||
                    !isset(self::$ssh_config["user"]) ||
                    !isset(self::$ssh_config["privatekey"])
                ) {
                    throw new InvalidArgumentException("Недостаточно параметров Exec");
                }

                // Создание экземпляра SSH2 и подключение к удаленному серверу
                $ssh = new SSH2(self::$ssh_config["ip"], self::$ssh_config["port"]);
                $key = PublicKeyLoader::load(file_get_contents(self::$ssh_config["privatekey"]));

                // Авторизация на удаленном сервере с использованием закрытого ключа
                if (!$ssh->login(self::$ssh_config["user"], $key)) {
                    throw new Exception('Ошибка авторизации');
                }

                return $ssh;
            } else return self::$ssh;
        }
        return null;
    }
}
