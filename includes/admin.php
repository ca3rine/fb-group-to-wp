<?php

require_once dirname( __FILE__ ) . '/class.settings-api.php';

/**
 * Admin options handler class
 *
 * @since 0.4
 * @author Tareq Hasan <tareq@wedevs.com>
 */
class WeDevs_FB_Group_To_WP_Admin {

    private $settings_api;

    function __construct() {
        $this->settings_api = new WeDevs_Settings_API();

        add_action( 'admin_init', array($this, 'admin_init') );
        add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() {
        add_options_page( __( 'Facebook Group to WordPress Importer', 'fbgr2wp' ), __( 'FB Group to WP', 'fbgr2wp' ), 'manage_options', 'fbgr2wp-settings', array( $this, 'settings_page' ) );
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id' => 'fbgr2wp_settings',
                'title' => __( 'Settings', 'cpm' )
            )
        );

        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $settings_fields = array();
        $settings_fields['fbgr2wp_settings'] = array(
            array(
                'name' => 'app_id',
                'label' => __( 'Facebook App ID', 'cpm'),
                'default' => '',
                'desc' => sprintf( __( 'Insert your facebook application ID from <a href="%s">here</a>.', 'fbgr2wp' ), 'https://developers.facebook.com/apps/' )
            ),
            array(
                'name' => 'app_secret',
                'label' => __( 'Facebook App Secret', 'cpm'),
                'default' => '',
                'desc' => __( 'Insert your facebook App Secret' )
            ),
            array(
                'name' => 'group_id',
                'label' => __( 'Facebook Group ID', 'fbgr2wp'),
                'default' => '',
                'desc' => __( 'Add your facebook group ID. e.g: 100274573409031' )
            )
        );

        return $settings_fields;
    }

    function current_action() {
        if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
            return $_REQUEST['action'];

        if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
            return $_REQUEST['action2'];

        return false;
    }

    function process_given_actions()
    {
        try {
            switch ($this->current_action()){
                case 'fullImport':
                    // die('fullImport');
                    $wp_fb_import = WeDevs_FB_Group_To_WP::init();
                    $wp_fb_import->do_import_all();
                    break;
            }
        } catch (Exception $e){
            $this->addNotice('error', $e->getMessage());
        }
    }

    function settings_page() {

        $this->process_given_actions();

        echo '<div class="wrap">';
        
        settings_errors();

        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        echo "<div class='metabox-holder'>
                <div class='postbox'>
                    <form method='post'>
                    <h3>Master Actions</h3>
                        <input type='hidden' name='action' value='fullImport'/>
                        <table class='form-table'><tbody><tr valign='top'><th scope='row'>Retrive All Historical Data</th><td><input class='button button-primary' type='submit' name='doaction2' value='Perform Master Import'/></td></tr></tbody></table>
                    </form>
                </div>
            </div>
        </div>";
    }
}
