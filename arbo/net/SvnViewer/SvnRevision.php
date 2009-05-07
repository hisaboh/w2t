<?php
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class SvnRevision extends Object{
	static protected $__id__ = "type=number";
	static protected $__date__ = "type=timestamp";
	static protected $__added__ = "type=string[]";
	static protected $__modified__ = "type=string[]";
	static protected $__deleted__ = "type=string[]";
	static protected $__file__ = "type=string,set=false";
	protected $id;
	protected $author;
	protected $date;
	protected $comment;

	protected $added = array();
	protected $modified = array();
	protected $deleted = array();
	protected $url;
	protected $file;

	protected function __new__($id,$author,$comment,$date,$url=null){
		$this->id($id);
		$this->author(trim($author));
		$this->comment(trim($comment));
		$this->date($date);
		$this->url($url);
	}
	protected function __str__(){
		return "rev: ".$this->id()."\n"
				.trim($this->comment())."\n\n"
				.$this->file." (".$this->count().")";
	}
	public function count(){
		return (sizeof($this->added) + sizeof($this->modified) + sizeof($this->deleted));
	}
	public function path($action,$file){
		$file = str_replace(array("\r\n","\r","\n"),"",$file);
		if(substr($file,0,1) == "/") $file = substr($file,1);
		if(!isset($this->file)) $this->file = $file;

		switch (strtolower($action)){
			case "a":
			case "add":
			case "added":
				$this->added[] = $file;
				break;
			case "m":
			case "modify":
			case "modified":
				$this->modified[] = $file;
				break;
			case "d":
			case "delete":
			case "deleted":
				$this->deleted[] = $file;
		}
	}
}
?>