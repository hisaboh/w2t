<?php
import("core.Http");
import("core.Tag");
import("core.Text");
import("core.Lib");
module("WikipediaResult");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class WikipediaAPI extends Http{
	function search($query){
		$this->vars("output","php");
		$this->vars("keyword",$query);
		$this->do_get("http://wikipedia.simpleapi.net/api");

		return WikipediaResult::parse_list($this->body());
		/***
			try{
				$api = new WikipediaAPI();
				$api->search("hoge");
				eq(true,false);
			}catch(Exception $e){
				eq(true,true);
			}
			try{
				$api = new WikipediaAPI();
				eq(true,true);
			}catch(Exception $e){
				eq(false,$e->getMessage());
			}
		 */
	}
}
?>