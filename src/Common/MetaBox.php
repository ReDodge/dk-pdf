<?php # -*- coding: utf-8 -*-

namespace Dinamiko\DKPDF\Common;

class MetaBox {

	public function init() {

		add_action( 'add_meta_boxes', array( $this, 'dkpdf_meta_box_setup' ) );
		add_action( 'save_post', array( $this, 'dkpdf_meta_box_save' ) );
	}

	/**
	 * Add metabox to post types
	 */
	public function dkpdf_meta_box_setup() {

		$pdfbutton_post_types = sanitize_option( 'dkpdf_pdfbutton_post_types',
			get_option( 'dkpdf_pdfbutton_post_types' ) );

		if ( $pdfbutton_post_types ) {

			// add metabox to selected post types
			foreach ( $pdfbutton_post_types as $post_type ) {
				add_meta_box(
					'post-data',
					__( 'DK PDF', 'dkpdf' ),
					array( $this, 'dkpdf_meta_box_content' ),
					$post_type,
					'normal',
					'high'
				);
			}
		}
	}

	/**
	 * Save metabox data
	 */
	public function dkpdf_meta_box_save( $post_id ) {

		global $post, $messages;

		if ( isset( $_POST[ 'dkpdf_nonce' ] ) ) {

			// Verify nonce
			if ( ! wp_verify_nonce( filter_var( wp_unslash( $_POST['dkpdf_nonce'] ), FILTER_SANITIZE_STRING ),
				plugin_basename( __FILE__ ) ) ) {
				return $post_id;
			}

		} else {

			return $post_id;

		}

		// Verify user permissions
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Handle custom fields
		$field_data = $this->dkpdf_get_custom_fields_settings();
		$fields     = array_keys( $field_data );

		foreach ( $fields as $f ) {

			${$f} = '';

			if ( isset( $_POST[ $f ] ) ) {
				${$f} = strip_tags( trim( filter_var( wp_unslash( $_POST[ $f ] ), FILTER_SANITIZE_STRING) ) );
			}

			// Escape the URLs.
			if ( 'url' == $field_data[ $f ]['type'] ) {
				${$f} = esc_url( ${$f} );
			}

			if ( ${$f} == '' ) {
				delete_post_meta( $post_id, $f, get_post_meta( $post_id, $f, true ) );
			} else {
				update_post_meta( $post_id, $f, ${$f} );
			}

		}

	}

	/**
	 * Add content to metabox
	 */
	public function dkpdf_meta_box_content() {

		global $post_id;
		$fields     = get_post_custom( $post_id );
		$field_data = $this->dkpdf_get_custom_fields_settings();

		$html = '';

		$html .= '<input type="hidden" name="' . 'dkpdf' . '_nonce" id="' . 'dkpdf' . '_nonce" value="' . wp_create_nonce( plugin_basename( __FILE__ ) ) . '" />';

		if ( 0 < count( $field_data ) ) {
			$html .= '<table class="form-table">' . "\n";
			$html .= '<tbody>' . "\n";

			foreach ( $field_data as $k => $v ) {
				$data = $v['default'];

				if ( isset( $fields[ $k ] ) && isset( $fields[ $k ][0] ) ) {
					$data = $fields[ $k ][0];
				}

				if ( $v['type'] == 'checkbox' ) {
					$html .= '<tr valign="top"><th scope="row">' . $v['name'] . '</th><td><input name="' . esc_attr( $k ) . '" type="checkbox" id="' . esc_attr( $k ) . '" ' . checked( 'on',
							$data,
							false ) . ' /> <label for="' . esc_attr( $k ) . '"><span class="description">' . $v['description'] . '</span></label>' . "\n";
					$html .= '</td></tr>' . "\n";
				} else {
					$html .= '<tr valign="top"><th scope="row"><label for="' . esc_attr( $k ) . '">' . $v['name'] . '</label></th><td><input name="' . esc_attr( $k ) . '" type="text" id="' . esc_attr( $k ) . '" class="regular-text" value="' . esc_attr( $data ) . '" />' . "\n";
					$html .= '<p class="description">' . $v['description'] . '</p>' . "\n";
					$html .= '</td></tr>' . "\n";
				}

			}

			$html .= '</tbody>' . "\n";
			$html .= '</table>' . "\n";
		}

		$allowed_html = array(
			'input' => array(
				'type'  => array(),
				'name'  => array(),
				'id'    => array(),
				'value' => array(),
				'checked' => array(),
			),
			'table' => array(
				'class' => array(),
			),
			'tbody' => array(),
			'tr'    => array(
				'valign' => array(),
			),
			'td'    => array(),
			'th'    => array(
				'scope' => array(),
			),
			'label' => array(
				'for' => array(),
			),
			'span'  => array(
				'class' => array(),
			),
		);

		echo wp_kses( $html, $allowed_html );
	}

	/**
	 * Return array with all fields in metabox
	 */
	public function dkpdf_get_custom_fields_settings() {

		$fields = array();

		$fields['_hide_pdfbutton'] = array(
			'name'        => __( 'Disable DK PDF Button:', 'dkpdf' ),
			'description' => '',
			'type'        => 'checkbox',
			'default'     => '',
			'section'     => '',
		);

		return $fields;
	}
}