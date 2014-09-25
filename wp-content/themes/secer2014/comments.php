<?php
if (!empty($_SERVER['SCRIPT_FILENAME']) && 'comments.php' == basename($_SERVER['SCRIPT_FILENAME'])){
die ( __( 'Please do not load this page directly. Thanks!', 'PhotoBroad' ) );
}
if ( post_password_required() ) {
echo '<p class="nocomments">'._e( 'This post is password protected. Enter the password to view comments.', 'PhotoBroad' ).'</p>';
return;
}
?>
 
<?php if ('open' == $post->comment_status || have_comments()) : ?>
 
<div class="comments clearfix" id="comments">
 
<?php if ( have_comments() ) : ?>
 
<h2 class="comments-title"><?php _e( 'Comment Reply', 'PhotoBroad' ); ?></h2>
 
<ol id="commentlist" class="commentlist clearfix">
<?php wp_list_comments(array('type'=>'comment','callback'=>'PhotoBroad_comment','avatar_size'=>48, 'reply_text'=> __( 'Reply', 'PhotoBroad' ) )); ?>
</ol>
 
<?php
if (get_option('page_comments')) {
$comment_pages = paginate_comments_links('prev_text=<&next_text=>&echo=0');
if ($comment_pages) {
echo '<div class="pagenavi">';
echo $comment_pages;
echo '</div>';
}
}
?>
 
<?php endif; ?>
 
<?php if ( comments_open() ) : ?>
 
<div id="respond" class="respond">
<div class="cancel-comment-reply">
<small><?php cancel_comment_reply_link(); ?></small>
</div>
 
<?php if ( get_option('comment_registration') && !$user_ID ) : ?>
 
<p><?php _e( 'You must be', 'PhotoBroad' )?> <a href="<?php echo get_option('siteurl'); ?>/wp-login.php?redirect_to=<?php echo urlencode(get_permalink()); ?>"><?php _e( 'logged in', 'PhotoBroad' ) ?></a> <?php _e( 'to post a comment.', 'PhotoBroad' ) ?></p>
 
<?php else : ?>
 
<form action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post" id="commentform">
 
<?php if ( $user_ID ) : ?>
 
<p>
<?php _e( 'Logged in as', 'PhotoBroad' ) ?> <a href="<?php echo get_option('siteurl'); ?>/wp-admin/profile.php"><?php echo $user_identity; ?></a>. <a href="<?php echo wp_logout_url(get_permalink()); ?>" title="Log out of this account"><?php _e( 'Log out &raquo;', 'PhotoBroad' ) ?></a></p>
 
<?php else: ?>
 
<p>
<input type="text" class="text" tabindex="1" size="22" value="<?php echo $comment_author; ?>" id="author" name="author">
<label for="author"><small><?php _e( 'Name', 'PhotoBroad' ); ?> (<span>*</span>)</small> </label>
</p>
 
<p>
<input type="text" class="text" tabindex="2" size="22" value="<?php echo $comment_author_email; ?>" id="email" name="email" />
<label for="email"><small><?php _e( 'Email', 'PhotoBroad' ); ?> <php _e( 'Will Not Be Published', 'PhotoBroad' ); ?>(<span>*</span>)</small> </label>
</p>
 
<p>
<input type="text" class="text" tabindex="3" size="22" value="<?php echo $comment_author_url; ?>" id="url" name="url">
<label for="url"><small><?php _e( 'Website', 'PhotoBroad' ); ?> ( <?php _e( 'http://', 'PhotoBroad' ); ?></small> )</label>
</p>
 
<?php endif; ?>
 
<?php require_once(TEMPLATEPATH . '/include/smilies.php'); ?>
 
<p>
<textarea tabindex="4" rows="5" id="comment" class="textarea" name="comment"></textarea>
</p>
 
<p>
<input type="submit" class="submit" value="<?php echo _e( 'Submit', 'PhotoBroad' ); ?>" tabindex="5" id="submit" name="submit">
<?php comment_id_fields();?>
</p>
 
<?php do_action('comment_form', $post->ID); ?>
</form>
 
<?php endif; ?>
 
</div>
 
<?php endif; ?>
 
</div>
<?php endif; ?><?php
defined('ABSPATH') or die('This file can not be loaded directly.');

global $comment_ids; $comment_ids = array();
foreach ( $comments as $comment ) {
	if (get_comment_type() == "comment") {
		$comment_ids[get_comment_id()] = ++$comment_i;
	}
} 

if ( !comments_open() ) return;

$my_email = get_bloginfo ( 'admin_email' );
$str = "SELECT COUNT(*) FROM $wpdb->comments WHERE comment_post_ID = $post->ID AND comment_approved = '1' AND comment_type = '' AND comment_author_email";
$count_t = $post->comment_count;

