<?php
class CrudTools{
	private $path;
	private $data_name;
	
	public function __construct($data_name){
		$this->data_name = $data_name;
		$this->path = Rhaco::def("generic.Crud@url",$_SERVER["SCRIPT_NAME"]);
	}
	public function link_find(){
		return $this->path."/".$this->data_name;
	}
	public function link_drop(){
		return $this->path."/".$this->data_name."/drop";
	}
	public function link_create(){
		return $this->path."/".$this->data_name."/create";
	}
	public function link_detail($object){
		return $this->path."/".$this->data_name."/detail?".Templf::query($object->primary_values(),'primary');
	}
	public function link_update($object){
		return $this->path."/".$this->data_name."/update?".Templf::query($object->primary_values(),'primary');
	}
	public function form($type,$name,$value){
		switch($type){
			case "serial": return sprintf('<input name="%s" type="hidden" value="%s" />%s',$name,$value,$value);
			case "text": return sprintf('<textarea name="%s">%s</textarea>',$name,$value);
			default: return sprintf('<input name="%s" type="text" value="%s" />',$name,$value);
		}
	}
}
?>