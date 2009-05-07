<?php
Rhaco::import("core.Tag");
Rhaco::import("core.Log");
Rhaco::import("net.feed.opml.OpmlOutline");
/**
 * Opml Model
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Opml extends Object{
	static protected $__dateCreated__ = "type=timestamp";
	static protected $__dateModified__ = "type=timestamp";
	static protected $__windowTop__ = "type=number";
	static protected $__windowLeft__ = "type=number";
	static protected $__windowBottom__ = "type=number";
	static protected $__windowRight__ = "type=number";
	static protected $__outline__ = "type=OpmlOutline[]";
	
	protected $version = "1.0";
	protected $title;
	protected $dateCreated;
	protected $dateModified;
	protected $ownerName;
	protected $ownerEmail;
	protected $expansionState;
	protected $vertScrollState;
	protected $windowTop;
	protected $windowLeft;
	protected $windowBottom;
	protected $windowRight;
	protected $outline;
	
	protected function __getattr__($arg,$param){
		if(is_string($arg)) return Tag::xmltext($arg);
		return $arg;
	}
	protected function __add__($arg){
		if($arg instanceof OpmlOutline){
			$this->outline($arg);
		}else if($arg instanceof self){
			foreach($arg->arOutline() as $outline) $this->outline($outline);
		}
	}
	protected function __str__(){
		$out = new Tag("opml");
		$out->param("version",$this->version());
		$head = new Tag("head");
		if($this->isTitle()) $head->add(new Tag("title",$this->title()));
		if($this->isDateCreated()) $head->add(new Tag("dateCreated",$this->formatDate($this->dateCreated())));
		if($this->isDateModified()) $head->add(new Tag("dateModified",$this->formatDate($this->dateModified())));
		if($this->isOwnerName()) $head->add(new Tag("ownerName",$this->ownerName()));
		if($this->isOwnerEmail()) $head->add(new Tag("ownerEmail",$this->ownerEmail()));
		if($this->isExpansionState()) $head->add(new Tag("expansionState",$this->expansionState()));
		if($this->isVertScrollState()) $head->add(new Tag("vertScrollState",$this->vertScrollState()));
		if($this->isWindowTop()) $head->add(new Tag("windowTop",$this->windowTop()));
		if($this->isWindowLeft()) $head->add(new Tag("windowLeft",$this->windowLeft()));
		if($this->isWindowBottom()) $head->add(new Tag("windowBottom",$this->windowBottom()));
		if($this->isWindowRight()) $head->add(new Tag("windowRight",$this->windowRight()));
		$out->adde($head);

		$body = new Tag("body");
		foreach($this->arOutline() as $outline) $body->add($outline->get());
		$out->add($body->get());
		return $out();
	}
	/**
	 * 文字列からOpmlをセットする
	 *
	 * @param string $src
	 */
	static public function parse($src){
		if(Tag::setof($tag,$src,"opml")){
			$result = new self();
			$result->title($tag->f("title.value()"));
			$result->dateCreated($tag->f("dateCreated.value()"));
			$result->dateModified($tag->f("dateModified.value()"));
			$result->ownerName($tag->f("ownerName.value()"));
			$result->ownerEmail($tag->f("ownerEmail.value()"));
			$result->expansionState($tag->f("expansionState.value()"));
			$result->vertScrollState($tag->f("vertScrollState.value()"));
			$result->windowTop($tag->f("windowTop.value()"));
			$result->windowLeft($tag->f("windowLeft.value()"));
			$result->windowBottom($tag->f("windowBottom.value()"));
			$result->windowRight($tag->f("windowRight.value()"));

			foreach($tag->in("outline") as $intag){
				$opmloutline = new OpmlOutline();
				$result->outline($opmloutline->parse($intag->plain()));
			}
			return $result;
		}
		throw new Exception("no opml");
		/***
			$text = text('
						<?xml version="1.0" encoding="utf-8"?>
						<opml version="1.0">
						<head>
							<title>Subscriptions</title>
							<dateCreated>Mon, 19 May 2008 04:51:05 UTC</dateCreated>
							<ownerName>rhaco</ownerName>
						</head>
						<body>
						<outline title="Subscriptions">
							  <outline title="スパムとか" htmlUrl="http://www.everes.net/" type="rss" xmlUrl="http://www.everes.net/blog/atom/" />
							  <outline title="tokushimakazutaka.com" htmlUrl="http://tokushimakazutaka.com" type="rss" xmlUrl="tokushimakazutaka.com/rss" />
							<outline title="rhaco">
							</outline>
							<outline title="php">
							  <outline title="riaf-ja blog" htmlUrl="http://blog.riaf.jp/" type="rss" xmlUrl="http://blog.riaf.jp/rss" />
							</outline>
							<outline title="お知らせ">
							</outline>
						</outline>
						</body></opml>
					');

			$feed = Opml::parse($text);
			eq("Subscriptions",$feed->title());
			eq("1.0",$feed->version());
			eq(1211172665,$feed->dateCreated());
			eq("rhaco",$feed->ownerName());
			eq(null,$feed->ownerEmail());
			
			eq(1,sizeof($feed->outline()));
			$opml = $feed->outline();
			eq("Subscriptions",$opml[0]->title());
	
			eq(3,sizeof($opml[0]->xml()));
			eq(3,sizeof($opml[0]->html()));
		*/
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
	private function formatDate($time){
		return date("D, d M Y H:i:s T",$time);
	}
	public function outlines(){
		$result = array();
		foreach($this->arOutline() as $outline){
			$result = array_merge($result,$outline->arOutline());
		}
		return $result;
	}
	public function atom(){
		Rhaco::import("net.feed.atom.Atom");
		$atom = new Atom();
		$atom->title($this->title());

		foreach($this->outlines() as $outline){
			$entry = new AtomEntry();
			$entry->title($outline->title());
			$entry->published(time());
			if($outline->isHtmlUrl()) $entry->link(new AtomLink("type=html,href=".$outline->htmlUrl()));
			if($outline->isXmlUrl()) $entry->link(new AtomLink("type=xml,href=".$outline->xmlUrl()));
			$entry->content(new AtomContent("value=".$outline->description()));
			$entry->summary(new AtomSummary("value=".$outline->tags()));
			$atom->add($entry);
		}
		return $atom;
		/***
			$text = text('
						<?xml version="1.0" encoding="utf-8"?>
						<opml version="1.0">
						<head>
							<title>Subscriptions</title>
							<dateCreated>Mon, 19 May 2008 04:51:05 UTC</dateCreated>
							<ownerName>rhaco</ownerName>
						</head>
						<body>
						<outline title="Subscriptions">
							  <outline title="スパムとか" htmlUrl="http://www.everes.net/" type="rss" xmlUrl="http://www.everes.net/blog/atom/" />
							  <outline title="tokushimakazutaka.com" htmlUrl="http://tokushimakazutaka.com" type="rss" xmlUrl="tokushimakazutaka.com/rss" />
							<outline title="rhaco">
							</outline>
							<outline title="php">
							  <outline title="riaf-ja blog" htmlUrl="http://blog.riaf.jp/" type="rss" xmlUrl="http://blog.riaf.jp/rss" />
							</outline>
							<outline title="お知らせ">
							</outline>
						</outline>
						</body></opml>
					');

			$feed = Opml::parse($text);
			eq("Subscriptions",$feed->title());
			eq("1.0",$feed->version());
			eq(1211172665,$feed->dateCreated());
			eq("rhaco",$feed->ownerName());
			eq(null,$feed->ownerEmail());
			
			eq(1,sizeof($feed->outline()));
			$opml = $feed->outline();
			eq("Subscriptions",$opml[0]->title());
	
			eq(3,sizeof($opml[0]->xml()));
			eq(3,sizeof($opml[0]->html()));

			$atom = $feed->atom();
			eq(true,$atom instanceof Atom);
			eq("Subscriptions",$atom->title());
			eq(null,$atom->subtitle());
			eq(time(),$atom->updated());
			eq(null,$atom->generator());
			eq(5,sizeof($atom->entry()));
		*/
	}
}
?>