<?php
/**
 * Vadnica Bančništvo - Avtomatska namestitev / Setup Script
 * Ta skripta samodejno ustvari ali posodobi potrebne tabele v bazi podatkov MySQL (users in transactions).
 */

$isCli = (php_sapi_name() === 'cli');

$dbConnected = false;
$dbError = null;
$messages = [];
$statusSuccess = false;

// 1. Poskus povezave z bazo
if (file_exists(__DIR__ . '/db.example.php')) {
    try {
        // Ker db.example.php ob napaki naredi exit z json_encode, začasno preverimo sami ali vključimo varno
        require_once __DIR__ . '/db.example.php';
        if (isset($pdo) && $pdo instanceof PDO) {
            $dbConnected = true;
            $messages[] = ["type" => "success", "title" => "Povezava z bazo podatkov", "text" => "Uspešno vzpostavljena povezava z MySQL strežnikom."];
        }
    } catch (Throwable $e) {
        $dbConnected = false;
        $dbError = $e->getMessage();
        $messages[] = ["type" => "error", "title" => "Napaka pri povezavi", "text" => "Povezava z bazo ni uspela: " . htmlspecialchars($dbError)];
    }
} else {
    $messages[] = ["type" => "error", "title" => "Manjka datoteka db.example.php", "text" => "Datoteka db.example.php ni bila najdena. Prosimo, vnesite svoje podatke za bazo v datoteko 'db.example.php'."];
}

// 2. Izvedba SQL ukazov za izdelavo / posodobitev tabel
if ($dbConnected && isset($pdo)) {
    try {
        // Tabela USERS
        $sqlUsers = "
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ime VARCHAR(100) NOT NULL,
            email VARCHAR(191) NOT NULL UNIQUE,
            pending_email VARCHAR(191) NULL,
            geslo_hash VARCHAR(255) NOT NULL,
            two_fa_secret VARCHAR(255) NULL,
            encrypted_profile LONGTEXT NULL,
            bank_iv VARCHAR(64) NULL,
            is_verified TINYINT(1) DEFAULT 0,
            is_admin TINYINT(1) DEFAULT 0,
            verification_token VARCHAR(64) NULL,
            pending_token VARCHAR(64) NULL,
            token_expires_at DATETIME NULL,
            ustvarjen TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sqlUsers);
        $messages[] = ["type" => "success", "title" => "Tabela `users`", "text" => "Tabela za uporabnike in šifrirane bančne profile je pripravljena."];

        // Preveri in posodobi manjkajoče stolpce v tabeli users, če tabela že obstaja iz starejših verzij
        $userCols = $pdo->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('encrypted_profile', $userCols)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN encrypted_profile LONGTEXT NULL AFTER two_fa_secret");
        }
        if (!in_array('bank_iv', $userCols)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN bank_iv VARCHAR(64) NULL AFTER encrypted_profile");
        }

        // Tabela TRANSACTIONS
        $sqlTransactions = "
        CREATE TABLE IF NOT EXISTS transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            encrypted_data LONGTEXT NULL,
            iv VARCHAR(64) NULL,
            opis VARCHAR(255) NULL,
            znesek DECIMAL(10, 2) NULL,
            vrsta ENUM('priliv', 'odliv') NULL,
            kategorija VARCHAR(100) NULL DEFAULT 'ostalo',
            datum TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_trans_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $pdo->exec($sqlTransactions);
        $messages[] = ["type" => "success", "title" => "Tabela `transactions`", "text" => "Tabela za transakcije z Zero-Knowledge šifriranjem je pripravljena."];

        // Preveri in posodobi manjkajoče stolpce v tabeli transactions
        $transCols = $pdo->query("SHOW COLUMNS FROM transactions")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('encrypted_data', $transCols)) {
            $pdo->exec("ALTER TABLE transactions ADD COLUMN encrypted_data LONGTEXT NULL AFTER user_id");
        }
        if (!in_array('iv', $transCols)) {
            $pdo->exec("ALTER TABLE transactions ADD COLUMN iv VARCHAR(64) NULL AFTER encrypted_data");
        }

        $statusSuccess = true;
    } catch (Throwable $e) {
        $statusSuccess = false;
        $messages[] = ["type" => "error", "title" => "Napaka pri izdelavi tabel", "text" => $e->getMessage()];
    }
}

