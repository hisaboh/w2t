<?php
import("core.Tag");

class NtvProgramResult extends Object {
	protected $title;
	protected $url;
	protected $airtime;
	protected $castlist;
	protected $synopsis;
	
	static public function parse_list($response){
		$result = array();
		if(Tag::setof($tag,$response,"error")) throw new Exception($tag->f("message.value()"));
		if(Tag::setof($tag,$response,"programs")){
			foreach($tag->in("program") as $program){
				$obj = new self();
				$result[] = $obj->cp($program->hash());
			}
		}
		return $result;
	}
}
?>