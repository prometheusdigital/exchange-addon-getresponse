<?php
/**
 * iThemes Exchange - GetResponse Add-on class.
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
    public $plugin_name = 'iThemes Exchange - GetResponse Add-on';

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
    public $domain = 'tgm-exchange-getresponse';

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
        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

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

        // Register the plugin updater.
        add_action( 'ithemes_updater_register', array( $this, 'updater' ) );

        // Load admin assets.
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_styles' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Utility actions.
        add_filter( 'plugin_action_links_' . plugin_basename( TGM_EXCHANGE_GETRESPONSE_FILE ), array( $this, 'plugin_links' ) );
        add_filter( 'it_exchange_theme_api_registration_password2', array( $this, 'output_optin' ) );
        add_action( 'it_exchange_register_user', array( $this, 'do_optin' ) );

    }

    /**
     * Initializes the plugin updater for the addon.
     *
     * @since 1.0.0
     */
    public function updater( $updater ) {

        // Return early if not in the admin.
        if ( ! is_admin() ) return;

        // Load the updater class.
        require_once dirname( __FILE__ ) . '/lib/updater/load.php';

        // Register the addon with the updater.
        $updater->register( 'exchange-addon-getresponse', TGM_EXCHANGE_GETRESPONSE_FILE );

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
            printf( __( 'To use the GetResponse add-on for iThemes Exchange, you must be using iThemes Exchange version 1.0.3 or higher. <a href="%s">Please update now</a>.', 'tgm-exchange-getresponse' ), admin_url( 'update-core.php' ) );
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
            <h2><?php _e( 'GetResponse Settings', 'tgm-exchange-getresponse' ); ?></h2>

            <?php if ( ! empty( $this->errors ) ) : ?>
                <div id="message" class="error"><p><strong><?php echo implode( '<br>', $this->errors ); ?></strong></p></div>
            <?php endif; ?>

            <?php if ( $this->saved ) : ?>
                <div id="message" class="updated"><p><strong><?php _e( 'Your settings have been saved successfully!', 'tgm-exchange-getresponse' ); ?></strong></p></div>
            <?php endif; ?>

            <?php do_action( 'it_exchange_getresponse_settings_page_top' ); ?>
            <?php do_action( 'it_exchange_addon_settings_page_top' ); ?>

            <div class="tgm-exchange-getresponse-settings">
                <p><?php _e( 'To setup GetResponse in Exchange, fill out the settings below.', 'tgm-exchange-getresponse' ); ?></p>
                <form class="tgm-exchange-getresponse-form" action="admin.php?page=it-exchange-addons&add-on-settings=getresponse" method="post">
                    <?php wp_nonce_field( 'tgm-exchange-getresponse-form' ); ?>
                    <input type="hidden" name="tgm-exchange-getresponse-form" value="1" />

                    <table class="form-table">
                        <tbody>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-getresponse-api-key"><strong><?php _e( 'GetResponse API Key', 'tgm-exchange-getresponse' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-getresponse-api-key" type="password" name="_tgm_exchange_getresponse[getresponse-api-key]" value="<?php echo $this->get_setting( 'getresponse-api-key' ); ?>" placeholder="<?php esc_attr_e( 'Enter your GetResponse API key here.', 'tgm-exchange-getresponse' ); ?>" />
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-getresponse-lists"><strong><?php _e( 'GetResponse List', 'tgm-exchange-getresponse' ); ?></strong></label>
                                </th>
                                <td>
                                    <div class="tgm-exchange-getresponse-list-output">
                                        <?php echo $this->get_getresponse_lists( $this->get_setting( 'getresponse-api-key' ) ); ?>
                                    </div>
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-getresponse-label"><strong><?php _e( 'GetResponse Label', 'tgm-exchange-getresponse' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-getresponse-label" type="text" name="_tgm_exchange_getresponse[getresponse-label]" value="<?php echo $this->get_setting( 'getresponse-label' ); ?>" placeholder="<?php esc_attr_e( 'Enter your GetResponse checkbox label here.', 'tgm-exchange-getresponse' ); ?>" />
                                </td>
                            </tr>
                            <tr valign="middle">
                                <th scope="row">
                                    <label for="tgm-exchange-getresponse-checked"><strong><?php _e( 'Check GetResponse box by default?', 'tgm-exchange-getresponse' ); ?></strong></label>
                                </th>
                                <td>
                                    <input id="tgm-exchange-getresponse-checked" type="checkbox" name="_tgm_exchange_getresponse[getresponse-checked]" value="<?php echo (bool) $this->get_setting( 'getresponse-checked' ); ?>" <?php checked( $this->get_setting( 'getresponse-checked' ), 1 ); ?> />
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <?php submit_button( __( 'Save Changes', 'tgm-exchange-getresponse' ), 'primary button-large', '_tgm_exchange_getresponse[save]' ); ?>
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
            $this->errors[] = __( 'Are you sure you want to do this? The form nonces do not match. Please try again.', 'tgm-exchange-getresponse' );
            return;
        }

        // Sanitize values before saving them to the database.
        $settings     = get_option( 'tgm_exchange_getresponse' );
        $new_settings = stripslashes_deep( $_POST['_tgm_exchange_getresponse'] );

        $settings['getresponse-api-key']  = isset( $new_settings['getresponse-api-key'] ) ? trim( $new_settings['getresponse-api-key'] ) : $settings['getresponse-api-key'];
        $settings['getresponse-list']     = isset( $new_settings['getresponse-list'] ) ? esc_attr( $new_settings['getresponse-list'] ) : $settings['getresponse-list'];
        $settings['getresponse-label']    = isset( $new_settings['getresponse-label'] ) ? esc_html( $new_settings['getresponse-label'] ) : $settings['getresponse-label'];
        $settings['getresponse-checked']  = isset( $new_settings['getresponse-checked'] ) ? 1 : 0;

        // Save the settings and set flags.
        update_option( 'tgm_exchange_getresponse', $settings );
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
                $html .= '<option value="none">' . __( 'No lists to select from at this time.', 'tgm-exchange-getresponse' ) . '</option>';
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
                    $html .= '<option value="none">' . __( 'GetResponse was unable to grant access to your account. Please try again.', 'tgm-exchange-getresponse' ) . '</option>';
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

        $links['setup_addon'] = '<a href="' . get_admin_url( null, 'admin.php?page=it-exchange-addons&add-on-settings=getresponse' ) . '" title="' . esc_attr__( 'Setup Add-on', 'tgm-exchange-getresponse' ) . '">' . __( 'Setup Add-on', 'tgm-exchange-getresponse' ) . '</a>';
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
        $output  = '<div class="tgm-exchange-getresponse-signup">';
            $output .= '<label for="tgm-exchange-getresponse-signup-field">';
                $output .= '<input type="checkbox" id="tgm-exchange-getresponse-signup-field" name="tgm-exchange-getresponse-signup-field" value="' . $this->get_setting( 'getresponse-checked' ) . '"' . checked( $this->get_setting( 'getresponse-checked' ), 1, false ) . ' />' . $this->get_setting( 'getresponse-label' );
            $output .= '</label>';
        $output .= '</div>';
        $output  = apply_filters( 'tgm_exchange_getresponse_output', $output );

        // Append the optin output to the password2 field.
        return $res . $output;

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
        if ( $data )
            $api->add_contact( $this->get_setting( 'getresponse-api-key' ), $data );

    }

}

// Initialize the plugin.
global $tgm_exchange_getresponse;
$tgm_exchange_getresponse = TGM_Exchange_GetResponse::get_instance();