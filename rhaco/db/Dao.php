<?php
Rhaco::import("core.Paginator");
Rhaco::import("core.Text");
Rhaco::import("core.Model");
Rhaco::import("db.Db");
Rhaco::import("db.Column");
Rhaco::import("db.Q");
Rhaco::module("StatementIterator");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
abstract class Dao extends Object implements Model{
	static private $CONNECTION;
	static private $DAO;
	protected $_database_;
	protected $_table_;
	protected $_has_hierarchy_ = 1;

	private $_columns_ = array();
	private $_self_columns_ = array();
	private $_conds_ = array();
	private $_alias_ = array();
	protected $_class_id_;
	protected $_has_many_conds_ = array();
	protected $_hierarchy_;

	protected function __new__(){
		if(func_num_args() == 1) $this->dict(func_get_arg(0));
		$this->get_connection();
		$this->parse_column_annotation();
		/***
			#has
			create_tmp_table("test_1","test_dao_init_has_parent",array("id"=>"serial","value"=>"string"));
			create_tmp_table("test_1","test_dao_init_has_child",array("id"=>"serial","parent_id"=>"number","value"=>"string"));
			create_tmp_table("test_1","test_dao_init_has_child_two",array("id"=>"serial","parent_id1"=>"number","parent_id2"=>"number","value"=>"string"));
			ACTIVE_TABLE("test_dao_init_has_parent","test_1","value=parent1")->save();
			ACTIVE_TABLE("test_dao_init_has_parent","test_1","value=parent2")->save();
			ACTIVE_TABLE("test_dao_init_has_child","test_1","parent_id=1,value=child")->save();
			ACTIVE_TABLE("test_dao_init_has_child_two","test_1","parent_id1=1,parent_id2=2,value=child_two")->save();

			class TestDaoInitHasParent extends Dao{
				static protected $__id__ = "type=serial";
				protected $id;
				protected $value;
			}
			class TestDaoInitHasChild extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__parent_id__ = "type=number";
				static protected $__parent__ = "type=TestDaoInitHasParent,cond=parent_id()id";
				protected $id;
				protected $parent_id;
				protected $value;
				protected $parent;
			}
			$result = C(TestDaoInitHasChild)->find_all(Q::order("parent_id"));
			eq(1,sizeof($result));
			foreach($result as $c){ eq(true,($c->parent() instanceof TestDaoInitHasParent)); }
			eq("parent1",$result[0]->parent()->value());

			class TestDaoInitHasChildTwo extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__parent_id1__ = "type=number";
				static protected $__parent_id2__ = "type=number";
				static protected $__parent1__ = "type=TestDaoInitHasParent,cond=parent_id1()id";
				static protected $__parent2__ = "type=TestDaoInitHasParent,cond=parent_id2()id";
				protected $id;
				protected $parent_id1;
				protected $parent_id2;
				protected $value;
				protected $parent1;
				protected $parent2;
			}
			$result = C(TestDaoInitHasChildTwo)->find_all();
			eq(1,sizeof($result));
			foreach($result as $c){ eq(true,($c->parent1() instanceof TestDaoInitHasParent)); }
			eq("parent1",$result[0]->parent1()->value());
			eq("parent2",$result[0]->parent2()->value());
		 */
		/***
			#grand
			create_tmp_table("test_1","test_dao_init_grand",array("id"=>"serial","parent_id"=>"number","value"=>"string"));
			ACTIVE_TABLE("test_dao_init_grand","test_1","parent_id=0,value=1")->save();
			ACTIVE_TABLE("test_dao_init_grand","test_1","parent_id=1,value=2")->save();
			ACTIVE_TABLE("test_dao_init_grand","test_1","parent_id=2,value=3")->save();

			class TestDaoInitGrand extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__parent_id__ = "type=number";
				static protected $__parent_value__ = "column=value,cond=parent_id(test_dao_init_grand.id.parent_id,test_dao_init_grand.id)";
				static protected $__parent_parent_id__ = "column=parent_id,cond=@parent_value";
				protected $id;
				protected $parent_id;
				protected $value;

				protected $parent_value;
				protected $parent_parent_id;
			}
			$result = C(TestDaoInitGrand)->find_all();
			eq(1,sizeof($result));
		 */
		/***
			#map
			create_tmp_table("test_1","test_dao_init_map_parent",array("id"=>"serial","value"=>"string"));
			create_tmp_table("test_1","test_dao_init_map_child",array("id"=>"serial","value"=>"string"));
			create_tmp_table("test_1","test_dao_init_map_map",array("id"=>"serial","parent_id"=>"number","child_id"=>"number"));
			ACTIVE_TABLE("test_dao_init_map_parent","test_1","value=parent1")->save();
			ACTIVE_TABLE("test_dao_init_map_parent","test_1","value=parent2")->save();
			ACTIVE_TABLE("test_dao_init_map_child","test_1","value=child1")->save();
			ACTIVE_TABLE("test_dao_init_map_map","test_1","parent_id=1,child_id=1")->save();

			class TestDaoInitMapParent extends Dao{
				static protected $__id__ = "type=serial";
				protected $id;
				protected $value;
			}
			class TestDaoInitMapChild extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__parent__ = "type=TestDaoInitMapParent,cond=id(test_dao_init_map_map.child_id.parent_id)id";
				protected $id;
				protected $value;
				protected $parent;
			}
			$result = C(TestDaoInitMapChild)->find_all();
			eq(1,sizeof($result));
			foreach($result as $c){ eq(true,($c->parent() instanceof TestDaoInitMapParent)); }
			eq("parent1",$result[0]->parent()->value());
		 */
		/***
			#many
			create_tmp_table("test_1","test_dao_init_many_parent",array("id"=>"serial","value"=>"string"));
			create_tmp_table("test_1","test_dao_init_many_child",array("id"=>"serial","parent_id"=>"number","value"=>"string"));
			ACTIVE_TABLE("test_dao_init_many_parent","test_1","value=parent1")->save();
			ACTIVE_TABLE("test_dao_init_many_parent","test_1","value=parent2")->save();
			ACTIVE_TABLE("test_dao_init_many_child","test_1","parent_id=1,value=child1-1")->save();
			ACTIVE_TABLE("test_dao_init_many_child","test_1","parent_id=1,value=child1-2")->save();
			ACTIVE_TABLE("test_dao_init_many_child","test_1","parent_id=1,value=child1-3")->save();
			ACTIVE_TABLE("test_dao_init_many_child","test_1","parent_id=2,value=child2-1")->save();
			ACTIVE_TABLE("test_dao_init_many_child","test_1","parent_id=2,value=child2-2")->save();

			class TestDaoInitManyParent extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__children__ = "type=TestDaoInitManyChild[],cond=id()parent_id";
				protected $id;
				protected $value;
				protected $children;
			}
			class TestDaoInitManyChild extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__parent_id__ = "type=number";
				protected $id;
				protected $parent_id;
				protected $value;
			}
			$size = array(3,2);
			foreach(C(TestDaoInitManyParent)->find() as $key => $r){
				eq($size[$key],sizeof($r->children()));
				foreach($r->arChildren() as $child){
					eq(true,($child instanceof TestDaoInitManyChild));
					eq($key + 1,$child->parent_id());
				}
			}
			
			$result = C(TestDaoInitManyParent)->find_all();
			foreach($result as $key => $r){
				eq($size[$key],sizeof($r->children()));
				foreach($r->children() as $child){
					eq(true,($child instanceof TestDaoInitManyChild));
					eq($key + 1,$child->parent_id());
				}
			}
		*/
	}
	final protected function parse_column_annotation(){
		if(!isset($this->_class_id_)) $this->_class_id_ = $this->_class_;
		if(isset(self::$DAO[$this->_class_id_])){
			$this->_columns_ = self::$DAO[$this->_class_id_]->columns;
			$this->_self_columns_ = self::$DAO[$this->_class_id_]->self_columns;
			$this->_conds_ = self::$DAO[$this->_class_id_]->conds;
			$this->_has_many_conds_ = self::$DAO[$this->_class_id_]->has_many_conds;
			$this->_alias_ = self::$DAO[$this->_class_id_]->alias;
			foreach(self::$DAO[$this->_class_id_]->has_dao as $name => $dao) $this->{$name}($dao);
			return;
		}
		$count = ($this->_class_id_ === $this->_class_) ? 0 : (int)substr($this->_class_id_,strpos($this->_class_id_,"___") + 3);
		$has_hierarchy = (isset($this->_hierarchy_)) ? $this->_hierarchy_ - 1 : $this->_has_hierarchy_;
		$root_table_alias = "t".$count++;
		$has_dao = array();
		foreach($this->access_members() as $name => $value){
			if($this->a($name,"extra") !== "true"){
				$anon_cond = $this->a($name,"cond");
				$column_type = $this->a($name,"type");

				$column = new Column();
				$column->name($name);
				$column->column($this->a($name,"column",$name));
				$column->column_alias("c".$count++);

				if($anon_cond === null){
					if(class_exists($column_type) && is_subclass_of($column_type,"Dao")) throw new Exception("undef ".$name." annotation 'cond'");
					$column->table($this->_table_);
					$column->table_alias($root_table_alias);
					$column->primary($this->a($name,"primary"));
					$column->auto($column_type === "serial");
					$this->_self_columns_[$name] = $this->_columns_[] = $column;
					$this->_alias_[$column->column_alias()] = $name;
				}else if(false !== strpos($anon_cond,"(")){
					$is_has = class_exists($column_type) && is_subclass_of($column_type,"Dao");
					$is_has_many = ($is_has && $this->a($name,"attr") === "a");
					if((!$is_has || $has_hierarchy > 0) && preg_match("/^(.+)\((.*)\)(.*)$/",$anon_cond,$match)){
						list(,$self_var,$conds_string,$has_var) = $match;
						list($self_var,$conds_string,$has_var) = Text::trim($self_var,$conds_string,$has_var);
						$conds = array();
						$ref_table = $ref_table_alias = null;
						
						if(!empty($conds_string)){
							foreach(explode(",",$conds_string) as $key => $cond){
								$tcc = explode(".",$cond,3);
								if(sizeof($tcc) === 3){
									list($t,$c1,$c2) = $tcc;
									$ref_table = $this->set_table_name($t);
									$ref_table_alias = "t".$count++;
									$conds[] = Column::cond_instance($c1,"c".$count++,$ref_table,$ref_table_alias);
									$conds[] = Column::cond_instance($c2,"c".$count++,$ref_table,$ref_table_alias);
								}else{
									list($t,$c1) = $tcc;
									$ref_table = $this->set_table_name($t);
									$ref_table_alias = "t".$count++;
									$conds[] = Column::cond_instance($c1,"c".$count++,$ref_table,$ref_table_alias);
								}
							}
						}
						if($is_has_many){
							$this->a($name,"has_many",true);
							$dao = new $column_type(("_class_id_=".$this->_class_."___".$count++).",_class_access_=true");
							$has_column = $dao->base_column($has_var);
							$conds[] = Column::cond_instance($has_column->column(),"c".$count++,$has_column->table(),$has_column->table_alias());
							array_unshift($conds,Column::cond_instance($self_var,"c".$count++,$this->_table_,$root_table_alias));
							$dao->add_conds($conds);
							$this->_has_many_conds_[$name] = array($dao,$has_var,$self_var);
						}else{
							if($is_has){
								$dao = new $column_type(("_class_id_=".$this->_class_."___".$count++).",_hierarchy_=".$has_hierarchy);
								$has_dao[$name] = $dao;
								$this->{$name}($dao);
								$this->_columns_ = array_merge($this->_columns_,$dao->columns());
								$this->_conds_ = array_merge($this->_conds_,$dao->conds());
								$this->a($name,"has",true);
								foreach($dao->columns() as $column) $this->_alias_[$column->column_alias()] = $name;
								$has_column = $dao->base_column($has_var);
								$conds[] = Column::cond_instance($has_column->column(),"c".$count++,$has_column->table(),$has_column->table_alias());
							}else{
								$column->table($ref_table);
								$column->table_alias($ref_table_alias);
								$this->_columns_[] = $column;
								$this->_alias_[$column->column_alias()] = $name;
							}
							array_unshift($conds,Column::cond_instance($self_var,"c".$count++,$this->_table_,$root_table_alias));
							$this->add_conds($conds);
						}
					}
				}else if($anon_cond[0] === "@"){
					$c = $this->base_column(substr($anon_cond,1));
					$column->table($c->table());
					$column->table_alias($c->table_alias());
					$this->_columns_[] = $column;
					$this->_alias_[$column->column_alias()] = $name;
				}
			}
		}
		self::$DAO[$this->_class_id_] = (object)array(
														"columns"=>$this->_columns_,
														"self_columns"=>$this->_self_columns_,
														"conds"=>$this->_conds_,
														"alias"=>$this->_alias_,
														"has_dao"=>$has_dao,
														"has_many_conds"=>$this->_has_many_conds_
														);
	}
	private function base_column($name){
		foreach($this->_columns_ as $c){
			if($c->isBase() && $c->name() === $name) return $c;
		}
		throw new Exception("undef var ".$name);
	}
	final public function columns(){
		return $this->_columns_;
	}
	final public function self_columns(){
		return $this->_self_columns_;
	}
	final public function primary_columns(){
		$result = array();
		foreach($this->_self_columns_ as $column){
			if($column->primary()) $result[$column->name()] = $column;
		}
		return $result;
	}
	final public function primary_values(){
		$result = array();
		foreach($this->primary_columns() as $name => $column){
			$result[$name] = $this->{$name}();
		}
		return $result;
	}
	final public function conds(){
		return $this->_conds_;
	}
	private function add_conds(array $conds){
		for($i=0;$i<sizeof($conds);$i+=2){
			$this->_conds_[] = array($conds[$i],$conds[$i+1]);
		}
	}
	final public function parse_resultset($resultset){
		foreach($resultset as $alias => $value){
			if(isset($this->_alias_[$alias])){
				if($this->a($this->_alias_[$alias],"has",false)){
					$this->{$this->_alias_[$alias]}()->parse_resultset(array($alias=>$value));
				}else{
					$this->{$this->_alias_[$alias]}($value);
				}
			}
		}
		return $this->__resultset_key__();
	}
	protected function __resultset_key__(){
		return null;
	}

