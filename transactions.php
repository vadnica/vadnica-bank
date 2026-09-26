<?php
header('Content-Type: application/json; charset=utf-8');
require_once "db.example.php";

$raw = file_get_contents("php://input");
$data = json_decode($raw, true);

$akcija = $_GET['action'] ?? ($data['action'] ?? '');
$userId = (int)($_GET['user_id'] ?? ($data['user_id'] ?? 0));

if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Neveljaven uporabnik."]);
    exit;
}

try {
    // 1. VPIS NOVE TRANSAKCIJE
    if ($akcija === 'add_transaction' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $encData = $data['encrypted_data'] ?? null;
        $iv = $data['iv'] ?? null;
        $datumVnosa = !empty($data['datum'])
            ? (strlen($data['datum']) === 10 ? $data['datum'] . ' ' . date('H:i:s') : $data['datum'])
            : date('Y-m-d H:i:s');

        // Zero-Knowledge šifriran vnos
        if (!empty($encData) && !empty($iv)) {
            $stmt = $pdo->prepare("INSERT INTO transactions (user_id, encrypted_data, iv, datum) VALUES (:user_id, :enc, :iv, :datum)");
            $stmt->execute([
                ':user_id' => $userId,
                ':enc' => $encData,
                ':iv' => $iv,
                ':datum' => $datumVnosa
            ]);

            echo json_encode([
                "status" => "success",
                "message" => "Transakcija uspešno shranjena!",
                "id" => $pdo->lastInsertId()
            ]);
            exit;
        }

        // Nešifriran fallback vnos
        $opis = trim($data['opis'] ?? '');
        $znesek = filter_var($data['znesek'] ?? 0, FILTER_VALIDATE_FLOAT);
        $vrsta = trim($data['vrsta'] ?? '');
        $kategorija = trim($data['kategorija'] ?? 'ostalo');

        if (empty($opis)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Vnesite opis transakcije."]);
            exit;
        }

        if ($znesek === false || $znesek <= 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Vnesite veljaven znesek večji od 0."]);
            exit;
        }

        if (!in_array($vrsta, ['priliv', 'odliv'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Neveljavna vrsta transakcije."]);
            exit;
        }

        if (empty($kategorija)) {
            $kategorija = 'ostalo';
        }

        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, opis, znesek, vrsta, kategorija, datum) VALUES (:user_id, :opis, :znesek, :vrsta, :kategorija, :datum)");
        $stmt->execute([
            ':user_id' => $userId,
            ':opis' => $opis,
            ':znesek' => $znesek,
            ':vrsta' => $vrsta,
            ':kategorija' => $kategorija,
            ':datum' => $datumVnosa
        ]);

        echo json_encode([
            "status" => "success",
            "message" => "Transakcija uspešno shranjena!",
            "id" => $pdo->lastInsertId()
        ]);
        exit;
    }

    // 1.1 MNOŽIČNI VPIS ŠIFRIRANIH TRANSAKCIJ (Kopiranje / Prenos)
    if (($akcija === 'batch_add' || $akcija === 'batch_insert') && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $items = $data['items'] ?? [];
        if (empty($items) || !is_array($items)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Ni podatkov za vnos."]);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO transactions (user_id, encrypted_data, iv, datum) VALUES (:user_id, :enc, :iv, :datum)");
        $count = 0;
        foreach ($items as $item) {
            $enc = $item['encrypted_data'] ?? null;
            $iv = $item['iv'] ?? null;
            $datum = $item['datum'] ?? date('Y-m-d H:i:s');
            if ($enc && $iv) {
                $stmt->execute([
                    ':user_id' => $userId,
                    ':enc' => $enc,
                    ':iv' => $iv,
                    ':datum' => $datum
                ]);
                $count++;
            }
        }

        echo json_encode([
            "status" => "success",
            "message" => "Uspešno prenesenih $count transakcij!",
            "copied_count" => $count
        ]);
        exit;
    }

    // 2. UREJANJE TRANSAKCIJE
    if (($akcija === 'edit_transaction' || $akcija === 'update_transaction') && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $transId = (int)($data['id'] ?? 0);
        if ($transId <= 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Neveljaven ID transakcije."]);
            exit;
        }

        $encData = $data['encrypted_data'] ?? null;
        $iv = $data['iv'] ?? null;
        $datumInput = trim($data['datum'] ?? '');
        $datumVnosa = !empty($datumInput)
            ? (strlen($datumInput) === 10 ? $datumInput . ' ' . date('H:i:s') : $datumInput)
            : date('Y-m-d H:i:s');

        // Zero-Knowledge šifrirana posodobitev
        if (!empty($encData) && !empty($iv)) {
            $stmt = $pdo->prepare("UPDATE transactions SET encrypted_data = :enc, iv = :iv, datum = :datum WHERE id = :id AND user_id = :user_id");
            $stmt->execute([
                ':enc' => $encData,
                ':iv' => $iv,
                ':datum' => $datumVnosa,
                ':id' => $transId,
                ':user_id' => $userId
            ]);

            echo json_encode([
                "status" => "success",
                "message" => "Transakcija uspešno posodobljena!"
            ]);
            exit;
        }

        // Nešifriran fallback
        $opis = trim($data['opis'] ?? '');
        $znesek = filter_var($data['znesek'] ?? 0, FILTER_VALIDATE_FLOAT);
        $vrsta = trim($data['vrsta'] ?? '');
        $kategorija = trim($data['kategorija'] ?? 'ostalo');

        if (empty($opis)) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Vnesite opis transakcije."]);
            exit;
        }

        if ($znesek === false || $znesek <= 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Vnesite veljaven znesek večji od 0."]);
            exit;
        }

        if (!in_array($vrsta, ['priliv', 'odliv'])) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Neveljavna vrsta transakcije."]);
            exit;
        }

        if (empty($kategorija)) {
            $kategorija = 'ostalo';
        }

        $stmt = $pdo->prepare("UPDATE transactions SET opis = :opis, znesek = :znesek, vrsta = :vrsta, kategorija = :kategorija, datum = :datum WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            ':opis' => $opis,
            ':znesek' => $znesek,
            ':vrsta' => $vrsta,
            ':kategorija' => $kategorija,
            ':datum' => $datumVnosa,
            ':id' => $transId,
            ':user_id' => $userId
        ]);

        echo json_encode([
            "status" => "success",
            "message" => "Transakcija uspešno posodobljena!"
        ]);
        exit;
    }

    // 3. BRISANJE TRANSAKCIJE
    if ($akcija === 'delete_transaction' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $transId = (int)($data['id'] ?? 0);

        if ($transId <= 0) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Neveljaven ID transakcije."]);
            exit;
        }

        $stmt = $pdo->prepare("DELETE FROM transactions WHERE id = :id AND user_id = :user_id");
        $stmt->execute([
            ':id' => $transId,
            ':user_id' => $userId
        ]);

        echo json_encode([
            "status" => "success",
            "message" => "Transakcija uspešno izbrisana!"
        ]);
        exit;
    }

    // 4. PRENOS / KOPIRANJE TRANSAKCIJ IZ PREJŠNJEGA MESECA (Položnice, Naročnine)
    if (($akcija === 'copy_prev_month' || $akcija === 'copy_recurring') && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $targetMonth = trim($data['target_month'] ?? '');
        $selectedIds = $data['selected_ids'] ?? [];
        $kategorija = trim($data['kategorija'] ?? 'vse');

        if (empty($targetMonth) || $targetMonth === 'vse') {
            $targetMonth = date('m');
        }
        $targetMonthInt = (int)$targetMonth;
        if ($targetMonthInt < 1 || $targetMonthInt > 12) {
            http_response_code(400);
            echo json_encode(["status" => "error", "message" => "Neveljaven ciljni mesec."]);
            exit;
        }

        $targetYear = (int)date('Y');
        $maxDays = (int)date('t', strtotime(sprintf('%04d-%02d-01', $targetYear, $targetMonthInt)));

        $toInsert = [];

        if (!empty($selectedIds) && is_array($selectedIds)) {
            $intIds = array_map('intval', $selectedIds);
            $intIds = array_filter($intIds, function($id) { return $id > 0; });

            if (empty($intIds)) {
                http_response_code(400);
                echo json_encode(["status" => "error", "message" => "Ni izbranih veljavnih transakcij za prenos."]);
                exit;
            }

            $inPlaceholders = implode(',', array_fill(0, count($intIds), '?'));
            $params = array_merge([$userId], $intIds);

            $stmt = $pdo->prepare("SELECT id, encrypted_data, iv, opis, znesek, vrsta, kategorija, datum FROM transactions WHERE user_id = ? AND id IN ($inPlaceholders)");
            $stmt->execute($params);
            $toInsert = $stmt->fetchAll();
        } else {
            // Avtomatski prenos glede na prejšnji mesec in kategorijo
            $sourceMonthInt = ($targetMonthInt === 1) ? 12 : ($targetMonthInt - 1);
            $sourceMonthStr = sprintf('%02d', $sourceMonthInt);

            $catSql = "";
            if ($kategorija === 'poloznice') {
                $catSql = " AND kategorija = 'poloznice'";
            } elseif ($kategorija === 'narocnine') {
                $catSql = " AND kategorija = 'narocnine'";
            } else {
                $catSql = " AND kategorija IN ('poloznice', 'narocnine')";
            }

            $stmt = $pdo->prepare("SELECT id, encrypted_data, iv, opis, znesek, vrsta, kategorija, datum FROM transactions WHERE user_id = :user_id AND DATE_FORMAT(datum, '%m') = :source_m $catSql ORDER BY datum ASC");
            $stmt->execute([':user_id' => $userId, ':source_m' => $sourceMonthStr]);
            $toInsert = $stmt->fetchAll();
        }

        if (empty($toInsert)) {
            echo json_encode([
                "status" => "empty",
                "message" => "V prejšnjem mesecu ni bilo najdenih transakcij za prenos.",
                "copied_count" => 0
            ]);
            exit;
        }

        $insertStmt = $pdo->prepare("INSERT INTO transactions (user_id, opis, znesek, vrsta, kategorija, datum) VALUES (:user_id, :opis, :znesek, :vrsta, :kategorija, :datum)");

        $copiedCount = 0;
        foreach ($toInsert as $item) {
            $origTime = strtotime($item['datum']);
            $origDay = ($origTime !== false) ? (int)date('d', $origTime) : 1;
            $timePart = ($origTime !== false) ? date('H:i:s', $origTime) : date('H:i:s');

            $day = min($origDay, $maxDays);
            $newDatum = sprintf('%04d-%02d-%02d %s', $targetYear, $targetMonthInt, $day, $timePart);

            $insertStmt->execute([
                ':user_id' => $userId,
                ':opis' => $item['opis'],
                ':znesek' => $item['znesek'],
                ':vrsta' => $item['vrsta'],
                ':kategorija' => $item['kategorija'],
                ':datum' => $newDatum
            ]);
            $copiedCount++;
        }

        echo json_encode([
            "status" => "success",
            "message" => "Uspešno prenesenih $copiedCount transakcij!",
            "copied_count" => $copiedCount
        ]);
        exit;
    }

    // 5. PRIDOBITEV VSEH TRANSAKCIJ UPORABNIKA
    if ($akcija === 'get_transactions' || ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($akcija))) {
        $stmt = $pdo->prepare("SELECT id, encrypted_data, iv, opis, znesek, vrsta, kategorija, datum FROM transactions WHERE user_id = :user_id ORDER BY datum DESC");
        $stmt->execute([':user_id' => $userId]);
        $transakcije = $stmt->fetchAll();

        echo json_encode([
            "status" => "success",
            "transactions" => $transakcije
        ]);
        exit;
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Napaka baze podatkov: " . $e->getMessage()]);
    exit;
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Sistemska napaka: " . $e->getMessage()]);
    exit;
}
