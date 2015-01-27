<?php
/*
 * Plugin Name: Secure Post
 * Plugin URI: www.21coder.com
 * Description: Simple Secure Post for Logged in user
 * Version: 0.1
 * Author: Tapan Kumer Das
 * Author URI: www.21coder.com
 * Text Domain: sp
 */

if ( !defined( 'ABSPATH' ) ) {
  exit; //Prevent Direct Access
}

//Check and load the class
if ( !class_exists( "SP_Secure_Post" ) ) {

  class SP_Secure_Post {

    const REVIEWLINK = 'http://wordpress.org/support/view/plugin-reviews/secure-post';
    const SUPPORTLINK = 'http://wordpress.org/support/plugin/secure-post';
    const AUTHORURL = 'http://21coder.com/';

    //-----------------------------------------
    // Options
    //-----------------------------------------
    var $options = 'secure_post';
    //-----------------------------------------
    // Paths
    //-----------------------------------------
    var $pluginURL = '';
    var $pluginPath = '';
    //-----------------------------------------
    // Options page
    //-----------------------------------------
    var $optionsPageTitle = '';
    var $optionsMenuTitle = '';

    public function __construct() {

      $this->pluginURL = plugin_dir_url( __FILE__ );
      $this->pluginPath = plugin_dir_path( __FILE__ );
      $this->optionsPageTitle = __( 'Secure Post', 'sp' );
      $this->optionsMenuTitle = __( 'Secure Post', 'sp' );

      add_filter( 'the_content', array($this, 'secure_post_content') );
      add_action( 'admin_menu', array($this, 'sp_admin_menu') );
    }

    public function secure_post_content( $content ) {
      global $post;
      $cat_id = get_the_category( $post->ID );
      //var_dump($cat_id);

      if ( is_user_logged_in() ) { // if user logged in show content.
        return $content;
      } else {
        $options = get_option( $this->options, true );
        foreach ( $options as $key => $option ) {
          if ( $option == 'yes' ) {
            $cat[] = $key;
          }
        }
        if ( is_single() || is_archive() || is_search() ) {
          $page_id = $options['page_id'];
          $post = get_post( $page_id );
          $url = home_url() . '/' . $post->post_name;
          //var_dump( $url );
          //var_dump( $cat );
          if ( in_array( $cat_id[0]->cat_ID, $cat ) ) {
            wp_redirect( $url );
            exit;
          } else {
            return $content;
          }
        } else {
          return $content;
        }
      }
    }

    public function optionsPage() {
      if ( isset( $_POST['sp_nonce_box_nonce'] ) && wp_verify_nonce( $_POST['sp_nonce_box_nonce'], 'sp_nonce_box' ) ) {
        if ( isset( $_POST['update_options'] ) ) {
          if ( get_magic_quotes_gpc() ) {
            $_POST = array_map( 'stripslashes', $_POST );
          }

          $options = $_POST['options'];
          if ( update_option( $this->options, $options ) ) {
            do_action( 'sp_option_saved' );
          }
          wp_redirect( admin_url( 'options-general.php?page=secure-post.php&msg=' . __( 'Options+saved.', 'sp' ) ) );
        }
      }

      $args = array(
          'type' => 'post',
          'child_of' => 0,
          'parent' => '',
          'orderby' => 'name',
          'order' => 'ASC',
          'hide_empty' => 0,
          'hierarchical' => 0,
          'exclude' => '',
          'include' => '',
          'number' => '',
          'taxonomy' => 'category',
          'pad_counts' => false
      );
      $categories = get_categories( $args );

      $options = get_option( $this->options, true );

      if ( isset( $_REQUEST['msg'] ) && !empty( $_REQUEST['msg'] ) ) {
        ?>
        <div class="updated"><p><strong><?php echo str_replace( '+', ' ', $_REQUEST['msg'] ); ?></strong></p>
        </div>
        <?php
      }

      // Display options form
      ?>
      <div class="wrap">
        <div id="poststuff">
          <form method="post" action="<?php echo admin_url( 'options-general.php?page=secure-post.php&noheader=true' ); ?>">
            <h2><?php echo $this->optionsPageTitle; ?></h2>
            By <b><a href="<?php echo self::AUTHORURL; ?>" title="Tapan Kumer Das" target="_blank"><?php _e( 'Tapan Kumer Das', 'sp' ) ?></a></b> |
            <a href="<?php echo self::REVIEWLINK; ?>" title="Secure Post" target="_blank"><?php _e( 'Rate & Review', 'sp' ) ?></a> |
            <a href="<?php echo self::SUPPORTLINK ?>" title="Support For Secure Post" target="_blank"><?php _e( 'Get Support', 'sp' ) ?></a>
            <br><br>
            <div class="postbox">
              <h3 class="hndle"><span><?php _e( 'General Settings', 'sp' ) ?></span></h3>
              <div class="inside">
                <table class="form-table">
                  <?php
                  $pages = get_pages();
                  //var_dump( $pages );
                  ?>
                  <tr>
                    <th>Select Page</th>
                    <td>
                      <select name="options[page_id]">
                        <option value="">Select Page for Restricted Post</option>
                        <?php foreach ( $pages as $key => $page ) { ?>
                          <option value="<?php echo $page->ID; ?>" <?php if ( isset( $options['page_id'] ) && $options['page_id'] == $page->ID ) { ?>selected="selected"<?php } ?>><?php echo $page->post_title; ?></option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>
                  <tr valign="top">
                    <th colspan="2">Select Category you need to secure.</th>
                  </tr>
                  <?php foreach ( $categories as $category ) { ?>
                    <tr valign="top">
                      <th scope="row"><?php echo $category->name; ?></th>
                      <td>
                        <label>
                          <input <?php echo isset( $options[$category->cat_ID] ) && $options[$category->cat_ID] == 'yes' ? 'checked' : '' ?> type="radio" name="options[<?php echo $category->cat_ID; ?>]" value="yes">
                          <?php _e( 'Yes', 'sp' ) ?>
                        </label>
                        <label>
                          <input <?php echo isset( $options[$category->cat_ID] ) && $options[$category->cat_ID] == 'no' ? 'checked' : (!isset( $options[$category->cat_ID] ) ? 'checked' : '' ) ?> type="radio" name="options[<?php echo $category->cat_ID; ?>]" value="no">
                          <?php _e( 'No', 'sp' ) ?>
                        </label>
                      </td>
                    </tr>
                  <?php } ?>
                </table>
                <?php wp_nonce_field( 'sp_nonce_box', 'sp_nonce_box_nonce' ); ?>
                <p class="submit">
                  <input type="submit" class="button-primary" name="update_options" value="<?php _e( 'Update Settings', 'sp' ) ?>"/>
                </p>
              </div>
            </div>
          </form>
        </div>
      </div>
      <?php
    }

    public function sp_admin_menu() {
      add_options_page( $this->optionsPageTitle, $this->optionsMenuTitle, 'manage_options', basename( __FILE__ ), array($this, 'optionsPage') );
    }

  }

}

$sp_class_instance = new SP_Secure_Post();
