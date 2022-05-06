<?php
/**
 * Plugin Name: Advanced Comments Edit
 * Version: 1.0.0
 * Plugin URI: https://pixelgrade.com
 * Description: Gain access to advanced comment data: assigned post ID, parent comment ID, comment owner user ID, author IP, author agent and the comment date.
 * Author: pixelgrade, vlad.olaru
 * Author URI: https://pixelgrade.com
 * Requires at least: 4.9.9
 * Tested up to: 5.9.4
 * Text Domain: advanced-comments-edit
 * Domain Path: /languages/
 * License: GPLv3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * This code started from the Comments-advanced plugin, but it was updated and fixed.
 * @link http://wordpress.org/plugins/comments-advanced/
 */

function advanced_comments_edit_add_meta() {
	add_meta_box( 'comment-info', esc_html__( 'Advanced Comment Details', '__plugin_txtd' ), 'advanced_comments_edit_metabox', 'comment', 'normal' );
}

add_action( 'admin_menu', 'advanced_comments_edit_add_meta' );

function advanced_comments_edit_metabox() {
	global $comment;
	?>

	<table class="widefat" cellspacing="0">
		<tbody>
		<tr class="alternate">
			<td class="textright">
				<label for="comment_post_id">Post ID</label>
			</td>
			<td>
				<?php
				$html       = '';
				$posts_list = get_posts( [
					'numberposts' => - 1,
					'post_status' => 'publish',
					'fields'      => 'ids',
					'post_type'   => [ 'post', 'page' ],
					'order'       => 'DESC',
					'orderby'     => 'modified',
				] );
				if ( ! empty( $posts_list ) ) {
					foreach ( $posts_list as $post_id ) {
						$selected = '';
						if ( ! empty( $comment->comment_post_ID ) && $post_id == $comment->comment_post_ID ) {
							$selected = ' selected="selected"';
						}

						$post_title = trim( wp_strip_all_tags( get_the_title( $post_id ) ) );
						if ( empty( $post_title ) ) {
							$post_title = '[Empty title]';
						}

						if ( mb_strlen( $post_title ) > 50 ) {
							$post_title = mb_substr( $post_title, 0, 50, 'UTF-8' ) . '...';
						}
						$html .= '<option value="' . esc_attr( $post_id ) . '"' . $selected . '>[' . $post_id . ']' . '[' . get_post_type( $post_id ) . '] ' . $post_title . '</option>';
					}
				} else {
					// No posts found.
					$html = '<option>No posts found</option>';
				}
				?>
				<select name="comment_post_id" id="comment_post_id">
					<?php echo $html; ?>
				</select>
				<div>Legend: [Post ID][Post Type] Post title</div>
			</td>
		</tr>
		<tr>
			<td class="textright">
				<label for="comment_parent">Parent Comment ID</label>
			</td>
			<td>
				<?php
				$html          = '';
				$comments_list = get_comments( array(
					'post_id'          => $comment->comment_post_ID,
					'comment_approved' => 1,
				) );

				foreach ( $comments_list as $comment_item ) {
					// Only show earlier comments, including from itself.
					if ( $comment_item->comment_ID < $comment->comment_ID ) {
						$selected = '';
						if ( $comment_item->comment_ID == $comment->comment_parent ) {
							$selected = ' selected="selected"';
						}

						$comment_content = trim( wp_strip_all_tags( $comment_item->comment_content ) );
						if ( empty( $comment_content ) ) {
							$comment_content = '[Empty comment]';
						}

						if ( mb_strlen( $comment_content ) > 50 ) {
							$comment_content = mb_substr( wp_strip_all_tags( $comment_item->comment_content ), 0, 50, 'UTF-8' ) . '...';
						}
						$html .= '<option value="' . esc_attr( $comment_item->comment_ID ) . '"' . $selected . '>';
						$html .= '[' . $comment_item->comment_ID . '][' . $comment_item->comment_author . '] ' . $comment_content . '</option>';
					}
				}
				?>
				<select name="comment_parent" id="comment_parent">
					<option value='0'>[0][Without parent comment]</option>
					<?php echo $html; ?>
				</select>
				<div>Legend: [Comment ID][Comment Author] Comment content</div>
			</td>
		</tr>
		<tr class="alternate">
			<td class="textright">
				<label for="comment_user_id">User ID</label>
			</td>
			<td>
				<?php
				$html = '';

				$users_list = get_users( [ 'fields' => 'all_with_meta' ] );

				/** @var WP_User $user_item */
				foreach ( $users_list as $user_item ) {
					$selected = '';
					if ( $user_item->ID == $comment->user_id ) {
						$selected = ' selected="selected"';
					}

					$html .= '<option value="' . esc_attr( $user_item->ID ) . '"' . $selected . '>';
					$html .= '[' . $user_item->ID . '][' . implode( ',', $user_item->roles ) . '] ' . $user_item->display_name . '</option>';
				}
				?>
				<select name="comment_user_id" id="comment_user_id">
					<option value='0'>[0][Without role] Guest</option>
					<?php echo $html; ?>
				</select>
				<div>Legend: [User ID][User Role] Username</div>
			</td>
		</tr>
		<tr>
			<td class="textright">
				<label for="comment_author_ip">Author IP</label>
			</td>
			<td>
				<input type="text" name="comment_author_ip" id="comment_author_ip"
				       value="<?php echo esc_attr( $comment->comment_author_IP ); ?>" size="40"/>
			</td>
		</tr>
		<tr class="alternate">
			<td class="textright">
				<label for="comment_agent">Author Agent</label>
			</td>
			<td>
				<input type="text" name="comment_agent" id="comment_agent"
				       value="<?php echo esc_attr( $comment->comment_agent ); ?>" size="40"/>
			</td>
		</tr>
		<tr>
			<td class="textright">
				<label for="comment_date">Comment Date</label>
			</td>
			<td>
				<input type="text" name="comment_date" id="comment_date"
				       value="<?php echo esc_attr( $comment->comment_date ); ?>" size="40"/>
			</td>
		</tr>
		</tbody>
	</table>


	<?php
}

