<?php
header('Content-Type: application/json; charset=utf-8');
require_once "db.example.php";
require_once "totp_helper.php";

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Podpora za neposreden POST (npr. upravitelji gesel / avtomatski vnosi)
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

// Honeypot preverjanje
if (!empty($data['website'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Zaznana avtomatizirana zahteva."]);
    exit;
}

$email = trim(filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL));
$geslo = $data['geslo'] ?? ($data['password'] ?? '');
$twoFaCode = trim($data['two_fa_code'] ?? ($data['twoFaCode'] ?? ''));

if (!$email || empty($geslo)) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Vnesite e-poštni naslov in geslo."]);
    exit;
}

try {
    // 1. Poišči uporabnika po e-pošti (s podporo za AES-256 šifriran zapis)
    $encEmail = encrypt_email($email);
    $stmt = $pdo->prepare("SELECT id, ime, email, geslo_hash, two_fa_secret, encrypted_profile, bank_iv, is_verified, is_admin, ustvarjen FROM users WHERE email = :email");
    $stmt->execute([':email' => $encEmail]);
    $user = $stmt->fetch();

    // Fallback za morebitne še neposodobljene nešifrirane zapise
    if (!$user) {
        $stmtLegacy = $pdo->prepare("SELECT id, ime, email, geslo_hash, two_fa_secret, encrypted_profile, bank_iv, is_verified, is_admin, ustvarjen FROM users WHERE email = :email");
        $stmtLegacy->execute([':email' => $email]);
        $user = $stmtLegacy->fetch();
        if ($user) {
            $updLegacy = $pdo->prepare("UPDATE users SET email = :enc_email WHERE id = :id");
            $updLegacy->execute([':enc_email' => $encEmail, ':id' => $user['id']]);
            $user['email'] = $encEmail;
        }
    }

    // Napačen e-poštni naslov (uporabnik ne obstaja)
    if (!$user) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Vpisali ste napačni e-naslov!"]);
        exit;
    }

    // Račun še ni potrjen prek e-pošte
    if ((int)$user['is_verified'] !== 1) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Vaš račun še ni bil potrjen. Preverite svoj e-poštni predal."]);
        exit;
    }

    // 2. Preveri geslo
    if (!password_verify($geslo, $user['geslo_hash'])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Vpisali ste napačno geslo!"]);
        exit;
    }

    // 3. Preveri 2FA, če ga ima uporabnik omogočenega
    $decryptedSecret = decrypt_secret($user['two_fa_secret']);
    if (!empty($decryptedSecret)) {
        if (empty($twoFaCode)) {
            echo json_encode([
                "status" => "2fa_required",
                "two_fa_required" => true,
                "message" => "Vnesite 6-mestno kodo iz aplikacije Google Authenticator.",
                "user_id" => $user['id']
            ]);
            exit;
        }

        if (!verifyTOTP($decryptedSecret, $twoFaCode)) {
            http_response_code(401);
            echo json_encode([
                "status" => "error",
                "message" => "Napačna 2FA koda. Preverite aplikacijo Google Authenticator."
            ]);
            exit;
        }
    }

    // Uspešna prijava
    echo json_encode([
        "status" => "success",
        "message" => "Prijava uspešna!",
        "user" => [
            "id" => $user['id'],
            "ime" => $user['ime'],
            "email" => decrypt_email($user['email']),
            "is_admin" => (int)($user['is_admin'] ?? 0),
            "ustvarjen" => $user['ustvarjen'] ?? null,
            "encrypted_profile" => $user['encrypted_profile'] ?? null,
            "bank_iv" => $user['bank_iv'] ?? null
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Sistemska napaka pri prijavi: " . $e->getMessage()]);
}
