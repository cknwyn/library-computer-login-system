<?php
// ============================================================
// API — Classifications — /api/classifications.php
// ============================================================
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/helpers.php';

$type = $_GET['type'] ?? 'colleges';
$pdo  = db();

try {
    if ($type === 'colleges') {
        $stmt = $pdo->query("SELECT id, name, code FROM colleges ORDER BY name ASC");
        json_response(['success' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($type === 'departments') {
        $college_id = (int)($_GET['college_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT id, name FROM departments WHERE college_id = ? ORDER BY name ASC");
        $stmt->execute([$college_id]);
        json_response(['success' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($type === 'degrees') {
        $dept_id = (int)($_GET['department_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT id, name FROM degrees WHERE department_id = ? ORDER BY name ASC");
        $stmt->execute([$dept_id]);
        json_response(['success' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($type === 'campuses') {
        $stmt = $pdo->query("SELECT id, name FROM campuses ORDER BY name ASC");
        json_response(['success' => true, 'data' => $stmt->fetchAll()]);
    }

    if ($type === 'rooms') {
        $campus_id = (int)($_GET['campus_id'] ?? 0);
        $stmt = $pdo->prepare("SELECT id, name FROM rooms WHERE campus_id = ? ORDER BY name ASC");
        $stmt->execute([$campus_id]);
        json_response(['success' => true, 'data' => $stmt->fetchAll()]);
    }

    json_response(['success' => false, 'error' => 'Invalid type'], 400);

} catch (Exception $e) {
    json_response(['success' => false, 'error' => $e->getMessage()], 500);
}