	/**
	 * テーブル名を取得
	 * @return string
	 */
	final public function table(){
		return $this->_table_;
	}
	/**
	 * 接続情報を返す
	 *
	 * @return Db
	 */
	final public function connection(){
		return self::$CONNECTION[$this->_class_];
	}
	final protected function get_connection(){
		if(!isset(self::$CONNECTION[$this->_class_])) self::$CONNECTION[$this->_class_] = Db::connection($this->_database_);
		if(empty($this->_table_)){
			$this->_table_ = strtolower($this->_class_[0]);
			for($i=1;$i<strlen($this->_class_);$i++) $this->_table_ .= (ctype_lower($this->_class_[$i])) ? $this->_class_[$i] : "_".strtolower($this->_class_[$i]);
		}
		$this->_table_ = $this->set_table_name($this->_table_);
		$dbtype = $this->connection()->type();
		$this->add_modules(new $dbtype());
	}
	final private function set_table_name($name){
		$name = $this->connection()->prefix().$name;
		if($this->connection()->isUpper()) $name = strtoupper($name);
		if($this->connection()->isLower()) $name = strtolower($name);
		return $name;
	}
	protected function __getattr__($arg,$param){
		if(isset($this->_has_many_conds_[$param->name])){
			if($this->{$param->name} !== null) return $this->{$param->name};
			list($dao,$has_var,$self_var) = $this->_has_many_conds_[$param->name];
			return $this->{$param->name} = $dao->find_all(Q::eq($has_var,$this->{$self_var}));
		}
		return $arg;
	}
	protected function __setattr__($args,stdClass $param){
		$arg = $args[0];
		switch($param->type){
			case "text":
				return $arg;
		}
		return parent::__setattr__($args,$param);
	}
	protected function __is_attr__($args,$param){
		return true;
	}
	protected function __save_verify__(){}
	protected function __before_save__(){}
	protected function __after_save__(){}
	protected function __before_create__(){}
	protected function __after_create__(){}
	protected function __before_update__(){}
	protected function __after_update__(){}
	protected function __after_delete__(){}
	protected function __before_delete__(){}
	
