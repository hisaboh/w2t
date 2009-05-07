<?php
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Column extends Object{
	static protected $__primary__ = "type=boolean";
	static protected $__self__ = "type=boolean";
	static protected $__auto__ = "type=boolean";
	static protected $__base__ = "type=boolean";
	protected $name;
	protected $column;
	protected $column_alias;
	protected $table;
	protected $table_alias;
	protected $primary = false;
	protected $auto = false;
	protected $base = true;
	
	static public function cond_instance($column,$column_alias,$table,$table_alias){
		$self = new self();
		$self->column($column);
		$self->column_alias($column_alias);
		$self->table($table);
		$self->table_alias($table_alias);
		$self->base(false);
		return $self;
	}
}
?>