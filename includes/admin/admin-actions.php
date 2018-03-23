<?php
/**
 * Admin Actions
 *
 * @package     Give
 * @subpackage  Admin/Actions
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load wp editor by ajax.
 *
 * @since 1.8
 */
function give_load_wp_editor() {
	if ( ! isset( $_POST['wp_editor'] ) ) {
		die();
	}

	$wp_editor                     = json_decode( base64_decode( $_POST['wp_editor'] ), true );
	$wp_editor[2]['textarea_name'] = $_POST['textarea_name'];

	wp_editor( $wp_editor[0], $_POST['wp_editor_id'], $wp_editor[2] );

	die();
}

add_action( 'wp_ajax_give_load_wp_editor', 'give_load_wp_editor' );


/**
 * Redirect admin to clean url give admin pages.
 *
 * @since 1.8
 *
 * @return bool
 */
function give_redirect_to_clean_url_admin_pages() {
	// Give admin pages.
	$give_pages = array(
		'give-payment-history',
		'give-donors',
		'give-reports',
		'give-tools',
	);

	// Get current page.
	$current_page = isset( $_GET['page'] ) ? esc_attr( $_GET['page'] ) : '';

	// Bailout.
	if (
		empty( $current_page )
		|| empty( $_GET['_wp_http_referer'] )
		|| ! in_array( $current_page, $give_pages )
	) {
		return false;
	}

	/**
	 * Verify current page request.
	 *
	 * @since 1.8
	 */
	$redirect = apply_filters( "give_validate_{$current_page}", true );

	if ( $redirect ) {
		// Redirect.
		wp_redirect(
			remove_query_arg(
				array( '_wp_http_referer', '_wpnonce' ),
				wp_unslash( $_SERVER['REQUEST_URI'] )
			)
		);
		exit;
	}
}

add_action( 'admin_init', 'give_redirect_to_clean_url_admin_pages' );


/**
 * Hide Outdated PHP Notice Shortly.
 *
 * This code is used with AJAX call to hide outdated PHP notice for a short period of time
 *
 * @since 1.8.9
 *
 * @return void
 */
function give_hide_outdated_php_notice() {

	if ( ! isset( $_POST['_give_hide_outdated_php_notices_shortly'] ) ) {
		give_die();
	}

	// Transient key name.
	$transient_key = '_give_hide_outdated_php_notices_shortly';

	if ( Give_Cache::get( $transient_key, true ) ) {
		return;
	}

	// Hide notice for 24 hours.
	Give_Cache::set( $transient_key, true, DAY_IN_SECONDS, true );

	give_die();

}

add_action( 'wp_ajax_give_hide_outdated_php_notice', 'give_hide_outdated_php_notice' );

/**
 * Register admin notices.
 *
 * @since 1.8.9
 */
