<?php
/**
 * WPLA Helper utilities
 */

class WPLA_Helper {
    
    /**
     * Extract SKU from log parameters field
     *
     * @param string $parameters The log parameters field content
     * @return string The extracted SKU or 'Unknown SKU' if not found
     */
    public static function extractSkuFromLogParameters( $parameters ) {
        // Try new format first (SKU: value)
        if ( !empty( $parameters ) && strpos( $parameters, 'SKU:' ) !== false ) {
            if ( preg_match('/SKU: (.+?)(?:\n|$)/', $parameters, $matches) ) {
                return sanitize_text_field( trim( $matches[1] ) );
            }
        }
        
        // Try serialized parameters (legacy)
        if ( !empty( $parameters ) ) {
            $unserialized = maybe_unserialize( $parameters );
            if ( is_array( $unserialized ) && isset( $unserialized['sku'] ) ) {
                return sanitize_text_field( $unserialized['sku'] );
            }
        }
        
        return 'Unknown SKU';
    }
    
    /**
     * Safely extract account title with fallback
     *
     * @param int $account_id
     * @return string
     */
    public static function getAccountTitle( $account_id ) {
        if ( empty( $account_id ) ) {
            return 'Unknown Account';
        }
        
        $account = WPLA()->memcache->getAccount( $account_id );
        return $account ? sanitize_text_field( $account->title ) : 'Account #' . intval( $account_id );
    }
}