<?php
import("core.Http");
import("core.Tag");
import("core.Text");
import("core.Lib");
module("PhotozouSearchResult");
/**
 * PhotozouAPI
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class PhotozouAPI extends Http{
	protected $_base_url_ = "http://api.photozou.jp/rest/";

	public function photo_search($query,$page=1,$rows=20){
		$this->vars("type","photo");
		return $this->searexecute_serachch($query,$page,$rows);
		/***
			try{
				$api = new PhotozouAPI();
				$result = $api->photo_search("ねこ");
				eq(true,($result[0] instanceof PhotozouSearchResult));
			}catch(Exception $e){
				eq(true,true);
			}
		 */
	}
	public function video_search($query,$page=1,$rows=20){
		$this->vars("type","video");
		return $this->execute_serach($query,$page,$rows);
		/***
			try{
				$api = new PhotozouAPI();
				$result = $api->video_search("ねこ");
				eq(true,($result[0] instanceof PhotozouSearchResult));
			}catch(Exception $e){
				eq(true,true);
			}
		 */
	}
	public function search($query,$page=1,$rows=20){
		return $this->execute_serach($query,$page,$rows);
		/***
			try{
				$api = new PhotozouAPI();
				$result = $api->search("ねこ");
				eq(true,($result[0] instanceof PhotozouSearchResult));
			}catch(Exception $e){
				eq(true,true);
			}
		 */
	}
	private function execute_serach($query,$page=1,$rows=20){
		$this->vars("keyword",$query);
		$this->vars("order_type","date");
		$this->vars("offset",($page - 1) * $rows);
		$this->vars("limit",$rows);
		$this->do_get("search_public");
		return PhotozouSearchResult::parse_list($this->body());
	}
}
?>