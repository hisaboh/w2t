<?php
Rhaco::import("core.Date");
Rhaco::import("db.Dao");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
abstract class DbController{
	protected $from = array();

	public function dsn($name,$host,$port,$user,$password,$sock){
		throw new Exception("undef");
	}
	public function create_table($name,array $columns){
		throw new Exception("undef");
	}
	public function drop_table($name){
		return "drop table `".$name."`";
	}
	public function show_tables($name){
		throw new Exception("undef");
	}
	public function last_insert_id_sql(){
		throw new Exception("undef");
	}
	public function show_columns_sql(Dao $dao){
		throw new Exception("undef");
	}

	public function create_sql(Dao $dao){
		$insert = $vars = array();
		$autoid = null;
		foreach($dao->self_columns() as $column){
			$insert[] = "`".$column->column()."`";
			$vars[] = $this->update_value($dao,$column->name());
			if($column->auto()) $autoid = $column->name();
		}
		return (object)array(
					"sql"=>"insert `".$column->table()."`(".implode(",",$insert).") values (".implode(",",array_fill(0,sizeof($insert),"?")).");"
					,"vars"=>$vars
					,"id"=>$autoid
				);
	}
	public function update_sql(Dao $dao){
		$where = $update = $wherevars = $updatevars = array();
		foreach($dao->primary_columns() as $column){
			$where[] = "`".$column->column()."` = ?";
			$wherevars[] = $this->update_value($dao,$column->name());
		}
		if(empty($where)) throw new Exception("not found primary");
		foreach($dao->self_columns() as $column){
			if(!$column->primary()){
				$update[] = "`".$column->column()."` = ?";
				$updatevars[] = $this->update_value($dao,$column->name());
			}
		}
		$vars = array_merge($updatevars,$wherevars);
		return (object)array(
						"sql"=>"update `".$column->table()."` set ".implode(",",$update)." where ".implode("and",$where)
						,"vars"=>$vars
					);
	}
	protected function update_value(Dao $obj,$name){
		return $this->column_value($obj->a($name,"type"),$obj->{$name}());
	}
	protected function column_value($type,$value){
		if($value === null) return null;
		try{
			switch($type){
				case "timestamp": return date("Y/m/d H:i:s",Date::parse_date($value));
				case "date": return date("Y/m/d",Date::parse_date($value));
			}
		}catch(Exception $e){}
		return $value;
	}
	public function delete_sql(Dao $dao){
		$where = $vars = array();
		foreach($dao->primary_columns() as $column){
			$where[] = "`".$column->column()."` = ?";
			$vars[] = $dao->{$column->name()}();
		}
		if(empty($where)) throw new Exception("not primary");
		return (object)array(
						"sql"=>"delete from `".$column->table()."` where ".implode("and",$where)
						,"vars"=>$vars
					);
	}


	protected function whice_columns(array $whice,array $self_columns){
		$result = array();
		foreach($whice as $column_str){
			$result[] = $this->column($column_str,$self_columns);
		}
		return $result;
	}
	protected function which_aggregator_sql($exe,Dao $dao,$target_name,$gorup_name,Q $query){
		$select = $from = $order = array();
		$target_column = $group_column = null;
		$self_columns = $dao->self_columns();
		if(empty($target_name)){
			$primary_columns = $dao->primary_columns();
			if(!empty($primary_columns)) $target_column = current($primary_columns);
			if(empty($target_name)) $target_column = current($self_columns);
		}else{
			$target_column = $this->column($target_name,$self_columns);
		}
		if(!empty($gorup_name)){
			$group_column = $this->column($gorup_name,$self_columns);
			$select[] = $group_column->table_alias().".`".$group_column->column()."` key_column";
		}
		foreach($dao->columns() as $column){
			$from[$column->table_alias()] = $column->table()." ".$column->table_alias();
		}
		foreach($query->order_by() as $q){
			foreach($q->arArg1() as $column_str){
				$order_name = ($column_str === $target_name) ? "target_column" : (($column_str === $gorup_name) ? "key_column" : null);
				if($order_name === null) throw new Exception("invalid order column ".$column_str);
				$order[] = $order_name.(($q->type() == Q::ORDER_ASC) ? " asc" : " desc");
			}
		}
		list($where_sql,$where_vars) = $this->where_sql($dao,$query,$self_columns,$this->where_cond_columns($dao,$from));
		return (object)array("sql"=>("select ".$exe."(".$target_column->table_alias().".`".$target_column->column()."`) target_column"
										.(empty($select) ? "" : ",".implode(",",$select))
										." from ".implode(",",$from)
										.(empty($where_sql) ? "" : " where ".$where_sql)
										.(empty($order) ? "" : " order by ".implode(",",$order))
										.(empty($group_column) ? "" : " group by key_column")
									),
							"vars"=>$where_vars
				);
	}
	
