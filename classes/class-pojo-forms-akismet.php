<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Pojo_Forms_Akismet {

	public function remote_check_comment( $params ) {
		$response = Akismet::http_post( _http_build_query( $params, '', '&' ), 'comment-check' );
		return ( 'true' === $response[1] );
	}

	public function register_form_akismet_metabox( $meta_boxes = array() ) {
		$fields = array();

		$fields[] = array(
			'id' => 'akismet_description',
			'type' => Pojo_MetaBox::FIELD_RAW_HTML,
			'title' => __( 'Spam Filter', 'pojo-forms' ),
			'raw' => __( 'If you are using the Akismet plugin you can also use it as the form\'s spam filter by setting the fields. Insert the relevant field\'s ID shortcode according to the following settings, e.g. Author Email: [form-field-1]', 'pojo-forms' ),
		);

		$fields[] = array(
			'id' => 'author',
			'title' => __( 'Author', 'pojo-forms' ),
			'std' => '',
		);

		$fields[] = array(
			'id' => 'author_email',
			'title' => __( 'Author Email', 'pojo-forms' ),
			'std' => '',
		);

		$fields[] = array(
			'id' => 'author_url',
			'title' => __( 'Author Url', 'pojo-forms' ),
			'std' => '',
		);

		$fields[] = array(
			'id' => 'content',
			'title' => __( 'Content', 'pojo-forms' ),
			'std' => '',
		);

		$meta_boxes[] = array(
			'id' => 'pojo-forms-akismet',
			'title' => __( 'Akismet Options', 'pojo-forms' ),
			'post_types' => array( 'pojo_forms' ),
			'context' => 'side',
			'prefix' => 'form_akismet_',
			'fields' => $fields,
		);

		return $meta_boxes;
	}

	public function skip_contact( $skip, $form_id, $inline_shortcodes ) {
		if ( $skip )
			return $skip;

		$params = array();

		$params['comment_author'] = strtr( atmb_get_field( 'form_akismet_author', $form_id ), $inline_shortcodes );
		$params['comment_author_email'] = strtr( atmb_get_field( 'form_akismet_author_email', $form_id ), $inline_shortcodes );
		$params['comment_author_url'] = strtr( atmb_get_field( 'form_akismet_author_url', $form_id ), $inline_shortcodes );
		$params['comment_content'] = strtr( atmb_get_field( 'form_akismet_content', $form_id ), $inline_shortcodes );
		
		$params['blog'] = get_option( 'home' );
		$params['blog_lang'] = get_locale();
		$params['blog_charset'] = get_option( 'blog_charset' );
		
		$params['user_ip'] = POJO_FORMS()->helpers->get_client_ip();
		$params['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		$params['referrer'] = $_SERVER['HTTP_REFERER'];
		
		// http://blog.akismet.com/2012/06/19/pro-tip-tell-us-your-comment_type/
		$params['comment_type'] = 'contact-form';
		
		$ignore = array( 'HTTP_COOKIE', 'HTTP_COOKIE2', 'PHP_AUTH_PW' );
		foreach ( $_SERVER as $key => $value ) {
			if ( ! in_array( $key, $ignore ) && is_string( $value ) )
				$params[ $key ] = $value;
		}
		
		if ( $this->remote_check_comment( $params ) )
			$skip = true;
		
		return $skip;
	}

	public function __construct() {
		add_filter( 'pojo_forms_skip_contact', array( &$this, 'skip_contact' ), 30, 3 );
		add_filter( 'pojo_meta_boxes', array( &$this, 'register_form_akismet_metabox' ), 70 );
	}
	
}