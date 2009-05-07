<?php
class FlipClipTag extends Object{
	protected $term;
	protected $scheme;
	protected $label;
	
	protected function __new__($term,$scheme,$label){
		$this->term = $term;
		$this->scheme = $scheme;
		$this->label = $label;
	}
}
?>