<?php

/**
 *
 * @link       https://mythemeshop.com/plugins/url-shortener/
 * @since      1.0.0
 *
 * @package    FIRST_URL_Shortener
 * @subpackage FIRST_URL_Shortener/admin/partials
 */
?>

<div class="wrap">
	<h1><?php echo $title; ?>  <?php if ( ! empty( $editing_link ) ) : ?><a href="<?php echo admin_url( 'admin.php?page=url_shortener_add' ); ?>" class="page-title-action"><?php echo esc_html_x('Add New', 'link'); ?></a><?php endif; ?></h1>

	<?php if ( isset( $_GET['added'] ) ) : ?>
		<div id="message" class="updated notice is-dismissible"><p><?php _e( 'Link added.', 'first-url-shortener' ); ?></p></div>
	<?php endif; ?>

	<form name="<?php echo esc_attr( $form_name ); ?>" id="<?php echo esc_attr( $form_name ); ?>" method="post" action="<?php echo $form_action; ?>">
		<?php if ( ! empty( $link_added ) ) {
			echo $link_added;
		}
		wp_nonce_field( $nonce_action );
		wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false );
		wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>

		<div id="poststuff">

			<div id="post-body" class="metabox-holder columns-<?php echo 1 == get_current_screen()->get_columns() ? '1' : '2'; ?>">

				<div id="post-body-content">
					<div id="main-fields-box" class="stuffbox">
						<?php 
						$this->editor_api->show_navigation();
						$this->editor_api->show_editor_form();
						?>
					</div>
				</div><!-- /post-body-content -->

				<div id="postbox-container-1" class="postbox-container">
					<?php
					/** This action is documented in wp-admin/includes/meta-boxes.php */
					do_action( 'submitshortlink_box' );
					$side_meta_boxes = do_meta_boxes( 'short_link', 'side', $link );
					?>
				</div>

				<div id="postbox-container-2" class="postbox-container">
					<?php
					do_meta_boxes('short_link', 'normal', $link);
					do_meta_boxes('short_link', 'advanced', $link);
					?>
				</div>

				<?php if ( $link_id ) : ?>
					<input type="hidden" name="action" value="edit_short_link" />
					<input type="hidden" name="link_id" value="<?php echo (int) $link_id; ?>" />
					<input type="hidden" name="cat_id" value="<?php //echo (int) $cat_id ?>" />
				<?php else: ?>
					<input type="hidden" name="action" value="add_short_link" />
				<?php endif; ?>

			</div>

		</div>

	</form>
</div>
