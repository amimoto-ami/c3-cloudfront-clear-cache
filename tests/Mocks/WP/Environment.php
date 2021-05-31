<?php
namespace C3_CloudFront_Cache_Controller\Test\Mocks\WP;
use C3_CloudFront_Cache_Controller\WP;

class Environment extends WP\Environment {
	private $mode = 'public';

	/**
	 * @param ['public', 'amimoto', 'amimoto_managed', 'wpcli'] $mode
	 */
	function __construct( string $mode = 'public' ) {
		$this->mode = $mode;
	}

	public function is_amimoto_managed() {
		if ( $this->mode === 'amimoto_managed' ) {
			return true;
		}
		return false;
	}

	public function is_amimoto() {
		if ( in_array( $this->mode, ['amimoto', 'amimoto_managed'] ) ) {
			return true;
		}
		return false;
	}

	public function has_managed_cdn() {
		if ( in_array( $this->mode, ['amimoto', 'amimoto_managed' ] ) ) {
			return true;
		}
		return false;
	}
	public function is_wp_cli() {
		return 'wpcli' === $this->mode;
	}

	private $distribution_id = null;
	public function set_distribution_id( string $id = null ) {
		$this->distribution_id = $id;
	}
	public function get_distribution_id() {
		return $this->distribution_id;
	}

	private $aws_access_key = null;
	public function set_aws_access_key( string $key = null ) {
		$this->aws_access_key = $key;
	}
	public function get_aws_access_key() {
		return $this->aws_access_key;
	}

	private $aws_secret_key = null;
	public function set_aws_secret_key( string $key = null ) {
		$this->aws_secret_key = $key;
	}
	public function get_aws_secret_key() {
		return $this->aws_secret_key;
	}
}