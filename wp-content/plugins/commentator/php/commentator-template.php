<?php
/**
 * The template for displaying Comments
 *
 * The area of the page that contains both current comments
 * and the comment form. The actual display of comments is
 * handled by a callback to twentytwelve_comment() which is
 * located in the functions.php file.
 *
 * @package WordPress
 * @subpackage Twenty_Twelve
 * @since Twenty Twelve 1.0
 */

/*
 * If the current post is protected by a password and
 * the visitor has not yet entered the password we will
 * return early without loading the comments.
 */
if ( post_password_required() )
	return;
?>
<script src="http://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js"></script>
<?php if(get_option('commentator_google-font-api-key') && get_option('commentator_font-family')){ ?>
<script type="text/javascript">
  WebFont.load({
    google: {
      families: ["<?php echo get_option('commentator_font-family', 'Lato'); ?>"]
    }
  });
</script>

<style type="text/css">
#comments.commentator-area{
	font-family: "<?php echo get_option('commentator_font-family', 'Lato'); ?>";
}
<?php } else { ?>
<style type="text/css">
#comments.commentator-area{
	font-family: "<?php echo get_option('commentator_font-family-classic', 'Helvetica'); ?>";
}
<?php } ?>
.commentator-thread-likes a .commentator_icon-thread-vote{
	color: <?php echo get_option('commentator_color-thread-vote', '#ffbf00'); ?>;
}
.commentator-thread-likes.commentator-active a {
	background: <?php echo get_option('commentator_color-thread-voted', '#8fc847'); ?>;
}
.commentator-thread-likes.commentator-active a .notch {
	border-right: 4px solid <?php echo get_option('commentator_color-thread-voted', '#8fc847'); ?>;
}
</style>

<?php echo do_shortcode( get_option('commentator_before', '') ); ?>


<?php 

global $current_user;
get_currentuserinfo();
$user_profile = $_SESSION["commentator_user_profile"];
$userName = "";
$userLoggedIn = true;

$idUser = $current_user->ID;
$avatarUser = get_avatar( $idUser );

if(is_user_logged_in()){
	$userName = $current_user->user_login;
}
elseif($user_profile != null){
	$idUser = $_SESSION["commentator_provider"]."-".$user_profile['identifier'];
	$avatarUser = '<img alt="" src="'.$user_profile['photoURL'].'" class="avatar avatar-96 photo" height="96" width="96">';
	$userName = $user_profile['displayName'];
}
else{
	$userLoggedIn = false;
	$userName = __("guest", "commentator");
}

global $post;
$id = get_the_ID();
$threadVotes = get_post_meta( $id, "upVote-discussion" );
$threadNumberLikes = count($threadVotes);
$hasUpVoted = in_array ( $idUser, $threadVotes );

?>

