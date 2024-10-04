<?php
class SPC_Background_Delete_Gallery_Images extends SPC_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 'delete_gallery_images';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $attachment_id ) {
		wp_delete_attachment( $attachment_id, true );
		SPC()->log( 'Attachment ' . $attachment_id . ' has been deleted from gallery deletion' );
		return false;
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {

		parent::complete();

		// Show notice to user or perform some other arbitrary task...
	}

}
