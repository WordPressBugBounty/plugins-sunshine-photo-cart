<?php

function sunshine_form_field( $id, $field, $value = '', $echo = true ) {

	if ( isset( $field['visible'] ) && ! $field['visible'] ) {
		return;
	}

	if ( ! empty( $field['default'] ) ) {
		$value = $field['default'];
	}

	$defaults = array(
		'id' => $id,
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
		'html'        => '',
		'required'    => false,
		'autocomplete' => '',
		'class' => ''
	);
	$field    = wp_parse_args( $field, $defaults );

	$html = '';

	switch ( $field['type'] ) {

		case 'legend':
			$html .= '<legend>' . esc_html( $field['name'] ) . '</legend>';
			break;

		case 'email':
		case 'tel':
		case 'text':
		case 'password':
			$html .= '<input ' . ( ( $field['required'] ) ? 'required="required"' : '' ) . ' autocomplete="' . esc_attr( $field['autocomplete'] ) . '" id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $id ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" value="' . esc_attr( $value ) . '" />' . "\n";
			break;

		case 'number':
			$html .= '<input ' . ( ( $field['required'] ) ? 'required="required"' : '' ) . ' autocomplete="' . esc_attr( $field['autocomplete'] ) . '" id="' . esc_attr( $field['id'] ) . '" type="' . $field['type'] . '" name="' . esc_attr( $id ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" min="' . esc_attr( $field['min'] ) . '" max="' . esc_attr( $field['max'] ) . '" step="' . esc_attr( $field['step'] ) . '" value="' . esc_attr( $value ) . '" />' . "\n";
			break;

		case 'textarea':
			$html .= '<textarea autocomplete="' . esc_attr( $field['autocomplete'] ) . '" ' . ( ( $field['required'] ) ? 'required="required"' : '' ) . ' id="' . esc_attr( $field['id'] ) . '" rows="5" cols="50" name="' . esc_attr( $id ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '">' . wp_kses_post( $value ) . '</textarea>' . "\n";
			break;

		case 'checkbox':
			$html .= '<label><input id="' . esc_attr( $field['id'] ) . '" type="' . esc_attr( $field['type'] ) . '" name="' . esc_attr( $id ) . '" value="yes" ' . checked( ! empty( $value ), true, false ) . '/> ' . $field['name'] . '</label>' . "\n";
			break;

		case 'checkbox_multi':
			foreach ( $field['options'] as $k => $v ) {
				$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '" class="sunshine--form--field--checkbox-option"><input type="checkbox" ' . ( ( $field['required'] ) ? 'required="required"' : '' ) . ' ' . checked( ( is_array( $value ) && in_array( $k, $value ) ), true, false ) . ' name="' . esc_attr( $field['id'] ) . '[]" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ' . wp_kses_post( $v );
				$html .= '</label>';
			}
			break;

		case 'radio':
			foreach ( $field['options'] as $k => $v ) {
				$html .= '<label for="' . esc_attr( $field['id'] . '_' . $k ) . '" class="sunshine--form--field--radio-option" id="sunshine--form--field--radio-option--' . esc_attr( $field['id'] ) . '--' . esc_attr( sanitize_title( $k ) ) . '"><input ' . ( ( $field['required'] ) ? 'required="required"' : '' ) . ' type="radio" ' . checked( $k, $value, false ) . ' name="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $k ) . '" id="' . esc_attr( $field['id'] . '_' . $k ) . '" /> ';
				if ( is_array( $v ) ) {
					if ( ! empty( $v['label'] ) ) {
						$html .= wp_kses_post( $v['label'] );
					}
					if ( ! empty( $v['description'] ) ) {
						$html .= '<span class="sunshine--form--field--label-description">' . $v['description'] . '</span>';
					}
				} else {
					$html .= wp_kses_post( $v );
				}
				$html .= '</label>';
			}
			break;

		case 'select':
			$html .= '<select autocomplete="' . esc_attr( $field['autocomplete'] ) . '" ' . ( ( $field['required'] ) ? 'required="required"' : '' ) . ' name="' . esc_attr( $id ) . '" id="' . esc_attr( $field['id'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '">';
			foreach ( $field['options'] as $k => $v ) {
				$html .= '<option ' . selected( ( $value == $k ) || ( is_array( $value ) && in_array( $k, $value ) ), true, false ) . ' value="' . esc_attr( $k ) . '">' . wp_kses_post( $v ) . '</option>';
			}
			$html .= '</select> ';
			if ( $field['select2'] ) {
				$html .= '
				<script type="text/javascript">jQuery(function () {
					jQuery("#' . esc_js( $field['id'] ) . '").select2({ width: "350px", placeholder: "' . esc_js( $field['placeholder'] ) . '" });
					});</script>';
			}
			break;

		case 'country':
			$html .= '<select autocomplete="' . esc_attr( $field['autocomplete'] ) . '" ' . ( ( $field['required'] ) ? 'required="required"' : '' ) . ' name="' . esc_attr( $id ) . '" id="' . esc_attr( $field['id'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '">';
			foreach ( $field['options'] as $k => $v ) {
				$html .= '<option ' . selected( ( $value == $k ) || ( is_array( $value ) && in_array( $k, $value ) ), true, false ) . ' value="' . esc_attr( $k ) . '">' . wp_kses_post( $v ) . '</option>';
			}
			$html .= '</select> ';
			if ( $field['select2'] ) {
				$html .= '
				<script type="text/javascript">jQuery(function () {
					jQuery("#' . esc_js( $field['id'] ) . '").select2({ width: "350px", placeholder: "' . esc_js( $field['placeholder'] ) . '" });
					});</script>';
			}
			break;

		case 'select_multi':
			$html .= '<select autocomplete="' . esc_attr( $field['autocomplete'] ) . '" ' . ( ( $field['required'] ) ? 'required="required"' : '' ) . ' name="' . esc_attr( $id ) . '[]" id="' . esc_attr( $field['id'] ) . '" multiple="multiple">';
			foreach ( $field['options'] as $k => $v ) {
				$html .= '<option ' . selected( in_array( $k, $value ), true, false ) . ' value="' . esc_attr( $k ) . '" />' . wp_kses_post( $v ) . '</label> ';
			}
			$html .= '</select> ';
			break;

		case 'html':
			$html .= $field['html'];
			break;

		case 'submit':
			$html .= '<button id="' . esc_attr( $id ) . '" type="submit" class="sunshine--button">' . $field['name'] . '</button>';
			$html .= wp_nonce_field( $id, $id, true, false );
			break;

		default:
			do_action( 'sunshine_form_field_' . $field['type'] . '_display' );
			break;

	}

	$required = '';
	if ( isset( $field['required'] ) && $field['required'] ) {
		$required = '<abbr>' . apply_filters( 'sunshine_form_field_required_symbol', '*' ) . '</abbr>';
	}

	// Add label
	switch ( $field['type'] ) {

		case 'radio':
		case 'checkbox_multi':
			if ( ! empty( $field['name'] ) ) {
				$html = '<span class="sunshine--form--field--name">' . $field['name'] . '</span>' . $html;
			}
			break;

		case 'legend':
		case 'header':
		case 'submit':
		case 'checkbox':
			break;
		case 'checkboxXXXX':
			$html = '<span class="sunshine--form--field--name">' . $field['name'] . '</span><label for="' . esc_attr( $field['id'] ) . '"><span class="sunshine--form--field--name">' . $html . esc_html( $field['description'] ) . $required . '</span></label>';
			break;

		default:
			$html = '<label for="' . esc_attr( $field['id'] ) . '"><span class="sunshine--form--field--name">' . esc_html( $field['name'] ) . $required . '</span></label>' . $html;
			break;

	}

	// Add description
	switch ( $field['type'] ) {

		case 'radio':
		case 'checkbox_multi':
		case 'legend':
			if ( ! empty( $field['description'] ) ) {
				$html .= '<span class="sunshine--form--field-description">' . $field['description'] . '</span>';
			}
			break;

		case 'checkbox':
			break;

		default:
			if ( ! empty( $field['description'] ) ) {
				$html .= '<br /><span class="sunshine--form--field-description">' . $field['description'] . '</span>' . "\n";
			}
			break;
	}

	$html .= apply_filters( 'sunshine_form_field_extra_' . $field['id'], '' );

	$size = ( isset( $field['size'] ) && in_array( $field['size'], array( 'full', 'half', 'third' ) ) ) ? $field['size'] : 'full';
	$show = ( isset( $field['show'] ) && ! $field['show'] ) ? ' style="display: none;"' : '';

	$classes = array(
		'sunshine--form--field-' . $field['type'],
		'sunshine--form--field-' . $size,
	);
	/*
	if ( ! empty( $this->errors[ $id ] ) ) {
		$classes[] = 'sunshine--form--field-has-error';
		$html     .= '<div class="sunshine--form--field-error">' . wp_kses_post( $this->errors[ $id ] ) . '</div>';
	}
	*/

	if ( isset( $field['visible'] ) && !$field['visible'] ) {
		$classes[] = 'sunshine--form--field-hidden';
	}

	if ( isset( $field['required'] ) && $field['required'] ) {
		$classes[] = 'sunshine--form--field-required';
	}

	$before = ( ! empty( $field['before'] ) ) ? '<div class="sunshine--form--field-before" id="sunshine--form--field--before--' . esc_attr( $field['id'] ) . '">' . $field['before'] . '</div>' : '';
	if ( $before ) {
		$html .= $before;
	}

	$html = '<div class="sunshine--form--field ' . esc_attr( join( ' ', $classes ) ) . '" id="sunshine--form--field--' . esc_attr( $field['id'] ) . '" data-type="' . esc_attr( $field['type'] ) . '"' . $show . '>' . $html. '</div>';

	$after  = ( ! empty( $field['after'] ) ) ? '<div class="sunshine--form--field-after" id="sunshine--form--field--after--' . esc_attr( $field['id'] ) . '">' . $field['after'] . '</div>' : '';
	if ( $after ) {
		$html .= $after;
	}

	if ( $echo ) {
		echo $html;
		return;
	}
	return $html;

}
