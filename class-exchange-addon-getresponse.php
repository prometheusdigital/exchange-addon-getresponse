<?php
/**
 * ExchangeWP - GetResponse Add-on class.
 *
 * @package   TGM_Exchange_GetResponse
 * @author    Thomas Griffin
 * @license   GPL-2.0+
 * @copyright 2013 Griffin Media, LLC. All rights reserved.
 */

/**
 * Main plugin class.
 *
 * @package TGM_Exchange_GetResponse
 */
class TGM_Exchange_GetResponse {

    /**
     * Plugin version, used for cache-busting of style and script file references.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $version = '1.0.0';

    /**
     * The name of the plugin.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_name = 'ExchangeWP - GetResponse Add-on';

    /**
     * Unique plugin identifier.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $plugin_slug = 'exchange-addon-getresponse';

    /**
     * Plugin textdomain.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $domain = 'LION';

    /**
     * Plugin file.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $file = __FILE__;

    /**
     * Instance of this class.
     *
     * @since 1.0.0
     *
     * @var object
     */
    public static $instance = null;

    /**
     * Holds any error messages.
     *
     * @since 1.0.0
     *
     * @var array
     */
    public $errors = array();

    /**
     * Flag to determine if form was saved.
     *
     * @since 1.0.0
     *
     * @var bool
     */
    public $saved = false;

    /**
     * Initialize the plugin class object.
     *
     * @since 1.0.0
     */
    private function __construct() {

        // Load plugin text domain.
        add_action( 'plugins_loaded', array( $this, 'load_plugin_textdomain' ) );

        // Load the plugin.
        add_action( 'init', array( $this, 'init' ) );

        // Load ajax hooks.
        add_action( 'wp_ajax_tgm_exchange_getresponse_update_lists', array( $this, 'lists' ) );

    }

    /**
     * Return an instance of this class.
     *
     * @since 1.0.0
     *
     * @return object A single instance of this class.
     */
    public static function get_instance() {

        // If the single instance hasn't been set, set it now.
        if ( null == self::$instance )
            self::$instance = new self;

        return self::$instance;

    }

    /**
     * Load the plugin text domain for translation.
     *
     * @since 1.0.0
     */
    public function load_plugin_textdomain() {

        $domain = $this->domain;
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

        load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

    }

    /**
     * Loads the plugin.
     *
     * @since 1.0.0
     */
    public function init() {

        // Load admin assets.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Utility actions.
        add_filter( 'plugin_action_links_' . plugin_basename( TGM_EXCHANGE_GETRESPONSE_FILE ), array( $this, 'plugin_links' ) );
        add_filter( 'it_exchange_theme_api_registration_password2', array( $this, 'output_optin' ) );
        add_action( 'it_exchange_content_checkout_logged_in_checkout_requirement_guest_checkout_end_form', array( $this, 'output_optin_guest' ) );
        add_action( 'it_exchange_register_user', array( $this, 'do_optin' ) );
        add_action( 'it_exchange_init_guest_checkout', array( $this, 'do_optin_guest' ) );

    }

    /**
     * Outputs update nag if the currently installed version does not meet the addon requirements.
     *
     * @since 1.0.0
     */
    public function nag() {

        ?>
        <div id="tgm-exchange-getresponse-nag" class="it-exchange-nag">
            <?php
            printf( __( 'To use the GetResponse add-on for ExchangeWP, you must be using ExchangeWP version 1.0.3 or higher. <a href="%s">Please update now</a>.', 'LION' ), admin_url( 'update-core.php' ) );
            ?>
        </div>
        <?php

    }

    /**
     * Register and enqueue admin-specific stylesheets.
     *
     * @since 1.0.0
     *
     * @return null Return early if not on our addon page in the admin.
     */
    public function enqueue_admin_styles() {

        if ( ! $this->is_settings_page() ) return;

        wp_enqueue_style( $this->plugin_slug . '-admin-styles', plugins_url( 'lib/css/admin.css', __FILE__ ), array(), $this->version );

    }

