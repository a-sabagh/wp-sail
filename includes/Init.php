<?php

namespace SAIL;

use SAIL\Packages\Providers\RouterProvider;
use SAIL\Packages\Singleton\ServiceContainer;


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
		add_action('init', [$this,'boot_services']);
		add_action('sail_route_init', [$this,'route_init']);
	}

    public function add_text_domain() {
        load_plugin_textdomain($this->web_slug, FALSE, SAIL_PRT . "/languages");
    }

    public function boot_modules() {
		$modules = [
            trailingslashit(__DIR__) . 'Requirements/vendor/autoload.php',
			trailingslashit(__DIR__) . 'Packages/Database/QueryBuilder.php',
            trailingslashit(__DIR__) . 'Packages/Database/Model.php',
			trailingslashit(__DIR__) . 'Packages/Singleton/ServiceContainer.php',
            trailingslashit(__DIR__) . 'Packages/Singleton/Repository.php',
			trailingslashit(__DIR__) . 'Packages/Providers/RouterProvider.php',
			trailingslashit(__DIR__) . 'Packages/Http/Validator.php',
			trailingslashit(__DIR__) . 'Packages/Http/Request.php',
			trailingslashit(__DIR__) . 'Packages/Http/RedirectResponse.php',
			trailingslashit(__DIR__) . 'Packages/Http/Session.php',
			trailingslashit(__DIR__) . 'Packages/Contracts/Controller.php',
			trailingslashit(__DIR__) . 'Packages/Contracts/Component.php',
			trailingslashit(__DIR__) . 'Packages/Utils/LastException.php',
			trailingslashit(__DIR__) . 'Packages/Utils/RechargableProduct.php',
			trailingslashit(__DIR__) . 'Packages/Utils/MessageBag.php',
            trailingslashit(__DIR__) . 'Packages/Utils/JDF.php',
			trailingslashit(__DIR__) . 'Packages/Utils/Date.php',
			trailingslashit(__DIR__) . 'Packages/Utils/Arr.php',
			trailingslashit(__DIR__) . 'Packages/Utils/PDF.php',
			trailingslashit(__DIR__) . 'Traits/Setting.php',
			trailingslashit(__DIR__) . 'Traits/Timestamp.php',
			trailingslashit(__DIR__) . 'Traits/Post.php',
			trailingslashit(__DIR__) . 'Traits/Term.php',
			trailingslashit(__DIR__) . 'Models/Session.php',
			trailingslashit(__DIR__) . 'Resources/SessionResource.php',
		];
		foreach(apply_filters('sail_modules',$modules) as $module){
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
                <?php esc_html_e("To make the wp sail plugin boilerplate worked right, Please first "); ?>
                <a href="<?php echo get_admin_url(); ?>/options-permalink.php" title="<?php esc_attr_e("Permalink Settings") ?>" >
                    <?php esc_html_e("Flush rewrite rules"); ?>
                </a>
            </p>
        </div>
        <?php
    }

	public function boot_services(){
		$services = [
			Services\Logic\AttachmentLogic::class => trailingslashit(__DIR__) . 'Services/Logic/AttachmentLogic.php',
			Services\Controller\Api\CryptoCompaire::class => trailingslashit(__DIR__) . 'Services/Controller/Api/CryptoCompaire.php',
		];
		$this->service_container = new ServiceContainer(apply_filters('sail_services',$services));
		do_action('sail_route_init',$this->service_container);
	}

	public function route_init($service_container){
		$serviec_container = $service_container ?: $this->service_container;	
		$this->router = new RouterProvider($service_container, [
			'SAILApi' => [
				'namespace' => 'SAIL\Services\Controller\Api',
				'type' => 'api',
			]
		]);
	}

}
