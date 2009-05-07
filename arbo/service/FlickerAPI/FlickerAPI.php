<?php
import("core.Http");
import("core.Tag");
import("core.Text");
import("core.Lib");
module("FlickerSearchResult");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class FlickerAPI extends Http{
	protected $_base_url_ = "http://flickr.com/services/rest/";

	protected function __new__($api_key=null){
		$this->set_api_key(isset($api_key) ? $api_key : def("arbo.service.FlickerAPI@api_key"));
	}
	
	/**
	 * 検索する
	 *
	 * @param string $query
	 * @param int $page
	 * @param int $rows
	 * @see http://www.flickr.com/services/api/flickr.photos.search.html
	 * @return array FlickerSearchResult
	 */
	public function search($query,$page=1,$rows=20){
		$this->vars("method","flickr.photos.search");
		$this->vars("page",$page);
		$this->vars("per_page",$rows);
		$this->vars("text",$query);
		$this->vars("extras","license,date_upload,date_taken,owner_name,icon_server"
							.",original_format,last_update,geo,tags,machine_tags,o_dims,views,media");
		$this->do_get();
		return FlickerSearchResult::parse_list($this->body());
		/***
			$api = new FlickerAPI();
			try{
				$result = $api->search("frog",1,1);
				if(eq(1,sizeof($result))){
					eq(true,($result[0] instanceof FlickerSearchResult));
				}
			}catch(Exception $e){
				eq(false,$e->getMessage());
			}
		 */
	}
}
?>