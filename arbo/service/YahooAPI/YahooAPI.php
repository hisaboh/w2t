<?php
import("core.Http");
import("core.Tag");
import("core.Text");
import("core.Lib");
module("YahooMAResult");
/**
 * YahppAPI
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 *
 */
class YahooAPI extends Http{
	protected $_api_key_name_ = "appid";

	protected function __new__($api_key=null){
		$this->set_api_key(isset($api_key) ? $api_key : def("arbo.service.YahooAPI@api_key"));
	}
	/**
	 * 日本語形態素解析
	 * filter (|区切り):
	 * 	1 : 形容詞
	 * 	2 : 形容動詞
	 * 	3 : 感動詞
	 * 	4 : 副詞
	 * 	5 : 連体詞
	 * 	6 : 接続詞
	 * 	7 : 接頭辞
	 * 	8 : 接尾辞
	 * 	9 : 名詞
	 * 	10 : 動詞
	 * 	11 : 助詞
	 * 	12 : 助動詞
	 * 	13 : 特殊（句読点、カッコ、記号など）
	 *
	 * @see http://developer.yahoo.co.jp/webapi/jlp/ma/v1/parse.html
	 * @param string $sentence
	 * @param string $filter
	 * @return YahooMAResult
	 */
	public function ma($sentence,$filter=null){
		if(!empty($filter)) $this->vars("ma_filter",$filter);
		$this->vars("sentence",$sentence);
		$this->vars("results","ma,uniq");
		$this->do_get("http://jlp.yahooapis.jp/MAService/V1/parse");
		return YahooMAResult::parse($this->body());
		/***
			try{
				$api = new YahooAPI();
				$result = $api->ma("hello");
				eq(true,($result instanceof YahooMAResult));
			}catch(Exception $e){
				eq(true,true);
			}
		 */
	}
}
