<?php
/**
 * @package ChatupIO
 * @version 1.1
 */
/*
Plugin Name: Chatup.io
Plugin URI: http://www.chatup.io
Description: Adds the Chatup.io Website communication system.
Author: Justin MacArthur
Version: 1.1
Author URI: about:blank
*/

defined('ABSPATH') or die("No script kiddies please!");

function chatup_getJwtToken($data)
{
    include "server.php";
    $url = "https://client.chatup.io/authenticate";

    // use key 'http' even if you send the request to https://...
    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
        )
    );
    $context = stream_context_create($options);
    $result = file_get_contents($url, false, $context);

    return $result;
}

function chatup_script() {
  global $current_user;
  
  if(is_user_logged_in()) { 
    get_currentuserinfo();

    $data = array(
      'secret'    => get_option( 'CUIO_Secret_Key' ), 
      'uniqueid'  => $current_user->ID, 
      'username'  => $current_user->user_login
    );
    
    $author_meta_image = trim(esc_attr(get_the_author_meta( 'chatup_image', $current_user->ID )));
    if(!empty($author_meta_image))
    {
      $data['avatarurl'] = esc_attr(get_the_author_meta( 'chatup_image', $current_user->ID ));
    }

    $serverAddress = "https://client.chatup.io";
    $chatup_jwt = chatup_getJwtToken($data);

    echo "
    <script type='text/javascript' src='{$serverAddress}/resources/chat.bootstrap.js?token={$chatup_jwt}'></script>
    ";
  }
}

function chatup_options_page() {
  add_options_page('Chatup.io Settings', 'Chatup.io', 'manage_options', 'chatupIO', 'chatup_options');
}

function Split_Table($value)
{
  $returnval = [];

  foreach(explode("`.`", $value) as $val)
  {
    $returnval[] = str_replace("`", "", $val);
  }

  return $returnval;
}

function chatup_options()
{
  global $wpdb;

  if ( !current_user_can( 'manage_options' ) )  {
    wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
  }

  // variables for the field and option names 
  $CUIO_SKey = 'CUIO_Secret_Key';
  $hidden_field_name = 'CUIO_submit_hidden';

  // Read in existing option value from database
  $opt_val = get_option( $CUIO_SKey );

  // See if the user has posted us some information
  // If they did, this hidden field will be set to 'Y'
  if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
    // Read their posted value
    $opt_val = $_POST[ $CUIO_SKey ];

    // Save the posted value in the database
    update_option( $CUIO_SKey, $opt_val );

    // Put an settings updated message on the screen

    ?>
    <div class="updated"><p><strong><?php _e('settings saved.', 'menu-test' ); ?></strong></p></div>
    <?php
  }

  // Now display the settings editing screen
  echo '<div class="wrap">';

  // header
  echo "<h2>" . __( 'Chatup.io Settings', 'menu-test' ) . "</h2>";
  // settings form
  ?>

    <form name="form1" method="post" action="">
      <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">

      <p><?php _e("Secret Key:", 'menu-test' ); ?> 
        <input type="text" name="<?php echo $CUIO_SKey; ?>" value="<?php echo $opt_val; ?>" size="20">
      </p>
      <hr />

      <p class="submit">
        <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
      </p>
    </form>
  </div>
<?php
}

function add_chatup_fields( $user )
{
    ?>
        <h3>Chatup.io</h3>

        <table class="form-table">
            <tr>
                <th><label for="chatup_image">chatup image url</label></th>
                <td><input type="text" name="chatup_image" value="<?php echo esc_attr(get_the_author_meta( 'chatup_image', $user->ID )); ?>" class="regular-text" /></td>
            </tr>
        </table>
    <?php
}

function save_chatup_fields( $user_id )
{
    update_user_meta( $user_id,'chatup_image', sanitize_text_field( $_POST['chatup_image'] ) );
}

add_action( 'personal_options_update', 'save_chatup_fields' );
add_action( 'edit_user_profile_update', 'save_chatup_fields' );
add_action( 'show_user_profile', 'add_chatup_fields' );
add_action( 'edit_user_profile', 'add_chatup_fields' );
add_action( 'admin_menu', 'chatup_options_page' );
add_action( 'get_footer', 'chatup_script' );

?>
