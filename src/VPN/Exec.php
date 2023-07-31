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
     * @var array|null
     */
    private ?array $ssh_config = null;

    /**
     * @var SSH2|null
     */
    private ?SSH2 $ssh = null;

    /**
     * @param string $exec
     * @return string
     * @throws Exception
     */
    private function exec(string $exec): string
    {
        if ($this->ssh()) {
            $output = $this->ssh()->exec($exec);
        } else {
            $output = exec($exec, $output, $result_code);
            if ($result_code) {
                $output = implode("\n", $output);
                throw new Exception("Вызов команды не удался.\nКоманда: {$exec}\n\n{$output}");
            }
        }

        // Преобразование кодировки вывода
//        $output = mb_convert_encoding($output, "ISO-8859-1", "UTF-8");

        // Замена символа новой строки на пробел
        return str_replace("\n", " ", $output);
    }

    /**
     * @return SSH2|null
     * @throws InvalidArgumentException
     * @throws Exception
     */
    private function ssh(): ?SSH2
    {
        if (class_exists('\phpseclib3\Net\SSH2') && $this->ssh_config) {
            if (!$this->ssh) {
                if (
                    !isset($this->ssh_config["ip"]) ||
                    !isset($this->ssh_config["port"]) ||
                    !isset($this->ssh_config["user"]) ||
                    !isset($this->ssh_config["privatekey"])
                ) {
                    throw new InvalidArgumentException("Недостаточно параметров Exec");
                }

                $ssh = new SSH2($this->ssh_config["ip"], $this->ssh_config["port"]);
                $key = PublicKeyLoader::load(file_get_contents($this->ssh_config["privatekey"]));

                if (!$ssh->login($this->ssh_config["user"], $key)) {
                    throw new Exception('Ошибка авторизации');
                }

                return $ssh;
            } else return $this->ssh;
        }
        return null;
    }
}