	final protected function prepare($sql){
		if(false === ($statement = $this->connection()->prepare($sql))) throw new LogicException($sql);
		return $statement;
	}
	final private function update_query($sql,array $vars=array(),$is_list=false){
		$statement = $this->prepare($sql);
		$statement->execute($vars);
		$errors = $statement->errorInfo();
		if(sizeof($errors) == 3) throw new LogicException("[".$errors[1]."] ".$errors[2]);
		return ($is_list) ? $statement->fetchAll(PDO::FETCH_ASSOC) : $statement->fetchAll(PDO::FETCH_COLUMN,0);
	}
	final private function save_verify(){
		foreach($this->self_columns() as $name => $column){
			$type = $this->a($name,"type");
			$value = $this->{$name};
			if($this->a($name,"require") === true && ($value === "" || $value === null)) Exceptions::add(new Exception($name." required"));
			if($this->a($name,"unique") === true){
				$q = array(Q::eq($name,$value));
				foreach($this->primary_columns() as $column){
					if(null !== ($value = $this->{$column->name()}())) $q[] = Q::neq($column->name(),$this->{$column->name()}());
				}
				if(0 < call_user_func_array(array(C($this),"find_count"),$q)) Exceptions::add(new Exception($name." unique"));
			}
		}
		foreach($this->self_columns() as $column){
			if(!$this->{"is".ucfirst($column->name())}()){
				Exceptions::add(new Exception("verify fail"));
			}
		}
		$this->__save_verify__();
		Exceptions::validation();
	}
	final private function which_aggregator($exe,array $args,$is_list=false){
		$target_name = $gorup_name = array();
		if(isset($args[0]) && is_string($args[0])){
			$target_name = array_shift($args);
			if(isset($args[0]) && is_string($args[0])) $gorup_name = array_shift($args);
		}
		$query = new Q();
		call_user_func_array(array($query,"add"),$args);
		$sql = $this->call_module($exe."_sql",$this,$target_name,$gorup_name,$query);
		return $this->update_query($sql->sql,$sql->vars,$is_list);
	}
	final private function exec_aggregator($exec,$target_name,$args,$format=true){
		$this->verify_class_access("call C(DAO_CLASS)->find_".$exec."()");
		$result = $this->which_aggregator($exec,$args);
		$current = current($result);
		if($format){
			$this->{$target_name}($current);
			$current = $this->{$target_name}();
		}
		return $current;
	}
	final private function exec_aggregator_by($exec,$target_name,$gorup_name,$args){
		if(empty($target_name) || !is_string($target_name)) throw new Exception("undef target_name");
		if(empty($gorup_name) || !is_string($gorup_name)) throw new Exception("undef group_name");
		$this->verify_class_access("call C(DAO_CLASS)->find_".$exec."_by()");
		$results = array();
		foreach($this->which_aggregator($exec,$args,true) as $key => $value){
			$this->{$target_name}($value["target_column"]);
			$this->{$gorup_name}($value["key_column"]);
			$results[$this->{$gorup_name}()] = $this->{$target_name}();
		}
		return $results;
	}
	/**
	 * カウントを取得する
	 *
	 * @return int
	 */
	final public function find_count($target_name=null){
		$args = func_get_args();
		return (int)$this->exec_aggregator("count",$target_name,$args,false);
		/***
			create_tmp_table("test_1","test_dao_size",array("id"=>"serial","type"=>"number","value"=>"string"));
			class TestDaoSize extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__type__ = "type=number";
				protected $id;
				protected $type;
				protected $value;
			}
			R(new TestDaoSize("value=abc,type=1"))->save();
			R(new TestDaoSize("value=def,type=2"))->save();
			R(new TestDaoSize("value=ghi,type=2"))->save();

			eq(3,C(TestDaoSize)->find_count("id"));
			eq(3,C(TestDaoSize)->find_count("value"));
			eq(1,C(TestDaoSize)->find_count(Q::eq("value","abc")));
			eq(2,C(TestDaoSize)->find_count(Q::eq("value","abc"),Q::or_block(Q::eq("value","def"))));
			eq(3,C(TestDaoSize)->find_count(Q::eq("value","abc"),Q::or_block(Q::eq("value","def"),Q::or_block(Q::neq("value","def"))),Q::eq("value","abc")));
		 */
	}
	/**
	 * グルーピングしてカウントを取得する
	 *
	 * @param string $target_name
	 * @param string $gorup_name
	 * @return array
	 */
	final public function find_count_by($target_name,$gorup_name){
		$args = func_get_args();
		return $this->exec_aggregator_by("count",$target_name,$gorup_name,$args);
		/***
			create_tmp_table("test_1","test_dao_count_by",array("id"=>"serial","type"=>"number","value"=>"string"));
			class TestDaoCountBy extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__type__ = "type=number";
				protected $id;
				protected $type;
				protected $value;
			}
			R(new TestDaoCountBy("value=abc,type=1"))->save();
			R(new TestDaoCountBy("value=def,type=2"))->save();
			R(new TestDaoCountBy("value=ghi,type=2"))->save();
			R(new TestDaoCountBy("value=abc,type=1"))->save();
			R(new TestDaoCountBy("value=abc,type=1"))->save();

			eq(array(1=>1,2=>2,3=>3,4=>4,5=>5),C(TestDaoCountBy)->find_count_by("id","id"));
			eq(array("abc"=>"abc","def"=>"def","ghi"=>"ghi"),C(TestDaoCountBy)->find_count_by("value","value"));
			eq(array(1=>3,2=>2),C(TestDaoCountBy)->find_count_by("id","type"));
		 */
	}
	/**
	 * 合計を取得する
	 *
	 * @param string $target_name
	 * @return mixed
	 */
	final public function find_sum($target_name){
		$args = func_get_args();
		return $this->exec_aggregator("sum",$target_name,$args);
		/***
			create_tmp_table("test_1","test_dao_sum",array("id"=>"serial","price"=>"number","type"=>"number"));
			ACTIVE_TABLE("test_dao_sum","test_1","price=20,type=2")->save();
			ACTIVE_TABLE("test_dao_sum","test_1","price=20,type=2")->save();
			ACTIVE_TABLE("test_dao_sum","test_1","price=10,type=1")->save();
			ACTIVE_TABLE("test_dao_sum","test_1","price=10,type=1")->save();
			class TestDaoSum extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__price__ = "type=number";
				static protected $__type__ = "type=number";
				protected $id;
				protected $price;
				protected $type;
			}
			eq(60,C(TestDaoSum)->find_sum("price"));
			eq(20,C(TestDaoSum)->find_sum("price",Q::eq("type",1)));
		 */
	}
	/**
	 * グルーピングした合計を取得する
	 *
	 * @param string $target_name
	 * @param string $gorup_name
	 * @return array
	 */
	final public function find_sum_by($target_name,$gorup_name){
		$args = func_get_args();
		return $this->exec_aggregator_by("sum",$target_name,$gorup_name,$args);
		/***
			create_tmp_table("test_1","test_dao_sum_by",array("id"=>"serial","price"=>"number","type"=>"number"));
			ACTIVE_TABLE("test_dao_sum_by","test_1","price=20,type=2")->save();
			ACTIVE_TABLE("test_dao_sum_by","test_1","price=20,type=2")->save();
			ACTIVE_TABLE("test_dao_sum_by","test_1","price=10,type=1")->save();
			ACTIVE_TABLE("test_dao_sum_by","test_1","price=10,type=1")->save();
			class TestDaoSumBy extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__price__ = "type=number";
				static protected $__type__ = "type=number";
				protected $id;
				protected $price;
				protected $type;
			}
			eq(array(1=>20,2=>40),C(TestDaoSumBy)->find_sum_by("price","type"));
			eq(array(1=>20),C(TestDaoSumBy)->find_sum_by("price","type",Q::eq("type",1)));
		 */
	}
	/**
	 * 最大値を取得する
	 *
	 * @param string $target_name
	 * @return mixed
	 */
	final public function find_max($target_name){
		$args = func_get_args();
		return $this->exec_aggregator("max",$target_name,$args);
		/***
			create_tmp_table("test_1","test_dao_max",array("id"=>"serial","price"=>"number","type"=>"number","name"=>"string"));
			ACTIVE_TABLE("test_dao_max","test_1","price=30,type=2,name=aaa")->save();
			ACTIVE_TABLE("test_dao_max","test_1","price=20,type=2,name=ccc")->save();
			ACTIVE_TABLE("test_dao_max","test_1","price=20,type=1,name=AAA")->save();
			ACTIVE_TABLE("test_dao_max","test_1","price=10,type=1,name=BBB")->save();
			class TestDaoMax extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__price__ = "type=number";
				static protected $__type__ = "type=number";
				protected $id;
				protected $price;
				protected $type;
				protected $name;
			}
			eq(30,C(TestDaoMax)->find_max("price"));
			eq(20,C(TestDaoMax)->find_max("price",Q::eq("type",1)));
			eq("ccc",C(TestDaoMax)->find_max("name"));
			eq("BBB",C(TestDaoMax)->find_max("name",Q::eq("type",1)));
		 */
	}
	final public function find_max_by($target_name,$gorup_name){
		$args = func_get_args();
		return $this->exec_aggregator_by("max",$target_name,$gorup_name,$args);
		/***
			create_tmp_table("test_1","test_dao_max_by",array("id"=>"serial","price"=>"number","type"=>"number","name"=>"string"));
			ACTIVE_TABLE("test_dao_max_by","test_1","price=30,type=2,name=aaa")->save();
			ACTIVE_TABLE("test_dao_max_by","test_1","price=20,type=2,name=ccc")->save();
			ACTIVE_TABLE("test_dao_max_by","test_1","price=20,type=1,name=AAA")->save();
			ACTIVE_TABLE("test_dao_max_by","test_1","price=10,type=1,name=BBB")->save();
			class TestDaoMaxBy extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__price__ = "type=number";
				static protected $__type__ = "type=number";
				protected $id;
				protected $price;
				protected $type;
				protected $name;
			}
			eq(array(1=>20,2=>30),C(TestDaoMaxBy)->find_max_by("price","type"));
			eq(array(1=>20),C(TestDaoMaxBy)->find_max_by("price","type",Q::eq("type",1)));
			eq(array(1=>"BBB",2=>"ccc"),C(TestDaoMaxBy)->find_max_by("name","type"));
			eq(array(1=>"BBB"),C(TestDaoMaxBy)->find_max_by("name","type",Q::eq("type",1)));
		 */
	}
	/**
	 * 最小値を取得する
	 *
	 * @param string $target_name
	 * @return mixed
	 */
	final public function find_min($target_name){
		$args = func_get_args();
		return $this->exec_aggregator("min",$target_name,$args);
		/***
			create_tmp_table("test_1","test_dao_min",array("id"=>"serial","price"=>"number","type"=>"number","name"=>"string"));
			ACTIVE_TABLE("test_dao_min","test_1","price=30,type=2,name=aaa")->save();
			ACTIVE_TABLE("test_dao_min","test_1","price=5,type=2,name=ccc")->save();
			ACTIVE_TABLE("test_dao_min","test_1","price=20,type=1,name=AAA")->save();
			ACTIVE_TABLE("test_dao_min","test_1","price=10,type=1,name=BBB")->save();
			class TestDaoMin extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__price__ = "type=number";
				static protected $__type__ = "type=number";
				protected $id;
				protected $price;
				protected $type;
				protected $name;
			}
			eq(5,C(TestDaoMin)->find_min("price"));
			eq(10,C(TestDaoMin)->find_min("price",Q::eq("type",1)));
			eq("aaa",C(TestDaoMin)->find_min("name"));
			eq("AAA",C(TestDaoMin)->find_min("name",Q::eq("type",1)));
		 */
	}
	final public function find_min_by($target_name,$gorup_name){
		$args = func_get_args();
		return $this->exec_aggregator_by("min",$target_name,$gorup_name,$args);
		/***
			create_tmp_table("test_1","test_dao_min_by",array("id"=>"serial","price"=>"number","type"=>"number","name"=>"string"));
			ACTIVE_TABLE("test_dao_min_by","test_1","price=30,type=2,name=aaa")->save();
			ACTIVE_TABLE("test_dao_min_by","test_1","price=5,type=2,name=ccc")->save();
			ACTIVE_TABLE("test_dao_min_by","test_1","price=20,type=1,name=AAA")->save();
			ACTIVE_TABLE("test_dao_min_by","test_1","price=10,type=1,name=BBB")->save();
			class TestDaoMinBy extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__price__ = "type=number";
				static protected $__type__ = "type=number";
				protected $id;
				protected $price;
				protected $type;
				protected $name;
			}
			eq(array(1=>10,2=>5),C(TestDaoMinBy)->find_min_by("price","type"));
			eq(array(1=>10),C(TestDaoMinBy)->find_min_by("price","type",Q::eq("type",1)));
			eq(array(1=>"AAA",2=>"aaa"),C(TestDaoMinBy)->find_min_by("name","type"));
			eq(array(1=>"AAA"),C(TestDaoMinBy)->find_min_by("name","type",Q::eq("type",1)));
		 */
	}
	/**
	 * 最小値を取得する
	 *
	 * @param string $target_name
	 * @return mixed
	 */
	final public function find_avg($target_name){
		$args = func_get_args();
		return $this->exec_aggregator("avg",$target_name,$args);
		/***
			create_tmp_table("test_1","test_dao_avg",array("id"=>"serial","price"=>"number","type"=>"number","name"=>"string"));
			ACTIVE_TABLE("test_dao_avg","test_1","price=20,type=2,name=aaa")->save();
			ACTIVE_TABLE("test_dao_avg","test_1","price=30,type=2,name=ccc")->save();
			ACTIVE_TABLE("test_dao_avg","test_1","price=25,type=1,name=AAA")->save();
			ACTIVE_TABLE("test_dao_avg","test_1","price=5,type=1,name=BBB")->save();
			class TestDaoAvg extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__price__ = "type=number";
				static protected $__type__ = "type=number";
				protected $id;
				protected $price;
				protected $type;
				protected $name;
			}
			eq(20,C(TestDaoAvg)->find_avg("price"));
			eq(15,C(TestDaoAvg)->find_avg("price",Q::eq("type",1)));
		 */
	}
	final public function find_avg_by($target_name,$gorup_name){
		$args = func_get_args();
		return $this->exec_aggregator_by("avg",$target_name,$gorup_name,$args);
		/***
			create_tmp_table("test_1","test_dao_avg_by",array("id"=>"serial","price"=>"number","type"=>"number","name"=>"string"));
			ACTIVE_TABLE("test_dao_avg_by","test_1","price=20,type=2,name=aaa")->save();
			ACTIVE_TABLE("test_dao_avg_by","test_1","price=30,type=2,name=ccc")->save();
			ACTIVE_TABLE("test_dao_avg_by","test_1","price=25,type=1,name=AAA")->save();
			ACTIVE_TABLE("test_dao_avg_by","test_1","price=5,type=1,name=BBB")->save();
			class TestDaoAvgBy extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__price__ = "type=number";
				static protected $__type__ = "type=number";
				protected $id;
				protected $price;
				protected $type;
				protected $name;
			}
			eq(array(1=>15,2=>25),C(TestDaoAvgBy)->find_avg_by("price","type"));
			eq(array(1=>15),C(TestDaoAvgBy)->find_avg_by("price","type",Q::eq("type",1)));
		 */
	}
	/**
	 * distinctした一覧を取得する
	 *
	 * @param string $target_name
	 * @return array
	 */
	final public function find_distinct($target_name){
		$this->verify_class_access("call C(DAO_CLASS)->find_distinct()");
		$args = func_get_args();
		$results = $this->which_aggregator("distinct",$args);
		return $results;
		/***
			create_tmp_table("test_1","test_dao_distinct",array("id"=>"serial","name"=>"string","type"=>"number"));
			ACTIVE_TABLE("test_dao_distinct","test_1","name=AAA,type=1")->save();
			ACTIVE_TABLE("test_dao_distinct","test_1","name=BBB,type=2")->save();
			ACTIVE_TABLE("test_dao_distinct","test_1","name=AAA,type=1")->save();
			ACTIVE_TABLE("test_dao_distinct","test_1","name=AAA,type=1")->save();
			ACTIVE_TABLE("test_dao_distinct","test_1","name=CCC,type=1")->save();

			class TestDaoDistinct extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__type__ = "type=number";
				protected $id;
				protected $name;
				protected $type;
			}
			eq(array("AAA","BBB","CCC"),C(TestDaoDistinct)->find_distinct("name"));
			eq(array("AAA","CCC"),C(TestDaoDistinct)->find_distinct("name",Q::eq("type",1)));
		 */
	}
	
