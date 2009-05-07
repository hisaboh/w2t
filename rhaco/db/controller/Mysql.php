<?php
Rhaco::import("db.DbController");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Mysql extends DbController{
	public function dsn($name,$host,$port,$user,$password,$sock){
		if(empty($sock)) return sprintf("mysql:dbname=%s;host=%s;port=%d",$name,$host,((empty($port) ? 3306 : $port)));
		return sprintf("mysql:dbname=%s;unix_socket=%s",$name,$sock);
	}
	public function create_table($name,array $columns){
		$sql = "create table `".$name."`(";
		$columndef = array();
		foreach($columns as $name => $type){
			switch($type){
				case "serial": $columndef[] = "`".$name."` int auto_increment primary key"; break;
				case "string": $columndef[] = "`".$name."` varchar(255)"; break;
				case "number": $columndef[] = "`".$name."` int"; break;
				case "timestamp": $columndef[] = "`".$name."` timestamp"; break;
				default: throw new Exception("undefined type ".$type);
			}
		}
		$sql .= implode(",",$columndef);
		$sql .= " ) engine = INNODB";
		return $sql;
	}
	public function show_tables($name){
		return (object)array("sql"=>"SHOW TABLES",
							"field_name"=>"Tables_in_".$name
						);
	}
	public function show_columns_sql(Dao $obj){
		return "show columns from ".$obj->table();
	}
	public function last_insert_id_sql(){
		return "select last_insert_id() as last_insert_id;";
	}
	public function parse_columns(PDOStatement $it){
		$results = array();
		foreach($it as $value){
			$type = $value["Type"];
			$name = $value["Field"];
			$size = $max_digits = $decimal_places = null;

			if(!ctype_alpha($name[0])) $name = "c_".$name;
			if($value["Extra"] == "auto_increment"){
				$type = "serial";
			}else if(preg_match("/^enum\((.+)\)$/",$type,$match)){
				$type = "choice(".$match[1].")";
			}else if(preg_match("/^(.+)\(([\d,]+)\)/",$type,$match)){
				$type = $match[1];
				$size = $match[2];

				if(strpos($size,",") !== false){
					list($max_digits,$decimal_places) = $size;
					$size = null;
				}
			}
			switch($type){
				case "varchar":
					$type = "string"; break;
				case "longblob":
				case "tinyblob":
					$type = "text"; break;
				case "double":
				case "int":
				case "bigint":
				case "tinyint":
				case "smallint":
				case "decimal":
					$type = "number"; break;
				case "datetime":
					$type = "timestamp"; break;
			}
			$annotation = "type=".$type.","
							."column=".$value["Field"].","
							.(($value["Key"] == "PRI") ? "pk=true," : "")
							.(($size !== null) ? "length=".intval($size)."," : "")
							.(($max_digits !== null) ? "max_digits=".intval($max_digits)."," : "")
							.(($decimal_places !== null) ? "decimal_places=".intval($decimal_places)."," : "")
							;
			$results[] = (object)array("name"=>$name,
										"default"=>$value["Default"],
										"annotation"=>$annotation);
		}
		return $results;
	}
}
?>