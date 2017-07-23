<?php
/**
 * Plugin Name: Wordpress Internal Retargeting
 * Plugin URI: https://jqueryplugins.net/wordpress-internal-re-targeting/
 * Description: This plugin allows you to easilly set up a goal page then target users who visit that page, don't convert, but still stay on your website with a message of your choice to get them to reconsider visitng the goal page.
 * Version: 1.0
 * Author: jqueryplugins.net
 * Author URI: https://jqueryplugins.net/
 * License: GPL2
 * Text Domain: wp_internal_retargeting
 */

add_action( 'wp', 'wp_ir_check_set_cookie' );
function wp_ir_check_set_cookie(){ 
  $gtf = get_option( 'goal_text_field' );
  $goalpages = explode(',', $gtf );
  $pid = get_the_ID();
  if (in_array($pid , $goalpages)) {
    //check if visited cookie exists
     if(!isset($_COOKIE['wp_ir_v'])) {
       //make sure were not on the frontpage
      if (!is_front_page()) {	
        $domain = ($_SERVER['HTTPS_HOST'] != 'localhost') ? $_SERVER['HTTPS_HOST'] : true;  
       	setcookie('wp_ir_v', 'diversity', time()+60*60*24*365, '/', $domain, true);}
       }else {
       //cookie is set - don't need to do anything
         }
}
}

function wordpress_internal_retargeting_shortcode() {
  // output retargeting message if visitor was in funnel but has fallen out.
   //cookie logic
  $gtf = get_option( 'goal_text_field' );
      $goalpages = explode(',', $gtf );
  $pid = get_the_ID();
      //check if we are in the funnel
   if (in_array($pid , $goalpages)) {
     // do nothing
   }else { //not in goalpages array
      if(!isset($_COOKIE['wp_ir_v'])) {
       //cookie not set - no need to do anything
       }else {
 //yes - not in array and cookie is set, indicating they were in the funnel - display message
 $message = get_option( 'retargeting_textarea' );
        echo $message;
         }
   }
}

function wordpress_internal_retargeting_set_success_shortcode() {
  //success page indicator - deletes cookies
  $domain = ($_SERVER['HTTPS_HOST'] != 'localhost') ? $_SERVER['HTTPS_HOST'] : true;
  setcookie('wp_ir_v', 'education', time()-3600, '/', $domain, true);
 }

class wp_internal_retargeting_Plugin {
    public function __construct() {
    	// Hook into the admin menu
    	add_action( 'admin_menu', array( $this, 'create_plugin_settings_page' ) );
        // Add Settings and Fields
    	add_action( 'admin_init', array( $this, 'setup_sections' ) );
    	add_action( 'admin_init', array( $this, 'setup_fields' ) );
      // add shortcodes
      add_shortcode( 'wp-ir-message', 'wordpress_internal_retargeting_shortcode' );
      add_shortcode( 'wp-ir-success', 'wordpress_internal_retargeting_set_success_shortcode' );
    }
    public function create_plugin_settings_page() {
    	 // Add the menu item and page
      $page_title = 'Wordpress Internal Retargeting Settings Page';
      $menu_title = 'Retargeting';
      $capability = 'manage_options';
      $slug = 'wp_internal_fields';
      $callback = array( $this, 'plugin_settings_page_content' );
      $icon = 'dashicons-admin-plugins';
      $position = 100;
      add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
    }
    public function plugin_settings_page_content() {?>
    	<div class="wrap">
    		<h2>Wordpress Internal Retargeting Settings Page</h2><?php
            if ( isset( $_GET['settings-updated'] ) && $_GET['settings-updated'] ){
                  $this->admin_notice();
            } ?>
    		<form method="POST" action="options.php">
                <?php
                    settings_fields( 'wp_internal_fields' );
                    do_settings_sections( 'wp_internal_fields' );
                    submit_button();
                ?>
    		</form>
    	 <?php
      	   echo '<h2>Plugin Shortcodes</h2>';
      	   echo '<strong>Retargeting Message Shortcode:</strong> [wp-ir-message]<ul><li>Add shortcode to any page outside of your goal funnel.</li>';
      echo '<li>This shortcode will display your retargeting message if the user has fallen out of goal funnel.</li></ul><hr />';
           echo '<strong>Success Shortcode:</strong> [wp-ir-success]<ul><li>Add shortcode to your thank you page or other success indicator page.</li>';
           echo '<li>The success shortcode clears user cookies so that retargeting message is no longer displayed</li></ul></div>';
      echo '<h2>Tutorial: How to use this plugin</h2> Please visit: <a href="https://jqueryplugins.net/wordpress-internal-re-targeting/">jqueryplugins.net/wordpress-internal-re-targeting/</a> for a detailed walkthrough of this plugin';
    }
    
    public function admin_notice() { ?>
        <div class="notice notice-success is-dismissible">
            <p>Your settings have been updated!</p>
        </div><?php
    }
    public function setup_sections() {
        add_settings_section( 'our_first_section', 'Set Goal Page', array( $this, 'section_callback' ), 'wp_internal_fields' );
        add_settings_section( 'our_second_section', 'Set Retargeting Message Shortcode Contents', array( $this, 'section_callback' ), 'wp_internal_fields' );      
    }
    public function section_callback( $arguments ) {
    	 switch( $arguments['id'] ){
        case 'our_first_section':
           
          echo 'Choose your goal pages';
          break;
        case 'our_second_section':
          echo 'Goal Message Output';
          break;
          
        }
    }
    public function setup_fields() {
       $fields = array(
          array(
            'uid' => 'goal_text_field',
            'label' => 'Enter Goal Page - Page ID',
            'section' => 'our_first_section',
            'type' => 'text',
            'placeholder' => '321',
            'helper' => 'Separate multiple pages with a comma',
            'supplimental' => 'Note: Blog Front Page can not be set as a goal page.',
          ),
          array(
            'uid' => 'retargeting_textarea',
            'label' => 'Enter Retargeting Shortcode Message',
            'section' => 'our_second_section',
            'type' => 'textarea',
              'placeholder' => '<a href=><img src=></a>',
                'supplimental' => 'HTML PHP or javascript can be entered.',
          )
          
          
        );
      foreach( $fields as $field ){
          add_settings_field( $field['uid'], $field['label'], array( $this, 'field_callback' ), 'wp_internal_fields', $field['section'], $field );
            register_setting( 'wp_internal_fields', $field['uid'] );
      }
    }
    public function field_callback( $arguments ) {
        $value = get_option( $arguments['uid'] );
        if( ! $value ) {
            $value = $arguments['default'];
        }
        switch( $arguments['type'] ){
            case 'text':
           
                printf( '<input name="%1$s" id="%1$s" type="%2$s" placeholder="%3$s" value="%4$s" />', $arguments['uid'], $arguments['type'], $arguments['placeholder'], $value );
                break;
            case 'textarea':
                printf( '<textarea name="%1$s" id="%1$s" placeholder="%2$s" rows="5" cols="50">%3$s</textarea>', $arguments['uid'], $arguments['placeholder'], $value );
                break;
        }
        if( $helper = $arguments['helper'] ){
            printf( '<span class="helper"> %s</span>', $helper );
        }
        if( $supplimental = $arguments['supplimental'] ){
            printf( '<p class="description">%s</p>', $supplimental );
        }
    }
}
new wp_internal_retargeting_Plugin();
