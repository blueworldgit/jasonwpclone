<?php
/**
 * One-time live DB migration script — T60 VIN merge + orphan term cleanup
 *
 * INSTRUCTIONS:
 *   1. Upload this file to the WordPress ROOT of the live site via FTP/SFTP
 *      (same folder as wp-config.php)
 *   2. Run migrate_live_db.py which will POST to this script with the token
 *   3. DELETE this file from the server immediately after use
 *
 * SECURITY:
 *   - Only responds to POST requests
 *   - Requires the correct token in the POST body
 *   - Only runs the specific pre-defined SQL below — nothing else
 *   - Token is compared with hash_equals() (timing-safe)
 */

// --- Secret token — must match migrate_live_db.py ---
define('MIGRATION_TOKEN', 'maxus-t60-merge-2026-03-07');

header('Content-Type: application/json');

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die(json_encode(['error' => 'Method not allowed']));
}

// Verify token
$provided = $_POST['token'] ?? '';
if (!hash_equals(MIGRATION_TOKEN, $provided)) {
    http_response_code(403);
    die(json_encode(['error' => 'Unauthorized']));
}

// Boot WordPress (gives us $wpdb and correct table prefix)
require_once __DIR__ . '/wp-load.php';
global $wpdb;

$log    = [];
$errors = [];

// ============================================================
// PRE-FLIGHT CHECKS
// ============================================================

// Confirm wp_sku_vin_mapping exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}sku_vin_mapping'");
if (!$table_exists) {
    die(json_encode(['error' => "{$wpdb->prefix}sku_vin_mapping table not found — wrong DB?"]));
}

$vin_a = 'LSFAM11C6RA133899'; // canonical maxus-t60  — KEEP
$vin_b = 'LSFAM11C4RA133898'; // duplicate artefact   — REMOVE

$count_a_before = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sku_vin_mapping WHERE vin = %s", $vin_a
));
$count_b_before = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sku_vin_mapping WHERE vin = %s", $vin_b
));

$log[] = "BEFORE: VIN A ($vin_a) = $count_a_before rows";
$log[] = "BEFORE: VIN B ($vin_b) = $count_b_before rows";

if ($count_b_before === 0) {
    $log[] = "VIN B already has 0 rows — migration may have already been applied.";
    // Still run verification and return
}

// ============================================================
// STEP 1 — Insert unique-B SKUs under VIN A
// ============================================================

$inserted = $wpdb->query($wpdb->prepare(
    "INSERT INTO {$wpdb->prefix}sku_vin_mapping (sku, vin)
     SELECT sku, %s
     FROM {$wpdb->prefix}sku_vin_mapping
     WHERE vin = %s
       AND sku NOT IN (
           SELECT sku FROM {$wpdb->prefix}sku_vin_mapping WHERE vin = %s
       )",
    $vin_a, $vin_b, $vin_a
));

if ($inserted === false) {
    $errors[] = "Step 1 failed: " . $wpdb->last_error;
} else {
    $log[] = "Step 1: $inserted unique-B SKUs migrated to VIN A";
}

// ============================================================
// STEP 2 — Delete all VIN B rows
// ============================================================

$deleted = $wpdb->delete(
    "{$wpdb->prefix}sku_vin_mapping",
    ['vin' => $vin_b],
    ['%s']
);

if ($deleted === false) {
    $errors[] = "Step 2 failed: " . $wpdb->last_error;
} else {
    $log[] = "Step 2: $deleted VIN B rows deleted";
}

// ============================================================
// STEP 3 — Remove orphan vehicles taxonomy term for VIN B
// ============================================================

$orphan_slug = 'lsfam11c4ra133898';

$term_row = $wpdb->get_row($wpdb->prepare(
    "SELECT t.term_id, tt.term_taxonomy_id
     FROM {$wpdb->terms} t
     INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
     WHERE tt.taxonomy = 'vehicles' AND t.slug = %s",
    $orphan_slug
));

if (!$term_row) {
    $log[] = "Step 3: Orphan term '$orphan_slug' not found — already removed or never existed";
} else {
    $term_id    = (int) $term_row->term_id;
    $tt_id      = (int) $term_row->term_taxonomy_id;

    $r1 = $wpdb->delete("{$wpdb->termmeta}", ['term_id' => $term_id], ['%d']);
    $r2 = $wpdb->delete("{$wpdb->term_relationships}", ['term_taxonomy_id' => $tt_id], ['%d']);
    $r3 = $wpdb->delete("{$wpdb->term_taxonomy}", ['term_taxonomy_id' => $tt_id], ['%d']);
    $r4 = $wpdb->delete("{$wpdb->terms}", ['term_id' => $term_id], ['%d']);

    if ($r1 === false || $r3 === false || $r4 === false) {
        $errors[] = "Step 3 failed: " . $wpdb->last_error;
    } else {
        $log[] = "Step 3: Orphan term removed (term_id=$term_id, tt_id=$tt_id) — termmeta:$r1 rows, term_rels:$r2 rows, term_taxonomy:$r3 rows, terms:$r4 rows";
    }
}

// ============================================================
// VERIFICATION
// ============================================================

$count_a_after   = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sku_vin_mapping WHERE vin = %s", $vin_a
));
$count_b_after   = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->prefix}sku_vin_mapping WHERE vin = %s", $vin_b
));
$distinct_vins   = (int) $wpdb->get_var(
    "SELECT COUNT(DISTINCT vin) FROM {$wpdb->prefix}sku_vin_mapping"
);
$orphan_check    = (int) $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->terms} t
     INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
     WHERE tt.taxonomy = 'vehicles' AND t.slug = %s",
    $orphan_slug
));

$verification = [
    'vin_a_rows'      => $count_a_after,   // expected 1495
    'vin_b_rows'      => $count_b_after,   // expected 0
    'distinct_vins'   => $distinct_vins,   // expected 17
    'orphan_term_gone'=> $orphan_check === 0, // expected true
];

$passed = ($count_b_after === 0 && $distinct_vins === 17 && $orphan_check === 0);

echo json_encode([
    'success'      => empty($errors) && $passed,
    'log'          => $log,
    'errors'       => $errors,
    'verification' => $verification,
    'passed'       => $passed,
], JSON_PRETTY_PRINT);
