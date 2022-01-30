<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://mythemeshop.com/plugins/url-shortener/
 * @since      1.0.0
 *
 * @package    FIRST_URL_Shortener
 * @subpackage FIRST_URL_Shortener/public
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    FIRST_URL_Shortener
 * @subpackage FIRST_URL_Shortener/public
 * @author     MyThemeShop <support-team@mythemeshop.com>
 */

class FIRST_URL_Shortener_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	public $current_shortlink;
	public $redirection_method;
	public $redirection_delay;
	public $link_styles = array();
	public $replacements_done = array();
	public $replacements_done_count = 0;
	public $max_replacements = 0;
	public $max_replacements_per_link = 0;
	public $countdown_present;
	public $countdown_delay;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$settings = get_option( 'urlshortener_replacements' );
		if ( is_array( $settings ) ) {
			$this->max_replacements = isset($settings['max_kw_replacement']) ? $settings['max_kw_replacement'] : 0;
			$this->max_replacements_per_link = isset($settings['max_kw_per_link_replacement']) ? $settings['max_kw_per_link_replacement'] : 0;
		}
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in FIRST_URL_Shortener_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The FIRST_URL_Shortener_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in FIRST_URL_Shortener_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The FIRST_URL_Shortener_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

	}

	/**
	 * Register Taxonomy
	 *
	 * @since    1.0.0
	 */
	public function register_taxonomy() {
		register_taxonomy( 'short_link_category', 'short_link', array(
			'hierarchical' => false,
			'labels' => array(
				'name' => __( 'Short Link Categories', 'first-url-shortener' ),
				'singular_name' => __( 'Link Category', 'first-url-shortener' ),
				'search_items' => __( 'Search Link Categories', 'first-url-shortener' ),
				'popular_items' => null,
				'all_items' => __( 'All Link Categories', 'first-url-shortener' ),
				'edit_item' => __( 'Edit Link Category', 'first-url-shortener' ),
				'update_item' => __( 'Update Link Category', 'first-url-shortener' ),
				'add_new_item' => __( 'Add New Link Category', 'first-url-shortener' ),
				'new_item_name' => __( 'New Link Category Name', 'first-url-shortener' ),
				'separate_items_with_commas' => null,
				'add_or_remove_items' => null,
				'choose_from_most_used' => null,
			),
			'capabilities' => array(
				'manage_terms' => 'manage_categories',
				'edit_terms'   => 'manage_categories',
				'delete_terms' => 'manage_categories',
				'assign_terms' => 'edit_posts',
			),
			'query_var' => false,
			'rewrite' => false,
			'public' => false,
			'show_ui' => true,
		) );
	}

	/**
	 * Initial check: does the current URI have any redirection?
	 * @return string|bool Redirect type for the current URI or false when no redirection is necessary
	 */
	public function pre_check_redirect() {
		if ( is_admin() ) {
			return;
		}

		$current_uri = ltrim( add_query_arg( NULL, NULL ), '/' );
		// Strip home directory when WP is installed in subdirectory
		$home_dir = ltrim( home_url( '', 'relative' ), '/' );
		if ( $home_dir ) {
			$home_dir = trailingslashit( $home_dir );
			$current_uri = preg_replace( '[^'.preg_quote($home_dir).']', '', $current_uri);
		}
		$this->current_uri = $current_uri;
		$current_uri_no_query = explode('?', $current_uri);
		$current_uri_no_query = urldecode( $current_uri_no_query[0] );

		$link = FIRST_URL_Shortener_Admin::get_link_by_slug( $current_uri_no_query );
		if ( $link ) {
			$redirection_method = explode( ';', $link->link_redirection_method);
			$this->redirection_delay = ( empty( $redirection_method[1] ) ? 0 : (int) $redirection_method[1] );
			$redirection_method = $redirection_method[0];
			$this->redirection_method = $redirection_method;
			if ( $link->link_forward_parameters ) {
				$link->link_url = esc_url( add_query_arg( $_GET, $link->link_url ) );
			}

			$this->current_shortlink = $link;
			// Register click
			$this->register_click();

			$redirect_now = array( '301', '302', '307' );
			if ( in_array( $redirection_method, $redirect_now ) ) {
				wp_redirect( $link->link_url, $redirection_method );
				exit();
			}

		}
	}


	function remove_redirect_guess_404_permalink( $redirect_url ) {
		if ( is_404() && !isset($_GET['p']) )
			return false;
		if ( ! empty( $this->block_redirection ) ) {
			return false;
		}
		return $redirect_url;
	}


	public function replace_links( $the_content ) {
		//return $the_content;
		if ( is_admin() )
			return $the_content;

		$replacements = FIRST_URL_Shortener_Admin::get_link_replacements();

		$replace_keywords = array();
		foreach ($replacements as $r_id => $replacement) {
			if ( $replacement->type == 'link' ) {
				$the_content = $this->do_replace_link( $the_content, $replacement );
			} elseif ( $replacement->type == 'keyword' ) {
				$replace_keywords[] = $replacement;
			}
		}
		if ( ! empty( $replace_keywords ) ) {
			$the_content = $this->do_replace_keywords( $the_content, $replace_keywords );
		}
		return $the_content;

	}

	public function do_replace_link( $content, $replacement ) {
		$url = $replacement->replace_key;
		$pattern = '/<a(?:\\s[^>]+\\shref=| href=)[\'"]'.preg_quote( $url, '/' ).'[\'"][^>]*>/Ui';
		$this->current_replacement = $replacement;

		$content = preg_replace_callback( $pattern, array( $this, 'url_replace_callback' ), $content );
		return $content;
	}
	public function do_replace_keywords( $content, $replacements ) {
		if ( $content == '' ) {
			return $content;
		}
		//return $content;
		$dom = new DOMDocument();

        // Suppress warnings about malformed HTML
		$content_enc = htmlspecialchars( $content );
        libxml_use_internal_errors( true );
        if( defined( 'LIBXML_HTML_NOIMPLIED' ) && defined( 'LIBXML_HTML_NODEFDTD' ) ) {
          @$dom->loadHTML(mb_convert_encoding($content_enc, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
        } else {
          @$dom->loadHTML(mb_convert_encoding($content_enc, 'HTML-ENTITIES', 'UTF-8'));
        }
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        foreach($xpath->query('//text()[not(ancestor::a)]') as $node) {
            $new_text = $node->wholeText;
            foreach ($replacements as $i => $replacement) {
                $keyword = $replacement->replace_key;
                // $pattern = '[\b'.preg_quote($keyword).'\b]i';
                $pattern = '#(?!<.*?)(\b'.preg_quote($keyword).'\b)(?![^<>]*?>)#si';
                $this->current_replacement = $replacement;
                $new_text = preg_replace_callback($pattern, array( $this, 'keyword_replace_callback' ), $new_text);

            }
        }
			 return $new_text;
	}
	function url_replace_callback( $matches ) {
		return $this->build_link( $this->current_replacement->link_id, $matches[0] );
	}
	function keyword_replace_callback( $matches ) {
		$link = FIRST_URL_Shortener_Admin::get_link( $this->current_replacement->link_id );

		return $this->build_link( $this->current_replacement->link_id ) . $matches[0] . '</a>';
	}
	function build_attributes_string( $attributes ) {
		$attr = '';
		foreach ($attributes as $key => $value) {
			if ( $value === true ) {
				$attr .= $key . ' ';
				continue;
			}
			$attr .= $key . '="' . esc_attr( $value ) . '" ';
		}
		return rtrim($attr);
	}

	function build_link( $link, $original = null ) {
		// $link can be link object or ID
		if ( ! is_object( $link ) ) {
			$link = FIRST_URL_Shortener_Admin::get_link( (int) $link );
		}
		if ( ! $link ) {
			return '<!-- URL Shortener: link building error -->';
		}

		$redirection_method = explode( ';', $link->link_redirection_method );
		$redirection_method = $redirection_method[0];
		$header_redirections = array( '301', '302', '307' );

		$url = trailingslashit( get_bloginfo( 'url' ) );
		$settings = get_option( 'urlshortener_general' );
		if ( ! empty( $settings['prefix_category'] ) ) {
			$cats = FIRST_URL_Shortener_Admin::get_link_cats( $link->link_id );
			if ( is_array( $cats ) && isset( $cats[0] ) ) {
				$term = get_term( $cats[0], 'short_link_category' );
				$url .= $term->slug . '/';
			} else {
				$url .= apply_filters( 'url_shortener_uncategorized_slug', 'uncategorized', $link ) . '/';
			}
		}
		$url .= $link->link_name;

		$attributes = array();

		// $original can be element string or attributes array
		if ( is_string( $original ) ) {
			$attributes = $this->parse_attributes( $original );
		} elseif ( is_array( $original ) ) {
			$attributes = $original;
		}

		$original_attributes = $attributes;
		// Look for 'noshortlink' class
		if ( isset( $attributes['class'] ) ) {
			$classes = explode( ' ', $attributes['class'] );
			if ( in_array( 'noshortlink', $classes ) ) {
				// Short link excluded – just return the original link
				return '<a '.$this->build_attributes_string( $original_attributes ).'>';
			}
		}

		// class
		$default_classes = array(
			'shortlink',
			'shortlink-' . $link->link_id
		);
		/**
		 * short_link_classes
		 * Filters default classes
		 */
		$default_classes = apply_filters( 'short_link_default_classes', $default_classes, $link, $attributes );
		if ( ! isset( $attributes['class'] ) ) {
			$attributes['class'] = '';
		}
		$attributes['class'] = trim( $attributes['class'].' '.join(' ', $default_classes ) );
		if ( ! empty( $link->link_attr_class ) ) {
			$attributes['class'] .= ' ' . $link->link_attr_class;
		}

		// href
		$attributes['href'] = $url;

		// target
		if ( ! empty( $link->link_attr_target ) ) {
			$attributes['target'] = $link->link_attr_target;
		}
		// rel
		$attributes['rel'] = '';
		if ( ! empty( $link->link_attr_rel ) ) {
			$attributes['rel'] = $link->link_attr_rel;
		}
		if ( ! empty( $link->link_remove_referrer ) && in_array( $redirection_method, $header_redirections ) ) {
			$attributes['rel'] .= ' noreferrer';
		}
		// title
		if ( ! empty( $link->link_attr_title ) ) {
			$attributes['title'] = $link->link_attr_title;
		}

		$attributes = apply_filters( 'short_link_attributes', $attributes, $link, $original_attributes );

		return '<a '.$this->build_attributes_string( $attributes ).'>';
	}

	public function parse_attributes( $tag ) {
		if ( ! preg_match_all('/( [\\w_\-]+ |([\\w\-_]+)\s*=\\s*("[^"]*"|\'[^\']*\'|[^"\'\\s>]*))/', $tag, $matches, PREG_SET_ORDER) )
			return array();

		$attrs = array();
		foreach ($matches as $match) {
			$name = '';
			$value = '';
			if (count($match) > 2) {
				$match[3] = trim( $match[3], '"\'');
			    $name = strtolower($match[2]);
			    $value = html_entity_decode($match[3]);
			    switch ($name) {
				    case 'class':
				    	// classes
				        //$attrs[$name] = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
				        $attrs[$name] = $value;
				    break;

				    case 'style':
				        // parse CSS property declarations
				        $attrs[$name] = $value;
				    break;

				    default:
				        $attrs[$name] = $value;
			    }
			} else {
				$match[0] = trim( $match[0] );
				$attrs[$match[0]] = true;
			}
		}
		return $attrs;
	}

	public function register_click() {
		global $wpdb;
		include_once URL_SHORTENER_PLUGIN_PATH . 'includes/class-uadetector.php';
		$useragent = new first_UADetector();

		$device = '';
		if ( $useragent->device ) {
			$device = $useragent->device;
			if ( $useragent->model ) {
				$device .= ' '.$useragent->model;
			}
		}
		if ( ! $device ) {
			$device = $useragent->platform;
		}
		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$click_data = array(
            'link_id' => $this->current_shortlink->link_id,
            'click_date' => current_time( 'mysql' ),
            'click_ip' => $_SERVER['REMOTE_ADDR'],
            'click_useragent' => $ua,
            'click_robot' => $useragent->is_robot,
            'click_handheld' => $useragent->is_mobile,
            'click_browser' => $useragent->name.(!empty( $useragent->version ) ? ' '.$useragent->version : '' ),
            'click_os' => $useragent->os,
            'click_device' => $device,
            'click_uri' => $this->current_uri,
            'click_referrer' => $referer,
        );

		// Do not register clicks from own WP install
		if ( stripos( $click_data['click_useragent'], get_bloginfo( 'url' ) ) !== FALSE ) {
			return false;
		}

        if ( $wpdb->insert( $wpdb->prefix.'short_link_clicks', $click_data ) ) {
            return $wpdb->insert_id;
        }
        return false;
	}

	function robots_txt( $output, $public ) {
		$settings = get_option( 'urlshortener_general' );
		if ( empty( $settings['robots_txt_exclude'] ) ) {
			return $output;
		}
		if ( ! is_array( $settings['robots_txt_exclude'] ) )  {
			$settings['robots_txt_exclude'] = array( $settings['robots_txt_exclude'] );
		}
		$output = trim( $output );
		foreach ( $settings['robots_txt_exclude'] as $folder ) {
			$output .= "\nDisallow: $folder";
		}
		return $output;
	}

	function add_shortcodes() {
		add_shortcode( 'shortlink', array( $this, 'shortcode_link' ) );
	}

	function shortcode_link( $atts, $content ) {
		$a = shortcode_atts( array(
		    'id' => '0',
		    'slug' => ''
		), $atts );

		// Support for [shortlink 11] format instead of [shortlink id="11"]
		if ( empty( $a['id'] ) && empty( $a['slug'] ) ) {
			if ( ! empty( $atts[0] ) ) {
				if ( preg_match('/^[0-9]+$/', trim( $atts[0] ) ) ) {
					$a['id'] = $atts[0];
				} else {
					$a['slug'] = $atts[0];
				}
			}
		}

		$id = $a['id'];
		$slug = $a['slug'];
		if ( ! $id && ! $slug ) {
			return '';
		}
		if ( $id ) {
			$link = FIRST_URL_Shortener_Admin::get_link( $id );
		} else {
			$link = FIRST_URL_Shortener_Admin::get_link_by_slug( $slug );
		}
		if ( empty( $link ) ) {
			return $content;
		}
		$id = $link->link_id;

		if ( empty( $content ) ) {
			$content = $link->link_url;
			if ( ! empty( $link->link_cloak_url ) ) {
				$content = $link->link_cloak_url;
			}
			if ( ! empty( $link->link_title ) ) {
				$content = $link->link_title;
			}
			if ( ! empty( $link->link_anchor ) ) {
				$content = $link->link_anchor;
			}
			$content = apply_filters( 'short_link_default_content', $content, $link );
		}
		$link_html = $this->build_link( $id ).$content.'</a>';

		return $link_html;
	}


}
