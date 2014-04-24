<?php
/**
 * Plugin Name: Multisite Domain Mapping Handler
 * Version: 1.2.0
 * Description: Handles database for WP-Cluster.
 * Author: Usability Dynamics
 * Domain Path: WIP
 * Network: True
 *
 * SELECT blog_id FROM corporate_blogs WHERE domain IN ('www.udx.io','udx.io') ORDER BY CHAR_LENGTH(domain) DESC LIMIT 1
 * Manually going to origin.udx.io will redirect to udx.io while www.origin.udx.io will redirect to www.udx.io.
 *
 * $current_blog->domain      => usabilitydyamics.com
 * $current_blog->subdomain   => media|static|assets
 *
 * @version 0.4.2
 */

/** Disable caching to avoid errors being cached by CloudFront. */
nocache_headers();

if( !defined( 'SUNRISE_LOADED' ) ) {
  define( 'SUNRISE_LOADED', 1 );
}

// Enable HTTPS Setting if proxied from Nginx.
$_host = $_SERVER[ 'HTTP_HOST' ];
if( isset( $_SERVER[ 'HTTP_X_FORWARDED_PROTO' ] ) ) {
  $_SERVER[ 'HTTPS' ] = 'on';
}

// Bail if we don't have the current blog set at this point.
if( !( isset( $current_blog ) && is_object( $current_blog ) && isset( $current_blog->domain ) && !empty( $current_blog->domain ) ) ){
  header( 'HTTP/1.1 404 Not Found' );
  wp_die( '<h1>Network Error</h1><p>The domain you requested (' . $_host . ') is not available on network.</p>' );
}

// Amazon CloudFront gets access.
if( isset( $_SERVER[ 'HTTP_USER_AGENT' ] ) && $_SERVER[ 'HTTP_USER_AGENT' ] === 'Amazon CloudFront' ) {}

// Veneer API Proxy Gets gets access.
if( isset( $_SERVER[ 'HTTP_USER_AGENT' ] ) && $_SERVER[ 'HTTP_USER_AGENT' ] === 'Veneer' ) {}

// If we don't have a current blog, we need to try to bail - we're missing the DB handler and can do nothing (wpdb
// doesn't exist, wp-config hasn't been called)
if( !isset( $current_blog ) || !$current_blog ){
  // We're going to try to get the current blog from $wpdb
  // @todo
  header( 'HTTP/1.1 500 Internal Server Error' );
  wp_die( "<h1>Network Error</h1><p>You're trying to use the wp-cluster sunrise functionality. You should have a \$wpdb connection. Make the query to set \$current_blog from HTTP_HOST!</p>" );
}

// Define globals.
$blog_id = $current_blog->blog_id;
$site_id = $current_blog->site_id;

// Determine if extra subdomain exists.
if( $_SERVER[ 'HTTP_HOST' ] != $subdomain = str_replace( '.' . $current_blog->domain, '', $_SERVER[ 'HTTP_HOST' ] ) ) {
  $current_blog->subdomain = $subdomain;
}

// Setup the needed current_site variable.
if( !$current_site = $wpdb->get_row( "SELECT * from {$wpdb->site} WHERE id = '{$current_blog->site_id}' LIMIT 0,1" ) ) {
  header( 'HTTP/1.1 404 Not Found' );
  wp_die( '<h1>Network Error</h1><p>Unable to determine network.</p>' );
}
$current_site->blog_id = $wpdb->get_var( "SELECT blog_id FROM {$wpdb->blogs} WHERE domain='{$current_site->domain}' AND path='{$current_site->path}'" );
$current_site->host = $_host;

// Setup our protected constants
$protected_constants = array(
  'WP_BASE_DOMAIN',
  'WP_BASE_URL',
  'WP_HOME',
  'WP_SITEURL',
  'WP_CONTENT_URL',
  'WP_VENDOR_URL',
  'WP_PLUGIN_URL',
  'WPMU_PLUGIN_URL',
  'UPLOADBLOGSDIR',
  'UPLOADS',
  'BLOGUPLOADDIR',
  'COOKIE_DOMAIN',
  'DOMAIN_CURRENT_SITE',
  'SITE_ID_CURRENT_SITE',
  'BLOG_ID_CURRENT_SITE',
  'PATH_CURRENT_SITE',
  'DOMAIN_MAPPING'
);

// Make sure none of them are defined
foreach( $protected_constants as $protected_constant ){
  if( defined( $protected_constant ) ) {
    header( 'HTTP/1.1 500 Internal Server Error' );
    wp_die( '<h1>Network Error</h1><p>The constant "' . $protected_constant . '" is defined (probably in wp-config.php) - it\'s autogenerated. Please remove or comment out that define() line.</p>' );
  }
}

// Now declare our dynamically generated constants for any URLs
define( 'WP_BASE_DOMAIN', $current_blog->domain );
define( 'WP_BASE_URL', WP_DEFAULT_PROTOCOL . '://' . WP_BASE_DOMAIN );
define( 'WP_HOME', rtrim( WP_BASE_URL, '/' ) );
define( 'WP_SITEURL', rtrim( WP_BASE_URL, '/' ) . '/' . WP_SYSTEM_DIRECTORY );
define( 'WP_CONTENT_URL', rtrim( WP_BASE_URL, '/' ) . '/application/dropins' );
define( 'WP_VENDOR_URL', rtrim( WP_BASE_URL, '/' ) . '/vendor' );
define( 'WP_PLUGIN_URL', rtrim( WP_VENDOR_URL, '/' ) . '/modules' );
define( 'WPMU_PLUGIN_URL', rtrim( WP_BASE_URL, '/' ) . '/application' );

// Ok, define our stuff for our uploads
// Base uploads dir relative to ABSPATH
define( 'UPLOADBLOGSDIR', trim( WP_VENEER_STORAGE, '/' ) . '/public/' . $current_blog->domain );
// Note, the main site in a post-MU network uses wp-content/uploads.
// This is handled in wp_upload_dir() by ignoring UPLOADS for this case.
define( 'UPLOADS', UPLOADBLOGSDIR . '/media/' );
// Uploads dir relative to ABSPATH
define( 'BLOGUPLOADDIR', rtrim( WP_CONTENT_DIR, '/' ) . '/' . UPLOADS );

// Define our cookie constants.
define( 'COOKIE_DOMAIN', '.' . $current_blog->domain );
define( 'DOMAIN_CURRENT_SITE', $current_blog->domain );

// Define our current blog/site/path constants.
define( 'SITE_ID_CURRENT_SITE', $site_id );
define( 'BLOG_ID_CURRENT_SITE', $blog_id );
define( 'PATH_CURRENT_SITE', $current_blog->path );

// Domain mapping has been successful.
define( 'DOMAIN_MAPPING', 1 );

// Ok, now we're going to get and set the configs for site and vertical */
global $wp_cluster;
// First, we're going to get the site config
$wp_cluster->site_config = $wp_cluster->config->get_config( 'options/sites/' . $current_blog->domain );
// Now, get the vertical config
$wp_cluster->vertical_config = $wp_cluster->config->get_config( 'options/verticals/' . $wp_cluster->site_config[ 'vertical' ] );