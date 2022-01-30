<?php

/**
 *
 * @link       https://mythemeshop.com/plugins/url-shortener/
 * @since      1.0.0
 *
 * @package    FIRST_URL_Shortener
 * @subpackage FIRST_URL_Shortener/admin
 * @author     MyThemeShop <support-team@mythemeshop.com>
 */

class Short_Links_List_Table extends WP_List_Table {

    var $links;
    var $cat_id;
    function __construct(){
        global $status, $page;
        $this->cat_id = isset($_REQUEST['cat_id']) ? $_REQUEST['cat_id'] : '';
        
        //Set parent defaults
        parent::__construct( array(
            'singular'  => 'link',     //singular name of the listed records
            'plural'    => 'links',    //plural name of the listed records
            'ajax'      => false        //does this table support ajax?
        ) );
        
    }

    function column_default($item, $column_name){
        switch($column_name){
            case 'name':
            case 'date':
                return $item[$column_name];
            default:
                return print_r($item,true); //Show the whole array for troubleshooting purposes
        }
    }

    function column_shortlink( $item ) {
        //Build row actions
        $edit_link = admin_url( 'admin.php?page=url_shortener_edit&link_id=' . $item['link_id'] );
        $redirection_method = explode( ';', $item['link_redirection_method']);
        $redirection_method = ucfirst( $redirection_method[0] );

        $actions = array(
            'method'      => '<span class="redirection-method">' . $redirection_method . '</span>',
            'edit'      => '<a href="' . $edit_link . '">' . __( 'Edit', 'first-url-shortener' ) . '</a>',
            'delete'    => '<a href="' . wp_nonce_url( admin_url( 'admin.php?page=url_shortener_links&action=delete_short_link&link_id=' . $item['link_id'] ), 'url_shortener_action' ) . '" onclick="if ( confirm(\''.esc_js(sprintf(__("You are about to delete this link '%s'\n  'Cancel' to stop, 'OK' to delete."), $item['link_name'] )).'\') ) {return true;}return false;">' . __( 'Delete', 'first-url-shortener' ) . '</a>',
        );
        
        $description = ( ! empty( $item['link_description'] ) ? "<p class='description'>{$item['link_description']}</p>" : '' );
        $url = trailingslashit( get_bloginfo( 'url' ) ) .  $item['link_name'];
        // Return the title contents
        return sprintf('<a href="%1$s" class="link-title">%2$s</a> <span class="redirection-method">%3$s</span> %4$s',
            $edit_link,
            $item['title'],
            '<a href="' . $url . '" class="open-shortlink" target="_blank" title="'.$url.'"><span class="dashicons dashicons-external"></span></a>',
            //$redirection_method,
            //$description,
            $this->row_actions( $actions )
        );
    }

    function column_targeturl( $item ) {
        echo '<a href="' . esc_attr( $item['link_url'] ) . '" title="' . esc_attr( $item['link_url'] ) . '" target="_blank">' . url_shorten( $item['link_url'] ) . '</a>';
    }

    function column_category( $item ) {
        $cats = FIRST_URL_Shortener_Admin::get_link_cats( $item['link_id'] );
        $i = 0;
        $column = '';
        foreach ($cats as $k => $cat) {
            $i++;
            if ( $i == '4' ) {
                $column .= ' ... ';
                break;
            }
            $term = get_term( $cat, 'short_link_category' );
            $column .= '<a href="'.add_query_arg( array('cat_id' => $cat, '_wpnonce' => wp_create_nonce( 'url_shortener_action' ) ), admin_url( 'admin.php?page=url_shortener_links' ) ).'">'.$term->name.'</a>, ';
        }
        $column = rtrim( $column, ', ' );
        echo $column;
    }

