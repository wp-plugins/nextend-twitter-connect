<?php
/*
Plugin Name: Nextend Twitter Connect
Plugin URI: http://nextendweb.com/
Description: Twitter connect
Version: 1.4.17
Author: Roland Soos
License: GPL2
*/

/*  Copyright 2012  Roland Soos - Nextend  (email : roland@nextendweb.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'NEW_TWITTER_LOGIN', 1 );
if ( ! defined( 'NEW_TWITTER_LOGIN_PLUGIN_BASENAME' ) )
	define( 'NEW_TWITTER_LOGIN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
  
$new_twitter_settings = maybe_unserialize(get_option('nextend_twitter_connect'));

/*
  Sessions required for the profile notices 
*/
function new_twitter_start_session() {
  if(!session_id())
    session_start();
}

function new_twitter_end_session() {
  if(session_id())
    session_destroy();
}

add_action('init', 'new_twitter_start_session', 1);
add_action('wp_logout', 'new_twitter_end_session');
add_action('wp_login', 'new_twitter_end_session');

/*
  Loading style for buttons
*/
function nextend_twitter_connect_stylesheet(){
  wp_register_style( 'nextend_twitter_connect_stylesheet', plugins_url('buttons/twitter-btn.css', __FILE__) );
  wp_enqueue_style( 'nextend_twitter_connect_stylesheet' );
}

if($new_twitter_settings['twitter_load_style']){
  add_action( 'wp_enqueue_scripts', 'nextend_twitter_connect_stylesheet' );
  add_action( 'login_enqueue_scripts', 'nextend_twitter_connect_stylesheet' );
  add_action( 'admin_enqueue_scripts', 'nextend_twitter_connect_stylesheet' );
}

/*
  Creating the required table on installation
*/
function new_twitter_connect_install(){
  global $wpdb;
  
  $table_name = $wpdb->prefix . "social_users";
    
  $sql = "CREATE TABLE $table_name (
    `ID` int(11) NOT NULL,
    `type` varchar(20) NOT NULL,
    `identifier` varchar(100) NOT NULL,
    KEY `ID` (`ID`,`type`)
  );";

   require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
   dbDelta($sql);
}
register_activation_hook(__FILE__, 'new_twitter_connect_install');

/*
  Adding query vars for the WP parser
*/
function new_twitter_add_query_var(){
  global $wp;
  $wp->add_query_var('editProfileRedirect');
  $wp->add_query_var('loginTwitter');
}
add_filter('init', 'new_twitter_add_query_var');

