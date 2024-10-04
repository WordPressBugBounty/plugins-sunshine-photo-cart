<div class="sunshine--image--comment">
	<div class="sunshine--image--comment--author"><?php echo $comment->comment_author; ?></div>
	<div class="sunshine--image--comment--date"><?php echo get_comment_date( '', $comment ); ?></div>
	<div class="sunshine--image--comment--content"><?php echo $comment->comment_content; ?></div>
	<?php if ( ! $comment->comment_approved ) { ?>
		<div class="sunshine--image--comment--approval"><?php _e( 'Comment awaiting approval', 'sunshine-photo-cart' ); ?></div>
	<?php } ?>
</div>
