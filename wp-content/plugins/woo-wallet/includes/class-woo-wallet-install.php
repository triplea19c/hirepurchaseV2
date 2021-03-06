<?php

if (!defined('ABSPATH')) {
    exit;
}

class Woo_Wallet_Install {

    /**
     * Plugin install
     * @return void
     */
    public static function install() {
        if (!is_blog_installed()) {
            return;
        }
        self::create_tables();
        if (!get_option('_woo_wallet_recharge_product') || !wc_get_product(get_option('_woo_wallet_recharge_product'))) {
            self::create_product();
        }
    }

    /**
     * plugins table creation
     * @global object $wpdb
     */
    private static function create_tables() {
        global $wpdb;
        $wpdb->hide_errors();
        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        dbDelta(self::get_schema());
    }

    /**
     * Plugin table schema
     * @global object $wpdb
     * @return string
     */
    private static function get_schema() {
        global $wpdb;
        $collate = '';

        if ($wpdb->has_cap('collation')) {
            $collate = $wpdb->get_charset_collate();
        }
        $tables = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}woo_wallet_transactions (
            transaction_id BIGINT UNSIGNED NOT NULL auto_increment,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT '0',
            date timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            type varchar(200) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            balance DECIMAL(10,2) NOT NULL,
            details longtext NULL,
            PRIMARY KEY  (transaction_id),
            KEY user_id (user_id)
        ) $collate;
        CREATE TABLE {$wpdb->prefix}woo_wallet_transaction_meta (
            meta_id BIGINT UNSIGNED NOT NULL auto_increment,
            transaction_id BIGINT UNSIGNED NOT NULL,
            meta_key varchar(255) default NULL,
            meta_value longtext NULL,
            PRIMARY KEY  (meta_id),
            KEY transaction_id (transaction_id),
            KEY meta_key (meta_key(32))
        ) $collate;";
        return $tables;
    }

    /**
     * create rechargeable product
     */
    private static function create_product() {
        $product_args = array(
            'post_title' => wc_clean('Wallet Topup'),
            'post_status' => 'publish',
            'post_type' => 'product',
            'post_excerpt' => '',
            'post_content' => stripslashes(html_entity_decode('Auto generated product for wallet recharge please do not delete or update.', ENT_QUOTES, 'UTF-8')),
            'post_author' => 1
        );
        $product_id = wp_insert_post($product_args);
        if (!is_wp_error($product_id)) {
            $product = wc_get_product($product_id);
            wp_set_object_terms($product_id, 'simple', 'product_type');
            update_post_meta($product_id, '_stock_status', 'instock');
            update_post_meta($product_id, 'total_sales', '0');
            update_post_meta($product_id, '_downloadable', 'no');
            update_post_meta($product_id, '_virtual', 'yes');
            update_post_meta($product_id, '_regular_price', '');
            update_post_meta($product_id, '_sale_price', '');
            update_post_meta($product_id, '_purchase_note', '');
            update_post_meta($product_id, '_featured', 'no');
            update_post_meta($product_id, '_weight', '');
            update_post_meta($product_id, '_length', '');
            update_post_meta($product_id, '_width', '');
            update_post_meta($product_id, '_height', '');
            update_post_meta($product_id, '_sku', '');
            update_post_meta($product_id, '_product_attributes', array());
            update_post_meta($product_id, '_sale_price_dates_from', '');
            update_post_meta($product_id, '_sale_price_dates_to', '');
            update_post_meta($product_id, '_price', '');
            update_post_meta($product_id, '_sold_individually', 'yes');
            update_post_meta($product_id, '_manage_stock', 'no');
            update_post_meta($product_id, '_backorders', 'no');
            update_post_meta($product_id, '_stock', '');
            if (version_compare(WC_VERSION, '3.0', '>=')) {
                $product->set_reviews_allowed(false);
                $product->set_catalog_visibility('hidden');
                $product->save();
            }

            update_option('_woo_wallet_recharge_product', $product_id);
        }
    }

}
