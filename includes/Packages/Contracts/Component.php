<?php

namespace SAIL\Packages\Contracts;

use Exception;

class Component extends Controller {

	public $path;
	public $slug;
	public $endpoint;
	public $accepted_args;

	protected $configuration = [
		'slug' => '',
		'endpoint' => '',
		'accepted_args' => 0,
	];

	public function __construct($service_container){
		parent::__construct($service_container);
		add_action("x_component_{$this->configuration['endpoint']}_{$this->configuration['slug']}", [$this, 'configuration_exception_handling'], 10);
		add_action("x_component_{$this->configuration['endpoint']}_{$this->configuration['slug']}", [$this, 'initialize_dynamic_properties'], 20);
		add_action("x_component_{$this->configuration['endpoint']}_{$this->configuration['slug']}", [$this, 'template_exception_handling'], 30);
		add_action("x_component_{$this->configuration['endpoint']}_{$this->configuration['slug']}", [$this, 'render'], 40, $this->configuration['accepted_args']);
	}

	public function template_exception_handling(){
		$component_path = $this->get_response_view($this->endpoint,$this->path);
		if( !file_exists($component_path) ){
			throw new Exception(
				sprintf(
					__('component %s template combination configuration invalid: %s', 'SAIL'),
					__CLASS__,
					$component_path
				)
			);
		}
	}

	public function configuration_exception_handling(){
		foreach($this->configuration as $key => $value){
			if( 0 == strlen($value) ){
				throw new Exception(
					sprintf(
						__('configuration %s invalid! because of empty value for %s key', 'SAIL'),
						__CLASS__,
						$key
					)
				);
			}
		}
	}

	public function initialize_dynamic_properties(){
		[
			'slug' => $slug,
			'endpoint' => $endpoint,
			'accepted_args' => $accepted_args,
		] = $this->configuration;
		$this->path = "components.{$slug}";
		$this->slug = $slug;
		$this->endpoint = $endpoint;
		$this->accepted_args = $accepted_args;
	}

}