function _give_register_admin_notices() {
	// Bailout.
	if ( ! is_admin() ) {
		return;
	}

	// Bulk action notices.
	if (
		isset( $_GET['action'] ) &&
		! empty( $_GET['action'] )
	) {

		// Add payment bulk notice.
		if (
			current_user_can( 'edit_give_payments' ) &&
			isset( $_GET['payment'] ) &&
			! empty( $_GET['payment'] )
		) {
			$payment_count = isset( $_GET['payment'] ) ? count( $_GET['payment'] ) : 0;

			switch ( $_GET['action'] ) {
				case 'delete':
					Give()->notices->register_notice( array(
						'id'          => 'bulk_action_delete',
						'type'        => 'updated',
						'description' => sprintf(
							_n(
								'Successfully deleted one donation.',
								'Successfully deleted %d donations.',
								$payment_count,
								'give'
							),
							$payment_count ),
						'show'        => true,
					) );

					break;

				case 'resend-receipt':
					Give()->notices->register_notice( array(
						'id'          => 'bulk_action_resend_receipt',
						'type'        => 'updated',
						'description' => sprintf(
							_n(
								'Successfully sent email receipt to one recipient.',
								'Successfully sent email receipts to %d recipients.',
								$payment_count,
								'give'
							),
							$payment_count
						),
						'show'        => true,
					) );
					break;

				case 'set-status-publish' :
				case 'set-status-pending' :
				case 'set-status-processing' :
				case 'set-status-refunded' :
				case 'set-status-revoked' :
				case 'set-status-failed' :
				case 'set-status-cancelled' :
				case 'set-status-abandoned' :
				case 'set-status-preapproval' :
					Give()->notices->register_notice( array(
						'id'          => 'bulk_action_status_change',
						'type'        => 'updated',
						'description' => _n(
							'Donation status updated successfully.',
							'Donation statuses updated successfully.',
							$payment_count,
							'give'
						),
						'show'        => true,
					) );
					break;
			}// End switch().
		}// End if().
	}// End if().

	// Add give message notices.
	if ( ! empty( $_GET['give-message'] ) ) {
		// Donation reports errors.
		if ( current_user_can( 'view_give_reports' ) ) {
			switch ( $_GET['give-message'] ) {
				case 'donation_deleted' :
					Give()->notices->register_notice( array(
						'id'          => 'give-donation-deleted',
						'type'        => 'updated',
						'description' => __( 'The donation has been deleted.', 'give' ),
						'show'        => true,
					) );
					break;
				case 'email_sent' :
					Give()->notices->register_notice( array(
						'id'          => 'give-payment-sent',
						'type'        => 'updated',
						'description' => __( 'The donation receipt has been resent.', 'give' ),
						'show'        => true,
					) );
					break;
				case 'refreshed-reports' :
					Give()->notices->register_notice( array(
						'id'          => 'give-refreshed-reports',
						'type'        => 'updated',
						'description' => __( 'The reports cache has been cleared.', 'give' ),
						'show'        => true,
					) );
					break;
				case 'donation-note-deleted' :
					Give()->notices->register_notice( array(
						'id'          => 'give-donation-note-deleted',
						'type'        => 'updated',
						'description' => __( 'The donation note has been deleted.', 'give' ),
						'show'        => true,
					) );
					break;
			}
		}// End if().

		// Give settings notices and errors.
		if ( current_user_can( 'manage_give_settings' ) ) {
			switch ( $_GET['give-message'] ) {
				case 'settings-imported' :
					Give()->notices->register_notice( array(
						'id'          => 'give-settings-imported',
						'type'        => 'updated',
						'description' => __( 'The settings have been imported.', 'give' ),
						'show'        => true,
					) );
					break;
				case 'api-key-generated' :
					Give()->notices->register_notice( array(
						'id'          => 'give-api-key-generated',
						'type'        => 'updated',
						'description' => __( 'API keys have been generated.', 'give' ),
						'show'        => true,
					) );
					break;
				case 'api-key-exists' :
					Give()->notices->register_notice( array(
						'id'          => 'give-api-key-exists',
						'type'        => 'updated',
						'description' => __( 'The specified user already has API keys.', 'give' ),
						'show'        => true,
					) );
					break;
				case 'api-key-regenerated' :
					Give()->notices->register_notice( array(
						'id'          => 'give-api-key-regenerated',
						'type'        => 'updated',
						'description' => __( 'API keys have been regenerated.', 'give' ),
						'show'        => true,
					) );
					break;
				case 'api-key-revoked' :
					Give()->notices->register_notice( array(
						'id'          => 'give-api-key-revoked',
						'type'        => 'updated',
						'description' => __( 'API keys have been revoked.', 'give' ),
						'show'        => true,
					) );
					break;
				case 'sent-test-email' :
					Give()->notices->register_notice( array(
						'id'          => 'give-sent-test-email',
						'type'        => 'updated',
						'description' => __( 'The test email has been sent.', 'give' ),
						'show'        => true,
					) );
					break;
				case 'matched-success-failure-page':
					Give()->notices->register_notice( array(
						'id'          => 'give-matched-success-failure-page',
						'type'        => 'updated',
						'description' => __( 'You cannot set the success and failed pages to the same page', 'give' ),
						'show'        => true,
					) );
					break;
			}// End switch().
		}// End if().
		// Payments errors.
		if ( current_user_can( 'edit_give_payments' ) ) {
			switch ( $_GET['give-message'] ) {
				case 'note-added' :
					Give()->notices->register_notice( array(
						'id'          => 'give-note-added',
						'type'        => 'updated',
						'description' => __( 'The donation note has been added.', 'give' ),
						'show'        => true,
					) );
					break;
				case 'payment-updated' :
					Give()->notices->register_notice( array(
						'id'          => 'give-payment-updated',
						'type'        => 'updated',
						'description' => __( 'The donation has been updated.', 'give' ),
						'show'        => true,
					) );
					break;
			}
		}

		// Donor Notices.
		if ( current_user_can( 'edit_give_payments' ) ) {
			switch ( $_GET['give-message'] ) {
				case 'donor-deleted' :
					Give()->notices->register_notice( array(
						'id'          => 'give-donor-deleted',
						'type'        => 'updated',
						'description' => __( 'The selected donor(s) has been deleted.', 'give' ),
						'show'        => true,
					) );
					break;

				case 'donor-donations-deleted' :
					Give()->notices->register_notice( array(
						'id'          => 'give-donor-donations-deleted',
						'type'        => 'updated',
						'description' => __( 'The selected donor(s) and its associated donations has been deleted.', 'give' ),
						'show'        => true,
					) );
					break;

				case 'confirm-delete-donor' :
					Give()->notices->register_notice( array(
						'id'          => 'give-confirm-delete-donor',
						'type'        => 'updated',
						'description' => __( 'You must confirm to delete the selected donor(s).', 'give' ),
						'show'        => true,
					) );
					break;

				case 'invalid-donor-id' :
					Give()->notices->register_notice( array(
						'id'          => 'give-invalid-donor-id',
						'type'        => 'updated',
						'description' => __( 'Invalid Donor ID.', 'give' ),
						'show'        => true,
					) );
					break;

				case 'donor-delete-failed' :
					Give()->notices->register_notice( array(
						'id'          => 'give-donor-delete-failed',
						'type'        => 'error',
						'description' => __( 'Unable to delete selected donor(s).', 'give' ),
						'show'        => true,
					) );
					break;

				case 'email-added' :
					Give()->notices->register_notice( array(
						'id'          => 'give-donor-email-added',
						'type'        => 'updated',
						'description' => __( 'Donor email added.', 'give' ),
						'show'        => true,
					) );
					break;

				case 'email-removed' :
					Give()->notices->register_notice( array(
						'id'          => 'give-donor-email-removed',
						'type'        => 'updated',
						'description' => __( 'Donor email removed.', 'give' ),
						'show'        => true,
					) );
					break;

				case 'email-remove-failed' :
					Give()->notices->register_notice( array(
						'id'          => 'give-donor-email-remove-failed',
						'type'        => 'updated',
						'description' => __( 'Failed to remove donor email.', 'give' ),
						'show'        => true,
					) );
					break;

				case 'primary-email-updated' :
					Give()->notices->register_notice( array(
						'id'          => 'give-donor-primary-email-updated',
						'type'        => 'updated',
						'description' => __( 'Primary email updated for donor.', 'give' ),
						'show'        => true,
					) );
					break;

				case 'primary-email-failed' :
					Give()->notices->register_notice( array(
						'id'          => 'give-donor-primary-email-failed',
						'type'        => 'updated',
						'description' => __( 'Failed to set primary email.', 'give' ),
						'show'        => true,
					) );
					break;

				case 'reconnect-user' :
					Give()->notices->register_notice( array(
						'id'          => 'give-donor-reconnect-user',
						'type'        => 'updated',
						'description' => __( 'User has been successfully connected with Donor.', 'give' ),
						'show'        => true,
					) );
					break;

				case 'profile-updated' :
					Give()->notices->register_notice( array(
						'id'          => 'give-donor-profile-updated',
						'type'        => 'updated',
						'description' => __( 'Donor information updated successfully.', 'give' ),
						'show'        => true,
					) );
					break;
			}// End switch().
		}// End if().
	}// End if().
}

