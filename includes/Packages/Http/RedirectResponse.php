<?php

namespace SAIL\Packages\Http;

class RedirectResponse {

    /**
     * The request instance.
     *
     * @var \SAIL\Packages\Request
     */
    protected $request;

    /**
     * The session store instance.
     *
     * @var SAIL\Packages\Session
     */
    protected $session;


	public function __construct(Request $request, Session $session){
		$this->session = $session;
		$this->request = $request;
	}

    /**
     * Flash a piece of data to the session.
     *
     * @param  string|array  $key
     * @param  mixed  $value
     * @return $this
     */
    public function with($key, $value = null){
        $key = is_array($key) ? $key : [$key => $value];
        foreach ($key as $k => $v) {
            $this->session->flash($k, $v);
        }
        return $this;
    }

    /**
     * Flash an array of input to the session.
     *
     * @param  array|null  $input
     * @return $this
     */
    public function with_input(array $input = null){
        $this->session->flash_input(
            ! is_null($input) ? $input : $this->request->all()
        );
        return $this;
    }

    /**
     * Get the request instance.
     *
     * @return \SAIL\Packages\Request
     */
    public function get_request(){
        return $this->request;
    }

    /**
     * Set the request instance.
     *
     * @param  \SAIL\Packages\Request  $request
     * @return void
     */
    public function set_request(Request $request){
        $this->request = $request;
    }

    /**
     * Get the session store instance.
     *
     * @return  \SAIL\Packages\Session  $session
     */
    public function get_session(){
        return $this->session;
    }

    /**
     * Set the session store instance.
     *
     * @param  \SAIL\Packages\Session  $session
     * @return void
     */
    public function set_session(Session $session){
        $this->session = $session;
    }

	public function to(string $url, array $query_args = [], int $code = 303){
		$this->session->save();
		$redirect_url = add_query_arg($query_args,$url);
		wp_redirect($redirect_url,$code);
		die();
	}

	public function to_route(string $endpoint, string $module, string $action, array $query_args = [],int $code = 303){
		$redirect_url = sail_get_request_uri($endpoint, $module, $action, $query_args);
		$this->to( $redirect_url );
	}

	public function to_action(string $action, array $query_args = [], int $code = 303){
		global $endpoint,$module;
		$this->to_route($endpoint, $module, $action, $query_args, $code);
	}

}
