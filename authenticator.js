/* ============================================================
   BASE32 KODIRANJE / DEKODIRANJE (RFC 4648)
   Base32 uporablja znake A-Z in 2-7 (32 znakov skupaj)
   ============================================================ */

const BASE32_ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ234567";

// Pretvori Base32 niz v ArrayBuffer (surovi bajti)
function base32Decode(base32Str) {
    // Odstrani presledke in male črke pretvori v velike
    const cleaned = base32Str.replace(/\s/g, "").toUpperCase();

    let bits = 0;
    let value = 0;
    let output = [];

    for (let i = 0; i < cleaned.length; i++) {
        const charIndex = BASE32_ALPHABET.indexOf(cleaned[i]);
        if (charIndex === -1) continue; // Preskoči neveljavne znake (npr. '=')

        // Vsak Base32 znak nosi 5 bitov
        value = (value << 5) | charIndex;
        bits += 5;

        // Ko nabremo vsaj 8 bitov, izločimo en bajt
        if (bits >= 8) {
            bits -= 8;
            output.push((value >> bits) & 0xFF);
        }
    }

    // Vrne ArrayBuffer (primeren za Web Crypto API)
    return new Uint8Array(output).buffer;
}

// Pretvori ArrayBuffer v Base32 niz
function base32Encode(buffer) {
    const bytes = new Uint8Array(buffer);
    let bits = 0;
    let value = 0;
    let output = "";

    for (let i = 0; i < bytes.length; i++) {
        value = (value << 8) | bytes[i];
        bits += 8;

        while (bits >= 5) {
            bits -= 5;
            output += BASE32_ALPHABET[(value >> bits) & 0x1F];
        }
    }

    // Ostanek bitov (če so)
    if (bits > 0) {
        output += BASE32_ALPHABET[(value << (5 - bits)) & 0x1F];
    }

    return output;
}


/* ============================================================
   GENERIRANJE NAKLJUČNEGA SKRIVNEGA KLJUČA
   Standard zaupriporoča vsaj 160 bitov (20 bajtov) ključa
   ============================================================ */

function generateRandomSecret(byteLength = 20) {
    const randomBytes = new Uint8Array(byteLength);
    crypto.getRandomValues(randomBytes);
    return base32Encode(randomBytes.buffer);
}


/* ============================================================
   TOTP ALGORITEM (RFC 6238)

   1. Izračunaj časovni korak: floor(unix_time / 30)
   2. Pretvori korak v 8-bajtni big-endian buffer
   3. Izračunaj HMAC-SHA1(skritni_ključ, časovni_korak)
   4. Dinamično odrezanje (truncation) → 6-mestna koda
   ============================================================ */

async function generateTOTP(secretBase32, timestamp = Date.now()) {
    // Časovni korak (30-sekundna okna)
    const counter = Math.floor(timestamp / 1000 / 30);

    // Pretvori counter v 8-bajtni big-endian buffer
    const counterBuffer = new ArrayBuffer(8);
    const counterView = new DataView(counterBuffer);
    // Zapišemo zgornje 4 bajte (za 32-bitne vrednosti je zgornji del 0)
    // Spodnje 4 bajte vsebujejo naš counter
    counterView.setUint32(0, Math.floor(counter / 0x100000000));
    counterView.setUint32(4, counter & 0xFFFFFFFF);

    // Base32 dekodiraj skrivni ključ v surove bajte
    const keyBuffer = base32Decode(secretBase32);

    // Uvoz ključa za HMAC v Web Crypto API
    const cryptoKey = await crypto.subtle.importKey(
        "raw",
        keyBuffer,
        { name: "HMAC", hash: "SHA-1" },
        false,
        ["sign"]
    );

    // Izračunaj HMAC-SHA1
    const hmacBuffer = await crypto.subtle.sign("HMAC", cryptoKey, counterBuffer);
    const hmacBytes = new Uint8Array(hmacBuffer);

    /* --- DINAMIČNO ODREZANJE (Dynamic Truncation) ---
       1. Vzemi zadnji bajt HMAC-a in dobi zadnji nibble (spodnje 4 bite)
       2. Ta nibble je odmik (offset) v HMAC bajtih
       3. Vzemi 4 bajte od tega odmika
       4. Maskiraj najvišji bit (& 0x7FFFFFFF)
       5. Modulo 1.000.000 za 6-mestno kodo
    */
    const offset = hmacBytes[hmacBytes.length - 1] & 0x0F;

    // Sestavi 32-bitno število iz 4 bajtov pri danem odmiku
    const truncated =
        ((hmacBytes[offset]     & 0x7F) << 24) |
        ((hmacBytes[offset + 1] & 0xFF) << 16) |
        ((hmacBytes[offset + 2] & 0xFF) << 8)  |
        ((hmacBytes[offset + 3] & 0xFF));

    // Modulo 10^6 in dopolni z ničlami na 6 mest
    return (truncated % 1000000).toString().padStart(6, "0");
}

