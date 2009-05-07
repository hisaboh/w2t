<?php
import("core.Http");
import("core.Tag");
import("core.Text");
import("core.Lib");
module("YouTubeDataResult");
/**
 * YoutubeAPI
 *
 * @see http://code.google.com/intl/ja-JP/apis/youtube/reference.html#Videos_feed
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class YouTubeAPI extends Http {
	protected $_api_key_name_ = "appid";

	protected function __new__($api_key=null){
		$this->set_api_key(isset($api_key) ? $api_key : def("arbo.service.YouTubeAPI@api_key"));
	}
	public function search($query,$page=1,$rows=20){
		$this->vars("vq",$query);
		$this->vars("start-index",(($page - 1) * $rows) + 1);
		$this->vars("max-results",$rows);
		$this->do_get("http://gdata.youtube.com/feeds/api/videos");
		return YouTubeDataResult::parse_list($this->body());
		/***
			try{
				$api = new YouTubeAPI();
				$result = $api->search("cat");
				eq(true,sizeof($result) > 0);
				eq(true,($result[0] instanceof YouTubeDataResult));
			}catch(Exception $e){
				eq(false,$e->getMessage());
			}
		 */
	}
}
?>