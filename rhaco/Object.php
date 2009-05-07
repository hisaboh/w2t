<?php
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Object extends stdClass{
	static protected $___ar_attr___ = "array";
	static protected $___fm_attr___ = "format";
	static protected $___is_attr___ = "verify";
	static private $OBJ_METHOD;
	static private $EXTENDS_CNT = 0;
	static private $ANNOTATION = array();
	static private $ATTRACC = array();
	static private $CLASSVARS = array();
	private $_modules_obj_ = array();
	private $_modules_func_ = array();
	private $_mixin_varnm_ = array();
	protected $_class_access_ = false;
	protected $_class_;
	protected $_mixin_;

	/**
	 * Objectのgetterを指定してソート
	 *
	 * @param array $list
	 * @param string $getter_name
	 * @param boolean $revers
	 * @return array
	 */
	final static public function osort(array &$list,$getter_name,$revers=false){
		usort($list,create_function('$a,$b',sprintf('return ($a->%s() %s $b->%s()) ? -1 : 1;',$getter_name,(($revers) ? ">" : "<"),$getter_name)));
		return $list;
		/***
			uc($name,'
						static protected $__aaa__ = "type=number";
						public $aaa;
					');
			$list = array(new $name("aaa=1"),new $name("aaa=3"),new $name("aaa=2"));
			eq(1,$list[0]->aaa());
			eq(3,$list[1]->aaa());
			eq(2,$list[2]->aaa());
			
			$result = Object::osort($list,"aaa");
			eq(1,$list[0]->aaa());
			eq(2,$list[1]->aaa());
			eq(3,$list[2]->aaa());
			eq(1,$result[0]->aaa());
			eq(2,$result[1]->aaa());
			eq(3,$result[2]->aaa());

			$result = Object::osort($list,"aaa",true);
			eq(3,$result[0]->aaa());
			eq(2,$result[1]->aaa());
			eq(1,$result[2]->aaa());
		*/
	}
	/**
	 * 文字列として比較してソート
	 *
	 * @param array $list
	 * @param boolean $revers
	 * @return array
	 */
	final static public function ssort(array &$list,$revers=false){
		usort($list,create_function('$a,$b',sprintf('return (strcmp((string)$a,(string)$b) > 0) ? %s1 : %s1;',(($revers) ? "-" : ""),(($revers) ? "" : "-"))));
		return $list;
		/***
			uc($name,'
						static protected $__aaa__ = "type=number";
						public $aaa;
						protected function __str__(){
							return $this->aaa;
						}
					');
			$list = array(new $name("aaa=1"),new $name("aaa=3"),new $name("aaa=2"));
			eq(1,$list[0]->aaa());
			eq(3,$list[1]->aaa());
			eq(2,$list[2]->aaa());
			
			$result = Object::ssort($list);
			eq(1,$list[0]->aaa());
			eq(2,$list[1]->aaa());
			eq(3,$list[2]->aaa());
			eq(1,$result[0]->aaa());
			eq(2,$result[1]->aaa());
			eq(3,$result[2]->aaa());
		*/
	}
	/**
	 * Objectのgetterを指定してマージ
	 *
	 * @param array $list
	 * @param string $getter_name
	 * @return array
	 */
	final static public function omerge(array $list,$getter_name){
		$result = array();
		foreach($list as $obj) $result[$obj->{$getter_name}()] = $obj;
		return $result;
		/***
			uc($name,'
						static protected $__aaa__ = "type=number";
						protected $aaa;
						protected $bbb;
					');
			$list = array(new $name("aaa=1,bbb=a"),new $name("aaa=3,bbb=b"),new $name("aaa=1,bbb=c"),new $name("aaa=2,bbb=d"));
			eq("a",$list[0]->bbb());
			eq("b",$list[1]->bbb());
			eq("c",$list[2]->bbb());
			eq("d",$list[3]->bbb());
			
			$result = Object::omerge($list,"aaa");
			eq("a",$list[0]->bbb());
			eq("b",$list[1]->bbb());
			eq("c",$list[2]->bbb());
			eq("d",$list[3]->bbb());

			eq(3,sizeof($result));
			eq("c",$result[1]->bbb());
			eq("b",$result[3]->bbb());
			eq("d",$result[2]->bbb());
		*/
	}
	/**
	 * モジュールのコピー
	 *
	 * @param self $object
	 * @param boolean $recursive
	 */
	final public function copy_module(self $object,$recursive=false){
		foreach($object->_modules_obj_ as $obj) $this->add_modules($obj,$recursive);
	}
	/**
	 * モジュールの追加
	 * @param object $obj
	 */
	final public function add_modules($obj,$recursive=false){
		if(get_class($this) === get_class($obj)) return;
		$this->_modules_obj_[] = clone($obj);
		foreach(get_class_methods(get_class($obj)) as $method){
			if($method[0] != "_" && !in_array($method,self::$OBJ_METHOD)) $this->_modules_func_[$method][] = sizeof($this->_modules_obj_) - 1;
		}
		if($recursive){
			foreach($this->_mixin_varnm_ as $c){
				if($this->{$c} instanceof self) $this->{$c}->add_modules($obj);
			}
		}
		return $this;
	}
	/**
	 * モジュールのクリア
	 */
	final public function clear_modules(){
		foreach($this->_mixin_varnm_ as $c){
			if($this->{$c} instanceof self) $this->{$c}->clear_modules();
		}
		$this->_modules_func_ = $this->_modules_obj_ = array();
		return $this;
	}
	/**
	 * 登録されたモジュール全ての実行
	 *
	 * @param string $method
	 * @return mixed
	 */
	final protected function call_modules($method,&$p0=null,&$p1=null,&$p2=null,&$p3=null,&$p4=null,&$p5=null,&$p6=null,&$p7=null,&$p8=null,&$p9=null){
		return $this->execute_modules($method,false,$p0,$p1,$p2,$p3,$p4,$p5,$p6,$p7,$p8,$p9);
	}
	/**
	 * 最後に登録されたモジュールの実行
	 *
	 * @param string $method
	 * @return mixed
	 */
	final protected function call_module($method,&$p0=null,&$p1=null,&$p2=null,&$p3=null,&$p4=null,&$p5=null,&$p6=null,&$p7=null,&$p8=null,&$p9=null){
		return $this->execute_modules($method,true,$p0,$p1,$p2,$p3,$p4,$p5,$p6,$p7,$p8,$p9);
	}

	/**
	 * アノテーションの値を取得/設定
	 * @param string $name
	 * @param string $param_name
	 * @param mixed $value
	 * @return mixed
	 */
	final public function a($name,$param_name,$value=null){
		$this->variable_annotation($name);
		if($value !== null && !isset(self::$ANNOTATION[$this->_class_][$name]->{$param_name})){
			self::$ANNOTATION[$this->_class_][$name]->{$param_name} = $value;
		}
		return ($this->isA($name,$param_name)) ? self::$ANNOTATION[$this->_class_][$name]->{$param_name} : null;
	}
	/**
	 * アノテーション値全てを取得
	 * @param string $name
	 * @return mixed
	 */
	final public function inA($name){
		$this->variable_annotation($name);
		return isset(self::$ANNOTATION[$this->_class_][$name]) ? self::$ANNOTATION[$this->_class_][$name] : new stdClass();
	}
	/**
	 * アノテーションを更新する
	 *
	 * @param mixed $name
	 */
	final public function upA($name){
		if(isset(self::$ANNOTATION[$this->_class_][$name])) unset(self::$ANNOTATION[$this->_class_][$name]);
		/***
			uc($name1,'
				public $aaa;
			');

			$obj = new $name1;
			eq("string",$obj->a("aaa","type"));

			$obj->__aaa__ = "type=number";
			$obj->upA("aaa");
			eq("number",$obj->a("aaa","type"));
		*/
	}
	/**
	 * アノテーションが定義されているか
	 *
	 * @param string $name
	 * @param string $param_name
	 * @return boolean
	 */
	final public function isA($name,$param_name){
		$this->variable_annotation($name);
		return isset(self::$ANNOTATION[$this->_class_][$name]->{$param_name});
	}
	/**
	 * ハッシュとしての値を返す
	 * @return array
	 */
	final public function hash(){
		$args = func_get_args();
		return call_user_func_array(array($this,"__hash__"),$args);
		/***
			uc($name1,'
				public $aaa = "hoge";
				public $bbb = 1;
			',"Object");
			$obj1 = new $name1();
			eq(array("aaa"=>"hoge","bbb"=>"1"),$obj1->hash());

			uc($name2,'
				static protected $__bbb__ = "type=number";
			',$name1);
			$obj2 = new $name2();
			eq(array("aaa"=>"hoge","bbb"=>1),$obj2->hash());
		*/
	}
	/**
	 * 空か
	 * @return boolean
	 */
	final public function none(){
		$args = func_get_args();
		return call_user_func_array(array($this,"__none__"),$args);
		/***
			uc($name1,'
				public $aaa;
				public $bbb;
			');
			$obj = new $name1();
			eq(true,$obj->none());
			$obj->aaa("hoge");
			eq(false,$obj->none());
			$obj->rmAaa();
			eq(true,$obj->none());
		*/
	}
	/**
	 * 加算
	 * @param mixed $arg
	 * @return $this
	 */
	final public function add(){
		$args = func_get_args();
		call_user_func_array(array($this,"__add__"),$args);
		return $this;
		/***
			uc($name1,'
				public $aaa;
				protected function __add__($arg){
					if($arg instanceof self){
						$this->aaa .= $arg->aaa();
					}
				}
			');
			$obj1 = new $name1("aaa=hoge");
			$obj2 = new $name1("aaa=fuga");
			eq("hoge",$obj1->aaa());
			eq("hogefuga",$obj1->add($obj2)->aaa());
		*/
	}
	/**
	 * 減算
	 * @param mixed $arg
	 * @return $this
	 */
	final public function sub(){
		$args = func_get_args();
		call_user_func_array(array($this,"__sub__"),$args);
		return $this;
		/***
			uc($name1,'
				public $aaa;
				protected function __sub__($arg){
					if($arg instanceof self){
						$this->aaa = str_replace($arg->aaa(),"",$this->aaa);
					}
				}
			');
			$obj1 = new $name1("aaa=hogefuga");
			$obj2 = new $name1("aaa=fuga");
			eq("hogefuga",$obj1->aaa());
			eq("hoge",$obj1->sub($obj2)->aaa());
		*/
	}
	/**
	 * 乗算
	 * @param mixed $arg
	 * @return $this
	 */
	final public function mul(){
		$args = func_get_args();
		call_user_func_array(array($this,"__mul__"),$args);
		return $this;
		/***
			uc($name1,'
				public $aaa;
				protected function __mul__($arg){
					if($arg instanceof self){
						$this->aaa .= $arg->aaa();
					}
				}
			');
			$obj1 = new $name1("aaa=hoge");
			$obj2 = new $name1("aaa=fuga");
			eq("hoge",$obj1->aaa());
			eq("hogefuga",$obj1->mul($obj2)->aaa());
		*/
	}
	/**
	 * 除算
	 * @param mixed $arg
	 * @return $this
	 */
	final public function div(){
		$args = func_get_args();
		call_user_func_array(array($this,"__div__"),$args);
		return $this;
		/***
			uc($name1,'
				public $aaa;
				protected function __div__($arg){
					if($arg instanceof self){
						$this->aaa = str_replace($arg->aaa(),"",$this->aaa);
					}
				}
			');
			$obj1 = new $name1("aaa=hogefuga");
			$obj2 = new $name1("aaa=fuga");
			eq("hogefuga",$obj1->aaa());
			eq("hoge",$obj1->div($obj2)->aaa());
		*/
	}
	/**
	 * 自身に値をコピーする
	 * @param Object $obj
	 * @return $this
	 */
	final public function cp(){
		$args = func_get_args();
		call_user_func_array(array($this,"__cp__"),$args);
		return $this;
		/***
			uc($name1,'public $aaa;');
			uc($name2,'public $aaa;');
			uc($name3,'public $ccc;');

			$obj1 = new $name1();
			$obj2 = new $name2("aaa=hoge");
			$obj3 = new $name3("ccc=fuga");

			eq("hoge",$obj1->cp($obj2)->aaa());
			eq("hoge",$obj1->cp($obj3)->aaa());

			$obj1 = new $name1();
			eq("hoge",$obj1->cp(array("aaa"=>"hoge"))->aaa());
		*/
	}

	/**
	 * objectをmixinさせる
	 * @param object $object
	 * @param string $name
	 * @return $this
	 */
	final public function mixin($object,$name=null){
		/***
			uc($name1,'
				public $aaa = "AAA";
				public function xxx(){
					return "xxx";
				}
			');
			uc($name2,'
				public $bbb = "BBB";
				protected $ccc = "CCC";
				public function zzz(){
					return "zzz";
				}
			');
			$aa = new $name1();
			eq("xxx",$aa->xxx());
			try{
				$aa->zzz();
				eq(false,true);
			}catch(Exception $e){
				eq(true,true);
			}
			$aa->mixin(new $name2());
			eq("zzz",$aa->zzz());

			uc($name3,'protected $_mixin_ = "'.$name2.'";',$name2);
			$obj3 = new $name3();
			eq("BBB",$obj3->bbb());
			eq("CCC",$obj3->ccc());
			eq("zzz",$obj3->zzz());
			eq(true,($obj3 instanceof $name3));

			uc($name4,'
				public $eee = "EEE";
				protected $fff = "FFF";
				private $ggg = "GGG";
				public $hhh = "hhh";
				public function hhh(){
					return "HHH";
				}
			',"stdClass");
			$obj4 = new $name1;
			$obj4->mixin(new $name4);
			eq("AAA",$obj4->aaa());
			eq("EEE",$obj4->eee());
			try{
				$obj4->fff();
				eq(false,true);
			}catch(Exception $e){
				eq(true,true);
			}
			try{
				$obj4->ggg();
				eq(false,true);
			}catch(Exception $e){
				eq(true,true);
			}
			eq("HHH",$obj4->hhh());
		 */
		if($name === null) $name = "X".self::$EXTENDS_CNT++;
		$this->{$name} = (is_object($object)) ? $object : new $object;
		array_unshift($this->_mixin_varnm_,$name);
		return $this;
	}
	final private function __call($method,$args){
		if(!isset(self::$ATTRACC[$this->_class_])) $this->set_attracc();
		foreach($this->_mixin_varnm_ as $c){
			try{
				return call_user_func_array(array($this->{$c},$method),$args);
			}catch (Exception $e){
				if(array_key_exists($method,get_object_vars($this->{$c}))) return $this->{$c}->{$method};
			}
		}
		$name = $method;
		if(!isset(self::$CLASSVARS[$this->_class_][$method])){
			for($i=1;$i<strlen($method);$i++){
				if(ctype_upper($method[$i])){
					list($call,$name) = array(substr($method,0,$i),substr($method,$i));
					$name = strtolower($name[0]).substr($name,1);
					break;
				}
			}
			if(!isset(self::$CLASSVARS[$this->_class_][$name])){
				$vars = get_object_vars($this);
				if(!array_key_exists($name,$vars) && !array_key_exists($method,$vars)) throw new ReflectionException("Call to undefined method ".$method);
			}
		}
		$param = $this->variable_annotation($name);

		if(isset($call)){
			if(isset(self::$ATTRACC[$this->_class_][$call])){
				if(!empty(self::$ATTRACC[$this->_class_][$call]) && $this->call_func_alias(self::$ATTRACC[$this->_class_][$call],$name,$args,$result)) return $result;
				return call_user_func_array(array($this,"__".$call."_attr__"),array($args,$param));
			}
			throw new Exception("undef call ".$call);
		}
		if(empty($args)) return ($this->call_func_alias("get",$name,$args,$result)) ? $result : $this->get_class_member($this->{$name},$param);
		if($this->call_func_alias("set",$name,$args,$result)) return $result;
		switch($param->attr){
			case "a": return $this->{$name}[] = $this->set_class_member($args,$param);
			case "h":
				if(sizeof($args) === 2){
					$this->{$name}[$args[0]] = $this->set_class_member(array($args[1]),$param);
				}else{
					$value = $this->set_class_member($args,$param);
					$this->{$name}[strtolower($value)] = $value;
				}
				return $this->{$name};
			default:
				return $this->{$name} = $this->set_class_member($args,$param);
		}
		/***
			uc($class1,'
				static protected $__aaa__ = "type=number";
				static protected $__bbb__ = "type=number[]";
				static protected $__ccc__ = "type=string{}";
				static protected $__eee__ = "type=timestamp";
				static protected $__fff__ = "type=string,column=Acol,table=BTbl";
				static protected $__ggg__ = "type=string,set=false";
				static protected $__hhh__ = "type=boolean";
				public $aaa;
				public $bbb;
				public $ccc;
				public $ddd;
				public $eee;
				public $fff;
				protected $ggg = "hoge";
				public $hhh;

				protected function setDdd($a,$b){
					$this->ddd = $a.$b;
				}
				public function nextDay(){
					return date("Y/m/d H:i:s",$this->eee + 86400);
				}
				protected function __cn_attr__($args,$param){
					if(!isset($param->column) || !isset($param->table)) throw new Exception();
					return array($param->table,$param->column);
				}
			');

			$hoge = new $class1();
			eq(null,$hoge->aaa());
			eq(false,$hoge->isAaa());
			$hoge->aaa("123");
			eq(123,$hoge->aaa());
			eq(true,$hoge->isAaa());
			eq(array(123),$hoge->arAaa());
			$hoge->rmAaa();
			eq(false,$hoge->isAaa());
			eq(null,$hoge->aaa());

			eq(array(),$hoge->bbb());
			$hoge->bbb("123");
			eq(array(123),$hoge->bbb());
			$hoge->bbb(456);
			eq(array(123,456),$hoge->bbb());
			eq(456,$hoge->inBbb(1));
			eq("hoge",$hoge->inBbb(5,"hoge"));
			$hoge->bbb(789);
			$hoge->bbb(10);
			eq(array(123,456,789,10),$hoge->bbb());
			eq(array(1=>456,2=>789),$hoge->arBbb(1,2));
			eq(array(1=>456,2=>789,3=>10),$hoge->arBbb(1));
			$hoge->rmBbb();
			eq(array(),$hoge->bbb());

			eq(array(),$hoge->ccc());
			eq(false,$hoge->isCcc());
			$hoge->ccc("AaA");
			eq(array("aaa"=>"AaA"),$hoge->ccc());
			eq(true,$hoge->isCcc());
			eq(true,$hoge->isCcc("aaa"));
			eq(false,$hoge->isCcc("bbb"));
			$hoge->ccc("bbb");
			eq(array("aaa"=>"AaA","bbb"=>"bbb"),$hoge->ccc());
			$hoge->ccc(123);
			eq(array("aaa"=>"AaA","bbb"=>"bbb","123"=>"123"),$hoge->ccc());
			$hoge->rmCcc("bbb");
			eq(array("aaa"=>"AaA","123"=>"123"),$hoge->ccc());
			$hoge->ccc("ddd");
			eq(array("aaa"=>"AaA","123"=>"123","ddd"=>"ddd"),$hoge->ccc());
			eq(array("123"=>"123"),$hoge->arCcc(1,1));
			$hoge->rmCcc("aaa","ddd");
			eq(array("123"=>"123"),$hoge->ccc());
			$hoge->rmCcc();
			eq(array(),$hoge->ccc());
			$hoge->ccc("abc","def");
			eq(array("abc"=>"def"),$hoge->ccc());

			eq(null,$hoge->ddd());
			$hoge->ddd("hoge","fuga");
			eq("hogefuga",$hoge->ddd());

			$hoge->eee("1976/10/04");
			eq("1976/10/04",date("Y/m/d",$hoge->eee()));
			eq("1976/10/05 00:00:00",$hoge->nextDay());

			try{
				$hoge->eee("ABC");
				eq(false,$hoge->eee());
			}catch(InvalidArgumentException $e){
				eq(true,true);
			}
			try{
				$hoge->eee(null);
				eq(true,true);
			}catch(InvalidArgumentException $e){
				eq(false,true);
			}
			eq(array("BTbl","Acol"),$hoge->cnFff());

			eq("hoge",$hoge->ggg());
			try{
				$hoge->ggg("fuga");
				eq(false,true);
			}catch(Exception $e){
				eq(true,true);
			}
			$hoge->hhh(true);
			eq(true,$hoge->hhh());
			$hoge->hhh(false);
			eq(false,$hoge->hhh());
			try{
				$hoge->hhh("hoge");
				eq(false,true);
			}catch(Exception $e){
				eq(true,true);
			}
		*/
	}
	final private function set_attracc(){
		foreach(get_class_methods($this->_class_) as $method){
			if(substr($method,-7) == "_attr__" && substr($method,0,2) == "__" && ($cn = substr($method,2,-7)) && strpos($cn,"_") === false){
				self::$ATTRACC[$this->_class_][$cn] = $this->get_annotation("_".$method."_",$this->_class_);
			}
		}
		foreach(get_object_vars($this) as $name => $value) self::$CLASSVARS[$this->_class_][$name] = 1;
	}
	final public function __construct(){
		$this->_class_ = get_class($this);
		if(!isset(self::$OBJ_METHOD)) self::$OBJ_METHOD = get_class_methods("Object");
		if(!empty($this->_mixin_)){
			foreach(explode(",",$this->_mixin_) as $type){
				if(strpos($type,"=") !== false) list($name,$type) = explode("=",$type,2);
				$this->mixin($type,isset($name) ? $name : null);
			}
		}
		if(!isset(self::$ATTRACC[$this->_class_])) $this->set_attracc();
		$args = func_get_args();
		call_user_func_array(array($this,"__new__"),$args);
		$this->__init__();
		/***
			uc($name1,'protected $aaa="A";');
			uc($name2,'
						protected $_mixin_ = "'.$name1.'";
						protected $bbb="B";
					');
			$obj2 = new $name2;
			
			eq("A",$obj2->aaa());
			eq("B",$obj2->bbb());
		*/
		/***
			uc($name1,'protected $aaa="a";');
			uc($name2,'
						protected $_mixin_ = "'.$name1.'";
						protected $bbb="B";
						protected $aaa="A";

						public function aaa2(){
							return $this->aaa;
						}
					');
			$obj2 = new $name2;

			eq("a",$obj2->aaa());
			eq("B",$obj2->bbb());
			eq("A",$obj2->aaa2());

			$obj2->aaa("Z");
			eq("Z",$obj2->aaa());
			eq("B",$obj2->bbb());
			eq("A",$obj2->aaa2());
		*/
	}
	final public function __destruct(){
		$this->__del__();
	}
	final private function __toString(){
		return (string)$this->__str__();
	}
	final public function __clone(){
		$this->__clone__();
    }
    private function get_annotation($name,$start_class){
		while($ref = new ReflectionClass($start_class)){
			if(array_key_exists($name,($vars = $ref->getStaticProperties()))) return $vars[$name];
			if("stdClass" == ($start_class = $ref->getParentClass()->getName())) break;
		}
		return "";
	}
	private function parse_annotation($str,$name){
		$result = array();
		if(strpos($str,"=") !== false){
			$str = preg_replace("/(\(.+\))|(([\"\']).+?\\3)/e",'stripcslashes(str_replace(",","__ANNON_COMMA__","\\0"))',$str);
			foreach(explode(",",$str) as $arg){
				if($arg != ""){
					$exp = explode("=",$arg,2);
					if(sizeof($exp) !== 2) throw new Exception("syntax error annotation ".$name);
					if(substr($exp[1],-1) == ",") $exp[1] = substr($exp[1],0,-1);
					$value = ($exp[1] === "") ? null : str_replace("__ANNON_COMMA__",",",$exp[1]);
					$result[$exp[0]] = ($value === "true") ? true : (($value === "false") ? false : $value);
				}
			}
		}
		return $result;
	}
	private function parse_annotation_arg($str){
		$result = array();
		foreach(explode(",",preg_replace("/([\"\']).+?\\1/e","str_replace(',','__CHOICE_COMMA__','\\0')",substr($str,strpos($str,"(") + 1,strpos($str,")") - strlen($str)))) as $arg){
			if($arg !== "") $result[] = str_replace("__CHOICE_COMMA__",",",preg_replace("/^([\"\'])(.+)\\1$/","\\2",$arg));
		}
		return $result;
	}
	final private function variable_annotation($name){
		if(isset(self::$ANNOTATION[$this->_class_][$name])) return self::$ANNOTATION[$this->_class_][$name];
		$param = (object)array("name"=>$name,"type"=>"string","attr"=>null,"primary"=>false,"get"=>true,"set"=>true);
		if($name[0] != "_" && false !== property_exists($this,$name)){
			$annotation_name = "__".$name."__";
			$annotation = (($vars = get_object_vars($this)) && array_key_exists($annotation_name,$vars)) ?
							$vars[$annotation_name] :
							$this->get_annotation($annotation_name,$this->_class_);
			$param = (object)array_merge((array)$param,$this->parse_annotation($annotation,$this->_class_."::".$name));
			if(strpos($param->type,"choice") === 0){
				$param->choices = $this->parse_annotation_arg($param->type);
				$param->type = "choice";
			}
			switch(substr($param->type,-2)){
				case "[]": $param->attr = "a"; break;
				case "{}": $param->attr = "h"; break;
			}
			if(isset($param->attr)) $param->type = substr($param->type,0,-2);
			if($param->type === "serial") $param->primary = true;
		}
		return self::$ANNOTATION[$this->_class_][$name] = $param;
		/***
			uc($class1,'
				static protected $__aaa__ = "type=choice(AA,BB,CC)";
				static protected $__bbb__ = "type=choice(\'aaa\',\'bbb\',\'cc,c\')";
				public $aaa;
				public $bbb;
			');

			$obj = new $class1();
			$obj->aaa("BB");
			$obj->bbb("bbb");

			eq(array("AA","BB","CC"),$obj->a("aaa","choices"));
			eq(array("aaa","bbb","cc,c"),$obj->a("bbb","choices"));
		 */
	}
	final private function get_class_member($arg,$param){
		if(!$param->get) throw new InvalidArgumentException("Processing not permitted [get]");
		return $this->__getattr__($arg,$param);
	}
	final private function set_class_member($args,$param){
		if(!$param->set) throw new InvalidArgumentException("Processing not permitted [set]");
		if($args[0] === null || $args[0] === "") return null;
		$arg = $args[0];
		switch($param->type){
			case "variable": return $arg;
			case "string": return (string)$arg;
			case "serial":
			case "number":
				if(!is_numeric($arg)) throw new InvalidArgumentException(sprintf("`%s` is not a %s value",$arg,$param->type));
				return (float)$arg;
			case "boolean":
				if(is_string($arg)){
					$arg = ($arg === "true" || $arg === "1") ? true : (($arg === "false" || $arg === "0") ? false : $arg);
				}else if(is_int($arg)){
					$arg = ($arg === 1) ? true : (($arg === 0) ? false : $arg);
				}
				if(!is_bool($arg)) throw new InvalidArgumentException(sprintf("`%s` is not a %s value",$arg,$param->type));
				return (boolean)$arg;
			case "date":
			case "timestamp":
				if(ctype_digit((string)$arg)) return (int)$arg;
				if(((int)preg_replace("/[^\d]/","",$arg)) === 0) throw new InvalidArgumentException(sprintf("`%s` is not a %s value",$arg,$param->type));
				$time = strtotime($arg);
				if($time === false) throw new InvalidArgumentException(sprintf("`%s` is not a %s value",$arg,$param->type));
				return $time;
			case "time":
				return (preg_match("/^(\d+):(\d+):(\d+)$/",$arg,$m)) ? (intval($m[1]) * 3600) + (intval($m[2]) * 60) + intval($m[3]) : null;
			case "choice":
				if(!in_array($arg,$param->choices,true)) throw new InvalidArgumentException(sprintf("`%s` is not a %s value",$arg,$param->type));
				return $arg;
			default:
				return $this->__setattr__($args,$param);
		}
	}
	final private function execute_modules($method,$overwrite,&$p0=null,&$p1=null,&$p2=null,&$p3=null,&$p4=null,&$p5=null,&$p6=null,&$p7=null,&$p8=null,&$p9=null){
		$result = null;
		if(isset($this->_modules_func_[$method]) && !empty($this->_modules_func_[$method])){
			$funcs = ($overwrite) ? array($this->_modules_func_[$method][sizeof($this->_modules_func_[$method]) - 1]) : $this->_modules_func_[$method];
			foreach($funcs as $num){
				$result = call_user_func_array(array($this->_modules_obj_[$num],$method),array(&$p0,&$p1,&$p2,&$p3,&$p4,&$p5,&$p6,&$p7,&$p8,&$p9));
			}
		}
		return $result;
	}
	final protected function dict($dict){
		if(!empty($dict) && is_string($dict) && preg_match_all("/.+?[^\\\],|.+?$/",$dict,$match)){
			foreach($match[0] as $arg){
				if(strpos($arg,"=") !== false){
					list($name,$value) = explode("=",$arg,2);
					if(substr($value,-1) == ",") $value = substr($value,0,-1);
					$this->{$name}(($value === "") ? null : str_replace("\\,",",",preg_replace("/^([\"\'])(.*)\\1$/","\\2",$value)));
				}
			}
		}
		/***
			uc($name,'
					static protected $__ccc__ = "type=boolean";
					static protected $__ddd__ = "type=number";
					public $aaa;
					public $bbb;
					public $ccc;
					public $ddd;
				');
			$hoge = new $name("aaa=hoge");
			eq("hoge",$hoge->aaa());
			eq(null,$hoge->bbb());
			$hoge = new $name("bbb=fuga,aaa=hoge");
			eq("hoge",$hoge->aaa());
			eq("fuga",$hoge->bbb());
			$hoge = new $name("ccc=true");
			eq(true,$hoge->ccc());
			$hoge = new $name("ddd=123");
			eq(123,$hoge->ddd());
			$hoge = new $name("ddd=123.45");
			eq(123.45,$hoge->ddd());
		*/
	}
	final protected function access_members(){
		$result = array();
		$vars = get_object_vars($this);
		foreach($vars as $name => $value){
			if($name[0] !== "_") $result[$name] = $this->{$name}();
		}
		return $result;
	}
	final protected function array_extract(array $list,$offset,$limit){
		$current = 0;
		$result = array();
		foreach($list as $key => $value){
			if($offset <= $current && $limit > $current) $result[$key] = $value;
			$current++;
		}
		return $result;
	}
	final private function call_func_alias($call,$name,array &$args,&$result){
		if(!method_exists($this,$call.$name)) return false;
		$result = call_user_func_array(array($this,$call.$name),$args);
		return true;
	}
	final protected function verify_class_access($msg){
		if(!$this->_class_access_) throw new Exception($msg);
	}
	protected function __add__($arg){}
	protected function __sub__($arg){}
	protected function __mul__($arg){}
	protected function __div__($arg){}
	protected function __init__(){}
	protected function __del__(){}
	protected function __hash__(){
		return $this->access_members();
	}
	protected function __none__(){
		foreach($this->access_members() as $var => $value){
			if(!empty($value)) return false;
		}
		return true;
	}
	protected function __cp__($obj){
		$vars = $this->access_members();
		if($obj instanceof self){
			foreach($obj->access_members() as $name => $value){
				if(array_key_exists($name,$vars)) $this->{$name}($value);
			}
		}else if(is_array($obj)){
			foreach($obj as $name => $value){
				if(array_key_exists($name,$vars)) $this->{$name}($value);
			}
		}else{
			throw new InvalidArgumentException("cp");
		}
	}
	protected function __new__(){
		if(func_num_args() > 0){
			$arg = func_get_arg(0);
			if(is_array($arg)){
				foreach($arg as $name => $value) $this->{$name} = $value;
			}else if(is_string($arg)){
				$this->dict($arg);
			}
		}
	}
	protected function __str__(){
		return $this->_class_;
	}
	protected function __clone__(){
		$vars = get_object_vars($this);
		foreach($vars as $name => $value){
			if(is_object($value)) $this->{$name} = clone($this->{$name});
		}
	}
	protected function __getattr__($arg,$param){
		if($param->attr == "a" || $param->attr == "h") return (is_array($this->{$param->name})) ? $this->{$param->name} : (is_null($this->{$param->name}) ? array() : array($this->{$param->name}));
		return $arg;
	}
	protected function __setattr__($args,$param){
		if(class_exists($param->type)){
			if(!($args[0] instanceof $param->type)) throw new ReflectionException(sprintf("an illegal argument expects `%s` in `%s`.",$param->type,gettype($args[0])));
		}
		return $args[0];
	}
	protected function __an_attr__($args,$param){
		if(!isset($args[0])) throw new Exception("first args");
		return $this->a($param->name,$args[0],isset($args[1]) ? $args[1] : null);
	}
	protected function __in_attr__($args,$param){
		$arg = $args[0];
		$default = (isset($args[1])) ? $args[1] : null;
		return (is_object($this->{$param->name})) ? ((isset($this->{$param->name})) ? $this->{$param->name}->{$args[0]}() : $default) :
											((isset($this->{$param->name}[$args[0]])) ? $this->{$param->name}[$args[0]] : $default);

	}
	protected function __rm_attr__($args,$param){
		if(!$param->set) throw new LogicException("Processing not permitted");
		switch($param->attr){
			case "h":
				if(!empty($args)){
					foreach($args as $arg){
						$lname = strtolower($arg);
						if(isset($this->{$param->name}[$lname])) unset($this->{$param->name}[$lname]);
					}
					return null;
				}
			default:
				return $this->{$param->name} = null;
		}
	}
	protected function __ar_attr__($args,$param){
		$list = (is_array($this->{$param->name})) ? $this->{$param->name} : (($this->{$param->name} === null) ? array() : array($this->{$param->name}));
		return (isset($args[0])) ? $this->array_extract($list,$args[0],((isset($args[1]) ? $args[1] : sizeof($list)) + $args[0])) : $list;
	}
	protected function __fm_attr__($args,$param){
		switch($param->type){
			case "timestamp": return ($this->{$param->name} === null) ? null : (date((empty($args) ? "Y/m/d H:i:s" : $args[0]),(int)$this->{$param->name}));
			case "date": return ($this->{$param->name} === null) ? null : (date((empty($args) ? "Y/m/d" : $args[0]),(int)$this->{$param->name}));
			case "boolean": return ($this->{$$param->name}) ? (isset($args[1]) ? $args[1] : "") : (isset($args[0]) ? $args[0] : "false");
			default: return $this->{$param->name}();
		}
		return $this->{$param->name};
	}
	protected function __is_attr__($args,$param){
		$value = $this->{$param->name};
		if($param->attr === "h" || $param->attr === "a"){
			if(sizeof($args) !== 1) return !empty($this->{$param->name});
			$value = isset($this->{$param->name}[$args[0]]) ? $this->{$param->name}[$args[0]] : null;
		}
		return (boolean)(($param->type == "boolean") ? $value : isset($value));
		/***
			uc($name1,'
				static protected $__aa__ = "type=variable";
				static protected $__bb__ = "type=string";
				static protected $__cc__ = "type=serial";
				static protected $__dd__ = "type=number";
				static protected $__ee__ = "type=boolean";
				static protected $__ff__ = "type=timestamp";
				static protected $__gg__ = "type=time";
				static protected $__hh__ = "type=choice(abc,def)";
				static protected $__ii__ = "type=string{}";
				static protected $__jj__ = "type=string[]";
				protected $aa;
				protected $bb;
				protected $cc;
				protected $dd;
				protected $ee;
				protected $ff;
				protected $gg;
				protected $hh;
				protected $ii;
				protected $jj;
			');
			$obj = new $name1();
			eq(false,$obj->isAa());
			$obj->aa("hoge");
			eq(true,$obj->isAa());

			eq(false,$obj->isBb());
			$obj->bb("hoge");
			eq(true,$obj->isBb());
			$obj->bb("");
			eq(false,$obj->isBb());
			
			eq(false,$obj->isCc());
			$obj->cc(1);
			eq(true,$obj->isCc());
			$obj->cc(0);
			eq(true,$obj->isCc());

			eq(false,$obj->isDd());
			$obj->dd(1);
			eq(true,$obj->isDd());
			$obj->dd(0);
			eq(true,$obj->isDd());
			
			eq(false,$obj->isEe());
			$obj->ee(true);
			eq(true,$obj->isEe());
			$obj->ee(false);
			eq(false,$obj->isEe());

			eq(false,$obj->isFf());
			$obj->ff("2009/04/27 12:00:00");
			eq(true,$obj->isFf());
			
			eq(false,$obj->isGg());
			$obj->gg("12:00:00");
			eq(true,$obj->isGg());

			eq(false,$obj->isHh());
			$obj->hh("abc");
			eq(true,$obj->isHh());
			
			eq(false,$obj->isIi());
			eq(false,$obj->isIi("hoge"));
			$obj->ii("hoge","abc");
			eq(true,$obj->isIi("hoge"));
			
			eq(false,$obj->isJj());
			eq(false,$obj->isJj(0));
			$obj->jj("abc");
			eq(true,$obj->isJj(0));
		*/
	}
}
?>