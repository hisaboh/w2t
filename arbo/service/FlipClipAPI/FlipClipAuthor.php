<?php
class FlipClipAuthor extends Object{
	protected $id;
	protected $name;
	protected $uri;
	
	protected function __new__($id,$name,$uri){
		$this->id = $id;
		$this->name = $name;
		$this->uri = $uri;
	}
}
?>