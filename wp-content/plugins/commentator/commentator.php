<?php
/*
Plugin Name: Commentator
Plugin URI: http://www.guxinweb.com/portfolio/commentator/
Description: Commentator 是一个基于 Wordpress 开发且功能齐全的ajax评论插件，拥有诸多实用的功能，让你网站更具互动性。
Author: Yukulelix
Author URI: http://guxin.net
Version: 1.5.8
Text Domain: commentator
Domain Path: /lang
*/

// Make sure that no info is exposed if file is called directly -- Idea taken from Akismet plugin
if ( !function_exists( 'add_action' ) ) {
	_e( 'This page cannot be called directly.', 'commentator' );
	exit;
}

// Define some useful constants that can be used by functions
if ( ! defined( 'WP_CONTENT_URL' ) ) {	
	if ( ! defined( 'WP_SITEURL' ) ) define( 'WP_SITEURL', get_option("siteurl") );
	define( 'WP_CONTENT_URL', WP_SITEURL . '/wp-content' );
}
if ( ! defined( 'WP_SITEURL' ) ) define( 'WP_SITEURL', get_option("siteurl") );
if ( ! defined( 'WP_CONTENT_DIR' ) ) define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) ) define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) ) define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );

if ( ! defined( 'SPEC_COMMENT_TMP' ) ) define('SPEC_COMMENT_TMP', plugins_url( '/php/commentator-template.php', __FILE__ ));

if ( basename(dirname(__FILE__)) == 'plugins' )
	define("COMMENTATOR_DIR",'');
else define("COMMENTATOR_DIR" , basename(dirname(__FILE__)) . '/');
define("COMMENTATOR_PATH", WP_PLUGIN_URL . "/" . COMMENTATOR_DIR);
/* Add new menu */
add_action('admin_menu', 'commentator_add_pages');
/* Register Settings */
add_action( 'admin_init', 'register_commentator_settings' );
 
require_once( WP_PLUGIN_DIR . '/' . COMMENTATOR_DIR . 'hybridauth/Hybrid/Auth.php' );

add_action('set_current_user', 'cc_hide_admin_bar');
function cc_hide_admin_bar() {
  if (!current_user_can('edit_posts')) {
    show_admin_bar(false);
  }
}

add_role(
    'commentator_commenter',
    __( 'Commentator Commenter' , 'commentator' ),
    array(
        'read'         => false,
        'edit_posts'   => false,
        'delete_posts' => false,
    )
);

function register_session(){
    if( !session_id())
        session_start();
}
add_action('init','register_session');


function commentator_translation_init() {
	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain( 'commentator', false, $plugin_dir."/lang" );
}
add_action('plugins_loaded', 'commentator_translation_init');
/*

******** BEGIN PLUGIN FUNCTIONS ********

*/

add_action('wp_head','commentator_ajaxurl');
function commentator_ajaxurl() {
?>
<script type="text/javascript">
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
</script>
<?php
}

function roles_and_capabilities(){
	$roles = get_option('commentator_disabled-user-roles', array());
	$roles = is_array($roles) ? $roles : array();
	global $wp_roles;
    $allRoles = $wp_roles->get_names();
	foreach ($allRoles as $role_name => $role_info){
		if(!in_array( $role_name, $roles ))
			get_role($role_name)->add_cap('commentator-comment');
		else
			get_role($role_name)->remove_cap('commentator-comment');
	}
}
add_action('wp_loaded', 'roles_and_capabilities');