    /**
     * Register and enqueue admin-specific JS.
     *
     * @since 1.0.0
     *
     * @return null Return early if not on our addon page in the admin.
     */
    public function enqueue_admin_scripts() {

        if ( ! $this->is_settings_page() ) return;

        wp_enqueue_script( $this->plugin_slug . '-admin-script', plugins_url( 'lib/js/admin.js', __FILE__ ), array( 'jquery' ), $this->version );

    }

    /**
     * Render the settings page for this plugin.
     *
     * @since 1.0.0
     */
    public function settings() {

        // Save form settings if necessary.
        if ( isset( $_POST['tgm-exchange-getresponse-form'] ) && $_POST['tgm-exchange-getresponse-form'] )
            $this->save_form();

        ?>
        <div class="wrap tgm-exchange-getresponse">
            <?php screen_icon( 'it-exchange' ); ?>
            <h2><?php _e( 'GetResponse Settings', 'LION' ); ?></h2>

            <?php if ( ! empty( $this->errors ) ) : ?>
                <div id="message" class="error"><p><strong><?php echo implode( '<br>', $this->errors ); ?></strong></p></div>
            <?php endif; ?>

            <?php if ( $this->saved ) : ?>
                <div id="message" class="updated"><p><strong><?php _e( 'Your settings have been saved successfully!', 'LION' ); ?></strong></p></div>
            <?php endif; ?>

            <?php do_action( 'it_exchange_getresponse_settings_page_top' ); ?>
            <?php do_action( 'it_exchange_addon_settings_page_top' ); ?>

            <div class="tgm-exchange-getresponse-settings">
                <p><?php _e( 'To setup GetResponse in Exchange, fill out the settings below.', 'LION' ); ?></p>
                <form class="tgm-exchange-getresponse-form" action="admin.php?page=it-exchange-addons&add-on-settings=getresponse" method="post">
                    <?php wp_nonce_field( 'tgm-exchange-getresponse-form' ); ?>
                    <input type="hidden" name="tgm-exchange-getresponse-form" value="1" />
                    <?php
                       $exchangewp_campaignmonitor_options = get_option( 'it-storage-exchange_addon_getresponse' );
                       $license = $exchangewp_campaignmonitor_options['exchange_getresponse_license_key'];
                       $exstatus = trim( get_option( 'exchange_getresponse_license_status' ) );
                    ?>
                    <table class="form-table">
                        <tbody>
                          <tr valign="middle">
                                <th scope="row">
                                    <label class="description" for="exchange_getresponse_license_key"><strong><?php _e('Enter your ExchangeWP Get Response license key'); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-getresponse_license" name="_tgm_exchange_getresponse[getresponse-license-key]" type="text" value="<?php echo $this->get_setting( 'getresponse-license-key' ); ?>" placeholder="<?php esc_attr_e( 'Enter your ExchangeWP License Key here.', 'LION' ); ?>" />
                                    <span>
                                        <?php if( $exstatus !== false && $exstatus == 'valid' ) { ?>
                                            <span style="color:green;"><?php _e('active'); ?></span>
                          			            <?php wp_nonce_field( 'exchange_getresponse_nonce', 'exchange_getresponse_nonce' ); ?>
                          			            <input type="submit" class="button-secondary" name="exchange_getresponse_license_deactivate" value="<?php _e('Deactivate License'); ?>"/>
                                        <?php } else {
                                            wp_nonce_field( 'exchange_getresponse_nonce', 'exchange_getresponse_nonce' ); ?>
                                            <input type="submit" class="button-secondary" name="exchange_getresponse_license_activate" value="<?php _e('Activate License'); ?>"/>
                                        <?php } ?>
                                    </span>
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-getresponse-api-key"><strong><?php _e( 'GetResponse API Key', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-getresponse-api-key" type="password" name="_tgm_exchange_getresponse[getresponse-api-key]" value="<?php echo $this->get_setting( 'getresponse-api-key' ); ?>" placeholder="<?php esc_attr_e( 'Enter your GetResponse API key here.', 'LION' ); ?>" />
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-getresponse-lists"><strong><?php _e( 'GetResponse List', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <div class="tgm-exchange-getresponse-list-output">
                                        <?php echo $this->get_getresponse_lists( $this->get_setting( 'getresponse-api-key' ) ); ?>
                                    </div>
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-getresponse-label"><strong><?php _e( 'GetResponse Label', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-getresponse-label" type="text" name="_tgm_exchange_getresponse[getresponse-label]" value="<?php echo $this->get_setting( 'getresponse-label' ); ?>" placeholder="<?php esc_attr_e( 'Enter your GetResponse checkbox label here.', 'LION' ); ?>" />
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-getresponse-checked"><strong><?php _e( 'Check GetResponse box by default?', 'LION' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-getresponse-checked" type="checkbox" name="_tgm_exchange_getresponse[getresponse-checked]" value="<?php echo (bool) $this->get_setting( 'getresponse-checked' ); ?>" <?php checked( $this->get_setting( 'getresponse-checked' ), 1 ); ?> />
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php submit_button( __( 'Save Changes', 'LION' ), 'primary button-large', '_tgm_exchange_getresponse[save]' ); ?>
                </form>
            </div>

            <?php do_action( 'it_exchange_getresponse_settings_page_bottom' ); ?>
            <?php do_action( 'it_exchange_addon_settings_page_bottom' ); ?>
        </div>
        <?php

    }