date_default_timezone_set(PRC);
$closeTimer = (strtotime(date('Y-m-d G:i:s'))-strtotime(get_the_time('Y-m-d G:i:s')))/86400;
?>
<div id="respond" class="no_webshot">
	<?php if ( get_option('comment_registration') && !is_user_logged_in() ) { ?>
	<h3 class="queryinfo">
		<?php printf('您必须 <a href="%s">登录</a> 才能发表评论！', wp_login_url( get_permalink() ) );?>
	</h3>
	<?php }elseif( get_option('close_comments_for_old_posts') && $closeTimer > get_option('close_comments_days_old') ) { ?>
	<h3 class="queryinfo">
		文章评论已关闭！
	</h3>
	<?php }else{ ?>
	<form action="<?php echo get_option('siteurl'); ?>/wp-comments-post.php" method="post" id="commentform">
		
		<div class="comt-title">
			<div class="comt-avatar pull-left">
				<?php 
					global $current_user;
					get_currentuserinfo();
					if ( is_user_logged_in() ) 
						echo get_avatar( $current_user->user_email, $size = '54' , deel_avatar_default() );
					elseif( !is_user_logged_in() && get_option('require_name_email') && $comment_author_email=='' ) 
						echo get_avatar( $current_user->user_email, $size = '54' , deel_avatar_default() );
					elseif( !is_user_logged_in() && get_option('require_name_email') && $comment_author_email!=='' )  
						echo get_avatar( $comment->comment_author_email, $size = '54' , deel_avatar_default() );
					else
						echo get_avatar( $comment->comment_author_email, $size = '54' , deel_avatar_default() );
				?>
			</div>
			<div class="comt-author pull-left">
			<?php 
				if ( is_user_logged_in() ) {
					printf($user_identity.'<span>发表我的评论</span>');
				}else{
					if( get_option('require_name_email') && !empty($comment_author_email) ){
						printf($comment_author.' <span>发表我的评论</span> &nbsp; <a class="switch-author" href="javascript:;" data-type="switch-author" style="font-size:12px;">换个身份</a>');
					}else{
						printf('发表我的评论');
					}
				}
			?>
			</div>
			<a id="cancel-comment-reply-link" class="pull-right" href="javascript:;">取消评论</a>
		</div>
		
		<div class="comt">
			<div class="comt-box">
				<textarea placeholder="写点什么..." class="input-block-level comt-area" name="comment" id="comment" cols="100%" rows="3" tabindex="1" onkeydown="if(event.ctrlKey&amp;&amp;event.keyCode==13){document.getElementById('submit').click();return false};"></textarea>
				<div class="comt-ctrl">
					<button class="btn btn-primary pull-right" type="submit" name="submit" id="submit" tabindex="5"><i class="fa fa-check-square-o"></i> 提交评论</button>
					<div class="comt-tips pull-right"><?php comment_id_fields(); do_action('comment_form', $post->ID); ?></div>
					<span data-type="comment-insert-smilie" class="muted comt-smilie"><i class="fa fa-smile-o"></i> 表情</span>
					<span class="muted comt-mailme"><?php deel_add_checkbox() ?></span>
				</div>
			</div>

			<?php if ( !is_user_logged_in() ) { ?>
				<?php if( get_option('require_name_email') ){ ?>
					<div class="comt-comterinfo" id="comment-author-info" <?php if ( !empty($comment_author) ) echo 'style="display:none"'; ?>>
						<h4>Hi，您需要填写昵称和邮箱！</h4>
						<ul>
							<li class="form-inline"><label class="hide" for="author">昵称</label><input class="ipt" type="text" name="author" id="author" value="<?php echo esc_attr($comment_author); ?>" tabindex="2" placeholder="昵称"><span class="help-inline">昵称 (必填)</span></li>
							<li class="form-inline"><label class="hide" for="email">邮箱</label><input class="ipt" type="text" name="email" id="email" value="<?php echo esc_attr($comment_author_email); ?>" tabindex="3" placeholder="邮箱"><span class="help-inline">邮箱 (必填)</span></li>
							<li class="form-inline"><label class="hide" for="url">网址</label><input class="ipt" type="text" name="url" id="url" value="<?php echo esc_attr($comment_author_url); ?>" tabindex="4" placeholder="网址"><span class="help-inline">网址</span></li>
						</ul>
					</div>
				<?php } ?>
			<?php } ?>
		</div>

		
	</form>
	<?php } ?>
</div>
<?php  

if ( have_comments() ) { 
?>
<div id="postcomments">
	<div id="comments">
		<i class="fa fa-comments-o"></i> <b><?php echo ' ('.$count_t.')'; ?></b>个小伙伴在吐槽
	</div>
	<ol class="commentlist">
		<?php wp_list_comments('type=comment&callback=deel_comment_list') ?>
	</ol>
	<div class="commentnav"	>
		<?php paginate_comments_links('prev_text=«&next_text=»');?>
	</div>
</div>
<?php 
} 
?>