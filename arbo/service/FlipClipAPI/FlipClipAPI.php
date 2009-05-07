<?php
import("core.Http");
import("core.Tag");
import("core.Text");
import("core.Lib");
import("core.Paginator");
module("FlipClipSearchResult");
/**
 * FlipClipAPI
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class FlipClipAPI extends Http{
	protected $paginator;
	/**
	 * メンバのホームページのURL
	 *
	 * @param string $userid
	 * @return string
	 */
	public function member_url($userid){
		return "http://www.flipclip.net/users/".$userid;
	}
	
	/**
	 * 検索する
	 *
	 * @param string $query
	 * @param int $page
	 * @param int $rows
	 * @return array FlipClipSearchResult
	 */
	public function search($query,$page=1,$rows=20){
		$this->vars("rows",$rows);
		$this->vars("page",$page);
		$this->vars("q",$query);
		$this->vars("_accept","json");
		$this->do_get("http://www.flipclip.net/api/clips/public");
		return FlipClipSearchResult::parse_list($this->body(),$this->paginator);
		/***
			$api = new FlipClipAPI();
			try{
				$result = $api->search("ねこ",1,1);
				if(eq(1,sizeof($result))){
					eq(true,($result[0] instanceof FlipClipSearchResult));
				}
			}catch(Exception $e){
				eq(false,$e->getMessage());
			}
		 */
	}
	/**
	 * 動画を検索
	 *
	 * @param string $query
	 * @param int $page
	 * @param int $rows
	 * @return array FlipClipSearchResult
	 */
	public function video_search($query,$page=1,$rows=20){
		$this->vars("ctype","video");
		return $this->search($query,$page,$rows);
	}
	/**
	 * 写真を検索
	 *
	 * @param string $query
	 * @param int $page
	 * @param int $rows
	 * @return array FlipClipSearchResult
	 */
	public function photo_search($query,$page=1,$rows=20){
		$this->vars("ctype","photo");
		return $this->search($query,$page,$rows);
	}
	
	/**
	 * メンバを指定して検索
	 *
	 * @param string $userid
	 * @param string $query
	 * @param int $page
	 * @param int $rows
	 * @return array FlipClipSearchResult
	 */
	public function member_clips($userid,$query=null,$page=1,$rows=20){
		$this->vars("rows",$rows);
		$this->vars("page",$page);
		$this->vars("userid",$userid);		
		if(!empty($query)) $this->vars("q",$query);
		$this->vars("_accept","json");
		$this->do_get("http://www.flipclip.net/api/clips/user");
		return FlipClipSearchResult::parse_list($this->body(),$this->paginator);
		/***
			$api = new FlipClipAPI();
			try{
				$result = $api->member_clips("tokushima",null,1,1);
				if(eq(1,sizeof($result))){
					eq(true,($result[0] instanceof FlipClipSearchResult));
				}
			}catch(Exception $e){
				eq(false,$e->getMessage());
			}
		 */
	}
	/**
	 * メンバの写真を検索
	 *
	 * @param string $userid
	 * @param string $query
	 * @param int $page
	 * @param int $rows
	 * @return array FlipClipSearchResult
	 */
	public function member_photo_clips($userid,$query=null,$page=1,$rows=20){
		$this->vars("ctype","photo");
		return $this->member_clips($userid,$query,$page,$rows);
	}
	/**
	 * メンバの動画を検索
	 *
	 * @param string $userid
	 * @param string $query
	 * @param int $page
	 * @param int $rows
	 * @return array FlipClipSearchResult
	 */
	public function member_video_clips($userid,$query=null,$page=1,$rows=20){
		$this->vars("ctype","video");
		return $this->member_clips($userid,$query,$page,$rows);
	}
}
?>