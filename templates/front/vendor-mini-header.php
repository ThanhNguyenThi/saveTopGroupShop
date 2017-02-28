<?php
/**
 *  Vendor Mini Header - Hooked into single-product page
 *
 *  THIS FILE WILL LOAD ON VENDORS INDIVIDUAL PRODUCT URLs (such as yourdomain.com/shop/product-name/)
 *
 * @author Thanh Nguyen
 * @package groupshops
 * @version 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly 

/*
*	Template Variables available 
*   $vendor : 			For pulling additional user details from vendor account.  This is an array.
*   $vendor_id  : 		current vendor user id number
*   $shop_name : 		Store/Shop Name (From Vendor Dashboard Shop Settings)
*   $shop_description : Shop Description (completely sanitized) (From Vendor Dashboard Shop Settings)
*   $seller_info : 		Seller Info(From Vendor Dashboard Shop Settings)
*	$vendor_email :		Vendors email address
*	$vendor_login : 	Vendors user_login name
*/ 

?>

<h1><?php echo $shop_name; ?></h1>
<div class="tgs_shop_description">
<?php echo $shop_description; ?>
</div>
