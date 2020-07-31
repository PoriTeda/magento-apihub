<?php

/**
 * Product:       Xtento_TwoFactorAuth (2.1.5)
 * ID:            %!uniqueid!%
 * Packaged:      %!packaged!%
 * Last Modified: 2016-02-25T14:30:56+00:00
 * File:          Model/Authenticator/Totp.php
 * Copyright:     Copyright (c) 2017 XTENTO GmbH & Co. KG <info@xtento.com> / All rights reserved.
 */

namespace Xtento\TwoFactorAuth\Model\Authenticator;

class Totp
{
    /*
     * Most of this source code has been adapted from the ga4php library which can be found at http://code.google.com/p/ga4php/
     *
     * Thanks to Takigama for his great work!
     *
     * Removed from the original ga4php library: some internal data saving mechanisms
     */

    public function authenticateUser($enteredCode, $tokenKey, $tokenTimer = 30, $totpSkew = 2)
    {
        // the totpSkew value is how many tokens either side of the current token we should check, based on a time skew.
        if (preg_match("/[0-9][0-9][0-9][0-9][0-9][0-9]/", $enteredCode) < 1) {
            return false;
        }

        $tokenKey = $this->helperb322hex($tokenKey);
        if ($tokenKey == "") {
            return false;
        }

        $t_now = time();
        $t_ear = $t_now - ($totpSkew * $tokenTimer);
        $t_lat = $t_now + ($totpSkew * $tokenTimer);
        $t_st = ((int)($t_ear / $tokenTimer));
        $t_en = ((int)($t_lat / $tokenTimer));
        for ($i = $t_st; $i <= $t_en; $i++) {
            $expectedToken = $this->oathHotp($tokenKey, $i);
            if ($enteredCode == $expectedToken) {
                return true;
            }
        }

        return false;
    }

    protected function oathHotp($key, $counter)
    {
        $key = pack("H*", $key);
        $cur_counter = [0, 0, 0, 0, 0, 0, 0, 0];
        for ($i = 7; $i >= 0; $i--) {
            $cur_counter[$i] = pack('C*', $counter);
            $counter = $counter >> 8;
        }
        $bin_counter = implode($cur_counter);
        // Pad to 8 chars
        if (strlen($bin_counter) < 8) {
            $bin_counter = str_repeat(chr(0), 8 - strlen($bin_counter)) . $bin_counter;
        }

        // HMAC
        $hash = hash_hmac('sha1', $bin_counter, $key);
        return str_pad($this->oathTruncate($hash), 6, "0", STR_PAD_LEFT);
    }

    protected function oathTruncate($hash, $length = 6)
    {
        // Convert to dec
        foreach (str_split($hash, 2) as $hex) {
            $hmac_result[] = hexdec($hex);
        }

        // Find offset
        $offset = $hmac_result[19] & 0xf;

        // Algorithm from RFC
        return
            (
                (($hmac_result[$offset + 0] & 0x7f) << 24) |
                (($hmac_result[$offset + 1] & 0xff) << 16) |
                (($hmac_result[$offset + 2] & 0xff) << 8) |
                ($hmac_result[$offset + 3] & 0xff)
            ) % pow(10, $length);
    }

    // creates a base 32 key (random)
    public function createBase32Key()
    {
        $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";
        $key = "";
        for ($i = 0; $i < 16; $i++) {
            $offset = rand(0, strlen($alphabet) - 1);
            $key .= $alphabet[$offset];
        }

        return $key;
    }

    public function helperb322hex($b32)
    {
        $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";

        $out = "";
        $dous = "";

        for ($i = 0; $i < strlen($b32); $i++) {
            $in = strrpos($alphabet, $b32[$i]);
            $b = str_pad(base_convert($in, 10, 2), 5, "0", STR_PAD_LEFT);
            $out .= $b;
            $dous .= $b . ".";
        }

        $ar = str_split($out, 20);
        $out2 = "";
        foreach ($ar as $val) {
            $rv = str_pad(base_convert($val, 2, 16), 5, "0", STR_PAD_LEFT);
            $out2 .= $rv;
        }

        return $out2;
    }
}