    function column_url( $item ) {
        $content = $item['content'];
        
        $url = trailingslashit( get_bloginfo( 'url' ) ) .  $item['link_name'];
        $shortcode = '[shortlink '.$item['link_name'].']'.$content.'[/shortlink]';
        $html = esc_attr( '<a href="'.$url.'">'.$content.'</a>' );
        $val = $url;
        
        $url_title = __('Link URL', 'first-url-shortener');
        $shortcode_title = __('Shortcode', 'first-url-shortener');
        $html_title = __('Link HTML', 'first-url-shortener');
        $title = $url_title;

        $state = 'url';
        $defaults = get_option( 'urlshortener_defaults' );
        if ( is_array( $defaults ) && isset( $defaults['preview_tool'] ) )  { 
            
            switch ($defaults['preview_tool']) {
                case 'shortcode':
                    $val = $shortcode;
                    $state = 'shortcode';
                    $title = $shortcode_title;
                break;

                case 'html':
                    $val = $html;
                    $state = 'html';
                    $title = $html_title;
                break;
            }
        } ?>
        <span class="copy-action-title"><?php echo $title; ?></span>
        <div id="copy-action">
            <input type="text" readonly="readonly" id="shortlink-url-<?php echo $item['link_id']; ?>" class="shortlink-url-field" value="<?php echo $val; ?>" data-state="<?php echo $state; ?>" data-url="<?php echo $url; ?>" data-shortcode="<?php echo $shortcode; ?>" data-html="<?php echo esc_attr( $html ); ?>" title="<?php echo $title; ?>" data-urltitle="<?php echo $url_title; ?>" data-shortcodetitle="<?php echo $shortcode_title; ?>" data-htmltitle="<?php echo esc_attr( $html_title ); ?>" />
            <button class="button shortlink-url-field-switch" title="<?php esc_attr_e( 'Change code', 'first-url-shortener' ); ?>"><span class="dashicons dashicons-editor-code"></span></button>
            <button class="button shortlink-url-field-copy" data-clipboard-target="#shortlink-url-<?php echo $item['link_id']; ?>" title="<?php esc_attr_e( 'Copy to clipboard', 'first-url-shortener' ); ?>"><span class="dashicons dashicons-clipboard"></span></button>
        </div>
        <?php
    }

    function column_clicks( $item ) {
        $count = FIRST_URL_Shortener_Admin::get_link_click_count( $item['link_id'] );
        echo $count;
    }

    function column_date( $item ) {
        /* translators: Links list table date format, see https://secure.php.net/date */
        $datef = __( 'M j, Y @ H:i', 'first-url-shortener' );
        $date = mysql2date( $datef, $item['link_created'], false );
        echo $date;
    }

