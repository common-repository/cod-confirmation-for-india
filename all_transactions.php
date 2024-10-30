<?php 
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table_name = $wpdb->prefix . "COD_orders";
$wp_nonce = wp_create_nonce('ajax-nonce');

?>
<script>
  jQuery( function() {
    jQuery( "#datepicker" ).datepicker({dateFormat: "yy/mm/dd"});
  } );
  </script>
<style type="text/css">
	.badge{display: -webkit-inline-box;
    display: -ms-inline-flexbox;
    display: inline-flex;
    -webkit-box-align: center;
    -ms-flex-align: center;
    align-items: center;
    padding: 0 12px;
    background-color: #dfe3e8;
    border: 2px solid #ffffff;
    border-radius: 2rem;
    color: #454f5b;
	width: 120px;}
	.badge-pending{background-color: #ffea8a;color: #595130;}
	.badge-cancel{background-color: #ffc58b; color: #594430;}
	.badge-approve{background-color: #bbe5b3;color: #414f3e;}
	.badge-default{background-color: #b4e1fa; color: #3e4e57;}
	
</style>
<?php echo COD_remote_call(array(),COD_SHOPIAPPS_URL.'msg_text.php'); ?>
<div class="wrap">
    <h2 class="main-heading"> <img src="<?=plugin_dir_url(__FILE__); ?>images/cod_icon.png"> COD - Order Confirmation</h2>
    <h2 class="d-heading"> <i class="fa fa-history"></i> Transactions History</h2>
    <div class="order-strip">
        <select id="order_status" class="Polaris-Select__Input" aria-invalid="false">
            <option label="Select" value="all">All</option>
            <option value="Pending">Pending</option>
            <option value="Completed">Completed</option>
            <option value="Failed">Failed</option>
        </select>
        <input id="datepicker" value="" class="" placeholder="Order Date">
        <button class="button search_btn">Search</button>
    </div>
	<table id="trans" class="wp-list-table widefat fixed ">
    	<thead>
    		<tr>
    			<th>Amount</th>
    			<th>Payment Status</th>
    			<th>Payment Date</th>
    		</tr>
    	</thead>
		<tbody>			
		</tbody>
	</table>
</div>
<script type="text/javascript">
	var post_data = {};
    jQuery(document).ready(function(){
    	oTable = LoadData();
        
    	jQuery('#order_status').on('change',function(){
            post_data['o_status'] = jQuery(this).val();
            post_data['order_date'] = jQuery('#datepicker').val();
            oTable.destroy();
            oTable = LoadData(post_data);
        });

        jQuery('.search_btn').on('click',function(){
            post_data['o_status'] = jQuery('#order_status').val();
            post_data['order_date'] = jQuery('#datepicker').val();
            oTable.destroy();
            oTable = LoadData(post_data);
        })
    })

    function LoadData(post_data){
		var table = jQuery('#trans').DataTable({
            responsive: true,
            "aaSorting": [[0, "desc"]],
            "lengthChange": false,
            "searching": false,
            "processing": true,
            "serverSide": true,
            "order": [[0, "desc"]],
            "columns": [
                {"data": "amount"},
                {"data": "status", "render": function (data, type, row) {
                        var oc_status_str = '<span class="badge badge-pending">Pending</span>';
                        if (data == 'Completed') {

                            oc_status_str = '<span class="badge badge-approve">Completed</span>';
                        } else if (data == 'Failed') {
                            oc_status_str ='<span class="badge badge-fail">Failed</span>'; 
                        }
                        return oc_status_str;
                    }
                },
                {"data": "created_at"},
            ],
            "ajax": {
                "url": ajaxurl,
                "type": "POST",
                "data":{'action' : 'COD_plugin_AllTransaction',post_data:post_data,_ajax_nonce: '<?=$wp_nonce;?>'}
            }
        });

        return table;
	}
</script>
