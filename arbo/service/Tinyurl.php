<?php
import("core.Http");
/**
 * Tinyurl
 *
 * @see http://tinyurl.com
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Tinyurl{
	/**
	 * 短縮URLを作成する
	 * @param $url
	 * @return string
	 */
	static public function create($url){
		$http = new Http();
		$http->vars("url",$url);
		return $http->do_get("http://tinyurl.com/api-create.php")->body();
		/***
			eq("http://tinyurl.com/6bkavu",Tinyurl::create("http://rhaco.org"));
		 */
	}

	/**
	 * 短縮urlから復元する
	 * @param $url
	 * @return string
	 */
	static public function lookup($url){
		if(strpos($url,"http://tinyurl.com/") !== 0) $url = "http://tinyurl.com/".$url;
		$http = new Http();
		$http->status_redirect(false);
		$http->do_get($url);
		if($http->status() === 301 && preg_match("/Location:[\040](.*)/i",$http->head(),$redirect_url)) return trim($redirect_url[1]);
		return $url;
		/***
			eq("http://rhaco.org",Tinyurl::lookup("http://tinyurl.com/6bkavu"));
			eq("http://rhaco.org",Tinyurl::lookup("6bkavu"));			
		 */
	}
}
?>