<?php if ( comments_open() || get_comments_number() ) : ?>
<div id="comments" class="yui3-cssreset commentator-area <?php echo get_option('commentator_disable-avatars') ? "commentator-without-avatars" : "commentator-with-avatars"; ?> <?php echo $userLoggedIn ? "commentator-logged-in" : "commentator-not-logged-in";?> commentator-<?php echo get_option('commentator_base-theme', 'light'); ?>">

	<div id="commentator-main-header">
		<div id="commentator-global-nav">
			<h4 id="commentator-post-count"><?php printf( _n( 'One comment', '%1$s comments', get_comments_number(), 'commentator' ), number_format_i18n( get_comments_number() ) ); ?></h4>

			<?php 
				if(!get_option('commentator_disable-thread-votes')) {
			?>
			<div id="thread-votes" class="commentator-pull-right">
				<div class="commentator-thread-likes<?php if($hasUpVoted){ echo " commentator-active"; } ?>">
					<a class="commentator-thread-likes-toggle" href="#" title="<?php _e( 'Star this discussion', 'commentator' ); ?>">
						<span class="notch"></span>
						<span class="commentator_icon-thread-vote <?php echo get_option('commentator_icon-thread-vote', 'commentator-icon-star'); ?>"></span>
						<span class="commentator-counter"><?php echo $threadNumberLikes; ?></span>
						<span class="commentator_icon-thread-voted <?php echo get_option('commentator_icon-thread-voted', 'commentator-icon-ok'); ?>"></span>
					</a>
				</div>
			</div>
			<?php
				}
			?>
		</div>
		<?php 
			if ( ! comments_open() && get_comments_number() ){ 
		?>
			<p class="nocomments"><?php _e( 'Comments are closed.' , 'commentator' ); ?></p>
		<?php 
			} 
			else{
		?>
		<div id="commentator-form">
			<form class="commentator-form">
				<div class="commentator-postbox">
					<div class="commentator-avatar">
						<span class="user">
							<?php echo $avatarUser; ?> 
						</span>
					</div>
					<div class="commentator-textarea-wrapper">
						<div class="commentator-textarea" placeholder="<?php _e( 'Join the discussion...', 'commentator' ); ?>"contenteditable></div>
					</div>
					<div class="commentator-author-info-form">
						<div class="commentator-grid">
							<div class="commentator-col-4 commentator-required">
								<input type="text" placeholder="<?php _e( 'Name', 'commentator' ); ?>" name="author-name">
							</div>
							<div class="commentator-col-4 commentator-required">
								<input type="text" placeholder="<?php _e( 'Email', 'commentator' ); ?>" name="author-email">
							</div>
							<div class="commentator-col-4 last">
								<input type="text" placeholder="<?php _e( 'Website', 'commentator' ); ?>" name="author-url">
							</div>
						</div>
					</div>
					<div class="commentator-proceed">
						<button type="submit" class="commentator-add-comment commentator-submit">
							<i class="<?php echo get_option('commentator_icon-send-message', 'commentator-icon-rocket'); ?>"></i>
						</button>
					</div>
				</div>
				<?php comment_id_fields(); ?>
				<input type="hidden" name="current-user-id" value="<?php echo $idUser; ?>"/>
			</form>
		</div>
		<?php 
			}
		?>
		<div id="commentator-main-nav" class="commentator-nav">
			<ul id="commentator-sort">
				<?php
	        		$tabs = get_option( 'commentator_order-tabs', 'popular,asc,desc|');
	        		$arrayTabs = explode( "|", $tabs );
	        		$enabledTabs = explode( ",", $arrayTabs[0] );
	        		$disabledTabs = explode( ",", $arrayTabs[1] );
	        	?>
	        	<?php
        			foreach ($enabledTabs as $key=>$value) {
        				if(!empty($value)){
					    ?>
					    <li <?php if($key==0){?>class="commentator-active"<?php } ?>>
							<a href="#" class="commentator-sort" data-commentator-sort="<?php echo $value; ?>"><?php echo get_tab_name($value); ?></a>
						</li>
					    <?php
						}
					}
        		?>
			</ul>
			<ul class="commentator-pull-right">
				<li class="commentator-in">
					<span><?php _e( 'Hello,', 'commentator' ); ?> <span><?php echo $userName; ?></span></span>
				</li>
				<?php echo get_option('commentator_custom-menu', ''); ?>
				<?php 
					if(!get_option('commentator_disable-login-tab')) {
				?>
				<li class="commentator-in">
					<a class="commentator-logout" href="#">
						<span><?php _e( 'Logout', 'commentator' ); ?></span>
					</a>
				</li>
				<li class="commentator-dropdown commentator-out">
					<a class="commentator-dropdown-toggle" href="#">
						<span><?php _e( 'Login', 'commentator' ); ?></span>
					</a>
					<div class="commentator-dropdown-menu">
						<form class="commentator-login-form">  
							<input type="text" name="username" placeholder="<?php _e( 'Name', 'commentator' ); ?>" />
							<input type="password" name="password" placeholder="<?php _e( 'Password', 'commentator' ); ?>" />
							<label>
								<input type="checkbox">
								<?php _e( 'Remember Me', 'commentator' ); ?>
							</label>
							<button type="submit" class="commentator-login commentator-submit">
								<i class="<?php echo get_option('commentator_icon-login', 'commentator-icon-arrow-right'); ?>"></i>
							</button>
						</form>
						<?php
							if(get_option('commentator_social-signin')){
						?>
						<hr/>
						<p><?php _e( 'Or use one of these social networks', 'commentator' ); ?></p>

						<div class="social-signin-container">
							<?php
								if(get_option('commentator_id_key-facebook') && get_option('commentator_secret_key-facebook')){
							?>
							<a href="#" class="commentator-social-login-button commentator-facebook" data-provider="facebook">
								<i class="commentator-icon-facebook"></i>
							</a>
							<?php
								}
								if(get_option('commentator_id_key-twitter') && get_option('commentator_secret_key-twitter')){
							?>
							<a href="#" class="commentator-social-login-button commentator-twitter" data-provider="twitter">
								<i class="commentator-icon-twitter"></i>
							</a>
							<?php 
								}
								if(get_option('commentator_id_key-google') && get_option('commentator_secret_key-google')){
							?>
							<a href="#" class="commentator-social-login-button commentator-google" data-provider="google">
								<i class="commentator-icon-google-plus"></i>
							</a>
							<?php 
								}
								if(get_option('commentator_id_key-linkedin') && get_option('commentator_secret_key-linkedin')){
							?>
							<a href="#" class="commentator-social-login-button commentator-linkedin" data-provider="linkedin">
								<i class="commentator-icon-linkedin"></i>
							</a>
							<?php 
								}
							?>
						</div>
						<?php
							}
						?>
					</div>
				</li>
				<?php
							}
				?>
				<?php
							if(get_option('commentator_anybody_register')) {
				?>
				<li class="commentator-out"><span>/</span></li>
				<li class="commentator-dropdown commentator-out">
					<a class="commentator-dropdown-toggle" href="#">
						<span><?php _e( 'Register', 'commentator' ); ?></span>
					</a>
					<div class="commentator-dropdown-menu">
						<form class="commentator-register-form">  
							<input type="text" name="username" placeholder="<?php _e( 'Name', 'commentator' ); ?>" />
							<input type="text" name="email" placeholder="<?php _e( 'Email', 'commentator' ); ?>" />
							<?php
								if(get_option('commentator_register_password_chose')) {
							?>
							<input type="password" name="password" placeholder="<?php _e( 'Password', 'commentator' ); ?>" />
							<?php
								}
							?>
							<button type="submit" class="commentator-register commentator-submit">
								<i class="<?php echo get_option('commentator_icon-register', 'commentator-icon-arrow-right'); ?>"></i>
							</button>
						</form>
					</div>
				</li>
				<?php
							}
				?>
			</ul>
		</div>
	</div>

	<?php if ( have_comments() ) : ?>

		<ul id="commentator-comments-list" class="commentator-comments-list">
			<?php

				$comments = get_comments(array(
					'post_id' => $post->ID,
					'order' => ($enabledTabs[0] == 'asc') ? 'ASC' : 'DESC',
					'status' => 'approve'
				));

				if($enabledTabs[0] == 'popular'){
					usort($comments, 'commentator_comment_karma_comparator');
				}

				wp_list_comments( 
					array( 
						'callback' => 'commentator_comment',
						'style' => 'ul',
    					'max_depth' => get_option('commentator_max_depth', 3),
					),
					$comments
				);
			?>
		</ul><!-- .commentlist -->

		<?php
	    	if(get_option('page_comments')){
	    ?>

			<div id="commentator-pagination">

			<?php 
				paginate_comments_links(
					array(
						'base' => '#%#%',
						'add_fragment' => ''
					)
				);
			?> 

			</div>

		<?php
	    	}
	    ?>

	<?php endif; // have_comments() ?>

</div><!-- #comments .comments-area -->
<?php endif; // have_comments() ?>