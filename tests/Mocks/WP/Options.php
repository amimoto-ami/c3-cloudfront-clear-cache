<?php
namespace C3_CloudFront_Cache_Controller\Test\Mocks\WP;
use C3_CloudFront_Cache_Controller\WP;

class Options extends WP\Options {
    private $should_null = false;
    
    function __construct( $should_null = false ) {
        $this->should_null = $should_null;
    }
    public function get_options() {
        if ( $this->should_null ) {
            return null;
        }
        return array(
            'distribution_id' => 'DIST_ID',
            'access_key'      => 'ACCESS_KEY',
            'secret_key'      => 'SECRET_KEY',
        );
    }
}