add_action( 'admin_notices', '_give_register_admin_notices', - 1 );


/**
 * Display admin bar when active.
 *
 * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance, passed by reference.
 *
 * @return bool
 */
function _give_show_test_mode_notice_in_admin_bar( $wp_admin_bar ) {
	$is_test_mode = ! empty( $_POST['test_mode'] ) ?
		give_is_setting_enabled( $_POST['test_mode'] ) :
		give_is_test_mode();

	if (
		! current_user_can( 'view_give_reports' ) ||
		! $is_test_mode
	) {
		return false;
	}

	// Add the main site admin menu item.
	$wp_admin_bar->add_menu( array(
		'id'     => 'give-test-notice',
		'href'   => admin_url( 'edit.php?post_type=give_forms&page=give-settings&tab=gateways' ),
		'parent' => 'top-secondary',
		'title'  => __( 'Give Test Mode Active', 'give' ),
		'meta'   => array(
			'class' => 'give-test-mode-active',
		),
	) );

	return true;
}

add_action( 'admin_bar_menu', '_give_show_test_mode_notice_in_admin_bar', 1000, 1 );

/**
 * Outputs the Give admin bar CSS.
 */
function _give_test_mode_notice_admin_bar_css() {
	if ( ! give_is_test_mode() ) {
		return;
	}
	?>
	<style>
		#wpadminbar .give-test-mode-active > .ab-item {
			color: #fff;
			background-color: #ffba00;
		}

		#wpadminbar .give-test-mode-active:hover > .ab-item, #wpadminbar .give-test-mode-active:hover > .ab-item {
			background-color: rgba(203, 144, 0, 1) !important;
			color: #fff !important;
		}
	</style>
	<?php
}

