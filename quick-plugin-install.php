<?php
/**
 * Plugin Name: Quick Plugin Installer
 * Plugin URI: 
 * Description: Quickly install and activate plugins via AJAX.
 * Version: 0.1-beta
 * Author: Blazer Six
 * Author URI: http://www.blazersix.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package Quick_Plugin_Installer
 * @author Brady Vercher <brady@blazersix.com>
 * @license GPL-2.0+
 */

/**
 * Load the plugin.
 */
function qpi_load() {
	add_action( 'wp_ajax_qpi-install-plugin', 'qpi_install_plugin' );
	add_action( 'wp_ajax_qpi-activate', 'qpi_activate_plugin' );
	
	add_action( 'admin_enqueue_scripts', 'qpi_admin_enqueue_scripts' );
}
add_action( 'plugins_loaded', 'qpi_load' );

/**
 * Enqueue scripts on the plugin install screens.
 */
function qpi_admin_enqueue_scripts( $hook_suffix ) {
	if ( 'plugin-install.php' == $hook_suffix ) {
		wp_enqueue_script( 'qpi-script', plugin_dir_url( __FILE__ ) . 'quick-plugin-install.js', array( 'jquery' ) );
		wp_localize_script( 'qpi-script', 'QPI', array(
			'activated' => __( 'Activated', 'qpi-i18n' ),
			'error'     => __( 'Error', 'qpi-i18n' ),
		) );
	}
}

/**
 * AJAX callback to install and activate a plugin.
 *
 * @see /wp-admin/update.php
 * @see /wp-admin/includes/plugin-install.php
 * @see /wp-admin/includes/class-wp-upgrader.php
 */
function qpi_install_plugin() {
	if ( empty( $_POST['plugin'] ) ) {
		wp_send_json_error( __( 'ERROR: No slug was passed to the AJAX callback.', 'qpi-i18n' ) );
	}
	
	check_admin_referer( 'install-plugin_' . $_POST['plugin'] );
	
	if ( ! current_user_can( 'install_plugins' ) || ! current_user_can( 'activate_plugins' ) ){
		wp_send_json_error(  __( 'You do not have sufficient permissions to install plugins on this site.', 'qpi-i18n' ) );
	}
	
	require_once( plugin_dir_path( __FILE__ ) . '/class-empty-upgrader-skin.php' );
	include_once ( ABSPATH . 'wp-admin/includes/plugin-install.php' );
	
	$api = plugins_api( 'plugin_information', array(
		'slug'   => $_POST['plugin'],
		'fields' => array( 'sections' => false )
	) );
	
	if ( is_wp_error( $api ) ) {
		wp_send_json_error( sprintf( __( 'ERROR: Error fetching plugin information: %s', 'qpi-i18n' ), $api->get_error_message() ) );
	}
	
	$upgrader = new Plugin_Upgrader( new QPI_Upgrader_Skin( array(
		'nonce'  => 'install-plugin_' . $_POST['plugin'],
		'plugin' => $_POST['plugin'],
		'api'    => $api,
	) ) );
	
	$install_result = $upgrader->install( $api->download_link );
	
	if ( ! $install_result || is_wp_error( $install_result ) ) {
		wp_send_json_error( sprintf( __( 'ERROR: Failed to install plugin: %s', 'qpi-i18n' ), $api->get_error_message() ) );
	}
	
	$plugin_file = $upgrader->plugin_info();
	
	if ( apply_filters( 'qpi_install_and_activate', '__return_false' ) ) {
		$activate_result = activate_plugin( $plugin_file );
		
		if ( is_wp_error( $activate_result ) ) {
			wp_send_json_error( sprintf( __( 'ERROR: Failed to activate plugin: %s', 'qpi-i18n' ), get_error_message( $api ) ) );
		}
		
		wp_send_json_success();
	}
	
	$activate_url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=' . $plugin_file, 'activate-plugin_' . $plugin_file );
	$data['activateLink'] = '<a href="' . $activate_url . '" class="install-now">' . __( 'Activate', 'qpi-i18n' ) . '</a>';
	wp_send_json_success( $data );
}

/**
 * AJAX callback to activate a plugin.
 */
function qpi_activate_plugin() {
	if ( empty( $_POST['plugin'] ) ) {
		wp_send_json_error( __( 'ERROR: No slug was passed to the AJAX callback.', 'qpi-i18n' ) );
	}
	
	if ( wp_verify_nonce( $_POST['_wpnonce'], 'activate-plugin_' . $_POST['plugin'] ) ) {
		wp_send_json_error();
	}
	
	if ( ! current_user_can( 'activate_plugins' ) ) {
		wp_send_json_error(  __( 'You do not have sufficient permissions to install plugins on this site.', 'qpi-i18n' ) );
	}
	
	$activate_result = activate_plugin( urldecode( $_POST['plugin'] ) );
	
	if ( is_wp_error( $activate_result ) ) {
		wp_send_json_error( sprintf( __( 'ERROR: Failed to activate plugin: %s', 'qpi-i18n' ), $activate_result->get_error_message() ) );
	}
	
	wp_send_json_success();
}