    /**
     * Saves form field settings for the addon.
     *
     * @since 1.0.0
     */
    public function save_form() {

        // If the nonce is not correct, return an error.
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'tgm-exchange-getresponse-form' ) ) {
            $this->errors[] = __( 'Are you sure you want to do this? The form nonces do not match. Please try again.', 'LION' );
            return;
        }

        // Sanitize values before saving them to the database.
        $settings     = get_option( 'tgm_exchange_getresponse' );
        $new_settings = stripslashes_deep( $_POST['_tgm_exchange_getresponse'] );

        $settings['getresponse-license-key'] = isset( $new_settings['getresponse-license-key'] ) ? trim( $new_settings['getresponse-license-key'] ) : $settings['getresponse-license-key'];
        $settings['getresponse-api-key'] = isset( $new_settings['getresponse-api-key'] ) ? trim( $new_settings['getresponse-api-key'] ) : $settings['getresponse-api-key'];
        $settings['getresponse-list']    = isset( $new_settings['getresponse-list'] ) ? esc_attr( $new_settings['getresponse-list'] ) : $settings['getresponse-list'];
        $settings['getresponse-label']   = isset( $new_settings['getresponse-label'] ) ? esc_html( $new_settings['getresponse-label'] ) : $settings['getresponse-label'];
        $settings['getresponse-checked'] = isset( $new_settings['getresponse-checked'] ) ? 1 : 0;

        // Save the settings and set flags.
        update_option( 'tgm_exchange_getresponse', $settings );

        if( isset( $_POST['exchange_getresponse_license_activate'] ) ) {

  		    // run a quick security check
  		    if( ! check_admin_referer( 'exchange_getresponse_nonce', 'exchange_getresponse_nonce' ) )
  			    return; // get out if we didn't click the Activate button

  		    // retrieve the license from the database
  		    // $license = trim( get_option( 'exchange_getresponse_license_key' ) );
  		    $exchangewp_getresponse_options = get_option( 'tgm_exchange_getresponse' );
  		    $license = trim( $exchangewp_getresponse_options['getresponse-license-key'] );

  		    // data to send in our API request
  		    $api_params = array(
  			    'edd_action' => 'activate_license',
  			    'license'    => $license,
  			    'item_name'  => urlencode( 'get-response' ), // the name of our product in EDD
  			    'url'        => home_url()
  		    );

  		    // Call the custom API.
  		    $response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

  		    // make sure the response came back okay
  		    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

  			    if ( is_wp_error( $response ) ) {
  				    $message = $response->get_error_message();
  			    } else {
  				    $message = __( 'An error occurred, please try again.' );
  			    }

  		    } else {

  			    $license_data = json_decode( wp_remote_retrieve_body( $response ) );

  			    if ( false === $license_data->success ) {

  				    switch( $license_data->error ) {

  					    case 'expired' :

  						    $message = sprintf(
  							    __( 'Your license key expired on %s.' ),
  							    date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
  						    );
  						    break;

  					    case 'revoked' :

  						    $message = __( 'Your license key has been disabled.' );
  						    break;

  					    case 'missing' :

  						    $message = __( 'Invalid license.' );
  						    break;

  					    case 'invalid' :
  					    case 'site_inactive' :

  						    $message = __( 'Your license is not active for this URL.' );
  						    break;

  					    case 'item_name_mismatch' :

  						    $message = sprintf( __( 'This appears to be an invalid license key for %s.' ), 'getresponse' );
  						    break;

  					    case 'no_activations_left':

  						    $message = __( 'Your license key has reached its activation limit.' );
  						    break;

  					    default :

  						    $message = __( 'An error occurred, please try again.' );
  						    break;
  				    }

  			    }

  		    }

  		    // Check if anything passed on a message constituting a failure
  		    if ( ! empty( $message ) ) {
  			    $base_url = admin_url( 'admin.php?page=' . 'it-exchange-addons&add-on-settings=getresponse' );
  			    $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

  			    wp_redirect( $redirect );
  			    exit();
  		    }

  		    //$license_data->license will be either "valid" or "invalid"
  		    update_option( 'exchange_getresponse_license_status', $license_data->license );

  	    }

  	    // deactivate here
  	    // listen for our activate button to be clicked
  	    if( isset( $_POST['exchange_getresponse_license_deactivate'] ) ) {

  		    // run a quick security check
  		    if( ! check_admin_referer( 'exchange_getresponse_nonce', 'exchange_getresponse_nonce' ) )
  			    return; // get out if we didn't click the Activate button

  		    $exchangewp_getresponse_options = get_option( 'tgm_exchange_getresponse' );
  		    $license = $exchangewp_getresponse_options['getresponse-license-key'];


  		    // data to send in our API request
  		    $api_params = array(
  			    'edd_action' => 'deactivate_license',
  			    'license'    => $license,
  			    'item_name'  => urlencode( 'get-response' ), // the name of our product in EDD
  			    'url'        => home_url()
  		    );
  		    // Call the custom API.
  		    $response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

  		    // make sure the response came back okay
  		    if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

  			    if ( is_wp_error( $response ) ) {
  				    $message = $response->get_error_message();
  			    } else {
  				    $message = __( 'An error occurred, please try again.' );
  			    }

  		    }

  		    // decode the license data
  		    $license_data = json_decode( wp_remote_retrieve_body( $response ) );
  		    // $license_data->license will be either "deactivated" or "failed"
  		    if( $license_data->license == 'deactivated' ) {
  			    delete_option( 'exchange_getresponse_license_status' );
  		    }

  	    }

        return $this->saved = true;

    }

    /**
     * Ajax callback to retrieve lists for the specific account.
     *
     * @since 1.0.0
     */
    public function lists() {

        // Prepare and sanitize variables.
        $api_key = stripslashes( $_POST['api_key'] );

        // Retrieve the lists and die.
        die( $this->get_getresponse_lists( $api_key ) );

    }

    /**
     * Helper flag function to determine if on the addon settings page.
     *
     * @since 1.0.0
     *
     * @return bool True if on the addon page, false otherwise.
     */
    public function is_settings_page() {

        return isset( $_GET['add-on-settings'] ) && 'getresponse' == $_GET['add-on-settings'];

    }

    /**
     * Helper function for retrieving addon settings.
     *
     * @since 1.0.0
     *
     * @param string $setting The setting to look for.
     * @return mixed Addon setting if set, empty string otherwise.
     */
    public function get_setting( $setting = '' ) {

        $settings = get_option( 'tgm_exchange_getresponse' );
        return isset( $settings[$setting] ) ? $settings[$setting] : '';

    }

    /**
     * Helper function to retrieve all available GetResponse lists for the account.
     *
     * @since 1.0.0
     *
     * @param string $api_key The GetResponse API key.
     * @return string An HTML string with lists or empty dropdown.
     */
    public function get_getresponse_lists( $api_key = '' ) {

        // Prepare the HTML holder variable.
        $html = '';

        // If there is no API key, send back an empty placeholder list.
        if ( '' === trim( $api_key ) ) {
            $html .= '<select id="tgm-exchange-getresponse-lists" name="_tgm_exchange_getresponse[getresponse-list]" disabled="disabled">';
                $html .= '<option value="none">' . __( 'No lists to select from at this time.', 'LION' ) . '</option>';
            $html .= '</select>';
            $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
        } else {
            // Load the GetResponse API.
            if ( ! class_exists( 'jsonRPCClient' ) )
                require_once plugin_dir_path( TGM_EXCHANGE_GETRESPONSE_FILE ) . 'lib/getresponse/jsonrpc.php';

            // Try to connect to the API and grab the lists.
            try {
                $api       = new jsonRPCClient( 'http://api2.getresponse.com' );
                $campaigns = $api->get_campaigns( $api_key );
            } catch ( Exception $e ) {
                $html .= '<select id="tgm-exchange-getresponse-lists" class="tgm-exchange-error" name="_tgm_exchange_getresponse[getresponse-list]" disabled="disabled">';
                    $html .= '<option value="none">' . __( 'GetResponse was unable to grant access to your account. Please try again.', 'LION' ) . '</option>';
                $html .= '</select>';
                $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
                return $html;
            }

            // If we have reached this point, send back the lists.
            $html .= '<select id="tgm-exchange-getresponse-lists" name="_tgm_exchange_getresponse[getresponse-list]">';
                foreach ( (array) $campaigns as $id => $data )
                    $html .= '<option value="' . $id . '"' . selected( $id, $this->get_setting( 'getresponse-list' ), false ) . '>' . $data['name'] . '</option>';
            $html .= '</select>';
            $html .= '<img class="tgm-exchange-loading" src="' . includes_url( 'images/wpspin.gif' ) . '" alt="" />';
        }

        // Return the HTML string.
        return $html;

    }

    /**
     * Adds custom action links to the plugin page.
     *
     * @since 1.0.0
     *
     * @param array $links Default action links.
     * @return array $links Amended action links.
     */
    public function plugin_links( $links ) {

        $links['setup_addon'] = '<a href="' . get_admin_url( null, 'admin.php?page=it-exchange-addons&add-on-settings=getresponse' ) . '" title="' . esc_attr__( 'Setup Add-on', 'LION' ) . '">' . __( 'Setup Add-on', 'LION' ) . '</a>';
        return $links;

    }

    /**
     * Outputs the optin checkbox on the appropriate checkout screens.
     *
     * @since 1.0.0
     *
     * @param string $res The password2 field.
     * @return string $res Password2 field with optin code appended.
     */
    public function output_optin( $res ) {

        // Return early if the appropriate settings are not filled out.
        if (  '' === trim( $this->get_setting( 'getresponse-api-key' ) ) )
            return $res;

        // Build the HTML output of the optin.
        $output = $this->get_optin_output();

        // Append the optin output to the password2 field.
        return $res . $output;

    }

    /**
     * Outputs the optin checkbox on the appropriate guest checkout screens.
     *
     * @since 1.0.0
     */
    public function output_optin_guest() {

        // Return early if the appropriate settings are not filled out.
        if (  '' === trim( $this->get_setting( 'getresponse-api-key' ) ) )
            return;

        // Build and echo the HTML output of the optin.
        echo $this->get_optin_output();

    }

    /**
     * Processes the optin to the email service.
     *
     * @since 1.0.0
     */
    public function do_optin() {

        // Return early if the appropriate settings are not filled out.
        if (  '' === trim( $this->get_setting( 'getresponse-api-key' ) ) )
            return;

        // Return early if our $_POST key is not set, no email address is set or the email address is not valid.
        if ( ! isset( $_POST['tgm-exchange-getresponse-signup-field'] ) || empty( $_POST['email'] ) || ! is_email( $_POST['email'] ) )
            return;

        // Load the GetResponse API.
        if ( ! class_exists( 'jsonRPCClient' ) )
            require_once plugin_dir_path( TGM_EXCHANGE_GETRESPONSE_FILE ) . 'lib/getresponse/jsonrpc.php';

        // Try to connect to the API and prepare optin variables.
        $api        = new jsonRPCClient( 'http://api2.getresponse.com' );
        $email      = trim( $_POST['email'] );
        $first_name = ! empty( $_POST['first_name'] ) ? trim( $_POST['first_name'] ) : '';
        $last_name  = ! empty( $_POST['last_name'] )  ? trim( $_POST['last_name'] )  : '';
        $data       = array( 'campaign' => $this->get_setting( 'getresponse-list' ), 'name' => $first_name . ' ' . $last_name, 'email' => $email );
        $data       = apply_filters( 'tgm_exchange_getresponse_optin_data', $data );

        // Process the optin.
        if ( $data ) {
            try {
                $api->add_contact( $this->get_setting( 'getresponse-api-key' ), $data );
            } catch( Exception $e ) {}
        }

    }

    /**
     * Processes the optin to the email service in a guest checkout.
     *
     * @since 1.0.0
     *
     * @param string $email The guest checkout email address.
     */
    public function do_optin_guest( $email ) {

        // Return early if the appropriate settings are not filled out.
        if (  '' === trim( $this->get_setting( 'getresponse-api-key' ) ) )
            return;

        // Load the GetResponse API.
        if ( ! class_exists( 'jsonRPCClient' ) )
            require_once plugin_dir_path( TGM_EXCHANGE_GETRESPONSE_FILE ) . 'lib/getresponse/jsonrpc.php';

        // Try to connect to the API and prepare optin variables.
        $api  = new jsonRPCClient( 'http://api2.getresponse.com' );
        $data = array( 'campaign' => $this->get_setting( 'getresponse-list' ), 'email' => $email );
        $data = apply_filters( 'tgm_exchange_getresponse_optin_data', $data );

        // Process the optin.
        if ( $data ) {
            try {
                $api->add_contact( $this->get_setting( 'getresponse-api-key' ), $data );
            } catch( Exception $e ) {}
        }

    }

    /**
     * Generates and returns the optin output.
     *
     * @since 1.0.0
     *
     * @return string $output HTML string of optin output.
     */
    public function get_optin_output() {

        $output  = '<div class="tgm-exchange-getresponse-signup" style="clear:both;">';
            $output .= '<label for="tgm-exchange-getresponse-signup-field">';
                $output .= '<input type="checkbox" id="tgm-exchange-getresponse-signup-field" name="tgm-exchange-getresponse-signup-field" value="' . $this->get_setting( 'getresponse-checked' ) . '"' . checked( $this->get_setting( 'getresponse-checked' ), 1, false ) . ' />' . $this->get_setting( 'getresponse-label' );
            $output .= '</label>';
        $output .= '</div>';
        $output  = apply_filters( 'tgm_exchange_getresponse_output', $output );

        return $output;

    }

}

// Initialize the plugin.
global $tgm_exchange_getresponse;
$tgm_exchange_getresponse = TGM_Exchange_GetResponse::get_instance();
