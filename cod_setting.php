<?php 
if ( ! defined( 'ABSPATH' ) ) exit; //Exit if accessed directly
global $wpdb;
$table_name = $wpdb->prefix . "COD_settings";
$wp_nonce = wp_create_nonce('ajax-nonce');
$id = 1;
if(isset($_POST['btnSave'])){
	if ( empty($_POST[$wp_nonce]) || ! wp_verify_nonce( $_POST[$wp_nonce], basename(__FILE__) ) ) return;
	$pause_plug = filter_input ( INPUT_POST , 'pause_plug' , FILTER_SANITIZE_NUMBER_INT );
    if(!isset($_POST['pause_plug'])){
    	$pause_plug = 0;
    } 
    $req_btn = filter_input ( INPUT_POST , 'request_btn' , FILTER_SANITIZE_NUMBER_INT );
    if(!isset($_POST['request_btn'])){
    	$req_btn = 0;
    }
    $call_btn = filter_input ( INPUT_POST , 'call_btn' , FILTER_SANITIZE_NUMBER_INT );
    if(!isset($_POST['call_btn'])){
    	$call_btn = 0;
    }
    $cancel_btn = filter_input ( INPUT_POST , 'cancel_btn' , FILTER_SANITIZE_NUMBER_INT );
    if(!isset($_POST['cancel_btn'])){
    	$cancel_btn = 0;
    }
    $app_mode = filter_input ( INPUT_POST , 'app_mode' , FILTER_SANITIZE_NUMBER_INT );
    if(!isset($_POST['app_mode'])){
    	$app_mode = 1;	
    } 
    $cancel_mode = filter_input ( INPUT_POST , 'cancel_mode' , FILTER_SANITIZE_NUMBER_INT );
    if(!isset($_POST['cancel_mode'])){
    	$cancel_mode = 0;
    }
    $owner_phno = '';
    if(isset($_POST['owner_phno'])){
     	$owner_phno = filter_input ( INPUT_POST , 'owner_phno' , FILTER_SANITIZE_NUMBER_INT );
    }
    $order_confirm = sanitize_text_field($_POST['order_confirm']);
    $order_cancel = sanitize_text_field($_POST['order_cancel']);
    $order_pending = sanitize_text_field($_POST['order_pending']);
    $IVR_call_message = sanitize_text_field($_POST['IVR_call_message']);
    $IVR_confirm_message = sanitize_text_field($_POST['IVR_confirm_message']);
    $IVR_cancel_message = sanitize_text_field($_POST['IVR_cancel_message']);
    $order_status_change = filter_input ( INPUT_POST , 'order_status_change' , FILTER_SANITIZE_NUMBER_INT );
    if(!isset($_POST['order_status_change'])){
    	$order_status_change = 0;
    }
    
    $order_contact = '';
    if(isset($_POST['order_contact'])){
    	$order_contact = sanitize_text_field($_POST['order_contact']);
    }
	$update = $wpdb->prepare("UPDATE {$table_name} SET order_confirm='{$order_confirm}',order_cancel='{$order_cancel}',order_pending='{$order_pending}',pause_plug='{$pause_plug}',order_cancel_mode='{$cancel_mode}',app_mode='{$app_mode}',request_btn='{$req_btn}',order_contact='{$order_contact}',call_btn='{$call_btn}',cancel_btn='{$cancel_btn}',owner_phno='{$owner_phno}',order_status_change='{$order_status_change}',IVR_call_message='{$IVR_call_message}',IVR_confirm_message='{$IVR_confirm_message}',IVR_cancel_message='{$IVR_cancel_message}' WHERE id = %d", $id);
	$result = $wpdb->query($update);
}
$sql = $wpdb->prepare("SELECT IVR_call_message FROM {$table_name} WHERE id = %d", $id);
$result = $wpdb->query($sql);
if($wpdb->last_error !== '') :
	$wpdb->query("ALTER TABLE `{$table_name}` ADD `IVR_call_message` VARCHAR(500) NOT NULL  DEFAULT 'thank you for your order , this is an order confirmation call , please press {Confirmation_Digit} to confirm OR press {Cancellation_Digit} to cancel your order' AFTER `app_mode`, ADD `IVR_confirm_message` VARCHAR(300) NOT NULL  DEFAULT 'thank you , your order has been confirmed' AFTER `IVR_call_message`, ADD `IVR_cancel_message` VARCHAR(300) NOT NULL  DEFAULT 'ok , your order has been cancelled' AFTER `IVR_confirm_message`");
	
