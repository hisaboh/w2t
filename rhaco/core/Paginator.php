<?php
Rhaco::import("core.Http");
/**
 * ページを管理するモデル
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Paginator extends Object{
	static protected $___wh_attr___ = "which";
	static protected $__offset__ = "type=number";
	static protected $__limit__ = "type=number";
	static protected $__current__ = "type=number";
	static protected $__total__ = "type=number";
	static protected $__first__ = "type=number,set=false";
	static protected $__last__ = "type=number,set=false";
	static protected $__vars__ = "type=variable{}";
	protected $offset;
	protected $limit;
	protected $current;
	protected $total;
	protected $first = 1;
	protected $last;
	protected $vars = array();

	protected function __new__($paginate_by=20,$current=1,$total=0){
		$this->limit($paginate_by);
		$this->total($total);
		$this->current($current);
		/***
			$paginator = new Paginator(10);
			eq(10,$paginator->limit());
			eq(1,$paginator->first());
			$paginator->total(100);
			eq(100,$paginator->total());
			eq(10,$paginator->last());
			eq(1,$paginator->whFirst(3));
			eq(3,$paginator->whLast(3));

			$paginator->current(3);
			eq(20,$paginator->offset());
			eq(true,$paginator->isNext());
			eq(true,$paginator->isPrev());
			eq(4,$paginator->next());
			eq(2,$paginator->prev());
			eq(1,$paginator->first());
			eq(10,$paginator->last());
			eq(2,$paginator->whFirst(3));
			eq(4,$paginator->whLast(3));

			$paginator->current(1);
			eq(0,$paginator->offset());
			eq(true,$paginator->isNext());
			eq(false,$paginator->isPrev());

			$paginator->current(6);
			eq(5,$paginator->whFirst(3));
			eq(7,$paginator->whLast(3));

			$paginator->current(10);
			eq(90,$paginator->offset());
			eq(false,$paginator->isNext());
			eq(true,$paginator->isPrev());
			eq(8,$paginator->whFirst(3));
			eq(10,$paginator->whLast(3));
		 */
	}
	protected function __cp__($var){
		if(is_array($var)){
			$this->vars = $this->vars + $var;
			return true;
		}
		return parent::__cp__($var);
	}
	public function next(){
		return $this->current + 1;
		/***
			$paginator = new Paginator(10,1,100);
			eq(2,$paginator->next());
		*/
	}
	public function prev(){
		return $this->current - 1;
		/***
			$paginator = new Paginator(10,2,100);
			eq(1,$paginator->prev());
		*/
	}
	public function isNext(){
		return ($this->last > $this->current);
		/***
			$paginator = new Paginator(10,1,100);
			eq(true,$paginator->isNext());
			$paginator = new Paginator(10,9,100);
			eq(true,$paginator->isNext());
			$paginator = new Paginator(10,10,100);
			eq(false,$paginator->isNext());
		*/
	}
	public function isPrev(){
		return ($this->current > 1);
		/***
			$paginator = new Paginator(10,1,100);
			eq(false,$paginator->isPrev());
			$paginator = new Paginator(10,9,100);
			eq(true,$paginator->isPrev());
			$paginator = new Paginator(10,10,100);
			eq(true,$paginator->isPrev());
		*/
	}
	public function query($current){
		$this->vars("page",$current);
		return Http::query($this->arVars());
		/***
			$paginator = new Paginator(10,1,100);
			eq("page=3&",$paginator->query(3));
		 */
	}
	protected function setCurrent($value){
		$value = intval($value);
		$this->current = ($value === 0) ? 1 : $value;
		$this->offset = $this->limit * round(abs($this->current - 1));
	}
	protected function setTotal($total){
		$this->total = intval($total);
		$this->last = ($this->total == 0) ? 0 : intval(ceil($this->total / $this->limit));
	}
	protected function __wh_attr__($args,$param){
		return null;
	}
	protected function verifyFirst($paginate){
		return ($this->whichFirst($paginate) !== $this->first);
	}
	protected function verifyLast($paginate){
		return ($this->whichLast($paginate) !== $this->last());
	}
	protected function whichFirst($paginate=null){
		if($paginate === null) return $this->first;
		$paginate = $paginate - 1;
		$first = ($this->current > ($paginate/2)) ? @ceil($this->current - ($paginate/2)) : 1;
		$last = ($this->last > ($first + $paginate)) ? ($first + $paginate) : $this->last;
		return (($last - $paginate) > 0) ? ($last - $paginate) : $first;
	}
	protected function whichLast($paginate=null){
		if($paginate === null) return $this->last;
		$paginate = $paginate - 1;
		$first = ($this->current > ($paginate/2)) ? @ceil($this->current - ($paginate/2)) : 1;
		return ($this->last > ($first + $paginate)) ? ($first + $paginate) : $this->last;
	}
}