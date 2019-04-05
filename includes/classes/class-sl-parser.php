<?php

if ( ! class_exists( '\WordPressdotorg\Plugin_Directory\Readme\Parser' ) ) {
	require_once EDD_SL_PLUGIN_DIR . 'includes/class-parser.php';
}

use \WordPressdotorg\Plugin_Directory\Readme\Parser as Parser;

/**
 * Class EDD_SL_Readme_Parser
 *
 * @link   https://meta.trac.wordpress.org/browser/sites/trunk/wordpress.org/public_html/wp-content/plugins/plugin-directory/readme/class-parser.php
 */
class EDD_SL_Readme_Parser extends Parser {

	/**
	 * Constructor.
	 *
	 * @return void
	 */
	public function __construct( $file ) {
		$file_path = WP_CONTENT_DIR . '/edd-sl-tmp-readme.txt';
		$file_path = file_put_contents( $file_path, $file ) ? $file_path : false;
		parent::__construct( $file_path );
	}

	/**
	 * Parse markdown and return HTML.
	 *
	 * @link https://github.com/erusev/parsedown
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public function parse_markdown( $text ) {
		if ( ! class_exists( 'Parsedown' ) ) {
			require_once EDD_SL_PLUGIN_DIR . 'includes/Parsedown.php';
		}
		static $markdown = null;

		if ( null === $markdown ) {
			$markdown = new Parsedown();
		}

		return $markdown->text( $text );
	}

	/**
	 * Return parsed readme.txt as array.
	 *
	 * @return array $data
	 */
	public function parse_data() {
		$data = array();
		foreach ( get_object_vars( $this ) as $key => $value ) {
			$data[ $key ] = 'contributors' === $key ? $this->create_contributors( $value ) : $value;
		}
		$data = $this->faq_as_h4( $data );
		$data = $this->readme_section_as_h4( 'changelog', $data );
		$data = $this->readme_section_as_h4( 'description', $data );

		@unlink( WP_CONTENT_DIR . '/edd-sl-tmp-readme.txt' );

		return $data;
	}

	/**
	 * @param array $users
	 *
	 * @return array
	 */
	protected function sanitize_contributors( $users ) {
		return $users;
	}

	/**
	 * Create contributor data.
	 *
	 * @param array $users
	 *
	 * @return array $contributors
	 */
	private function create_contributors( $users ) {
		global $wp_version;
		$contributors = [];
		foreach ( (array) $users as $contributor ) {
			$contributors[ $contributor ]['display_name'] = $contributor;
			$contributors[ $contributor ]['profile']      = '//profiles.wordpress.org/' . $contributor;
			$contributors[ $contributor ]['avatar']       = 'https://wordpress.org/grav-redirect.php?user=' . $contributor;
			if ( $wp_version < '5.1-alpha' ) {
				$contributors[ $contributor ] = '//profiles.wordpress.org/' . $contributor;
			}
		}

		return $contributors;
	}

	/**
	 * Converts FAQ from dictionary list to h4 style.
	 *
	 * @param array $data Array of parsed readme data.
	 *
	 * @return array $data
	 */
	public function faq_as_h4( $data ) {
		if ( empty( $data['faq'] ) ) {
			return $data;
		}
		unset( $data['sections']['faq'] );
		$data['sections']['faq'] = '';
		foreach ( $data['faq'] as $question => $answer ) {
			$data['sections']['faq'] .= "<h4>{$question}</h4>\n{$answer}\n";
		}

		return $data;
	}

	/**
	 * Converts wp.org readme section items to h4 style.
	 *
	 * @param string $section Readme section.
	 * @param array  $data Array of parsed readme data.
	 *
	 * @return array $data
	 */
	public function readme_section_as_h4( $section, $data ) {
		if ( empty( $data['sections'][ $section ] ) || false !== strpos( $data['sections'][ $section ], '<h4>' ) ) {
			return $data;
		}
		$pattern = '~<p>=(.*)=</p>~';
		$replace = '<h4>$1</h4>';

		$data['sections'][ $section ] = preg_replace( $pattern, $replace, $data['sections'][ $section ] );

		return $data;
	}

	/**
	 * Replace parent method as some users don't have `mb_strrpos()`.
	 *
	 * @access protected
	 *
	 * @param string $desc
	 * @param int    $length
	 *
	 * @return string
	 */
	protected function trim_length( $desc, $length = 150 ) {
		if ( mb_strlen( $desc ) > $length ) {
			$desc = mb_substr( $desc, 0, $length ) . ' &hellip;';

			// If not a full sentence, and one ends within 20% of the end, trim it to that.
			if ( function_exists( 'mb_strrpos' ) ) {
				$pos = mb_strrpos( $desc, '.' );
			} else {
				$pos = strrpos( $desc, '.' );
			}
			if ( $pos > ( 0.8 * $length ) && '.' !== mb_substr( $desc, - 1 ) ) {
				$desc = mb_substr( $desc, 0, $pos + 1 );
			}
		}

		return trim( $desc );
	}

}