/* -----------------------------------------------------------------------------
  Main function to handle the Sign in/Register/Linking process
----------------------------------------------------------------------------- */
add_action('parse_request', new_twitter_login);
function new_twitter_login(){
  global $wp, $wpdb,$new_twitter_settings;
  if($wp->request == 'loginTwitter' || isset($wp->query_vars['loginTwitter'])){
    require(dirname(__FILE__).'/sdk/init.php');
    $here = new_twitter_login_url();
    if ( isset($_SESSION['access_token']) ) {
      $tmhOAuth->config['user_token'] = $_SESSION['access_token']['oauth_token'];
      $tmhOAuth->config['user_secret'] = $_SESSION['access_token']['oauth_token_secret'];
    
      $code = $tmhOAuth->request('GET', $tmhOAuth->url('1/account/verify_credentials'));
      if ($code == 200) {
        $resp = json_decode($tmhOAuth->response['response']);
        $ID = $wpdb->get_var($wpdb->prepare('
          SELECT ID FROM '.$wpdb->prefix.'social_users WHERE type = "twitter" AND identifier = "'.$resp->id.'"
        '));
        if(!get_user_by('id',$ID)){
          $wpdb->query($wpdb->prepare('
            DELETE FROM '.$wpdb->prefix.'social_users WHERE ID = "'.$ID.'"
          '));
          $ID = null;
        }
        if(!is_user_logged_in()){
          if($ID == NULL){ // Register
            $email = $resp->id.'@noemail.twitter.com';
            $ID = email_exists($email);
            if($ID == false){ // Real register
              require_once( ABSPATH . WPINC . '/registration.php');
              $random_password = wp_generate_password( $length=12, $include_standard_special_chars=false );
                
              if(!isset($new_twitter_settings['twitter_user_prefix'])) $new_twitter_settings['twitter_user_prefix'] = 'Twitter - ';
              $ID = wp_create_user( $new_twitter_settings['twitter_user_prefix'].$resp->screen_name, $random_password, $email );
              wp_update_user(array(
                'ID' => $ID, 
                'display_name' => $resp->name, 
                'twitter' => $resp->screen_name
              ));
            }
            $wpdb->insert( 
            	$wpdb->prefix.'social_users', 
            	array( 
            		'ID' => $ID, 
            		'type' => 'twitter',
                'identifier' => $resp->id
            	), 
            	array( 
            		'%d', 
            		'%s',
                '%s'
            	)
            );
            if(isset($new_twitter_settings['twitter_redirect_reg']) && $new_twitter_settings['twitter_redirect_reg'] != '' && $new_twitter_settings['twitter_redirect_reg'] != 'auto'){
              $_SESSION['redirect'] = $new_twitter_settings['twitter_redirect_reg'];
            }
          }
          if($ID){ // Login
            wp_set_auth_cookie($ID, true, false);
            $user_info = get_userdata($ID);
            do_action('wp_login', $user_info->user_login);
            header( 'Location: '.(isset($_SESSION['redirect']) ? $_SESSION['redirect'] : $_GET['redirect']) );
            unset($_SESSION['redirect']);
            exit;
          }
          exit;
        }else{
          $current_user = wp_get_current_user();
          if($current_user->ID == $ID){ // It was a simple login
            header( 'Location: '.$_SESSION['redirect'] );
            unset($_SESSION['redirect']);
            exit;
          }elseif($ID === NULL){  // Let's connect the accout to the current user!
            $wpdb->insert( 
            	$wpdb->prefix.'social_users', 
            	array( 
            		'ID' => $current_user->ID, 
            		'type' => 'twitter',
                'identifier' => $resp->id
            	), 
            	array( 
            		'%d', 
            		'%s',
                '%s'
            	) 
            );
            $_SESSION['new_twitter_admin_notice'] = __('Your Twitter profile is successfully linked with your account. Now you can sign in with Twitter easily.', 'nextend-twitter-connect');
            header( 'Location: '.(isset($_SESSION['redirect']) ? $_SESSION['redirect'] : $_GET['redirect']) );
            unset($_SESSION['redirect']);
            exit;
          }else{
            $_SESSION['new_twitter_admin_notice'] = __('This Twitter profile is already linked with other account. Linking process failed!', 'nextend-twitter-connect');
            header( 'Location: '.(isset($_SESSION['redirect']) ? $_SESSION['redirect'] : $_GET['redirect']) );
            unset($_SESSION['redirect']);
            exit;
          }
        }
      } else {
        //print_r($tmhOAuth);
        echo "Twitter Error 3";
        exit;
      }
    // we're being called back by Twitter
    } elseif (isset($_REQUEST['oauth_verifier'])) {
      $tmhOAuth->config['user_token'] = $_SESSION['oauth']['oauth_token'];
      $tmhOAuth->config['user_secret'] = $_SESSION['oauth']['oauth_token_secret'];
    
      $code = $tmhOAuth->request('POST', $tmhOAuth->url('oauth/access_token', ''), array(
        'oauth_verifier' => $_REQUEST['oauth_verifier']
      ));
    
      if ($code == 200) {
        $_SESSION['access_token'] = $tmhOAuth->extract_params($tmhOAuth->response['response']);
        unset($_SESSION['oauth']);
        header("Location: {$here}");
      } else {
        echo "Twitter Error 2";
        exit;
      }
    // start the OAuth dance
    } else {
      if(isset($new_twitter_settings['twitter_redirect']) && $new_twitter_settings['twitter_redirect'] != '' && $new_twitter_settings['twitter_redirect'] != 'auto'){
        $_GET['redirect'] = $new_twitter_settings['twitter_redirect'];
      }
      $_SESSION['redirect'] = isset($_GET['redirect']) ? $_GET['redirect'] : site_url();
      
      $callback = $here;
      $params = array(
        'oauth_callback' => $callback
      );
    
      if (isset($_REQUEST['force_read'])) :
        $params['x_auth_access_type'] = 'read';
      endif;
    
      $code = $tmhOAuth->request('POST', $tmhOAuth->url('oauth/request_token', ''), $params);
    
      if ($code == 200) {
        $_SESSION['oauth'] = $tmhOAuth->extract_params($tmhOAuth->response['response']);
        $method = isset($_REQUEST['authenticate']) ? 'authenticate' : 'authorize';
        $force = isset($_REQUEST['force']) ? '&force_login=1' : '';
        $authurl = $tmhOAuth->url("oauth/{$method}", '') . "?oauth_token={$_SESSION['oauth']['oauth_token']}{$force}";
        header('Location: '.$authurl);
        exit;
      } else {
        //print_r($tmhOAuth);
        echo "Twitter Error 1";
        exit;
      }
    }
    exit;
  }
}

/*
  Is the current user connected the Facebook profile? 
*/
function new_twitter_is_user_connected(){
  global $wpdb;
  $current_user = wp_get_current_user();
  $ID = $wpdb->get_var($wpdb->prepare('
    SELECT ID FROM '.$wpdb->prefix.'social_users WHERE type = "twitter" AND ID = "'.$current_user->ID.'"
  '));
  if($ID === NULL) return false;
  return true;
}

/*
  Connect Field in the Profile page
*/
function new_add_twitter_connect_field() {
  global $new_is_social_header;
  if($new_is_social_header === NULL){
    ?>
    <h3>Social connect</h3>
    <?php
    $new_is_social_header = true;
  }
  ?>
  <table class="form-table">
    <tbody>
      <tr>	
        <th></th>	
        <td>
          <?php if(!new_twitter_is_user_connected()): ?>
            <?php echo new_twitter_link_button() ?>
          <?php endif; ?>
        </td>
      </tr>
    </tbody>
  </table>
  <?php
}
add_action('profile_personal_options', 'new_add_twitter_connect_field');

function new_add_twitter_login_form(){
  ?>
  <script>
  var has_social_form = false;
  var socialLogins = null;
  jQuery(document).ready(function(){
    (function($) {
      if(!has_social_form){
        has_social_form = true;
        var loginForm = $.merge($('#loginform'),$('#registerform'));
        socialLogins = $('<div class="newsociallogins" style="text-align: center;"><div style="clear:both;"></div></div>');
        loginForm.prepend("<h3 style='text-align:center;'>OR</h3>");
        loginForm.prepend(socialLogins);
      }
      socialLogins.prepend('<?php echo addslashes(preg_replace('/^\s+|\n|\r|\s+$/m', '',new_twitter_sign_button())); ?>');
    }(jQuery));
  });
  </script>
  <?php
}

add_action('login_form', 'new_add_twitter_login_form');
add_action('register_form', 'new_add_twitter_login_form');

/* 
  Options Page 
*/
require_once(trailingslashit(dirname(__FILE__)) . "nextend-twitter-settings.php");

if(class_exists('NextendTwitterSettings')) {
	$nextendtwittersettings = new NextendTwitterSettings();
	
	if(isset($nextendtwittersettings)) {
		add_action('admin_menu', array(&$nextendtwittersettings, 'NextendTwitter_Menu'), 1);
	}
}

add_filter( 'plugin_action_links', 'new_twitter_plugin_action_links', 10, 2 );

function new_twitter_plugin_action_links( $links, $file ) {
  if ( $file != NEW_TWITTER_LOGIN_PLUGIN_BASENAME )
  	return $links;
	$settings_link = '<a href="' . menu_page_url( 'nextend-twitter-connect', false ) . '">'
		. esc_html( __( 'Settings', 'nextend-twitter-connect' ) ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}

/* -----------------------------------------------------------------------------
  Miscellaneous functions
----------------------------------------------------------------------------- */
function new_twitter_sign_button(){
  global $new_twitter_settings;
  return '<a href="'.new_twitter_login_url().(isset($_GET['redirect_to']) ? '&redirect='.$_GET['redirect_to'] : '').'" rel="nofollow">'.$new_twitter_settings['twitter_login_button'].'</a><br />';
}

function new_twitter_link_button(){
  global $new_twitter_settings;
  return '<a href="'.new_twitter_login_url().'&redirect='.site_url().$_SERVER["REQUEST_URI"].'">'.$new_twitter_settings['twitter_link_button'].'</a><br />';
}

function new_twitter_login_url(){
  return site_url('index.php').'?loginTwitter=1';
}

function new_twitter_edit_profile_redirect(){
  global $wp;
  if(isset($wp->query_vars['editProfileRedirect']) ){
    if(function_exists('bp_loggedin_user_domain')){
      header('LOCATION: '.bp_loggedin_user_domain().'profile/edit/group/1/');
    }else{
      header('LOCATION: '.self_admin_url( 'profile.php' ));
    }
    exit;
  }
}
add_action('parse_request', new_twitter_edit_profile_redirect);

function new_twitter_jquery(){
  wp_enqueue_script( 'jquery' );
}

add_action('login_form_login', 'new_twitter_jquery');
add_action('login_form_register', 'new_twitter_jquery');


/*
  Session notices used in the profile settings
*/
function new_twitter_admin_notice(){
  if(isset($_SESSION['new_twitter_admin_notice'])){
    echo '<div class="updated">
       <p>'.$_SESSION['new_twitter_admin_notice'].'</p>
    </div>';
    unset($_SESSION['new_twitter_admin_notice']);
  }
}
add_action('admin_notices', 'new_twitter_admin_notice');