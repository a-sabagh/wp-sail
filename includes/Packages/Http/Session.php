<?php

namespace SAIL\Packages\Http;

use SAIL\Resources\SessionResource;
use SAIL\Packages\Utils\Arr;

class Session {

	private $client_identifier;
	private $request_identifier;
	private $user_agent;
	private $ip_address;
	private $session;
	private $payload = [];

	public function __construct(
		$user_agent,
		string $ip_address = null,
		string $client_identifier,
		string $request_identifier = null
	){
		$this->user_agent = $user_agent;
		$this->client_identifier = $client_identifier ?: 'guest';
		$this->ip_address = $ip_address;
		$this->request_identifier = $request_identifier;
		$resource = new SessionResource;
		$this->resource = $resource->find($client_identifier);
		if(false == $this->resource instanceof SessionResource){
			$resource->set_data(['identifier' => $this->client_identifier]);
			$this->resource = $resource;
		}
		$this->payload = $this->resource->get_payload();
		add_action('sail_application_shutdown',[$this,'save']);
	}


    /**
     * Age the flash data for the session.
     *
     * @return void
     */
	protected function age_flash_data(){
        $this->forget( $this->get('_flash.old', []) );
        $this->put('_flash.old', $this->get('_flash.new', []));
        $this->put('_flash.new', []);
	}
	
	/**
	 * Request Identifier getter
	 *
	 * @return SAIL\Package\Session
	 */
	public function get_request_identifier(){
		return $this->request_identifier;	
	}

	/**
	 * Client Identifier setter
	 *
	 * @return SAIL\Package\Session
	 */
	public function set_client_identifier(string $identifier){
		$this->client_identifier = $identifier;
		return $this;
	}

	/**
	 * User id setter
	 *
	 * @return SAIL\Package\Session
	 */
	public function set_user_id(int $user_id){
		$this->user_id = $user_id;
		return $this;
	}

   /**
     * Remove the given keys from the old flash data.
     *
     * @param  array  $keys
     * @return void
     */
    protected function remove_from_old_flash_data(array $keys){
        $this->put('_flash.old', array_diff($this->get('_flash.old', []), $keys));
    }

    /**
     * Get an item from the session.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get($key, $default = null){
        return Arr::get($this->payload, $key, $default);
    }

    /**
     * Get the value of a given key and then forget it.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
	public function pull($key,$default = null){
		$value = $this->get($key,$default); 
		$this->forget([$key]);
		return $value;
	}

    /**
     * Get all of the session data.
     *
     * @return array
     */
	public function all(){
		return $this->payload;
	}
    /**
     * Get a subset of the session data.
     *
     * @param  array  $keys
     * @return array
     */
    public function only(array $keys){
        return Arr::only($this->payload, $keys);
    }

    /**
     * Get all the session data except for a specified array of items.
     *
     * @param  array  $keys
     * @return array
     */
    public function except(array $keys){
        return Arr::except($this->payload, $keys);
    }

    /**
     * Checks if a key exists.
     *
     * @param  string  $key
     * @return bool
     */
	public function exists($key){
		return $this->get($key, false);
	}

    /**
     * Determine if the given key is missing from the session data.
     *
     * @param  string $key
     * @return bool
     */
	public function missing($key){
		return ! $this->get($key, false);
	}


    /**
     * Put a key / value pair or array of key / value pairs in the session.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return void
     */
    public function put($key, $value = null){
        if (! is_array($key)) {
            $key = [$key => $value];
        }
        foreach ($key as $array_key => $array_value) {
            Arr::set($this->payload, $array_key, $array_value);
        }
    }

    /**
     * Push a value onto a session array.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
	public function push($key,$value){
        $array = $this->get($key, []);
        $array[] = $value;
        $this->put($key, $array);
	}

    /**
     * Increment the value of an item in the session.
     *
     * @param  string  $key
     * @param  int  $amount
     * @return mixed
     */
    public function increment($key, $amount = 1){
        $this->put($key, $value = $this->get($key, 0) + $amount);
        return $value;
    }

	/**
     * Decrement the value of an item in the session.
     *
     * @param  string  $key
     * @param  int  $amount
     * @return int
     */
    public function decrement($key, $amount = 1){
        return $this->increment($key, $amount * -1);
    }

    /**
     * Flash a key / value pair to the session.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function flash(string $key, $value = true){
        $this->put($key, $value);
        $this->push('_flash.new', $key);
        $this->remove_from_old_flash_data([$key]);
    }

    /**
     * Flash an input array to the session.
     *
     * @param  array  $value
     * @return void
     */
    public function flash_input(array $value){
        $this->flash('_old_input', $value);
    }

    /**
     * Determine if the session contains old input.
     *
     * @param  string|null  $key
     * @return bool
     */
    public function has_old_input($key = null){
        $old = $this->get_old_input($key);
        return is_null($key) ? count($old) > 0 : ! is_null($old);
    }

    /**
     * Get the requested item from the flashed input array.
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    public function get_old_input($key = null, $default = null){
        return Arr::get($this->get('_old_input', []), $key, $default);
    }

    /**
     * Reflash all of the session flash data.
     *
     * @return void
     */
    public function reflash(){
        $this->mergeNewFlashes( $this->get('_flash.old', [] ) );
        $this->put('_flash.old', []);
    }


    /**
     * Flash a key / value pair to the session for immediate use.
     *
     * @param  string  $key
     * @param  mixed  $value
     * @return void
     */
    public function now($key, $value){
        $this->put($key, $value);
        $this->push('_flash.old', $key);
    }

    /**
     * Remove one or many items from the session.
     *
     * @param  string|array  $keys
     * @return void
     */
    public function forget($keys){
        Arr::forget($this->payload, $keys);
    }

    /**
     * Remove all of the items from the session.
     *
     * @return void
     */
	public function flush(){
		$this->payload = [];
	}

	public function save(){
		$this->age_flash_data();
		$this->resource->set_data([
			'identifier' => $this->client_identifier,
			'user_id' => get_current_user_id(),
			'updated_at'=>time(),
			'user_agent' => $this->user_agent,
			'ip_address' => $this->ip_address
		])->set_payload($this->payload)->save();
	}

}
