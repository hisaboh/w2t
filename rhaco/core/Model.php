<?php
Rhaco::import("core.Paginator");

interface Model{
	public function sync();
	public function delete();
	public function save();
	public function commit();
	public function rollback();
	public function find_page($query,Paginator $paginator,$order,$porder=null);
	
	public function set_model($vars);
	public function values();
}
?>