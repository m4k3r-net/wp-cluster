<?php
/**
 * Plugin Name: WP-Cluster
 * Plugin URI: http://usabilitydynamics.com/
 * Description: Application managing must-use plugins and obfuscation rewrites.
 * Version: 0.4.4
 * Author: Usability Dynamics
 * Author URI: http://usabilitydynamics.com/
 * License: GPLv2 or later
 * Network: True
 * GitHub Plugin URI: UsabilityDynamics/wp-cluster
 *
 * The Loder class is self-initializing.
 *
 * @example
 *
 *      UsabilityDynamics\Cluster::get_instance()->state->settings;
 *      UsabilityDynamics\Cluster::get_instance()->get()
 *
 * @namespace Veneer
 * @module Veneer
 */

if( file_exists( __DIR__ . '/vendor/libraries/autoload.php' ) ) {
	include_once( __DIR__ . '/vendor/libraries/autoload.php' );
}

// Include bootstrap.
if( !class_exists( 'UsabilityDynamics\Cluster\Bootstrap' ) ) {
	include_once( __DIR__ . '/lib/class-bootstrap.php' );
}

// Initialize.
new UsabilityDynamics\Cluster\Bootstrap();