	/**
	 * 検索結果をすべて取得する
	 *
	 * @return array Dao
	 */
	final public function find_all(){
		$this->verify_class_access("call C(DAO_CLASS)->find_all()");
		$args = func_get_args();
		$result = array();
		$it = call_user_func_array(array($this,"find"),$args);
		$has_many_column = array();
		$is_has_many = (!empty($this->_has_many_conds_));
		
		foreach($it as $p){
			if($is_has_many){
				foreach($this->_has_many_conds_ as $name => $conds) $has_many_column[$name][] = $p->{$conds[2]}();
			}
			$result[] = $p;
		}
		if($is_has_many){
			foreach($this->_has_many_conds_ as $name => $conds){
				foreach($conds[0]->find(Q::in($conds[1],$has_many_column[$name])) as $dao){
					foreach($result as $self_dao){
						if($dao->{$conds[1]}() === $self_dao->{$conds[2]}()) $self_dao->{$name}($dao);
					}
				}
			}
		}
		return $result;
		/***
			# test
			create_tmp_table("test_1","test_dao_find",array("id"=>"serial","value"=>"string","value2"=>"string","updated"=>"timestamp","order"=>"number"));
			class TestDaoFind extends Dao{
				static protected $__id__ = "type=serial";
				static protected $__order__ = "type=number";
				static protected $__updated__ = "type=timestamp";
				protected $id;
				protected $order;
				protected $value;
				protected $value2;
				protected $updated;
			}
			R(new TestDaoFind("value=abc,updated=2008/12/24 10:00:00,order=4"))->save();
			R(new TestDaoFind("value=def,updated=2008/12/24 10:00:00,order=3"))->save();
			R(new TestDaoFind("value=ghi,updated=2008/12/24 10:00:00,order=1"))->save();
			R(new TestDaoFind("value=jkl,updated=2008/12/24 10:00:00,order=2"))->save();
			R(new TestDaoFind("value=aaa,updated=2008/12/24 10:00:00,order=2"))->save();

			eq(5,sizeof(C(TestDaoFind)->find_all()));
			foreach(C(TestDaoFind)->find(Q::eq("value","abc")) as $obj){
				eq("abc",$obj->value());
			}

			$paginator = new Paginator(1,2);
			eq(1,sizeof($result = C(TestDaoFind)->find_all(Q::neq("value","abc"),$paginator)));
			eq("ghi",$result[0]->value());
			eq(4,$paginator->total());

			$i = 0;
			foreach(C(TestDaoFind)->find(
					Q::eq("value","abc"),
					Q::or_block(
						Q::eq("id",2),
						Q::or_block(
							Q::eq("value","ghi")
						)
					),
					Q::neq("value","aaa")
				) as $obj){
				$i++;
			}
			eq(3,$i);

			$list = array("abc","def","ghi","jkl","aaa");
			$i = 0;
			foreach(C(TestDaoFind)->find() as $obj){
				eq($list[$i],$obj->value());
				eq("2008/12/24 10:00:00",$obj->fmUpdated());
				$i++;
			}
			foreach(C(TestDaoFind)->find(Q::eq("value","AbC",Q::IGNORE)) as $obj){
				eq("abc",$obj->value());
			}
			foreach(C(TestDaoFind)->find(Q::neq("value","abc")) as $obj){
				neq("abc",$obj->value());
			}
			try{
				C(TestDaoFind)->find(Q::eq("value_error","abc"));
				eq(false,true);
			}catch(Exception $e){
				eq(true,true);
			}
			try{
				$dao = new TestDaoFind();
				$dao->find(Q::eq("value_error","abc"));
				eq(false,true);
			}catch(Exception $e){
				eq(true,true);
			}

			$i = 0;
			foreach(C(TestDaoFind)->find(Q::startswith("value,value2",array("aa"))) as $obj){
				$i++;
				eq("aaa",$obj->value());
			}
			eq(1,$i);

			$i = 0;
			foreach(C(TestDaoFind)->find(Q::endswith("value,value2",array("c"))) as $obj){
				eq("abc",$obj->value());
				$i++;
			}
			eq(1,$i);

			$i = 0;
			foreach(C(TestDaoFind)->find(Q::contains("value,value2",array("b"))) as $obj){
				eq("abc",$obj->value());
				$i++;
			}
			eq(1,$i);

			$i = 0;
			foreach(C(TestDaoFind)->find(Q::endswith("value,value2",array("C"),Q::NOT|Q::IGNORE)) as $obj){
				neq("abc",$obj->value());
				$i++;
			}
			eq(4,$i);


			$i = 0;
			foreach(C(TestDaoFind)->find(Q::in("value",array("abc"))) as $obj){
				eq("abc",$obj->value());
				$i++;
			}
			eq(1,$i);

			foreach(C(TestDaoFind)->find(Q::match("value=abc")) as $obj){
				eq("abc",$obj->value());
			}
			foreach(C(TestDaoFind)->find(Q::match("value=!abc")) as $obj){
				neq("abc",$obj->value());
			}
			foreach(C(TestDaoFind)->find(Q::match("abc")) as $obj){
				eq("abc",$obj->value());
			}
			foreach(C(TestDaoFind)->find(Q::neq("value","abc"),new Paginator(1,3),Q::order("-id")) as $obj){
				eq("ghi",$obj->value());
			}
			foreach(C(TestDaoFind)->find(Q::neq("value","abc"),new Paginator(1,3),Q::order("id")) as $obj){
				eq("jkl",$obj->value());
			}
			foreach(C(TestDaoFind)->find(Q::neq("value","abc"),new Paginator(1,2),Q::order("order,-id")) as $obj){
				eq("aaa",$obj->value());
			}
		 */
		/***
			# ref_test
			create_tmp_table("test_1","test_dao_find_ref_1",array("id1"=>"serial","value1"=>"string"));
			create_tmp_table("test_1","test_dao_find_ref_2",array("id2"=>"serial","id1"=>"number","value2"=>"string"));
			ACTIVE_TABLE("test_dao_find_ref_1","test_1","value1=aaa")->save();
			ACTIVE_TABLE("test_dao_find_ref_2","test_1","id1=1,value2=bbb")->save();

			class TestDaoFindRef2 extends Dao{
				static protected $__id2__ = "type=serial";
				static protected $__id1__ = "type=number";
				static protected $__value1__ = "cond=id1(test_dao_find_ref_1.id1)";
				protected $id2;
				protected $id1;
				protected $value1;
			}
			$result = C(TestDaoFindRef2)->find_all();
			eq(1,sizeof($result));
			eq("aaa",$result[0]->value1());

			create_tmp_table("test_1","test_dao_find_ref_3",array("id3"=>"serial","id2"=>"number"));
			ACTIVE_TABLE("test_dao_find_ref_3","test_1","id2=1")->save();

			class TestDaoFindRef3 extends Dao{
				static protected $__id3__ = "type=serial";
				static protected $__id2__ = "type=number";
				static protected $__value1__ = "cond=id2(test_dao_find_ref_2.id2, test_dao_find_ref_2.id1, test_dao_find_ref_1.id1)";
				static protected $__value2__ = "cond=id2(test_dao_find_ref_2.id2)";
				protected $id3;
				protected $id2;
				protected $value1;
				protected $value2;
			}
			$result = C(TestDaoFindRef3)->find_all();
			eq(1,sizeof($result));
			eq("aaa",$result[0]->value1());
			eq("bbb",$result[0]->value2());
		*/
		/***
			# has_test
			create_tmp_table("test_1","test_dao_find_has_1",array("id1"=>"serial","value1"=>"string"));
			create_tmp_table("test_1","test_dao_find_has_2",array("id2"=>"serial","id1"=>"number"));
			ACTIVE_TABLE("test_dao_find_has_1","test_1","value1=aaa")->save();
			ACTIVE_TABLE("test_dao_find_has_2","test_1","id1=1")->save();

			class TestDaoFindHas1 extends Dao{
				static protected $__id1__ = "type=serial";
				protected $id1;
				protected $value1;
			}
			class TestDaoFindHas2 extends Dao{
				static protected $__id2__ = "type=serial";
				static protected $__ref1__ = "type=TestDaoFindHas1,cond=id1()id1";
				protected $id2;
				protected $ref1;
			}
			$result = C(TestDaoFindHas2)->find_all();
			eq(1,sizeof($result));
			eq(true,$result[0]->ref1() instanceof TestDaoFindHas1);
			eq("aaa",$result[0]->ref1()->value1());
		 */
	}
	/**
	 * 検索結果をひとつ取得する
	 *
	 * @return Dao
	 */
	final public function find_get(){
		$this->verify_class_access("call C(DAO_CLASS)->find_one()");
		$args = func_get_args();
		$args[] = new Paginator(1,1);
		$result = null;

		$it = call_user_func_array(array($this,"find"),$args);
		foreach($it as $p){
			$result = $p;
			break;
		}
		if($result === null) throw new Exception("not found");
		return $result;
		/***
			create_tmp_table("test_1","test_dao_find_get",array("id"=>"serial","value"=>"string"));
			ACTIVE_TABLE("test_dao_find_get","test_1","value=aaa")->save();
			ACTIVE_TABLE("test_dao_find_get","test_1","value=bbb")->save();
			ACTIVE_TABLE("test_dao_find_get","test_1","value=ccc")->save();
			class TestDaoFindGet extends Dao{
				static protected $__id__ = "type=serial";
				protected $id;
				protected $value;
			}
			eq("aaa",C(TestDaoFindGet)->find_get()->value());
			eq("aaa",C(TestDaoFindGet)->find_get()->value());
		 */
	}
	/**
	 * 検索を実行する
	 *
	 * @return StatementIterator
	 */
	final public function find(){
		$this->verify_class_access("call C(DAO_CLASS)->find()");
		$args = func_get_args();
		$query = new Q();

		call_user_func_array(array($query,"add"),$args);
		if(!$query->isOrder_by()){
			foreach($this->primary_columns() as $column) $query->order($column->name());
		}
		$paginator = $query->paginator();
		if($paginator instanceof Paginator) $paginator->total(call_user_func_array(array($this,"find_count"),$query->arAnd_block()));
		$sql = $this->call_module("select_sql",$this,$query,$paginator);
		$statement = $this->prepare($sql->sql);
		$statement->execute($sql->vars);
		$errors = $statement->errorInfo();
		if(sizeof($errors) == 3) throw new LogicException("[".$errors[1]."] ".$errors[2]);
		return new StatementIterator($this,$statement);
	}
	