add_action( 'admin_head', '_give_test_mode_notice_admin_bar_css' );


/**
 * Add Link to Import page in from donation archive and donation single page
 *
 * @since 1.8.13
 */
function give_import_page_link_callback() {
	?>
	<a href="<?php echo esc_url( give_import_page_url() ); ?>"
	   class="page-import-action page-title-action"><?php _e( 'Import Donations', 'give' ); ?></a>

	<?php
	// Check if view donation single page only.
	if ( ! empty( $_REQUEST['view'] ) && 'view-payment-details' === (string) give_clean( $_REQUEST['view'] ) && 'give-payment-history' === give_clean( $_REQUEST['page'] ) ) {
		?>
		<style type="text/css">
			.wrap #transaction-details-heading {
				display: inline-block;
			}
		</style>
		<?php
	}
}

add_action( 'give_payments_page_top', 'give_import_page_link_callback', 11 );

/**
 * Load donation import ajax callback
 * Fire when importing from CSV start
 *
 * @since  1.8.13
 *
 * @return json $json_data
 */
function give_donation_import_callback() {

	// Disable Give cache
	Give_Cache::get_instance()->disable();

	$import_setting = array();
	$fields         = isset( $_POST['fields'] ) ? $_POST['fields'] : null;

	parse_str( $fields );

	$import_setting['create_user'] = $create_user;
	$import_setting['mode']        = $mode;
	$import_setting['delimiter']   = $delimiter;
	$import_setting['csv']         = $csv;
	$import_setting['delete_csv']  = $delete_csv;

	// Parent key id.
	$main_key = maybe_unserialize( $main_key );

	$current    = absint( $_REQUEST['current'] );
	$total_ajax = absint( $_REQUEST['total_ajax'] );
	$start      = absint( $_REQUEST['start'] );
	$end        = absint( $_REQUEST['end'] );
	$next       = absint( $_REQUEST['next'] );
	$total      = absint( $_REQUEST['total'] );
	$per_page   = absint( $_REQUEST['per_page'] );
	if ( empty( $delimiter ) ) {
		$delimiter = ',';
	}

	// Processing done here.
	$raw_data = give_get_donation_data_from_csv( $csv, $start, $end, $delimiter );
	$raw_key  = maybe_unserialize( $mapto );

	// Prevent normal emails.
	remove_action( 'give_complete_donation', 'give_trigger_donation_receipt', 999 );
	remove_action( 'give_insert_user', 'give_new_user_notification', 10 );
	remove_action( 'give_insert_payment', 'give_payment_save_page_data' );

	$current_key = $start;
	foreach ( $raw_data as $row_data ) {
		$import_setting['donation_key'] = $current_key;
		give_save_import_donation_to_db( $raw_key, $row_data, $main_key, $import_setting );
		$current_key ++;
	}

	// Check if function exists or not.
	if ( function_exists( 'give_payment_save_page_data' ) ) {
		add_action( 'give_insert_payment', 'give_payment_save_page_data' );
	}
	add_action( 'give_insert_user', 'give_new_user_notification', 10, 2 );
	add_action( 'give_complete_donation', 'give_trigger_donation_receipt', 999 );

	if ( $next == false ) {
		$json_data = array(
			'success' => true,
			'message' => __( 'All donation uploaded successfully!', 'give' ),
		);
	} else {
		$index_start = $start;
		$index_end   = $end;
		$last        = false;
		$next        = true;
		if ( $next ) {
			$index_start = $index_start + $per_page;
			$index_end   = $per_page + ( $index_start - 1 );
		}
		if ( $index_end >= $total ) {
			$index_end = $total;
			$last      = true;
		}
		$json_data = array(
			'raw_data' => $raw_data,
			'raw_key'  => $raw_key,
			'next'     => $next,
			'start'    => $index_start,
			'end'      => $index_end,
			'last'     => $last,
		);
	}

	$url              = give_import_page_url( array(
		'step'          => '4',
		'importer-type' => 'import_donations',
		'csv'           => $csv,
		'total'         => $total,
		'delete_csv'    => $import_setting['delete_csv'],
		'success'       => ( isset( $json_data['success'] ) ? $json_data['success'] : '' ),
	) );
	$json_data['url'] = $url;

	$current ++;
	$json_data['current'] = $current;

	$percentage              = ( 100 / ( $total_ajax + 1 ) ) * $current;
	$json_data['percentage'] = $percentage;

	// Enable Give cache
	Give_Cache::get_instance()->enable();

	$json_data = apply_filters( 'give_import_ajax_responces', $json_data, $fields );
	wp_die( json_encode( $json_data ) );
}

