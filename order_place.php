<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $wpdb;
$setting_table = $wpdb->prefix . "COD_settings";
$order = wc_get_order( $order_id );
$gatway = $order->get_payment_method();
$params = array();
if($gatway == 'cod'){
    $set_sql = "SELECT pause_plug,app_mode,order_status_change,IVR_call_message,IVR_confirm_message,IVR_cancel_message FROM `{$setting_table}` limit 1";
    $wpdb->query($set_sql);
    if($wpdb->last_error !== '') :
        $wpdb->query("ALTER TABLE `{$setting_table}` ADD `order_status_change` ENUM('1','0') NOT NULL DEFAULT '1' AFTER `pause_plug`");
    endif;
    $setting_ary = $wpdb->get_results($set_sql, ARRAY_A);
    $setting_ary = $setting_ary[0];
    
    if($setting_ary['pause_plug'] == '0'){
        $params['order_id'] = $order_id;
        $params['recall'] = (isset($recall) ? '1' : '0');
        $params['shop'] = COD_STORE_URL;
        $params['admin_url'] = admin_url();
        
        /*if($setting_ary['order_status_change'] == '1') $order->update_status('wc-on-hold', 'Order confirmation is pending');*/
        $order_data = $order->get_data(); 
        $params['phone'] = $order_data['billing']['phone'];
        $params['shop_name'] = get_bloginfo('name');
        $params['mode'] = $setting_ary['app_mode'];
        $params['IVR_call_message'] = $setting_ary['IVR_call_message'];
        $params['IVR_confirm_message'] = $setting_ary['IVR_confirm_message'];
        $params['IVR_cancel_message'] = $setting_ary['IVR_cancel_message'];
    
        $url = COD_SHOPIAPPS_URL.'wp_hook.php';
        $result = COD_remote_call($params,$url);
    }
}
?>