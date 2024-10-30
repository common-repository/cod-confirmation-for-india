<?php
if (!defined('ABSPATH'))
    exit; // Exit if accessed directly

global $wpdb;
$order_tb = $wpdb->prefix . "COD_orders";
$table_name = $wpdb->prefix . "COD_settings";

if (isset($_GET['wpse9870_api']) && (!isset($_POST['type']))) {
    $order_id = filter_input(INPUT_GET, 'wpse9870_api', FILTER_SANITIZE_NUMBER_INT);

    $params = array(
        "order_id" => $order_id,
        "shop" => COD_STORE_URL,
        "pg" => 'is_verified'
    );
    $url = COD_SHOPIAPPS_URL . 'confirm_sms.php';
    $is_verified = json_decode(COD_remote_call($params, $url), true);

    $confirmed = false;
    if ($is_verified['verified'] == '0') {
        $order = new WC_Order($order_id);
        // $order = wc_get_order($order_id);

        $order_arr = COD_order_data($order);
        $shop_name = get_bloginfo('name');
        $id = 1;
        $sql = $wpdb->prepare("SELECT owner_phno,request_btn,call_btn,cancel_btn,order_status_change FROM {$table_name} WHERE id = %d", $id);
        $wpdb->query($sql);
        if($wpdb->last_error !== '') :
            $wpdb->query("ALTER TABLE `{$table_name}` ADD `order_status_change` ENUM('1','0') NOT NULL DEFAULT '1' AFTER `pause_plug`");
        endif;
        $setting = $wpdb->get_results($sql, ARRAY_A);
        $owner_phno = $setting[0]['owner_phno'];
        $request_btn = $setting[0]['request_btn'];
        $call_btn = $setting[0]['call_btn'];
        $cancel_btn = $setting[0]['cancel_btn'];
        $order_status_change = $setting[0]['order_status_change'];
    } else {
        $confirmed = true;
    }
}


if (isset($_POST['type']) && $_POST['type'] == 'order_confirm') {

    $flag = true;
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_NUMBER_INT);
    $val = filter_input(INPUT_POST, 'val', FILTER_SANITIZE_NUMBER_INT);

    $order = new WC_Order($order_id);
    $id = 1;
    $sql = $wpdb->prepare("SELECT order_confirm,order_cancel,order_pending,pause_plug,order_cancel_mode,order_contact,owner_phno,order_status_change FROM {$table_name} WHERE id = %d", $id);
    $setting = $wpdb->get_results($sql, ARRAY_A);
    $data = $setting[0];

    if ($val == '1') { // confirm order
        add_post_meta($order_id, 'Order Confirm', $data['order_confirm']);
        if($data['order_status_change'] == '1') $order->update_status('wc-processing', 'Order confirm by COD plugin');
    } elseif ($val == '2') { // cancel order
        add_post_meta($order_id, 'Order Cancelled', $data['order_cancel']);
        if ($data['order_cancel_mode'] == '1')
            $order->update_status('wc-cancelled', 'Order Cancelled by COD plugin');
    }elseif($verified == '3'){
        add_post_meta( $order_id, 'Order Failed','Confirmation failed by COD');
        if($data['order_status_change'] == '1') $order->update_status('wc-failed', 'Order confirmation failed by COD plugin');
    }elseif ($val == '4') { // request call
        add_post_meta($order_id, 'Contact to Customer', $data['order_contact']);
        if($data['order_status_change'] == '1') $order->update_status('wc-processing', 'Contact to Customer');
    } elseif ($val == '5') { // customer called
        add_post_meta($order_id, 'Customer called', 'Customer has contacted to you');
        if($data['order_status_change'] == '1') $order->update_status('wc-processing', 'Order Cancelled by COD plugin');
    }

    $params = array();
    $params['order_id'] = $order_id;
    $params['shop'] = COD_STORE_URL;
    $params['varified'] = $val;
    $params['pg'] = 'confirm';
    $url = COD_SHOPIAPPS_URL . 'confirm_sms.php';
    COD_remote_call($params, $url);
    $resp = array();
    $resp['flag'] = $flag;
    $resp['val'] = $val;
    $resp['error'] = 'Something went wrong please try again!';
    echo json_encode($resp);
    exit;
}

if (empty($order_arr) && (!$confirmed)) {
    echo "Something went wrong!";
    exit;
}
?>

