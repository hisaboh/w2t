<?php
import("core.Tag");
import("core.Http");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class HttpSpider extends Http implements Iterator{
	static protected $__replace__ = "type=string{}";
	static protected $__exclude__ = "type=string{}";
	static protected $__ignore__ = "type=string[]";
	static protected $__revisit__ = "type=boolean";
	protected $ignore = array(); // 除外するタグ
	protected $replace = array(); // 置換文字列
	protected $exclude = array(); // 指定の拡張なら中身を確認しない
	protected $revisit = false;
	protected $current_info;
	private $limitation;
	private $hierarchy = 0;
	private $buffer = array();
	private $visit = array();
	private $exceptions = array();

	/**
	 * 探索する
	 *
	 * @param string $url 開始URL
	 * @param string $limitation 探索対象アドレス
	 * @return $this
	 */
	public function crawl($url,$limitation=null){
		$this->limitation = $limitation;
		$this->exceptions = $this->buffer = $this->visit = array();
		$this->hierarchy = 0;
		return $this->get_page($url);
		/***
			$spider = new HttpSpider();
			$i = 0;
			foreach($spider->crawl("http://rhaco.org","http://rhaco.org") as $site){
				$i++;
			}
			eq(true,(0 < $i));
		 */
	}
	public function isParent(){
		return (isset($this->current_info) && isset($this->current_info->parent_info));
	}
	public function exceptions(){
		return $this->exceptions;
	}
	private function get_page($url){
		try{
			$this->do_get($url,false);
			$this->hierarchy++;
			$this->buffer[$this->hierarchy] = array();
			$this->body = Tag::uncomment($this->body);
			$bool = true;

			if(!empty($this->exclude)){
				foreach($this->exclude as $ext){
					if(preg_match("/\.".$ext."$/i",preg_replace("/[\?#].*$/","",$this->url()))){
						$bool = false;
						break;
					}
				}
			}
			if($bool){
				if(!empty($this->ignore)){
					foreach($this->ignore as $ignore){
						foreach(Tag::anyhow($this->body)->in($ignore) as $tag){
							$this->body = str_replace($tag->plain(),"",$this->body);
						}
					}
				}
				foreach(Tag::anyhow($this->body)->in(array("a","link","script","img")) as $a){
					$href = $a->inParam("href",$a->inParam("src"));
	
					if(!empty($href)){
						$url_info = self::parse_url($href,$this->url());
						$url_info->value = $a->value();
						$url = $url_info->full_url();
	
						if($this->get_url($url)){
							$url_info->full_url($url);
							$url_info->label = $a->value();
							$url_info->parent_info = $this->current_info;
			
							$this->visit[$url] = $url;
							$this->buffer[$this->hierarchy][] = $url_info;
						}
					}
				}
			}
		}catch (Exception $e){
			$this->exceptions[] = $e;
		}
		return $this;
	}
	/**
	 * ページタイトル
	 *
	 * @return string
	 */
	public function title(){
		return Tag::anyhow($this->body)->f("title.value()");
	}
	protected function __str__(){
		return "status:		".$this->status()."\n".
				"url:		".$this->url()."\n".
				"title:		".$this->title()."\n".
				"referer:	".(($this->isParent()) ? $this->current_info()->parent_info()->full_url() : "")."\n".
				"href		".$this->current_info()->url()."\n".
				"label:		".$this->current_info()->label()."\n";
	}
	public function rewind(){
		if($this->hierarchy > 0 && !empty($this->buffer[$this->hierarchy])){
			$this->current_info = array_shift($this->buffer[$this->hierarchy]);
		}
	}
	public function key(){
		return $this->current_info->full_url();
	}
	public function current(){
		return $this->get_page($this->current_info->full_url(),$this->limitation);
	}
	public function valid(){
		return ($this->current_info !== null && $this->hierarchy > 0 && !empty($this->buffer));
	}
	public function next(){
		if($this->hierarchy > 0){
			if(!empty($this->buffer[$this->hierarchy])){
				$this->current_info = array_shift($this->buffer[$this->hierarchy]);
			}else{
				$this->hierarchy--;
				$this->next();
			}
		}
	}
	private function get_url(&$url){
		if($this->status() !== 200) return false;
		foreach($this->replace as $dec => $rep) $url = str_replace($dec,$rep,$url);
		return (($this->revisit || !isset($this->visit[$url])) && (!isset($this->limitation) || strpos($url,$this->limitation) === 0));
	}
}
?>