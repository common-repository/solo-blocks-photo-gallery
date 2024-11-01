<?php

namespace SOLO\Gutenberg\Gallery\Photo;

defined('ABSPATH') OR exit;


class Assets {
	private static $instance = null;

	public static function instance(){
		if(!self::$instance instanceof self) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private $style = array();
	private $responsive_style = array();

	private function __construct(){
		add_action('enqueue_block_assets', array( $this, 'enqueue_block_assets' ));
		add_action('enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ));

		add_action('wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ));
		add_action('admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ));

		add_filter('block_categories', function($categories, $post){
			array_splice($categories, 3, 0, array(
				array(
					'slug'  => 'solo-gallery-photo',
					'title' => __('Solo Blocks Photo Gallery', 'solo-gallery-photo-textdomain'),
				)
			));

			return $categories;
		}, 10, 2);
	}

	function get_jed_locale_data($domain){
		$translations = get_translations_for_domain($domain);

		$locale = array(
			'' => array(
				'domain' => $domain,
				'lang'   => is_admin() ? get_user_locale() : get_locale(),
			),
		);

		if(!empty($translations->headers['Plural-Forms'])) {
			$locale['']['plural_forms'] = $translations->headers['Plural-Forms'];
		}

		foreach($translations->entries as $msgid => $entry) {
			$locale[$msgid] = $entry->translations;
		}

		return $locale;
	}

	/**
	 * Enqueue Gutenberg block assets for both frontend + backend.
	 */
	function enqueue_block_assets(){
		wp_enqueue_style('wp-blocks');
		if(apply_filters('solo/blocks/gallery/photo/style/enable-style', true)) {
			wp_enqueue_style(
				'solo-blocks-photo-gallery-frontend',
				plugins_url('dist/frontend.css', dirname(__FILE__)),
				array(),
				filemtime(plugin_dir_path(__DIR__).'dist/frontend.css')
			);
		}

		wp_enqueue_script(
			'solo-blocks-photo-gallery-frontend',
			plugins_url('/dist/frontend.js', dirname(__FILE__)),
			array(
				'jquery-ui-tabs',
				'jquery-ui-accordion',
				'wp-i18n',
				'imagesloaded',
				'masonry',
			),
			filemtime(plugin_dir_path(__DIR__).'dist/frontend.js'),
			true
		);

		$locale  = $this->get_jed_locale_data('solo-gallery-photo-textdomain');
		$content = 'wp.i18n.setLocaleData('.json_encode($locale).', "solo-gallery-photo-textdomain" );';
		$content .= 'window.ajaxurl = window.ajaxurl || "'.admin_url('admin-ajax.php').'";';

		wp_script_add_data('solo-blocks-photo-gallery-frontend', 'data', $content);
	}


	/**
	 * Enqueue Gutenberg block assets for backend editor.
	 */
	function enqueue_block_editor_assets(){
		wp_enqueue_media();
		wp_enqueue_script('media-grid');
		wp_enqueue_script('media');
		// Scripts.
		wp_enqueue_script(
			'solo-blocks-photo-gallery-block',
			plugins_url('/dist/editor.js', dirname(__FILE__)),
			array(
				'wp-blocks',
				'wp-i18n',
				'wp-element',
				// Tabs
				'jquery-ui-tabs',
				'jquery-ui-accordion',
			), // Dependencies, defined above.
			filemtime(plugin_dir_path(__DIR__).'dist/editor.js'),
			true
		);

		// Styles.
		wp_enqueue_style(
			'solo-blocks-photo-gallery-editor',
			plugins_url('dist/editor.css', dirname(__FILE__)),
			array( 'wp-edit-blocks' ),
			filemtime(plugin_dir_path(__DIR__).'dist/editor.css')
		);
	}

	function solo_isotope(){
		wp_register_script(
			'isotope',
			plugins_url('/dist/jquery.isotope.min.js', dirname(__FILE__)),
			array( 'jquery' ),
			'3.0.6',
			true
		);
	}


	public function camelToUnderscore($string, $us = "-"){
		$patterns = array(
			'/([a-z]+)([0-9]+)/i',
			'/([a-z]+)([A-Z]+)/',
			'/([0-9]+)([a-z]+)/i'
		);
		$string   = preg_replace($patterns, '$1'.$us.'$2', $string);

		return strtolower($string);
	}

	public function get_styles($with_tags = true, $getResponsiveStyle = true){
		$style = '';
		if(is_array($this->style) && count($this->style)) {
			foreach($this->style as $selector => $_styles) {
				if(is_array($_styles) && count($_styles)) {
					$_style = '';
					foreach($_styles as $styleName => $value) {
						if(!empty($value)) {
//							if (!is_array($value)) $value = array($value);
							if(substr($styleName, -1, 1) !== ';') {
								$styleName .= ';';
							}
							$_style .= "\t".sprintf($this->camelToUnderscore($styleName), $value).PHP_EOL;
						}
					}
					if(!empty($_style)) {
						$style .= $selector.' {'.PHP_EOL.$_style.'}'.PHP_EOL;
					}
				}
			}
		}
		if($getResponsiveStyle) {
			$style .= $this->get_responsive_styles();
		}
		if(!empty($style) && $with_tags) {
			return '<style>'.$style.'</style>';
		}

		return $style;
	}

	/**
	 * @param array|string $selector
	 * @param array|null   $value
	 */
	public function add_style($selector, $value = null){
		$oldStyle = array();
		if(is_array($selector) && count($selector)) {

			foreach($selector as $_selector => $_value) {
				if(is_numeric($_selector)) {
					$_selector = $_value;
					$_value    = $value;
				}
				if(isset($this->style[$_selector])) {
					$oldStyle = $this->style[$_selector];
				} else {
					$oldStyle = array();
				}
				$this->style[$_selector] = array_merge($oldStyle, $_value);
			}
		} else {
			if(isset($this->style[$selector])) {
				$oldStyle = $this->style[$selector];
			} else {
				$oldStyle = array();
			}
			$this->style[$selector] = array_merge($oldStyle, $value);
		}
	}

	public function get_responsive_styles(){
		$style            = '';
		$responsive_style = '';
		if(is_array($this->responsive_style) && count($this->responsive_style)) {
			krsort($this->responsive_style);
			foreach($this->responsive_style as $maxWidth => $_styles) {
				if(is_array($_styles) && count($_styles)) {
					$this->style      = $_styles;
					$responsive_style = $this->get_styles(false, false);
					if(!empty($responsive_style)) {
						$style .= '@media screen and (max-width: '.$maxWidth.'px) {'."\t".PHP_EOL.$responsive_style."\t".PHP_EOL.'}'.PHP_EOL;
					}
				}
			}
		}

		return $style;
	}

	public function add_responsive_style($maxWidth, $selector, $value = null){
		$oldStyle = array();
		if(is_array($selector) && count($selector)) {
			foreach($selector as $_selector => $value) {
				if(isset($this->responsive_style[$maxWidth]) && isset($this->responsive_style[$maxWidth][$_selector])) {
					$oldStyle = $this->responsive_style[$maxWidth][$_selector];
				} else {
					$oldStyle = array();
				}
				$this->responsive_style[$maxWidth][$_selector] = array_merge($oldStyle, $value);
			}
		} else {
			if(isset($this->responsive_style[$maxWidth]) && isset($this->responsive_style[$maxWidth][$selector])) {
				$oldStyle = $this->responsive_style[$maxWidth][$selector];
			} else {
				$oldStyle = array();
			}
			$this->responsive_style[$maxWidth][$selector] = array_merge($oldStyle, $value);
		}
	}

	/**
	 * @param array|string $selector
	 * @param array|string $style
	 * @param array        $block
	 */
	public function add_responsive_block($selector, $style, $block){
		if(is_array($block) && key_exists('default', $block)) {
			if(is_array($selector) && count($selector)) {
				foreach($selector as $_selector) {
					// Default
					if(is_array($style) && count($style)) {
						foreach($style as $_style) {
							$this->add_style($_selector, array( $_style => $block['default'] ));
						}
					} else {
						$this->add_style($_selector, array( $style => $block['default'] ));
					}

					// Responsive
					if(key_exists('responsive', $block)
					   && $block['responsive']
					   && key_exists('data', $block)
					   && is_array($block['data'])
					   && count($block['data'])) {
						foreach($block['data'] as $name => $data) {
							if(is_array($style) && count($style)) {
								foreach($style as $_style) {
									$this->add_responsive_style($data['width'], $_selector, array( $_style => $data['value'] ));
								}
							} else {
								$this->add_responsive_style($data['width'], $_selector, array( $style => $data['value'] ));
							}
						}
					}
				}
			}
		}
	}

	public function wp_enqueue_scripts(){
		$this->solo_isotope();
	}

	public function admin_enqueue_scripts($page){
		$this->solo_isotope();
	}

}

Assets::instance();


