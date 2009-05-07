<?php
import("core.Tag");

class NtvNewsResult extends Object {
	protected $title;
	protected $url;
	protected $date;
	protected $summary;
	protected $thumbnail_url;
	
	static public function parse_list($response){
		$result = array();
		if(Tag::setof($tag,$response,"error")) throw new Exception($tag->f("message.value()"));

		if(Tag::setof($tag,$response,"news")){
			foreach($tag->in("article") as $program){
				$obj = new self();
				$result[] = $obj->cp($program->hash());
			}
		}
		return $result;
	}
}
?>