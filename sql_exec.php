<?php
/**
 * Remote SQL Executor — maxusvanparts.acstestweb.co.uk
 *
 * Accepts SQL queries via POST and executes them through $wpdb.
 * Returns results as JSON.
 *
 * SECURITY:
 *   - POST requests only
 *   - Token required (timing-safe comparison)
 *   - HTTPS enforced
 *   - Every query is logged to wp-content/sql_exec_log.txt with timestamp + IP
 *   - DELETE THIS FILE when not actively in use
 *
 * UPLOAD TO: WordPress root (same folder as wp-config.php)
 * DELETE AFTER USE.
 */

define('SQL_EXEC_TOKEN', 'maxus-sql-exec-a7f3k9z2-2026');

header('Content-Type: application/json');

// --- HTTPS only ---
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
         || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

if (!$is_https) {
    http_response_code(403);
    die(json_encode(['error' => 'HTTPS required']));
}

// --- POST only ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'POST required']));
}

// --- Token ---
$provided = $_POST['token'] ?? '';
if (!hash_equals(SQL_EXEC_TOKEN, $provided)) {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized']));
}

// --- SQL required ---
$sql = trim($_POST['sql'] ?? '');
if ($sql === '') {
    http_response_code(400);
    die(json_encode(['error' => 'No SQL provided']));
}

// --- Boot WordPress ---
require_once __DIR__ . '/wp-load.php';
global $wpdb;
$wpdb->show_errors();

// --- Log every query ---
$log_file = WP_CONTENT_DIR . '/sql_exec_log.txt';
$ip       = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown';
$log_line = '[' . date('Y-m-d H:i:s T') . '] IP=' . $ip . ' SQL=' . preg_replace('/\s+/', ' ', $sql) . PHP_EOL;
file_put_contents($log_file, $log_line, FILE_APPEND | LOCK_EX);

// --- Execute ---
$trimmed  = ltrim($sql);
$keyword  = strtoupper(substr($trimmed, 0, 6));
$is_read  = in_array($keyword, ['SELECT', 'SHOW  ', 'DESCRI', 'EXPLAI'], true)
         || stripos($trimmed, 'SELECT') === 0
         || stripos($trimmed, 'SHOW')   === 0
         || stripos($trimmed, 'DESC')   === 0
         || stripos($trimmed, 'EXPLAIN') === 0;

if ($is_read) {
    $results = $wpdb->get_results($sql, ARRAY_A);
    if ($wpdb->last_error) {
        http_response_code(500);
        echo json_encode(['error' => $wpdb->last_error, 'sql' => $sql]);
    } else {
        echo json_encode([
            'type'         => 'select',
            'row_count'    => count($results),
            'rows'         => $results,
            'last_query'   => $wpdb->last_query,
        ], JSON_PRETTY_PRINT);
    }
} else {
    $affected = $wpdb->query($sql);
    if ($wpdb->last_error) {
        http_response_code(500);
        echo json_encode(['error' => $wpdb->last_error, 'sql' => $sql]);
    } else {
        echo json_encode([
            'type'          => 'write',
            'affected_rows' => $affected,
            'insert_id'     => $wpdb->insert_id,
            'last_query'    => $wpdb->last_query,
        ], JSON_PRETTY_PRINT);
    }
}
