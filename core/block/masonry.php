<?php

namespace SOLO\Gallery\Blocks;

defined('ABSPATH') OR exit;

use SOLO\Gallery\Photo\Basic;

class Masonry extends Basic {

	protected $attributes = array(
		'backgroundColor' => array(
			'type'    => 'string',
			'default' => '',
		),
		'imageSize'       => array(
			'type'    => 'string',
			'default' => 'large',
		),
		'images'          => array(
			'type'    => 'string',
			'default' => '',
		),
		'images_count'    => array(
			'type'    => 'string',
			'default' => '12',
		),
		'cols'            => array(
			'type'    => 'string',
			'default' => '4',
		),
		'grid_gap'        => array(
			'type' => 'object',
		),
		'hover'           => array(
			'type'    => 'string',
			'default' => 'hover1',
		),
		'lightbox'        => array(
			'type'    => 'bool',
			'default' => false,
		),
		'show_title'      => array(
			'type'    => 'bool',
			'default' => false,
		),
		'load_more'       => array(
			'type'    => 'bool',
			'default' => false,
		),
		'load_more_count' => array(
			'type'    => 'string',
			'default' => '4',
		),
	);
	protected $slug = 'masonry';

	protected function construct(){
		$this->attributes['load_more_title'] = array(
			'type'    => 'string',
			'default' => __('Load More', 'solo-gallery-photo-textdomain'),
		);

		$this->add_script_depends('imageloaded');
		$this->add_script_depends('isotope');
		add_action('wp_ajax_gt3_masonry_load_images', array( $this, 'ajax_handler' ));
		add_action('wp_ajax_nopriv_gt3_masonry_load_images', array( $this, 'ajax_handler' ));
	}

	public function ajax_handler(){
		header('Content-Type: application/json');

		$respond = '';
		$this->ajaxCheckValue($_POST['lightbox']);
		$this->ajaxCheckValue($_POST['show_title']);

		$gallery_items = array();

		foreach($_POST['images'] as $image) {
			$image = wp_prepare_attachment_for_js($image);
			if($_POST['lightbox']) {
				$gallery_items[] = array(
					'href'        => $image['url'],
					'title'       => $image['title'],
					'thumbnail'   => $image['sizes']['thumbnail']['url'],
					'description' => $image['caption'],
					'is_video'    => 0,
					'image_id'    => $image['id'],
				);
			}
			$respond .= $this->renderItem($image, $_POST);
		}

		die(wp_json_encode(array(
			'post_count'    => count($_POST['images']),
			'respond'       => $respond,
			'gallery_items' => $gallery_items,
		)));
	}

	private function renderItem($image, $settings){
		$item_class = '';

		$render = '';
		$render .= '<div class="isotope_item loading '.$item_class.'"><div class="isotope_item-wrapper">';
		if((bool) $settings['lightbox']) {
			$render .= '<a href="'.esc_url($image['url']).'" class="lightbox">';
		}
		$render .= '<div class="img_wrap"><div class="img">';
		$render .= wp_get_attachment_image($image['id'], $settings['imageSize']);
		$render .= '</div></div>';
		if((bool) $settings['show_title'] && !empty($image['title'])) {
			$render .= '<div class="text_wrap">';
			$render .= '<h4 class="title">'.esc_html($image['title']).'</h4>';
			$render .= '</div>';
		}

		if((bool) $settings['lightbox']) {
			$render .= '</a>';
		}
		$render .= '</div></div>';

		return $render;
	}


	protected function render($settings){
		$this->wrapper_classes = array( 'gutenberg-solo-gallery-photo--isotope_gallery' );

		if(!$settings['show_title'] && !$settings['lightbox']) {
			$settings['hover'] = 'hover_none';
		}

		$settings['images'] = explode(',', $settings['images']);

		if(isset($settings['images']) && is_array($settings['images']) && count($settings['images'])) {
			$this->add_responsive_block(array( '.solo_isotope_wrapper' ), 'margin-right:-%1$spx; margin-bottom:-%1$spx;', $settings['grid_gap']);
			$this->add_responsive_block(array( '.isotope_item' ), 'padding-right: %1$spx; padding-bottom: %1$spx;', $settings['grid_gap']);
			$this->add_style('.isotope_background_wrapper', array(
				'backgroundColor: %s' => $settings['backgroundColor'],
			));
			$uid      = mt_rand(300, 1000);
			$lightbox = (bool) ($settings['lightbox']) ? true : false;
			$this->add_render_attribute('wrapper', 'class', array(
				'isotope_gallery_wrapper',
				esc_attr('items'.$settings['cols']),
				esc_attr($settings['hover']),
				'gallery-masonry',
			));
			$load_more_images = array_slice($settings['images'], $settings['images_count']);

			$content            = '';
			$lightbox_array     = array();
			$settings['images'] = array_slice($settings['images'], 0, $settings['images_count']);
			foreach($settings['images'] as $image) {
				$image = wp_prepare_attachment_for_js($image);
				if($lightbox) {
					$lightbox_array[] = array(
						'href'        => $image['url'],
						'title'       => $image['title'],
						'thumbnail'   => $image['sizes']['thumbnail']['url'],
						'description' => $image['caption'],
						'is_video'    => 0,
						'image_id'    => $image['id'],
					);
				}
				$content .= $this->renderItem($image, $settings);
			}

			$this->add_render_attribute('wrapper', 'data-images', wp_json_encode($load_more_images));
			$this->add_render_attribute('wrapper', 'data-settings', wp_json_encode(array(
				'cols'           => $settings['cols'],
				'lightbox'       => $lightbox,
				'show_title'     => (bool) ($settings['show_title']),
				'load_more'      => $settings['load_more'],
				'load_items'     => $settings['load_more_count'],
				'imageSize'      => $settings['imageSize'],
				'uid'            => $uid,
				'lightbox_array' => $lightbox_array,
			)));
			?>
			<div <?php $this->print_render_attribute_string('wrapper') ?>>
				<div class="isotope_background_wrapper">
					<div class="solo_isotope_wrapper " data-cols="<?php echo esc_attr($settings['cols']) ?>">
						<?php
						echo $content; // XSS OK
						?>
					</div>
				</div>
				<?php
				if((bool) ($settings['load_more']) && count($load_more_images)) {
					if(empty($settings['load_more_title'])) {
						$settings['load_more_title'] = esc_html__('Load More', 'solo-gallery-photo-textdomain');
					}

					$this->add_render_attribute('view_more_button', 'href', 'javascript:void(0)');
					$this->add_render_attribute('view_more_button', 'class', 'view_more_link');

					if(!empty($settings['load_more_title'])) {
						$this->add_render_attribute('view_more_button', 'title', esc_attr($settings['load_more_title']));
					}
					echo '<a '.$this->get_render_attribute_string('view_more_button').'>'.esc_html($settings['load_more_title']).'<div '.$this->get_render_attribute_string('button_icon').'></div></a>';
				} // End button
				?>
			</div>
			<?php
		}
	}

}

new Masonry();

