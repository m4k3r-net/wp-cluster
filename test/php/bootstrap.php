<?php
/**
 * PHP Unit Test Bootstrap
 */

// Set ROOT of current module
define( 'TEST_ROOT_PATH', dirname( dirname( dirname( __FILE__ ) ) ) );

// Be sure we have wp-test-config.php file installed
$config = TEST_ROOT_PATH . '/wp-test-config.php';
if( !file_exists( $config ) ) {
  exit( "You should put and setup your wp-test-config.php in {$config}. Sample can be found in test/fixtures/wp-test-config-sample.php\n" );
}

// Set correct path to Composer Autoload file
$path = TEST_ROOT_PATH . '/vendor/autoload.php';
if( !file_exists( $path ) || !require_once( $path ) ) {
  exit( "Could not load composer autoload file. Path: {$path}\n" );
}

// Determine if our Bootstrap class exists.
if( !class_exists( 'UsabilityDynamics\Test\Bootstrap' ) ) {
  exit( "Bootstrap class for init WP PHPUnit Tests is not found.\n" );
}

// Loader
UsabilityDynamics\Test\Bootstrap::get_instance( array(
  'config' => $config
) );

echo 'Wordpress Environment loaded...';