<?php

namespace WOAP;

use WOAP\Packages\ServiceContainer;
use WOAP\Packages\ApiProvider;
use WOAP\Packages\Router;

defined('ABSPATH') || exit;

class Init {
    
    const first_flush_option = "first_flush_permalinks";

    public $version;
    public $web_slug;
    public $api_slug;
	public $route;
	public $service_container;

    public function __construct($version, $web_slug, $api_slug) {
        $this->version = $version;
        $this->web_slug = $web_slug;
        $this->api_slug = $api_slug;
		add_action('plugins_loaded', array($this, 'add_text_domain'));
        add_action("admin_notices", array($this, "first_flush_notice"));
        add_action("update_option_permalink_structure", function() {
            update_option(self::first_flush_option, true);
        });
		$this->boot_modules();
		$this->service_container = $this->boot_services();
		$this->route_init($this->service_container);
	}

    public function add_text_domain() {
        load_plugin_textdomain($this->web_slug, FALSE, WOAP_PRT . "/languages");
    }

    public function boot_modules() {
		$modules = [
			trailingslashit(__DIR__) . 'Packages/LastException.php',
			trailingslashit(__DIR__) . 'Packages/RechargableProduct.php',
			trailingslashit(__DIR__) . 'Packages/QueryBuilder.php',
			trailingslashit(__DIR__) . 'Packages/Repository.php',
			trailingslashit(__DIR__) . 'Packages/Auth.php',
			trailingslashit(__DIR__) . 'Packages/Request.php',
			trailingslashit(__DIR__) . 'Packages/Model.php',
			trailingslashit(__DIR__) . 'Packages/Controller.php',
			trailingslashit(__DIR__) . 'Packages/JDF.php',
			trailingslashit(__DIR__) . 'Packages/Date.php',
			trailingslashit(__DIR__) . 'Packages/Session.php',
			trailingslashit(__DIR__) . 'Packages/PDF.php',
		];
		foreach(apply_filters('wore_modules',$modules) as $module){
			require_once $module;
		}	
    }


    public function first_flush_notice() {
        if (get_option(self::first_flush_option)) {
            return;
        }
        ?>
        <div class="error">
            <p>
                <?php esc_html_e("To make the api-boilerplate plugin worked Please first "); ?>
                <a href="<?php echo get_admin_url(); ?>/options-permalink.php" title="<?php esc_attr_e("Permalink Settings") ?>" >
                    <?php esc_html_e("Flush rewrite rules"); ?>
                </a>
            </p>
        </div>
        <?php
    }

	public function boot_services(){
		require_once trailingslashit(__DIR__) . 'Packages/ServiceContainer.php';
		$services = [
			#services here
		];
		return new ServiceContainer(
			apply_filters('wore_services',$services)
		);
	}
	
	public function route_init($service_container=null){
		require_once trailingslashit(__DIR__) . 'Packages/Router.php';
		$serviec_container = $service_container ?: $this->service_container;	
		$route_mapping = [
			#register Rotes
		];
		$this->router = new Router($service_container,$route_mapping);
	}

}