add_action( 'wp_ajax_commentator_register', 'commentator_register' );
add_action('wp_ajax_nopriv_commentator_register', 'commentator_register' );
function commentator_register() {
	$errors = array();
	$arr = array();
	$username = $_POST['username'];
	if(empty($username)) {  
        $errors[] = __( 'User name should not be empty.', 'commentator' );  
    }
    $email = $_POST['email'];
    if(!$email || strlen($email) == 0 || ctype_space($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {  
        $errors[] = __( 'The email is empty or invalid.', 'commentator' );  
    }
    $password = $_POST['password'];
    if(get_option('commentator_register_password_chose')){
    	if(!$password || strlen($password) == 0 || ctype_space($password)) {  
        	$errors[] = __( 'You need to chose a password.', 'commentator' );  
    	}
    }
    else{
    	$password = wp_generate_password( 12, false );
    }

    if(count($errors) == 0){
	    $status = wp_create_user( $username, $password, $email );
	    if ( is_wp_error($status) ) {
	        $errors[] = $status->get_error_message();
	    }
	}

    if(count($errors) == 0){
    	$user = get_userdata( $status );
    	$user->set_role('commentator_commenter');
        $from = get_option('admin_email');  
        $headers = __( 'From', 'commentator' ).' '.$from . "</br>";  
        $subject = __( 'Registration successful', 'commentator' );
        $msg = __( 'Registration successful', 'commentator' ).".<br/>".__( 'Your login details', 'commentator' )."\n".__( 'Username', 'commentator' ).": ".$username."\n".__( 'Password', 'commentator' ).": ".$password;
        add_filter( 'wp_mail_content_type', 'set_html_content_type' );
        wp_mail( $email, $subject, $msg, $headers );
        remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
        $arr = array(
			'message' => __( 'Registration successful, check your email for your password', 'commentator' )
		);
    }
    else{
    	$arr = array(
			'errors' => $errors,
		);
    }
	wp_send_json($arr);
	die();
}



add_action( 'wp_ajax_commentator_login', 'commentator_login' );
add_action('wp_ajax_nopriv_commentator_login', 'commentator_login' );
function commentator_login() {
	$errors = array();
	$arr = array();
	$username = $_POST['username'];
	$password = $_POST['password'];

	$creds = array();
	$creds['user_login'] = $username;
	$creds['user_password'] = $password;
	$creds['remember'] = $_POST['remember'];
	$user = wp_signon( $creds, false );

	if ( is_wp_error($user) )
		$errors[] = $user->get_error_message();

    if(count($errors) == 0){
        $arr = array(
			'avatar' => get_avatar( $user->ID ),
			'username' => $user->user_login,
			'ID' => $user->ID
		);
    }  
    else{
    	$arr = array(
			'errors' => $errors,
		);
    }
	wp_send_json($arr);
	die();
}

add_action( 'wp_ajax_commentator_logout', 'commentator_logout' );
add_action('wp_ajax_nopriv_commentator_logout', 'commentator_logout' );
function commentator_logout() {
	if(is_user_logged_in()){
		wp_logout();
	}
	elseif($_SESSION["commentator_user_profile"] != null){
			Hybrid_Auth::logoutAllProviders();
			$_SESSION["commentator_user_profile"] = null;
			$_SESSION["commentator_provider"] = null;
	}
	die();
}

add_action( 'wp_ajax_commentator_vote-thread', 'commentator_voteThread' );
add_action('wp_ajax_nopriv_commentator_vote-thread', 'commentator_voteThread' );
function commentator_voteThread() {
	$upVotes = get_post_meta( $_POST['comment_post_ID'], "upVote-discussion" );
	$count = count($upVotes);
	$hasVoted = false;
	$errors = array();
	$arr = array();
	$content_post = get_post($_POST['comment_post_ID']);

	global $current_user;
	get_currentuserinfo();

	$voteAuthorID = $current_user->ID;

	$user_profile = $_SESSION["commentator_user_profile"];
	if(!is_user_logged_in() && $user_profile != null){
		$voteAuthorID = $_SESSION["commentator_provider"]."-".$user_profile['identifier'];
	}

	if(!$content_post){
		$errors[] = __( 'the post doesn\'t exist', 'commentator' );
	}

	if(!is_user_logged_in() && $user_profile == null){
		$errors[] = __( 'you are not logged in !', 'commentator' );
	}

	if(count($errors) == 0){
	    if(!in_array ( $voteAuthorID, $upVotes )){
			add_post_meta($_POST['comment_post_ID'], "upVote-discussion", $voteAuthorID);
	    	$hasVoted = true;
		}
		else{
			delete_post_meta( $_POST['comment_post_ID'], "upVote-discussion", $voteAuthorID );
		}

		$upVotes = get_post_meta( $_POST['comment_post_ID'], "upVote-discussion" );
		$count = count($upVotes);

		$arr = array(
			'count' => $count,
			'hasVoted' => $hasVoted
		);
	}

	else{
		$arr = array(
			'errors' => $errors,
		);
	}

	wp_send_json($arr);
	die();
}

add_action( 'wp_ajax_commentator_flag', 'commentator_flag' );
add_action('wp_ajax_nopriv_commentator_flag', 'commentator_flag' );
function commentator_flag() {
	$comment_ID = $_POST['comment_ID'];
	$comment = get_comment( $comment_ID );
	$post  = get_post( $comment->comment_post_ID );
	$errors = array();
	$arr = array();

	global $current_user;
	get_currentuserinfo();

	$voteAuthorID = $current_user->ID;
	$user_profile = $_SESSION["commentator_user_profile"];
	if(!is_user_logged_in() && $user_profile != null){
		$voteAuthorID = $_SESSION["commentator_provider"]."-".$user_profile['identifier'];
	}

	if(!is_user_logged_in() && $user_profile == null){
		$errors[] = __( 'you are not logged in !', 'commentator' );
	}

	if(!get_option('commentator_flag-comment')){
		$errors[] = __( 'Flagging is not allowed !', 'commentator' );
	}

	if(count($errors) == 0){
	    update_comment_meta($comment_ID, "flag-comment", $voteAuthorID);
	    $link = get_comment_link( $comment->comment_ID );
	    $count = count(get_comment_meta($comment_ID, "flag-comment"));
	    update_comment_meta($comment_ID, "flag-comment-count", $count);

	    if(get_option('commentator_flag-limit') && $count >= ((int) get_option('commentator_flag-limit'))){
	    	$commentarr = array();
			$commentarr['comment_ID'] = $comment_ID; // This is the only required array key
			$commentarr['comment_approved'] = 0;
			wp_update_comment( $commentarr );
		}

	    $to = get_option('admin_email');  
        $headers = __( 'From', 'commentator' ).' '.$to . "</br>";  
        $subject = __( 'New Comment Flagging', 'commentator' );
        $msg = 
        	__( 'A new comment has been flagged on ', 'commentator' ).'<a href="'.$link.'">'.$post->post_title.'</a>'.
			".<br/>".
        	__( 'Number of flags : ', 'commentator' ).$count.
        	".<br/>".
        	"<pre>".$comment->comment_content."</pre>".
        	"<br/>".
        	__( 'Go to your wordpress admin to see it', 'commentator' );
        add_filter( 'wp_mail_content_type', 'set_html_content_type' );
        wp_mail( $to, $subject, $msg, $headers );
        remove_filter( 'wp_mail_content_type', 'set_html_content_type' );
		$arr = array(
			'message' => __('The comment has been flagged', 'commentator')
		);
	}

	else{
		$arr = array(
			'errors' => $errors,
		);
	}

	wp_send_json($arr);
	die();
}

add_action('transition_comment_status', 'commentator_approve_comment_callback', 10, 3);
function commentator_approve_comment_callback($new_status, $old_status, $comment) {
    if($old_status != $new_status) {
        if($new_status == 'approved') {
            delete_comment_meta($comment->comment_ID, "flag-comment");
        }
    }
}

function set_html_content_type()
{
    return 'text/html';
}

add_action( 'wp_ajax_commentator_social_signin', 'commentator_social_signin' );
add_action('wp_ajax_nopriv_commentator_social_signin', 'commentator_social_signin' );
function commentator_social_signin(){
	if( isset($_REQUEST["provider"]) ){ 
		// the selected provider
		$provider_name = $_REQUEST["provider"];
?>
	<!DOCTYPE html>
	<html lang="en">
		<head>
		    <meta charset="utf-8">
		    <title>Commentator Social Signin</title>
		    <link rel="stylesheet" href="<?php echo plugins_url( '/css/font-awesome.min.css', __FILE__ ); ?>" type="text/css" media="all">
		    <link rel="stylesheet" href="<?php echo plugins_url( '/css/font-awesome-ie7.min.css', __FILE__ ); ?>" type="text/css" media="all">
			<style>
		    	@import url(http://fonts.googleapis.com/css?family=Lato:300,400,700);
			    body, html{
			    	margin: 0;
			    	padding: 0;
					width: 100%;
					height: 100%;
  					font-family: 'Lato', sans-serif;
			    }
			    #commentator-social-signin{
			    	position: relative;
					width: 100%;
					height: 100%;
			    }
				.commentator-social-message{
					color: #fff;
					position: absolute;
					top: 0;
					left: 0;
					width: 100%;
					height: 100%;
		  			display: table; 
				}
				#commentator-social-signin .commentator-social-message > div{
					display: table-cell; 
		  			vertical-align: middle; 
		  			text-align: center; 
		  			text-align: center;
				}
				h4{
					opacity: .8;
				}
				.commentator-facebook .commentator-social-message{
					background: #4862A3;
				}
				.commentator-twitter .commentator-social-message{
					background: #00ACEE;
				}
				.commentator-google .commentator-social-message{
				  background: #C13222;
				}
				.commentator-linkedin .commentator-social-message{
				  background: #0073B2;
				}
			</style>
		</head>
		<body>

		    <div id="commentator-social-signin" class="commentator-<?php echo $provider_name; ?>">

		    	<div class="commentator-social-message">
			    	<div id="commentator-social-loading">
			    		<div>
			    			<i class="commentator-icon-spin commentator-icon-repeat"></i>
			    		</div>
					</div>
				</div>
				
<?php
 
		try{
			// initialize Hybrid_Auth with a given file
			$hybridauth = new Hybrid_Auth( 
				array(
					"base_url" => WP_PLUGIN_URL . '/' . COMMENTATOR_DIR . 'hybridauth/', 

					"providers" => array ( 
						// openid providers
						"OpenID" => array (
							"enabled" => false
						),

						"Yahoo" => array ( 
							"enabled" => false,
							"keys"    => array ( "id" => "", "secret" => "" ),
						),

						"AOL"  => array ( 
							"enabled" => false 
						),

						"Google" => array ( 
							"enabled" => get_option('commentator_id_key-google') && get_option('commentator_secret_key-google'),
							"keys"    => array ( "id" => get_option('commentator_id_key-google'), "secret" => get_option('commentator_secret_key-google') ), 
						),

						"Facebook" => array ( 
							"enabled" => get_option('commentator_id_key-facebook') && get_option('commentator_secret_key-facebook'),
							"keys"    => array ( "id" => get_option('commentator_id_key-facebook'), "secret" => get_option('commentator_secret_key-facebook') ), 
						),

						"Twitter" => array ( 
							"enabled" => get_option('commentator_id_key-twitter') && get_option('commentator_secret_key-twitter'),
							"keys"    => array ( "key" => get_option('commentator_id_key-twitter'), "secret" => get_option('commentator_secret_key-twitter') ), 
						),

						// windows live
						"Live" => array ( 
							"enabled" => false,
							"keys"    => array ( "id" => "", "secret" => "" ) 
						),

						"MySpace" => array ( 
							"enabled" => false,
							"keys"    => array ( "key" => "", "secret" => "" ) 
						),

						"LinkedIn" => array ( 
							"enabled" => get_option('commentator_id_key-linkedin') && get_option('commentator_secret_key-linkedin'),
							"keys"    => array ( "key" => get_option('commentator_id_key-linkedin'), "secret" => get_option('commentator_secret_key-linkedin') ), 
						),

						"Foursquare" => array (
							"enabled" => false,
							"keys"    => array ( "id" => "", "secret" => "" ) 
						),
					),

					// if you want to enable logging, set 'debug_mode' to true  then provide a writable file by the web server on "debug_file"
					"debug_mode" => false,

					"debug_file" => "",
				)	
			);
 
			// try to authenticate with the selected provider
			$adapter = $hybridauth->authenticate( $provider_name );
 
			// then grab the user profile 
			$user_profile = $adapter->getUserProfile();

			$_SESSION["commentator_user_profile"] = (array) $user_profile;
			$_SESSION["commentator_provider"] = $provider_name;
		}
		catch( Exception $e ){
			$caught = true;
			?>
			<div class="commentator-social-message">
				<div>
					<h1><?php _e("Error: please try again!", 'commentator' ); ?></h1>
					<h4><?php _e("Original error message: ", 'commentator'); ?> <?php echo $e->getMessage(); ?></h4>
				</div>
			</div>
			<?php
		}
 
		if(!$caught){
	?>
			<div class="commentator-social-message">
				<div>
					<h1><?php _e('Congratulations !', 'commentator' ); ?></h1>
					<h4><?php _e('You can now join the conversation !', 'commentator'); ?></h4>
				</div>
			</div>

			<script type="text/javascript">
				window.opener.location.reload();
				setTimeout(function(){
					window.close();
				}, 3000);
			</script>
		<?php
		}
		?>
		</body>
	</html>
	<?php
	}
	die();
}

add_action( 'wp_ajax_commentator_add-comment', 'commentator_add_comment' );
add_action('wp_ajax_nopriv_commentator_add-comment', 'commentator_add_comment' );
function commentator_add_comment() {

	$comment_post_ID = isset($_POST['comment_post_ID']) ? (int) $_POST['comment_post_ID'] : 0;

	$post = get_post($comment_post_ID);

	if ( empty( $post->comment_status ) ) {
		/**
		 * Fires when a comment is attempted on a post that does not exist.
		 *
		 * @since unknown
		 * @param int $comment_post_ID Post ID.
		 */
		do_action( 'comment_id_not_found', $comment_post_ID );
		exit;
	}

	// get_post_status() will get the parent status for attachments.
	$status = get_post_status($post);

	$status_obj = get_post_status_object($status);

	if ( ! comments_open( $comment_post_ID ) ) {
		/**
		 * Fires when a comment is attempted on a post that has comments closed.
		 *
		 * @since unknown
		 * @param int $comment_post_ID Post ID.
		 */
		do_action( 'comment_closed', $comment_post_ID );
		wp_die( __('Sorry, comments are closed for this item.') );
	} elseif ( 'trash' == $status ) {
		/**
		 * Fires when a comment is attempted on a trashed post.
		 *
		 * @since 2.9.0
		 * @param int $comment_post_ID Post ID.
		 */
		do_action( 'comment_on_trash', $comment_post_ID );
		exit;
	} elseif ( ! $status_obj->public && ! $status_obj->private ) {
		/**
		 * Fires when a comment is attempted on a post in draft mode.
		 *
		 * @since unknown
		 * @param int $comment_post_ID Post ID.
		 */
		do_action( 'comment_on_draft', $comment_post_ID );
		exit;
	} elseif ( post_password_required( $comment_post_ID ) ) {
		/**
		 * Fires when a comment is attempted on a password-protected post.
		 *
		 * @since unknown
		 * @param int $comment_post_ID Post ID.
		 */
		do_action( 'comment_on_password_protected', $comment_post_ID );
		exit;
	} else {
		/**
		 * Fires before a comment is posted.
		 *
		 * @since unknown
		 * @param int $comment_post_ID Post ID.
		 */
		do_action( 'pre_comment_on_post', $comment_post_ID );
	}

	$comment_author       = ( isset($_POST['author-name']) )  ? trim(strip_tags($_POST['author-name'])) : null;
	$comment_author_email = ( isset($_POST['author-email']) )   ? trim($_POST['author-email']) : null;
	$comment_author_url   = ( isset($_POST['author-url']) )     ? trim($_POST['author-url']) : null;
	$comment_content      = ( isset($_POST['comment']) ) ? trim($_POST['comment']) : null;

	$user_profile = null;
	if(get_option('commentator_social-signin')){
		$user_profile = $_SESSION["commentator_user_profile"];
		if($user_profile != null){
			$comment_author = $user_profile['displayName'];
			$comment_author_url = $user_profile['webSiteURL'] ? $user_profile['webSiteURL'] : $user_profile['profileURL'];
			$comment_author_email = $user_profile['email'];
		}
	}

	// If the user is logged in
	$user = wp_get_current_user();
	if ( $user->exists() ) {
		if ( empty( $user->display_name ) )
			$user->display_name=$user->user_login;
		$comment_author       = wp_slash( $user->display_name );
		$comment_author_email = wp_slash( $user->user_email );
		$comment_author_url   = wp_slash( $user->user_url );
		if(!current_user_can( 'commentator-comment' )){
			wp_die( __('Sorry, your user role doesn\'t allow you to comment.', 'commentator') );
		}
		if ( current_user_can( 'unfiltered_html' ) ) {
			if ( ! isset( $_POST['_wp_unfiltered_html_comment'] )
				|| ! wp_verify_nonce( $_POST['_wp_unfiltered_html_comment'], 'unfiltered-html-comment_' . $comment_post_ID )
			) {
				kses_remove_filters(); // start with a clean slate
				kses_init_filters(); // set up the filters
			}
		}
	} else {
		if ( get_option('comment_registration') || 'private' == $status )
			wp_die( __('Sorry, you must be logged in to post a comment.') );
	}

	$comment_type = '';

	if ( get_option('require_name_email') && !$user->exists() && $user_profile == null ) {
		if ( 6 > strlen($comment_author_email) || '' == $comment_author )
			wp_die( __('<strong>ERROR</strong>: please fill the required fields (name, email).') );
		elseif ( !is_email($comment_author_email))
			wp_die( __('<strong>ERROR</strong>: please enter a valid email address.') );
	}

	if ( '' == $comment_content )
		wp_die( __('<strong>ERROR</strong>: please type a comment.') );

	$comment_parent = isset($_POST['comment_parent']) ? absint($_POST['comment_parent']) : 0;

	$commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_type', 'comment_parent', 'user_ID');

	$comment_id = wp_new_comment( $commentdata );
	$comment = get_comment($comment_id);

	if($user_profile != null){
		update_comment_meta( $comment_id, "commentator-social-avatar", $user_profile['photoURL']);
	}

	/**
	 * Perform other actions when comment cookies are set.
	 *
	 * @since 3.4.0
	 *
	 * @param object $comment Comment object.
	 * @param WP_User $user   User object. The user may not exist.
	 */
	do_action( 'set_comment_cookies', $comment, $user );

	$result = array();
	if ( '0' == $comment->comment_approved ){
		$result['message'] = __('Your comment is awaiting moderation', 'commentator');
	}

	wp_send_json($result);
    die();
}

add_action( 'wp_ajax_commentator_vote-comment', 'commentator_vote_comment' );
add_action('wp_ajax_nopriv_commentator_vote-comment', 'commentator_vote_comment' );
function commentator_vote_comment(){
	$multiplicator = $_POST['multiplicator'];
	$voteType = ($multiplicator > 0) ? "upVote" : "downVote";
	$voteOpposite = ($multiplicator < 0) ? "upVote" : "downVote";
	$votes = get_comment_meta( $_POST['comment_ID'], $voteType );

	$errors = array();
	$arr = array();


	global $current_user;
	get_currentuserinfo();

	$voteAuthorID = $current_user->ID;

	$user_profile = $_SESSION["commentator_user_profile"];
	if(!is_user_logged_in() && $user_profile != null){
		$voteAuthorID = $_SESSION["commentator_provider"]."-".$user_profile['identifier'];
	}

	if(!is_user_logged_in() && $user_profile == null){
		$errors[] = __( 'you are not logged in', 'commentator' );
	}

	if(!get_comment($_POST['comment_ID'])){
		$errors[] = __( 'the comment you wan\'t to vote doesn\'t exist', 'commentator' );
	}

	if(count($errors) == 0){
	    if(!in_array ( $voteAuthorID, $votes )){
	    	add_comment_meta( $_POST['comment_ID'], $voteType, $voteAuthorID);
			delete_comment_meta( $_POST['comment_ID'], $voteOpposite, $voteAuthorID );
		}
		else{
			delete_comment_meta( $_POST['comment_ID'], $voteType, $voteAuthorID );
		}


		$votes = get_comment_meta( $_POST['comment_ID'], $voteType );
		$count = count($votes);
		update_comment_meta( $_POST['comment_ID'], $voteType."-count", $count );
		$hasVoted = in_array ( $voteAuthorID, $votes );
		$votesOpposite = get_comment_meta( $_POST['comment_ID'], $voteOpposite );
		$countOpposite = count($votesOpposite);
		update_comment_meta( $_POST['comment_ID'], $voteOpposite."-count", $countOpposite );
		$hasVotedOpposite = in_array ( $voteAuthorID, $votesOpposite );

		$arr = array(
			'count' => $count,
			'hasVoted' => $hasVoted,
			'countOpposite' => $countOpposite,
			'hasVotedOpposite' => $hasVotedOpposite
		);
		$commentarr = array();
		$commentarr['comment_ID'] = $_POST['comment_ID'];
		wp_update_comment( $commentarr );
	}

	else{
		$arr = array(
			'errors' => $errors,
		);
	}


	wp_send_json($arr);
    die();
}

add_action( 'wp_ajax_commentator_sort-comments', 'commentator_sort_comments');
add_action('wp_ajax_nopriv_commentator_sort-comments', 'commentator_sort_comments' );
function commentator_sort_comments(){
	$comments = get_comments(array(
		'post_id' => $_POST['comment_post_ID'],
		'order' => ($_POST['sort'] == 'asc') ? 'ASC' : 'DESC',
		'status' => 'approve'
	));
	$page = null;
	if(get_option('page_comments') && isset($_POST['comment_page'])){
		$page = $_POST['comment_page'];
	}
	if($_POST['sort'] == 'popular'){
		usort($comments, 'commentator_comment_karma_comparator');
	}

    wp_list_comments( 
    	array( 
    		'callback' => 'commentator_comment',
    		'page'              => get_option('page_comments') ? $page : "",
    		'per_page'			=> get_option('page_comments') ? get_option('comments_per_page') : "",
    		'max_depth' => get_option('commentator_max_depth', 3)
    	), $comments);

    ?>
    <?php
    	if(get_option('page_comments')){
    ?>
    <li id="commentator-new-pagination-container">
    	<?php
    	$args = array(
			'base' => '#%#%',
			'add_fragment' => ''
		);
		$max_page = get_comment_pages_count();
		$defaults = array(
			'base' => add_query_arg( 'cpage', '%#%' ),
			'format' => '',
			'total' => $max_page,
			'current' => $page,
			'echo' => true,
			'add_fragment' => '#comments'
		);
		$args = wp_parse_args( $args, $defaults );
		$page_links = paginate_links( $args );

		if ( $args['echo'] )
			echo $page_links;
		else
			return $page_links;
		?>
	</li>
	<?php
    	}
    ?>
<?php
	die();
}

function get_comment_karma($comment_id){
	$downVotes = get_comment_meta( $comment_id, "downVote" );
	$downCount = count($downVotes);
	$upVotes = get_comment_meta( $comment_id, "upVote" );
	$upCount = count($upVotes);
	return $upCount - $downCount;
}

function commentator_comment_karma_comparator($a, $b){
	$compared = 0;
	$karmaA = get_comment_karma($a->comment_ID);
	$karmaB = get_comment_karma($b->comment_ID);
	if($karmaA != $karmaB){
		$compared = $karmaA < $karmaB ? 1:-1;
	}
	return $compared;
}



function commentator_comment( $comment, $args, $depth ) {
	$GLOBALS['comment'] = $comment;
	global $post;
	global $current_user;
	get_currentuserinfo();
	$user=get_userdata($comment->user_id);

	$voteAuthorID = $current_user->ID;
	$avatar = get_avatar( $comment );
	$user_profile = $_SESSION["commentator_user_profile"];
	if(!is_user_logged_in() && $user_profile != null){
		$voteAuthorID = $_SESSION["commentator_provider"]."-".$user_profile['identifier'];
	}
	if(get_comment_meta( $comment->comment_ID, "commentator-social-avatar", true )){
		$avatar = '<img alt="" src="'.get_comment_meta( $comment->comment_ID, "commentator-social-avatar", true ).'" class="avatar avatar-96 photo" height="96" width="96">';
	}

	$upVotes = get_comment_meta( $comment->comment_ID, "upVote" );
	$hasUpVoted = in_array ( $voteAuthorID, $upVotes );
	$downVotes = get_comment_meta( $comment->comment_ID, "downVote" );
	$hasDownVoted = in_array ( $voteAuthorID, $downVotes );

	$commentUrl = get_comment_author_url();
	$commentUrl = empty($commentUrl) ? "#comment-".get_comment_ID() : $commentUrl;
	?>
	<li <?php comment_class(); ?> id="comment-<?php comment_ID(); ?>">
		<article id="comment-<?php comment_ID(); ?>" class="commentator-comment-content">
			<div class="commentator-avatar hovercard">
	            <a href="<?php echo $commentUrl; ?>" class="user">
	                <?php echo $avatar; ?>
	            </a>
	        </div>
	        <div class="commentator-comment-body">
	        	<div class="commentator-comment-header">
                    <span class="commentator-comment-byline">
                        
	                    <span class="author publisher-anchor-color">
	                    	<?php echo get_comment_author_link(); ?>
	                    </span>
                    </span>

                    <?php if ( $comment->user_id === $post->post_author ){ ?>
                    <span class="commentator-comment-bullet time-ago-bullet">•</span>
                    <span class="commentator-comment-author-tag"><?php _e("author", "commentator"); ?></span>
                   	<?php } ?>


                    <div class="commentator-comment-meta">
                        <span class="commentator-comment-bullet time-ago-bullet">•</span>
						<?php 	
						if(get_option('commentator_time-ago')){
							printf( '<a href="%1$s" class="commentator-time" title="%2$s">%3$s</a>',
									esc_url( get_comment_link( $comment->comment_ID ) ),
									get_comment_time( 'c' ),
									sprintf(
										__('%1$s ago', 'commentator'),
										human_time_diff( 
											get_comment_time('U'),
											current_time('timestamp', 1 )
										)
									)
							); 
						}
						else{
							printf( '<a href="%1$s" class="commentator-time" title="%2$s">%3$s</a>',
									esc_url( get_comment_link( $comment->comment_ID ) ),
									get_comment_time( 'c' ),
									sprintf( 
										__( '%1$s at %2$s', 'commentator' ),
										get_comment_date(),
										get_comment_time()
									)
							);
						}
						?>
                    </div>

				    <ul class="commentator-comment-menu">
				        <li class="commentator-collapse">
				           <a class="commentator-toggle-visibility" href="#comment-<?php comment_ID(); ?>" title="合并">
								<i class="commentator-icon-minus"></i>
							</a>
				        </li>
				        <li class="commentator-expand">
				            <a class="commentator-toggle-visibility" href="#comment-<?php comment_ID(); ?>" title="展开">
								<i class="commentator-icon-plus"></i>
							</a>
				        </li>
				    </ul>
                </div class="commentator-comment-header">
                <?php if ( '0' == $comment->comment_approved ) : ?>
				<p class="comment-awaiting-moderation"><?php _e( 'Your comment is awaiting moderation.', 'commentator' ); ?></p>
				<?php endif; ?>

				<section class="commentator-comment-text">

				<?php
					if(get_option('commentator_flag-comment') && count(get_comment_meta($comment->comment_ID, "flag-comment")) > 0){
						_e('This comment has been flagged', 'commentator');
					}
					else{
						comment_text();
					}

				?>
				</section><!-- .comment-content -->

				<div class="commentator-comment-footer">
		            <span class="commentator-voting">
					    <a href="#" class="commentator-vote-up<?php if($hasUpVoted){ echo " commentator-active"; } ?>" title="<?php _e( 'Vote up', 'commentator' ); ?>" data-comment-id="<?php comment_ID(); ?>">
					        <span><?php echo count($upVotes); ?></span>
					        <i class="<?php echo get_option('commentator_icon-comment-upvote', 'commentator-icon-caret-up'); ?>"></i>
					    </a>
					    <a href="#" class="commentator-vote-down<?php if($hasDownVoted){ echo " commentator-active"; } ?>" title="<?php _e( 'Vote down', 'commentator' ); ?>" data-comment-id="<?php comment_ID(); ?>">
					        <span><?php echo count($downVotes); ?></span>
					        <i class="<?php echo get_option('commentator_icon-comment-downvote', 'commentator-icon-caret-down'); ?>"></i>
					    </a>
		            </span>
		            <?php
		            	if(get_option('commentator_flag-comment')){
		            ?>
		            <span class="commentator-comment-bullet">•</span>
					<span>
						<a href="#" title="<?php _e( 'Flag comment', 'commentator' ); ?>" class="commentator-flag">
							<i class="<?php echo get_option('commentator_icon-comment-flag', 'commentator-icon-flag'); ?>"></i>
						</a>
					</span>
					<?php } ?>

		            <?php global $comment_depth; ?>
		            <?php
		            	if($comment_depth < get_option('commentator_max_depth', 3)){
		            ?>
		            <span class="commentator-comment-bullet">•</span>
					<span>
						<a href="#" class="commentator-reply">
							<i class="<?php echo get_option('commentator_icon-comment-reply', 'commentator-icon-reply'); ?>"></i>
							<span><?php _e( 'Reply', 'commentator' ); ?></span>
						</a>
					</span>
					<?php } ?>

			    </div>
	        </div>
		</article><!-- #comment-## -->
	<?php
}
/**
 * Proper way to enqueue scripts and styles
**/

function commentator_scripts() {
	wp_enqueue_script( 'commentator-script', plugins_url( '/js/commentator-script.js', __FILE__ ), array( 'jquery' ), '1.0', true);
	wp_enqueue_style( 'commentator-style', plugins_url( '/css/commentator.css', __FILE__ ) );
	wp_enqueue_style( 'commentator-font-awesome', plugins_url( '/css/font-awesome.min.css', __FILE__ ) );
	wp_enqueue_style( 'commentator-font-awesome-ie7', plugins_url( '/css/font-awesome-ie7.min.css', __FILE__ ) );
}
if(!get_option('commentator_load-from-cdn')) {
	add_action( 'wp_enqueue_scripts', 'commentator_scripts' );
}

function commentator_admin_scripts($hook) {
	global $commentator_settings_page;
 
	if( $hook != $commentator_settings_page ) 
		return;

	wp_enqueue_script( 'commentator-admin-script', plugins_url( '/js/commentator-admin-script.js', __FILE__ ), array( 'jquery' ), '1.0');
	wp_enqueue_style( 'commentator-admin-style', plugins_url( '/css/commentator-admin.css', __FILE__ ) );

	wp_enqueue_script( 'commentator-color-picker-script', plugins_url( '/colorpicker/js/colorpicker.js', __FILE__ ), array( 'jquery' ), '1.0');
	wp_enqueue_style( 'commentator-color-picker-style', plugins_url( '/colorpicker/css/colorpicker.css', __FILE__ ) );

	wp_enqueue_script( 'commentator-select2', plugins_url( '/select2/select2.js', __FILE__ ), array( 'jquery' ), '1.0');
	wp_enqueue_style( 'commentator-select2-style', plugins_url( '/select2/select2.css', __FILE__ ) );

	wp_enqueue_style( 'commentator-font-awesome', plugins_url( '/css/font-awesome.min.css', __FILE__ ) );
	wp_enqueue_style( 'commentator-font-awesome-ie7', plugins_url( '/css/font-awesome-ie7.min.css', __FILE__ ) );
}
add_action( 'admin_enqueue_scripts', 'commentator_admin_scripts' );

add_filter('comments_template', 'commentator_template');
function commentator_template($passed){
	return dirname(__FILE__) . '/php/commentator-template.php';
}

function commentator_comment_columns( $columns )
{
	return array_merge( $columns, array(
		'upVote-count' => __( 'Upvotes', 'commentator' ),
		'downVote-count' => __( 'Downvotes', 'commentator' ),
		'flag-comment-count' => __( 'Flags', 'commentator' )
	) );
}
add_filter( 'manage_edit-comments_columns', 'commentator_comment_columns' );

function commentator_comment_column( $column, $comment_ID )
{
	if ( $meta = get_comment_meta( $comment_ID, $column , true ) ) {
		echo $meta;
	} else {
		echo '-';
	}
}
add_filter( 'manage_comments_custom_column', 'commentator_comment_column', 10, 2 );

// Register the column as sortable
function register_sortable_columns( $columns ) {
    return array_merge( $columns, array(
		'upVote-count' => 'upVote-count',
		'downVote-count' => 'downVote-count',
		'flag-comment-count' => 'flag-comment-count'
	) );
}
add_filter( 'manage_edit-comments_sortable_columns', 'register_sortable_columns' );

add_action( 'pre_get_comments', 'commentator_orderby' );  
function commentator_orderby( $comments ) {  
    $orderby = $comments->query_vars['orderby'];
    if( 'upVote-count' == $orderby || 'downVote-count' == $orderby || 'flag-comment-count' == $orderby ) {  
        $comments->query_vars['meta_key'] = $orderby;
        $comments->query_vars['orderby'] = 'meta_value_num';
    }
    $comments->meta_query->parse_query_vars( $comments->query_vars );
}

global $arrayProviders;
$arrayProviders = array(
	array(
		"Google",
		"google",
		"google-plus"
	),
	array(
		"LinkedIn",
		"linkedin",
		"linkedin"
	),
	array(
		"Facebook",
		"facebook",
		"facebook"
	),
	array(
		"Twitter",
		"twitter",
		"twitter"
	)
);

function register_commentator_settings(){
	global $arrayProviders;
	//register our settings
	register_setting( 'commentator-settings-group', 'commentator_max_depth' );
	register_setting( 'commentator-settings-group', 'commentator_anybody_register' );
	register_setting( 'commentator-settings-group', 'commentator_register_password_chose' );
	register_setting( 'commentator-settings-group', 'commentator_disable-login-tab' );
	register_setting( 'commentator-settings-group', 'commentator_before' );
	register_setting( 'commentator-settings-group', 'commentator_custom-menu' );
	register_setting( 'commentator-settings-group', 'commentator_disable-avatars' );
	register_setting( 'commentator-settings-group', 'commentator_disable-thread-votes' );
	register_setting( 'commentator-settings-group', 'commentator_order-tabs' );
	register_setting( 'commentator-settings-group', 'commentator_flag-comment' );
	register_setting( 'commentator-settings-group', 'commentator_flag-limit');
	register_setting( 'commentator-settings-group', 'commentator_user-roles' );
	register_setting( 'commentator-settings-group', 'commentator_time-ago');
	register_setting( 'commentator-settings-group', 'commentator_disabled-user-roles');


	register_setting( 'commentator-settings-group', 'commentator_updated-counts-in-db' );
	if(!get_option( 'commentator_updated-counts-in-db' )){
		update_option( 'commentator_updated-counts-in-db', '1' );
		$arrayComments = get_comments();
		foreach ($arrayComments as $comment) {
			$comment_ID = $comment->comment_ID;

			$countFlags = count(get_comment_meta($comment_ID, "flag-comment"));
	    	update_comment_meta($comment_ID, "flag-comment-count", $countFlags);

			$countDownVotes = count(get_comment_meta( $comment_ID, "downVote" ));
			update_comment_meta( $comment_ID, "downVote-count", $countDownVotes );

			$countUpVotes = count(get_comment_meta( $comment_ID, "upVote" ));
			update_comment_meta( $comment_ID, "upVote-count", $countUpVotes );
		}
	}
	register_setting( 'commentator-settings-group', 'commentator_base-theme' );
	register_setting( 'commentator-settings-group', 'commentator_font-family' );
	register_setting( 'commentator-settings-group', 'commentator_google-font-api-key' );

	register_setting( 'commentator-settings-group', 'commentator_icon-thread-vote' );
	register_setting( 'commentator-settings-group', 'commentator_color-thread-vote' );
	register_setting( 'commentator-settings-group', 'commentator_icon-thread-voted' );
	register_setting( 'commentator-settings-group', 'commentator_color-thread-voted' );
	register_setting( 'commentator-settings-group', 'commentator_icon-send-message' );
	register_setting( 'commentator-settings-group', 'commentator_icon-comment-upvote' );
	register_setting( 'commentator-settings-group', 'commentator_icon-comment-downvote' );
	register_setting( 'commentator-settings-group', 'commentator_icon-comment-reply' );
	register_setting( 'commentator-settings-group', 'commentator_icon-register' );
	register_setting( 'commentator-settings-group', 'commentator_icon-login' );
	register_setting( 'commentator-settings-group', 'commentator_icon-comment-flag' );

	register_setting( 'commentator-settings-group', 'commentator_social-signin' );
	foreach ($arrayProviders as $provider) {
		register_setting( 'commentator-settings-group', 'commentator_id_key-'.$provider[1] );
		register_setting( 'commentator-settings-group', 'commentator_secret_key-'.$provider[1] );
	}

	register_setting( 'commentator-settings-group', 'commentator_load-from-cdn' );

}

function commentator_add_pages() {
	global $commentator_settings_page;
	$commentator_settings_page = add_comments_page(__( 'Overview for the Commentator Plugin', 'commentator' ), __( 'Commentator', 'commentator' ), 'manage_options', 'commentator_overview', 'commentator_overview');

}

function icon_option($title, $name, $defaultIcon, $defaultColor){
?>
	<tr>
        <th scope="row"><?php echo $title; ?></th>
        <td>
            <?php icon_choose_option($name, $defaultIcon); ?>
        	<?php color_option($name, $defaultColor); ?>
        </td>
    </tr>
<?php
}

function icon_only_option($title, $name, $defaultIcon){
?>
	<tr>
        <th scope="row"><?php echo $title; ?></th>
        <td>
            <?php icon_choose_option($name, $defaultIcon); ?>
        </td>
    </tr>
<?php
}

function color_only_option($title, $name, $defaultColor){
?>
	<tr>
        <th scope="row"><?php echo $title; ?></th>
        <td>
            <?php color_option($name, $defaultColor); ?>
        </td>
    </tr>
<?php
}

function icon_choose_option($name, $defaultIcon){
?>
	<div class="commentator-icon-preview">
		<i class="commentator-icon-modal <?php echo get_option('commentator_icon-'.$name, $defaultIcon); ?>"></i>
	</div>
	<input class="commentator-icon-input" type="hidden" name="commentator_icon-<?php echo $name; ?>" value="<?php echo get_option('commentator_icon-'.$name, $defaultIcon); ?>" data-commentator-default="<?php echo $defaultIcon; ?>" />
<?php
}

function color_option($name, $defaultColor){
?>
	<div class="commentator-color-preview">
		<div style="background-color: <?php echo get_option('commentator_color-'.$name, $defaultColor); ?>;">
		</div>
	</div>
	<input class="commentator-color-input" type="hidden" name="commentator_color-<?php echo $name; ?>" value="<?php echo get_option('commentator_color-'.$name, $defaultColor); ?>" data-commentator-default="<?php echo $defaultColor; ?>"/>
<?php
}

function social_provider_option($provider_array){
	$provider = $provider_array[0];
	$provider_perma = $provider_array[1];
	$provider_icon = $provider_array[2];
	?>
		<tr>
	        <th scope="row"><?php echo $provider; ?></th>
	        <td class="commentator-form-icon-social">
	        	<i class="commentator-icon-<?php echo $provider_icon; ?>"></i>
	        </td>
	        <td>
	            <label>ID Key</label>
				<input type="text" name="commentator_id_key-<?php echo $provider_perma; ?>" value="<?php echo get_option('commentator_id_key-'.$provider_perma); ?>" data-commentator-default=""/>
				<label>Secret Key</label>
				<input type="text" name="commentator_secret_key-<?php echo $provider_perma; ?>" value="<?php echo get_option('commentator_secret_key-'.$provider_perma); ?>" data-commentator-default=""/>
	        </td>
	    </tr>
	<?php
}

function get_tab_name($key){
	$result = array(
		"asc" => __("Oldest", "commentator"),
		"desc" => __("Newest", "commentator"),
		"popular" => __("Best", "commentator"),
	);
	return $result[$key];
}

function commentator_overview() {
	global $arrayProviders;
?>
<div id="commentator-admin-settings">
	<div class="wrap">
		<h2>评论员插件设置</h2>
	    <ul class="commentator-admin-tabs">
	    	<li>
	    		<a href="#commentator-admin-general" class="commentator-active"><?php _e( 'General', 'commentator' ); ?></a>
	    	</li>
	    	<li>
	    		<a href="#commentator-admin-icons"><?php _e( 'Icons', 'commentator' ); ?></a>
	    	</li>
	    	<li>
	    		<a href="#commentator-admin-style"><?php _e( 'Style', 'commentator' ); ?></a>
	    	</li>
	    	<li>
	    		<a href="#commentator-admin-social"><?php _e( 'Social', 'commentator' ); ?></a>
	    	</li>
	    	<li>
	    		<a href="#commentator-admin-advanced"><?php _e( 'Advanced', 'commentator' ); ?></a>
	    	</li>
	    </ul>
		<form method="post" action="options.php">
		    <?php settings_fields( 'commentator-settings-group' ); ?>
		    <?php do_settings_sections( 'commentator-settings-group' ); ?>

			<div class="commentator-admin-wrapper">
			    <div class="commentator-tabs-content">
			    	<div id="commentator-admin-general" class="commentator-tab-content commentator-active">
					    <table class="form-table">
					        <tr>
						        <th scope="row"><?php _e( 'Max Threaded Comments Depth (default : 3)', 'commentator' ); ?></th>
						        <td><input type="text" name="commentator_max_depth" value="<?php echo get_option('commentator_max_depth', 3); ?>" data-commentator-default="3"/></td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e( 'Anybody can register ?', 'commentator' ); ?></th>
						        <td><input name="commentator_anybody_register" type="checkbox" value="1" <?php checked( '1', get_option( 'commentator_anybody_register' ) ); ?> /></td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e( 'Human readable time (ago) ?', 'commentator' ); ?></th>
						        <td><input name="commentator_time-ago" type="checkbox" value="1" <?php checked( '1', get_option( 'commentator_time-ago' ) ); ?> /></td>
					        </tr>
					        <tr>
					        	 <th scope="row"><?php _e( 'Chose your password when registering ?', 'commentator' ); ?></th>
						        <td><input name="commentator_register_password_chose" type="checkbox" value="1" <?php checked( '1', get_option( 'commentator_register_password_chose' ) ); ?> /></td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e( 'Disable login tab ?', 'commentator' ); ?></th>
						        <td><input name="commentator_disable-login-tab" type="checkbox" value="1" <?php checked( '1', get_option( 'commentator_disable-login-tab' ) ); ?> /></td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e( 'Enable comment flagging ?', 'commentator' ); ?></th>
						        <td><input name="commentator_flag-comment" type="checkbox" value="1" <?php checked( '1', get_option( 'commentator_flag-comment' ) ); ?> /></td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e( 'From how many flags untill a comment gets unapproved (leave blank for no automation)', 'commentator' ); ?></th>
						        <td><input type="text" name="commentator_flag-limit" value="<?php echo get_option('commentator_flag-limit'); ?>"/></td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e( 'Disable thread votes ?', 'commentator' ); ?></th>
						        <td><input name="commentator_disable-thread-votes" type="checkbox" value="1" <?php checked( '1', get_option( 'commentator_disable-thread-votes' ) ); ?> /></td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e( 'Section of content before the commenting section', 'commentator' ); ?></th>
						        <td><textarea name="commentator_before"><?php echo get_option('commentator_before', '') ?></textarea></td>
					        </tr>

					        <tr>
						        <th scope="row"><?php _e( 'Custom menu html', 'commentator' ); ?></th>
						        <td><textarea name="commentator_custom-menu"><?php echo get_option('commentator_custom-menu', '') ?></textarea></td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e( 'Disable avatars ?', 'commentator' ); ?></th>
						        <td><input name="commentator_disable-avatars" type="checkbox" value="1" <?php checked( '1', get_option( 'commentator_disable-avatars' ) ); ?> /></td>
					        </tr>
					        <tr>
					        	<?php
					        		$tabs = get_option( 'commentator_order-tabs', 'popular,asc,desc|');
					        		$arrayTabs = explode( "|", $tabs );
					        		$enabledTabs = explode( ",", $arrayTabs[0] );
					        		$disabledTabs = explode( ",", $arrayTabs[1] );
					        	?>
						        <th scope="row"><?php _e( 'Order and Disable Tabs ?', 'commentator' ); ?></th>
						        <td>
						        	<span><?php _e( 'Enabled Tabs', 'commentator' ); ?></span>
						        	<div class="commentator-tab-sortable" id="commentator-enabled-tabs">
						        		<?php
						        			foreach ($enabledTabs as $value) {
						        				if(!empty($value)){
											    ?>
											    <div class="commentator-tab" data-commentator-tab="<?php echo $value; ?>">
											    	<span><?php echo get_tab_name($value); ?></span>
											    </div>
											    <?php
												}
											}
						        		?>
						        	</div>
						          	<input id="commentator_order-tabs" name="commentator_order-tabs" type="hidden" value="<?php echo get_option( 'commentator_order-tabs', 'popular,asc,desc|'); ?>">
						        </td>
						        <td>
						        	<span><?php _e( 'Disabled Tabs', 'commentator' ); ?></span>
						        	<div class="commentator-tab-sortable" id="commentator-disabled-tabs">
						        		<?php
						        			foreach ($disabledTabs as $value) {
						        				if(!empty($value)){
											    ?>
											    <div class="commentator-tab" data-commentator-tab="<?php echo $value; ?>">
											    	<span><?php echo get_tab_name($value); ?></span>
											    </div>
											    <?php
												}
											}
						        		?>
						        	</div>
						        </td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e( 'Disable commenting abilities to some user roles ?', 'commentator' ); ?></th>
						        <td colspan="2">
						        	<?php
											// create a new role for Members
											$roles = get_option('commentator_disabled-user-roles', array());
											$roles = is_array($roles) ? $roles : array();
											global $wp_roles;
    										$allRoles = $wp_roles->get_names();
    								?>
						        	<select class="commentator-select2" name="commentator_disabled-user-roles[]" multiple style="width:100%;">
						        			<?php
						        		
											foreach ($allRoles as $role_name => $role_info){
												$selected = in_array ( $role_name, $roles ) ? " selected" : "";
											?>
												<option value="<?php echo $role_name; ?>"<?php echo $selected; ?>><?php echo $role_info; ?></option>
											<?php
											}
						        		?>
						        	</select>
						        </td>
					        </tr>
					    </table>
					</div>
					<div id="commentator-admin-style" class="commentator-tab-content">
					    <table class="form-table">
					    	<tr>
						        <th scope="row"><?php _e( 'Base Theme', 'commentator' ); ?></th>
						        <td>
						        	<?php
											// create a new role for Members
											$selectedTheme = get_option('commentator_base-theme', 'light');
    										$allThemes = array('light', 'dark', 'metro');
    								?>
						        	<select class="commentator-select2" name="commentator_base-theme" style="width:100%;">
						        			<?php
						        		
											foreach ($allThemes as $theme){
												$selected = ($selectedTheme == $theme) ? " selected" : "";
											?>
												<option value="<?php echo $theme; ?>"<?php echo $selected; ?>><?php echo $theme; ?></option>
											<?php
											}
						        		?>
						        	</select>
						        </td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e( 'Google Font Api Key', 'commentator' ); ?></th>
						        <td><input type="text" name="commentator_google-font-api-key" value="<?php echo get_option('commentator_google-font-api-key'); ?>" data-commentator-default=""/></td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e( 'Google Font Family', 'commentator' ); ?></th>
						        <td>
						        	<select class="commentator-select2 commentator-google-fonts" name="commentator_font-family" style="width:100%;" data-commentator-value="<?php echo get_option('commentator_font-family',  "Lato"); ?>"></select>
						        </td>
					        </tr>
					        <tr>
						        <th scope="row"><?php _e( 'Classic Declaration Font Family', 'commentator' ); ?></th>
						        <td>
						        	<input name="commentator_font-family-classic" type="text" value="<?php echo get_option('commentator_font-family-classic', 'Arial, Helvetica, sans-serif'); ?>"/>
						        </td>
					        </tr>
					    </table>
					</div>
					<div id="commentator-admin-icons" class="commentator-tab-content">
					    <table class="form-table">
					        <?php icon_option(__( 'Icon and color for thread voting', 'commentator' ), 'thread-vote', 'commentator-icon-star', '#ffbf00'); ?>
					        <?php icon_only_option(__( 'Icon for thread voted', 'commentator' ), 'thread-voted', 'commentator-icon-ok'); ?>
					        <?php color_only_option(__( 'Color when the thread is voted', 'commentator' ), 'thread-voted', '#8fc847'); ?>
					        <?php icon_only_option(__( 'Icon for comment button', 'commentator' ), 'send-message', 'commentator-icon-rocket'); ?>
					        <?php icon_only_option(__( 'Icon for comment upvote', 'commentator' ), 'comment-upvote', 'commentator-icon-caret-up'); ?>
					        <?php icon_only_option(__( 'Icon for comment downvote', 'commentator' ), 'comment-downvote', 'commentator-icon-caret-down'); ?>
					        <?php icon_only_option(__( 'Icon for comment flagging', 'commentator' ), 'comment-flag', 'commentator-icon-flag'); ?>
					        <?php icon_only_option(__( 'Icon for comment reply', 'commentator' ), 'comment-reply', 'commentator-icon-reply'); ?>
					        <?php icon_only_option(__( 'Icon for comment register', 'commentator' ), 'register', 'commentator-icon-arrow-right'); ?>
					        <?php icon_only_option(__( 'Icon for comment login', 'commentator' ), 'login', 'commentator-icon-arrow-right'); ?>
					    </table>
					</div>
					<div id="commentator-admin-social" class="commentator-tab-content">
					    <table class="form-table">
					        <tr>
						        <th scope="row"><?php _e( 'Enable Social Signin ?', 'commentator' ); ?></th>
						        <td><input name="commentator_social-signin" type="checkbox" value="1" <?php checked( '1', get_option( 'commentator_social-signin' ) ); ?> /></td>
					        </tr>
					        <?php 
					        	foreach ($arrayProviders as $provider) {
									social_provider_option($provider);
								}
							?>
					    </table>
					</div>
					<div id="commentator-admin-advanced" class="commentator-tab-content">
					    <table class="form-table">
					        <tr>
						        <th scope="row"><?php _e( 'I load my scripts myself from my CDN', 'commentator' ); ?></th>
						        <td><input name="commentator_load-from-cdn" type="checkbox" value="1" <?php checked( '1', get_option( 'commentator_load-from-cdn' ) ); ?> /></td>
					        </tr>
					    </table>
					</div>
				</div>
			</div>
			<div class="commentator-admin-footer"><!-- 
		    	<a href="#" class="commentator-reset-default">Reset all to defaults</a>    --> 
		    	<?php submit_button( '提交', 'primary', 'submit-form', false ); ?>
		    </div>
		</form>
	</div>
	<div id="commentator-modal-overlay">
		<section id="commentator-icons">
			<h3><?php _e( 'Choose an icon', 'commentator' ); ?></h3>
			<div id="commentator-icon-list">
				<i class="commentator-icon-adjust"></i>
				<i class="commentator-icon-anchor"></i>
				<i class="commentator-icon-asterisk"></i>
				<i class="commentator-icon-ban-circle"></i>
				<i class="commentator-icon-bar-chart"></i>
				<i class="commentator-icon-barcode"></i>
				<i class="commentator-icon-beaker"></i>
				<i class="commentator-icon-beer"></i>
				<i class="commentator-icon-bell-alt"></i>
				<i class="commentator-icon-bell"></i>
				<i class="commentator-icon-bolt"></i>
				<i class="commentator-icon-book"></i>
				<i class="commentator-icon-bookmark-empty"></i>
				<i class="commentator-icon-bookmark"></i>
				<i class="commentator-icon-briefcase"></i>
				<i class="commentator-icon-bullhorn"></i>
				<i class="commentator-icon-bullseye"></i>
				<i class="commentator-icon-calendar-empty"></i>
				<i class="commentator-icon-calendar"></i>
				<i class="commentator-icon-camera-retro"></i>
				<i class="commentator-icon-camera"></i>
				<i class="commentator-icon-certificate"></i>
				<i class="commentator-icon-check-empty"></i>
				<i class="commentator-icon-check-minus"></i>
				<i class="commentator-icon-check-sign"></i>
				<i class="commentator-icon-check"></i>
				<i class="commentator-icon-circle-blank"></i>
				<i class="commentator-icon-circle"></i>
				<i class="commentator-icon-cloud-download"></i>
				<i class="commentator-icon-cloud-upload"></i>
				<i class="commentator-icon-cloud"></i>
				<i class="commentator-icon-code-fork"></i>
				<i class="commentator-icon-code"></i>
				<i class="commentator-icon-coffee"></i>
				<i class="commentator-icon-cog"></i>
				<i class="commentator-icon-cogs"></i>
				<i class="commentator-icon-collapse-alt"></i>
				<i class="commentator-icon-comment-alt"></i>
				<i class="commentator-icon-comment"></i>
				<i class="commentator-icon-comments-alt"></i>
				<i class="commentator-icon-comments"></i>
				<i class="commentator-icon-credit-card"></i>
				<i class="commentator-icon-crop"></i>
				<i class="commentator-icon-dashboard"></i>
				<i class="commentator-icon-desktop"></i>
				<i class="commentator-icon-download-alt"></i>
				<i class="commentator-icon-download"></i>
				<i class="commentator-icon-edit-sign"></i>
				<i class="commentator-icon-edit"></i>
				<i class="commentator-icon-ellipsis-horizontal"></i>
				<i class="commentator-icon-ellipsis-vertical"></i>
				<i class="commentator-icon-envelope-alt"></i>
				<i class="commentator-icon-envelope"></i>
				<i class="commentator-icon-eraser"></i>
				<i class="commentator-icon-exchange"></i>
				<i class="commentator-icon-exclamation-sign"></i>
				<i class="commentator-icon-exclamation"></i>
				<i class="commentator-icon-expand-alt"></i>
				<i class="commentator-icon-external-link-sign"></i>
				<i class="commentator-icon-external-link"></i>
				<i class="commentator-icon-eye-close"></i>
				<i class="commentator-icon-eye-open"></i>
				<i class="commentator-icon-facetime-video"></i>
				<i class="commentator-icon-fighter-jet"></i>
				<i class="commentator-icon-film"></i>
				<i class="commentator-icon-filter"></i>
				<i class="commentator-icon-fire-extinguisher"></i>
				<i class="commentator-icon-fire"></i>
				<i class="commentator-icon-flag-alt"></i>
				<i class="commentator-icon-flag-checkered"></i>
				<i class="commentator-icon-flag"></i>
				<i class="commentator-icon-folder-close-alt"></i>
				<i class="commentator-icon-folder-close"></i>
				<i class="commentator-icon-folder-open-alt"></i>
				<i class="commentator-icon-folder-open"></i>
				<i class="commentator-icon-food"></i>
				<i class="commentator-icon-frown"></i>
				<i class="commentator-icon-gamepad"></i>
				<i class="commentator-icon-gift"></i>
				<i class="commentator-icon-glass"></i>
				<i class="commentator-icon-globe"></i>
				<i class="commentator-icon-group"></i>
				<i class="commentator-icon-hdd"></i>
				<i class="commentator-icon-headphones"></i>
				<i class="commentator-icon-heart-empty"></i>
				<i class="commentator-icon-heart"></i>
				<i class="commentator-icon-home"></i>
				<i class="commentator-icon-inbox"></i>
				<i class="commentator-icon-info-sign"></i>
				<i class="commentator-icon-info"></i>
				<i class="commentator-icon-key"></i>
				<i class="commentator-icon-keyboard"></i>
				<i class="commentator-icon-laptop"></i>
				<i class="commentator-icon-leaf"></i>
				<i class="commentator-icon-legal"></i>
				<i class="commentator-icon-lemon"></i>
				<i class="commentator-icon-level-down"></i>
				<i class="commentator-icon-level-up"></i>
				<i class="commentator-icon-lightbulb"></i>
				<i class="commentator-icon-location-arrow"></i>
				<i class="commentator-icon-lock"></i>
				<i class="commentator-icon-magic"></i>
				<i class="commentator-icon-magnet"></i>
				<i class="commentator-icon-mail-forward"></i>
				<i class="commentator-icon-mail-reply"></i>
				<i class="commentator-icon-mail-reply-all"></i>
				<i class="commentator-icon-map-marker"></i>
				<i class="commentator-icon-meh"></i>
				<i class="commentator-icon-microphone-off"></i>
				<i class="commentator-icon-microphone"></i>
				<i class="commentator-icon-minus-sign-alt"></i>
				<i class="commentator-icon-minus-sign"></i>
				<i class="commentator-icon-minus"></i>
				<i class="commentator-icon-mobile-phone"></i>
				<i class="commentator-icon-money"></i>
				<i class="commentator-icon-move"></i>
				<i class="commentator-icon-music"></i>
				<i class="commentator-icon-off"></i>
				<i class="commentator-icon-ok-circle"></i>
				<i class="commentator-icon-ok-sign"></i>
				<i class="commentator-icon-ok"></i>
				<i class="commentator-icon-pencil"></i>
				<i class="commentator-icon-phone-sign"></i>
				<i class="commentator-icon-phone"></i>
				<i class="commentator-icon-picture"></i>
				<i class="commentator-icon-plane"></i>
				<i class="commentator-icon-plus-sign"></i>
				<i class="commentator-icon-plus"></i>
				<i class="commentator-icon-print"></i>
				<i class="commentator-icon-pushpin"></i>
				<i class="commentator-icon-puzzle-piece"></i>
				<i class="commentator-icon-qrcode"></i>
				<i class="commentator-icon-question-sign"></i>
				<i class="commentator-icon-question"></i>
				<i class="commentator-icon-quote-left"></i>
				<i class="commentator-icon-quote-right"></i>
				<i class="commentator-icon-random"></i>
				<i class="commentator-icon-refresh"></i>
				<i class="commentator-icon-remove-circle"></i>
				<i class="commentator-icon-remove-sign"></i>
				<i class="commentator-icon-remove"></i>
				<i class="commentator-icon-reorder"></i>
				<i class="commentator-icon-reply-all"></i>
				<i class="commentator-icon-reply"></i>
				<i class="commentator-icon-resize-horizontal"></i>
				<i class="commentator-icon-resize-vertical"></i>
				<i class="commentator-icon-retweet"></i>
				<i class="commentator-icon-road"></i>
				<i class="commentator-icon-rocket"></i>
				<i class="commentator-icon-rotate-left"></i>
				<i class="commentator-icon-rotate-right"></i>
				<i class="commentator-icon-rss-sign"></i>
				<i class="commentator-icon-rss"></i>
				<i class="commentator-icon-screenshot"></i>
				<i class="commentator-icon-search"></i>
				<i class="commentator-icon-share-alt"></i>
				<i class="commentator-icon-share-sign"></i>
				<i class="commentator-icon-share"></i>
				<i class="commentator-icon-shield"></i>
				<i class="commentator-icon-shopping-cart"></i>
				<i class="commentator-icon-sign-blank"></i>
				<i class="commentator-icon-signal"></i>
				<i class="commentator-icon-signin"></i>
				<i class="commentator-icon-signout"></i>
				<i class="commentator-icon-sitemap"></i>
				<i class="commentator-icon-smile"></i>
				<i class="commentator-icon-sort-down"></i>
				<i class="commentator-icon-sort-up"></i>
				<i class="commentator-icon-sort"></i>
				<i class="commentator-icon-spinner"></i>
				<i class="commentator-icon-star-empty"></i>
				<i class="commentator-icon-star-half-full"></i>
				<i class="commentator-icon-star-half-empty"></i>
				<i class="commentator-icon-star-half"></i>
				<i class="commentator-icon-star"></i>
				<i class="commentator-icon-tablet"></i>
				<i class="commentator-icon-tag"></i>
				<i class="commentator-icon-tags"></i>
				<i class="commentator-icon-tasks"></i>
				<i class="commentator-icon-terminal"></i>
				<i class="commentator-icon-thumbs-down"></i>
				<i class="commentator-icon-thumbs-up"></i>
				<i class="commentator-icon-ticket"></i>
				<i class="commentator-icon-time"></i>
				<i class="commentator-icon-tint"></i>
				<i class="commentator-icon-trash"></i>
				<i class="commentator-icon-trophy"></i>
				<i class="commentator-icon-truck"></i>
				<i class="commentator-icon-umbrella"></i>
				<i class="commentator-icon-unlock-alt"></i>
				<i class="commentator-icon-unlock"></i>
				<i class="commentator-icon-upload-alt"></i>
				<i class="commentator-icon-upload"></i>
				<i class="commentator-icon-user-md"></i>
				<i class="commentator-icon-user"></i>
				<i class="commentator-icon-volume-down"></i>
				<i class="commentator-icon-volume-off"></i>
				<i class="commentator-icon-volume-up"></i>
				<i class="commentator-icon-warning-sign"></i>
				<i class="commentator-icon-wrench"></i>
				<i class="commentator-icon-zoom-in"></i>
				<i class="commentator-icon-zoom-out"></i>
				<i class="commentator-icon-file"></i>
				<i class="commentator-icon-file-alt"></i>
				<i class="commentator-icon-cut"></i>
				<i class="commentator-icon-copy"></i>
				<i class="commentator-icon-paste"></i>
				<i class="commentator-icon-save"></i>
				<i class="commentator-icon-undo"></i>
				<i class="commentator-icon-repeat"></i>
				<i class="commentator-icon-text-height"></i>
				<i class="commentator-icon-text-width"></i>
				<i class="commentator-icon-align-left"></i>
				<i class="commentator-icon-align-center"></i>
				<i class="commentator-icon-align-right"></i>
				<i class="commentator-icon-align-justify"></i>
				<i class="commentator-icon-indent-left"></i>
				<i class="commentator-icon-indent-right"></i>
				<i class="commentator-icon-font"></i>
				<i class="commentator-icon-bold"></i>
				<i class="commentator-icon-italic"></i>
				<i class="commentator-icon-strikethrough"></i>
				<i class="commentator-icon-underline"></i>
				<i class="commentator-icon-superscript"></i>
				<i class="commentator-icon-subscript"></i>
				<i class="commentator-icon-link"></i>
				<i class="commentator-icon-unlink"></i>
				<i class="commentator-icon-paper-clip"></i>
				<i class="commentator-icon-eraser"></i>
				<i class="commentator-icon-columns"></i>
				<i class="commentator-icon-table"></i>
				<i class="commentator-icon-th-large"></i>
				<i class="commentator-icon-th"></i>
				<i class="commentator-icon-th-list"></i>
				<i class="commentator-icon-list"></i>
				<i class="commentator-icon-list-ol"></i>
				<i class="commentator-icon-list-ul"></i>
				<i class="commentator-icon-list-alt"></i>
				<i class="commentator-icon-angle-left"></i>
				<i class="commentator-icon-angle-right"></i>
				<i class="commentator-icon-angle-up"></i>
				<i class="commentator-icon-angle-down"></i>
				<i class="commentator-icon-arrow-down"></i>
				<i class="commentator-icon-arrow-left"></i>
				<i class="commentator-icon-arrow-right"></i>
				<i class="commentator-icon-arrow-up"></i>
				<i class="commentator-icon-caret-down"></i>
				<i class="commentator-icon-caret-left"></i>
				<i class="commentator-icon-caret-right"></i>
				<i class="commentator-icon-caret-up"></i>
				<i class="commentator-icon-chevron-down"></i>
				<i class="commentator-icon-chevron-left"></i>
				<i class="commentator-icon-chevron-right"></i>
				<i class="commentator-icon-chevron-up"></i>
				<i class="commentator-icon-chevron-sign-left"></i>
				<i class="commentator-icon-chevron-sign-right"></i>
				<i class="commentator-icon-chevron-sign-up"></i>
				<i class="commentator-icon-chevron-sign-down"></i>
				<i class="commentator-icon-circle-arrow-down"></i>
				<i class="commentator-icon-circle-arrow-left"></i>
				<i class="commentator-icon-circle-arrow-right"></i>
				<i class="commentator-icon-circle-arrow-up"></i>
				<i class="commentator-icon-double-angle-left"></i>
				<i class="commentator-icon-double-angle-right"></i>
				<i class="commentator-icon-double-angle-up"></i>
				<i class="commentator-icon-double-angle-down"></i>
				<i class="commentator-icon-hand-down"></i>
				<i class="commentator-icon-hand-left"></i>
				<i class="commentator-icon-hand-right"></i>
				<i class="commentator-icon-hand-up"></i>
			</div>
		</section>
	</div>
</div>
<?php
exit;
}

?>