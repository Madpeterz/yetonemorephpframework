<?php

namespace YAPF\Framework\Generator;

use YAPF\Framework\Core\SQLi\SqlConnectedClass;

abstract class FileWriter extends SqlConnectedClass
{
    protected string $writeLog = "";
    protected int $writeLineCount = 0;
    protected int $files = 0;
    protected bool $hadWriteError = false;
    /**
     * statsWrite
     * gets the writes stats and log file
     * @return mixed[]
     */
    public function statsWrite(): array
    {
        return ["error" => $this->hadWriteError, "files" => $this->files,
        "log" => $this->writeLog, "lines" => $this->writeLineCount];
    }

    protected array $lines = [];
    protected array $tabLookup = [
        0 => "",
        1 => "  ",
        2 => "      ",
        3 => "          ",
        4 => "              ",
        5 => "                ",
        6 => "                  ",
    ];
    protected function lines2text(): string
    {
        $file_content = "";
        $tabs = 0;
        foreach ($this->lines as $line_data) {
            if (is_array($line_data) == true) {
                $tabs = $line_data[0];
                continue;
            }
            if ($file_content != "") {
                $file_content .= "\n";
                $file_content .= $this->tabLookup[$tabs];
            }
            $file_content .= $line_data;
        }
        $file_content .= "\n";
        $this->writeLineCount += count($this->lines);
        return $file_content;
    }
    protected function writeFile(string $contents, string $name, string $folder): void
    {
        $create_file = $folder . $name;
        $this->writeModelFile($create_file, $contents);
    }
    protected function writeModelFile(string $create_file, string $file_content = ""): void
    {
        if (file_exists($create_file) == true) {
            unlink($create_file);
            usleep((30 * 0.001) * 10000); // wait for 300ms for the disk to finish
        }
        $status = file_put_contents($create_file, $file_content);
        $this->files++;
        usleep((10 * 0.001) * 10000);  // wait for 100ms for the disk to finish
        if ($status == false) {
            $this->hadWriteError = true;
            $this->writeLog .= "failed to write: " . $create_file . "\n";
        }
    }
}
