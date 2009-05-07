<?php
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Q extends Object{
	static protected $__type__ = "type=number,set=false";
	static protected $__param__ = "type=number,set=false";
	static protected $__and_block__ = "type=Q[],set=false";
	static protected $__paginator__ = "type=Paginator,set=false";
	static protected $__order_by__ = "type=Q[],set=false";
	const EQ = 1;
	const NEQ = 2;
	const GT = 3;
	const LT = 4;
	const GTE = 5;
	const LTE = 6;
	const START_WITH = 7;
	const END_WITH = 8;
	const CONTAINS = 9;
	const IN = 10;
	const ORDER_ASC = 11;
	const ORDER_DESC = 12;
	const ORDER = 13;
	const MATCH = 14;

	const OR_BLOCK = 16;
	const AND_BLOCK = 17;

	const IGNORE = 2;
	const NOT = 4;

	protected $arg1;
	protected $arg2;
	protected $type;
	protected $param;
	protected $and_block = array();
	protected $or_block = array();
	protected $paginator;
	protected $order_by = array();

	protected function __new__($type=self::AND_BLOCK,$arg1=null,$arg2=null,$param=null){
		if($type === self::OR_BLOCK){
			$this->and_block = $arg1;
		}else{
			$this->arg1 = $arg1;
		}
		$this->arg2 = $arg2;
		$this->type = $type;
		if($param !== null) $this->param = decbin($param);
	}
	protected function arrayArg1(){
		if(empty($this->arg1)) return array();
		if(is_string($this->arg1)){
			$result = array();
			foreach(explode(",",$this->arg1()) as $arg){
				if(!empty($arg)) $result[] = $arg;
			}
			return $result;
		}else if($this->arg1 instanceof Column){
			return array($this->arg1);
		}
		throw new Exception("invalid arg1");
	}
	protected function __add__(){
		$args = func_get_args();
		foreach($args as $arg){
			if($arg instanceof Q){
				if($arg->type() == self::ORDER_ASC || $arg->type() == self::ORDER_DESC){
					$this->order_by[] = $arg;
				}else if($arg->type() == self::ORDER){
					foreach($arg->arArg1() as $column){
						if($column[0] === "-"){
							$this->add(new self(self::ORDER_DESC,substr($column,1)));
						}else{
							$this->add(new self(self::ORDER_ASC,$column));
						}
					}
				}else if($arg->type() == self::AND_BLOCK){
					if(!$arg->none()) call_user_func_array(array($this,"add"),$arg->and_block());
				}else if($arg->type() == self::OR_BLOCK){
					if(!$arg->none()) $this->or_block[] = $arg->and_block();
				}else{
					$this->and_block[] = $arg;
				}
			}else if($arg instanceof Paginator){
				$this->paginator = $arg;
			}else{
				throw new Exception("not supported");
			}
		}
	}
	protected function __none__(){
		return empty($this->and_block);
	}
	public function isBlock(){
		return ($this->type == self::AND_BLOCK || $this->type == self::OR_BLOCK);
	}
	public function ignore_case(){
		return (substr($this->param,-2,1) == "1");
	}
	public function not(){
		return (substr($this->param,-3,1) == "1");
	}
	static public function eq($column_str,$value,$param=null){
		return new self(self::EQ,$column_str,$value,$param);
	}
	static public function neq($column_str,$value,$param=null){
		return new self(self::NEQ,$column_str,$value,$param);
	}
	static public function gt($column_str,$value,$param=null){
		return new self(self::GT,$column_str,$value,$param);
	}
	static public function lt($column_str,$value,$param=null){
		return new self(self::LT,$column_str,$value,$param);
	}
	static public function gte($column_str,$value,$param=null){
		return new self(self::GTE,$column_str,$value,$param);
	}
	static public function lte($column_str,$value,$param=null){
		return new self(self::LTE,$column_str,$value,$param);
	}
	static public function startswith($column_str,$words,$param=null){
		return new self(self::START_WITH,$column_str,self::words_array($words),$param);
	}
	static public function endswith($column_str,$words,$param=null){
		return new self(self::END_WITH,$column_str,self::words_array($words),$param);
	}
	static public function contains($column_str,$words,$param=null){
		return new self(self::CONTAINS,$column_str,self::words_array($words),$param);
	}
	static public function in($column_str,$words,$param=null){
		return new self(self::IN,$column_str,array(self::words_array($words)),$param);
	}
	static private function words_array($words){
		if($words === "" || $words === null) throw new Exception("invalid character");
		if(is_array($words)){
			$result = array();
			foreach($words as $w){
				$w = (string)$w;
				if($w !== "") $result[] = $w;
			}
			return $result;
		}
		return array($words);
	}
	static public function order($column_str){
		return new self(self::ORDER,$column_str);
	}
	static public function select_order(&$column_str,$pre_column_str){
		if($column_str == $pre_column_str){
			$column_str = (substr($column_str,0,1) == "-") ? substr($column_str,1) : "-".$column_str;
		}
		return new self(self::ORDER,$column_str);
	}
	static public function match($dict,$param=null){
		if(!($param === null || $param === self::IGNORE)) throw new Exception("invalid param");
		return new self(self::MATCH,str_replace(" ",",",trim($dict)),null,$param);
	}
	static public function or_block(){
		$args = func_get_args();
		return new self(self::OR_BLOCK,$args);
	}
}
?>