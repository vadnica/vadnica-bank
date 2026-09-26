<?php
header('Content-Type: application/json; charset=utf-8');
require_once "db.example.php";
require_once "totp_helper.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$akcija = $_GET['action'] ?? ($data['action'] ?? '');
$userId = (int)($_GET['user_id'] ?? ($data['user_id'] ?? 0));

if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Neveljaven uporabnik."]);
    exit;
}

// Univerzalno dinamično določanje osnovnega URL naslova
$protokol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$gostitelj = $_SERVER['HTTP_HOST'];
$mapa = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
$osnovniUrl = $protokol . $gostitelj . $mapa;

try {
    // 1. PRIDOBITEV PODATKOV ZA PROFIL
    if ($_SERVER['REQUEST_METHOD'] === 'GET' || $akcija === 'get') {
        $stmt = $pdo->prepare("SELECT id, ime, email, two_fa_secret, encrypted_profile, bank_iv, is_admin, ustvarjen FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(404);
            echo json_encode(["status" => "error", "message" => "Uporabnik ne obstaja."]);
            exit;
        }

        $userPayload = [
            "id" => $user['id'],
            "ime" => $user['ime'],
            "email" => decrypt_email($user['email']),
            "is_admin" => (int)($user['is_admin'] ?? 0),
            "ustvarjen" => $user['ustvarjen'],
            "has_2fa" => !empty($user['two_fa_secret']),
            "encrypted_profile" => $user['encrypted_profile'] ?? null,
            "bank_iv" => $user['bank_iv'] ?? null
        ];

        echo json_encode(["status" => "success", "user" => $userPayload]);
        exit;
    }

    // 1.1 SHRANJEVANJE ŠIFRIRANEGA BANČNEGA PROFILA (Zero-Knowledge)
    if ($akcija === 'save_bank_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $encProfile = $data['encrypted_profile'] ?? '';
        $bankIv = $data['bank_iv'] ?? '';

        $stmtUpd = $pdo->prepare("UPDATE users SET encrypted_profile = :enc_profile, bank_iv = :bank_iv WHERE id = :id");
        $stmtUpd->execute([
            ':enc_profile' => $encProfile,
            ':bank_iv' => $bankIv,
            ':id' => $userId
        ]);

        echo json_encode([
            "status" => "success",
            "message" => "Bančni profil uspešno shranjen!"
        ]);
        exit;
    }

    // 2. SPREMEMBA IMENA IN PRIIMKA
    if ($akcija === 'update_name') {
        $novoIme = trim($data['ime'] ?? '');
        if (empty($novoIme)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Vnesite veljavno ime in priimek."]);
            exit;
        }

        $stmtUpd = $pdo->prepare("UPDATE users SET ime = :ime WHERE id = :id");
        $stmtUpd->execute([':ime' => $novoIme, ':id' => $userId]);

        echo json_encode([
            "status" => "success",
            "message" => "Ime in priimek sta bila uspešno posodobljena.",
            "ime" => $novoIme
        ]);
        exit;
    }

    // 3. ZAHTEVA ZA SPREMEMBO E-POŠTNEGA NASLOVA
    if ($akcija === 'request_email_change') {
        $noviEmail = trim(filter_var($data['novi_email'] ?? '', FILTER_VALIDATE_EMAIL));

        if (!$noviEmail) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Vnesite veljaven e-poštni naslov."]);
            exit;
        }

        // Preveri, ali novi e-mail že uporablja kdo drug (preverimo šifrirano in nešifrirano obliko)
        $encNoviEmail = encrypt_email($noviEmail);
        $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE (email = :enc_email OR email = :plain_email) AND id != :id");
        $stmtCheck->execute([':enc_email' => $encNoviEmail, ':plain_email' => $noviEmail, ':id' => $userId]);
        if ($stmtCheck->fetch()) {
            http_response_code(409);
            echo json_encode(["status" => "error", "message" => "Ta e-poštni naslov je že v uporabi."]);
            exit;
        }

        // Pridobi podatke uporabnika
        $stmtUser = $pdo->prepare("SELECT ime FROM users WHERE id = :id");
        $stmtUser->execute([':id' => $userId]);
        $uporabnik = $stmtUser->fetch();

        $token = bin2hex(random_bytes(32));
        $potek = date("Y-m-d H:i:s", strtotime("+5 minutes"));

        // Shranimo žeton in nov e-mail na čakanje (AES-256 šifriran)
        $stmtUpd = $pdo->prepare("UPDATE users SET pending_email = :novi_email, pending_token = :token, token_expires_at = :potek WHERE id = :id");
        $stmtUpd->execute([
            ':novi_email' => $encNoviEmail,
            ':token' => $token,
            ':potek' => $potek,
            ':id' => $userId
        ]);

        $povezava = $osnovniUrl . "/verify.php?type=email&token=" . $token;

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@example.com';
        $mail->Password = 'your-smtp-password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('your-email@example.com', 'Moje finance');
        $mail->addAddress($noviEmail, $uporabnik['ime']);

        $mail->isHTML(true);
        $mail->Subject = 'Potrditev spremembe e-poštnega naslova';
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; 
        padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
            <h3 style="color: #004d99; margin-top: 0;">Pozdravljeni, ' . htmlspecialchars($uporabnik['ime']) . '!</h3>
            <p>Prejeli smo zahtevo za spremembo vašega e-poštnega naslova.</p>
            <p style="margin: 25px 0;">
                <a href="' . htmlspecialchars($povezava) . '" target="_blank" rel="noopener" 
                style="background-color: #0066cc; color: #ffffff; padding: 12px 24px; text-decoration: none; 
                border-radius: 4px; font-weight: bold; display: inline-block;">Potrdi nov e-naslov</a>
            </p>
            <p style="font-size: 13px; color: #666;">Če gumb zgoraj ni odziven, odprite spodnjo povezavo v brskalniku:<br>
                <a href="' . htmlspecialchars($povezava) . '" target="_blank" rel="noopener" style="color: #0066cc; 
                word-break: break-all;">' . htmlspecialchars($povezava) . '</a>
            </p>
            <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
            <p style="font-size: 12px; color: #888; margin-bottom: 0;">Povezava velja 5 minut. Če spremembe niste 
            zahtevali vi, sporočilo prezrite.</p>
        </div>';
        $mail->AltBody = "Za potrditev odprite: {$povezava} (veljavno 5 minut).";

        $mail->send();

        echo json_encode(["status" => "success", "message" => "Potrditvena povezava je bila poslana na nov e-poštni naslov (veljavna 5 minut)."]);
        exit;
    }

    // 3. ZAHTEVA ZA SPREMEMBO GESLA
    if ($akcija === 'request_password_change') {
        $staroGeslo = $data['staro_geslo'] ?? '';
        $novoGeslo = $data['novo_geslo'] ?? '';
        $potrdiNovo = $data['potrdi_novo_geslo'] ?? '';

        if (empty($staroGeslo) || strlen($novoGeslo) < 8) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Novo geslo mora vsebovati vsaj 8 znakov."]);
            exit;
        }

        if ($novoGeslo !== $potrdiNovo) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Novi gesli se ne ujemata."]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT ime, email, geslo_hash FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($staroGeslo, $user['geslo_hash'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Trenutno geslo ni pravilno."]);
            exit;
        }

        $token = bin2hex(random_bytes(32));
        $potek = date("Y-m-d H:i:s", strtotime("+5 minutes"));
        $zacasniHash = password_hash($novoGeslo, PASSWORD_BCRYPT, ['cost' => 12]);

        // Novo geslo začasno shranimo v verification_token, potrditveni žeton pa v pending_token
        $stmtUpd = $pdo->prepare("UPDATE users SET pending_token = :token, token_expires_at = :potek, verification_token = :novi_hash WHERE id = :id");
        $stmtUpd->execute([
            ':token' => $token,
            ':potek' => $potek,
            ':novi_hash' => $zacasniHash,
            ':id' => $userId
        ]);

        $povezava = $osnovniUrl . "/verify.php?type=password&token=" . $token;

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.example.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@example.com';
        $mail->Password = 'your-smtp-password';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('your-email@example.com', 'Moje finance');
        $mail->addAddress(decrypt_email($user['email']), $user['ime']);

        $mail->isHTML(true);
        $mail->Subject = 'Potrditev spremembe gesla';
        $mail->Body = '
        <div style="font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; 
        padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;">
            <h3 style="color: #d32f2f; margin-top: 0;">Pozdravljeni, ' . htmlspecialchars($user['ime']) . '!</h3>
            <p>Prejeli smo zahtevo za spremembo vašega gesla.</p>
            <p style="margin: 25px 0;">
                <a href="' . htmlspecialchars($povezava) . '" target="_blank" rel="noopener" 
                style="background-color: #d32f2f; color: #ffffff; padding: 12px 24px; text-decoration: none; 
                border-radius: 4px; font-weight: bold; display: inline-block;">Potrdi spremembo gesla</a>
            </p>
            <p style="font-size: 13px; color: #666;">Če gumb zgoraj ni odziven, odprite spodnjo povezavo v brskalniku:<br>
                <a href="' . htmlspecialchars($povezava) . '" target="_blank" rel="noopener" style="color: #d32f2f; 
                word-break: break-all;">' . htmlspecialchars($povezava) . '</a>
            </p>
            <hr style="border: none; border-top: 1px solid #eee; margin: 20px 0;">
            <p style="font-size: 12px; color: #888; margin-bottom: 0;">Povezava velja 5 minut. Če tega niste zahtevali 
            vi, takoj zaščitite svoj račun.</p>
        </div>';
        $mail->AltBody = "Za potrditev spremembe gesla kliknite: {$povezava} (veljavno 5 minut).";

        $mail->send();

        echo json_encode(["status" => "success", "message" => "Potrditveno sporočilo je bilo poslano na vaš e-mail (veljavno 5 minut)."]);
        exit;
    }

    // 4. NASTAVITEV / POSODOBITEV 2FA
    if ($akcija === 'update_2fa') {
        $twoFaSecret = trim($data['two_fa_secret'] ?? '');
        $twoFaCode = trim($data['two_fa_code'] ?? '');
        $geslo = $data['geslo'] ?? '';

        if (empty($twoFaSecret) || empty($twoFaCode)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Vnesite 6-mestno potrditveno kodo."]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT geslo_hash FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($geslo, $user['geslo_hash'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Za spremembo 2FA morate vnesti pravilno geslo."]);
            exit;
        }

        if (!verifyTOTP($twoFaSecret, $twoFaCode)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Napačna 2FA koda! Preverite kodo v Google Authenticatorju."]);
            exit;
        }

        $encSecret = encrypt_secret($twoFaSecret);
        $stmtUpd = $pdo->prepare("UPDATE users SET two_fa_secret = :secret WHERE id = :id");
        $stmtUpd->execute([':secret' => $encSecret, ':id' => $userId]);

        echo json_encode(["status" => "success", "message" => "2FA dvostopenjska avtentikacija je bila uspešno omogočena!"]);
        exit;
    }

    // 5. IZKLOP 2FA
    if ($akcija === 'disable_2fa') {
        $geslo = $data['geslo'] ?? '';

        $stmt = $pdo->prepare("SELECT geslo_hash FROM users WHERE id = :id");
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($geslo, $user['geslo_hash'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Za izklop 2FA morate vnesti pravilno trenutno geslo."]);
            exit;
        }

        $stmtUpd = $pdo->prepare("UPDATE users SET two_fa_secret = NULL WHERE id = :id");
        $stmtUpd->execute([':id' => $userId]);

        echo json_encode(["status" => "success", "message" => "2FA avtentikacija je bila izklopljena."]);
        exit;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Napaka baze: " . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Napaka pri pošiljanju e-pošte: " . ($mail->ErrorInfo ?? $e->getMessage())]);
    exit;
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Sistemska napaka: " . $e->getMessage()]);
    exit;
}