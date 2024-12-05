<?php

namespace SAIL\Packages\Utils;

class Date extends JDF {

	protected $unixtime;
	protected $mode;

	public function __construct(int $unixtime=null,string $mode=null){
		$this->set_unixtime($unixtime);
		$this->set_mode($mode);
	}

	public function get_default_mode(){
		return strpos(strtolower(get_locale()),'ir')? 'jalali' : 'gregorian';
	}

	public function set_mode($mode){
		$valid_modes = ['jalali','gregorian'];
		$mode = in_array($mode,$valid_modes)? $mode : $this->get_default_mode();
		$this->mode = $mode ?: $this->get_default_mode();
		return $this;
	}

	public function get_mode(){
		return $this->mode;
	}

	public function set_unixtime($unixtime){
		$this->unixtime = $unixtime > 0 ? $unixtime : time();
		return $this;
	}

	public function get_unixtime(){
		return $this->unixtime;
	}

	public function set_date(int $year,int $month=1, int $day =1, int $hour=0,int $minute=0,int $second=0){
		$date_collection = ('jalali' == $this->get_mode())? $this->jalali_to_gregorian($year,$month,$day) : [$year,$month,$day];
		$year = current($date_collection);
		$month = next($date_collection);
		$day = next($date_collection);
		$unixtime = mktime($hour,$minute,$second,$month,$day,$year);
		$this->set_unixtime($unixtime);
		return $this;
	}

	public function get_date_formatted($format){
		return ('jalali' == $this->get_mode())? $this->jdate($format,$this->get_unixtime()) : date($format,$this->get_unixtime());
	}

}
