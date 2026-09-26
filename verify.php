<?php
require_once "db.example.php";

$type  = trim($_GET['type'] ?? 'register'); // Privzeto registracija
$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    die("<h3 style='color:red; font-family:sans-serif; text-align:center;'>Neveljaven ali manjkajoč žeton.</h3>");
}

try {
    $zdaj = date("Y-m-d H:i:s");

    // ==========================================
    // 1. POTRDITEV REGISTRACIJE RAČUNA
    // ==========================================
    if ($type === 'register') {
        $stmt = $pdo->prepare("SELECT id, token_expires_at, is_verified FROM users WHERE verification_token = :token");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            die("<div style='text-align:center; font-family:sans-serif; margin-top:50px;'>
                    <h3 style='color:#c62828;'>Žeton ne obstaja ali pa je račun že bil potrjen.</h3>
                    <p><a href='index.php'>Pojdi na domačo stran</a></p>
                 </div>");
        }

        if ($zdaj > $user['token_expires_at']) {
            $del = $pdo->prepare("DELETE FROM users WHERE id = :id AND is_verified = 0");
            $del->execute([':id' => $user['id']]);
            die("<div style='text-align:center; font-family:sans-serif; margin-top:50px;'>
                    <h3 style='color:#c62828;'>Povezava je potekla (veljavna je bila 5 minut).</h3>
                    <p>Prosimo, registrirajte se ponovno na <a href='index.php'>domači strani</a>.</p>
                 </div>");
        }

        $potrdi = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, token_expires_at = NULL WHERE id = :id");
        $potrdi->execute([':id' => $user['id']]);

        $naslov = "Račun uspešno potrjen!";
        $opis = "Vaš e-poštni naslov je potrjen. Sedaj se lahko prijavite v sistem.";
    }

    // ==========================================
    // 2. POTRDITEV SPREMEMBE E-POŠTNEGA NASLOVA
    // ==========================================
    elseif ($type === 'email') {
        $stmt = $pdo->prepare("SELECT id, pending_email, token_expires_at FROM users WHERE pending_token = :token");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();

        if (!$user || empty($user['pending_email'])) {
            die("<div style='text-align:center; font-family:sans-serif; margin-top:50px;'>
                    <h3 style='color:#c62828;'>Zahteva za spremembo e-pošte ne obstaja ali je že potrjena.</h3>
                    <p><a href='index.php'>Nazaj na vstopno stran</a></p>
                 </div>");
        }

        if ($zdaj > $user['token_expires_at']) {
            $del = $pdo->prepare("UPDATE users SET pending_email = NULL, pending_token = NULL, token_expires_at = NULL WHERE id = :id");
            $del->execute([':id' => $user['id']]);
            die("<div style='text-align:center; font-family:sans-serif; margin-top:50px;'>
                    <h3 style='color:#c62828;'>Povezava je potekla (veljavna je bila 5 minut).</h3>
                    <p>Prosimo, ponovite zahtevo v <a href='index.php'>svojem profilu</a>.</p>
                 </div>");
        }

        $upd = $pdo->prepare("UPDATE users SET email = pending_email, pending_email = NULL, pending_token = NULL, token_expires_at = NULL WHERE id = :id");
        $upd->execute([':id' => $user['id']]);

        $naslov = "E-poštni naslov uspešno posodobljen!";
        $opis = "Vaš novi e-poštni naslov je aktiviran. Prosimo, da se ponovno prijavite z novim naslovom.";
    }

    // ==========================================
    // 3. POTRDITEV SPREMEMBE GESLA
    // ==========================================
    elseif ($type === 'password') {
        $stmt = $pdo->prepare("SELECT id, verification_token, token_expires_at FROM users WHERE pending_token = :token");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();

        if (!$user || empty($user['verification_token'])) {
            die("<div style='text-align:center; font-family:sans-serif; margin-top:50px;'>
                    <h3 style='color:#c62828;'>Zahteva za spremembo gesla ne obstaja ali je že bila uporabljena.</h3>
                    <p><a href='index.php'>Nazaj na vstopno stran</a></p>
                 </div>");
        }

        if ($zdaj > $user['token_expires_at']) {
            $del = $pdo->prepare("UPDATE users SET verification_token = NULL, pending_token = NULL, token_expires_at = NULL WHERE id = :id");
            $del->execute([':id' => $user['id']]);
            die("<div style='text-align:center; font-family:sans-serif; margin-top:50px;'>
                    <h3 style='color:#c62828;'>Povezava je potekla (veljavna je bila 5 minut).</h3>
                    <p>Prosimo, ponovite zahtevo v <a href='index.php'>svojem profilu</a>.</p>
                 </div>");
        }

        $upd = $pdo->prepare("UPDATE users SET geslo_hash = verification_token, verification_token = NULL, pending_token = NULL, token_expires_at = NULL WHERE id = :id");
        $upd->execute([':id' => $user['id']]);

        $naslov = "Geslo uspešno spremenjeno!";
        $opis = "Vaše novo geslo je aktivirano. Sedaj se lahko prijavite.";
    }

    else {
        die("<h3 style='color:red; font-family:sans-serif; text-align:center;'>Neznana vrsta zahteve.</h3>");
    }

    // Izpis uspešnega stanja
    echo "<div style='text-align:center; margin-top:60px; font-family:sans-serif;'>
            <h2 style='color:#2e7d32;'>{$naslov}</h2>
            <p>{$opis}</p>
            <br>
            <a href='index.php' style='display:inline-block; padding:10px 22px; background-color:#0066cc; 
            color:#ffffff; text-decoration:none; border-radius:4px; font-weight:bold;'>Prijava v Moje finance</a>
          </div>";

} catch (PDOException $e) {
    die("<h3 style='color:red; font-family:sans-serif; text-align:center;'>Napaka baze: " . $e->getMessage() . "</h3>");
}
