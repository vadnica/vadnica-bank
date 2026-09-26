<?php
require_once "db.example.php";
require_once "totp_helper.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Naložimo PHPMailer datoteke
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

// 1. Branje prejetih podatkov iz JavaScripta ali obrazca
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

// Podpora za neposreden POST
if (empty($data) && !empty($_POST)) {
    $data = $_POST;
}

// Honeypot zaščita pred boti
if (!empty($data['website'])) {
    echo json_encode(["status" => "error", "message" => "Neveljavna zahteva."]);
    exit;
}

$ime = trim($data['ime'] ?? ($data['name'] ?? ''));
$email = trim(filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL));
$geslo = $data['geslo'] ?? ($data['password'] ?? '');
$potrdiGeslo = $data['potrdiGeslo'] ?? ($data['confirm_password'] ?? ($data['confirmPassword'] ?? ''));
$twoFaSecret = trim($data['two_fa_secret'] ?? ($data['reg_two_fa_secret'] ?? ''));
$twoFaCode = trim($data['two_fa_code'] ?? ($data['twoFaCode'] ?? ''));

// 2. Preverjanje veljavnosti vnosa
if (empty($ime) || !$email || strlen($geslo) < 8) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Vsa polja so obvezna. Geslo mora imeti vsaj 8 znakov."]);
    exit;
}

if ($geslo !== $potrdiGeslo) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Gesli se ne ujemata!"]);
    exit;
}

// Če je bil poslan 2FA ključ, preverimo vneseno kodo
if (!empty($twoFaSecret) && !empty($twoFaCode)) {
    if (!verifyTOTP($twoFaSecret, $twoFaCode)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Napačna potrditvena 2FA koda! Preverite številko v aplikaciji Google Authenticator."]);
        exit;
    }
}

// 3. Logika baze in pošiljanje e-pošte
try {
    $encEmail = encrypt_email($email);
    $encTwoFaSecret = !empty($twoFaSecret) ? encrypt_secret($twoFaSecret) : null;

    // Če obstaja nepotrjen račun za TA specifičen e-mail in je žeton potekel (> 5 min), ga takoj pobrišemo
    $pocisti = $pdo->prepare("DELETE FROM users WHERE (email = :enc_email OR email = :plain_email) AND is_verified = 0 AND token_expires_at < NOW()");
    $pocisti->execute([':enc_email' => $encEmail, ':plain_email' => $email]);

    // Preverimo trenutno stanje tega e-maila v bazi
    $stmt = $pdo->prepare("SELECT id, is_verified FROM users WHERE email = :enc_email OR email = :plain_email");
    $stmt->execute([':enc_email' => $encEmail, ':plain_email' => $email]);
    $obstojeci = $stmt->fetch();

    // Če uporabnik že obstaja in je potrjen, zavrnemo
    if ($obstojeci && $obstojeci['is_verified'] == 1) {
        http_response_code(409);
        echo json_encode(["status" => "error", "message" => "Uporabnik s tem e-poštnim naslovom že obstaja."]);
        exit;
    }

    $token = bin2hex(random_bytes(32));
    $potek = date("Y-m-d H:i:s", strtotime("+5 minutes"));
    $gesloHash = password_hash($geslo, PASSWORD_BCRYPT, ['cost' => 12]);

    if ($obstojeci) {
        // Uporabnik je še znotraj 5 minut, posodobimo žeton, geslo, e-mail in 2FA ključ
        $update = $pdo->prepare("UPDATE users SET ime = :ime, email = :email, geslo_hash = :geslo_hash, two_fa_secret = :two_fa_secret, verification_token = :token, token_expires_at = :potek WHERE id = :id");
        $update->execute([
            ':ime' => $ime,
            ':email' => $encEmail,
            ':geslo_hash' => $gesloHash,
            ':two_fa_secret' => $encTwoFaSecret,
            ':token' => $token,
            ':potek' => $potek,
            ':id' => $obstojeci['id']
        ]);
    } else {
        // Nov vnos v bazo z AES-256 šifriranim e-mailom in 2FA ključem
        $insert = $pdo->prepare("INSERT INTO users (ime, email, geslo_hash, two_fa_secret, is_verified, verification_token, token_expires_at) VALUES (:ime, :email, :geslo_hash, :two_fa_secret, 0, :token, :potek)");
        $insert->execute([
            ':ime' => $ime,
            ':email' => $encEmail,
            ':geslo_hash' => $gesloHash,
            ':two_fa_secret' => $encTwoFaSecret,
            ':token' => $token,
            ':potek' => $potek
        ]);
    }

    // Povezava za potrditev
    $potrditvenaPovezava = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/verify.php?token=" . $token;

    // PHPMailer konfiguracija
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host = 'smtp.example.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your-email@example.com';
    $mail->Password = 'your-smtp-password';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port = 465;
    $mail->CharSet = 'UTF-8';

    // Pošiljatelj in prejemnik
    $mail->setFrom('your-email@example.com', 'Moje finance');
    $mail->addAddress($email, $ime);

    // Vsebina sporočila
    $mail->isHTML(true);
    $mail->Subject = 'Potrditev registracije - Moje finance';
    $mail->Body = "<h3>Pozdravljeni, " . htmlspecialchars($ime) . "!</h3>"
                   . "<p>Hvala za registracijo. Za potrditev računa kliknite na spodnji gumb:</p>"
    . "<p><a href='{$potrditvenaPovezava}' style='display:inline-block; padding:10px 18px; background-color:#0066cc; 
color:#ffffff; text-decoration:none; border-radius:4px; font-weight:bold;'>Potrdi račun</a></p>"
    . "<p><small style='color:#666;'>Povezava je veljavna 5 minut. Če registracije niste zahtevali vi, to sporočilo prezrite.</small></p>";
    $mail->AltBody = "Za potrditev računa odprite to povezavo: {$potrditvenaPovezava} (veljavna 5 minut).";

    $mail->send();

    echo json_encode([
        "status" => "success",
        "message" => "Registracija uspešna! Na vaš e-naslov je bilo poslano potrditveno sporočilo. Veljavno je 5 minut."
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Napaka baze: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Napaka pri pošiljanju pošte: " . $mail->ErrorInfo]);
}