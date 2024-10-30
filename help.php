<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

echo COD_remote_call(array('action'=>'Help','shop' => COD_STORE_URL),COD_SHOPIAPPS_URL.'help.php');
?>