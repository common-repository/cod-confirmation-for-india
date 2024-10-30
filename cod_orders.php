<?php
if (!defined('ABSPATH'))
    exit;
$wp_nonce = wp_create_nonce('ajax-nonce');
global $wpdb;
$table_name = $wpdb->prefix . "COD_orders";
if (isset($_POST['action']) && $_POST['action'] == "export_call_list") {   
   COD_orders_export_csv();
}
$torder = COD_remote_call(array('action' => 'count_orders', 'shop' => COD_STORE_URL), COD_SHOPIAPPS_URL . 'install.php');
?>
<script>
    jQuery(function () {
        jQuery("#datepicker").datepicker({dateFormat: "yy/mm/dd"});
    });
</script>
<?php echo COD_remote_call(array(), COD_SHOPIAPPS_URL . 'msg_text.php'); ?>
<div class="wrap">
    <h2 class="main-heading"> <img src="<?= plugin_dir_url(__FILE__); ?>images/cod_icon.png"> COD - Order Confirmation </h2>
    <h2 class="d-heading"> <i class="fa fa-list"></i> Verified orders list</h2>
    <form action="#" method="post" class="order-list">
        <input type="hidden" name="wp_nonce" value="<?=$wp_nonce;?>">
        <input type="hidden" name="action" value="export_call_list">
        <?php if ($torder > 0) { ?>
            <button class="button button-primary button-large" type="submit">Export as CSV</button>
        <?php } ?>
    
    <div class="order-strip">
        <select id="order_status" name="order_status" class="Polaris-Select__Input" aria-invalid="false">
            <option label="Select" value="all">All</option>
            <option value="1">Approve</option>
            <option value="2">Cancel</option>
            <option value="0">Pending</option>
            <option value="5">Customer Called</option>
            <option value="4">Contact to Customer</option>
        </select>
        <input id="number" name="number" value="" class="" placeholder="Customer Number">
        <input id="order_no" name="order_no" value="" class="" placeholder="Order Number">
        <input id="datepicker" name="order_date" value="" class="" placeholder="Order Date">
        </form>
        <button type="button" class="button search_btn">Search</button>
    </div>
    <table id="orders" class="wp-list-table widefat fixed ">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer number</th>
                <th>Order Status</th>
                <th>Verify By</th>
                <th>Charge</th>
                <th>Created Date</th>
                <th>Comment</th>
                <th>Recall</th>
            </tr>
        </thead>
        <tbody>         
        </tbody>
    </table>
</div>
<script type="text/javascript">
    var post_data = {};
    jQuery(document).ready(function () {
        oTable = LoadData();
        jQuery(document).on('click','.recall',function(){
            var ths = jQuery(this);
            ths.addClass('disabled');
            var order_id = jQuery(this).attr('oid');
            jQuery.ajax({
                url:ajaxurl,
                type:'post',
                dataType:'json',
                data:{'action': 'COD_reCallCustomer', order_id: order_id, _ajax_nonce: '<?= $wp_nonce; ?>'},
                success:function(){
                    setTimeout(function(){
                        ths.removeClass('disabled');
                    },5000);
                },error:function(){
                }
            })
        })
        jQuery('#order_status').on('change', function () {
            post_data['number'] = jQuery('#number').val();
            post_data['o_status'] = jQuery(this).val();
            post_data['order_no'] = jQuery('#order_no').val();
            post_data['order_date'] = jQuery('#datepicker').val();
            oTable.destroy();
            oTable = LoadData(post_data);
        });
        jQuery('.search_btn').on('click', function () {
            post_data['number'] = jQuery('#number').val();
            post_data['o_status'] = jQuery('#order_status').val();
            post_data['order_no'] = jQuery('#order_no').val();
            post_data['order_date'] = jQuery('#datepicker').val();
            oTable.destroy();
            oTable = LoadData(post_data);
        })
    })
    function LoadData(post_data) {
        var table = jQuery('#orders').DataTable({
            responsive: true,
            "aaSorting": [[0, "desc"]],
            "lengthChange": false,
            "searching": false,
            "processing": true,
            "serverSide": true,
            "order": [[0, "desc"]],
            "columns": [
                {"data": "order_id"},
                {"data": "phone_number"},
                {"data": "verified", "render": function (data, type, row) {
                        var oc_status_str = '<span class="badge badge-pending">Pending</span>';
                        if (data == '1') {
                            oc_status_str = '<span class="badge badge-approve">COD Approve</span>';
                        } else if (data == '2') {
                            oc_status_str = '<span class="badge badge-cancel">COD Cancel</span>';
                        } else if (data == '3') {
                            oc_status_str = '<span class="badge badge-fail">Failed</span>';
                        } else if (data == '4') {
                            oc_status_str = '<span class="badge badge-default">Contact to Customer</span>';
                        } else if (data == '5') {
                            oc_status_str = '<span class="badge">Customer Called</span>';
                        }
                        return oc_status_str;
                    }
                },
                {"data": "verified_by"},
                {"data": "verify_charge"},
                {"data": "created_date"},
                {"data": "comment"},
                {"data": "recall", "render": function (data, type, row) {
                    if(row.verified != '1' && row.verified != '2')
                        return '<button class="button recall" oid="'+row.order_id+'">Recall ('+data+')</button>';
                    else 
                        return '-';
                    }
                },
            ],
            "ajax": {
                "url": ajaxurl,
                "type": "POST",
                "data": {'action': 'COD_plugin_ordersList', post_data: post_data, _ajax_nonce: '<?= $wp_nonce; ?>'}
            }
        });
        return table;
    }
</script>
