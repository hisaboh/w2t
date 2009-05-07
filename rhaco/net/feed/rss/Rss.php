<?php
Rhaco::import("core.Tag");
Rhaco::import("core.Log");
Rhaco::import("net.feed.rss.RssItem");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Rss extends Object{
	static protected $__lastBuildDate__ = "type=timestamp";
	static protected $__pubDate__ = "type=timestamp";
	static protected $__item__ = "type=RssItem[]";
	protected $version;
	protected $title;
	protected $link;
	protected $description;
	protected $language;
	protected $copyright;
	protected $docs;
	protected $lastBuildDate;
	protected $managingEditor;
	protected $pubDate;
	protected $webMaster;
	protected $item;

	protected function __init__(){
		$this->pubDate = time();
		$this->lastBuildDate = time();
	}
	protected function __getattr__($arg,$param){
		if(is_string($arg)) return Tag::xmltext($arg);
		return $arg;
	}
	protected function __add__($arg){
		if($arg instanceof RssItem){
			$this->item[] = $arg;
		}else if($arg instanceof self){
			foreach($arg->arItem() as $item) $this->item[] = $item;
		}
	}
	protected function formatLastBuildDate(){
		return date("D, d M Y H:i:s O",$this->lastBuildDate);
	}
	protected function formatPubDate(){
		return date("D, d M Y H:i:s O",$this->pubDate);
	}
	protected function __str__(){
		$result = new Tag("rss");
		$channel = new Tag("channel");
		foreach($this->access_members() as $name => $value){
			if(!empty($value)){
				switch($name){
					case "version":
						$result->param("version",$value);
						break;
					case "title":
					case "link":
					case "description":
					case "language":
					case "copyright":
					case "docs":
					case "managingEditor":
					case "webMaster":
						$channel->add(new Tag($name,$value));
						break;
					case "lastBuildDate":
					case "pubDate":
						$channel->add(new Tag($name,$this->formatDate($value)));
						break;
					default:
						if(is_array($this->{$name})){
							foreach($this->{$name} as $o) $channel->add($o);
							break;
						}else if(is_object($this->{$name})){
							$channel->add($value);
							break;
						}else{
							$channel->add(new Tag($name,$value));
							break;
						}
				}
			}
		}
		$result->add($channel);
		return $result->get();
		/***
			$src = text('
						<rss version="2.0">
							<channel>
								<title>rhaco</title>
								<link>http://rhaco.org</link>
								<description>php</description>
								<language>ja</language>
								<copyright>rhaco.org</copyright>
								<docs>hogehoge</docs>
								<lastBuildDate>2007-10-10T10:10:10+09:00</lastBuildDate>
								<managingEditor>tokushima</managingEditor>
								<pubDate>2007-10-10T10:10:10+09:00</pubDate>
								<webMaster>kazutaka</webMaster>
								<item><title>rhaco</title><link>http://rhaco.org</link><description>rhaco desc</description></item>
								<item><title>everes</title><link>http://www.everes.net</link><description>everes desc</description></item>
							</channel>
						</rss>
					');
					$xml = Rss::parse($src);
					eq(str_replace(array("\n","\t"),"",$src),(string)$xml);
		*/
	}
	private function formatDate($time){
		$tzd = date("O",$time);
		$tzd = $tzd[0].substr($tzd,1,2).":".substr($tzd,3,2);
		return date("Y-m-d\TH:i:s".$tzd,$time);
	}
	/**
	 * 出力する
	 *
	 * @param string $name
	 */
	public function output($name=""){
		Log::disable_display();
		header(sprintf("Content-Type: application/rss+xml; name=%s",(empty($name)) ? uniqid("") : $name));
		print($this->get(true));
		exit;
	}
	public function get($enc=false){
		$value = sprintf("%s",$this);
		return (($enc) ? (sprintf("<?xml version=\"1.0\" encoding=\"%s\"?>\n",mb_detect_encoding($value))) : "").$value;
	}
	/**
	 * xmlをRssに変換する
	 *
	 * @param string $src
	 * @return Rss
	 */
	static public function parse($src){
		if(Tag::setof($rss,$src,"rss")){
			$result = new self();
			$result->version($rss->inParam("version","2.0"));
			if(Tag::setof($channel,$rss->value(),"channel")){
				$result->title($channel->f("title.value()"));
				$result->link($channel->f("link.value()"));
				$result->description($channel->f("description.value()"));
				$result->language($channel->f("language.value()"));
				$result->copyright($channel->f("copyright.value()"));
				$result->docs($channel->f("docs.value()"));
				$result->managingEditor($channel->f("managingEditor.value()"));
				$result->webMaster($channel->f("webMaster.value()"));
				$result->lastBuildDate($channel->f("lastBuildDate.value()"));
				$result->pubDate($channel->f("pubDate.value()"));

				$value = $channel->value();
				$result->item = RssItem::parse($value);
				return $result;
			}
		}
		throw new Exception("no rss");
		/***
			 $src = text('
						<rss version="2.0">
							<channel>
								<title>rhaco</title>
								<link>http://rhaco.org</link>
								<description>php</description>
								<language>ja</language>
								<copyright>rhaco.org</copyright>
								<docs>hogehoge</docs>
								<lastBuildDate>2007-10-10T10:10:10+09:00</lastBuildDate>
								<managingEditor>tokushima</managingEditor>
								<pubDate>2007-10-10T10:10:10+09:00</pubDate>
								<webMaster>kazutaka</webMaster>
								<item>
									<title>rhaco</title>
									<link>http://rhaco.org</link>
									<description>rhaco desc</description>
								</item>
								<item>
									<title>everes</title>
									<link>http://www.everes.net</link>
									<description>everes desc</description>
								</item>
							</channel>
						</rss>
					');
					$xml = Rss::parse($src);
					eq("2.0",$xml->version());
					eq("rhaco",$xml->title());
					eq("http://rhaco.org",$xml->link());
					eq("php",$xml->description());
					eq("ja",$xml->language());
					eq("rhaco.org",$xml->copyright());
					eq("hogehoge",$xml->docs());
					eq(1191978610,$xml->lastBuildDate());
					eq("Wed, 10 Oct 2007 10:10:10 +0900",$xml->fmLastBuildDate());
					eq("tokushima",$xml->managingEditor());
					eq(1191978610,$xml->pubDate());
					eq("Wed, 10 Oct 2007 10:10:10 +0900",$xml->fmPubDate());
					eq("kazutaka",$xml->webMaster());
					eq(2,sizeof($xml->item()));
			*/
	}
	public function sort(){
		self::osort($this->item,"published");
		return $this;
	}
	public function atom(){
		Rhaco::import("net.feed.atom.Atom");
		$atom = new Atom();
		$atom->title($this->title());
		$atom->subtitle($this->description());
		$atom->generator($this->webMaster());
		$atom->updated($this->lastBuildDate());

		$link = new AtomLink();
		$link->href($this->link());
		$atom->link($link);

		foreach($this->arItem() as $item){
			$entry = new AtomEntry();
			$entry->title($item->title());
			$entry->published($item->pubDate());

			$author = new AtomAuthor();
			$author->name($item->author());
			$entry->author($author);

			$link = new AtomLink();
			$link->href($item->link());
			$entry->link($link);

			$content = new AtomContent();
			$content->value($item->description());
			$entry->content($content);

			$summary = new AtomSummary();
			$summary->value($item->comments());
			$entry->summary($summary);

			$atom->add($entry);
		}
		return $atom;
		/***
			 $src = text('
						<rss version="2.0">
							<channel>
								<title>rhaco</title>
								<link>http://rhaco.org</link>
								<description>php</description>
								<language>ja</language>
								<copyright>rhaco.org</copyright>
								<docs>hogehoge</docs>
								<lastBuildDate>2007-10-10T10:10:10+09:00</lastBuildDate>
								<managingEditor>tokushima</managingEditor>
								<pubDate>2007-10-10T10:10:10+09:00</pubDate>
								<webMaster>kazutaka</webMaster>
								<item>
									<title>rhaco</title>
									<link>http://rhaco.org</link>
									<description>rhaco desc</description>
								</item>
								<item>
									<title>everes</title>
									<link>http://www.everes.net</link>
									<description>everes desc</description>
								</item>
							</channel>
						</rss>
					');
					$xml = Rss::parse($src);
					eq("2.0",$xml->version());
					eq("rhaco",$xml->title());
					eq("http://rhaco.org",$xml->link());
					eq("php",$xml->description());
					eq("ja",$xml->language());
					eq("rhaco.org",$xml->copyright());
					eq("hogehoge",$xml->docs());
					eq(1191978610,$xml->lastBuildDate());
					eq("Wed, 10 Oct 2007 10:10:10 +0900",$xml->fmLastBuildDate());
					eq("tokushima",$xml->managingEditor());
					eq(1191978610,$xml->pubDate());
					eq("Wed, 10 Oct 2007 10:10:10 +0900",$xml->fmPubDate());
					eq("kazutaka",$xml->webMaster());
					eq(2,sizeof($xml->item()));

					$atom = $xml->atom();
					eq(true,$atom instanceof Atom);
					eq("rhaco",$atom->title());
					eq("php",$atom->subtitle());
					eq(1191978610,$atom->updated());
					eq("kazutaka",$atom->generator());
					eq(2,sizeof($atom->entry()));
			*/
	}
}
?>