<?php
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Exceptions extends Exception{
	static private $self;
	private $messages = array();

	static public function add(Exception $exception,$name=null){
		if(self::$self === null) self::$self = new self();
		if($exception instanceof self){
			foreach($exception->messages as $e) self::add($e);
		}else{
			self::$self->messages["exceptions"][] = $exception;
			if($name !== null) self::$self->messages[$name][] = $exception;
		}
	}
	static public function messages($name="exceptions"){
		$messages = (self::invalid($name)) ? self::$self->messages[$name] : array();
		$result = array();
		foreach($messages as $m) $result[] = $m->getMessage();
		return $result;
	}
	static public function invalid($name="exceptions"){
		return (isset(self::$self) && isset(self::$self->messages[$name]));
	}
	static public function validation($name=null){
		if(self::$self !== null && (($name === null && !empty(self::$self->messages)) || isset(self::$self->messages[$name]))) throw self::$self;
	}
	public function __toString(){
		if(self::$self === null || empty(self::$self->messages)) return null;
		$result = (string)count(self::$self->messages["exceptions"])." exceptions: ";
		foreach(self::$self->messages["exceptions"] as $e){
			$result .= "\n ".$e->getMessage();
		}
		return $result;
	}
}
?>