<!DOCTYPE html>
<html>
    <head>
        <title></title>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
        <link rel="stylesheet" href="https://sdks.shopifycdn.com/polaris/latest/polaris.css" />
        <style type="text/css">
            body{
                font-size: 1.2rem;
                line-height: 1.8rem;
            }
            .table--divided{width: 100%;} 
            .t-center{text-align: center;}
            .t-right{text-align: right;}
            .price_qty span {display: inline-block;padding: 0 5px;}

            .Polaris-DescriptionList__Description {
                -webkit-box-flex: 1;
                -ms-flex: 1 1 51%;
                flex: 1 1 51%;
                padding: 1.2rem 0;
                text-align: right;
            }
            .Polaris-Heading {
                font-size: 1.5rem;
                font-weight: 600;
                line-height: 1rem;
                margin: 0;
            }
            .Polaris-Card__Header {
                padding: 1.5rem 2rem 0; 
            }

            .orders-line-item__image {width: 45px;float: left;margin-right: 5px;}
            .btn-grp{padding: 10px 0;width: 100%;}
            .btn-grp .text{
                display: inline-block;
                width: 50%;
            }
            .btn-grp .btn{display: inline-block;}
            .Polaris-Page__Header--hasSecondaryActions {
                padding-top: 1.4rem;
            }
            .Polaris-Card__Section {
                padding: 1.5rem 2rem;
            }
            .d_lbl{ 
                font-weight: bold;
                display: inline-block;
                width: 20%;
                vertical-align: top;
            }
            .add_text{
                width: 80%;
                display: inline-block;
            }

            @media screen and (min-width: 1024px){
                .btn-grp-right{
                    width: 50%;
                    display: inline-block;
                    float: right;
                    text-align: left;
                    padding-left: 20px;
                }
                .btn-grp-left{
                    display: inline-block;
                    width: 50%;
                    float: left;
                    text-align: right;
                }
            }

            @media screen and (max-width: 720px){
                td.cust-td {display: block;width: 100%;}
                tr.orders-line-item {border-bottom: 1px solid #ccc;display: inline-block;padding: 10px 0; width: 100%;}
                .orders-line-item__image {width: 45px;float: left;margin-right: 5px;}
                td.cust-td.t-right{text-align: right;}
                .price_qty span.orders-line-item__price {padding-left: 0;}
                .Polaris-DisplayText--sizeLarge {font-size: 1.5rem; font-weight: 600;line-height: 2rem;}   
                .o_name{font-size: 1.3rem;}
                .Polaris-Layout__Section, .Polaris-Layout__AnnotatedSection{margin-top: 1.5rem;}
                .Polaris-DescriptionList__Term{width: 50%;display: inline-block;float: left;padding: 1.2rem 0;}
                .Polaris-DescriptionList__Description{width: 50%;display: inline-block;float: left;}
                p{color: #637381;}
                .btn{display: inline-block;}
                .text{width: 65%;display: inline-block;}
                .btn-grp{padding: 10px 0;text-align: center;}
                td.price_qty{width: 50%;float: left;}
                .Polaris-Button__Content{font-size: 1.2rem;}
                .Polaris-Button--outline {width: 107px;}
            }

            @media screen and (max-width: 450px){
                .Polaris-Page{padding: 0 1.2rem;}
                .Polaris-Page__Content {
                    margin: 1rem 0;
                }
                .d_lbl {
                    font-weight: bold;
                    display: inline-block;
                    width: 30%;
                    vertical-align: top;
                }
                .add_text{
                    width: 70%;
                    display: inline-block;
                }
                .orders-line-item__description{ padding-bottom: 8px;}
            }
            .p_t_0{padding-top: 0px;}
            .m_l_10{margin-left: 10px;}
            .m_r_10{margin-right: 10px;}
        </style>
    </head>
    <body>
<?php
if (isset($confirmed) && $confirmed == true) {
    echo "<h1 style='font-size: 35px;text-align: center;display:block;line-height: 2;'>";
    if ($is_verified['verified'] == '1') {
        echo "Thank you, Your order has been confirmed!";
    } elseif ($is_verified['verified'] == '2') {
        echo "Thank you, Your order has been cancelled!";
    } elseif ($is_verified['verified'] == '4') {
        echo "Thank you, Store owner will contact you soon!";
    } else {
        echo "Thank you";
    }
    echo "</h1>";
    exit;
} else {
    ?>

            <div class="Polaris-Page">
                <div class="Polaris-Page__Header Polaris-Page__Header--hasPagination Polaris-Page__Header--hasBreadcrumbs Polaris-Page__Header--hasRollup Polaris-Page__Header--hasSecondaryActions">

                    <div class="Polaris-Page__MainContent">
                        <div class="Polaris-Page__TitleAndActions">
                            <div class="Polaris-Page__Title">
                                <h1 class="Polaris-DisplayText Polaris-DisplayText--sizeLarge t-center"><?= $shop_name; ?></h1>
                                <h1 class="Polaris-DisplayText Polaris-DisplayText--sizeLarge o_name">Order #<?= $order_arr['order_number']; ?></h1>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="Polaris-Page__Content">
                    <div class="Polaris-Layout">
                        <div class="Polaris-Layout__Section first_div">
                            <div class="Polaris-Card">
                                <div class="Polaris-Card__Header">

                                    <h2 class="Polaris-Heading">Product details</h2>
                                </div>
                                <div class="Polaris-Card__Section">
                                    <div class="next-card__section next-card__section--no-vertical-spacing">
                                        <table class="table--no-side-padding table--divided">
                                            <tbody>
                                                <?php foreach ($order_arr['line_items'] as $key => $item) { ?>
                                                    <tr class="orders-line-item">
                                                        <td class="orders-line-item__image hide-when-printing">
                                                            <div class="aspect-ratio aspect-ratio--square aspect-ratio--square--50">
                                                                <img title="Saree" class="block aspect-ratio__content" src="<?= $item['image']; ?>" alt="Saree" width="100%">
                                                            </div>
                                                        </td>
                                                        <td class="orders-line-item__description cust-td">
                                                            <a href="javascript:void(0)"><?= $item['name']; ?></a>
                                                        </td>
                                                        <td class="price_qty cust-td">
                                                            <span class="orders-line-item__price"><?= get_woocommerce_currency_symbol() . $item['price']; ?></span>
                                                            <span>x</span>
                                                            <span class="orders-line-item__quantity"><?= $item['quantity']; ?></span>
                                                        </td>
                                                        <td class="t-right orders-line-item__total cust-td"><?= get_woocommerce_currency_symbol() . $item['subtotal']; ?></td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                        <dl class="Polaris-DescriptionList">
                                            <dt class="Polaris-DescriptionList__Term">Payable amount :</dt>
                                            <dd class="Polaris-DescriptionList__Description"><?= get_woocommerce_currency_symbol() . $order_arr['total']; ?> <span style="color: #637381">(Inc.Tax)</span></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="Polaris-Layout__Section second_div">
                            <div class="Polaris-Card">
                                <div class="Polaris-Card__Header">
                                    <h2 class="Polaris-Heading">Shipping Address</h2>
                                </div>
                                <div class="Polaris-Card__Section">
                                    <p class="type--subdued">
                                        <span class="d_lbl">Name:</span><span class="add_text"><?= $order_arr['shipping_address']['first_name'] . ' ' . $order_arr['shipping_address']['last_name']; ?></span>
                                    </p>
                                    <p class="type--subdued">
                                        <span class="d_lbl">Address : </span><span class="add_text"><?= $order_arr['shipping_address']['address_1']; ?></span></p>
                                    <p class="type--subdued">
                                        <span class="d_lbl">State : </span><span class="add_text"><?= $order_arr['shipping_address']['state']; ?></span></p>
                                    <p class="type--subdued">
                                        <span class="d_lbl">Zip : </span><span class="add_text"><?= $order_arr['shipping_address']['postcode']; ?></span></p>
                                    <p class="type--subdued">
                                        <span class="d_lbl">Phone : </span><span class="add_text"><?= $order_arr['billing_address']['phone']; ?></span></p>

                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="btn-grp btn-grp-left">

                    <div class="btn m_r_10">
                        <button type="button" class="Polaris-Button Polaris-Button--primary Polaris-Button--sizeSlim order_confirm" oid="<?= $order_id; ?>"  id='1'><span class="Polaris-Button__Content"><span>Confirm Order</span></span></button>
                    </div>
                    <?php if ($cancel_btn == '1') { ?>
                    <div class="btn m_l_10">
                        <button type="button" class="Polaris-Button Polaris-Button--destructive Polaris-Button--sizeSlim order_confirm" oid="<?= $order_id; ?>" id='2'><span class="Polaris-Button__Content"><span>Cancel Order</span></span></button>
                    </div>
                    <?php } ?>
                </div>
                <div class="btn-grp btn-grp-right">
                    <?php if ($request_btn == '1') { ?>
                        <div class="btn m_r_10">
                            <button type="button" class="Polaris-Button Polaris-Button--outline Polaris-Button--sizeSlim order_confirm" oid="<?= $order_id; ?>"  id='4'><span class="Polaris-Button__Content"><span>Request a Call</span></span></button>
                        </div>
                    <?php } ?>
                    <?php if ($call_btn == '1') { ?>
                        <div class="btn m_l_10">
                            <button type="button" class="Polaris-Button Polaris-Button--outline Polaris-Button--sizeSlim order_confirm" oid="<?= $order_id; ?>"  id='5'><span class="Polaris-Button__Content"><span>Call Now</span></span></button>
                        </div>
                    <?php } ?>

                </div>
            <?php } ?>
    </body>

    <script type="text/javascript">
        var oid = '<?= $order_id; ?>';

        $(document).ready(function () {
            $('.order_confirm').on('click', function () {
                var val = $(this).attr('id');
                $.ajax({
                    url: '<?= home_url(); ?>/index.php?wpse9870_api=' + oid,
                    type: "post",
                    dataType: 'json',
                    data: {type: 'order_confirm', val: val, order_id: oid},
                    success: function (data) {
                        if (data.flag) {
                            if (data.val == '1') {
                                document.write('<h1>Thank you, Your order has been confirmed!</h1>');
                            } else if (data.val == '2') {
                                document.write('<h1>Thank you, Your order has been cancelled!</h1>');
                            } else if (data.val == '4') {
                                document.write('<h1>Thank you, Store owner will contact you soon!</h1>');
                            } else if (data.val == '5') {
                                document.write('<h1>Thank you!</h1>');
                                window.location.href = 'tel:<?= $owner_phno; ?>';
                            } else {
                                location.reload();
                            }
                        }
                    }
                })
            })
        });
    </script>
</html> 