add_action( 'wp_ajax_give_donation_import', 'give_donation_import_callback' );

/**
 * Load core settings import ajax callback
 * Fire when importing from JSON start
 *
 * @since  1.8.17
 *
 * @return json $json_data
 */

function give_core_settings_import_callback() {
	$fields = isset( $_POST['fields'] ) ? $_POST['fields'] : null;
	parse_str( $fields, $fields );

	$json_data['success'] = false;

	/**
	 * Filter to Modify fields that are being pass by the ajax before importing of the core setting start.
	 *
	 * @access public
	 *
	 * @since  1.8.17
	 *
	 * @param array $fields
	 *
	 * @return array $fields
	 */
	$fields = (array) apply_filters( 'give_import_core_settings_fields', $fields );

	$file_name = ( ! empty( $fields['file_name'] ) ? give_clean( $fields['file_name'] ) : false );

	if ( ! empty( $file_name ) ) {
		$type = ( ! empty( $fields['type'] ) ? give_clean( $fields['type'] ) : 'merge' );

		// Get the json data from the file and then alter it in array format
		$json_string   = give_get_core_settings_json( $file_name );
		$json_to_array = json_decode( $json_string, true );

		// get the current settign from the options table.
		$host_give_options = get_option( 'give_settings', array() );

		// Save old settins for backup.
		update_option( 'give_settings_old', $host_give_options );

		/**
		 * Filter to Modify Core Settings that are being going to get import in options table as give settings.
		 *
		 * @access public
		 *
		 * @since  1.8.17
		 *
		 * @param array $json_to_array     Setting that are being going to get imported
		 * @param array $type              Type of Import
		 * @param array $host_give_options Setting old setting that used to be in the options table.
		 * @param array $fields            Data that is being send from the ajax
		 *
		 * @return array $json_to_array Setting that are being going to get imported
		 */
		$json_to_array = (array) apply_filters( 'give_import_core_settings_data', $json_to_array, $type, $host_give_options, $fields );

		update_option( 'give_settings', $json_to_array );

		$json_data['success'] = true;
	}

	$json_data['percentage'] = 100;

	/**
	 * Filter to Modify core import setting page url
	 *
	 * @access public
	 *
	 * @since  1.8.17
	 *
	 * @return array $url
	 */
	$json_data['url'] = give_import_page_url( (array) apply_filters( 'give_import_core_settings_success_url', array(
		'step'          => ( empty( $json_data['success'] ) ? '1' : '3' ),
		'importer-type' => 'import_core_setting',
		'success'       => ( empty( $json_data['success'] ) ? '0' : '1' ),
	) ) );

	wp_send_json( $json_data );
}

