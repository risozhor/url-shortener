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

	<h1><?php echo get_admin_page_title(); ?></h1>

	<?php
	$this->settings_api->show_navigation();
	$this->settings_api->show_settings_form();
	?>

</div>
