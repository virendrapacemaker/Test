<?php
/**
 * The template for displaying Comments.
 *
 * Based on WordPress Twenty Ten theme
 * The area of the page that contains both current comments
 * and the comment form.  The actual display of comments is
 * handled by a callback to twentyten_comment which is
 * located in the inc/comments.php file.
 *
 * @package Ace Gallery
 * @subpackage Comments
 * @since 1.1.0
 */
?>

			<div id="comments">
<?php if ( post_password_required() ) : ?>
				<p class="nopassword"><?php esc_html_e( 'This post is password protected. Enter the password to view any comments.', 'ace-xml-gallery-builder' ); ?></p>
			</div><!-- #comments -->
<?php
		/* Stop the rest of comments.php from being processed,
		 * but don't kill the script entirely -- we still have
		 * to fully load the template.
		 */
		return;
	endif;
?>
<?php if ( ace_login_required() ) : ?>
        <p class="nopassword"><?php esc_html_e( 'You should be logged in to view this page. Log in to view any comments.', 'ace-xml-gallery-builder' ); ?></p>
			</div><!-- #comments -->
<?php
		return;
 endif;
?>
<?php if ( ace_level_required() ) : /* translators 1: <br /> */ ?>	
        <p class="nopassword"><?php sprintf( esc_html_e( 'Sorry, you are not allowed to view this item.%1sThe owner of the gallery has set access restrictions', 'ace-xml-gallery-builder' ), '<br />' ); ?></p>
			</div><!-- #comments -->
<?php
		return;
 endif;
?>

<?php
	// You can start editing here -- including this comment! 
?>

<?php if ( have_comments() ) : ?>
			<h3 id="comments-title"><?php
			printf( _n( 'One Response to %2$s', '%1$s Responses to %2$s', get_comments_number(), 'ace-xml-gallery-builder' ),
			number_format_i18n( get_comments_number() ), '<em>' . ace_get_the_title() . '</em>' );
			?></h3>

<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // Are there comments to navigate through? ?>

			<div class="navigation">
			<nav id="comment-nav-above">
				<div class="nav-previous"><?php previous_comments_link( __( '<span class="meta-nav">&larr;</span> Older Comments', 'ace-xml-gallery-builder' ) ); ?></div>
				<div class="nav-next"><?php next_comments_link( __( 'Newer Comments <span class="meta-nav">&rarr;</span>', 'ace-xml-gallery-builder' ) ); ?></div>
			</nav>
			</div> <!-- .navigation -->
<?php endif; // check for comment navigation ?>

			<ol class="commentlist" id="commentslist">
				<?php
					/* Loop through and list the comments. Tell wp_list_comments()
					 * to use ace_comment() to format the comments.
					 * If you want to overload this in a child theme then you can
					 * define ace_comment() and that will be used instead.
					 * See ace_comment() in inc/comments.php for more.
					 */
					wp_list_comments( array( 'callback' => 'ace_comment' ) );
				?>
			</ol>

<?php if ( get_comment_pages_count() > 1 && get_option( 'page_comments' ) ) : // Are there comments to navigate through? ?>
			<div class="navigation">
			<nav id="comment-nav-below">
				<div class="nav-previous"><?php previous_comments_link( __( '<span class="meta-nav">&larr;</span> Older Comments', 'ace-xml-gallery-builder' ) ); ?></div>
				<div class="nav-next"><?php next_comments_link( __( 'Newer Comments <span class="meta-nav">&rarr;</span>', 'ace-xml-gallery-builder' ) ); ?></div>
			</nav>
			</div><!-- .navigation -->
<?php endif; // check for comment navigation ?>

<?php else : // or, if we don't have comments:

	/* If there are no comments and comments are closed,
	 * let's leave a little note, shall we?
	 */
	if ( !  comments_open() ) :
?>
	<p class="nocomments"><?php esc_html_e( 'Comments are closed.', 'ace-xml-gallery-builder' ); ?></p>
<?php endif; // end !  comments_open() ?>

<?php endif; // end have_comments() ?>

<?php ace_comment_form(); ?>

</div><!-- #comments -->