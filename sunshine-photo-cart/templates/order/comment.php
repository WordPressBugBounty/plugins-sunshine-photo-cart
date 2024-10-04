<div class="sunshine--order--comment">
	<div class="sunshine--order--comment--data">
		<span class="sunshine--order--comment--author">
			<?php
			if ( $comment->comment_author ) {
				echo $comment->comment_author;
			} elseif ( $comment->user_id ) {
				$user = get_user_by( 'id', $comment->user_id );
				echo $user->display_name;
			}
			?>
			@
			<?php echo $comment->comment_date; ?>
		</span>
		<span>
	</div>
	<div class="sunshine--order--comment--content">
		<?php echo $comment->comment_content; ?>
	</div>
</div>