	public function count_sql(Dao $dao,$target_column,$gorup_column,Q $query){
		return $this->which_aggregator_sql("count",$dao,$target_column,$gorup_column,$query);
	}
	public function sum_sql(Dao $dao,$target_column,$gorup_column,Q $query){
		return $this->which_aggregator_sql("sum",$dao,$target_column,$gorup_column,$query);
	}
	public function max_sql(Dao $dao,$target_column,$gorup_column,Q $query){
		return $this->which_aggregator_sql("max",$dao,$target_column,$gorup_column,$query);
	}
	public function min_sql(Dao $dao,$target_column,$gorup_column,Q $query){
		return $this->which_aggregator_sql("min",$dao,$target_column,$gorup_column,$query);
	}
	public function avg_sql(Dao $dao,$target_column,$gorup_column,Q $query){
		return $this->which_aggregator_sql("avg",$dao,$target_column,$gorup_column,$query);
	}
	public function distinct_sql(Dao $dao,$target_column,$gorup_column,Q $query){
		return $this->which_aggregator_sql("distinct",$dao,$target_column,$gorup_column,$query);
	}
	public function select_sql(Dao $dao,Q $query,$paginator){
		$select = $from = $order = array();
		$self_columns = $dao->self_columns();
		foreach($dao->columns() as $column){
			$select[] = $column->table_alias().".`".$column->column()."` ".$column->column_alias();
			$from[$column->table_alias()] = $column->table()." ".$column->table_alias();
		}
		foreach($query->order_by() as $q){
			foreach($q->arArg1() as $column_str){
				$order[] = $this->column($column_str,$self_columns)->column_alias().(($q->type() == Q::ORDER_ASC) ? " asc" : " desc");
			}
		}
		list($where_sql,$where_vars) = $this->where_sql($dao,$query,$self_columns,$this->where_cond_columns($dao,$from));
		return (object)array("sql"=>("select ".implode(",",$select)." from ".implode(",",$from)
										.(empty($where_sql) ? "" : " where ".$where_sql)
										.(empty($order) ? "" : " order by ".implode(",",$order))
										.(($paginator instanceof Paginator) ? sprintf(" limit %d,%d ",$paginator->offset(),$paginator->limit()) : "")
									),
							"vars"=>$where_vars
				);
	}
	protected function where_cond_columns(Dao $dao,array &$from){
		$conds = array();
		foreach($dao->conds() as $name => $columns){
			$conds[] = $columns[0]->table_alias().".`".$columns[0]->column()."`"
						." = "
						.$columns[1]->table_alias().".`".$columns[1]->column()."`";
			$from[$columns[0]->table_alias()] = $columns[0]->table()." ".$columns[0]->table_alias();
			$from[$columns[1]->table_alias()] = $columns[1]->table()." ".$columns[1]->table_alias();
		}
		return (empty($conds)) ? "" : implode(" and ",$conds);
	}
	protected function column($column_str,array $self_columns){
		if(!isset($self_columns[$column_str])) throw new Exception("undef ".$column_str);
		return $self_columns[$column_str];
	}
	protected function column_alias($column,Q $q){
		if($q->ignore_case()) return "upper(".$column->table_alias().".`".$column->column()."`".")";
		return $column->table_alias().".`".$column->column()."`";
	}
	protected function where_cond(Dao $dao,Q $q,array $self_columns){
		$and = $vars = array();
		foreach($q->arArg2() as $value){
			$or = array();

			foreach($q->arArg1() as $column_str){
				$column = $this->column($column_str,$self_columns);
				$column_alias = $this->column_alias($column,$q);

				switch($q->type()){
					case Q::EQ:  $column_alias .= " = ?"; break;
					case Q::NEQ: $column_alias .= " <> ?"; break;
					case Q::GT: $column_alias .= " > ?"; break;
					case Q::GTE: $column_alias .= " >= ?"; break;
					case Q::LT: $column_alias .= " < ?"; break;
					case Q::LTE: $column_alias .= " <= ?"; break;
					case Q::CONTAINS:
					case Q::START_WITH:
					case Q::END_WITH:
						$column_alias .= ($q->not() ? " not" : "")." like(?)";
						$value = (($q->type() == Q::CONTAINS || $q->type() == Q::END_WITH) ? "%" : "")
									.$value
									.(($q->type() == Q::CONTAINS || $q->type() == Q::START_WITH) ? "%" : "");
						break;
					case Q::IN:
						$column_alias .= ($q->not() ? " not" : "")." in(".substr(str_repeat("?,",sizeof($value)),0,-1).")";
						break;
				}
				$or[] = $column_alias;
				$column_type = $dao->a($column->name(),"type");
				if(is_array($value)){
					$values = array();
					foreach($value as $v) $values[] = $this->column_value($column_type,$v);
					$vars = array_merge($vars,$values);
				}else{
					$vars[] = $this->column_value($column_type,$value);
				}
			}
			$and[] = " (".implode(" or ",$or).") ";
		}
		return array(implode(" and ",$and),$vars);
	}
	protected function where_match(Q $q,array $self_columns){
		$query = new Q();
		foreach($q->arArg1() as $cond){
			if(strpos($cond,"=") !== false){
				list($column,$value) = explode("=",$cond);
				$not = (substr($value,0,1) == "!");
				$value = ($not) ? ((strlen($value) > 1) ? substr($value,1) : "") : $value;
				if($value === ""){
					$query->add(($not) ? Q::neq($column,"") : Q::eq($column,""));
				}else{
					$query->add(($not) ? Q::contains($column,$value,$q->param()|Q::NOT) : Q::contains($column,$value,$q->param()));
				}
			}else{
				$columns = array();
				foreach($self_columns as $column) $columns[] = $column->name();
				$query->add(Q::contains(implode(",",$columns),explode(" ",$cond),$q->param()));
			}
		}
		return $query;
	}

	protected function where_sql(Dao $dao,Q $q,array $self_columns,$require_where=null){
		if($q->isBlock()){
			$vars = $and = array();

			foreach($q->arAnd_block() as $qa){
				list($where,$var) = $this->where_sql($dao,$qa,$self_columns);
				if(!empty($where)){
					$and[] = $where;
					$vars = array_merge($vars,$var);
				}
			}
			$where_sql = empty($and) ? "" : "(".implode(" and ",$and).") ";
			foreach($q->arOr_block() as $or_block){
				foreach($or_block as $qa){
					list($where,$var) = $this->where_sql($dao,$qa,$self_columns);
					if(!empty($where)){
						$where_sql .= " or (".$where.") ";
						$vars = array_merge($vars,$var);
					}
				}
			}
			if(empty($where_sql)){
				$where_sql = $require_where;
			}else if(!empty($require_where)){
				$where_sql = "(".$require_where.") and (".$where_sql.")";
			}
			return array($where_sql,$vars);
		}
		if($q->type() == Q::MATCH){
			return $this->where_sql($dao,$this->where_match($q,$self_columns),$self_columns);
		}
		return $this->where_cond($dao,$q,$self_columns);
	}
}
?>