add_action( 'wp_ajax_give_core_settings_import', 'give_core_settings_import_callback' );

/**
 * Initializes blank slate content if a list table is empty.
 *
 * @since 1.8.13
 */
function give_blank_slate() {
	$blank_slate = new Give_Blank_Slate();
	$blank_slate->init();
}

add_action( 'current_screen', 'give_blank_slate' );

/**
 * Validate Fields of User Profile
 *
 * @param object   $errors Object of WP Errors.
 * @param int|bool $update True or False.
 * @param object   $user   WP User Data.
 *
 * @since 2.0
 *
 * @return mixed
 */
function give_validate_user_profile( $errors, $update, $user ) {

	if ( ! empty( $_POST['action'] ) && ( 'adduser' === $_POST['action'] || 'createuser' === $_POST['action'] ) ) {
		return;
	}

	if ( ! empty( $user->ID ) ) {
		$donor = Give()->donors->get_donor_by( 'user_id', $user->ID );

		if ( $donor ) {
			// If Donor is attached with User, then validate first name.
			if ( empty( $_POST['first_name'] ) ) {
				$errors->add(
					'empty_first_name',
					sprintf(
						'<strong>%1$s:</strong> %2$s',
						__( 'ERROR', 'give' ),
						__( 'Please enter your first name.', 'give' )
					)
				);
			}
		}
	}

}

add_action( 'user_profile_update_errors', 'give_validate_user_profile', 10, 3 );

/**
 * Show Donor Information on User Profile Page.
 *
 * @param object $user User Object.
 *
 * @since 2.0
 */
function give_donor_information_profile_fields( $user ) {
	$donor = Give()->donors->get_donor_by( 'user_id', $user->ID );

	// Display Donor Information, only if donor is attached with User.
	if ( ! empty( $donor->user_id ) ) {
		?>
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row"><?php _e( 'Donor', 'give' ); ?></th>
				<td>
					<a href="<?php echo admin_url( 'edit.php?post_type=give_forms&page=give-donors&view=overview&id=' . $donor->id ); ?>">
						<?php _e( 'View Donor Information', 'give' ); ?>
					</a>
				</td>
			</tr>
			</tbody>
		</table>
		<?php
	}
}

add_action( 'personal_options', 'give_donor_information_profile_fields' );
/**
 * Get Array of WP User Roles.
 *
 * @since 1.8.13
 *
 * @return array
 */
function give_get_user_roles() {
	$user_roles = array();

	// Loop through User Roles.
	foreach ( get_editable_roles() as $role_name => $role_info ) :
		$user_roles[ $role_name ] = $role_info['name'];
	endforeach;

	return $user_roles;
}


/**
 * Ajax handle for donor address.
 *
 * @since 2.0
 *
 * @return string
 */
