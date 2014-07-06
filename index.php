<?php
/**
 * WordPress Site Loader
 */
try {

  /** Try to pull in our local debug file if it exists */
  if( file_exists( 'local-debug.php' ) ){
    require_once( 'local-debug.php' );
  }
  
  /** Make sure we have a proper wp-config file */
  if( !file_exists( 'vendor/libraries/automattic/wordpress/wp-config.php' ) ) {
    throw new Exception( 'Site not installed.' );
  }
  
  /** Make sure we have our vendor libraries installed, and if we - include them */
  if( !file_exists( 'vendor/libraries/automattic/wordpress/wp-blog-header.php' ) ) {
    throw new Exception( 'Site vendor libraries not installed.' );
  }else{
    require_once( 'vendor/libraries/automattic/wordpress/wp-blog-header.php' );
  }

} catch( Exception $e ) {

  /** There was an issue, we need to bail */
  echo( $e->getMessage() );
  die();
  
}