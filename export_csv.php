<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $wpdb;
$table_name = $wpdb->prefix . "COD_orders";

if(isset($_POST['action']) && $_POST['action'] == "export_call_list") {

    if ( ! wp_verify_nonce( $_POST['wp_nonce'], 'ajax-nonce' ) )
        die ( 'Busted!');

    $json = array();
    $params = array(
                'page' => 'export_csv',
                'post_data' => json_encode($_POST),
                'shop' => COD_STORE_URL,
            );
    
    $url = COD_SHOPIAPPS_URL.'orders.php';
    $jsn = COD_remote_call($params,$url);
    $json = json_decode($jsn,true);
    
    if (!empty($json)) {
        # Generate CSV data from array
        $fh = fopen('php://temp', 'rw'); # don't create a file, attempt
        # to use memory instead
        # write out the headers
        fputcsv($fh, array_keys(current($json)));

        # write out the data
        foreach ($json as $row) {
            fputcsv($fh, $row);
        }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        //$data = str_putcsv($json);
        header("Content-type: application/x-msdownload");
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=COD_orders" . time() . ".csv");
        header("Pragma: no-cache");
        header("Expires: 0");
        
        echo $csv;
        exit;
    }
}

?>