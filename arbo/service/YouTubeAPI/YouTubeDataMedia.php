<?php
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class YouTubeDataMedia extends Object{
	protected $keyword;
	protected $duration;
	protected $player;
	protected $category;
	protected $thumbnail;
	
	public function parse(&$src,&$result){
		if(Tag::setof($tag,$src,"media:group")){
			$media = new self();
			$media->keyword($tag->f("media:keywords.value()"));
			$media->duration($tag->f("yt:duration.param(seconds)"));
			$media->player($tag->f("media:player.value()"));
			$media->category($tag->f("media:category.value()"));
			$media->thumbnail($tag->f("media:thumbnail.param(url)"));
			$result = $media;
		}
	}
}
?>