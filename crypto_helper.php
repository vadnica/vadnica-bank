<?php
/**
 * AES-256 Šifriranje in dešifriranje občutljivih podatkov (Email, 2FA Secret)
 * Standard: AES-256-CBC prek OpenSSL
 */

if (!defined('AES_SECRET_KEY')) {
    // 32-bajtni (256-bitni) ključ, izpeljan iz varne skrivnosti (Zamenjajte s svojo skrivnostjo!)
    define('AES_SECRET_KEY', hash('sha256', 'Zamenjajte_s_svojo_skrivno_frazo_za_sifriranje_AES256', true));
}

/**
 * Deterministično AES-256 šifriranje za e-pošto (omogoča poizvedbe WHERE email = :email)
 */
if (!function_exists('encrypt_email')) {
    function encrypt_email($email) {
        if ($email === null || $email === '') {
            return null;
        }

        $emailStr = strtolower(trim((string)$email));

        // Če je že šifrirano z enc:, ne šifriraj ponovno
        if (str_starts_with($emailStr, 'enc:')) {
            return $emailStr;
        }

        // Deterministični 16-bajtni IV izpeljan iz HMAC(email, key)
        $iv = substr(hash_hmac('sha256', $emailStr, AES_SECRET_KEY, true), 0, 16);
        $encrypted = openssl_encrypt($emailStr, 'AES-256-CBC', AES_SECRET_KEY, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            return $emailStr;
        }

        return 'enc:' . base64_encode($iv . $encrypted);
    }
}

/**
 * Dešifriranje e-poštnega naslova
 */
if (!function_exists('decrypt_email')) {
    function decrypt_email($encryptedEmail) {
        if ($encryptedEmail === null || $encryptedEmail === '') {
            return '';
        }

        $str = (string)$encryptedEmail;

        // Če ni šifrirano s predpono enc:, vrnemo original (npr. nešifriran obstoječi zapis)
        if (!str_starts_with($str, 'enc:')) {
            return $str;
        }

        $raw = base64_decode(substr($str, 4));
        if ($raw === false || strlen($raw) < 17) {
            return $str;
        }

        $iv = substr($raw, 0, 16);
        $ciphertext = substr($raw, 16);
        $decrypted = openssl_decrypt($ciphertext, 'AES-256-CBC', AES_SECRET_KEY, OPENSSL_RAW_DATA, $iv);

        return ($decrypted !== false) ? $decrypted : $str;
    }
}

/**
 * Varno AES-256 šifriranje z naključnim IV za 2FA skrivne ključe
 */
if (!function_exists('encrypt_secret')) {
    function encrypt_secret($secret) {
        if ($secret === null || $secret === '') {
            return null;
        }

        $secretStr = trim((string)$secret);

        if (str_starts_with($secretStr, 'enc:')) {
            return $secretStr;
        }

        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($secretStr, 'AES-256-CBC', AES_SECRET_KEY, OPENSSL_RAW_DATA, $iv);

        if ($encrypted === false) {
            return $secretStr;
        }

        return 'enc:' . base64_encode($iv . $encrypted);
    }
}

/**
 * Dešifriranje 2FA skrivnega ključa
 */
if (!function_exists('decrypt_secret')) {
    function decrypt_secret($encryptedSecret) {
        if ($encryptedSecret === null || $encryptedSecret === '') {
            return null;
        }

        $str = (string)$encryptedSecret;

        if (!str_starts_with($str, 'enc:')) {
            return $str;
        }

        $raw = base64_decode(substr($str, 4));
        if ($raw === false || strlen($raw) < 17) {
            return $str;
        }

        $iv = substr($raw, 0, 16);
        $ciphertext = substr($raw, 16);
        $decrypted = openssl_decrypt($ciphertext, 'AES-256-CBC', AES_SECRET_KEY, OPENSSL_RAW_DATA, $iv);

        return ($decrypted !== false) ? $decrypted : null;
    }
}