/* ============================================================
   PREVERJANJE KODE
   Preveri tudi ±1 časovno okno, da omogoči majhen časovni zamik
   ============================================================ */

async function verifyTOTP(secretBase32, userInputCode, timestamp = Date.now()) {
    // Preveri trenutno okno in ±1 (skupno 3 okna)
    for (let offset = -1; offset <= 1; offset++) {
        const adjustedTime = timestamp + (offset * 30000);
        const expectedCode = await generateTOTP(secretBase32, adjustedTime);

        if (expectedCode === userInputCode) {
            return true;
        }
    }
    return false;
}


/* ============================================================
   OTPAUTH URI (za prikaz in kasnejšo QR kodo)
   Format: otpauth://totp/OZNACBA?secret=SKRIVNOST&issuer=IZDAJATELJ
   Aplikacije na telefonu uporabljajo ta format
   ============================================================ */

function buildOtpauthUri(secret, label, issuer) {
    return `otpauth://totp/${encodeURIComponent(label)}?secret=${secret}&issuer=${encodeURIComponent(issuer)}`;
}


/* ============================================================
   UI LOGIKA (s preverjanjem obstoja elementov)
   ============================================================ */

let currentSecret = "";

function generateNewSecret() {
    const stored = localStorage.getItem("totp_secret");

    if (stored) {
        currentSecret = stored;
    } else {
        currentSecret = generateRandomSecret(20);
        localStorage.setItem("totp_secret", currentSecret);
    }

    const disp = document.getElementById("secretDisplay");
    if (disp) disp.textContent = currentSecret;
}

// Funkcija za ročno generiranje novega ključa (gumb)
function regenerateSecret() {
    currentSecret = generateRandomSecret(20);
    localStorage.setItem("totp_secret", currentSecret);
    const disp = document.getElementById("secretDisplay");
    if (disp) disp.textContent = currentSecret;
    const inp = document.getElementById("codeInput");
    if (inp) inp.value = "";
    const res = document.getElementById("verifyResult");
    if (res) {
        res.textContent = "";
        res.className = "result";
    }
}

async function verifyCode() {
    const inp = document.getElementById("codeInput");
    const resultEl = document.getElementById("verifyResult");
    if (!inp || !resultEl) return;

    const input = inp.value.trim();

    if (input.length !== 6) {
        resultEl.textContent = "";
        resultEl.className = "result";
        return;
    }

    if (!currentSecret) {
        resultEl.textContent = "Najprej generiraj ključ!";
        resultEl.className = "result error";
        return;
    }

    const isValid = await verifyTOTP(currentSecret, input);

    if (isValid) {
        resultEl.textContent = "✅ Koda je pravilna!";
        resultEl.className = "result success";
    } else {
        resultEl.textContent = "❌ Napačna koda!";
        resultEl.className = "result error";
    }
}

// Ob nalaganju strani generiraj prvi ključ, če obstaja prikaz
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById("secretDisplay")) {
        generateNewSecret();
    }
});

// za modal
let modalSecret = "";

// Ko se modal odpre, generiraj ključ
document.addEventListener('click', function(e) {
    if (e.target && (e.target.matches('.modal-button[data-target="modal2FA"]') || e.target.closest('.modal-button[data-target="modal2FA"]'))) {
        modalSecret = generateRandomSecret(20);
        const disp = document.getElementById("secretDisplay");
        if (disp) disp.textContent = modalSecret;
        const inp = document.getElementById("codeInput");
        if (inp) inp.value = "";
        const res = document.getElementById("verifyResult");
        if (res) res.textContent = "";
    }
});

// Preveri kodo v modalu
async function confirm2FA() {
    const inp = document.getElementById("codeInput");
    const resultEl = document.getElementById("verifyResult");
    if (!inp || !resultEl) return;

    const input = inp.value.trim();

    if (input.length !== 6) {
        resultEl.textContent = "Vnesi 6-mestno kodo.";
        resultEl.style.color = "red";
        return;
    }

    const isValid = await verifyTOTP(modalSecret, input);

    if (isValid) {
        const targetInput = document.getElementById("totp_secret");
        if (targetInput) targetInput.value = modalSecret;
        resultEl.textContent = "✅ Avtentikator omogočen!";
        resultEl.style.color = "green";
        setTimeout(function() {
            const modal = document.getElementById('modal2FA');
            if (modal) modal.style.display = 'none';
        }, 1500);
    } else {
        resultEl.textContent = "❌ Napačna koda!";
        resultEl.style.color = "red";
    }
}

// za kopiranje ključa
function copySecret() {
    const disp = document.getElementById("secretDisplay");
    if (!disp) return;
    const secret = disp.textContent;
    navigator.clipboard.writeText(secret).then(function() {
        const btn = document.getElementById("copyBtn");
        if (!btn) return;
        const originalText = btn.textContent;
        btn.textContent = "✅ Kopirano!";
        setTimeout(function() { btn.textContent = originalText; }, 1500);
    });
}