endif;
$sql = $wpdb->prepare("SELECT order_confirm,order_cancel,order_pending,pause_plug,order_cancel_mode,app_mode,request_btn,order_contact,cancel_btn,call_btn,owner_phno,order_status_change,IVR_call_message,IVR_confirm_message,IVR_cancel_message FROM {$table_name} WHERE id = %d", $id);
$result = $wpdb->query($sql);
if($wpdb->last_error !== '') :
    $wpdb->query("ALTER TABLE `{$table_name}` ADD `order_status_change` ENUM('1','0') NOT NULL DEFAULT '1' AFTER `pause_plug`");
    $sql = $wpdb->prepare("SELECT order_confirm,order_cancel,order_pending,pause_plug,order_cancel_mode,app_mode,request_btn,order_contact,cancel_btn,call_btn,owner_phno,order_status_change FROM {$table_name} WHERE id = %d", $id);
    $result = $wpdb->query($sql);
endif;
$result = $wpdb->get_results($sql, ARRAY_A);
if(empty($result)){
	$table_name = $wpdb->prefix.'COD_settings';
    $create_tbl = "CREATE TABLE `{$table_name}` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `order_confirm` varchar(255) NOT NULL,
          `order_cancel` varchar(255) NOT NULL,
          `order_pending` varchar(255) NOT NULL,
          `pause_plug` enum('0','1') NOT NULL DEFAULT '0',
          `order_status_change` enum('0','1') NOT NULL DEFAULT '1',
          `order_cancel_mode` enum('0','1') NOT NULL DEFAULT '1',
          `order_contact` varchar(255) NOT NULL,
          `owner_phno` bigint(20) NOT NULL,
          `request_btn` enum('0','1') NOT NULL DEFAULT '0',
          `call_btn` enum('0','1') NOT NULL DEFAULT '0',
          `cancel_btn` enum('0','1') NOT NULL DEFAULT '0',
          `app_mode` enum('1','2','3') NOT NULL DEFAULT '1',
          PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1";
    $wpdb->query($create_tbl);
    
    $ins = "INSERT INTO {$table_name} (app_mode,order_status_change,order_confirm,order_cancel,order_pending,pause_plug,order_cancel_mode,cancel_btn) VALUES ('1','1','Order Confirmed','Order Canceled','Order Pending','0','1','0')";
    $result = $wpdb->query($ins);
}
$data = $result[0];
$get_settings = COD_remote_call(array('action'=>'get_settings','shop' => COD_STORE_URL),COD_SHOPIAPPS_URL.'install.php');
$plug_Settings = json_decode($get_settings,true);
/**/
$blogusers = get_users('role=Administrator');
foreach ($blogusers as $user) {
    $user_email = $user->user_email;
    $user_name = $user->user_login;
}
$MERCHANT_KEY = $plug_Settings['key'];
$SALT = $plug_Settings['salt'];
$PAYU_BASE_URL = $plug_Settings['url'];
$txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);
$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
?>
<?php echo COD_remote_call(array(),COD_SHOPIAPPS_URL.'msg_text.php'); ?>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" >
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>

