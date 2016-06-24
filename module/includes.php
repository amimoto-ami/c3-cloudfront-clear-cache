<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once( 'base.php' );

// Model
require_once( 'model/auth.php' );
require_once( 'model/invalidation.php' );


// View
require_once( 'view/components.php' );
require_once( 'view/root.php' );
