<?php

/**
 *
 * @link       https://mythemeshop.com/plugins/url-shortener/
 * @since      1.0.0
 *
 * @package    FIRST_URL_Shortener
 * @subpackage FIRST_URL_Shortener/admin/partials
 */

global $linksListTable;

//Fetch, prepare, sort, and filter our data...
$linksListTable->prepare_items();

?>
<div class="wrap">
    
    <div id="icon-users" class="icon32"><br/></div>
    <h1><?php echo get_admin_page_title(); ?> <a href="<?php echo admin_url( 'admin.php?page=url_shortener_add' ); ?>" class="page-title-action"><?php _e( 'Add New', 'first-url-shortener' ); ?></a></h1>
    
    <form id="shortlinks-form" method="POST">
        <!-- we need to ensure that the form posts back to our current page -->
        <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <?php $linksListTable->search_box( __( 'Search Links', 'first-url-shortener' ), 'search_links' ); ?>
        <!-- Now we can render the completed list table -->
        <?php $linksListTable->display() ?>
    </form>
    
</div>
