<?php
Rhaco::import("db.Dao");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
abstract class ActiveMapper extends Dao{
	static private $ACTIVE_MAPPER;
	static private $ACTIVE_TABLE_NAME;

	static public function __funcs__(){
		/**
		 * @return Dao
		 */
		function ACTIVE_TABLE($name,$db=null,$dict=null){
			$class_name = ActiveMapper::active_table_name($name,$db);
			return new $class_name($dict);
		}
	}
	static public function active_table_name($name,$db=null){
		if(empty($db)){
			foreach(Db::connections() as $key => $con){
				if(in_array($name,$con->tables())){
					$db = $key;
					break;
				}
			}
		}
		if(!isset(self::$ACTIVE_TABLE_NAME[$db][$name])){
			$class_name = uniqid("AT_");
			eval('class '.$class_name.' extends ActiveMapper{ protected $_database_ = "'.$db.'" ; protected $_table_ = "'.$name.'"; }');
			self::$ACTIVE_TABLE_NAME[$db][$name] = $class_name;
		}
		return self::$ACTIVE_TABLE_NAME[$db][$name];
		/***
			create_tmp_table("test_1","test_active_mapper_query",array("id"=>"serial","value"=>"string"));
			ACTIVE_TABLE("test_active_mapper_query","test_1","value=abc")->save();
			ACTIVE_TABLE("test_active_mapper_query","test_1","value=def")->save();
			C(ACTIVE_TABLE("test_active_mapper_query","test_1"))->commit();

			$list = array("abc","def");
			$i = 0;
			foreach(C(ACTIVE_TABLE("test_active_mapper_query","test_1"))->find() as $obj){
				eq($list[$i],$obj->value());
				$i++;
			}
		 */
	}
	protected function __new__(){
		$this->get_connection();
		if(!isset(self::$ACTIVE_MAPPER[$this->_class_])){
			$statement = $this->prepare($this->call_module("show_columns_sql",$this));
			$statement->execute();
			$errors = $statement->errorInfo();
			if(sizeof($errors) == 3) throw new LogicException("[".$errors[1]."] ".$errors[2]);
			self::$ACTIVE_MAPPER[$this->_class_] = $this->call_module("parse_columns",$statement);
		}
		foreach(self::$ACTIVE_MAPPER[$this->_class_] as $column){
			$this->{"__".$column->name."__"} = $column->annotation;
			$this->{$column->name} = $column->default;
		}
		$args = (func_num_args() == 1) ? func_get_arg(0) : null;
		$this->dict($args);
		$this->parse_column_annotation();
	}
}
?>