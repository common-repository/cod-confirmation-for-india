<?php
if ( ! defined( 'ABSPATH' ) ) exit; //Exit if accessed directly
$get = json_encode($_GET);
global $wpdb;
$setting_table = $wpdb->prefix . "COD_settings";
if(isset($_GET['wpclbk'])  && $_GET['wpclbk'] != ''){//&& isset($_GET['dtmf'])
    $order_id = filter_input ( INPUT_GET , 'wpclbk' , FILTER_SANITIZE_NUMBER_INT );
    $order = new WC_Order($order_id);
    $verified = filter_input ( INPUT_GET , 'dtmf' , FILTER_SANITIZE_NUMBER_INT );
    $id = 1;
    $sql = $wpdb->prepare("SELECT order_confirm,order_cancel,order_pending,order_cancel_mode,order_status_change FROM {$setting_table} WHERE id = %d", $id);
    $wpdb->query($sql);
    if($wpdb->last_error !== '') :
        $wpdb->query("ALTER TABLE `{$setting_table}` ADD `order_status_change` ENUM('1','0') NOT NULL DEFAULT '1' AFTER `pause_plug`");
    endif;
    $setting = $wpdb->get_results($sql, ARRAY_A);
    $data = $setting[0];
    
    delete_post_meta($order_id, 'Order Failed','Confirmation failed by COD');
    if($verified == '1'){ // confirm order
        add_post_meta( $order_id, 'Order Confirm',$data['order_confirm']);
        if($data['order_status_change'] == '1') $order->update_status('wc-processing', 'Order confirm by COD plugin');
    }elseif($verified == '2'){ // cancel order
        add_post_meta( $order_id, 'Order Cancelled',$data['order_cancel']);
        if($data['order_cancel_mode'] == '1') $order->update_status('wc-cancelled', 'Order Cancelled by COD plugin');
    }else{
        add_post_meta( $order_id, 'Order Failed','Confirmation failed by COD');
        /*if($data['order_status_change'] == '1') $order->update_status('wc-failed', 'Order confirmation failed by COD plugin');*/
    }/*else{ // pending orders
        add_post_meta( $order_id, 'Order Pending',$data['order_pending']);
        $order->update_status('wc-on-hold', 'Order confirmation is pending');
    }*/
}
?>