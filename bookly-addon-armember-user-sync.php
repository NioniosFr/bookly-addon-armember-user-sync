<?php
/**
Plugin Name: Bookly ARMember Customer Sync (Add-on)
Plugin URI: https://www.github.com/nioniosfr/bookly-addon-armember-user-sync
Description: Bookly ARMember Customer sync add-on allows you to sync customers registered or added from ARMember to Bookly.
Version: 0.0.1
Author: Dionysios Fryganas <dfryganas@gmail.com>
Author URI: https://www.github.com/nioniosfr
Text Domain: baarmcs
Domain Path: /languages
License: MIT
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Display a warning in admin sections when the plugin cannot be used.
 *
 * @param array $missing_plugins The list of missing plugins.
 */
function dfr_baarmcs_plugin_required_admin_notice( $missing_plugins ) {
	printf(
		'<div class="error"><h3>Bookly ARMember Customer Sync (Add-on)</h3><p>To install this plugin - <strong>%s</strong> plugin is required.</p></div>',
		esc_html( implode( ', ', $missing_plugins ) )
	);
}

/**
 * Initialization logic of this plugin.
 */
function dfr_baarmcs_init() {
	$missing_plugins = array();
	if ( ! is_plugin_active( 'bookly-responsive-appointment-booking-tool/main.php' ) ) {

		$missing_plugins[] = 'Bookly';
	}
	if ( ! is_plugin_active( 'armember/armember.php' ) ) {

		$missing_plugins[] = 'ARMember';
	}

	if ( ! empty( $missing_plugins ) ) {
		add_action(
			is_network_admin() ? 'network_admin_notices' : 'admin_notices',
			function () use ( $missing_plugins ) {
				dfr_baarmcs_plugin_required_admin_notice( $missing_plugins );
			}
		);
	}
}

add_action( 'init', 'dfr_baarmcs_init' );

/**
 * User manual registration action from UI, or admin
 * wp_user_id, posted_data[]
 */
add_action( 'arm_after_add_new_user', 'dfr_baarmcs_sync_armember_to_bookly', 10, 2 );

/**
 * User update from admin action
 *
 * $wp_user_id, $member_data[]
 */
add_action( 'arm_after_update_user_profile', 'dfr_baarmcs_sync_armember_to_bookly', 10, 2 );

/**
 * User update from user action
 *
 * $wp_user_id, $member_data[]
 */
add_action( 'arm_member_update_meta', 'dfr_baarmcs_sync_armember_to_bookly_after_update', 100, 3 );

/**
 * Syncs ARMember data to Bookly after user update.
 *
 * @param int   $user_id The user ID.
 * @param array $member_data The member data.
 * @param bool  $is_admin_save Whether the update is performed by an admin.
 */
function dfr_baarmcs_sync_armember_to_bookly_after_update( $user_id, $member_data, $is_admin_save ) {
	if ( $is_admin_save ) {
		// we handle this on our own.
		return;
	}
	dfr_baarmcs_sync_armember_to_bookly( $user_id, $member_data );
}

/**
 * Syncs ARMember data to Bookly after user update.
 *
 * @param int   $user_id The user ID.
 * @param array $member_data The member data.
 */
function dfr_baarmcs_sync_armember_to_bookly( $user_id, $member_data ) {
	// Get user data.
	$user      = get_userdata( $user_id );
	$user_meta = get_user_meta( $user_id );

	if ( ! $user || ! $user_meta ) {
		return;
	}

	// Prepare user data for Bookly.
	$user_data = array(
		'wp_user_id' => $user_id,
		'full_name'  => $user->display_name,
		'first_name' => $user->first_name,
		'last_name'  => $user->last_name,
		'email'      => $user->user_email,
		'phone'      => get_user_meta( $user_id, 'text_phone', true ),
		// 'avatar'     => get_user_meta( $user_id, 'avatar', true ),
	);

	// Add or update user in Bookly.
	dfr_baarmcs_add_or_update_bookly_customer( $user_id, $user_data );
}

/**
 * Add or update a Bookly customer.
 *
 * @param int   $customer_id The customer ID.
 * @param array $user_data   The user data.
 */
function dfr_baarmcs_add_or_update_bookly_customer( $customer_id, $user_data ) {
	$customer = new \Bookly\Lib\Entities\Customer();
	$customer->loadBy( array( 'wp_user_id' => $customer_id ) );

	if ( null !== $customer && $customer->isLoaded() ) {
		if ( $customer->getEmail() !== $user_data['email'] ) {
			return;
		}
	}
	$customer->setFields( $user_data );
	$customer->save();
}

/**
 * After a WordPress user is deleted, delete the corresponding Bookly customer.
 */
add_action( 'deleted_user', 'dfr_baarmcs_delete_bookly_customer', 10, 2 );

/**
 * Delete the corresponding Bookly customer after a WordPress user is deleted.
 *
 * @param int  $user_id The user ID.
 * @param bool $reassign Whether to reassign the user's posts.
 */
function dfr_baarmcs_delete_bookly_customer( $user_id, $reassign = 1 ) {
	$customer = new \Bookly\Lib\Entities\Customer();
	$customer->loadBy( array( 'wp_user_id' => $user_id ) );

	if ( null !== $customer && $customer->isLoaded() ) {
		// Customer Appointments and giftcards are deleted via SQL foreign key constraints.
		$customer->delete();
	}
}
