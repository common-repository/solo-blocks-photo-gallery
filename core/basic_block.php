<?php

namespace SOLO\Gallery\Photo;

defined('ABSPATH') OR exit;


abstract class Basic {
	protected $style = array();
	protected $responsive_style = array();
	protected $scripts = array();
	protected $styles = array();
	protected $attributes = array();
	protected $_id = array();
	protected $WRAP = array();
	protected $style_print = true;
	protected $wrapper_classes = array();
	protected $_render_attributes = array();

	protected $default_attributes = array(
		// Basic
		'align' => array(
			'type'    => 'string',
			'default' => '',
		),
		'blockAlignment' => array(
			'type'    => 'string',
			'default' => '',
		),
		'textAlignment'  => array(
			'type'    => 'string',
			'default' => '',
		),
		'uid'            => array(
			'type' => 'string',
			'default' => '',
		),
		'blockName'      => array(
			'type' => 'string',
			'default' => '',
		),
		'className'      => array(
			'type'    => 'string',
			'default' => '',
		),
		'blockAnimation' => array(
			'type'    => 'object',
			'default' => array(
				'type'     => '',
				'speed'    => 'normal',
				'delay'    => 0,
				'infinite' => false,
			),
		)
	);
	protected $render_index = 1;
	protected $slug = 'solo/basic';

	public function __construct(){
		add_action('init', function(){
			if(is_array($this->slug) && count($this->slug)) {
				foreach($this->slug as $module => $render) {
					if(!is_string($module)) {
						$module = $render;
						$render = 'render_block';
					}
					register_block_type($module, array(
						'attributes'      => array_merge($this->default_attributes, $this->attributes),
						'render_callback' => array( $this, $render ),
					));
				}
			} else {
				register_block_type('solo-gallery-photo/'.$this->slug, array(
					'attributes'      => array_merge($this->default_attributes, $this->attributes),
					'render_callback' => array( $this, 'render_block' ),
				));
			}
		});

		$this->construct();
	}

	protected function construct(){
	}

	protected function ajaxCheckValue(&$value, $type = 'bool'){
		switch($type) {
			case 'bool':
				if(isset($value) && ($value === true || strtolower($value) == 'true')) {
					$value = true;
				} else {
					$value = false;
				}
				break;
			case 'array':
				if(!isset($value) || !is_array($value)) {
					$value = array();
				}
				break;
			default:
				break;
		}

	}

	public function camelToUnderscore($string, $us = "-"){
		$patterns = [
			'/([a-z]+)([0-9]+)/i',
			'/([a-z]+)([A-Z]+)/',
			'/([0-9]+)([a-z]+)/i'
		];
		$string   = preg_replace($patterns, '$1'.$us.'$2', $string);

		// Lowercase
		$string = strtolower($string);

		return $string;
	}

