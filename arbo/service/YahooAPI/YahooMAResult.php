<?php
import("core.Tag");
import("core.Lib");
module("YahooMAWord");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class YahooMAResult extends Object{
	static protected $__total_count__ = "type=number";
	static protected $__filtered_count__ = "type=number";
	static protected $__noun__ = "type=string{},set=false";
	static protected $__verb__ = "type=string{},set=false";
	protected $total_count;
	protected $filtered_count;
	protected $ma = array();
	protected $uniq = array();

	/** 名詞 */
	protected $noun = array();
	/** 動詞 */
	protected $verb = array();

	static public function parse($response){
		if(Tag::setof($tag,$response,"Error")){
			throw new Exception($tag->f("Message.value()"));
		}
		$obj = new self();
		if(Tag::setof($tag,$response,"ResultSet")){
			$ma_result = $tag->f("ma_result");
			$obj->total_count($ma_result->f("total_count.value()"));
			$obj->filtered_count($ma_result->f("filtered_count.value()"));
			$obj->ma(YahooMAWord::parse($ma_result->f("word_list")));
			$obj->uniq(YahooMAWord::parse($tag->f("uniq_result")->f("word_list")));
		}
		return $obj;
	}
	/**
	 * すべて平仮名で読みを返す
	 *
	 * @return string
	 */
	public function hiragana(){
		$result = "";
		foreach($this->arMa() as $w) $result .= $w->hiragana();
		return $result;
	}
	/**
	 * すべてカタカナで読みを返す
	 *
	 * @return string
	 */
	public function katakana(){
		$result = "";
		foreach($this->arMa() as $w) $result .= $w->katakana();
		return $result;
	}
	/**
	 * すべてローマ字で返す
	 *
	 * @return string
	 */
	public function roman(){
		$result = "";
		foreach($this->arMa() as $w) $result .= $w->roma();
		return $result;
	}
	protected function setMa($list){
		$this->ma = (is_array($list)) ? $list : array();

		foreach($this->ma as $ma){
			if($ma->isNoun()){
				$this->noun[$ma->surface()] = $ma->reading();
			}else if($ma->isVerb()){
				$this->verb[$ma->surface()] = $ma->reading();
			}
		}
	}
	protected function setUniq($list){
		$this->uniq = (is_array($list)) ? $list : array();
	}
}
?>