	/**
	 * コミットする
	 */
	final public function commit(){
		$this->verify_class_access("call C(DAO_CLASS)->commit()");
		$this->connection()->commit();
	}
	/**
	 * ロールバックする
	 */
	final public function rollback(){
		$this->verify_class_access("call C(DAO_CLASS)->rollback()");
		$this->connection()->rollback();
	}
	/**
	 * DBから削除する
	 */
	final public function delete(){
		$this->__before_delete__();
		$sql = $this->call_module("delete_sql",$this);
		$this->update_query($sql->sql,$sql->vars);
		$this->__after_delete__();
		/***
			create_tmp_table("test_1","test_dao_delete",array("id"=>"serial","value"=>"string"));
			class TestDaoDelete extends Dao{
				static protected $__id__ = "type=serial";
				protected $id;
				protected $value;
			}
			eq(0,C(TestDaoDelete)->find_count());
			R(new TestDaoDelete("value=abc"))->save();
			R(new TestDaoDelete("value=def"))->save();
			R(new TestDaoDelete("value=ghi"))->save();

			eq(3,C(TestDaoDelete)->find_count());
			$obj = new TestDaoDelete("id=1");
			$obj->delete();
			eq(2,C(TestDaoDelete)->find_count());
			$obj = new TestDaoDelete("id=3");
			$obj->delete();
			eq(1,C(TestDaoDelete)->find_count());
			eq("def",C(TestDaoDelete)->find_get()->value());
		 */
	}
	final public function save(){
		try{
			$query = array();
			foreach($this->primary_columns() as $column){
				$var = $this->{$column->name()}();
				if(empty($var)) throw new InvalidArgumentException();
				$query[] = Q::eq($column->name(),$var);
			}
			if(0 === call_user_func_array(array(C($this->_class_),"find_count"),$query)) throw new InvalidArgumentException();

			$this->__before_save__();
			$this->__before_update__();
			$this->save_verify();
			$sql = $this->call_module("update_sql",$this);
			$this->update_query($sql->sql,$sql->vars);
			$this->__after_update__();
			$this->__after_save__();
			$this->sync();
		}catch(InvalidArgumentException $e){
			$this->__before_save__();
			$this->__before_create__();
			$this->save_verify();
			$sql = $this->call_module("create_sql",$this);
			$this->update_query($sql->sql,$sql->vars);
			if($sql->id !== null){
				$result = $this->update_query($this->call_module("last_insert_id_sql"));
				$this->{$sql->id}($result[0]);
			}
			$this->__after_create__();
			$this->__after_save__();
			$this->sync();
		}
		return $this;
	}
	/**
	 * DBの値と同じにする
	 * @return Dao
	 */
	final public function sync(){
		$query = array();
		foreach($this->primary_columns() as $column){
			$query[] = Q::eq($column->name(),$this->{$column->name()}());
		}
		$this->cp(call_user_func_array(array(C($this->_class_),"find_get"),$query));
		return $this;
	}
	/**
	 * find_pageでのデフォルトのソート対象
	 *
	 * @return string
	 */
	protected function __page_order__(){
		$columns = $this->primary_columns();
		if(empty($columns)) $columns = $this->self_columns();
		$column = array_shift($columns);
		return "-".$column->name();
	}
	public function find_page($query,Paginator $paginator,$order,$porder=null){
		return C($this)->find_all($paginator,Q::match($query),Q::select_order($order,$porder),Q::order($this->__page_order__()));
	}
	public function set_model($vars){
		$this->cp($vars);
	}
	public function values(){
		$result = array();
		foreach($this->access_members() as $name => $value){
			$result[$name] = new Object(array("label"=>gettext($this->a($name,"label",$name)),
												"value"=>$this->{"fm".ucfirst($name)}(),
												"type"=>$this->a($name,"type"),
												"primary"=>$this->a($name,"primary"),
								));
		}
		return $result;
	}
}
?>