<div class="wrap spmainDiv">
    <h2 class="main-heading"> <img src="<?=plugin_dir_url(__FILE__); ?>images/cod_icon.png"> COD - Order Confirmation </h2>
	<div id="message" class="balance_div">
		<div class="icon_div">
			<img src="<?=plugin_dir_url(__FILE__); ?>images/rs_icon.png">
		</div>
		<div class="bal_detail">
		    <p class="bal">Your Remaining balance is :  <strong style="font-size: 23px;color: green;">INR ₹<?=$plug_Settings['remain_bln'];?></strong> </p>
		    <p class="submit">
		        <button id="recharg_now" class="button button-primary">Recharge Now</button>
		    </p>
	    </div>
	    <div class="back-img">
	    	<img src="<?=plugin_dir_url(__FILE__); ?>images/bal-back.jpg">
	    </div>	    
	</div>    
    <form method="post" class="main-form">    
    	<?php wp_nonce_field(basename(__FILE__), $wp_nonce ); ?>	
    	<div class="padding_div">
    		<p><b>Custom field : </b> <i>Custom Order Fields use for add extra order information like order confirmed, cancelled or pending</i>.</p>
    		<h2 class="heading"> <i class="fa fa-cogs"></i> General Settings 
    			<div class="tooltip">
	    			<i class="fa fa-question-circle"></i> 
	    			<span class="tooltiptext">Basic settings for identify your COD orders status.</span>
    			</div>
    			<input type="submit" name="btnSave" value="Save Settings" class="save_btn button button-primary btnSave" />
    		</h2>
	        <table class="form-table white-back p-15" id="COD_setting">
	        	<tbody>
	                <tr>
	                    <th class="label">Order Confirm Mode </th>
	                    <td><div class="tooltip" style="margin-right: 10px;">
				    			<i class="fa fa-question-circle"></i> 
				    			<span class="tooltiptext"><b>Voice call mode : </b>Confirm COD orders only by voice call<br><b>SMS mode : </b>Confirm COD orders only by sms</span>
			    			</div>
			    			<div class="timer_radio">
			    				<div class="radio_div">
					    			<input name="app_mode" id="app_mode_call" type="radio" value="1" class="tog radio" <?=($data['app_mode'] == '1' ? 'checked':'');?> >
			                    	<label for="app_mode_call">Voice Call Mode</label>
			                    </div>
			                    <div class="radio_div">
			                    	<input name="app_mode" id="app_mode_sms" type="radio" value="2" class="tog radio" <?=($data['app_mode'] == '2' ? 'checked':'');?> >
			                    	<label for="app_mode_sms">SMS Mode</label>
			                    </div>
	                    	</div>
	                    </td>
	                </tr>
	        		<tr>
	        			<th class="label">Custom field for Order Confirm</th>
	        			<td><div class="tooltip" style="margin-right: 10px;">
				    			<i class="fa fa-question-circle"></i> 
				    			<span class="tooltiptext">Custom field value for confirmed order.</span>
			    			</div>
			    			<input class="regular-text" type="text" name="order_confirm" value="<?=$data['order_confirm'];?>">
	                    </td>
	        		</tr>
	        		<tr>
	        			<th class="label">Admin Cancel Order mode</th>
	        			<td>
	        				<div class="tooltip" style="margin-right: 10px;">
				    			<i class="fa fa-question-circle"></i> 
				    			<span class="tooltiptext"><b>Yes : </b>Add a custom field in order & automatically cancel order in admin panel.<br><b>No : </b>Only add a custom field in order but order will not get cancelled automatically in admin panel.</span>
			    			</div>
			    			<input name="cancel_mode" id="cancel_mode_yes" type="radio" value="1" class="tog" <?=($data['order_cancel_mode'] == '1' ? 'checked="checked"':'');?> >
	        				<label for="cancel_mode_yes">Yes</label>
	        				<input name="cancel_mode" id="cancel_mode_no" type="radio" value="0" class="tog" <?=($data['order_cancel_mode'] == '0' ? 'checked="checked"':'');?> >
	        				<label for="cancel_mode_no">No</label>
	        			</td>
	        		</tr>
	        		<tr>
	        			<th class="label">Custom field for Order Pending</th>
	        			<td>
	        				<div class="tooltip" style="margin-right: 10px;">
				    			<i class="fa fa-question-circle"></i> 
				    			<span class="tooltiptext">Custom field value for pending order.</span>
			    			</div>
			    			<input class="regular-text" type="text" name="order_pending" value="<?=$data['order_pending'];?>">
	                    </td>
	        		</tr>
	        		<tr>
	                    <th class="label">Order Status</th>
	                    <td><div class="tooltip" style="margin-right: 10px;">
				    			<i class="fa fa-question-circle"></i> 
				    			<span class="tooltiptext"><b>Checked : </b> When order get Confirm/Cancel order status will change automatic.</span>
			    			</div>
			    			<input name="order_status_change" type="checkbox" id="order_status_change" value="1" <?=($data['order_status_change'] == '1' ? 'checked="checked"':'');?>> Automatic change order status?
	                    </td>
	                </tr>
	                <tr>
	                    <th class="label">Pause plugin</th>
	                    <td><div class="tooltip" style="margin-right: 10px;">
				    			<i class="fa fa-question-circle"></i> 
				    			<span class="tooltiptext"><b>Checked : </b> Stop order confirmation process using this plugin.</span>
			    			</div>
			    			<input name="pause_plug" type="checkbox" id="pause_plug" value="1" <?=($data['pause_plug'] == '1' ? 'checked="checked"':'');?>> Do you want to pause this plugin?
	                    </td>
	                </tr>	      
	                <tr>
	        			<th class="label">Custom field for Order Cancel</th>
	        			<td>
	        				<div class="tooltip" style="margin-right: 10px;">
				    			<i class="fa fa-question-circle"></i> 
				    			<span class="tooltiptext">Custom field value for cancelled order.</span>
			    			</div>
			    			<input class="regular-text" type="text" name="order_cancel" value="<?=$data['order_cancel'];?>">
	                    </td>
	        		</tr> 
	                          
	            </tbody>
	        </table>
	    </div>
	    <div class="padding_div">	    	
	    	<div id="sms_tbl" class="m_t_15" style="<?=($data['app_mode'] == '2' ? '':'display:none;');?>">
	    		<h2 class="heading"> <i class="fa fa-envelope"></i> SMS Settings 
	    			<div class="tooltip">
		    			<i class="fa fa-question-circle"></i> 
		    			<span class="tooltiptext">Message text will forward to customers for confirm order using this SENDER ID.</span>
	    			</div>
	    		</h2>
       			<table class="form-table white-back p-15">
		            <tbody>
		        		
		                <tr>
		                    <th class="label">Message text</th>
		                    <td><textarea style="margin: 0px; height: 56px; width: 405px;" disabled><?=$plug_Settings['msg_text'];?></textarea>
		                        <p class="description">Ex. Thank you for your order on Ethnicyug, please confirm your order <a href="https://goo.gl/1JGh4Q" target="_blank">https://goo.gl/1JGh4Q</a>.</p>
		                    </td>
		                </tr>
		            </tbody>
		        </table>
		        <h2 class="sub-heading">Order page button settings 
		        	<div class="tooltip">
		    			<i class="fa fa-question-circle"></i> 
		    			<span class="tooltiptext">Settings for order confirmation page which will send link to customer in sms.</span>
	    			</div>
		        </h2>
		        <table class="form-table white-back p-15">
	            	<tbody>
		                <tr>
		                    <th class="label">Request a Call Button</th>
		                    <td><input name="request_btn" type="checkbox" id="request_btn" value="1" <?=($data['request_btn'] == '1' ? 'checked="checked"':'');?> class='sms_check' opn='.order_contact'> Show 'Request a Call' button, which means you (admin) need to contact customer.</td>
		                </tr>
		                <tr style="<?=($data['request_btn'] == '1' ? '':'display: none;');?>" class='order_contact'>
		                    <th class="label">Custom field for Order Contact</th>
		                    <td><div class="tooltip" style="margin-right: 10px;">
					    			<i class="fa fa-question-circle"></i> 
					    			<span class="tooltiptext">Custom field value for admin need to contact customer.</span>
				    			</div>
		                    	<input class="regular-text" type="text" name="order_contact" value="<?=$data['order_contact'];?>">
		                    </td>
		                </tr>
		            </tbody>
		        </table>
				<table class="form-table white-back p-15">
	            	<tbody>
	            		<tr>
		                    <th class="label">Customer Call Button</th>
		                    <td><input name="call_btn" type="checkbox" id="call_btn" value="1" <?=($data['call_btn'] == '1' ? 'checked="checked"':'');?> class='sms_check' opn='.call_btn'> Want to show customer call to admin Button?</td>
		                </tr>
		                <tr style="<?=($data['call_btn'] == '1' ? '':'display: none;');?>" class='call_btn'>
		                    <th class="label">Store Owner Contact number </th>
		                    <td>
		                    	<div class="tooltip" style="margin-right: 10px;">
					    			<i class="fa fa-question-circle"></i> 
					    			<span class="tooltiptext">Store owner contact number for customer service.</span>
				    			</div>
				    			<input class="regular-text" type="number" name="owner_phno" value="<?=$data['owner_phno'];?>">
		                    </td>
		                </tr>
		        	</tbody>
	        	</table>
	        	<table class="form-table white-back p-15">
	            	<tbody>
		                <tr>
		                    <th class="label">Cancel Order Button</th>
		                    <td><input name="cancel_btn" type="checkbox" id="cancel_btn" value="1" <?=($data['cancel_btn'] == '1' ? 'checked="checked"':'');?> class='sms_check' opn='.cancel_btn'> Want to show cancel order button?</td>
		                </tr>
		        	</tbody>
	        	</table>
	        </div>
	        	    	
	    	<div id="voice_tbl" class="m_t_15" style="<?=($data['app_mode'] == '1' ? '':'display:none;');?>">
	    		<h2 class="heading"> <i class="fa fa-envelope"></i> Voice Settings 
	    			<div class="tooltip">
		    			<i class="fa fa-question-circle"></i> 
		    			<span class="tooltiptext">Voice call template for IVR.</span>
	    			</div>
	    		</h2>
       			<table class="form-table white-back p-15">
		            <tbody>
		                <tr>
		                    <th class="label">Voice Call Template (Text to say)</th>
		                    <td><textarea name="IVR_call_message" maxlength="300" rows="5" style="margin: 0px; width: 405px;" class="key_change_value"><?=(isset($data['IVR_call_message']) && $data['IVR_call_message'] != '' ? $data['IVR_call_message'] :  $plug_Settings['IVR_call_message']);?></textarea>
		                        <p class="description">Customise the IVR message (Max 300 character allowed):</p>
		                        <li>{Confirmation_Digit} - 1</li>
		                        <li>{Cancellation_Digit} - 2</li>
		                    </td>
		                </tr>
		                <tr>
		        			<th class="label">Say When Press 1</th>
		        			<td>
		        				<div class="tooltip" style="margin-right: 10px;">
					    			<i class="fa fa-question-circle"></i> 
					    			<span class="tooltiptext">What should play when order get confirmed (Max 60 character allowed).</span>
				    			</div>
				    			<input class="regular-text key_change_value"  maxlength="60" type="text" name="IVR_confirm_message" value="<?=(isset($data['IVR_confirm_message']) && $data['IVR_confirm_message'] != '' ? $data['IVR_confirm_message'] :  $plug_Settings['IVR_confirm_message']);?>">
		                    </td>
		        		</tr>
		        		<tr></tr>
		        		<tr>
		        			<th class="label">Say When Press 2</th>
		        			<td>
		        				<div class="tooltip" style="margin-right: 10px;">
					    			<i class="fa fa-question-circle"></i> 
					    			<span class="tooltiptext">What should play when order got cancelled (Max 60 character allowed).</span>
				    			</div>
				    			<input class="regular-text key_change_value" type="text"  maxlength="60" name="IVR_cancel_message" value="<?=(isset($data['IVR_cancel_message']) && $data['IVR_cancel_message'] != '' ? $data['IVR_cancel_message'] :  $plug_Settings['IVR_cancel_message']);?>">
		                    </td>
		        		</tr>
		            </tbody>
		        </table>
	        </div>
	        <input type="submit" name="btnSave" value="Save Settings" class="btnSave button button-primary last-btn" />
	    </div>   
    </form>
