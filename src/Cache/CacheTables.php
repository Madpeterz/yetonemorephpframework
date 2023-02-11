<?php

namespace YAPF\Framework\Cache;

use Exception;
use YAPF\Framework\Responses\Cache\CacheStatusReply;

abstract class CacheTables extends CacheDatastore
{
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
        bool $encryptData = false,
    ): CacheStatusReply {
        if ($maxAgeInMins < 1) {
            return new CacheStatusReply("invaild max age");
        }
        $this->tableConfig[$tableName] = [
            "single" => $enableForSingles,
            "set" => $enableForSets,
            "encrypt" => $encryptData,
            "maxAge" => $maxAgeInMins,
        ];
        if (is_array($this->tablesLastChanged) == false) {
            $this->tablesLastChanged = [];
        }
        if (array_key_exists($tableName, $this->tablesLastChanged) == false) {
            $this->tablesLastChanged[$tableName] = ["version" => 1, "time" => 0];
        }
        return new CacheStatusReply("ok", true);
    }

    public function markChangeToTable(string $table): void
    {
        if (is_array($this->tablesLastChanged) == false) {
            return;
        }
        if (array_key_exists($table, $this->tableConfig) == false) {
            return;
        }
        $vnumber = $this->tablesLastChanged[$table]["version"] + 1;
        if ($vnumber > 999) {
            $vnumber = 1;
        }
        $this->tablesLastChanged[$table] = ["version" => $vnumber, "time" => time()];
    }

    protected function tableUsesCache(string $table, bool $asSingle = true): bool
    {
        if ($this->haveDriver() == false) {
            return false;
        }
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
        return json_encode([
            "version" => $this->tablesLastChanged[$table]["version"],
            "table" => $table,
            "time" => $this->tablesLastChanged[$table]["time"],
            "data" => $dataString,
        ]);
    }

    /**
     * It checks if the table exists, if the table has a last changed time, if the dataset has a table,
     * time, and data key, and if all of those are true, it returns the dataset
     * @param string table The name of the table to unpack
     * @param string raw The raw data from the database
     * @return ?mixed[] The dataset is being returned.
     */
    protected function tableUnpackValidate(string $table, string $raw): ?array
    {
        $dataset = json_decode($raw, true);
        if (array_key_exists($table, $this->tableConfig) == false) {
            return null;
        }
        if (array_key_exists($table, $this->tablesLastChanged) == false) {
            return null;
        }
        if (array_key_exists("table", $dataset) == false) {
            return null;
        }
        if (array_key_exists("time", $dataset) == false) {
            return null;
        }
        if (array_key_exists("data", $dataset) == false) {
            return null;
        }
        return $dataset;
    }

    protected function tableUnpackChecks(
        string $foundTable,
        string $sourceTable,
        int $time,
        string $version
    ): bool {
        if ($foundTable != $sourceTable) {
            // very rare hash collided ignore the data
            return false;
        }
        if ($time != $this->tablesLastChanged[$sourceTable]["time"]) {
            // table has had changes from when this data was put into cache
            // ignore the data and reload
            return false;
        }
        if ($version != $this->tablesLastChanged[$sourceTable]["version"]) {
            // table version has changed
            // ignore the data and reload
            return false;
        }
        return true;
    }

    /**
     * It decrypts the data if needed, then decodes it from JSON
     * @param string table The name of the table you want to unpack.
     * @param string raw The raw string from the database
     * @return ?mixed[] The data is being returned as an array.
     */
    protected function tableUnpackString(string $table, string $raw): ?array
    {
        $data = $this->tableUnpackValidate($table, $raw);
        if ($data === null) {
            return null;
        }
        if ($this->tableUnpackChecks($data["table"], $table, $data["time"], $data["version"]) == false) {
            return null;
        }

        $data = $data["data"];
        if ($this->tableConfig[$table]["encrypt"] == true) {
            $data = $this->decrypt($data, $table . $this->encryptKeycode); // decode teh data
        }
        try {
            $dataarray = json_decode($data, true); // convert the json into an array
            return $dataarray;
        } catch (Exception $e) {
            return null;
        }
    }
}