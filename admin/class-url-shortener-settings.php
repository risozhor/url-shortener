<?php

/**
 * weDevs Settings API wrapper class
 *
 * @version 1.3 (27-Sep-2016)
 *
 * @author Tareq Hasan <tareq@weDevs.com>
 * @link https://tareq.co Tareq Hasan
 * @example example/oop-example.php How to use the class
 */

if ( !class_exists( 'FIRST_URL_Shortener_Settings' ) ):
class FIRST_URL_Shortener_Settings {

    /**
     * settings sections array
     *
     * @var array
     */
    protected $settings_sections = array();

    /**
     * Settings fields array
     *
     * @var array
     */
    protected $settings_fields = array();

    public $settings_page = '';

    public $option_name = '';

    public $current_link;
    public $is_editor = false;
    public $is_settings = false;
    public $has_tabs = true;

    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
    }

    /**
     * Enqueue scripts and styles
     */
    function admin_enqueue_scripts( $hook ) {
        $this->settings_page = $hook;
        $this->screen_base = sanitize_title( __('Short Links', 'first-url-shortener') );
    	if ( $hook == $this->screen_base.'_page_url_shortener_settings' ) {
	        wp_enqueue_style( 'wp-color-picker' );

	        wp_enqueue_media();
	        wp_enqueue_script( 'wp-color-picker' );
	        wp_enqueue_script( 'jquery' );
    	}
    }

    function set_settings_page( $page ) {
    	$this->settings_page = $page;

    	return $this;
    }

    function set_option_name( $name ) {
    	$this->option_name = $name;

    	return $this;
    }

    /**
     * Set settings sections
     *
     * @param array   $sections setting sections array
     */
    function set_sections( $sections ) {
        $this->settings_sections = $sections;

        return $this;
    }

    /**
     * Add a single section
     *
     * @param array   $section
     */
    function add_section( $section ) {
        $this->settings_sections[] = $section;

        return $this;
    }

    /**
     * Set settings fields
     *
     * @param array   $fields settings fields array
     */
    function set_fields( $fields ) {
        $this->settings_fields = $fields;

        return $this;
    }

    function add_field( $section, $field ) {
        $defaults = array(
            'name'  => '',
            'label' => '',
            'desc'  => '',
            'type'  => 'text'
        );

        $arg = wp_parse_args( $field, $defaults );
        $this->settings_fields[$section][] = $arg;

        return $this;
    }

    /**
     * Initialize and registers the settings sections and fileds to WordPress
     *
     * Usually this should be called at `admin_init` hook.
     *
     * This function gets the initiated settings sections and fields. Then
     * registers them to WordPress and ready for use.
     */
    function init_settings() {
        $this->is_settings = true;
        //register settings sections
        foreach ( $this->settings_sections as $section ) {
            if ( false == get_option( $section['id'] ) ) {
                add_option( $section['id'] );
            }

            if ( isset($section['desc']) && !empty($section['desc']) ) {
                $section['desc'] = '<div class="inside">' . $section['desc'] . '</div>';
                $callback = create_function('', 'echo "' . str_replace( '"', '\"', $section['desc'] ) . '";');
            } else if ( isset( $section['callback'] ) ) {
                $callback = $section['callback'];
            } else {
                $callback = null;
            }

            add_settings_section( $section['id'], $section['title'], $callback, $section['id'] );
        }

        //register settings fields
        foreach ( $this->settings_fields as $section => $field ) {
            foreach ( $field as $option ) {

                $name = $option['name'];
                $type = isset( $option['type'] ) ? $option['type'] : 'text';
                $label = isset( $option['label'] ) ? $option['label'] : '';
                $callback = isset( $option['callback'] ) ? $option['callback'] : array( $this, 'callback_' . $type );

                $args = array(
                    'id'                => $name,
                    'class'             => isset( $option['class'] ) ? $option['class'] : $name,
                    'label_for'         => "{$section}[{$name}]",
                    'desc'              => isset( $option['desc'] ) ? $option['desc'] : '',
                    'name'              => $label,
                    'section'           => $section,
                    'is_editor'         => false,
                    'is_settings'       => true,
                    'size'              => isset( $option['size'] ) ? $option['size'] : null,
                    'options'           => isset( $option['options'] ) ? $option['options'] : '',
                    'std'               => isset( $option['default'] ) ? $option['default'] : '',
                    'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
                    'type'              => $type,
                    'placeholder'       => isset( $option['placeholder'] ) ? $option['placeholder'] : '',
                    'min'               => isset( $option['min'] ) ? $option['min'] : '',
                    'max'               => isset( $option['max'] ) ? $option['max'] : '',
                    'step'              => isset( $option['step'] ) ? $option['step'] : '',
                );
                if ( $option['type'] == 'info' ) {
                    $args['label_for'] = '';
                }
                add_settings_field( "{$section}[{$name}]", $label, $callback, $section, $section, $args );
            }
        }

        // creates our settings in the options table
        foreach ( $this->settings_sections as $section ) {
            //register_setting( $section['id'], $section['id'], array( $this, 'sanitize_options' ) );
            register_setting( $this->settings_sections[0]['id'], $section['id'], array( $this, 'sanitize_options' ) );
        }
    }


    function init_editor( $link ) {
        $this->is_editor = true;
        $this->current_link = $link;

        // Separate link_redirection_method and link_redirection_delay
        if ( ! empty( $link->link_redirection_method ) ) {
            $link->link_redirection_method = explode( ';', $link->link_redirection_method );
            if ( isset( $link->link_redirection_method[1] ) ) {
                $link->link_redirection_delay = ( absint( $link->link_redirection_method[1] ) / 1000 );
            }
            $link->link_redirection_method = $link->link_redirection_method[0];
        }

        if ( ! isset( $link->link_nofollow ) && ! empty( $link->link_attr_rel ) ) {
            if ( $link->link_attr_rel == 'nofollow' ) {
                $link->link_nofollow = 'on';
            }
        }

        if ( ! isset( $link->link_newwindow ) && ! empty( $link->link_attr_target ) ) {
            if ( $link->link_attr_target == '_blank' ) {
                $link->link_newwindow = 'on';
            }
        }

        if ( ! empty( $link->link_forward_parameters ) && $link->link_forward_parameters != 'off' ) {
            $link->link_forward_parameters = 'on';
        }

        if ( ! empty( $link->link_remove_referrer ) && $link->link_remove_referrer != 'off' ) {
            $link->link_remove_referrer = 'on';
        }

        // Handle CSS fields
        if ( ! empty( $link->link_css ) ) {
            $css = $link->link_css;
            
            $color = $this->parse_css_value( $css, 'color' );
            if ( $color ) {
                $link->styling_color = $color;
            }

            $fontweight = $this->parse_css_value( $css, 'font-weight' );
            if ( $fontweight ) {
                $link->styling_font_weight = $fontweight;
            }

            $textdecoration = $this->parse_css_value( $css, 'text-decoration' );
            //echo 'aaaaa'.$textdecoration;
            $borderbottom = $this->parse_css_value( $css, 'border-bottom' );
            if ( $textdecoration == 'none' && $borderbottom == 'none' ) {
                $link->styling_font_underline = 'none';
            } elseif ( $textdecoration == 'underline' ) {
                $link->styling_font_underline = 'normal';
            } elseif ( stristr( $borderbottom, 'dashed' ) ) {
                $link->styling_font_underline = 'dashed';
            }
        }

        if ( ! empty( $link->link_hover_css ) ) {
            $hover_css = $link->link_hover_css;
            
            $hover_color = $this->parse_css_value( $hover_css, 'color' );
            if ( $hover_color ) {
                $link->styling_hover_color = $hover_color;
            }
        }

        // Show Title
        /* 
        if ( ! empty( $link->link_attr_title ) ) {
            if ( $link->link_attr_title == $link->link_title ) {
                $link->show_title = 'title';
            } elseif ( $link->link_attr_title == $link->link_description ) {
                $link->show_title = 'description';
            }
        }
        */

        // Replacements
        $linkurl = trailingslashit( get_bloginfo( 'url' ) ) . $link->link_name;
        $replacements = FIRST_URL_Shortener_Admin::get_link_replacements( $link->link_id );
        $link->link_replace_url = array();
        $link->link_replace_keyword = array();
        foreach ($replacements as $k => $replacement) {
            if ( $replacement->type == 'keyword' ) {
                $link->link_replace_keyword[] = $replacement->replace_key;
            } elseif ( $replacement->type == 'link' ) {
                if ( $replacement->replace_key == $linkurl ) {
                    // Base replacement, don't show this one in the list
                } else {
                    $link->link_replace_url[] = $replacement->replace_key;
                }
            }
        }
        
    }

    /**
     * Get field description for display
     *
     * @param array   $args settings field args
     */
    public function get_field_description( $args ) {
        if ( ! empty( $args['desc'] ) ) {
            $desc = sprintf( '<p class="description">%s</p>', $args['desc'] );
        } else {
            $desc = '';
        }

        return $desc;
    }

    function parse_css_value( $css_string, $property ) {
        $prop = preg_quote( $property );
        $pattern = "/{$prop}\s*:\s*(.+)(;|$)/Uim";
        $value = '';
        if ( preg_match( $pattern, $css_string, $matches ) ) {
            return trim( $matches[1], '; ' );
        }

        return $value;
    }

    /**
     * Displays useful information
     *
     * @param array   $args settings field args
     */
    function callback_info( $args ) {
        $html       = '';

        if ( $this->is_settings ) {
            $html       .= '</td></tr></table>';
        }

        $html       .= $this->get_field_description( $args );
        
        if ( $this->is_settings ) {
            $html       .= '<table class="form-table"><tr><th></th><td>';
        }
        
        echo $html;
    }

    /**
     * Displays a text field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_text( $args ) {

        $value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size        = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $type        = isset( $args['type'] ) ? $args['type'] : 'text';
        $placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
        $id_attr     = $this->get_field_id( $args );
        $name_attr   = $this->get_field_name( $args );
        
        $html        = '';
        $html       .= ( ! empty( $args['before_field'] ) ? $args['before_field'] : '' );

        $html       .= sprintf( '<input type="%1$s" class="%2$s-text" id="%3$s" name="%4$s" value="%5$s"%6$s/>', $type, $size, $id_attr, $name_attr, $value, $placeholder );
        $html       .= ( ! empty( $args['after_field'] ) ? $args['after_field'] : '' );
        $html       .= $this->get_field_description( $args );

        echo $html;
    }


    /**
     * Displays a text field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_repeatable_text( $args ) {

        $values      = $this->get_option( $args['id'], $args['section'], $args['std'] );
        $size        = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $type      = isset( $args['input_type'] ) ? $args['input_type'] : 'text';
        //$type        = 'text';
        $placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
        $id_attr     = $this->get_field_id( $args );
        $name_attr   = $this->get_field_name( $args ).'[]';

        if ( ! is_array( $values ) ) {
            $values = array( $values );
        }
        if ( empty( $values ) ) {
            $values = array( '' );
        }
        
        $html        = '<div class="ls-repeatable ls-repeatable-dummy">';
        $html       .= ( ! empty( $args['before_field'] ) ? $args['before_field'] : '' );
        $html       .= sprintf( '<input type="%1$s" class="%2$s-text" data-name="%3$s" value=""/> <a href="#" class="ls-remove-repeatable"><span class="dashicons dashicons-trash"></span></a>', $type, $size, $name_attr );
        $html       .= ( ! empty( $args['after_field'] ) ? $args['after_field'] : '' );
        $html       .= '</div>';
        // Values
        $i = 0;
        $html        .= '<div class="ls-repeatable-fields">';
        $id_attr     = ' id="'.$id_attr.'"';
        foreach ($values as $k => $value) {
            $i++;
            $html       .= '<div class="ls-repeatable">';
            $html       .= ( ! empty( $args['before_field'] ) ? $args['before_field'] : '' );
            $html       .= sprintf( '<input type="%1$s" class="%2$s-text"%3$s name="%4$s" value="%5$s"%6$s/>', $type, $size, $id_attr, $name_attr, $value, $placeholder );
            if ( $i == 1 ) {
                $html   .= ' <a href="#" class="ls-add-repeatable"><span class="dashicons dashicons-plus-alt"></span></a>';
                $id_attr = '';
            } else {
                $html   .= ' <a href="#" class="ls-remove-repeatable"><span class="dashicons dashicons-trash"></span></a>';
            }
            $html       .= ( ! empty( $args['after_field'] ) ? $args['after_field'] : '' );
            $html       .= '</div>';
        }
        $html       .= '</div>';

        $html       .= $this->get_field_description( $args );

        echo $html;
    }

    /**
     * Displays a url field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_url( $args ) {
        $this->callback_text( $args );
    }

    /**
     * Displays a number field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_number( $args ) {
        $value       = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size        = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $type        = isset( $args['type'] ) ? $args['type'] : 'number';
        $placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="' . $args['placeholder'] . '"';
        $min         = !isset( $args['min'] ) ? '' : ' min="' . $args['min'] . '"';
        $max         = empty( $args['max'] ) ? '' : ' max="' . $args['max'] . '"';
        $step        = empty( $args['max'] ) ? '' : ' step="' . $args['step'] . '"';
        $id_attr 	 = $this->get_field_id( $args );
        $name_attr   = $this->get_field_name( $args );

        $html        = '';
        $html        .= ( ! empty( $args['before_field'] ) ? $args['before_field'] : '' );
        $html        .= sprintf( '<input type="%1$s" class="%2$s-text %2$s-number" id="%3$s" name="%4$s" value="%5$s"%6$s%7$s%8$s%9$s/>', $type, $size, $id_attr, $name_attr, $value, $placeholder, $min, $max, $step );
        $html        .= ( ! empty( $args['after_field'] ) ? $args['after_field'] : '' );
        $html        .= $this->get_field_description( $args );

        echo $html;
    }

    /**
     * Displays a checkbox for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_checkbox( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $id_attr 	 = $this->get_field_id( $args );
        $name_attr   = $this->get_field_name( $args );

        $html  = '<fieldset>';
        $html  .= sprintf( '<label for="%1$s">', $id_attr );
        $html  .= sprintf( '<input type="hidden" name="%1$s" value="off" />', $name_attr );
        $html  .= sprintf( '<input type="checkbox" class="checkbox" id="%1$s" name="%2$s" value="on" %3$s />', $id_attr, $name_attr, checked( $value, 'on', false ) );
        $html  .= sprintf( ' %1$s</label>', $args['name'] );
        $html  .= $this->get_field_description( $args );
        $html  .= '</fieldset>';

        echo $html;
    }

    /**
     * Displays a multicheckbox a settings field
     *
     * @param array   $args settings field args
     */
    function callback_multicheck( $args ) {

        $value = $this->get_option( $args['id'], $args['section'], $args['std'] );
        $html  = '<fieldset>';
        $name_attr   = $this->get_field_name( $args );
        $html .= sprintf( '<input type="hidden" name="%1$s" value="" />', $name_attr );
        foreach ( $args['options'] as $key => $label ) {

	        $id_attr 	 = $this->get_field_id( $args, $key );
	        $name_attr   = $this->get_field_name( $args, $key );

            $checked = isset( $value[$key] ) ? $value[$key] : '0';
            $html    .= sprintf( '<label for="%1$s">', $id_attr );
            $html    .= sprintf( '<input type="checkbox" class="checkbox" id="%1$s" name="%2$s" value="%3$s" %4$s />', $id_attr, $name_attr, $key, checked( $checked, $key, false ) );
            $html    .= sprintf( '%1$s</label><br>',  $label );
        }

        $html .= $this->get_field_description( $args );
        $html .= '</fieldset>';

        echo $html;
    }

    /**
     * Displays a multicheckbox a settings field
     *
     * @param array   $args settings field args
     */
    function callback_radio( $args ) {

        $value 		 = $this->get_option( $args['id'], $args['section'], $args['std'] );
        $html  		 = '<fieldset>';
        $id_attr 	 = $this->get_field_id( $args );
        $name_attr   = $this->get_field_name( $args );

        $html .= $this->get_field_description( $args );
        $name_attr   = $this->get_field_name( $args );
        foreach ( $args['options'] as $key => $label ) {
	        $id_attr 	 = $this->get_field_id( $args, $key );
            $html .= sprintf( '<label for="%1$s">', $id_attr );
            $html .= sprintf( '<input type="radio" class="radio" id="%1$s" name="%2$s" value="%3$s" %4$s />', $id_attr, $name_attr, $key, checked( $value, $key, false ) );
            $html .= sprintf( '%1$s</label>', $label );
        }

        $html .= '</fieldset>';

        echo $html;
    }

    /**
     * Displays a selectbox for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_select( $args ) {

        $value 		 = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $id_attr 	 = $this->get_field_id( $args );
        $name_attr   = $this->get_field_name( $args );
        $size  		 = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $html  		 = sprintf( '<select class="%1$s" id="%2$s" name="%3$s">', $size, $id_attr, $name_attr );

        foreach ( $args['options'] as $key => $label ) {
            $html .= sprintf( '<option value="%s"%s>%s</option>', $key, selected( $value, $key, false ), $label );
        }

        $html .= sprintf( '</select>' );
        $html .= $this->get_field_description( $args );

        echo $html;
    }

    /**
     * Displays a textarea for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_textarea( $args ) {

        $value       = esc_textarea( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size        = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $placeholder = empty( $args['placeholder'] ) ? '' : ' placeholder="'.$args['placeholder'].'"';
        $id_attr 	 = $this->get_field_id( $args );
        $name_attr   = $this->get_field_name( $args );

        $html        = sprintf( '<textarea rows="5" cols="55" class="%1$s-text" id="%2$s" name="%3$s"%4$s>%5$s</textarea>', $size, $id_attr, $name_attr, $placeholder, $value );
        $html        .= $this->get_field_description( $args );

        echo $html;
    }

    /**
     * Displays a textarea for a settings field
     *
     * @param array   $args settings field args
     * @return string
     */
    function callback_html( $args ) {
        echo $this->get_field_description( $args );
    }

    /**
     * Displays a rich text textarea for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_wysiwyg( $args ) {

        $value = $this->get_option( $args['id'], $args['section'], $args['std'] );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : '500px';
        $id_attr 	 = $this->get_field_id( $args );
        $name_attr   = $this->get_field_name( $args );

        echo '<div style="max-width: ' . $size . ';">';

        $editor_settings = array(
            'teeny'         => true,
            'textarea_name' => $name_attr,
            'textarea_rows' => 10
        );

        if ( isset( $args['options'] ) && is_array( $args['options'] ) ) {
            $editor_settings = array_merge( $editor_settings, $args['options'] );
        }

        wp_editor( $value, $id_attr, $editor_settings );

        echo '</div>';

        echo $this->get_field_description( $args );
    }

    /**
     * Displays a file upload field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_file( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $id    = $args['section']  . '[' . $args['id'] . ']';
        $label = isset( $args['options']['button_label'] ) ? $args['options']['button_label'] : __( 'Choose File', 'first-url-shortener' );
        $id_attr 	 = $this->get_field_id( $args );
        $name_attr   = $this->get_field_name( $args );

        $html  = sprintf( '<input type="text" class="%1$s-text wpsa-url" id="%2$s" name="%3$s" value="%4$s"/>', $size, $id_attr, $name_attr, $value );
        $html  .= '<input type="button" class="button wpsa-browse" value="' . $label . '" />';
        $html  .= $this->get_field_description( $args );

        echo $html;
    }

    /**
     * Displays a password field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_password( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $id_attr 	 = $this->get_field_id( $args );
        $name_attr   = $this->get_field_name( $args );

        $html  = sprintf( '<input type="password" class="%1$s-text" id="%2$s" name="%3$s" value="%4$s"/>', $size, $id_attr, $name_attr, $value );
        $html  .= $this->get_field_description( $args );

        echo $html;
    }

    /**
     * Displays a color picker field for a settings field
     *
     * @param array   $args settings field args
     */
    function callback_color( $args ) {

        $value = esc_attr( $this->get_option( $args['id'], $args['section'], $args['std'] ) );
        $size  = isset( $args['size'] ) && !is_null( $args['size'] ) ? $args['size'] : 'regular';
        $id_attr 	 = $this->get_field_id( $args );
        $name_attr   = $this->get_field_name( $args );

        $html  = sprintf( '<input type="text" class="%1$s-text wp-color-picker-field" id="%2$s" name="%3$s" value="%4$s" data-default-color="%5$s" />', $size, $id_attr, $name_attr, $value, $args['std'] );
        $html  .= $this->get_field_description( $args );

        echo $html;
    }

    /**
     * Sanitize callback for Settings API
     *
     * @return mixed
     */
    function sanitize_options( $options ) {

        if ( !$options ) {
            return $options;
        }

        foreach( $options as $option_slug => $option_value ) {
            $sanitize_callback = $this->get_sanitize_callback( $option_slug );

            // If callback is set, call it
            if ( $sanitize_callback ) {
                $options[ $option_slug ] = call_user_func( $sanitize_callback, $option_value );
                continue;
            }
        }

        return $options;
    }

    /**
     * Get sanitization callback for given option slug
     *
     * @param string $slug option slug
     *
     * @return mixed string or bool false
     */
    function get_sanitize_callback( $slug = '' ) {
        if ( empty( $slug ) ) {
            return false;
        }

        // Iterate over registered fields and see if we can find proper callback
        foreach( $this->settings_fields as $section => $options ) {
            foreach ( $options as $option ) {
                if ( $option['name'] != $slug ) {
                    continue;
                }

                // Return the callback name
                return isset( $option['sanitize_callback'] ) && is_callable( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : false;
            }
        }

        return false;
    }

    /**
     * Get the value of a settings field
     *
     * @param string  $option  settings field name
     * @param string  $section the section name this field belongs to
     * @param string  $default default text if it's not found
     * @return string
     */
    function get_option( $option, $section, $default = '' ) {
        if ( $this->is_settings ) {
            $options = get_option( $section );

            if ( isset( $options[$option] ) ) {
                return $options[$option];
            }
        } elseif ( $this->is_editor ) {
            if ( isset( $this->current_link->$option ) ) {
                return $this->current_link->$option;
            }
        }

        return $default;
    }

    function get_field_id( $args, $index = null ) {
    	return  sanitize_html_class( $args['section'] . '-' . $args['id'] . ( $index ? '-' . $index : '' ) );
    }

    function get_field_name( $args, $index = null ) {
    	if ( isset( $args['is_editor'] ) && $args['is_editor'] ) {
    		return  $args['id'] . ( $index ? '[' . $index . ']' : '' );
    	}
    	return  $args['section'] . '[' . $args['id'] . ']' . ( $index ? '[' . $index . ']' : '' );
    }

    /**
     * Show navigations as tab
     *
     * Shows all the settings section labels as tab
     */
    function show_navigation() {
        $html = '<h2 class="nav-tab-wrapper">';

        $count = count( $this->settings_sections );

        // don't show the navigation if only one section exists
        if ( $count === 1 ) {
            return;
        }

        foreach ( $this->settings_sections as $tab ) {
            $html .= sprintf( '<a href="#%1$s" class="nav-tab" id="%1$s-tab">%2$s</a>', $tab['id'], $tab['title'] );
        }

        $html .= '</h2>';

        echo $html;
    }

    /**
     * Show the section settings forms
     *
     * This function displays every sections in a different form
     */
    function show_settings_form() {
        ?>
        <div class="metabox-holder">
            <form method="post" action="options.php">
	            <?php $i = 0; foreach ( $this->settings_sections as $form ) { $i++; ?>
                    <?php if ( $this->has_tabs ) { ?>
    	                <div id="<?php echo $form['id']; ?>" class="group" style="display: none;">
                    <?php } ?>
    	                    <?php
    	                    do_action( 'url_shortener_settings_before_section_' . $form['id'], $form );
    	                    if ($i == 1)
    	                    	settings_fields( $form['id'] );
    	                    do_settings_sections( $form['id'] );
    	                    do_action( 'url_shortener_settings_after_section_' . $form['id'], $form );
    	                    if ( isset( $this->settings_fields[ $form['id'] ] ) ):
    	                    ?>
    	                    <div>
    	                        <?php submit_button(); ?>
    	                    </div>
    	                    <?php endif; ?>
                    <?php if ( $this->has_tabs ) { ?>
    	                </div>
                    <?php } ?>

	            <?php } ?>
            </form>
        </div>
        <?php
        $this->script();
    }

    function show_editor_form() {
        $dont_show_label = array( 'checkbox', 'info' );
        ?>
        <div class="metabox-holder">
            <?php foreach ( $this->settings_fields as $section => $field ) { ?>
                <?php if ( $this->has_tabs ) { ?>
                <div id="<?php echo $section; ?>" class="group" style="display: none;">
                <?php } ?>
                    <?php
		            foreach ( $field as $option ) { 
		                $name = $option['name'];
		                $type = isset( $option['type'] ) ? $option['type'] : 'text';
		                $label = isset( $option['label'] ) ? $option['label'] : '';
		                $callback = isset( $option['callback'] ) ? $option['callback'] : array( $this, 'callback_' . $type );
		                $before = isset( $option['before_field'] ) ? $option['before_field'] : '';
		                $after = isset( $option['after_field'] ) ? $option['after_field'] : '';

		                $args = array(
		                    'id'                => $name,
		                    'class'             => isset( $option['class'] ) ? $option['class'] : $name,
		                    'label_for'         => "{$section}[{$name}]",
		                    'desc'              => isset( $option['desc'] ) ? $option['desc'] : '',
		                    'name'              => $label,
		                    'section'           => $section,
		                    'is_editor'         => true,
		                    'is_settings'       => false,
		                    'size'              => isset( $option['size'] ) ? $option['size'] : null,
		                    'options'           => isset( $option['options'] ) ? $option['options'] : '',
		                    'std'               => isset( $option['default'] ) ? $option['default'] : '',
		                    'sanitize_callback' => isset( $option['sanitize_callback'] ) ? $option['sanitize_callback'] : '',
		                    'type'              => $type,
		                    'placeholder'       => isset( $option['placeholder'] ) ? $option['placeholder'] : '',
		                    'min'               => isset( $option['min'] ) ? $option['min'] : '',
		                    'max'               => isset( $option['max'] ) ? $option['max'] : '',
		                    'step'              => isset( $option['step'] ) ? $option['step'] : '',
		                    'before_field'		=> $before,
		                    'after_field'		=> $after,
		                );
						if ( is_callable( $callback ) ) {
							?>
		                	<div id="field-<?php echo $args['id']; ?>" class="link-editor-field">
		                		<?php if ( ! in_array($type, $dont_show_label) ) { ?>
		                		   <label for="<?php echo $this->get_field_id( $args ); ?>"><?php echo $label; ?></label>
		                		<?php } ?>
		                		<?php call_user_func( $callback, $args ); ?>
							</div>
							<?php
						}
		            }
		            ?>
                <?php if ( $this->has_tabs ) { ?>
                </div>
                <?php } ?>
            <?php } ?>
        
        </div>
        <?php
        $this->script();
    }

    /**
     * Tabbable JavaScript codes & Initiate Color Picker
     *
     * This code uses localstorage for displaying active tabs
     */
    function script() {
        global $ls_admin_script_init;
        if ( ! empty( $ls_admin_script_init ) ) {
            return;
        }
        $ls_admin_script_init = true;
        ?>
        <script>
            /*
              Autosize input
              https://github.com/yuanqing/autosize-input
            */
            (function(){var t=/\s/g;var e=/>/g;var n=/</g;function i(i){return i.replace(t,"&nbsp;").replace(e,"&lt;").replace(n,"&gt;")}var o="__autosizeInputGhost";function l(){var t=document.createElement("div");t.id=o;t.style.cssText="box-sizing:content-box;display:inline-block;height:0;overflow:hidden;position:absolute;top:0;visibility:hidden;white-space:nowrap;";document.body.appendChild(t);return t}var r=l();function d(t,e){t.style.boxSizing="content-box";var n=window.getComputedStyle(t);var d="font-family:"+n.fontFamily+";font-size:"+n.fontSize;function a(e){e=e||t.value||t.getAttribute("placeholder")||"";if(document.getElementById(o)===null){r=l()}r.style.cssText+=d;r.innerHTML=i(e);var n=window.getComputedStyle(r).width;t.style.width=n;return n}t.addEventListener("input",function(){a()});var u=a();if(e&&e.minWidth&&u!=="0px"){t.style.minWidth=u}return a}if(typeof module==="object"){module.exports=d}else{window.autosizeInput=d}})();
            
            /*
              Admin Page Script
            */
            jQuery(document).ready(function($) {
                //Initiate Color Picker
                $('.wp-color-picker-field').wpColorPicker();

                // Switches option sections
                $('.group').hide();
                var activetab = '';
                <?php if ( ! $this->is_settings  || isset( $_GET['settings-updated']) ) { ?>
                if ( typeof(localStorage) != 'undefined' && ! $('#addshortlink').length ) {
                    activetab = localStorage.getItem("ls_<?php echo sanitize_html_class( $this->settings_page ); ?>_activetab");
                }
                <?php } ?>

                // Check hashvalue for active tab
                var hashval = window.location.hash;
                console.log(hashval);
                if ( hashval ) {
                    activetab = hashval;
                }
                if (activetab != '' && $(activetab).length ) {
                    $(activetab).fadeIn();
                } else {
                    $('.group:first').fadeIn();
                }
                $('.group .collapsed').each(function(){
                    $(this).find('input:checked').parent().parent().parent().nextAll().each(
                    function(){
                        if ($(this).hasClass('last')) {
                            $(this).removeClass('hidden');
                            return false;
                        }
                        $(this).filter('.hidden').removeClass('hidden');
                    });
                });

                if (activetab != '' && $(activetab + '-tab').length ) {
                    $(activetab + '-tab').addClass('nav-tab-active');
                }
                else {
                    $('.nav-tab-wrapper a:first').addClass('nav-tab-active');
                }
                $('.nav-tab-wrapper a').click(function(evt) {
                    evt.preventDefault();
                    if ($(this).hasClass('nav-tab-active')) {
                    	return false;
                    }
                    $('.nav-tab-wrapper a').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active').blur();
                    var clicked_group = $(this).attr('href');
                    if (typeof(localStorage) != 'undefined' ) {
                        localStorage.setItem("ls_<?php echo sanitize_html_class( $this->settings_page ); ?>_activetab", $(this).attr('href'));
                    }
                    $('.group').hide();
                    $(clicked_group).fadeIn();
                });

                $('.wpsa-browse').on('click', function (event) {
                    event.preventDefault();

                    var self = $(this);

                    // Create the media frame.
                    var file_frame = wp.media.frames.file_frame = wp.media({
                        title: self.data('uploader_title'),
                        button: {
                            text: self.data('uploader_button_text'),
                        },
                        multiple: false
                    });

                    file_frame.on('select', function () {
                        attachment = file_frame.state().get('selection').first().toJSON();
                        self.prev('.wpsa-url').val(attachment.url).change();
                    });

                    // Finally, open the modal
                    file_frame.open();
                });

                // Repeatable Text
                $('.ls-repeatable-fields').on('click', '.ls-add-repeatable', function(event) {
                    event.preventDefault();
                    var $this = $(this);
                    var $field_wrapper = $this.closest('.ls-repeatable-fields');
                    var $dummy = $field_wrapper.siblings('.ls-repeatable-dummy');
                    
                    $dummy.clone().removeClass('ls-repeatable-dummy').appendTo($field_wrapper).find('input').attr('name', function() { return $(this).data('name'); } ).focus();
                });
                $('.ls-repeatable-fields').on('click', '.ls-remove-repeatable', function(event) {
                    event.preventDefault();
                    $(this).closest('.ls-repeatable').remove();
                });
                
                if ( $('#urlshortener_basic-link_name').length )
                    autosizeInput(document.querySelector('#urlshortener_basic-link_name'));
                
                $(window).load(function() {
                    if ( $('#urlshortener_basic-link_name').is(':visible') && $('#urlshortener_basic-link_name').val() == '' ) {
                        $('#urlshortener_basic-link_name').focus();
                    }
                });
        });
        </script>
        <?php
    }

}

endif;