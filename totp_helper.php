<?php
/**
 * TOTP / 2FA Helper za Google Authenticator (RFC 6238 & RFC 4648)
 */

if (!function_exists('base32_decode_totp')) {
    function base32_decode_totp($secret) {
        $secret = strtoupper(preg_replace('/[^A-Z2-7]/', '', (string)$secret));
        if (empty($secret)) {
            return '';
        }

        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $binary = '';
        $len = strlen($secret);
        for ($i = 0; $i < $len; $i++) {
            $char = $secret[$i];
            $pos = strpos($alphabet, $char);
            if ($pos === false) continue;
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        $binLen = strlen($binary);
        for ($i = 0; $i + 8 <= $binLen; $i += 8) {
            $bytes .= chr(bindec(substr($binary, $i, 8)));
        }

        return $bytes;
    }
}

if (!function_exists('generate_totp')) {
    function generate_totp($secret, $timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        $timeSlice = floor($timestamp / 30);
        $key = base32_decode_totp($secret);
        if (empty($key)) {
            return null;
        }

        // 8-bajtni big-endian counter
        $packedTime = pack('N*', 0) . pack('N*', $timeSlice);
        $hash = hash_hmac('sha1', $packedTime, $key, true);

        // Dinamično odrezanje (Dynamic Truncation)
        $offset = ord($hash[19]) & 0x0f;
        $truncatedHash = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;

        return str_pad((string)$truncatedHash, 6, '0', STR_PAD_LEFT);
    }
}

if (!function_exists('verifyTOTP')) {
    function verifyTOTP($secret, $code, $discrepancy = 1) {
        $code = trim((string)$code);
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            return false;
        }

        $currentTime = time();
        for ($offset = -$discrepancy; $offset <= $discrepancy; $offset++) {
            $testTime = $currentTime + ($offset * 30);
            $calculated = generate_totp($secret, $testTime);
            if ($calculated !== null && hash_equals($calculated, $code)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('generate_totp_secret')) {
    function generate_totp_secret($length = 20) {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bytes = random_bytes($length);
        $secret = '';
        $bits = 0;
        $val = 0;
        for ($i = 0; $i < strlen($bytes); $i++) {
            $val = ($val << 8) | ord($bytes[$i]);
            $bits += 8;
            while ($bits >= 5) {
                $bits -= 5;
                $secret .= $alphabet[($val >> $bits) & 0x1f];
            }
        }
        if ($bits > 0) {
            $secret .= $alphabet[($val << (5 - $bits)) & 0x1f];
        }
        return $secret;
    }
}
