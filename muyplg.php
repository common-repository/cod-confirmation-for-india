<?php
/*
Plugin Name: COD Order Confirmation for India
Description: This plugin will confirm your COD orders on call or SMS as per your selected mode and you don't need to create any account on any service provider sites!
Version: 1.2.0
Author: Softpulse Infotech
Author URI: http://softpulseinfotech.com
*/
if ( ! defined( 'ABSPATH' ) ) exit;
define('COD_SHOPIAPPS_URL', 'https://shopiapps.in/wpcod_plg/');
define('COD_STORE_URL', COD_remove_http(site_url()));
function COD_Confirm() {
    if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
        global $table_prefix, $wpdb;
        $table_name = $table_prefix.'COD_settings';
        $create_tbl = "CREATE TABLE `{$table_name}` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `order_confirm` varchar(255) NOT NULL,
              `order_cancel` varchar(255) NOT NULL,
              `order_pending` varchar(255) NOT NULL,
              `pause_plug` enum('0','1') NOT NULL DEFAULT '0',
              `order_cancel_mode` enum('0','1') NOT NULL DEFAULT '1',
              `order_contact` varchar(255) NOT NULL,
              `owner_phno` bigint(20) NOT NULL,
              `request_btn` enum('0','1') NOT NULL DEFAULT '0',
              `call_btn` enum('0','1') NOT NULL DEFAULT '0',
              `cancel_btn` enum('0','1') NOT NULL DEFAULT '1',
              `app_mode` enum('1','2','3') NOT NULL DEFAULT '1',
              `IVR_call_message` VARCHAR(500) NOT NULL  DEFAULT 'thank you for your order , this is an order confirmation call , please press {Confirmation_Digit} to confirm OR press {Cancellation_Digit} to cancel your order',
              `IVR_confirm_message` VARCHAR(300) NOT NULL  DEFAULT 'thank you , your order has been confirmed',
              `IVR_cancel_message` VARCHAR(300) NOT NULL  DEFAULT 'ok , your order has been cancelled',
              PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
        $wpdb->query($create_tbl);
        $ins = "INSERT INTO {$table_name} (app_mode,order_confirm,order_cancel,order_pending,pause_plug,order_cancel_mode) VALUES ('1','Order Confirmed','Order Canceled','Order Pending','0','1')";
        $result = $wpdb->query($ins);
        $blogusers = get_users('role=Administrator');
        foreach ($blogusers as $user) {
            $user_email = $user->user_email;
            $user_name = $user->user_login;
        }
        $params = array('shop'=>COD_STORE_URL,'action'=>'add_new','status' => 'installed','store_email'=> $user_email);
        $url = COD_SHOPIAPPS_URL.'install.php';
        COD_remote_call($params,$url);
    } else {
        echo '<h3>'.__('Woocommerce is compulsory for activate this plugin!', 'ap').'</h3>';
        //Adding @ before will prevent XDebug output
        @trigger_error(__('Please install woocommerce before activating.', 'ap'), E_USER_ERROR);
    }
}
function COD_deactivate(){
    global $table_prefix, $wpdb;
    $table_name = $wpdb->prefix . "COD_settings";
    $wpdb->query("DROP table {$table_name}");
    $params = array('shop'=>COD_STORE_URL,'action'=>'deactivate','status' => 'uninstalled');
    $url = COD_SHOPIAPPS_URL.'install.php';
    COD_remote_call($params,$url);
}
/* intialization */
register_activation_hook(__FILE__,'COD_Confirm');
register_deactivation_hook( __FILE__, 'COD_deactivate' );
/* Add setting link at plugin listing page */
$plugin = plugin_basename( __FILE__ );
add_filter( "plugin_action_links_$plugin", 'plugin_add_settings_link' );
function plugin_add_settings_link( $links ) {
    $settings_link = '<a href="'.admin_url().'/admin.php?page=COD_confirm">' . __( 'Settings' ) . '</a>';
    array_push( $links, $settings_link );
    return $links;
}
/* Creating Menus */
function COD_Menu(){
    /* Adding menus */
    add_menu_page(__('COD_Confirm'),'COD Settings', 'edit_pages','COD_confirm', 'COD_plugin_Settings');
    /* Adding Sub menus */
    
    add_submenu_page('COD_confirm', 'COD Orders', 'COD Orders', 'edit_pages', 'Orders', 'COD_plugin_orders');
    add_submenu_page('COD_confirm', 'Transactions', 'Transactions', 'edit_pages', 'transactions', 'COD_plugin_all_transactions');
    add_submenu_page('COD_confirm', 'Help', 'Help', 'edit_pages', 'help', 'COD_plugin_help_sec');
    
    /* Adding css and Js */ 
    wp_register_style('demo_table.css', plugin_dir_url(__FILE__) . 'css/demo_table.css');
    wp_enqueue_style('demo_table.css');
    wp_register_style('font-awesome.min.css', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css');
    wp_enqueue_style('font-awesome.min.css');
    wp_register_style('common.css', plugin_dir_url(__FILE__) . 'css/common.css');
    wp_enqueue_style('common.css');
   
    wp_register_script('jquery.dataTables.js', plugin_dir_url(__FILE__) . 'js/jquery.dataTables.js', array('jquery'));
    wp_enqueue_script('jquery.dataTables.js');
    wp_register_style('jquery-ui.css', plugin_dir_url(__FILE__) .'css/jquery-ui.css');
    wp_enqueue_style('jquery-ui.css');
   
    wp_enqueue_script('jquery-ui-datepicker');
}
function COD_remove_http($url) {
   $disallowed = array('http://', 'https://');
   foreach($disallowed as $d) {
      if(strpos($url, $d) === 0) {
         return str_replace($d, '', $url);
      }
   }
   return $url;
}
add_action('admin_menu', 'COD_Menu');
add_action('woocommerce_thankyou','COD_new_order_callback',10,1 ); 
add_action('admin_init', 'COD_orders_export_csv');
add_action( 'wp_ajax_COD_plugin_ordersList', 'COD_plugin_ordersList' );
add_action( 'wp_ajax_COD_plugin_AllTransaction', 'COD_plugin_AllTransaction' );
add_action( 'wp_ajax_COD_plugin_sender_request', 'COD_plugin_sender_request' );
add_action( 'wp_ajax_COD_reCallCustomer', 'COD_reCallCustomer' );
add_action( 'wp_enqueue_scripts', 'COD_plugin_enqueue' );
function COD_plugin_enqueue()
{
    // order list Uniqe call.
    wp_enqueue_script( 'COD_plugin_ordersList' );
    // Localize the script
    $data = array( 
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'COD_plugin_nonce' )
    );
    wp_localize_script( 'COD_plugin_ordersList', 'COD_plugin_object', $data );
    wp_enqueue_script( 'COD_plugin_AllTransaction' );
    $data = array( 
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'nonce'    => wp_create_nonce( 'COD_plugin_nonce' )
    );
    wp_localize_script( 'COD_plugin_AllTransaction', 'COD_plugin_object', $data );
}
add_action('init', 'CODplug_init_internal');
add_filter( 'query_vars', 'CODplug_query_vars' );
add_action( 'parse_request', 'CODplug_parse_request' );
add_filter('admin_footer_text', 'COD_reviews_footer_admin');
function COD_reviews_footer_admin () {
  echo 'If you like <strong>COD Order Confirmation</strong> please leave us a <a href="https://wordpress.org/support/plugin/cod-confirmation-for-india/reviews/#new-post" target="_blank" data-rated="Thanks :)">★★★★★</a> rating. A huge thanks in advance!';
}
function CODplug_init_internal()
{
    add_rewrite_rule( 'sms-confirm.php$', 'index.php?wpse9870_api=1', 'top' );
    add_rewrite_rule( 'call-confirm.php$', '?wpclbk=1', 'top' );
}
function CODplug_query_vars( $query_vars )
{
    $query_vars[] = 'wpse9870_api';
    $query_vars[] = 'wpclbk';
    return $query_vars;
}
function CODplug_parse_request( &$wp )
{
    if ( array_key_exists( 'wpse9870_api', $wp->query_vars ) ) {
        include 'sms-confirm.php';
        exit();
    }
   
    if ( array_key_exists( 'wpclbk', $wp->query_vars ) ) {
        include 'call-confirm.php';
        exit();
    }
    
    return;
}
/* COD Settings page */
function COD_plugin_Settings() 
{
    include "cod_setting.php";
}
/* Display All Orders */
function COD_plugin_orders() 
{
    include "cod_orders.php";
}
/* All transactions */
function COD_plugin_all_transactions(){
    include "all_transactions.php";
}
/* Get Help */
function COD_plugin_help_sec()
{
    include "help.php";
}
/* Export All Orders */
function COD_orders_export_csv()
{
    include 'export_csv.php';
}
/* Recall to customer fire same hook*/
function COD_reCallCustomer(){
    
    $order_id = str_replace('#', '', $_POST['order_id']);
    $recall = true;
    include 'order_place.php';
}
/* Trigger order hook */
function COD_new_order_callback($order_id)
{
    include 'order_place.php';
}
/* Get all orders data */
function COD_order_data( $order ) 
{
    
    $order_post = get_post( $order->id );
    $dp         = wc_get_price_decimals();
    $order_data = array(
        'id'                        => $order->id,
        'order_number'              => $order->get_order_number(),
        'created_at'                => $order_post->post_date_gmt ,
        'updated_at'                =>  $order_post->post_modified_gmt ,
        'completed_at'              => $order->completed_date,
        'status'                    => $order->get_status(),
        'currency'                  => get_woocommerce_currency_symbol(),
        'total'                     => wc_format_decimal( $order->get_total(), $dp ),
        'subtotal'                  => wc_format_decimal( $order->get_subtotal(), $dp ),
        'total_line_items_quantity' => $order->get_item_count(),
        'total_tax'                 => wc_format_decimal( $order->get_total_tax(), $dp ),
        'total_shipping'            => wc_format_decimal( $order->get_total_shipping(), $dp ),
        'cart_tax'                  => wc_format_decimal( $order->get_cart_tax(), $dp ),
        'shipping_tax'              => wc_format_decimal( $order->get_shipping_tax(), $dp ),
        'total_discount'            => wc_format_decimal( $order->get_total_discount(), $dp ),
        'shipping_methods'          => $order->get_shipping_method(),
        'payment_details' => array(
            'method_id'    => $order->payment_method,
            'method_title' => $order->payment_method_title,
            'paid'         => isset( $order->paid_date ),
        ),
        'billing_address' => array(
            'first_name' => $order->billing_first_name,
            'last_name'  => $order->billing_last_name,
            'company'    => $order->billing_company,
            'address_1'  => $order->billing_address_1,
            'address_2'  => $order->billing_address_2,
            'city'       => $order->billing_city,
            'state'      => $order->billing_state,
            'postcode'   => $order->billing_postcode,
            'country'    => $order->billing_country,
            'email'      => $order->billing_email,
            'phone'      => $order->billing_phone,
        ),
        'shipping_address' => array(
            'first_name' => $order->shipping_first_name,
            'last_name'  => $order->shipping_last_name,
            'company'    => $order->shipping_company,
            'address_1'  => $order->shipping_address_1,
            'address_2'  => $order->shipping_address_2,
            'city'       => $order->shipping_city,
            'state'      => $order->shipping_state,
            'postcode'   => $order->shipping_postcode,
            'country'    => $order->shipping_country,
        ),
        'customer_id'               => $order->get_user_id(),
        'view_order_url'            => $order->get_view_order_url(),
        'line_items'                => array(),
        'shipping_lines'            => array(),
        'tax_lines'                 => array(),
        'fee_lines'                 => array(),
        'coupon_lines'              => array(),
    );
    // add line items
    foreach ( $order->get_items() as $item_id => $item ) {
        $product     = $order->get_product_from_item( $item );
        $product_id  = null;
        $product_sku = null;
        // Check if the product exists.
        if ( is_object( $product ) ) {
            $product_id  = ( isset( $product->variation_id ) ) ? $product->variation_id : $product->id;
            $image = wp_get_attachment_image_src( get_post_thumbnail_id( $product->id ), 'single-post-thumbnail' );
            $product_sku = $product->get_sku();
        }
        $order_data['line_items'][] = array(
            'id'           => $item_id,
            'subtotal'     => wc_format_decimal( $order->get_line_subtotal( $item, false, false ), $dp ),
            'subtotal_tax' => wc_format_decimal( $item['line_subtotal_tax'], $dp ),
            'total'        => wc_format_decimal( $order->get_line_total( $item, false, false ), $dp ),
            'total_tax'    => wc_format_decimal( $item['line_tax'], $dp ),
            'price'        => wc_format_decimal( $order->get_item_total( $item, false, false ), $dp ),
            'quantity'     => wc_stock_amount( $item['qty'] ),
            'tax_class'    => ( ! empty( $item['tax_class'] ) ) ? $item['tax_class'] : null,
            'name'         => $item['name'],
            'product_id'   => $product_id,
            'image'        => $image[0],
            'sku'          => $product_sku,
        );
    }
    // Add shipping.
    foreach ( $order->get_shipping_methods() as $shipping_item_id => $shipping_item ) {
        $order_data['shipping_lines'][] = array(
            'id'           => $shipping_item_id,
            'method_id'    => $shipping_item['method_id'],
            'method_title' => $shipping_item['name'],
            'total'        => wc_format_decimal( $shipping_item['cost'], $dp ),
        );
    }
    // Add taxes.
    foreach ( $order->get_tax_totals() as $tax_code => $tax ) {
        $order_data['tax_lines'][] = array(
            'id'       => $tax->id,
            'rate_id'  => $tax->rate_id,
            'code'     => $tax_code,
            'title'    => $tax->label,
            'total'    => wc_format_decimal( $tax->amount, $dp ),
            'compound' => (bool) $tax->is_compound,
        );
    }
    // Add fees.
    foreach ( $order->get_fees() as $fee_item_id => $fee_item ) {
        $order_data['fee_lines'][] = array(
            'id'        => $fee_item_id,
            'title'     => $fee_item['name'],
            'tax_class' => ( ! empty( $fee_item['tax_class'] ) ) ? $fee_item['tax_class'] : null,
            'total'     => wc_format_decimal( $order->get_line_total( $fee_item ), $dp ),
            'total_tax' => wc_format_decimal( $order->get_line_tax( $fee_item ), $dp ),
        );
    }
    // Add coupons.
    foreach ( $order->get_items( 'coupon' ) as $coupon_item_id => $coupon_item ) {
        $order_data['coupon_lines'][] = array(
            'id'     => $coupon_item_id,
            'code'   => $coupon_item['name'],
            'amount' => wc_format_decimal( $coupon_item['discount_amount'], $dp ),
        );
    }
    $order_data = apply_filters( 'woocommerce_cli_order_data', $order_data );
    return $order_data;
}
/* List all cod orders */
function COD_plugin_ordersList() 
{
    $nonce = $_POST['_ajax_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) )
        die ( 'Busted!');
    ob_clean();
    $params = array(
                'page' => 'order_list',
                'shop' => COD_STORE_URL,
                'data' => json_encode($_REQUEST)
            );
   
    $url = COD_SHOPIAPPS_URL.'orders.php';
    $jsn = COD_remote_call($params,$url);
    echo $jsn;
    die();
}
function COD_plugin_AllTransaction(){
    $nonce = $_POST['_ajax_nonce'];
    if ( ! wp_verify_nonce( $nonce, 'ajax-nonce' ) )
        die ( 'Busted!');
    ob_clean();
    $params = array(
                'page' => 'trans_list',
                'shop' => COD_STORE_URL,
                'data' => json_encode($_REQUEST)
            );
   
    $url = COD_SHOPIAPPS_URL.'orders.php';
    $jsn = COD_remote_call($params,$url);
    echo $jsn;
    die();
}
/* Send request for update sender ID*/
function COD_plugin_sender_request($post)
{
    ob_clean();
    $sender_id = sanitize_text_field($_POST['sender_id']);
    $params = array(
        'action'=>'update_senderID',
        'sender_id'=> $sender_id,
        'shop' => COD_STORE_URL
    );
    
    $url = COD_SHOPIAPPS_URL.'install.php';
    $jsn = COD_remote_call($params,$url);
    echo $jsn;
    die();
}
/* Get Curl response */
function COD_remote_call($params,$url){   
    $response = wp_remote_post($url,array(
        'method'      => 'POST',
        'timeout'     => 45,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking'    => true,
        'headers'     => array(),
        'body'        => $params
        )
    );
    if ( is_wp_error( $response ) ) {
        $result = $response->get_error_message();
    } else {
        $result = $response['body'];
    }
    
    return $result;
}
?>