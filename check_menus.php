<?php
require __DIR__ . '/wp-load.php';

// List all menus
$menus = wp_get_nav_menus();
foreach ($menus as $m) {
    echo "MENU: {$m->term_id} - {$m->name}\n";
    $items = wp_get_nav_menu_items($m->term_id);
    if ($items) {
        foreach ($items as $i) {
            $indent = str_repeat('  ', intval($i->menu_item_parent > 0));
            echo "  {$i->ID}: {$i->title} => {$i->url} (parent:{$i->menu_item_parent})\n";
        }
    }
    echo "\n";
}
