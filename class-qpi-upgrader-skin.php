<?php
include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );

class QPI_Upgrader_Skin extends WP_Upgrader_Skin {
	public function __construct( $args = array() ) {
		$defaults = array(
			'type'   => 'web',
			'url'    => '',
			'plugin' => '',
			'nonce'  => '',
			'title'  => '',
		);

		$args = wp_parse_args( $args, $defaults );

		$this->type = $args['type'];
		$this->api = isset( $args['api'] ) ? $args['api'] : array();

		parent::__construct( $args );
	}

	public function request_filesystem_credentials() {
		return true;
	}

	public function error() {
		die( '-1' );
	}

	public function header() {}
	public function footer() {}
	public function feedback() {}
}
