<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class B2BKing_Group_Rules_Log {
    
    private static $instance = null;
    private $table_name;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'b2bking_group_rules_log';
        $this->maybe_create_table();
    }
    
    /**
     * Create table if it doesn't exist
     */
    private function maybe_create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            rule_id bigint(20) NOT NULL,
            old_group_id bigint(20) DEFAULT NULL,
            new_group_id bigint(20) NOT NULL,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY rule_id (rule_id),
            KEY old_group_id (old_group_id),
            KEY new_group_id (new_group_id),
            KEY date_created (date_created)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Check if table exists
     */
    public function table_exists() {
        global $wpdb;
        return $wpdb->get_var("SHOW TABLES LIKE '{$this->table_name}'") === $this->table_name;
    }
    
    /**
     * Add log entry
     */
    public function add_log($user_id, $rule_id, $old_group_id, $new_group_id) {
        if (!$this->table_exists()) {
            return false;
        }
        
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->table_name,
            array(
                'user_id' => absint($user_id),
                'rule_id' => absint($rule_id),
                'old_group_id' => $old_group_id ? absint($old_group_id) : null,
                'new_group_id' => absint($new_group_id),
                'date_created' => current_time('mysql')
            ),
            array('%d', '%d', '%d', '%d', '%s')
        );
        
        return $result !== false ? $wpdb->insert_id : false;
    }
    
    /**
     * Get log entries with pagination
     */
    public function get_logs($page = 1, $per_page = 20, $user_id = null, $rule_id = null) {
        if (!$this->table_exists()) {
            return array('logs' => array(), 'total' => 0);
        }
        
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        $where_conditions = array();
        $where_values = array();
        
        if ($user_id) {
            $where_conditions[] = 'l.user_id = %d';
            $where_values[] = absint($user_id);
        }
        
        if ($rule_id) {
            $where_conditions[] = 'l.rule_id = %d';
            $where_values[] = absint($rule_id);
        }
        
        $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$this->table_name} l $where_clause";
        if (!empty($where_values)) {
            $count_sql = $wpdb->prepare($count_sql, $where_values);
        }
        $total = $wpdb->get_var($count_sql);
        
        // Get logs with user, rule, and group info
        $sql = "SELECT l.*, u.display_name, u.user_email, r.post_title as rule_name,
                       og.post_title as old_group_name, ng.post_title as new_group_name
                FROM {$this->table_name} l
                LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
                LEFT JOIN {$wpdb->posts} r ON l.rule_id = r.ID
                LEFT JOIN {$wpdb->posts} og ON l.old_group_id = og.ID
                LEFT JOIN {$wpdb->posts} ng ON l.new_group_id = ng.ID
                $where_clause
                ORDER BY l.date_created DESC
                LIMIT %d OFFSET %d";
        
        $query_values = array_merge($where_values, array($per_page, $offset));
        $logs = $wpdb->get_results($wpdb->prepare($sql, $query_values));
        
        return array(
            'logs' => $logs ?: array(),
            'total' => intval($total)
        );
    }
    
    /**
     * Search logs
     */
    public function search_logs($search_term, $page = 1, $per_page = 20) {
        if (!$this->table_exists() || empty($search_term)) {
            return array('logs' => array(), 'total' => 0);
        }
        
        global $wpdb;
        
        $offset = ($page - 1) * $per_page;
        $search_term = '%' . $wpdb->esc_like($search_term) . '%';
        
        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$this->table_name} l
                      LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
                      LEFT JOIN {$wpdb->posts} r ON l.rule_id = r.ID
                      LEFT JOIN {$wpdb->posts} og ON l.old_group_id = og.ID
                      LEFT JOIN {$wpdb->posts} ng ON l.new_group_id = ng.ID
                      WHERE u.display_name LIKE %s OR u.user_email LIKE %s OR r.post_title LIKE %s
                         OR og.post_title LIKE %s OR ng.post_title LIKE %s";
        $total = $wpdb->get_var($wpdb->prepare($count_sql, $search_term, $search_term, $search_term, $search_term, $search_term));
        
        // Get search results
        $sql = "SELECT l.*, u.display_name, u.user_email, r.post_title as rule_name,
                       og.post_title as old_group_name, ng.post_title as new_group_name
                FROM {$this->table_name} l
                LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
                LEFT JOIN {$wpdb->posts} r ON l.rule_id = r.ID
                LEFT JOIN {$wpdb->posts} og ON l.old_group_id = og.ID
                LEFT JOIN {$wpdb->posts} ng ON l.new_group_id = ng.ID
                WHERE u.display_name LIKE %s OR u.user_email LIKE %s OR r.post_title LIKE %s
                   OR og.post_title LIKE %s OR ng.post_title LIKE %s
                ORDER BY l.date_created DESC
                LIMIT %d OFFSET %d";
        
        $logs = $wpdb->get_results($wpdb->prepare($sql, $search_term, $search_term, $search_term, $search_term, $search_term, $per_page, $offset));
        
        return array(
            'logs' => $logs ?: array(),
            'total' => intval($total)
        );
    }
    
    /**
     * Get user's log history
     */
    public function get_user_logs($user_id, $limit = 10) {
        if (!$this->table_exists()) {
            return array();
        }
        
        global $wpdb;
        
        $sql = "SELECT l.*, r.post_title as rule_name,
                       og.post_title as old_group_name, ng.post_title as new_group_name
                FROM {$this->table_name} l
                LEFT JOIN {$wpdb->posts} r ON l.rule_id = r.ID
                LEFT JOIN {$wpdb->posts} og ON l.old_group_id = og.ID
                LEFT JOIN {$wpdb->posts} ng ON l.new_group_id = ng.ID
                WHERE l.user_id = %d
                ORDER BY l.date_created DESC
                LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, absint($user_id), $limit)) ?: array();
    }
    
    /**
     * Get rule's log history
     */
    public function get_rule_logs($rule_id, $limit = 10) {
        if (!$this->table_exists()) {
            return array();
        }
        
        global $wpdb;
        
        $sql = "SELECT l.*, u.display_name, u.user_email,
                       og.post_title as old_group_name, ng.post_title as new_group_name
                FROM {$this->table_name} l
                LEFT JOIN {$wpdb->users} u ON l.user_id = u.ID
                LEFT JOIN {$wpdb->posts} og ON l.old_group_id = og.ID
                LEFT JOIN {$wpdb->posts} ng ON l.new_group_id = ng.ID
                WHERE l.rule_id = %d
                ORDER BY l.date_created DESC
                LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, absint($rule_id), $limit)) ?: array();
    }
    
    /**
     * Delete old logs (cleanup)
     */
    public function cleanup_old_logs($days = 365) {
        if (!$this->table_exists()) {
            return false;
        }
        
        global $wpdb;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$this->table_name} WHERE date_created < %s",
            $cutoff_date
        ));
    }
    
    /**
     * Get table stats
     */
    public function get_stats() {
        if (!$this->table_exists()) {
            return array();
        }
        
        global $wpdb;
        
        $stats = array();
        $stats['total_entries'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        $stats['unique_users'] = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$this->table_name}");
        $stats['unique_rules'] = $wpdb->get_var("SELECT COUNT(DISTINCT rule_id) FROM {$this->table_name}");
        $stats['oldest_entry'] = $wpdb->get_var("SELECT MIN(date_created) FROM {$this->table_name}");
        $stats['newest_entry'] = $wpdb->get_var("SELECT MAX(date_created) FROM {$this->table_name}");
        
        return $stats;
    }
    
    /**
     * Drop table (for uninstall)
     */
    public function drop_table() {
        global $wpdb;
        return $wpdb->query("DROP TABLE IF EXISTS {$this->table_name}");
    }
}

// Initialize the log system
B2BKing_Group_Rules_Log::get_instance();
