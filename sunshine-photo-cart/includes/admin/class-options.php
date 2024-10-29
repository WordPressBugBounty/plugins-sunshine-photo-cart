<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'SPC_Settings_API' ) ) {

	class SPC_Settings_API {

		private $key;
		private $name;
		private $menu_name;
		private $menu_parent;
		private $icon_url;
		private $prefix;
		private $settings;
		private $show_submit = false;

		public function __construct( $key, $name, $menu_name, $menu_parent = '', $icon_url = '' ) {

			$this->key         = $key;
			$this->prefix      = $this->key . '_';
			$this->name        = $name;
			$this->menu_name   = $menu_name;
			$this->menu_parent = ( $menu_parent ) ? $menu_parent : 'options-general.php';
			$this->icon_url    = $icon_url;

			// Initialise settings
			add_action( 'admin_init', array( $this, 'init' ) );

			// Register plugin settings
			add_action( 'admin_init', array( $this, 'register_settings' ) );

			// Add settings page to menu
			// add_action( 'admin_menu' , array( $this, 'add_menu_item' ) );
			add_filter( 'sunshine_admin_menu', array( $this, 'sunshine_admin_menu' ) );

			// add_action( 'admin_head', array( $this, 'styles' ) );
			add_action( 'admin_footer', array( $this, 'scripts' ) );

			// Add settings link to plugins page
			add_filter( 'plugin_action_links_' . plugin_basename( SUNSHINE_PHOTO_CART_FILE ), array( $this, 'add_settings_link' ) );

			add_action( 'admin_notices', array( $this, 'admin_notices' ) );

			add_action( 'admin_init', array( $this, 'flush_endpoint_rewrite_rules_check' ) );
			add_action( 'admin_notices', array( $this, 'flush_endpoint_rewrite_rules_process' ) );

		}

		/**
		 * Initialise settings
		 *
		 * @return void
		 */
		public function init() {
			$this->settings = $this->settings_fields();
		}

		public function sunshine_admin_menu( $menu ) {
			$menu[10] = array( __( 'Settings', 'sunshine-photo-cart' ), __( 'Settings', 'sunshine-photo-cart' ), 'sunshine_manage_options', $this->key, array( $this, 'settings_page' ) );
			// add_action( 'admin_print_styles-sunshine_page_sunshine', array( $this, 'settings_assets' ) );
			return $menu;
		}

		/**
		 * Add settings page to admin menu
		 *
		 * @return void
		 */
		public function add_menu_item() {
			$page = add_submenu_page( $this->menu_parent, $this->name, $this->menu_name, 'manage_options', $this->key, array( $this, 'settings_page' ) );
			// sunshine_log( $page, 'admin menu item page' );
			// add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );
		}

		/**
		 * Load settings JS & CSS
		 *
		 * @return void
		 */
		public function settings_assets() {

			// Select2.
			wp_enqueue_style( 'select2' );
			wp_enqueue_script( 'select2' );

			// Color picker.
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wp-color-picker' );

			// We're including the WP media scripts here because they're needed for the image upload field.
			// If you're not including an image upload then you can leave this function call out.
			wp_enqueue_media();

			// Allow things to be sorted.
			wp_enqueue_script( 'jquery-ui-sortable' );

			// wp_register_script( $this->prefix . '-settings-js', $this->assets_url . 'js/settings.js', array( 'farbtastic', 'jquery' ), '1.0.0' );
			// wp_enqueue_script( $this->prefix . '-settings-js' );
			// wp_enqueue_style( $this->prefix . '-settings-css', $this->assets_url . 'css/admin.css' );
		}

		/**
		 * Add settings link to plugin list table
		 *
		 * @param  array $links Existing links
		 * @return array        Modified links
		 */
		public function add_settings_link( $links ) {
			$settings_link = '<a href="' . admin_url( 'admin.php?page=' . $this->key ) . '">' . __( 'Settings' ) . '</a>';
			array_push( $links, $settings_link );
			return $links;
		}

		/**
		 * Build settings fields
		 *
		 * @return array Fields to be displayed on settings page
		 */
		private function settings_fields() {
			return sunshine_get_settings_fields();
		}

		/**
		 * Register plugin settings
		 *
		 * @return void
		 */
		public function register_settings() {
			if ( is_array( $this->settings ) ) {
				foreach ( $this->settings as $data ) {

					// Add section to page
					add_settings_section( $data['id'], $data['title'], array( $this, 'settings_section' ), $this->prefix . $data['id'] );

					add_filter( 'option_page_capability_sunshine_' . $data['id'], array( $this, 'options_capability' ) );

					foreach ( $data['fields'] as $field ) {

						// Validation callback for field
						$args = array();
						if ( isset( $field['callback'] ) ) {
							$args['sanitize_callback'] = $field['callback'];
						}

						if ( empty( $field['id'] ) ) {
							$field['id'] = sanitize_title( $field['name'] );
						}

						if ( $field['type'] != 'header' ) {
							register_setting( $this->prefix . $data['id'], $this->prefix . $field['id'], $args );
						}

						// Add field to page
						$class = 'sunshine-settings sunshine-settings-' . $field['type'] . ' sunshine-settings-' . $field['id'];
						if ( ! empty( $field['class'] ) ) {
							$class .= ' ' . $field['class'];
						}

						add_settings_field(
							$field['id'],
							$field['name'],
							array( $this, 'display_field' ),
							$this->prefix . $data['id'],
							$data['id'],
							array(
								'field' => $field,
								'class' => $class,
							)
						);

					}
				}
			}
		}

		public function settings_section( $section ) {
			foreach ( $this->settings as $settings_group ) {
				if ( $section['id'] == $settings_group['id'] && ! empty( $settings_group['description'] ) ) {
					$html = '<div class="sunshine-settings-section-description">' . $settings_group['description'] . '</div>' . "\n";
					echo $html;
				}
			}
		}

		/**
		 * Generate HTML for displaying fields
		 *
		 * @param  array $args Field data
		 * @return void
		 */
		public function display_field( $args ) {

			$field = $args['field'];

			$html = '';

			$option_name  = $this->prefix . $field['id'];
			$option_value = maybe_unserialize( get_option( $option_name ) );

			if ( empty( $option_value ) && ! empty( $field['default'] ) ) {
				$option_value = $field['default'];
			}

			$defaults = array(
				'id'          => '',
				'name'        => '',
				'description' => '',
				'type'        => '',
				'min'         => '',
				'max'         => '',
				'step'        => '',
				'default'     => '',
				'placeholder' => '',
				'select2'     => false,
				'multiple'    => false,
				'options'     => array(),
				'before'      => '',
				'after'       => '',
				'required'    => false,
			);
			$field    = wp_parse_args( $field, $defaults );

			switch ( $field['type'] ) {

				case 'text':
				case 'password':
				case 'email':
					$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $option_value ) . '" ' . ( ( $field['required'] ) ? 'required' : '' ) . ' />' . "\n";
					$this->show_submit = true;
					break;

				case 'number':
					$html .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" min="' . esc_attr( $field['min'] ) . '" max="' . esc_attr( $field['max'] ) . '" step="' . esc_attr( $field['step'] ) . '" value="' . esc_attr( $option_value ) . '" ' . ( ( $field['required'] ) ? 'required' : '' ) . ' />' . "\n";
					$this->show_submit = true;
					break;

				case 'range':
					$option_value            = ( $option_value ) ? $option_value : 0;
					$min                     = ( isset( $field['min'] ) ) ? $field['min'] : 0;
					$max                     = ( isset( $field['max'] ) ) ? $field['max'] : 100;
					$step                    = ( isset( $field['step'] ) ) ? $field['step'] : 1;
					$set_value_function_name = 'sunshine_set_value_for_' . str_replace( '-', '_', sanitize_title_with_dashes( $field['id'] ) );
					$html                   .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $option_name ) . '" min="' . esc_attr( $field['min'] ) . '" max="' . esc_attr( $field['max'] ) . '" step="' . esc_attr( $field['step'] ) . '" value="' . esc_attr( $option_value ) . '" oninput="' . $set_value_function_name . '( value )" /> <output for="' . esc_attr( $field['id'] ) . '" id="' . esc_attr( $field['id'] ) . '-output">' . floatval( $option_value ) . '</output>' . "\n";
					$html                   .= '<script> function ' . $set_value_function_name . '( range_value ) { document.querySelector( "#' . esc_js( $field['id'] ) . '-output" ).value = range_value; } </script>';
					$this->show_submit = true;
					break;

				case 'textarea':
					$html .= '<textarea id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '">' . wp_kses_post( $option_value ) . '</textarea>' . "\n";
					$this->show_submit = true;
					break;

				case 'wysiwyg':
					wp_editor( $option_value, $field['id'], array( 'textarea_name' => $option_name ) );
					$this->show_submit = true;
					break;

				case 'checkbox':
					$checked = '';
					$html   .= '<input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $option_name ) . '" value="1" ' . checked( $option_value, 1, false ) . '/>' . "\n";
					$this->show_submit = true;
					break;

				case 'checkbox_multi':
					foreach ( $field['options'] as $k => $v ) {
						$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="checkbox" ' . checked( ( is_array( $option_value ) && in_array( $k, $option_value ) ), true, false ) . ' name="' . esc_attr( $option_name ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" />';
						if ( is_array( $v ) ) {
							if ( ! empty( $v['label'] ) ) {
								$html .= wp_kses_post( $v['label'] );
							}
							if ( ! empty( $v['description'] ) ) {
								$html .= '<span class="sunshine-checkout-label-description">' . wp_kses_post( $v['description'] ) . '</span>';
							}
						} else {
							$html .= wp_kses_post( $v );
						}
						$html .= '</label><br />';
					}
					$this->show_submit = true;
					break;

				case 'radio':
					foreach ( $field['options'] as $k => $v ) {
						$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><input type="radio" ' . checked( $k, $option_value, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" />';
						if ( is_array( $v ) ) {
							if ( ! empty( $v['label'] ) ) {
								$html .= wp_kses_post( $v['label'] );
							}
							if ( ! empty( $v['description'] ) ) {
								$html .= '<span class="sunshine-checkout-label-description">' . wp_kses_post( $v['description'] ) . '</span>';
							}
						} else {
							$html .= wp_kses_post( $v );
						}
						$html .= '</label><br />';
					}
					$this->show_submit = true;
					break;

				case 'radio_images':
					$html .= '<div class="sunshine-option-radio-images">';
					foreach ( $field['options'] as $k => $v ) {
						$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '"><img src="' . esc_url( $v['image'] ) . '" alt="" /><input type="radio" ' . checked( $k, $option_value, false ) . ' name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" />';
						if ( is_array( $v ) ) {
							if ( ! empty( $v['label'] ) ) {
								$html .= wp_kses_post( $v['label'] );
							}
							if ( ! empty( $v['description'] ) ) {
								$html .= '<span class="sunshine-label-description">' . wp_kses_post( $v['description'] ) . '</span>';
							}
						} else {
							$html .= wp_kses_post( $v );
						}
						$html .= '</label><br />';
					}
					$html .= '</div>';
					$this->show_submit = true;
					break;

				case 'select':
					if ( $field['options'] ) {
						$html .= '<select name="' . esc_attr( $option_name ) . ( ( $field['multiple'] ) ? '[]' : '' ) . '" id="' . esc_attr( $field['id'] ) . '"' . ( ( $field['multiple'] ) ? ' multiple="multiple"' : '' ) . ' ' . ( ( $field['required'] ) ? 'required' : '' ) . '>';
						foreach ( $field['options'] as $k => $v ) {
							$html .= '<option ' . selected( ( $option_value == $k ) || ( is_array( $option_value ) && in_array( $k, $option_value ) ), true, false ) . ' value="' . esc_attr( $k ) . '">' . wp_kses_post( $v ) . '</option>';
						}
						$html .= '</select> ';
					}
					if ( $field['select2'] ) {
						$html .= '<script type="text/javascript">jQuery(function () {
                                    jQuery("#' . esc_js( $field['id'] ) . '").select2({ width: "350px", placeholder: "' . esc_js( $field['placeholder'] ) . '" });
                                    });</script>';
					}
					$this->show_submit = true;
					break;

				case 'single_select_page':
					$selected = ( $option_value !== false ) ? $option_value : false;

					if ( $option_value == 0 ) {
						$selected = false;
					}

					$args = array(
						'name'       => $option_name,
						'id'         => $field['id'],
						'sort_order' => 'ASC',
						'echo'       => 0,
						'selected'   => $selected,
					);

					$html .= str_replace( "'>", "'><option></option>", wp_dropdown_pages( $args ) );

					if ( $selected ) {
						$html .= '<a href="' . esc_url( get_permalink( $selected ) ) . '" target="_blank" class="button">' . __( 'View page', 'sunshine-photo-cart' ) . '</a>';
					}

					$html .= '<script type="text/javascript">jQuery(function () {
                        jQuery("#' . esc_js( $field['id'] ) . '").select2({ width: "350px", placeholder: "' . esc_js( __( 'Please select a page', 'sunshine-photo-cart' ) ) . '" });
                    });</script>';
					$this->show_submit = true;
					break;

				case 'select_multi':
					$html .= '<select name="' . esc_attr( $option_name ) . '[]" id="' . esc_attr( $field['id'] ) . '" multiple="multiple">';
					foreach ( $field['options'] as $k => $v ) {
						$html .= '<option ' . selected( ( is_array( $option_value ) && in_array( $k, $option_value ) ), true, false ) . ' value="' . esc_attr( $k ) . '" />' . wp_kses_post( $v ) . '</label> ';
					}
					$html .= '</select> ';
					if ( $field['select2'] ) {
						$html .= '<script type="text/javascript">jQuery(function () {
                                    jQuery("#' . esc_js( $field['id'] ) . '").select2({ width: "350px", placeholder: "' . esc_js( $field['placeholder'] ) . '" });
                                    });</script>';
					}
					$this->show_submit = true;
					break;

				case 'galleries':
					$multiple = '';
					if ( $field['multiple'] ) {
						$option_name = $option_name . '[]';
						$multiple = 'multiple="multiple"';
					}
					$html .= '<select ' . ( ( $field['required'] ) ? 'required="required"' : '' ) . ' name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '" ' . $multiple . '>';
					$data = array();
					if ( ! empty( $option_value ) ) {
						foreach ( $option_value as $gallery_id ) {
							$gallery = sunshine_get_gallery( $gallery_id );
							$html .= '<option value="' . esc_attr( $gallery_id ) . '" selected="selected">' . esc_html( $gallery->get_name() ) . '</option>';
						}
					}
					$html .= '</select> ';
					$html .= '
						<script type="text/javascript">jQuery(function () {

							jQuery("#' . esc_js( $field['id'] ) . '").select2({
								width: "350px",
								placeholder: "' . esc_js( $field['placeholder'] ) . '",
								ajax: {
						            url: ajaxurl,
						            dataType: "json",
						            delay: 250,
						            data: function(params) {
						                return {
						                    action: "sunshine_search_galleries",
						                    search: params.term,
											security: "' . wp_create_nonce( 'sunshine_search_galleries' ) . '",
						                };
						            },
						            processResults: function(data) {
						                return {
						                    results: data
						                };
						            }
						        },
						        minimumInputLength: 3
								});
							});
						</script>';
					break;

				case 'products':
					$multiple = '';
					if ( $field['multiple'] ) {
						$option_name = $option_name . '[]';
						$multiple = 'multiple="multiple"';
					}
					$html .= '<select ' . ( ( $field['required'] ) ? 'required="required"' : '' ) . ' name="' . esc_attr( $option_name ) . '" id="' . esc_attr( $field['id'] ) . '" ' . $multiple . '>';
					$data = array();
					if ( ! empty( $option_value ) ) {
						foreach ( $option_value as $product_id ) {
							$product = sunshine_get_product( $product_id );
							$html .= '<option value="' . esc_attr( $product_id ) . '" selected="selected">' . esc_html( $product->get_name() ) . '</option>';
						}
					}
					$html .= '</select> ';
					$html .= '
						<script type="text/javascript">jQuery(function () {

							jQuery("#' . esc_js( $field['id'] ) . '").select2({
								width: "350px",
								placeholder: "' . esc_js( $field['placeholder'] ) . '",
								ajax: {
						            url: ajaxurl,
						            dataType: "json",
						            delay: 250,
						            data: function(params) {
						                return {
						                    action: "sunshine_search_products",
						                    search: params.term,
											security: "' . wp_create_nonce( 'sunshine_search_products' ) . '",
						                };
						            },
						            processResults: function(data) {
						                return {
						                    results: data
						                };
						            }
						        },
						        minimumInputLength: 3
								});
							});
						</script>';
					break;

				case 'dimensions':
					$w = ( ! empty( $option_value['w'] ) ) ? intval( $option_value['w'] ) : '';
					$h = ( ! empty( $option_value['h'] ) ) ? intval( $option_value['h'] ) : '';
					$html .= '<input type="number" min="0" name="' . esc_attr( $option_name ) . '[w]" step="1" style="width: 100px;" value="' . esc_attr( $w ) . '" />px &times; <input type="number" name="' . esc_attr( $option_name ) . '[h]" min="0" step="1" style="width: 100px;" value="' . esc_attr( $h ) . '" />px';
					$this->show_submit = true;
					break;

				case 'file':
					$html .= '<div id="' . esc_attr( $option_name ) . '_file" class="sunshine-file-preview">';
					if ( $option_value > 0 ) {
						$attachment_url = wp_get_attachment_url( $option_value );
						$attachment_name = get_the_title( $option_value );
						$html .= '<a href="' . esc_url( $attachment_url ) . '" target="_blank">' . esc_html( $attachment_name ) . '</a>';
					}
					$html .= '</div>';
					$html .= '<input id="' . esc_attr( $option_name ) . '_button" type="button" ' . ( ( ! empty( $field['media_type'] ) ) ? 'data-media_type="' . esc_attr( $field['media_type'] ) . '"' : '' ) . ' data-uploader_title="' . __( 'Upload a file', 'sunshine-photo-cart' ) . '" data-uploader_button_text="' . __( 'Select file', 'sunshine-photo-cart' ) . '" class="file_upload_button button" value="' . __( 'Upload new file', 'sunshine-photo-cart' ) . '" />' . "\n";
					$html .= '<input id="' . esc_attr( $option_name ) . '_delete" type="button" class="file_delete_button button delete" data-field="' . esc_attr( $option_name ) . '" style="display: ' . ( ( $option_value ) ? 'inline-block' : 'none' ) . '" value="' . __( 'Remove file', 'sunshine-photo-cart' ) . '" />' . "\n";
					$html .= '<input id="' . esc_attr( $option_name ) . '" class="file_data_field" type="hidden" name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $option_value ) . '"/>' . "\n";
					$this->show_submit = true;
					break;

				case 'image':
					$html .= '<div id="' . esc_attr( $option_name ) . '_preview" class="sunshine-image-preview">';
					if ( $option_value ) {
						$image_thumb = wp_get_attachment_image_src( $option_value, 'medium' );
						if ( $image_thumb ) {
							$html       .= '<img src="' . $image_thumb[0] . '" />';
						}
					}
					$html .= '</div>' . "\n";
					$html .= '<input id="' . esc_attr( $option_name ) . '_button" type="button" ' . ( ( ! empty( $field['media_type'] ) ) ? 'data-media_type="' . esc_attr( $field['media_type'] ) . '"' : '' ) . ' data-uploader_title="' . __( 'Upload an image', 'sunshine-photo-cart' ) . '" data-uploader_button_text="' . __( 'Use image', 'sunshine-photo-cart' ) . '" class="image_upload_button button" value="' . __( 'Upload new image', 'sunshine-photo-cart' ) . '" />' . "\n";
					$html .= '<input id="' . esc_attr( $option_name ) . '_delete" type="button" class="image_delete_button button delete" data-field="' . esc_attr( $option_name ) . '" style="display: ' . ( ( $option_value ) ? 'inline-block' : 'none' ) . '" value="' . __( 'Remove image', 'sunshine-photo-cart' ) . '" />' . "\n";
					$html .= '<input id="' . esc_attr( $option_name ) . '" class="image_data_field" type="hidden" name="' . esc_attr( $option_name ) . '" value="' . esc_attr( $option_value ) . '"/>' . "\n";
					$this->show_submit = true;
					break;

				case 'color':
					?>
					<input type="text" name="<?php echo esc_attr( $option_name ); ?>" class="colorpicker" value="<?php echo esc_attr( $option_value ); ?>" />
					<?php
					$this->show_submit = true;
					break;

				case 'header':
				case 'title':
					$html .= '<h3>' . $field['name'] . '</h3>';
					break;

				case 'html':
					if ( ! empty( $field['html'] ) ) {
						$html .= $field['html'];
					}
					break;

				case 'license':
					$html .= '<input id="' . esc_attr( $field['id'] ) . '" size="40" type="text" name="' . esc_attr( $option_name ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $option_value ) . '" ' . ( ( $field['required'] ) ? 'required' : '' ) . ' ' . ( ( isset( $field['status'] ) && $field['status'] == 'valid' ) ? 'readonly' : '' ) . ' />' . "\n";
					if ( $option_value && isset( $field['status'] ) && $field['status'] == 'valid' ) {
						$base_url = admin_url( 'admin.php?page=sunshine&section=license' );
						$url = wp_nonce_url( $base_url, 'sunshine_addon_deactivate_' . $field['addon'], 'sunshine_addon_deactivate',  );
						$url = add_query_arg( 'addon', $field['addon'], $url );
						$html .= '<a href="' . esc_url( $url ) . '" class="button">' . __( 'Deactivate', 'sunshine-photo-cart' ) . '</a>';
						$url = add_query_arg( 'check_license', $field['addon'], $base_url );
						$html .= ' <a href="' . esc_url( $url ) . '" class="button">' . __( 'Refresh license', 'sunshine-photo-cart' ) . '</a>';
					} else {
						$html .= '<button class="button">' . __( 'Activate', 'sunshine-photo-cart' ) . '</button>';
					}
					$this->show_submit = true;
					break;

				default:
					do_action( $this->prefix . $field['type'] . '_display', $field );
					break;

			}

			$html_safe = $field['before'] . $html . $field['after'];

			switch ( $field['type'] ) {

				case 'checkbox':
				case 'radio':
				case 'checkbox_multi':
				case 'color':
				case 'header':
					if ( ! empty( $field['description'] ) ) {
						$html_safe .= '<span class="sunshine-settings-description">' . $field['description'] . '</span>';
					}
					break;

				default:
					if ( ! empty( $field['description'] ) ) {
						$html_safe .= '<div class="sunshine-settings-description">' . $field['description'] . '</div>';
					}
					break;
			}

			echo $html_safe;
		}

		/**
		 * Load settings page content
		 *
		 * @return void
		 */
		public function settings_page() {

			if ( empty( $this->settings ) ) {
				return;
			}
			$section = $this->settings[0]['id']; // Default to the first section

			// Get passed section
			if ( isset( $_GET['section'] ) ) {
				$section = sanitize_text_field( $_GET['section'] );
			}

			// Build page HTML
			?>
		<div class="wrap">
			<h2></h2>

			<div id="sunshine-settings-page">

				<?php
				if ( count( $this->settings ) > 1 ) {
					echo '<nav id="sunshine-settings-menu">';
					echo '<ul>';
					$count = 0;
					foreach ( $this->settings as $tab ) {
						$count++;
						$class = '';
						if ( ( isset( $_GET['section'] ) && $tab['id'] == $_GET['section'] ) || ( ! isset( $_GET['section'] ) && $count == 1 ) ) {
							$class = 'sunshine-settings-active';
						}
						$url = add_query_arg(
							array(
								'page'    => $this->key,
								'section' => $tab['id'],
							),
							admin_url( 'admin.php' )
						);
						// $icon = ( !empty( $tab['icon'] ) ) ? '<div class="sunshine-settings-menu-icon">' . file_get_contents( $tab['icon'] ) . '</div>' : '';
						echo '<li class="' . $class . '" id="sunshine-settings-menu-' . esc_attr( $tab['id'] ) . '"><a href="' . esc_url( $url ) . '"><span>' . wp_kses_post( $tab['title'] ) . '</span></a></li>';
					}
					echo '</ul>';
					echo '</nav>';
				}
				?>
				<div class="sunshine-settings-section">
					<?php do_action( 'sunshine_' . $this->key . '_settings_before' ); ?>
					<form method="post" action="options.php" enctype="multipart/form-data" id="sunshine-settings-section-<?php echo esc_attr( $section ); ?>" class="sunshine-settings-section-form">
					<?php
					// Get settings fields
					settings_fields( $this->key . '_' . $section );
					do_settings_sections( $this->key . '_' . $section );
					?>
					<?php if ( $this->show_submit ) { ?>
						<p class="submit"><input name="Submit" type="submit" class="button-primary" value="<?php echo esc_attr( __( 'Save Settings', 'sunshine-photo-cart' ) ); ?>" /></p>
					<?php } ?>
					</form>
					<?php do_action( 'sunshine_' . $this->key . '_settings_after' ); ?>
				</div>

			</div>

		</div>

		<script>
		jQuery( document ).ready(function($){
			$( '.sunshine-settings-header th' ).remove();
			$( '.sunshine-settings-header td' ).attr( 'colspan', 2 );
			$.each( $( '.sunshine-settings-section tr' ), function(){
				if ( $( 'th', this ).html() == '' ) {
					$( 'th[scope="row"]', this ).remove();
					$( '> td', this ).attr( 'colspan', 2 );
				}
			});
		});

		// Field conditions
		function sunshine_get_condition_field_value( field_id ) {
			var field_row = jQuery( '.sunshine-settings-' + field_id );
			var field_type = '';
			if ( jQuery( 'input[type="radio"]', field_row ).length > 0 ) {
				field_type = 'radio';
			} else if ( jQuery( 'input[type="checkbox"]', field_row ).length > 0 ) {
				field_type = 'radio';
			} else if ( jQuery( 'input', field_row ).length > 0 ) {
				field_type = 'text';
			} else if ( jQuery( 'select', field_row ).length > 0 ) {
				field_type = 'select';
			}

			if ( field_type == '' ) {
				return false;
			}

			var value;
			if ( field_type == 'text' ) { // Text input box
				value = jQuery( 'input', field_row ).val();
			} else if ( field_type == 'checkbox' ) {
				value = jQuery( 'input:checked', field_row ).val();
				if ( typeof value === 'undefined' ) {
					value = 'no';
				}
			} else if ( field_type == 'radio' ) {
				value = jQuery( 'input:checked', field_row ).val();
				if ( typeof value === 'undefined' ) {
					value = 0;
				}
			} else if ( field_type == 'select' ) {
				value = jQuery( 'select option:selected', field_row ).val();
			}

			return value;
		}

			<?php
			foreach ( $this->settings as $section_id => $section_data ) {
				if ( ! isset( $section_data['id'] ) || $section_data['id'] != $section || empty( $section_data['fields'] ) ) {
					continue;
				}
				$i = 0;
				foreach ( $section_data['fields'] as $field ) {
					if ( ! empty( $field['conditions'] ) && is_array( $field['conditions'] ) ) {
						foreach ( $field['conditions'] as $condition ) {
							if ( empty( $condition['compare'] ) || empty( $condition['value'] ) || empty( $condition['field'] ) || empty( $condition['action'] ) ) {
								continue;
							}
							if ( ! in_array( $condition['action'], array( 'show', 'hide' ) ) ) {
								continue;
							}
							if ( ! in_array( $condition['compare'], array( '==', '!=', '<', '>', '<=', '>=' ) ) ) {
								continue;
							}
							$i++;
							?>
							var condition_field_value_<?php echo $i; ?> = sunshine_get_condition_field_value( '<?php echo esc_js( $condition['field'] ); ?>' );
							function condition_field_action_<?php echo $i; ?>( value ) {
								<?php
								$action_target     = ( isset( $condition['action_target'] ) ) ? $condition['action_target'] : '.sunshine-settings-' . $field['id'];
								$true_action       = ( $condition['action'] == 'show' ) ? 'show' : 'hide';
								$false_action      = ( $condition['action'] == 'show' ) ? 'hide' : 'show';
								$comparison_string = '';
								if ( is_array( $condition['value'] ) ) { // If value is an array, need to compare against each array value
									$comparison_strings = array();
									foreach ( $condition['value'] as $value ) {
										$comparison_strings[] = '( value ' . esc_js( $condition['compare'] ) . ' "' . esc_js( $value ) . '" )';
									}
									$comparison_string = join( ' || ', $comparison_strings );
								} else {
									$comparison_string = 'value ' . esc_js( $condition['compare'] ) . ' "' . esc_js( $condition['value'] ) . '"';
								}
								?>
								if ( <?php echo $comparison_string; ?> ) {
									console.log( value + ' true' );
									jQuery( '<?php echo esc_js( $action_target ); ?>' ).closest( 'tr' ).<?php echo $true_action; ?>();
								} else {
									console.log( value + ' false' );
									jQuery( '<?php echo esc_js( $action_target ); ?>' ).closest( 'tr' ).<?php echo $false_action; ?>();
								}
							}

							// Default action
							condition_field_action_<?php echo $i; ?>( condition_field_value_<?php echo $i; ?> );

							// On change action
							jQuery( '.sunshine-settings-<?php echo esc_js( $condition['field'] ); ?> input, .sunshine-settings-<?php echo esc_js( $condition['field'] ); ?> select' ).on( 'change', function(){
								condition_field_value_<?php echo $i; ?> = sunshine_get_condition_field_value( '<?php echo esc_js( $condition['field'] ); ?>' );
								condition_field_action_<?php echo $i; ?>( condition_field_value_<?php echo $i; ?> );
							});
							<?php
						}
					}
				}
			}
			?>

		</script>

			<?php
		}

		public function scripts() {

			if ( ! isset( $_GET['page'] ) || $_GET['page'] != $this->key ) {
				return;
			}
			?>
		<script>
		jQuery(document).ready(function($) {

			/***** Colour picker *****/

			$('.colorpicker').wpColorPicker();

			/*
			$('.colorpicker').hide();
			$('.colorpicker').each( function() {
				$(this).farbtastic( $(this).closest('.color-picker').find('.color') );
			});

			$('.color').click(function() {
				$(this).closest('.color-picker').find('.colorpicker').fadeIn();
			});

			$(document).mousedown(function() {
				$('.colorpicker').each(function() {
					var display = $(this).css('display');
					if ( display == 'block' )
						$(this).fadeOut();
				});
			});
			*/


			/***** Uploading images *****/

			var file_frame;

			jQuery.fn.uploadMediaFile = function( button, preview_media ) {
				var button_id = button.attr('id');
				var field_id = button_id.replace( '_button', '' );
				var preview_id = button_id.replace( '_button', '_preview' );
				var media_type = button.data( 'media_type' );

				// If the media frame already exists, reopen it.
				if ( file_frame ) {
				  file_frame.open();
				  return;
				}

				// Create the media frame.
				file_frame = wp.media.frames.file_frame = wp.media({
				  title: jQuery( this ).data( 'uploader_title' ),
				  button: {
					text: jQuery( this ).data( 'uploader_button_text' ),
				  },
					library: {
						type: media_type
					},
				  multiple: false
				});

				// When an image is selected, run a callback.
				file_frame.on( 'select', function() {
				  attachment = file_frame.state().get( 'selection' ).first().toJSON();
				  jQuery( "#" + field_id ).val( attachment.id );
				  if ( preview_media ) {
					  if ( !jQuery( '#' + preview_id + ' img' ).length ) {
						  var img = jQuery( '<img />' ).appendTo( '#' + preview_id );
					  }
					  jQuery( '#' + preview_id + ' img' ).attr( 'src', attachment.sizes.full.url );
				  } else {
					jQuery( "#" + field_id + "_file" ).html( '<a href="' + attachment.url + '" target="_blank">' + attachment.filename + '</a>' );
				  }
				  jQuery( '#' + field_id + '_delete' ).show();
				});

				// Finally, open the modal
				file_frame.open();
			}

			jQuery('.image_upload_button').click(function() {
				jQuery.fn.uploadMediaFile( jQuery(this), true );
			});
			jQuery('.file_upload_button').click(function() {
				jQuery.fn.uploadMediaFile( jQuery(this), false );
			});

			jQuery('.image_delete_button').click(function() {
				var field_id = $( this ).data( 'field' );
				jQuery(this).closest('td').find( '.image_data_field' ).val( '' );
				jQuery( '#' + field_id + '_preview img' ).remove();
				jQuery( '#' + field_id + '_delete' ).hide();
				return false;
			});

			jQuery('.file_delete_button').click(function() {
				var field_id = $( this ).data( 'field' );
				jQuery(this).closest('td').find( '.file_data_field' ).val( '' );
				jQuery( '#' + field_id + '_file' ).html( '' );
				jQuery( '#' + field_id + '_delete' ).hide();
				return false;
			});


		});
		</script>
			<?php
		}

		public function options_capability( $capability ) {
			return 'sunshine_manage_options';
		}

		public function admin_notices() {
			if ( isset( $_GET['page'] ) && $_GET['page'] == 'sunshine' ) {
				settings_errors();
			}
		}

		function flush_endpoint_rewrite_rules_check() {
			$flush = false;
			if ( isset( $_POST['sunshine_endpoint_gallery'] ) && SPC()->get_option( 'endpoint_gallery' ) != $_POST['sunshine_endpoint_gallery'] ) {
				$flush = true;
			}
			if ( isset( $_POST['sunshine_endpoint_order_received'] ) && SPC()->get_option( 'endpoint_order_received' ) != $_POST['sunshine_endpoint_order_received'] ) {
				$flush = true;
			}
			if ( isset( $_POST['sunshine_endpoint_store'] ) && SPC()->get_option( 'endpoint_store' ) != $_POST['sunshine_endpoint_store'] ) {
				$flush = true;
			}
			if ( $flush ) {
				update_option( 'sunshine_flush_rewrite_rules', true, false );
			}
		}

		function flush_endpoint_rewrite_rules_process() {
			$flush = get_option( 'sunshine_flush_rewrite_rules' );
			if ( $flush ) {
				flush_rewrite_rules();
			}
		}

	}

}

$sunshine_settings = new SPC_Settings_API( 'sunshine', 'Sunshine Photo Cart', 'Settings', 'sunshine_admin', SUNSHINE_PHOTO_CART_URL . 'assets/images/sun.svg' );
