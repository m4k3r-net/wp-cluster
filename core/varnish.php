<?php
/**
 * Varnish CMS Loader
 *
 * The Loder class is self-initializing.
 *
 *
 * @example
 *
 *      // Get Settings Object
 *      Varnish::get_instance()->state->settings;
 *      Varnish::get_instance()->get()
 *
 * @namespace Varnish
 * @module Varnish
 */
namespace Varnish {

  /**
   * Class Loader
   *
   * @class Loader
   */
  final class Varnish {

    /**
     * Varnish core version.
     *
     * @static
     * @property $version
     * @type {Object}
     */
    public static $version = '0.1.1';

    /**
     * Singleton Instance Reference.
     *
     * @public
     * @static
     * @property $instance
     * @type {Object}
     */
    public static $instance;

    /**
     * Constructor.
     *
     * @for Loader
     * @method __construct
     */
    public function __construct() {

      // Save context reference.
      self::$instance = & $this;

      // Load Controllers
      require_once( 'controllers/api.php' );
      require_once( 'controllers/debug.php' );
      require_once( 'controllers/developer.php' );
      require_once( 'controllers/media.php' );
      require_once( 'controllers/security.php' );
      require_once( 'controllers/settings.php' );
      require_once( 'controllers/theme.php' );

      // Load Helpers
      require_once( 'helpers/utility.php' );
      require_once( 'helpers/log.php' );
      require_once( 'helpers/views.php' );

      set_error_handler( array( $this, 'error_handler' ) );

      // Initialize Controllers and Helpers
      $this->Developer = new Developer();
      $this->Debug     = new Debug();
      $this->API       = new API();
      $this->Media     = new Media();
      $this->Settings  = new Settings();
      $this->Security  = new Security();
      $this->Theme     = new Theme();
      $this->Views     = new Views();
      $this->Utility   = new Utility();
      $this->Log       = new Log();

      $this->state = json_decode( json_encode( array(
        'settings'  => $this->Settings->data,
        'paths'     => array(
          'root'        => untrailingslashit( __DIR__ ),
          'controllers' => trailingslashit( __DIR__ ) . 'controllers',
          'helpers'     => trailingslashit( __DIR__ ) . 'helpers',
          'modules'     => trailingslashit( __DIR__ ) . 'modules',
          'schemas'     => trailingslashit( __DIR__ ) . 'schemas',
          'vendor'      => trailingslashit( __DIR__ ) . 'vendor'
        ),
        'structure' => array()
      ) ) );

      // Fix MultiSite URLs
      $this->fix_urls();

      // Initialize all else.
      add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
      add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ), 21 );

    }

    /**
     * Initializer.
     *
     * @method plugins_loaded
     * @author potanin@UD
     */
    public function plugins_loaded() {
      add_action( 'admin_init', array( $this, 'admin_init' ) );
    }

    /**
     * Error Handler
     *
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     *
     * @return bool
     */
    function error_handler( $errno, $errstr, $errfile, $errline ) {

      if( !( error_reporting() & $errno ) ) {
        // This error code is not included in error_reporting
        return;
      }
      switch( $errno ) {

        // Fatal
        case E_ERROR:
        case E_CORE_ERROR:
        case E_RECOVERABLE_ERROR:
        case E_COMPILE_ERROR:
        case E_USER_ERROR:
          wp_die( "<h1>Website Temporarily Unavailable</h1><p>We apologize for the inconvenience and will return shortly.</p>" );
        break;

        // Do Nothing
        case E_WARNING:
        case E_USER_NOTICE:
          return true;
        break;

        // No Idea.
        default:
          return;
          // wp_die( "<h1>Website Temporarily Unavailable</h1><p>We apologize for the inconvenience and will return shortly.</p>" );
        break;
      }

      return true;

    }

    /**
     * Get Setting.
     *
     *    // Get Setting
     *    Varnish::get( 'my_key' )
     *
     * @method get
     *
     * @for Flawless
     * @author potanin@UD
     * @since 0.1.1
     */
    public function get( $key, $default ) {
      return self::get_instance()->Settings->get( $key, $default );
    }

    /**
     * Set Setting.
     *
     * @usage
     *
     *    // Set Setting
     *    Varnish::set( 'my_key', 'my-value' )
     *
     * @method get
     * @for Flawless
     *
     * @author potanin@UD
     * @since 0.1.1
     */
    public function set( $key, $value ) {
      return self::get_instance()->Settings->set( $key, $value );
    }

    /**
     * Initialize Admin
     *
     * @method admin_init
     * @author potanin@UD
     */
    public function admin_init() {

      /* Remove Akismet API Key Nag */
      remove_action( 'admin_notices', 'akismet_warning' );

      /* Disable BuddyPress Nag */
      remove_action( 'admin_notices', 'bp_core_update_nag', 5 );
      remove_action( 'network_admin_notices', 'bp_core_update_nag', 5 );

    }

    /**
     * Update Amin Menu
     *
     * @method admin_bar_menu
     * @author potanin@UD
     */
    public function admin_bar_menu( $wp_admin_bar = false ) {

      if( !is_super_admin() || !is_multisite() || !$wp_admin_bar ) {
        return;
      }

      $wp_admin_bar->add_menu( array(
        'parent' => 'network-admin',
        'id'     => 'network-themes',
        'title'  => __( 'Themes' ),
        'href'   => network_admin_url( 'themes.php' ),
      ) );

      $wp_admin_bar->add_menu( array(
        'parent' => 'network-admin',
        'id'     => 'network-plugins',
        'title'  => __( 'Plugins' ),
        'href'   => network_admin_url( 'plugins.php' ),
      ) );

      $wp_admin_bar->add_menu( array(
        'parent' => 'network-admin',
        'id'     => 'network-plugins',
        'title'  => __( 'Settings' ),
        'href'   => network_admin_url( 'settings.php' ),
      ) );

    }

    /**
     * Automatically fix MS URLs that get messed up
     *
     */
    public function fix_urls() {

      add_filter( 'network_site_url', function ( $url ) {
        //if( !strpos( $url, '/system' ) ) { return trailingslashit( $url ) . 'system/'; }
        return str_replace( 'wp-admin', 'system/wp-admin', $url );
      } );

      add_filter( 'blog_option_upload_path', function ( $url ) {

        if( strpos( $url, 'wp-content/blogs.dir' ) !== false ) {
          return str_replace( 'wp-content/blogs.dir', 'media/sites', $url );
        }

        if( strpos( $url, 'wp-content/uploads' ) !== false ) {
          return str_replace( 'wp-content/uploads', 'media/sites', $url );
        }

        return $url;

      } );

    }

    /**
     * Get the Varnish Singleton
     *
     * Concept based on the CodeIgniter get_instance() concept.
     *
     * @example
     *
     *      var settings = Varnish::get_instance()->Settings;
     *      var api = Varnish::$instance()->API;
     *
     * @static
     * @return object
     *
     * @method get_instance
     * @for Varnish
     */
    public static function &get_instance() {
      return self::$instance;
    }
  }

  // Initialize VarnishCMS
  new Varnish();

}