    function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  //Let's simply repurpose the table's singular label ("movie")
            /*$2%s*/ $item['link_id']           //The value of the checkbox should be the record's id
        );
    }

    public function search_box( $text, $input_id ) { ?>
        <p class="search-box">
          <label class="screen-reader-text" for="<?php echo $input_id ?>"><?php echo $text; ?>:</label>
          <input type="search" id="<?php echo $input_id ?>" name="s" value="<?php _admin_search_query(); ?>" />
          <?php submit_button( $text, 'button', false, false, array( 'id' => 'search-submit', 'formmethod' => 'GET' ) ); ?>
      </p>
    <?php }

    function get_columns() {
        $columns = array(
            'cb'        => '<input type="checkbox" />', //Render a checkbox instead of text
            'shortlink' => __('Short Link', 'first-url-shortener'),
            'url'       => __('Copy Link', 'first-url-shortener'),
            'category'  => __('Category', 'first-url-shortener'),
            'targeturl' => __('Target URL', 'first-url-shortener'),
            'clicks'    => __('Clicks', 'first-url-shortener'),
            'date'      => __('Date', 'first-url-shortener'),
        );
        return $columns;
    }

    public function get_hidden_columns() {
        $screen = get_current_screen();
        $hidden = get_user_option( 'manage' . $screen->id . 'columnshidden' );
        $use_defaults = ! is_array( $hidden );
        if ( $use_defaults ) {
            $hidden = array( 'category' );
            /**
             * Filters the default list of hidden columns.
             *
             * @since 1.0.0
             *
             * @param array     $hidden An array of columns hidden by default.
             * @param WP_Screen $screen WP_Screen object of the current screen.
             */
            $hidden = apply_filters( 'default_hidden_columns', $hidden, $screen );
        }
        /**
         * Filters the list of hidden columns.
         *
         * @since 1.0.0
         *
         * @param array     $hidden An array of hidden columns.
         * @param WP_Screen $screen WP_Screen object of the current screen.
         * @param bool      $use_defaults Whether to show the default columns.
         */
        return apply_filters( 'hidden_columns', $hidden, $screen, $use_defaults );
    }

    function get_sortable_columns() {
        $sortable_columns = array(
            'shortlink'     => array('link_name',false),     //true means it's already sorted
            'targeturl'    => array('link_url',false),
            //'clicks'  => array('clicks',false),
            'date'  => array('link_created',true)
        );
        return $sortable_columns;
    }


    function get_bulk_actions() {
        $actions = array(
            'bulk_delete_short_links' => __( 'Delete', 'first-url-shortener' )
        );
        return $actions;
    }

    function extra_tablenav( $which ) {
        

        if ( 'top' != $which )
            return;

        wp_nonce_field( 'url_shortener_action' ); ?>

        <div class="alignleft actions">
            <?php
            $dropdown_options = array(
                'selected' => $this->cat_id,
                'name' => 'cat_id',
                'taxonomy' => 'short_link_category',
                'show_option_all' => get_taxonomy( 'short_link_category' )->labels->all_items,
                'hide_empty' => true,
                'hierarchical' => 1,
                'show_count' => 0,
                'orderby' => 'name',
            );
            echo '<label class="screen-reader-text" for="cat_id">' . __( 'Filter by category', 'first-url-shortener' ) . '</label>';
            wp_dropdown_categories( $dropdown_options );
            submit_button( __( 'Filter', 'first-url-shortener' ), '', 'filter_action', false, array( 'id' => 'post-query-submit', 'formmethod' => 'GET' ) );
            ?>
        </div>
        <?php
    }

    function prepare_items() {
        global $wpdb; //This is used only if making any database queries
        
        global $per_page;
        $per_page = $this->get_items_per_page( 'short_links_per_page', 10 );
        $per_page = apply_filters( 'short_links_per_page', $per_page );
        if ( ! $per_page ) {
            $per_page = 10;
        }

        $offset = 0;
        // If no sort, default to title
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'link_created';
        
        // If no order, default to desc
        $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'desc';
        //$paged = ( ! empty($_GET['paged'] ) ) ? $_GET['paged'] : 1;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;
        $search = ( ! empty ( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : '';
        
        $columns = $this->get_columns();
        $hidden = $this->get_hidden_columns();
        $sortable = $this->get_sortable_columns();
        
        $this->_column_headers = array($columns, $hidden, $sortable);
        
        $this->links = FIRST_URL_Shortener_Admin::get_links( array(
            'cat' => $this->cat_id,
            'search' => $search,
            'limit' => $per_page, 
            'offset' => $offset, 
            'orderby' => $orderby, 
            'order' => $order 
        ), ARRAY_A );

        $data = $this->links;
        if ( is_array( $data ) ) {
            foreach ($data as $link_k => $link) {
                $content = $link['link_url'];
                $title = '/'.$link['link_name'];
                if ( ! empty( $link['link_cloak_url'] ) ) {
                    $content = $link['link_cloak_url'];
                }
                if ( ! empty( $link['link_attr_title'] ) ) {
                    $content = $link['link_attr_title'];
                }
                if ( ! empty( $link['link_anchor'] ) ) {
                    $content = $title = $link['link_anchor'];
                }
                $content = apply_filters( 'short_link_default_content', $content, $link );
                $data[$link_k]['content'] = $content;
                $data[$link_k]['title'] = $title;
            }
        }
            

        $total_items = count( FIRST_URL_Shortener_Admin::get_links( array(
            'cat' => $this->cat_id,
            'limit' => -1,
            'orderby' => $orderby, 
            'order' => $order,
            'fields' => 'ids'
        ), ARRAY_A ) );

        $this->items = $data;
        
        
        /**
         * We also have to register our pagination options & calculations.
         */
        $this->set_pagination_args( array(
            'total_items' => $total_items,                      // calculate the total number of items
            'per_page'    => $per_page,                         // determine how many items to show on a page
            'total_pages' => ceil( $total_items / $per_page )   // calculate the total number of pages
        ) );
    }


}