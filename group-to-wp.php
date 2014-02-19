<?php
/*
Plugin Name: Facebook Group to WordPress importer
Description: Import facebook group posts to WordPress
Version: 0.1
Author: Tharshan Muthulingam, Daniel Koehler and Tareq Hasan
License: GPL2
*/

/**
 * Copyright (c) 2014 Tareq Hasan (email: tareq@wedevs.com). All rights reserved.
 * Modifications made by Tharshan Muthulingam and Daniel Koehler
 * Released under the GPL license
 * http://www.opensource.org/licenses/gpl-license.php
 *
 * This is an add-on for WordPress
 * http://wordpress.org/
 *
 * **********************************************************************
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * **********************************************************************
 */

// don't call the file directly
if ( !defined( 'ABSPATH' ) ) exit;

if ( is_admin() ) {
    require_once dirname( __FILE__ ) . '/includes/admin.php';
}
// Cardiff Start GID: 100274573409031
// WeDevs_FB_Group_To_WP::init()->trash_all();

/**
 * WeDevs_FB_Group_To_WP class
 *
 * @class WeDevs_FB_Group_To_WP The class that holds the entire WeDevs_FB_Group_To_WP plugin
 */
class WeDevs_FB_Group_To_WP {

    private $post_type = 'fb_group_post';

    /**
     * Constructor for the WeDevs_FB_Group_To_WP class
     *
     * Sets up all the appropriate hooks and actions
     * within our plugin.
     *
     * @uses register_activation_hook()
     * @uses register_deactivation_hook()
     * @uses is_admin()
     * @uses add_action()
     */
    public function __construct() {
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        // Localize our plugin
        add_action( 'init', array( $this, 'localization_setup' ) );
        add_action( 'init', array( $this, 'debug_run' ) );
        add_action('init', array($this, 'publish_post'));
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action('init',array($this, 'add_categories_to_cpt'));
        add_action( 'fbgr2wp_import', array( $this, 'do_import' ) );
        add_filter( 'the_content', array( $this, 'the_content' ) );
        add_filter( 'pre_get_posts', array($this, 'my_get_posts') );
        if ( is_admin() ) {
            new WeDevs_FB_Group_To_WP_Admin();
        }
    }
    public function add_categories_to_cpt(){
        register_taxonomy_for_object_type('category', 'fb_group_post');
    }
    /**
     * Registers our custom post type
     * 
     * @return void 
     */
    public function register_post_type() {
        $labels = array(
            'name'                => _x( 'Group Posts', 'Post Type General Name', 'fbgr2wp' ),
            'singular_name'       => _x( 'Group Post', 'Post Type Singular Name', 'fbgr2wp' ),
            'menu_name'           => __( 'FB Group Posts', 'fbgr2wp' ),
            'parent_item_colon'   => __( 'Parent Post:', 'fbgr2wp' ),
            'all_items'           => __( 'All Posts', 'fbgr2wp' ),
            'view_item'           => __( 'View Post', 'fbgr2wp' ),
            'add_new_item'        => __( 'Add New Post', 'fbgr2wp' ),
            'add_new'             => __( 'Add New', 'fbgr2wp' ),
            'edit_item'           => __( 'Edit Post', 'fbgr2wp' ),
            'update_item'         => __( 'Update Post', 'fbgr2wp' ),
            'search_items'        => __( 'Search Post', 'fbgr2wp' ),
            'not_found'           => __( 'Not found', 'fbgr2wp' ),
            'not_found_in_trash'  => __( 'Not found in Trash', 'fbgr2wp' ),
        );

        $rewrite = array(
            'slug'                => 'fb-post',
            'with_front'          => true,
            'pages'               => true,
            'feeds'               => false,
        );

        $args = array(
            'label'               => __( 'fb_group_post', 'fbgr2wp' ),
            'description'         => __( 'WordPress Group Post', 'fbgr2wp' ),
            'labels'              => $labels,
            'supports'            => array( 'title', 'editor', 'post-formats', ),
            'taxonomies'          => array( 'category', 'post_tag' ),
            'hierarchical'        => false,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'can_export'          => true,
            'has_archive'         => true,
            'exclude_from_search' => false,
            'publicly_queryable'  => true,
            'rewrite'             => $rewrite,
            'capability_type'     => 'post',
        );

        register_post_type( $this->post_type, $args );
        

    }

