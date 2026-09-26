/**
 * Zero-Knowledge Cryptography Module for Vadnica Bančništvo
 * Implements PBKDF2 key derivation (SHA-256, 100k iterations) and AES-256-GCM client-side encryption/decryption.
 * All sensitive financial data (amounts, descriptions, categories, bank profile) are encrypted in the browser
 * before transmission to the server.
 */

const ZKCrypto = (() => {
    let masterKey = null;

    // Helper: Convert ArrayBuffer to Base64 string
    function arrayBufferToBase64(buffer) {
        const bytes = new Uint8Array(buffer);
        let binary = '';
        const len = bytes.byteLength;
        for (let i = 0; i < len; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }

    // Helper: Convert Base64 string to ArrayBuffer
    function base64ToArrayBuffer(base64) {
        const binary = window.atob(base64);
        const len = binary.length;
        const bytes = new Uint8Array(len);
        for (let i = 0; i < len; i++) {
            bytes[i] = binary.charCodeAt(i);
        }
        return bytes.buffer;
    }

    // Derive 256-bit AES-GCM key from password and email salt
    async function deriveKey(password, email) {
        if (!password) throw new Error("Password is required for key derivation.");
        const normalizedEmail = (email || '').trim().toLowerCase();
        const enc = new TextEncoder();
        const salt = enc.encode("vadnica_zk_salt_" + normalizedEmail);

        const passwordBuffer = enc.encode(password);
        const baseKey = await crypto.subtle.importKey(
            "raw",
            passwordBuffer,
            { name: "PBKDF2" },
            false,
            ["deriveKey", "deriveBits"]
        );

        const key = await crypto.subtle.deriveKey(
            {
                name: "PBKDF2",
                salt: salt,
                iterations: 100000,
                hash: "SHA-256"
            },
            baseKey,
            { name: "AES-GCM", length: 256 },
            true, // extractable for session caching
            ["encrypt", "decrypt"]
        );

        masterKey = key;
        await persistSessionKey(key);
        return key;
    }

    // Persist key in sessionStorage so page reload (F5) maintains decryption capability during tab session
    async function persistSessionKey(key) {
        try {
            const raw = await crypto.subtle.exportKey("raw", key);
            sessionStorage.setItem("banka_zk_key", arrayBufferToBase64(raw));
        } catch (e) {
            console.error("Failed to persist session key:", e);
        }
    }

    // Restore key from sessionStorage if available
    async function restoreSessionKey() {
        if (masterKey) return masterKey;
        try {
            const rawB64 = sessionStorage.getItem("banka_zk_key");
            if (!rawB64) return null;
            const raw = base64ToArrayBuffer(rawB64);
            masterKey = await crypto.subtle.importKey(
                "raw",
                raw,
                { name: "AES-GCM", length: 256 },
                true,
                ["encrypt", "decrypt"]
            );
            return masterKey;
        } catch (e) {
            console.error("Failed to restore session key:", e);
            return null;
        }
    }

    // Encrypt data (object or string) with AES-256-GCM
    async function encrypt(data, keyToUse = null) {
        const key = keyToUse || masterKey || await restoreSessionKey();
        if (!key) throw new Error("No cryptographic key available for encryption.");

        const text = typeof data === 'string' ? data : JSON.stringify(data);
        const enc = new TextEncoder();
        const encodedData = enc.encode(text);

        // 12-byte IV standard for AES-GCM
        const iv = new Uint8Array(12);
        crypto.getRandomValues(iv);

        const ciphertextBuffer = await crypto.subtle.encrypt(
            { name: "AES-GCM", iv: iv },
            key,
            encodedData
        );

        return {
            encrypted_data: arrayBufferToBase64(ciphertextBuffer),
            iv: arrayBufferToBase64(iv.buffer)
        };
    }

    // Decrypt data with AES-256-GCM
    async function decrypt(encryptedDataBase64, ivBase64, keyToUse = null) {
        if (!encryptedDataBase64 || !ivBase64) return null;
        const key = keyToUse || masterKey || await restoreSessionKey();
        if (!key) throw new Error("No cryptographic key available for decryption.");

        const ciphertext = base64ToArrayBuffer(encryptedDataBase64);
        const iv = base64ToArrayBuffer(ivBase64);

        const decryptedBuffer = await crypto.subtle.decrypt(
            { name: "AES-GCM", iv: new Uint8Array(iv) },
            key,
            ciphertext
        );

        const dec = new TextDecoder();
        const decryptedStr = dec.decode(decryptedBuffer);

        try {
            return JSON.parse(decryptedStr);
        } catch (e) {
            return decryptedStr;
        }
    }

    // Clear key from memory and session
    function clearKey() {
        masterKey = null;
        sessionStorage.removeItem("banka_zk_key");
    }

    function getKey() {
        return masterKey;
    }

    function setKey(k) {
        masterKey = k;
    }

    return {
        deriveKey,
        persistSessionKey,
        restoreSessionKey,
        encrypt,
        decrypt,
        clearKey,
        getKey,
        setKey
    };
})();
