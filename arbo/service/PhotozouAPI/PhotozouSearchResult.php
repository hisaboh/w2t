<?php
import("net.feed.atom.Atom");
import("core.Http");
import("core.Text");
import("core.File");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class PhotozouSearchResult extends Object{
	static protected $__regist_time__ = "type=timestamp";
	protected $photo_id;
	protected $user_id;
	protected $album_id;
	protected $photo_title;
	protected $favorite_num;
	protected $comment_num;
	protected $view_num;
	protected $copyright;
	protected $copyright_commercial;
	protected $copyright_modifications;
	protected $regist_time;
	protected $url;
	protected $image_url;
	protected $original_image_url;
	protected $thumbnail_image_url;

	public function download($save_dir,$save_filename,$ext=true){
		$b = new Http();
		if(!empty($this->original_image_url)){
			$b->do_download($this->original_image_url,File::absolute($save_dir,$save_filename).(($ext) ? ".jpg" : ""));
			return;
		}else{
			$b->do_get($this->url);
			if(Tag::setof($tag,$b->body(),"body")){
				foreach($tag->in("script") as $s){
					if(preg_match("/addVariable\('url', '(.+?)'\)/",$s->value(),$match)){
						$b->do_download(trim($match[1]),File::absolute($save_dir,$save_filename).(($ext) ? ".flv" : ""));
						return;
					}
				}
			}
		}
		throw new Exception("undef video");
	}
	static public function parse_list($response){
		$result = array();
		if(Tag::setof($tag,$response,"info")){
			foreach($tag->in("photo") as $photo){
				$obj = new self();
				$result[] = $obj->cp($photo->hash());
			}
		}else{
			throw new Exception("Invalid data");
		}
		return $result;
	}
}
?>