<?php
import("core.Tag");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class TwitpicResponse extends Object{
	static protected $__statusid__ = "type=number";
	static protected $__userid__ = "type=number";
	protected $statusid;
	protected $userid;
	protected $mediaid;
	protected $mediaurl;

	static public function parse($response){
		if(Tag::setof($tag,$response,"err")) throw new Exception($tag->inParam("msg"));

		if(Tag::setof($tag,$response,"rsp")){
			$obj = new self();
			return $obj->cp($tag->hash());
		}
		throw new InvalidArgumentException();
	}
}
?>