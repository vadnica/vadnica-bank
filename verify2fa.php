<?php
ob_start();
session_start();
require_once "db.example.php";
require_once "lang_init.php";
require_once "totp_helper.php";

$error = '';

// 1. Podpora za AJAX / Fetch klice (JSON)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)) {
    header('Content-Type: application/json; charset=utf-8');
    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);
    
    $userId = (int)($data['user_id'] ?? 0);
    $code = trim($data['code'] ?? '');
    
    if (!$userId || empty($code)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => $txt['two_fa_invalid_code'] ?? "Vnesite 6-mestno kodo."]);
        exit;
    }
    
    $stmt = $pdo->prepare("SELECT id, ime, email, two_fa_secret, is_verified, is_admin, ustvarjen FROM users WHERE id = :id");
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch();
    
    if (!$user || empty($user['two_fa_secret'])) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Uporabnik nima omogočenega 2FA."]);
        exit;
    }
    
    $secret = decrypt_secret($user['two_fa_secret']);
    if (verifyTOTP($secret, $code)) {
        echo json_encode([
            "status" => "success",
            "message" => "2FA uspešno preverjen!",
            "user" => [
                "id" => $user['id'],
                "ime" => $user['ime'],
                "email" => decrypt_email($user['email']),
                "is_admin" => (int)($user['is_admin'] ?? 0),
                "ustvarjen" => $user['ustvarjen'] ?? null
            ]
        ]);
        exit;
    } else {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => $txt['two_fa_invalid_code'] ?? "Napačna 2FA koda. Preverite aplikacijo Google Authenticator."]);
        exit;
    }
}

// 2. Klasični obrazec preko POST / seje
$pendingUserId = $_SESSION['pending_2fa_user_id'] ?? null;
$pendingSecret = $_SESSION['pending_2fa_secret'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pendingUserId && $pendingSecret) {
    $code = trim($_POST['code'] ?? '');
    if (verifyTOTP($pendingSecret, $code)) {
        $_SESSION['user_id'] = $pendingUserId;
        $_SESSION['ime'] = $_SESSION['pending_2fa_ime'] ?? '';
        $_SESSION['email'] = $_SESSION['pending_2fa_email'] ?? '';
        
        unset($_SESSION['pending_2fa_user_id']);
        unset($_SESSION['pending_2fa_secret']);
        unset($_SESSION['pending_2fa_ime']);
        unset($_SESSION['pending_2fa_email']);
        
        header("Location: index.php");
        exit;
    } else {
        $error = $txt['two_fa_invalid_code'] ?? ($lang === 'en' ? "Invalid 2FA code. Please try again." : "Napačna 2FA koda. Poskusite znova.");
    }
}

require_once "header.php";
?>

<div class="auth-card" style="max-width: 460px; margin: 20px auto; background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 10px; padding: 24px 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.05); text-align: center;">
        <div style="font-size: 40px; margin-bottom: 15px;">🛡️</div>
        <h2 style="margin-top: 0;"><?php echo $txt['two_fa_title'] ?? 'Dvostopenjska avtentikacija'; ?></h2>
        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 25px;">
            <?php echo $txt['two_fa_login_prompt'] ?? 'Vnesite 6-mestno kodo iz aplikacije Google Authenticator:'; ?>
        </p>
        
        <?php if (!empty($error)): ?>
            <div class="message error" style="display: block; margin-bottom: 20px;"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" action="verify2fa.php">
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="code" style="display: block; margin-bottom: 8px; font-weight: 600;"><?php echo $txt['two_fa_enter_code'] ?? '6-mestna koda:'; ?></label>
                <input type="text" id="code" name="code" maxlength="6" inputmode="numeric" placeholder="000000" required autofocus 
                       style="text-align: center; letter-spacing: 6px; font-size: 24px; font-weight: bold; height: 55px; width: 100%; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-color); color: var(--text-main);">
            </div>
            <button type="submit" class="btn-primary btn-full" style="padding: 12px; font-size: 16px;"><?php echo $txt['two_fa_verify'] ?? 'Potrdi kodo'; ?></button>
        </form>
        
        <div style="margin-top: 20px;">
            <a href="index.php" style="color: var(--link-color); text-decoration: none; font-size: 14px;">&larr; <?php echo $txt['two_fa_cancel'] ?? 'Nazaj na prijavo'; ?></a>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const codeInput = document.getElementById('code');
    const form = document.querySelector('form');
    
    if (codeInput && form) {
        codeInput.addEventListener('input', function() {
            if (this.value.length === 6) {
                form.requestSubmit();
            }
        });
    }
});
</script>

<?php require_once "footer.php"; ?>