    /**
     * Initializes the WeDevs_FB_Group_To_WP() class
     *
     * Checks for an existing WeDevs_FB_Group_To_WP() instance
     * and if it doesn't find one, creates it.
     */
    public static function init() {
        static $instance = false;

        if ( ! $instance ) {
            $instance = new WeDevs_FB_Group_To_WP();
        }

        return $instance;
    }
    /**
     * Placeholder for activation function
     *
     * Nothing being called here yet.
     */
    public function activate() {
        if ( false == wp_next_scheduled( 'fbgr2wp_import' ) ){
            wp_schedule_event( time(), 'hourly', 'fbgr2wp_import' );
        }
        wp_create_category('Cardiff Start Facebook Posts');
    }

    /**
     * Placeholder for deactivation function
     *
     * Nothing being called here yet.
     */
    public function deactivate() {
        wp_clear_scheduled_hook( 'fbgr2wp_import' );
        $this->trash_all(); //FIX ME. This is just for development.
    }

    /**
     * Initialize plugin for localization
     *
     * @uses load_plugin_textdomain()
     */
    public function localization_setup() {
        load_plugin_textdomain( 'fbgr2wp', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    function debug_run() {
        
        if ( !isset( $_GET['fb2wp_test'] ) ) {
            return;
        }

        $this->do_import();

        die();
    }
    /**
    * This should be used to publish a draft Facebook post
    * The GET param should contain the value of the wordpress post ID
    * These links are followed through by Email, and are generated every 2 days by a cron job that looks for draft posts or after import
    * Email is sent to admins
    */
    function publish_post() {
        if ( isset( $_GET['fb_post_publish'] ) ) {
            $my_post = array(
              'ID'           => $_GET['fb_post_publish'],
              'post_status'  => 'publish',
            );
            //update the custom post type with this category and append it, since as a draft it has not category.
            //publish the post
            wp_update_post($my_post);
        }
        if ( isset( $_GET['fb_send_mail'] ) ) {
            $this->send_mail();
        }
    }
    function send_mail() {
        $query = new WP_Query( array( 'post_type' => $this->post_type, 'posts_per_page' => -1, 'post_status' => 'draft' ) );
        if ( $query->have_posts()) {
            // get html file contents into string
            $html = file_get_contents(dirname( __FILE__ ) . '/includes/html_template.html');
            $find = array(
                 '#title#',
                 '#header#',
                 '#message#'
            );
            $message = '';
            $template = '<h2>%s</h2><h4>%s</h4><a href="%s" target="_blank">Publish</a><br /><br />';
            $all_posts = $query->get_posts();
            $count_posts = count($all_posts);
            foreach ($all_posts as $post) {
                self::log('debug', print_r(get_site_url().'/?fb_post_publish='.$post->ID, TRUE));            
                $message = $message.sprintf($template, $post->post_title, substr($post->post_content,0, 80), get_site_url().'/?fb_post_publish='.$post->ID);
            }
            $search = array(
                 $count_posts.' New Cardiff Start Facebook Posts',
                 $count_posts.' New Cardiff Start Facebook Posts',
                 $message
            );
            $content = str_replace($find, $search, $html);
            $multiple_to_recipients = array(
                'tharshan09@gmail.com'
            );

            add_filter( 'wp_mail_content_type', function($content_type){
                return 'text/html';
            });
            wp_mail( $multiple_to_recipients, $count_posts.' Cardiff Start Facebook Posts', $content );

            // Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
            remove_filter( 'wp_mail_content_type', function($content_type){
                return 'text/html';
            });

            self::log('debug', 'Email sent');
        }
    }
    function get_settings() {
        $option = get_option( 'fbgr2wp_settings', array() );

        // return if no configuration found
        if ( !isset( $option['app_id'] ) || !isset( $option['app_secret'] ) || !isset( $option['group_id'] ) ) {
            return false;
        }

        // no app id or app secret
        if ( empty( $option['app_id'] ) || empty( $option['app_secret'] ) ) {
            return false;
        }

        // no group id
        if ( empty( $option['group_id'] ) ) {
            return false;
        }
        
        return $option;
    }

    /**
     * Do the actual import via cron
     * 
     * @return boolean
     */
    function do_import() {
        $option = $this->get_settings();
        
        if ( !$option ) {
            return;
        }

        $access_token = $option['app_id'] . '|' . $option['app_secret'];
        $group_id = $option['group_id'];
        $url = 'https://graph.facebook.com/' . $group_id . '/feed/?access_token=' . $access_token;

        $json_posts = $this->fetch_stream( $url );

        if ( !$json_posts ) {
            return;
        }

        $decoded = json_decode( $json_posts );
        $group_posts = $decoded->data;
        $paging = $decoded->paging;

        $count = $this->insert_posts( $group_posts, $group_id, 'draft' );

        // printf( '%d posts imported', $count );
    }

   function do_import_all() {
        
        $option = $this->get_settings();
        
        if ( !$option ) {
            return;
        }

        $access_token = $option['app_id'] . '|' . $option['app_secret'];
        $group_id = $option['group_id'];
        
        $count = 0;

        $url = 'https://graph.facebook.com/' . $group_id . '/feed/?limit=25&access_token=' . $access_token;
        // echo $url;
        do {
            $json_posts = $this->fetch_stream( $url );
            if ( !$json_posts ) {
                return;
            }

            $decoded = json_decode( $json_posts );
            $group_posts = $decoded->data;
            
            // echo  count($group_posts)."<br>";

            $count += $this->insert_posts( $group_posts, $group_id, 'publish' );

        } while (property_exists($decoded, 'paging') && $url = $decoded->paging->next);


        printf( '(%d new posts imported)', $count );
    }
    
    function fetch_stream( $url ) {
        self::log( 'debug', 'Fetching data from facebook' );
        
        $request = wp_remote_get( $url );
        $json_posts = wp_remote_retrieve_body( $request );

        if ( is_wp_error( $request ) ) {
            $error_message = $request->get_error_message();
            self::log( 'error', 'Fetching failed with code. WP_Error '.$error_message );
            return;
        }
        
        if ( $request['response']['code'] != 200 ) {
            self::log( 'error', 'Fetching failed with code: ' . $request['response']['code'] );
            return false;
        }
        
        return $json_posts;
    }
    
    /**
     * Loop through the facebook feed and insert them
     * 
     * @param array $group_posts
     * @return int
     */
    function insert_posts( $group_posts, $group_id, $status ) {
        $count = 0;
        
        if ( $group_posts ) {
            foreach ($group_posts as $fb_post) {
                $post_id = $this->insert_post( $fb_post, $group_id, $status);
                if ( $post_id ) {
                    $count++;
                }
            }
        }
        
        return $count;
    }

    /**
     * Check if the post already exists
     * 
     * Checks via guid. guid = fb post link
     * 
     * @global object $wpdb
     * @param string $fb_link_id facebook post link
     * @return boolean
     */
    function is_post_exists( $fb_post ) {
        global $wpdb;

        // $row = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid = %s AND post_status = 'publish'", $fb_link_id ) );
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid = %s", $fb_post->actions[0]->link  ) );

        if ( $row ) {
            //post exists so we need to update comments
            if (property_exists($fb_post, 'comments')) {
                $comments = $this->insert_comments($row->ID, $fb_post->comments->data);
            }
            return true;
        }

        return false;
    }

    function is_comment_exists($fb_comment_id) {
        global $wpdb;

        $row = $wpdb->get_row( $wpdb->prepare( "SELECT meta_id FROM $wpdb->commentmeta WHERE meta_key = '_fb_comment_id' AND meta_value = %s", $fb_comment_id) );
        if ( $row ) {
            return true;
        }

        return false;
    }

    /**
     * Insert a new imported post from facebook
     * 
     * @param object $fb_post
     * @param int $group_id
     * @return int|WP_Error
     */
    function insert_post( $fb_post, $group_id, $status ) {

        // bail out if the post already exists
        if ( $this->is_post_exists( $fb_post)) {
            return;
        }

        $postarr = array(
            'post_type' => $this->post_type,
            'post_status' => $status,
            'post_category' => array(get_cat_ID('Cardiff Start Facebook Posts')),
            'post_author' => 1,
            'post_date' => gmdate( 'Y-m-d H:i:s', strtotime( $fb_post->updated_time ) ),
            'guid' => $fb_post->actions[0]->link,
            'comment_status' => 'open'
        );

        if (!property_exists($fb_post, 'message')) {
            return;
        }

        $meta = array(
            '_fb_author_id' => $fb_post->from->id,
            '_fb_author_name' => $fb_post->from->name,
            '_fb_link' => $fb_post->actions[0]->link,
            '_fb_group_id' => $group_id,
            '_fb_post_id' => $fb_post->id
        );

        switch ($fb_post->type) {
            case 'status':

                $postarr['post_title'] = wp_trim_words( strip_tags( $fb_post->message ), 6, '...' );
                $postarr['post_content'] = $fb_post->message;
            
                break;

            case 'photo':

                if ( !isset( $fb_post->message ) ) {
                    $postarr['post_title'] = wp_trim_words( strip_tags( $fb_post->story ), 6, '...' );
                    $postarr['post_content'] = sprintf( '<p>%1$s</p> <div class="image-wrap"><img src="%2$s" alt="%1$s" /></div>', $fb_post->story, $fb_post->picture );
                } else {
                    $postarr['post_title'] = wp_trim_words( strip_tags( $fb_post->message ), 6, '...' );
                    $postarr['post_content'] = sprintf( '<p>%1$s</p> <div class="image-wrap"><img src="%2$s" alt="%1$s" /></div>', $fb_post->message, $fb_post->picture );
                }

                break;

            case 'link':

                $parsed_link = false;

                if (property_exists($fb_post, 'picture')){
                    parse_str( $fb_post->picture, $parsed_link );
                }

                $postarr['post_title'] = wp_trim_words( strip_tags( $fb_post->message ), 6, '...' );
                $postarr['post_content'] = '<p>' . $fb_post->message . '</p>';

                if ( !empty( $parsed_link['url']) ) {
                    $postarr['post_content'] .= sprintf( '<a href="%s"><img src="%s"></a>', $fb_post->link, $parsed_link['url'] );
                } else if (property_exists($fb_post, 'name')) {
                    $postarr['post_content'] .= sprintf( '<a href="%s">%s</a>', $fb_post->link, $fb_post->name );
                }

                break;

            default:
                # code...
                break;
        }

        $post_id = wp_insert_post( $postarr );
        if ($status == 'draft')  {
            wp_set_object_terms( $post_id, array(get_cat_ID('Cardiff Start Facebook Posts')), 'category',true );
        }
        if ( $post_id && !is_wp_error( $post_id ) ) {

            if ( $fb_post->type !== 'status' ) {
                set_post_format( $post_id, $fb_post->type );
            }

            foreach ($meta as $key => $value) {
                update_post_meta( $post_id, $key, $value );
            }
            //post is new so we need to insert comments
            if (property_exists($fb_post, 'comments')) {
                $comments = $this->insert_comments($post_id, $fb_post->comments->data);
            }
        }

        // var_dump( $fb_post );
        // self::log('debug', print_r($fb_post, TRUE));
        // var_dump( $meta );

        // self::log('debug','post is being inserted');
        return $post_id;
    }

    function insert_comments($post_id, $fb_comments) {
        $count = 0;
        
        if ( $fb_comments ) {
            foreach ($fb_comments as $fb_comment) {

                $comment_id = $this->insert_comment($post_id, $fb_comment );
                if ( $comment_id ) {
                    $count++;
                }
            }
        }
        
        return $count;
    }

    function insert_comment($post_id, $fb_comment) {

        // bail out if the post already exists
        if ( $this->is_comment_exists( $fb_comment->id )) {
            return;
        }

        $commentarr = array(
            'comment_post_ID' => $post_id,
            'comment_author' => $fb_comment->from->name,
            'comment_author_url' => 'http://facebook.com/'.$fb_comment->from->id,
            'comment_content' => $fb_comment->message,
            'comment_date' => gmdate( 'Y-m-d H:i:s', strtotime( $fb_comment->created_time ) ),
            'comment_approved' => 1,
        );
        $meta = array(
            '_fb_author_id' => $fb_comment->from->id,
            '_fb_author_name' => $fb_comment->from->name,
            '_fb_comment_id' => $fb_comment->id
        );

        $comment_id = wp_insert_comment($commentarr);
        if ( $comment_id && !is_wp_error( $comment_id ) ) {
            foreach ($meta as $key => $value) {
                update_comment_meta( $comment_id, $key, $value );
            }
        }
        return $comment_id;
    }


    /**
     * Trash all imported posts
     * 
     * @return void
     */
    function trash_all() {
        $query = new WP_Query( array( 'post_type' => $this->post_type, 'posts_per_page' => -1 ) );

        if ( $query->have_posts()) {
            $all_posts = $query->get_posts();

            foreach ($all_posts as $post) {
                $c_query = new WP_Comment_Query();
                $comments = $c_query->query(array('post_id'=>$post->ID));
                if ($comments) {
                    foreach($comments as $comment) {

                        delete_comment_meta($comment->comment_ID,'_fb_author_id');
                        delete_comment_meta($comment->comment_ID,'_fb_author_name');
                        delete_comment_meta($comment->comment_ID,'_fb_comment_id');
                        wp_delete_comment($comment->comment_ID, true);
                    }
                }
                delete_post_meta($post->ID, '_fb_author_id');
                delete_post_meta($post->ID, '_fb_author_name');
                delete_post_meta($post->ID, '_fb_link');
                delete_post_meta($post->ID, '_fb_group_id');
                delete_post_meta($post->ID, '_fb_post_id');
                wp_delete_post( $post->ID, true );
            }
        }

    }
    // this function adds our custom page to the home page.
    function my_get_posts( $query ) {
        if ( (is_home() || is_category()) && $query->is_main_query()) {

            $query->set( 'post_type', array( 'post', 'fb_group_post' ) );
            
        }
        return $query;
    }
    /**
     * Adds author, post and group link to the end of the post
     * 
     * @global object $post
     * @param string $content
     * @return string
     */
    function the_content( $content ) {
        global $post;

        if ( $post->post_type == $this->post_type ) {
            $author_id = get_post_meta( $post->ID, '_fb_author_id', true );
            $author_name = get_post_meta( $post->ID, '_fb_author_name', true );
            $link = get_post_meta( $post->ID, '_fb_link', true );
            $group_id = get_post_meta( $post->ID, '_fb_group_id', true );

            $author_link = sprintf( '<a href="https://facebook.com/profile.php?id=%d" target="_blank">%s</a>', $author_id, $author_name );

            $custom_data = '<div class="fb-group-meta">';
            $custom_data .= sprintf( __( 'Posted by %s', 'fbgr2wp' ), $author_link );
            $custom_data .= '<span class="sep"> | </span>';
            $custom_data .= sprintf( '<a href="%s" target="_blank">%s</a>', $link, __( 'View Post', 'fbgr2wp' ) );
            $custom_data .= '<span class="sep"> | </span>';
            $custom_data .= sprintf( '<a href="https://facebook.com/groups/%s" target="_blank">%s</a>', $group_id, __( 'View Group', 'fbgr2wp' ) );
            $custom_data .= '</div>';

            $content .= $custom_data;
        }

        return $content;
    }

    /**
     * The main logging function
     *
     * @uses error_log
     * @param string $type type of the error. e.g: debug, error, info
     * @param string $msg
     */
    public static function log( $type = '', $msg = '' ) {
        if ( WP_DEBUG == true ) {
            $msg = sprintf( "[%s][%s] %s\n", date( 'd.m.Y h:i:s' ), $type, $msg );
            error_log( $msg, 3, dirname( __FILE__ ) . '/debug.log' );
        }
    }

} // WeDevs_FB_Group_To_WP

$wp_fb_import = WeDevs_FB_Group_To_WP::init();
