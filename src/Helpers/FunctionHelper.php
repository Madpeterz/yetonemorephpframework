<?php

namespace YAPF\Framework\Helpers;

use YAPF\Core\ErrorControl\ErrorLogging;

/*
    YAPF/Helpers/FunctionHelper.php

    functions used all over the place nice to have
    quick access to
*/

class FunctionHelper extends ErrorLogging
{
    protected function sha256(string $input): string
    {
        return hash("sha256", $input, false);
    }

    /**
     * convertIfBool
     * takes a input and if its a bool converts it to a int
     * otherwise returns input
     */
    public static function convertIfBool($input): mixed
    {
        if ($input === false) {
            return 0;
        } elseif ($input === true) {
            return 1;
        }
        return $input;
    }

    protected function userAgentIdToName(int $agentId): string
    {
        $agents = [
            1 => 'Unknown',
            2 => 'Internet Explorer',
            3 => 'Mozilla Firefox',
            4 => 'Google Chrome',
            5 => 'Apple Safari',
            6 => 'Opera',
            7 => 'Netscape',
        ];
        if (array_key_exists($agentId, $agents) == false) {
            return '?';
        }
        return $agents[$agentId];
    }

    protected function timeDisplay(int $secs): string
    {
        $mins = floor($secs / 60);
        $secs -= $mins * 60;
        $hours = floor($mins / 60);
        $mins -= $hours * 60;
        $days = floor($hours / 24);
        $hours -= $days * 24;
        $output = "";
        $addon = "";
        if ($days > 0) {
            $output .= $addon . $days . " days";
            $addon = ", ";
        }
        if ($hours > 0) {
            $output .= $addon . $hours . " hours";
            $addon = ", ";
        }
        if ($mins > 0) {
            $output .= $addon . $mins . " mins";
            $addon = ", ";
        }
        if ($secs > 0) {
            $output .= $addon . $secs . " secs";
            $addon = ", ";
        }
        if ($output == "") {
            $output = "-";
        }
        return $output;
    }

    protected function expiredAgo(
        $unixtime = 0,
        bool $withSeconds = false,
        string $expiredWord = "Expired",
        string $activeWord = "Active"
    ): string {
        $dif = time() - $unixtime;
        if ($dif < 0) {
            return $activeWord;
        }
        return $this->timeRemainingHumanReadable(time() + $dif, $withSeconds, $expiredWord);
    }
    /**
     * get_opts
     * @return mixed[]
     */
    protected function getOpts(): array
    {
        $opts = [];
        foreach ($_SERVER["argv"] as $argKey => $argValue) {
            $value = $argValue;
            $key = $argKey;
            if (preg_match('@\-\-(.+)=(.+)@', $argValue, $matches)) {
                $key = $matches[1];
                $value = $matches[2];
            } elseif (preg_match('@\-\-(.+)@', $argValue, $matches)) {
                $key = $matches[1];
                $value = true;
            } elseif (preg_match('@\-(.+)=(.+)@', $argValue, $matches)) {
                $key = $matches[1];
                $value = $matches[2];
            } elseif (preg_match('@\-(.+)@', $argValue, $matches)) {
                $key = $matches[1];
                $value = true;
            }
            $opts[$key] = $value;
        }
        return $opts;
    }
    protected function timeRemainingHumanReadable(
        $unixtime = 0,
        bool $withSeconds = false,
        string $expiredWord = "Expired"
    ): string {
        $dif = $unixtime - time();
        if ($dif <= 0) {
            return $expiredWord;
        }
        $mins = floor(($dif / 60));
        $hours = floor(($mins / 60));
        $days = floor($hours / 24);
        if ($days > 0) {
            $hours -= $days * 24;
            return $days . " days, " . $hours . " hours";
        }
        if (($withSeconds == false) && ($hours > 0)) {
            $mins -= $hours * 60;
            return $hours . " hours, " . $mins . " mins";
        }
        if ($withSeconds == false) {
            return $mins . " mins";
        }
        $dif -= $mins * 60;
        if ($mins > 0) {
            return $mins . " mins, " . $dif . " secs";
        }
        return $dif . " secs";
    }

    protected function isChecked(bool $input_value): string
    {
        if ($input_value == true) {
            return " checked ";
        }
        return "";
    }
}