function __give_ajax_donor_manage_addresses() {
	// Bailout.
	if (
		empty( $_POST['form'] ) ||
		empty( $_POST['donorID'] )
	) {
		wp_send_json_error( array(
			'error' => 1,
		) );
	}

	$post                  = give_clean( wp_parse_args( $_POST ) );
	$donorID               = absint( $post['donorID'] );
	$form_data             = give_clean( wp_parse_args( $post['form'] ) );
	$is_multi_address_type = ( 'billing' === $form_data['address-id'] || false !== strpos( $form_data['address-id'], '_' ) );
	$address_type          = false !== strpos( $form_data['address-id'], '_' ) ?
		array_shift( explode( '_', $form_data['address-id'] ) ) :
		$form_data['address-id'];
	$address_id            = false !== strpos( $form_data['address-id'], '_' ) ?
		array_pop( explode( '_', $form_data['address-id'] ) ) :
		null;
	$response_data         = array(
		'action' => $form_data['address-action'],
		'id'     => $form_data['address-id'],
	);

	// Security check.
	if ( ! wp_verify_nonce( $form_data['_wpnonce'], 'give-manage-donor-addresses' ) ) {
		wp_send_json_error( array(
				'error'     => 1,
				'error_msg' => wp_sprintf(
					'<div class="notice notice-error"><p>%s</p></div>',
					__( 'Error: Security issue.', 'give' )
				),
			)
		);
	}

	$donor = new Give_Donor( $donorID );

	// Verify donor.
	if ( ! $donor->id ) {
		wp_send_json_error( array(
			'error' => 3,
		) );
	}

	// Unset all data except address.
	unset(
		$form_data['_wpnonce'],
		$form_data['address-action'],
		$form_data['address-id']
	);

	// Process action.
	switch ( $response_data['action'] ) {

		case 'add':
			if ( ! $donor->add_address( "{$address_type}[]", $form_data ) ) {
				wp_send_json_error( array(
						'error'     => 1,
						'error_msg' => wp_sprintf(
							'<div class="notice notice-error"><p>%s</p></div>',
							__( 'Error: Unable to save the address. Please check if address already exist.', 'give' )
						),
					)
				);
			}

			$total_addresses = count( $donor->address[ $address_type ] );

			$address_index = $is_multi_address_type ?
				$total_addresses - 1 :
				$address_type;

			$address_id = $is_multi_address_type ?
				end( array_keys( $donor->address[ $address_type ] ) ) :
				$address_type;

			$response_data['address_html'] = __give_get_format_address(
				end( $donor->address['billing'] ),
				array(
					// We can add only billing address from donor screen.
					'type'  => 'billing',
					'id'    => $address_id,
					'index' => ++ $address_index,
				)
			);
			$response_data['success_msg']  = wp_sprintf(
				'<div class="notice updated"><p>%s</p></div>',
				__( 'Successfully added a new address to the donor.', 'give' )
			);

			if ( $is_multi_address_type ) {
				$response_data['id'] = "{$response_data['id']}_{$address_index}";
			}

			break;

		case 'remove':
			if ( ! $donor->remove_address( $response_data['id'] ) ) {
				wp_send_json_error( array(
						'error'     => 2,
						'error_msg' => wp_sprintf(
							'<div class="notice notice-error"><p>%s</p></div>',
							__( 'Error: Unable to delete address.', 'give' )
						),
					)
				);
			}

			$response_data['success_msg'] = wp_sprintf(
				'<div class="notice updated"><p>%s</p></div>',
				__( 'Successfully removed a address of donor.', 'give' )
			);

			break;

		case 'update':
			if ( ! $donor->update_address( $response_data['id'], $form_data ) ) {
				wp_send_json_error( array(
						'error'     => 3,
						'error_msg' => wp_sprintf(
							'<div class="notice notice-error"><p>%s</p></div>',
							__( 'Error: Unable to update address. Please check if address already exist.', 'give' )
						),
					)
				);
			}

			$response_data['address_html'] = __give_get_format_address(
				$is_multi_address_type ?
					$donor->address[ $address_type ][ $address_id ] :
					$donor->address[ $address_type ],
				array(
					'type'  => $address_type,
					'id'    => $address_id,
					'index' => $address_id,
				)
			);
			$response_data['success_msg']  = wp_sprintf(
				'<div class="notice updated"><p>%s</p></div>',
				__( 'Successfully updated a address of donor', 'give' )
			);

			break;
	}// End switch().

	wp_send_json_success( $response_data );
}