// Če se skripta izvaja prek terminala (CLI)
if ($isCli) {
    echo "========================================\n";
    echo "  Vadnica Bančništvo - Namestitev tabel \n";
    echo "========================================\n\n";
    foreach ($messages as $msg) {
        $prefix = ($msg['type'] === 'success') ? '[OK] ' : '[NAPAKA] ';
        echo $prefix . $msg['title'] . ": " . $msg['text'] . "\n";
    }
    echo "\n" . ($statusSuccess ? "Namestitev je bila uspešno zaključena!\n" : "Namestitev ni uspela. Preverite zgornje napake.\n");
    exit($statusSuccess ? 0 : 1);
}
?>
<!DOCTYPE html>
<html lang="sl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Namestitev baze podatkov | Vadnica Bančništvo</title>
    <link rel="icon" type="image/png" sizes="16x16" href="icons/favicon-16x16.png">
    <style>
        :root {
            --bg-color: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
            --primary-color: #0284c7;
            --primary-hover: #0369a1;
            --success-bg: #ecfdf5;
            --success-border: #a7f3d0;
            --success-text: #065f46;
            --error-bg: #fef2f2;
            --error-border: #fecaca;
            --error-text: #991b1b;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg-color: #0f172a;
                --card-bg: #1e293b;
                --text-main: #f1f5f9;
                --text-muted: #94a3b8;
                --border-color: #334155;
                --primary-color: #38bdf8;
                --primary-hover: #0284c7;
                --success-bg: #064e3b;
                --success-border: #047857;
                --success-text: #a7f3d0;
                --error-bg: #7f1d1d;
                --error-border: #b91c1c;
                --error-text: #fecaca;
            }
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .setup-card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 580px;
            padding: 32px;
        }
        .header {
            text-align: center;
            margin-bottom: 24px;
        }
        .header img {
            height: 48px;
            margin-bottom: 16px;
        }
        .header h1 {
            font-size: 24px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 6px;
        }
        .header p {
            color: var(--text-muted);
            font-size: 14px;
        }
        .step-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin: 24px 0;
        }
        .step-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 8px;
            font-size: 14px;
            border: 1px solid transparent;
        }
        .step-item.success {
            background-color: var(--success-bg);
            border-color: var(--success-border);
            color: var(--success-text);
        }
        .step-item.error {
            background-color: var(--error-bg);
            border-color: var(--error-border);
            color: var(--error-text);
        }
        .step-icon {
            font-size: 18px;
            line-height: 1;
        }
        .step-content strong {
            display: block;
            margin-bottom: 2px;
        }
        .actions {
            margin-top: 28px;
            display: flex;
            gap: 12px;
            flex-direction: column;
        }
        .btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            padding: 12px 20px;
            font-size: 15px;
            font-weight: 600;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.2s ease;
            cursor: pointer;
            border: none;
            text-align: center;
        }
        .btn-primary {
            background-color: var(--primary-color);
            color: #ffffff;
        }
        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background-color: transparent;
            color: var(--text-muted);
            border: 1px solid var(--border-color);
        }
        .btn-secondary:hover {
            background-color: rgba(0, 0, 0, 0.05);
            color: var(--text-main);
        }
        .info-box {
            margin-top: 20px;
            padding: 12px 14px;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.03);
            font-size: 12px;
            color: var(--text-muted);
            text-align: center;
            line-height: 1.5;
        }
    </style>
</head>
<body>

<div class="setup-card">
    <div class="header">
        <a href="https://vadnica.org" target="_blank" rel="noopener">
            <picture>
                <source srcset="img/tutorial-from-html-to-arduino-dark.svg" media="(prefers-color-scheme: dark)">
                <img src="img/tutorial-from-html-to-arduino.svg" alt="Vadnica Logo">
            </picture>
        </a>
        <h1>Namestitev podatkovne baze</h1>
        <p>Vadnica Bančništvo &bull; Samodejna konfiguracija tabel</p>
    </div>

    <div class="step-list">
        <?php foreach ($messages as $msg): ?>
            <div class="step-item <?php echo $msg['type']; ?>">
                <span class="step-icon"><?php echo ($msg['type'] === 'success') ? '✅' : '❌'; ?></span>
                <div class="step-content">
                    <strong><?php echo htmlspecialchars($msg['title']); ?></strong>
                    <span><?php echo htmlspecialchars($msg['text']); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="actions">
        <?php if ($statusSuccess): ?>
            <a href="index.php" class="btn btn-primary">🚀 Pojdi na prijavo v spletno banko</a>
            <a href="setup.php" class="btn btn-secondary">🔄 Ponovno preveri tabele</a>
        <?php else: ?>
            <a href="setup.php" class="btn btn-primary">🔄 Poskusi znova</a>
            <div class="info-box">
                Prosimo, preverite povezovalne podatke za MySQL (gostitelj, uporabnik, geslo, ime baze) v datoteki <code>db.example.php</code>.
            </div>
        <?php endif; ?>
    </div>

    <div class="info-box">
        🔐 Vse tabele podpirajo popolno <strong>Zero-Knowledge</strong> AES-256 šifriranje transakcij in bančnega profila.
    </div>
</div>

</body>
</html>
