<?php

namespace SOLO\Gallery\Photo;

/**
 * @property string $VERSION Version
 * @property string $NAME    Name
 * @property string $FILE    File Path
 */
class Loader {
	private static $instance = null;
	private $required_plugin = 'gutenberg/gutenberg.php';
	private $required_plugin_path = 'gutenberg/gutenberg.php';
	private $required_plugin_name = 'Gutenberg';
	private $required_plugin_version = '4.1';
	public $VERSION = '1.0';
	private $NAME = '';
	private $FILE = 'solo-blocks-photo-gallery.php';

	public static function instance(){
		if(!self::$instance instanceof self) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct(){
		$this->actions();

		$this->FILE                 = dirname(__FILE__).'/'.$this->FILE;
		$this->required_plugin_path = plugin_dir_path(__DIR__).$this->required_plugin;

		if(!function_exists('get_plugin_data')) {
			require_once ABSPATH.'wp-admin/includes/plugin.php';
		}
		$plugin_info   = get_plugin_data($this->FILE);
		$this->VERSION = $plugin_info['Version'];
		$this->NAME    = $plugin_info['Name'];
		define('SOLO_GALLERY_VERSION', $plugin_info['Version']);
	}

	function _is_gutenberg_installed(){
		$file_path         = 'gutenberg/gutenberg.php';
		$installed_plugins = get_plugins();

		return isset($installed_plugins[$file_path]);
	}

	private function actions(){
		add_action('plugins_loaded', array( $this, 'plugins_loaded' ));
	}

	function required_plugin_installed(){
		$installed_plugins = get_plugins();

		return isset($installed_plugins[$this->required_plugin]);
	}

	public function plugins_loaded(){
		load_plugin_textdomain('solo-gallery-photo-textdomain', false, dirname(plugin_basename(__FILE__)).'/languages/');

		if(!function_exists('register_block_type')) {
			add_action('admin_notices', array( $this, 'required_plugin_not_loaded' ));

			return;
		}
		if(!function_exists('get_plugin_data')) {
			require_once(ABSPATH.'wp-admin/includes/plugin.php');
		}
		$version = get_bloginfo('version');

		if ($this->_is_gutenberg_installed()) {
			$gutenberg_version = get_plugin_data($this->required_plugin_path);

			if(!version_compare($gutenberg_version['Version'], $this->required_plugin_version, '>=')) {
				add_action('admin_notices', array( $this, 'required_plugin_wrong_version' ));
			} else {
				require_once __DIR__.'/core/init.php';
			}

		} else if (version_compare($version, '5.0', '>=')) {
			require_once __DIR__.'/core/init.php';
		}
	}

	function required_plugin_not_loaded(){
		$screen = get_current_screen();
		if(isset($screen->parent_file) && 'plugins.php' === $screen->parent_file && 'update' === $screen->id) {
			return;
		}

		if($this->required_plugin_installed()) {
			if(!current_user_can('activate_plugins')) {
				return;
			}

			$activation_url = wp_nonce_url('plugins.php?action=activate&amp;plugin='.$this->required_plugin.'&amp;plugin_status=all&amp;paged=1&amp;s', 'activate-plugin_'.$this->required_plugin);

			$message = '<p>'.sprintf(esc_html__('%1$s is not working because you need to activate the %2$s plugin.', 'solo-gallery-photo-textdomain'), $this->NAME, $this->required_plugin_name).'</p>';
			$message .= sprintf('<p><a href="%s" class="button-primary">%s</a></p>', $activation_url, sprintf(esc_html__('Activate %1$s Now', 'solo-gallery-photo-textdomain'), $this->required_plugin_name));
		} else {
			if(!current_user_can('install_plugins')) {
				return;
			}

			$install_url = wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=gutenberg'), 'install-plugin_gutenberg');

			$message = '<p>'.sprintf(esc_html__('%1$s is not working because you need to install the %2$s plugin.', 'solo-gallery-photo-textdomain'), $this->NAME, $this->required_plugin_name).'</p>';
			$message .= '<p>'.sprintf('<a href="%s" class="button-primary">%s</a>', $install_url, sprintf(esc_html__('Install %1$s Now', 'solo-gallery-photo-textdomain'), $this->required_plugin_name)).'</p>';
		}

		echo '<div class="error"><p>'.$message.'</p></div>';
	}

	function required_plugin_wrong_version(){
		$msg = sprintf(esc_html__('%1$s requires an update of the %2$s version up to %3$s to work properly.', 'solo-gallery-photo-textdomain'), $this->NAME, $this->required_plugin_name, $this->required_plugin_version);
		echo '<div class="error"><p>'.$msg.'</p></div>';
	}

	public function __get($name){
		return property_exists($this, $name) ? $this->$name : null;
	}

	public function __set($name, $value){
	}

	public function __clone(){
		// Cloning instances of the class is forbidden.
		_doing_it_wrong(__FUNCTION__, esc_html__('Cheatin&#8217; huh?', 'solo-gallery-photo-textdomain'), $this->VERSION);
	}

	public function __wakeup(){
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong(__FUNCTION__, esc_html__('Cheatin&#8217; huh?', 'solo-gallery-photo-textdomain'), $this->VERSION);
	}
}

Loader::instance();
