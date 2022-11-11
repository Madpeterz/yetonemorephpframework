<?php

namespace YAPF\Framework\Cache;

use Exception;

abstract class CacheTables extends CacheDatastore
{
    protected array $tableConfig = [];
    // table => singles [true|false], sets [true|false], encryptData [true|false]
    protected ?string $encryptKeycode = null;

    public function setEncryptKeyCode(?string $code): void
    {
        $this->encryptKeycode = $code;
    }

    public function addTableToCache(
        string $tableName,
        int $maxAgeInMins = 15,
        bool $enableForSingles = true,
        bool $enableForSets = false,
        bool $encryptData = false
    ): void {
        $this->tableConfig[$tableName] = [
            "single" => $enableForSingles,
            "set" => $enableForSets,
            "encrypt" => $encryptData,
            "maxAge" => $maxAgeInMins,
        ];
    }

    protected function tableUsesCache(string $table, bool $asSingle = true): bool
    {
        if (array_key_exists($table, $this->tableConfig) == false) {
            return false;
        }
        if (($this->tableConfig[$table]["encrypt"] == true) && ($this->encryptKeycode == null)) {
            return false;
        }
        $source = "set";
        if ($asSingle == true) {
            $source = "single";
        }
        return $this->tableConfig[$table][$source];
    }

    protected function makeKey(string $key): string
    {
        while (mb_strlen($key, '8bit') < SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            $key .= $key . "abcd" . $key;
        }
        if (mb_strlen($key, '8bit') > SODIUM_CRYPTO_SECRETBOX_KEYBYTES) {
            $key = mb_substr($key, 0, SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
        }
        return $key;
    }
    protected function decrypt(string $encrypted, string $key): ?string
    {
        $key = $this->makeKey($key);
        $decoded = base64_decode($encrypted);
        $nonce = mb_substr($decoded, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, '8bit');
        $ciphertext = mb_substr($decoded, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES, null, '8bit');

        $plain = sodium_crypto_secretbox_open(
            $ciphertext,
            $nonce,
            $key
        );
        if (!is_string($plain)) {
            return null;
        }
        sodium_memzero($ciphertext);
        sodium_memzero($key);
        return $plain;
    }
    protected function encrypt(string $message, string $key): string
    {
        $key = $this->makeKey($key);
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = base64_encode(
            $nonce .
            sodium_crypto_secretbox(
                $message,
                $nonce,
                $key
            )
        );
        sodium_memzero($message);
        sodium_memzero($key);
        return $cipher;
    }

    /**
     * It takes a table name and an array of data, and returns a string of data that can be stored in the
     * cache if needed it will also encrypt the data
     * @param string table The name of the table you want to pack.
     * @param array raw The data to be packed.
     */
    protected function tablePackString(string $table, array $raw): string
    {
        $dataString = json_encode($raw);
        if ($this->tableConfig[$table]["encrypt"] == true) {
            $dataString = $this->encrypt($dataString, $table . $this->encryptKeycode);
        }
        return $dataString;
    }

    /**
     * It decrypts the data if needed, then decodes it from JSON
     * @param string table The name of the table you want to unpack.
     * @param string raw The raw string from the database
     * @return ?mixed[] The data is being returned as an array.
     */
    protected function tableUnpackString(string $table, string $raw): ?array
    {
        if ($this->tableConfig[$table]["encrypt"] == true) {
            $raw = $this->decrypt($raw, $table . $this->encryptKeycode);
        }
        $data = null;
        try {
            $data = json_decode($raw, true);
            return $data;
        } catch (Exception $e) {
            return null;
        }
    }
}