</div>
<form action="<?php echo $PAYU_BASE_URL; ?>" method="post" name="payuForm" id="checkout_form">
    <input type="hidden" name="key" value="<?php echo $MERCHANT_KEY ?>" />
    <input type="hidden" name="salt" value="<?php echo $SALT ?>" />
    <input type="hidden" name="hash" value=""/>
    <input type="hidden" name="txnid" value="<?php echo $txnid ?>" />
    <input type="hidden" name="amount" value="" />
    <input type="hidden" name="firstname" id="firstname" value="<?=$user_name;?>" />
    
    <input type="hidden" name="email" id="email" value="<?=$user_email;?>" />
    <input type="hidden" name="phone" value="9999999999" />
   	<input type="hidden" name="productinfo" value='COD plugin recharge'>
    <input type="hidden" name="surl" value="<?=$actual_link;?>" />
    <input type="hidden" name="furl" value="<?=$actual_link;?>" />
    <input type="hidden" name="service_provider" value="payu_paisa" size="64" />
    <input type="hidden" name="udf1" value="<?=COD_STORE_URL;?>" />
    <input type="hidden" name="udf5" value="BOLT_KIT_PHP7" />
</form>
<div id="recharge_now_popup" class="spmodal">
  <!-- spModal content -->
  	<div class="spmodal-content">
  		<form action="https://shopiapps.in/wpcod_plg/payment.php" method="post" id="payment-form" target="_blank">
	  		<div class="spmodal-header">
	    		<span class="close">&times;</span>
	    		<h2>Recharge Balance for confirm COD orders</h2>
	  		</div>
	  		<div class="spmodal-body">
	  			<p class="error"></p>
			    <table id="rechare_table">
			    	<tbody>
			            <tr>
			                <th class="label">Phone number</th>
			                <td>+91-<input type="number" name="phone_number" class="regular-text" placeholder="9099999999"></td>
			            </tr>
			            <tr>
			            	<th class="label">Amount</th>
			            	<td><select class="paypal_charge m_l_26" name="amount">
						            <option value="">Select Value</option>
						            <option value="200">₹200</option>
						            <option value="500">₹500</option>
						            <option value="1000">₹1000</option>
						            <option value="2000">₹2000</option>
						            <option value="5000">₹5000</option>
						            <option value="10000">₹10000</option>
						            <option value="30000">₹30000</option>
						        </select>
						    </td>
			            </tr>
			        </tbody>
			    </table>
			    <input type="hidden" name="email" id="email" value="<?=$user_email;?>" />
			    <input type="hidden" name="udf1" value="<?=COD_STORE_URL;?>" />
			    <input type="hidden" name="firstname" id="firstname" value="<?=$user_name;?>" />
			    <input type="hidden" name="retun_url" value="<?=$actual_link;?>" />
			    <input type="hidden" name="admin_url" value="<?=admin_url();?>" />
	  		</div>
	  		<div class="spmodal-footer">
	        	<button id="pay_now" class="button button-primary">Recharge Now</button>
	  		</div>
	  	</form>	
    </div>
