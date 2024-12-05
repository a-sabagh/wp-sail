<?php

namespace SAIL\Packages\Utils;

class MessageBag{

    /**
     * All of the registered messages.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Create a new message bag instance.
     *
     * @param  array  $messages
     * @return void
     */
    public function __construct(array $messages = []){
        foreach ($messages as $key => $value) {
            $this->messages[$key] = array_unique($value);
        }
    }

	 /**
     * Get the keys present in the message bag.
     *
     * @return array
     */
    public function keys(){
        return array_keys($this->messages);
    }

    /**
     * Add a message to the message bag.
     *
     * @param  string  $key
     * @param  string  $message
     * @return $this
     */
    public function add($key, $message){
        if ($this->is_unique($key, $message)) {
            $this->messages[$key][] = $message;
        }
        return $this;
    }

    /**
     * Add a message to the message bag if the given conditional is "true".
     *
     * @param  bool  $boolean
     * @param  string  $key
     * @param  string  $message
     * @return $this
     */
    public function add_if($boolean, $key, $message){
        return $boolean ? $this->add($key, $message) : $this;
    }

    /**
     * Determine if a key and message combination already exists.
     *
     * @param  string  $key
     * @param  string  $message
     * @return bool
     */
    protected function is_unique($key, $message){
        $messages = (array) $this->messages;
        return ! isset($messages[$key]) || ! in_array($message, $messages[$key]);
    }


    /**
     * Merge a new array of messages into the message bag.
     *
     * @param  array  $messages
     * @return $this
     */
    public function merge($messages){
        $this->messages = array_merge_recursive($this->messages, $messages);
        return $this;
    }

    /**
     * Determine if messages exist for all of the given keys.
     *
     * @param  array|string|null  $key
     * @return bool
     */
    public function has($key){
        if ($this->is_empty()) {
            return false;
        }
        if (is_null($key)) {
            return $this->any();
        }
		return $this->get($key);
    }

    /**
     * Determine if the message bag has any messages.
     *
     * @return bool
     */
    public function is_empty(){
        return ! $this->any();
    }

    /**
     * Determine if the message bag has any messages.
     *
     * @return bool
     */
    public function is_not_empty(){
        return $this->any();
    }

    /**
     * Determine if the message bag has any messages.
     *
     * @return bool
     */
    public function any(){
        return $this->count() > 0;
    }

    /**
     * Get the number of messages in the message bag.
     *
     * @return int
     */
    public function count(){
        return count($this->messages, COUNT_RECURSIVE) - count($this->messages);
    }

    /**
     * Get all of the messages from the message bag for a given key.
     *
     * @param  string  $key
     * @return array
     */
    public function get($key){
        return (array_key_exists($key, $this->messages)) ?  $this->messages[$key] : null;
    }

    /**
     * Get all of the messages for every key in the message bag.
     *
     * @return array
     */
    public function all(){
		return $this->messages;
    }

    /**
     * Get all of the unique messages for every key in the message bag.
     *
     * @return array
     */
    public function unique(){
        return array_unique($this->all());
    }

    /**
     * Convert the object to its JSON representation.
     *
     * @param  int  $options
     * @return string
     */
	public function to_json($options = 0){
		return json_encode( $this->all(), $options );
	}

    /**
     * Convert the object to its serialize string representation.
     *
     * @return string
     */
	public function to_serialize(){
		return serialize( $this->all() );
	}

    /**
     * Get all of the messages as array list
     *
     * @return array
     */
	public function values(){
		$data = [];
		$message_list = $this->all();
		if( !empty($message_list) ){
			foreach($message_list as $messages){
				$data = array_merge($data, array_values($messages));
			}
		}
		return $data;
	}

}
