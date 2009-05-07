<?php
import("core.Http");
import("core.Tag");
import("core.Text");
import("core.Lib");
/**
 * GoogleAPI
 *
 * @see http://code.google.com/intl/ja/apis/ajaxsearch/documentation/reference.html#_intro_fonje
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class GoogleAPI extends Http{
	protected $_base_url_ = "http://ajax.googleapis.com/ajax/services/";

	/**
	 * web検索
	 *
	 * @param 検索文字列 $q
	 * @param ページ番号 $page
	 * @param 言語 $lang
	 * @return array GoogleSearchResult
	 */
	public function web($q,$page=1,$lang="ja"){
		module("search.GoogleSearchResult");
		$result = array();
		$this->vars("q",$q);
		$this->vars("hl",$lang);
		$this->vars("start",(($page - 1) * 8));
		$this->vars("v","1.0");
		$this->vars("rsz","large");
		$this->do_get("search/web");
		return GoogleSearchResult::parse_list($this->body());
		/***
			$api = new GoogleAPI();
			try{
				$result = $api->web("rhaco");
				eq(true,($result[0] instanceof GoogleSearchResult));
			}catch(Exception $e){
				eq(false,$e->getMessage());
			}
		 */
	}
	/**
	 * blog検索
	 *
	 * @param 検索文字列 $q
	 * @param ページ番号 $page
	 * @param 言語 $lang
	 * @return array GoogleSearchBlogResult
	 */
	public function blogs($q,$page=1,$lang="ja"){
		module("search.GoogleSearchBlogResult");
		$result = array();
		$this->vars("q",$q);
		$this->vars("hl",$lang);
		$this->vars("start",(($page - 1) * 8));
		$this->vars("v","1.0");
		$this->vars("rsz","large");
		$this->do_get("search/blogs");
		return GoogleSearchBlogResult::parse_list($this->body());
		/***
			$api = new GoogleAPI();
			try{
				$result = $api->blogs("rhaco");
				eq(true,($result[0] instanceof GoogleSearchBlogResult));
			}catch(Exception $e){
				eq(false,$e->getMessage());
			}
		 */
	}
	/**
	 * news検索
	 *
	 * @param 検索文字列 $q
	 * @param ページ番号 $page
	 * @param 言語 $lang
	 * @return array GoogleSearchNewsResult
	 */
	public function news($q,$page=1,$lang="ja"){
		module("search.GoogleSearchNewsResult");
		$result = array();
		$this->vars("q",$q);
		$this->vars("hl",$lang);
		$this->vars("start",(($page - 1) * 8));
		$this->vars("v","1.0");
		$this->vars("rsz","large");
		$this->do_get("search/news");
		return GoogleSearchNewsResult::parse_list($this->body());
		/***
			$api = new GoogleAPI();
			try{
				$result = $api->news("事件");
				eq(true,($result[0] instanceof GoogleSearchNewsResult));
			}catch(Exception $e){
				eq(false,$e->getMessage());
			}
		 */
	}
	public function images($q,$page=1,$lang="ja"){
		module("search.GoogleSearchImageResult");
		$result = array();
		$this->vars("q",$q);
		$this->vars("hl",$lang);
		$this->vars("start",(($page - 1) * 8));
		$this->vars("v","1.0");
		$this->vars("rsz","large");
		$this->do_get("search/images");
		return GoogleSearchImageResult::parse_list($this->body());
		/***
			$api = new GoogleAPI();
			try{
				$result = $api->images("rhaco");
				eq(true,($result[0] instanceof GoogleSearchImageResult));
			}catch(Exception $e){
				eq(false,$e->getMessage());
			}
		 */
	}
	/**
	 * 英和翻訳
	 *
	 * @param 翻訳対象文字列 $word
	 * @return string
	 */
	public function trans_e2j($word){
		return $this->trans($word,"ja","en");
		/***
			$api = new GoogleAPI();
			try{
				$result = $api->trans_e2j("frog");
				eq(true,true);
			}catch(Exception $e){
				eq(false,$e->getMessage());
			}
		 */
	}
	/**
	 * 和英翻訳
	 *
	 * @param 翻訳対象文字列 $word
	 * @return string
	 */
	public function trans_j2e($word){
		return $this->trans($word,"en","ja");
		/***
			$api = new GoogleAPI();
			try{
				$result = $api->trans_e2j("蛙");
				eq(true,true);
			}catch(Exception $e){
				eq(false,$e->getMessage());
			}
		 */
	}

	/**
	 * 翻訳
	 *  言語コード http://code.google.com/intl/ja-JP/apis/ajaxlanguage/documentation/reference.html#LangNameArray
	 *
	 * @param 翻訳対象文字列 $word
	 * @param 翻訳後言語コード $to
	 * @param 翻訳前言語コード $from
	 * @return string
	 */
	public function trans($word,$to,$from=null){
		$this->vars("q",$word);
		$this->vars("v","1.0");
		$this->vars("langpair",$from."|".$to);
		$this->do_get("language/translate");
		$result = Text::parse_json($this->body());
		if(isset($result["responseStatus"])){
			if($result["responseStatus"] !== 200) throw new Exception($result["responseDetails"]);
			return $result["responseData"]["translatedText"];
		}
		throw new Exception("invalid response");
		/***
			$api = new GoogleAPI();
			try{
				$result = $api->trans("frog","ja");
				eq(true,true);
			}catch(Exception $e){
				eq(false,$e->getMessage());
			}
		 */
	}
	/**
	 * カレンダーAPIを返す
	 *
	 * @param string $email
	 * @param string $password
	 * @return GoogleCalendarAPI
	 */
	static public function calendar($email=null,$password=null){
		module("GoogleCalendarAPI");
		return new GoogleCalendarAPI($email,$password);
	}
}
?>