function advanced_comments_edit_save_meta( $data ) {

	$comment_post_ID   = absint( $_POST['comment_post_id'] );
	$comment_parent    = absint( $_POST['comment_parent'] );
	$user_id           = absint( $_POST['comment_user_id'] );
	$comment_author_IP = esc_attr( $_POST['comment_author_ip'] );
	$comment_agent     = esc_attr( $_POST['comment_agent'] );
	$comment_date      = esc_attr( $_POST['comment_date'] );

	// Bail since this is very weird.
	if ( empty( $data['comment_ID'] ) ) {
		return $data;
	}

	// The comment parent cannot be self.
	if ( $comment_parent == $data['comment_ID'] ) {
		return $data;
	}

	// Check if new parent post exists.
	$post = get_post( $comment_post_ID );
	if ( ! $post ) {
		return $data;
	}

	// Check if new user exists, unless it was assigned to a guest.
	if ( 0 !== $user_id && ! get_user_by( 'id', $user_id ) ) {
		return $data;
	}

	$comment             = get_comment( $data['comment_ID'] );
	$old_comment_post_ID = $comment->comment_post_ID; // get old comment_post_ID

	if ( $old_comment_post_ID != $comment_post_ID ) {    // if comment_post_ID was updated
		wp_update_comment_count( $old_comment_post_ID ); // we need to update comment counts for both posts (old and new)
		wp_update_comment_count( $comment_post_ID );
		// Reset comment_parent if comment was moved to another post.
		$comment_parent = 0;
	}

	// Merge with existing data overwriting with our own.
	return array_merge( $data, [
			'comment_post_ID'   => $comment_post_ID,
			'comment_parent'    => $comment_parent,
			'user_id'           => $user_id,
			'comment_author_IP' => $comment_author_IP,
			'comment_agent'     => $comment_agent,
			'comment_date'      => $comment_date,
		] );
}
// Use the filter instead of the 'edit_comment' action to avoid infinite loops.
add_filter( 'wp_update_comment_data', 'advanced_comments_edit_save_meta', 10, 1 );
