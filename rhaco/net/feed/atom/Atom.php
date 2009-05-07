<?php
Rhaco::import("core.Tag");
Rhaco::import("core.Log");
Rhaco::import("core.Date");
Rhaco::import("net.feed.atom.AtomLink");
Rhaco::import("net.feed.atom.AtomEntry");
Rhaco::import("net.feed.atom.AtomAuthor");
Rhaco::import("net.feed.atom.AtomInterface");
/**
 * Atom
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Atom extends Object{
	static private $XMLNS = "http://www.w3.org/2005/Atom";
	static protected $__updated__ = "type=timestamp";
	static protected $__link__ = "type=AtomLink[]";
	static protected $__entry__ = "type=AtomEntry[]";
	static protected $__author__ = "type=AtomAuthor[]";
	static protected $__entry_modules__ = "type=object[]";
	protected $title;
	protected $subtitle;
	protected $id;
	protected $generator;
	protected $updated;
	protected $link;
	protected $entry;
	protected $author;
	protected $entry_modules;

	protected function __init__(){
		$this->updated = time();
	}
	protected function __getattr__($arg,$param){
		if(is_string($arg)) return Tag::xmltext($arg);
		return $arg;
	}
	protected function __add__($arg){
		if($arg instanceof AtomEntry){
			$this->entry[] = $arg;
		}else if($arg instanceof self){
			foreach($arg->arEntry() as $entry) $this->entry[] = $entry;
		}else if(is_implements_of($arg,"AtomInterface")){
			$entry = new AtomEntry();
			$entry->id($arg->atom_id());
			$entry->title($arg->atom_title());
			$entry->published($arg->atom_published());
			$entry->updated($arg->atom_updated());
			$entry->issued($arg->atom_issued());
			
			$content = new AtomContent();
			$content->value($arg->atom_content());
			$entry->content($content);
			
			$summary = new AtomSummary();
			$summary->value($arg->atom_summary());
			$entry->summary($summary);

			$entry->link(new AtomLink("href=".$arg->atom_href()));
			$entry->author(new AtomAuthor("name=".$arg->atom_author()));
			$this->entry[] = $entry;
		}
	}
	static public function parse($src){
		$args = func_get_args();
		array_shift($args);

		if(Tag::setof($tag,$src,"feed") && $tag->inParam("xmlns") == self::$XMLNS){
			$result = new self();
			$value = $tag->value();
			$tag = Tag::anyhow($value);

			$result->id($tag->f("id.value()"));
			$result->title($tag->f("title.value()"));
			$result->subtitle($tag->f("subtitle.value()"));
			$result->updated($tag->f("updated.value()"));
			$result->generator($tag->f("generator.value()"));

			$value = $tag->value();
			$result->entry = call_user_func_array(array("AtomEntry","parse"),array_merge(array(&$value),$args));
			$result->link = AtomLink::parse($value);
			$result->author = AtomAuthor::parse($value);

			return $result;
		}
		throw new Exception("no atom");
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

			$xml = Atom::parse($src);
			eq("atom10 feed",$xml->title());
			eq("atom10 sub title",$xml->subtitle());
			eq(1184775391,$xml->updated());
			eq("2007-07-18T16:16:31Z",$xml->fmUpdated());
			eq("tokushima",$xml->generator());
			eq(2,sizeof($xml->entry()));
		*/
	}
	protected function formatUpdated(){
		return Date::format_atom($this->updated);
	}
	protected function __str__(){
		$result = new Tag("feed");
		$result->param("xmlns",self::$XMLNS);
		foreach($this->access_members() as $name => $value){
			if(!empty($value)){
				switch($name){
					case "title":
					case "subtitle":
					case "id":
					case "generator":
						$result->add(new Tag($name,$value));
						break;
					case "author":
						foreach($this->{$name} as $o) $result->add($o);
						break;
					case "updated":
						$result->add(new Tag($name,Date::format_atom($value)));
						break;
					default:
						if(is_array($this->{$name})){
							foreach($this->{$name} as $o) $result->add($o);
							break;
						}else if(is_object($this->{$name})){
							$result->add($value);
							break;
						}else{
							$result->add(new Tag($name,$value));
							break;
						}
				}
			}
		}
		return $result->get();
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

			$xml = Atom::parse($src);
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
	public function output($name=""){
		Log::disable_display();
		header(sprintf("Content-Type: application/atom+xml; name=%s",(empty($name)) ? uniqid("") : $name));
		print($this->get(true));
		exit;
	}
	public function get($enc=false){
		$value = sprintf("%s",$this);
		return (($enc) ? (sprintf("<?xml version=\"1.0\" encoding=\"%s\"?>\n",mb_detect_encoding($value))) : "").$value;
	}
	public function sort(){
		self::osort($this->entry,"published");
		return $this;
	}
	protected function __none__(){
		return (empty($this->entry));
	}
	
	/**
	 * Atomに変換する
	 *
	 * @param string $title
	 * @param string $link
	 * @param iterator $entrys
	 * @return Atom
	 */
	static public function convert($title,$link,$entrys){
		$atom = new self();
		$atom->title($title);
		$atom->link(new AtomLink("href=".$link));
		foreach($entrys as $entry) $atom->add($entry);
		return $atom;
	}
}
?>