	protected function get_styles($with_tags = true, $getResponsiveStyle = true){
		$style = '';
		if(is_array($this->style) && count($this->style)) {
			foreach($this->style as $selector => $_styles) {
				if(is_array($_styles) && count($_styles)) {
					$_style = '';
					foreach($_styles as $styleName => $value) {
						if(!empty($value) || (is_numeric($value))) {
//							if (!is_array($value)) $value = array($value);
							if(substr($styleName, -1, 1) !== ';') {
								$styleName .= ';';
							}
							$_style .= "\t".sprintf($this->camelToUnderscore($styleName), $value).PHP_EOL;
						}
					}
					if(!empty($_style)) {
						$style .= $this->WRAP.' '.$selector.' {'.PHP_EOL.$_style.'}'.PHP_EOL;
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
	protected function add_style($selector, $value = null){
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

	protected function get_responsive_styles(){
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

	protected function add_responsive_style($maxWidth, $selector, $value = null){
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
	protected function add_responsive_block($selector, $style, $block){
		if(gettype($selector) == 'string') {
			$selector = array( $selector );
		}
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

	protected function add_script_depends($slug){
		if(is_array($slug) && count($slug)) {
			foreach($slug as $script) {
				$this->scripts[] = $script;
			}
		} else {
			$this->scripts[] = $slug;
		}
	}

	protected function add_style_depends($slug){
		if(is_array($slug) && count($slug)) {
			foreach($slug as $styles) {
				$this->styles[] = $styles;
			}
		} else {
			$this->styles[] = $slug;
		}
	}

	protected function enqueue_scripts(){
		if(is_array($this->scripts) && count($this->scripts)) {
			foreach($this->scripts as $script) {
				wp_enqueue_script($script);
			}
		}
	}

	/**
	 * @param $settings
	 *
	 * @return string
	 */
	public function render_block($settings){
		$this->render_index       = 1;
		$this->style              = array();
		$this->responsive_style   = array();
		$this->wrapper_classes    = array();
		$this->_render_attributes = array();

		$this->enqueue_scripts();
		$this->_render_attributes = array();

		$this->_id       = 'uid-'.substr(md5($settings['uid'].mt_rand(10000, 99999)), 0, 16);
		$this->WRAP      = esc_html('#'.$this->_id.' ');
		$wrapper_classes = array(
			'gutenberg-solo-gallery-photo--wrapper',
			'gutenberg-solo-gallery-photo--'.(str_replace('_', '-', $settings['blockName'])),
			$settings['className'],
		);

		$attr = array(
			'id'              => $this->_id,
			'data-solo-gallery-block' => $settings['blockName']
		);

		$settings['blockAlignment'] = isset($settings['align']) && !empty($settings['align']) ? $settings['align'] : $settings['blockAlignment'];
		if(!empty($settings['blockAlignment'])) {
			$wrapper_classes[] = 'align'.$settings['blockAlignment'];
		}

		if(!empty($settings['blockAnimation']) && is_array($settings['blockAnimation']) && key_exists('type', $settings['blockAnimation']) && !empty($settings['blockAnimation']['type'])) {
			$wrapper_classes[]      = 'animated';
			$attr['data-animation'] = $settings['blockAnimation']['type'];
			if(key_exists('infinite', $settings['blockAnimation']) && (bool) $settings['blockAnimation']['infinite']) {
				$wrapper_classes[] = 'infinite';
			}
			if(key_exists('speed', $settings['blockAnimation']) && $settings['blockAnimation']['speed'] !== 'normal') {
				$wrapper_classes[] = $settings['blockAnimation']['speed'];
			}
			if(key_exists('delay', $settings['blockAnimation']) && $settings['blockAnimation']['delay'] > 0) {
				$wrapper_classes[] = sprintf('delay-%ss', (int) $settings['blockAnimation']['delay']);
			}
		}

		$settings['uid']  = $this->_id;
		$settings['WRAP'] = $this->WRAP;

		ob_start();
		$this->render($settings);
		$content = ob_get_clean();

		$styles = '';
		if($this->style_print) {
			$styles = $this->get_styles();
		}

		if(is_array($attr) && count($attr)) {
			$attr = implode(' ', array_map(function($key, $value){
				if(is_array($value)) {
					$value = implode(' ', $value);
				}

				return $key.'="'.esc_attr($value).'"';
			}, array_keys($attr), $attr));
		} else if(!is_string($attr)) {
			$attr = '';
		}

		$wrapper_classes = array_merge($wrapper_classes, $this->wrapper_classes);

		$wrapper_classes = implode(' ', $wrapper_classes);

		return $styles.'<div class="'.esc_attr($wrapper_classes).'" '.$attr.'>'.$content.'</div>';
	}

	protected function render($settings){
	}

	public function add_render_attribute($element, $key = null, $value = null, $overwrite = false){
		if(is_array($element)) {
			foreach($element as $element_key => $attributes) {
				$this->add_render_attribute($element_key, $attributes, null, $overwrite);
			}

			return $this;
		}

		if(is_array($key)) {
			foreach($key as $attribute_key => $attributes) {
				$this->add_render_attribute($element, $attribute_key, $attributes, $overwrite);
			}

			return $this;
		}

		if(empty($this->_render_attributes[$element][$key])) {
			$this->_render_attributes[$element][$key] = [];
		}

		settype($value, 'array');

		if($overwrite) {
			$this->_render_attributes[$element][$key] = $value;
		} else {
			$this->_render_attributes[$element][$key] = array_merge($this->_render_attributes[$element][$key], $value);
		}

		return $this;
	}

	public function set_render_attribute($element, $key = null, $value = null){
		return $this->add_render_attribute($element, $key, $value, true);
	}

	public function get_render_attribute_string($element){
		if(empty($this->_render_attributes[$element])) {
			return '';
		}

		$render_attributes = $this->_render_attributes[$element];

		$attributes = [];

		foreach($render_attributes as $attribute_key => $attribute_values) {
			$attributes[] = sprintf('%1$s="%2$s"', $attribute_key, esc_attr(implode(' ', $attribute_values)));
		}

		return implode(' ', $attributes);
	}

	public function print_render_attribute_string($element){
		echo $this->get_render_attribute_string($element);
	}
}


