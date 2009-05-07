<?php
Rhaco::import("core.Http");
Rhaco::import("core.File");
Rhaco::import("net.feed.atom.Atom");
Rhaco::import("net.feed.rss.Rss");
Rhaco::import("net.feed.opml.Opml");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Feed extends Http{
	static private $CACHE;
	static private $CACHE_TIME;
	static protected $__updated__ = "type=timestamp";
	protected $title;
	protected $subtitle;
	protected $id;
	protected $generator;
	protected $updated;

	static public function __import__(){
		self::$CACHE = Rhaco::def("net.xml.Feed@cache",false);
		self::$CACHE_TIME = Rhaco::def("net.xml.Feed@time",86400);
	}
	static public function read($url){
		$feed = new self();
		return $feed->do_read($url);
	}
	/**
	 * URLからフィードを取得
	 *
	 * @param string $url
	 * @return Atom
	 */
	public function do_read($url){
		$urls = func_get_args();
		$feed = null;

		if(!self::$CACHE || File::isExpiry($urls,self::$CACHE_TIME)){
			foreach($urls as $url){
				if(is_string($url) && ($url = trim($url)) && !empty($url)){
					if(!self::$CACHE || File::isExpiry($url,self::$CACHE_TIME)){
						$src = Tag::xhtmlnize($this->do_get($url)->body(),"link");
						if(Tag::setof($tag,$src,"head")){
							foreach($tag->in("link") as $link){
								if("alternate" == strtolower($link->inParam("rel"))
									&& strpos(strtolower($link->inParam("type")),"application") === 0
									&& $url != ($link = File::absolute($url,$link->inParam("href")))
								){
									$src = $this->do_get($link)->body();
									break;
								}
							}
						}
						$tmp = self::parse($src);
						if(self::$CACHE) File::cwrite($url,$tmp);
					}else{
						$tmp = File::cread($url);
					}
					if($feed === null){
						if($this->title !== null) $tmp->title($this->title());
						if($this->subtitle !== null) $tmp->subtitle($this->subtitle());
						if($this->id !== null) $tmp->id($this->id());
						if($this->generator !== null) $tmp->generator($this->generator());
						if($this->updated !== null) $tmp->updated($this->updated());

						$feed = $tmp;
					}else{
						$feed->add($tmp);
					}
				}
			}
			if(!($feed instanceof Atom)) $feed = new Atom();
			$feed->sort();
			if(self::$CACHE) File::cwrite($urls,$feed);
		}else{
			$feed = File::cread($urls);
		}
		return $feed;
	}
	/**
	 * フィードを取得
	 *
	 * @param string $src
	 * @return Atom
	 */
	static public function parse($src){
		try{
			return Atom::parse($src);
		}catch(Exception $e){
			try{
				return Rss::parse($src)->atom();
			}catch(Exception $e){
				try{
					return Opml::parse($src)->atom();
				}catch(Exception $e){
					throw new Exception("no feed");
				}
			}
		}
		/***
			$src = text('
				<feed xmlns="http://www.w3.org/2005/Atom">
				<title>atom10 feed</title>
				<subtitle>atom10 sub title</subtitle>
				<updated>2007-07-18T16:16:31+00:00</updated>
				<generator>tokushima</generator>
				<link href="http://tokushimakazutaka.com" rel="abc" type="xyz" />

				<author>
					<url>http://tokushimakazutaka.com</url>
					<name>tokushima</name>
					<email>tokushima@hoge.hoge</email>
				</author>

				<entry>
					<title>rhaco</title>
					<summary type="xml" xml:lang="ja">summary test</summary>
					<content type="text/xml" mode="abc" xml:lang="ja" xml:base="base">atom content</content>
					<link href="http://rhaco.org" rel="abc" type="xyz" />
					<link href="http://conveyor.rhaco.org" rel="abc" type="conveyor" />
					<link href="http://lib.rhaco.org" rel="abc" type="lib" />

					<updated>2007-07-18T16:16:31+00:00</updated>
				 	<issued>2007-07-18T16:16:31+00:00</issued>
				 	<published>2007-07-18T16:16:31+00:00</published>
				 	<id>rhaco</id>
				<author>
					<url>http://rhaco.org</url>
					<name>rhaco</name>
					<email>rhaco@rhaco.org</email>
				</author>
				</entry>

				<entry>
					<title>django</title>
					<summary type="xml" xml:lang="ja">summary test</summary>
					<content type="text/xml" mode="abc" xml:lang="ja" xml:base="base">atom content</content>
					<link href="http://djangoproject.jp" rel="abc" type="xyz" />

				 <updated>2007-07-18T16:16:31+00:00</updated>
				 <issued>2007-07-18T16:16:31+00:00</issued>
				 <published>2007-07-18T16:16:31+00:00</published>
				 <id>django</id>
				<author>
					<url>http://www.everes.net</url>
					<name>everes</name>
					<email>everes@hoge.hoge</email>
				</author>
				</entry>

				</feed>
			');

			$xml = Feed::parse($src);
			$result = text('
							<feed xmlns="http://www.w3.org/2005/Atom">
							<title>atom10 feed</title>
							<subtitle>atom10 sub title</subtitle>
							<id>rhaco</id>
							<generator>tokushima</generator>
							<updated>2007-07-18T16:16:31Z</updated>
							<link rel="abc" type="xyz" href="http://tokushimakazutaka.com" />
							<entry>
								<id>rhaco</id>
								<title>rhaco</title>
								<published>2007-07-18T16:16:31Z</published>
								<updated>2007-07-18T16:16:31Z</updated>
								<issued>2007-07-18T16:16:31Z</issued>
								<content type="text/xml" mode="abc" xml:lang="ja" xml:base="base">atom content</content>
								<summary type="xml" xml:lang="ja">summary test</summary>
								<link rel="abc" type="xyz" href="http://rhaco.org" />
								<link rel="abc" type="conveyor" href="http://conveyor.rhaco.org" />
								<link rel="abc" type="lib" href="http://lib.rhaco.org" />
								<author>
									<name>rhaco</name>
									<url>http://rhaco.org</url>
									<email>rhaco@rhaco.org</email>
								</author>
							</entry>
							<entry>
								<id>django</id>
								<title>django</title>
								<published>2007-07-18T16:16:31Z</published>
								<updated>2007-07-18T16:16:31Z</updated>
								<issued>2007-07-18T16:16:31Z</issued>
								<content type="text/xml" mode="abc" xml:lang="ja" xml:base="base">atom content</content>
								<summary type="xml" xml:lang="ja">summary test</summary>
								<link rel="abc" type="xyz" href="http://djangoproject.jp" />
								<author>
									<name>everes</name>
									<url>http://www.everes.net</url>
									<email>everes@hoge.hoge</email>
								</author>
							</entry>
							<author>
								<name>tokushima</name>
								<url>http://tokushimakazutaka.com</url>
								<email>tokushima@hoge.hoge</email>
							</author>
							</feed>
						');
			$result = str_replace(array("\n","\t"),"",$result);
			eq($result,(string)$xml);
		*/
	}
}
?>