</div>
<script>
    jQuery(document).ready(function ($) {
		// Get the modal
		var modal = document.getElementById('recharge_now_popup');
		// Get the button that opens the modal
		var btn = document.getElementById("recharg_now");
		// Get the <span> element that closes the modal
		var span = document.getElementsByClassName("close")[0];
		// When the user clicks on the button, open the modal 
		btn.onclick = function() {
		    modal.style.display = "block";
		}
		// When the user clicks on <span> (x), close the modal
		span.onclick = function() {
		    modal.style.display = "none";
		}
		// When the user clicks anywhere outside of the modal, close it
		window.onclick = function(event) {
		    if (event.target == modal) {
		        modal.style.display = "none";
		    }
		}
        $('.sms_check').on('change',function(){
            var cls = $(this).attr('opn');
            if($(this).is(':checked')){
                $(cls).show();
            }else{
                $(cls).hide();
            }
        });
        
        $("#payment-form").submit(function(event) {
		        event.preventDefault();

		        // Reset any previous error messages
		        $(".error").text("");

		        // Get the phone number and amount fields
		        var phoneNumberInput = $('input[name="phone_number"]');
		        var amountSelect = $('select[name="amount"]');

		        var phoneNumber = phoneNumberInput.val().trim();
		        var amount = amountSelect.val();
		        if (phoneNumber === ""|| phoneNumber.length != 10) {
		            $('#recharge_now_popup').find(".error").text("Please enter valid phone number!");
		            phoneNumberInput.focus();
		            return false; 
		        }else if (amount === "") {
		            $('#recharge_now_popup').find(".error").text("Select any value for recharge!");
		            amountSelect.focus();
		            return false; 
		        }else{
		        	$('#recharge_now_popup').find('.error').text('');
		        	modal.style.display = "none";
		        	$(this).unbind("submit").submit();
		        	location.reload(true);
		        } 
		});
        $('input[name=app_mode]').on('change',function(){
            if($(this).val() == '2'){
                $('#sms_tbl').show();
                $('#voice_tbl').hide();
            }else{
                $('#sms_tbl').hide();
                $('#voice_tbl').show();
            }
        });
        $('#pause_plug').on('change',function(){
            if($(this).is(':checked')){
                if(!confirm('Do you really want to pause this app?')){
                    $(this).prop('checked',false);
                    return false;
                }
            }
        })
        $(".btnSave").click(function (e) {
            var isValid = true;
            var isval = true;
            var contact_number = $('input[name=owner_phno]').val();
            if($('input[name=call_btn]').is(':checked') && (contact_number == '' || contact_number == '0' || contact_number == undefined || contact_number.length != 10)){
                isval = false;
                alert("please enter store owner number");
                return false;
            }
            if($('input[name=request_btn]').is(':checked') && $('input[name=order_contact]').val() == ''){
                isval = false;
                alert("All values are required!");
                return false;
            }
            if(isval){
                $("table.form-table input").each(function () {
                    if($(this).attr('name') != 'order_contact' && $(this).attr('name') != 'owner_phno'){
                        if ($.trim($(this).val()) == "") {
                            isValid = false;
                            return false;
                        }
                    }
                });
                if (!isValid) {
                    alert("Values in All Text Box are required");
                    e.preventDefault();
                    e.stopPropagation();
                    return false;
                }
            }
        });
        $('.send_req').on('click',function(){
            var sender_id = $('input[name=sender_id]').val();
            var pat = '^[a-zA-Z]{6}$';
            if(!sender_id.match(pat)){
                alert("Enter valid sender id!");
            }else{
                var data = {
                    'action': 'COD_plugin_sender_request',
                    'sender_id': sender_id
                };
                jQuery.post(ajaxurl, data, function(response) {
                    alert(response);
                    setTimeout(function(){
                        window.location.reload();
                    },1000);
                });
            }
            return false;
        })
        $(document).on("keyup",".key_change_value",function(){
            var text = $(this).val();
            var maxlength = $(this).attr("maxlength");
            var charCount = text.length;
            if(maxlength <= charCount) return false;
        });
    });

</script>