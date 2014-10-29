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
 * @class UsabilityDynamics\Cluster\Sunrise
 */
namespace UsabilityDynamics\Cluster {

	if( !class_exists( 'UsabilityDynamics\Cluster\Sunrise' ) ){

		/**
		 * This is our WP-Cluster sunrise handler
		 * initialized and handled in the sunrise.php dropin
		 */
		class Sunrise {

			/**
			 * Current host
			 */
			protected $host;

			/**
			 * The constants that should be dynamically generated
			 */
			protected $protected_constants = array(
				'SUNRISE_LOADED',
				//'DOMAIN_CURRENT_SITE',
				//'SITE_ID_CURRENT_SITE',
				//'BLOG_ID_CURRENT_SITE',
				//'PATH_CURRENT_SITE',
				'DOMAIN_MAPPING'
			);

			/**
			 * Identify Current Blog.
			 *
			 * Used to get database information of network.
			 * Sets database configuration constants.
			 *
			 * @return mixed Nothing on success, string on failure
			 */
			function _identify_current_network() {
				global $current_blog, $blog_id, $current_site, $site_id, $wpdb;

				/** Try to figure out our domain parts and reconstruct them */
				$possible_domains = array();

				$domain_parts = (array) explode( '.', $_SERVER[ 'HTTP_HOST' ] );

				/** Add our domain suffixes */
				$domain_suffix = array_pop( $domain_parts );

				// Strip out port
				if( strpos( $domain_suffix, ':' ) ) {

					$domain_suffix_parts = explode( ':', $domain_suffix );

					if( is_array( $domain_suffix_parts ) && count( $domain_suffix_parts ) === 2 ) {
						$domain_suffix = $domain_suffix_parts[0];
						//$port = $domain_suffix_parts[1];
					}

				}

				/** Now, lets try to figure out the different domains we may have available, by going backwards */
				$last_domain = $domain_suffix;

				for( $x = count( $domain_parts ) - 1; $x >= 0; $x-- ){
					$last_domain = $domain_parts[ $x ] . '.' . $last_domain;
					$possible_domains[] = $last_domain;
				}

				// die( '<pre>' . print_r($current_blog, true ) . '</pre>');

				/** Try to lookup the blog */
				if( $possible_domains ) {
					$query = $wpdb->prepare( "SELECT blog_id, site_id, domain, path  FROM {$wpdb->blogs} WHERE domain IN (" . implode( ',', array_fill( 0, count( $possible_domains ), '%s' ) ) . ") AND public=1 ORDER BY CHAR_LENGTH( domain ) + CHAR_LENGTH( path ) DESC", $possible_domains );
				}

				if( isset( $query ) ) {
					$_singleMatch = $wpdb->get_results( $query );
				}

				// If we have possible domains Run MySQL Search returned ONE single result.
				if ( isset( $_singleMatch ) && count( $_singleMatch ) === 1 ) {

					$current_blog = (object) array(
						"blog_id" => reset($_singleMatch)->blog_id,
						"site_id" => reset($_singleMatch)->site_id,
						"path" => reset($_singleMatch)->path,
						"domain" => reset($_singleMatch)->domain
					);

				}

				// Multile domain matches, find first match.
				if ( !$current_blog && isset( $query ) && count( $_matches = $wpdb->get_results( $query ) ) > 1 ) {

					foreach ( $_matches as $_key => $_potential ) {

						if( isset( $_SERVER[ 'REQUEST_URI' ] ) && !empty( $_SERVER[ 'REQUEST_URI' ] ) ) {
							$_matches[ $_key ]->_position = $_position = strpos( $_potential->path, $_SERVER[ 'REQUEST_URI' ] );
						}

						if ( $_matches[ $_key ]->_position !== 0 ) {
							unset( $_matches[ $_key ] );
						}

					}


					if( count( $_matches ) === 1 ) {

						$current_blog = (object) array(
							"blog_id" => reset($_matches)->blog_id,
							"site_id" => reset($_matches)->site_id,
							"path" => reset($_matches)->path,
							"domain" => reset($_matches)->domain
						);

					}

				}

				if( !$current_blog && ( !isset( $query ) || ( !$current_blog = $wpdb->get_row( $query ) ) && defined( 'DOMAIN_CURRENT_SITE' ) ) ) {

					$current_blog = (object) array(
						"blog_id" => SITE_ID_CURRENT_SITE,
						"site_id" => BLOG_ID_CURRENT_SITE,
						"path" => PATH_CURRENT_SITE,
						"domain" => DOMAIN_CURRENT_SITE,
						"_default" => true
					);

					$current_site = (object) array(
						"blog_id" => SITE_ID_CURRENT_SITE,
						"site_id" => BLOG_ID_CURRENT_SITE,
						"path" => PATH_CURRENT_SITE,
						"host" => DOMAIN_CURRENT_SITE,
						"domain" => DOMAIN_CURRENT_SITE,
						"_default" => true
					);

				};

				/** If we don't have a blog, bail */
				if( !$current_blog ){
					return 'Unable to determine blog for host: ' . $this->host . '.';
				}

				/** Try to lookup the site */
				if( !$current_site = $wpdb->get_row( "SELECT * from {$wpdb->site} WHERE id = '{$current_blog->site_id}' LIMIT 0,1" ) ) {
					return 'Unable to to determine network for host: ' . $this->host . '.';
				}

				/** Set some other variables for current_site */
				$current_site->blog_id = $blog_id;
				$current_site->host = $this->host;

				/** Set our globals */
				$blog_id = $current_blog->blog_id;
				$site_id = $current_blog->site_id;

				/** Set the blog ID */
				$wpdb->set_blog_id( $blog_id, $site_id );

				if( isset( $_SERVER[ 'HTTP_X_UDS_DEBUG' ] ) && $_SERVER[ 'HTTP_X_UDS_DEBUG' ] === 'true' ) {
					header( "X-Site-Id:$site_id" );
					header( "X-Blog-Id:$blog_id" );
				}

			}

			/**
			 * Our constructor, finds the current network and blog, and also sets some constants
			 * @param bool $do_stuff Whether we should actually do initialization( needed for 'init' )
			 */
			function __construct( $do_stuff = true ){
				global $wp_veneer, $current_blog, $blog_id, $current_site, $site_id, $wpdb;

				if( !( is_bool( $do_stuff ) && $do_stuff ) ){
					return;
				}

				/** Make sure we have a valid http host */
				$this->host = $_SERVER[ 'HTTP_HOST' ];

				/** If we're not multisite, just bail */
				if( !defined( 'MULTISITE' ) && MULTISITE ){
					return $this;
				}

				/** Ok, now we should try to identify the current blog */
				if( is_string( $results = $this->_identify_current_network() ) ){
					header( 'HTTP/1.1 404 Not Found' );
					wp_die( '<h1>Network Error</h1><p>' . $results . '</p>' );
				}

				/** Ensure protected constants are not defined */
				foreach( $this->protected_constants as $protected_constant ){
					if( defined( $protected_constant ) ) {
						header( 'HTTP/1.1 500 Internal Server Error' );
						wp_die( '<h1>Network Error</h1><p>The constant "' . $protected_constant . '" is defined (probably in wp-config.php) - it\'s autogenerated. Please remove or comment out that define() line.</p>' );
					}
				}

				/** Define our sunrise constant */
				if( !defined( 'SUNRISE_LOADED' ) ) {
					define( 'SUNRISE_LOADED', 1 );
				}

				/** Define our cookie constants. */
				if( !defined( 'DOMAIN_CURRENT_SITE' ) ) {
					define( 'DOMAIN_CURRENT_SITE', $current_blog->domain );
				}

				/** Domain mapping has been successful. */
				if( !defined( 'DOMAIN_MAPPING' ) ) {
					define( 'DOMAIN_MAPPING', 1 );
				}

				/** Define our current blog/site/path constants */
				if( !defined( 'SITE_ID_CURRENT_SITE' ) ) {
					define( 'SITE_ID_CURRENT_SITE', $site_id );
				}

				if( !defined( 'BLOG_ID_CURRENT_SITE' ) ) {
					define( 'BLOG_ID_CURRENT_SITE', $blog_id );
				}

				if( !defined( 'PATH_CURRENT_SITE' ) ) {
					define( 'PATH_CURRENT_SITE', $current_blog->path );
				}

				/** Ok, if we have a multi_db environment, we should now connect to the new DB */
				if( @isset( $wp_veneer->cluster->db ) ){
					$wp_veneer->cluster->db->_maybe_connect_to_blog_db();
				}

				/** Return this */
				return $this;

			}

			/**
			 * This function lets us chain methods without having to instantiate first, YOU MUST COPY THIS TO ALL SUB CLASSES
			 */
			static public function init(){
				return new self( false );
			}

		}

		/**
		 * If we don't have a wp_cluster object, we should make one, but it should be a child of wp_veneer
		 */
		global $wp_veneer;

		if( !is_object( $wp_veneer ) ){
			$wp_veneer = new \stdClass();
		}

		/** Add to our object, if we don't have the cluster object */
		if( !isset( $wp_veneer->cluster ) ){
			$wp_veneer->cluster = new \stdClass();
		}

		/** Now, add on our sunrise object, finally */
		if( !isset( $wp_veneer->cluster->sunrise ) ){
			$wp_veneer->cluster->sunrise = Sunrise::init()->__construct();
		}

	}

}