add_action( 'wp_ajax_donor_manage_addresses', '__give_ajax_donor_manage_addresses' );

/**
 * Admin donor billing address label
 *
 * @since 2.0
 *
 * @param string $address_label
 *
 * @return string
 */
function __give_donor_billing_address_label( $address_label ) {
	$address_label = __( 'Billing Address', 'give' );

	return $address_label;
}

add_action( 'give_donor_billing_address_label', '__give_donor_billing_address_label' );

/**
 * Admin donor personal address label
 *
 * @since 2.0
 *
 * @param string $address_label
 *
 * @return string
 */
function __give_donor_personal_address_label( $address_label ) {
	$address_label = __( 'Personal Address', 'give' );

	return $address_label;
}

add_action( 'give_donor_personal_address_label', '__give_donor_personal_address_label' );

/**
 * Update Donor Information when User Profile is updated from admin.
 * Note: for internal use only.
 *
 * @param int $user_id
 *
 * @access public
 * @since  2.0
 *
 * @return bool
 */
function give_update_donor_name_on_user_update( $user_id = 0 ) {

	if ( current_user_can( 'edit_user', $user_id ) ) {

		$donor = new Give_Donor( $user_id, true );

		// Bailout, if donor doesn't exists.
		if ( ! $donor ) {
			return false;
		}

		// Get User First name and Last name.
		$first_name = ( $_POST['first_name'] ) ? give_clean( $_POST['first_name'] ) : get_user_meta( $user_id, 'first_name', true );
		$last_name  = ( $_POST['last_name'] ) ? give_clean( $_POST['last_name'] ) : get_user_meta( $user_id, 'last_name', true );
		$full_name  = strip_tags( wp_unslash( trim( "{$first_name} {$last_name}" ) ) );

		// Assign User First name and Last name to Donor.
		Give()->donors->update( $donor->id, array(
			'name' => $full_name,
		) );
		Give()->donor_meta->update_meta( $donor->id, '_give_donor_first_name', $first_name );
		Give()->donor_meta->update_meta( $donor->id, '_give_donor_last_name', $last_name );

	}
}

add_action( 'edit_user_profile_update', 'give_update_donor_name_on_user_update', 10 );
add_action( 'personal_options_update', 'give_update_donor_name_on_user_update', 10 );


/**
 * Updates the email address of a donor record when the email on a user is updated
 * Note: for internal use only.
 *
 * @since  1.4.3
 * @access public
 *
 * @param  int          $user_id       User ID.
 * @param  WP_User|bool $old_user_data User data.
 *
 * @return bool
 */
function give_update_donor_email_on_user_update( $user_id = 0, $old_user_data = false ) {

	$donor = new Give_Donor( $user_id, true );

	if ( ! $donor ) {
		return false;
	}

	$user = get_userdata( $user_id );

	if ( ! empty( $user ) && $user->user_email !== $donor->email ) {

		$success = Give()->donors->update( $donor->id, array(
			'email' => $user->user_email,
		) );

		if ( $success ) {
			// Update some payment meta if we need to
			$payments_array = explode( ',', $donor->payment_ids );

			if ( ! empty( $payments_array ) ) {

				foreach ( $payments_array as $payment_id ) {

					give_update_payment_meta( $payment_id, 'email', $user->user_email );

				}
			}

			/**
			 * Fires after updating donor email on user update.
			 *
			 * @since 1.4.3
			 *
			 * @param  WP_User    $user  WordPress User object.
			 * @param  Give_Donor $donor Give donor object.
			 */
			do_action( 'give_update_donor_email_on_user_update', $user, $donor );

		}
	}

}

add_action( 'profile_update', 'give_update_donor_email_on_user_update', 10, 2 );


/**
 * Flushes Give's cache.
 */
function give_cache_flush() {
	$result = Give_Cache::flush_cache();

	if ( $result ) {
		wp_send_json_success();
	} else {
		wp_send_json_error();
	}
}

add_action( 'wp_ajax_give_cache_flush', 'give_cache_flush', 10, 0 );
