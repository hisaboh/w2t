<?php
import("core.Text");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class WassrTodo extends Object{
	protected $body;
	protected $todo_rid;
	
	static public function parse_list($response){
		$result = array();
		$res = Text::parse_json($response);
		foreach($res as $re){
			$obj = new self();
			$result[] = $obj->cp($re);
		}
		return $result;
	}
}
?>