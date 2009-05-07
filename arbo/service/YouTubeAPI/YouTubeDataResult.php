<?php
import("net.feed.atom.Atom");
import("core.Http");
import("core.Text");
import("core.File");
import("core.Lib");
module("YouTubeDataMedia");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class YouTubeDataResult extends Object{
	static protected $__published__ = "type=timestamp";
	static protected $__updated__ = "type=timestamp";
	protected $title;
	protected $url;
	protected $mobile_url;
	protected $content;
	protected $published;
	protected $updated;
	
	protected $keyword;
	protected $duration;
	protected $player;
	protected $category;
	protected $thumbnail;
	
	private $ext = ".mp4";

	public function download($save_dir,$save_filename,$ext=true){
		$b = new Http();
		$b->do_get($this->url()."&fmt=22");
		
		if(preg_match("/var[\s]+swfArgs[\s]*=[\s]*(\{.+?\})/m",$b->body(),$match)){
			$json = Text::parse_json($match[1]);
			$base_url = "http://www.youtube.com/get_video?video_id=".$json["video_id"]."&t=".$json["t"];
			$url = $base_url."&fmt=22";
			if($b->do_head($url)->status() !== 200) $url = $base_url."&fmt=18";
			$b->do_download($url,File::absolute($save_dir,$save_filename).(($ext) ? $this->ext : ""));
			return;
		}
		throw new Exception("undef video");
	}
	static public function parse_list($response){
		$result = array();
		try{
			$atom = Atom::parse($response,new YouTubeDataMedia());
		}catch(Exception $e){
			throw new Exception($response);
		}
		foreach($atom->arEntry() as $entry){
			$obj = new self();
			$links = $entry->link();
			if(isset($links[0])) $obj->url($links[0]->href());
			if(isset($links[3])) $obj->mobile_url($links[3]->href());
			if($entry->content() instanceof AtomContent) $obj->content($entry->content()->value());
			$obj->title($entry->title());
			$obj->published($entry->published());
			$obj->updated($entry->updated());
			
			$obj->keyword($entry->extra()->keyword());
			$obj->duration($entry->extra()->duration());
			$obj->player($entry->extra()->player());
			$obj->category($entry->extra()->category());
			$obj->thumbnail($entry->extra()->thumbnail());
			$result[] = $obj;
		}
		return $result;
	}
}
?>