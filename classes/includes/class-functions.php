<?php 

if ( !class_exists( 'TGS_Dependencies' ) ) require_once 'class-dependencies.php';

/**
 * WC Detection
 * */
if ( !function_exists( 'tgs_is_woocommerce_activated' ) ) {
	function tgs_is_woocommerce_activated()
	{
		return TGS_Dependencies::woocommerce_active_check();
	}
}

/**
 * JS Detection
 * */
if ( !function_exists( 'tgs_is_jigoshop_activated' ) ) {
	function tgs_is_jigoshop_activated()
	{
		return TGS_Dependencies::jigoshop_active_check();
	}
}

/**
 * EDD Detection
 * */
if ( !function_exists( 'tgs_is_edd_activated' ) ) {
	function tgs_is_edd_activated()
	{
		return TGS_Dependencies::edd_active_check();
	}
}

/* 
*
*  Get User Role 
*/
if (!function_exists('tgs_get_user_role')) { 
	function tgs_get_user_role($user_id) { 
		global $wp_roles;
		$user = new WP_User($user_id);
		$roles = $user->roles;
		$role = array_shift($roles);
		return isset($wp_roles->role_names[$role]) ? translate_user_role($wp_roles->role_names[$role] ) : false;
	}
}
?>
