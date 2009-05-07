<?php
Rhaco::import("core.iterator.TagIterator");
Rhaco::import("core.Log");
/**
 * Tagを処理する
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Tag extends Object{
	static protected $__attr__ = "type=string{}";
	static protected $__param__ = "type=string{}";
	static protected $__plain__ = "set=false";
	static protected $__pos__ = "type=number,set=false";
	static protected $__close_empty__ = "type=boolean";	
	protected $param;
	protected $attr;
	protected $name;
	protected $value;
	protected $plain;
	protected $pos;
	protected $close_empty = true;
	
	final protected function __str__(){
		return $this->get();
	}
	final protected function __new__($name="",$value=null){
		$this->name(trim($name));
		$this->value($value);
	}
	final protected function __add__($arg){
		$args = func_get_args();
		if(sizeof($args) == 2){
			$this->param($args[0],$args[1]);
		}else{
			$this->value($this->value().(string)$args[0]);
		}
		/***
			$tag = new Tag("hoge","aaa");
			eq("aaa",$tag->value());
			$tag->add("bbb");
			eq("aaabbb",$tag->value());
			$tag->add("ccc");
			eq("aaabbbccc",$tag->value());
		*/
	}
	final protected function __hash__(){
		$list = array();
		$src = $this->value();
		foreach($this->arParam() as $name => $param) $list[$name] = $param[1];

		while(self::setof($ctag,$src)){
			$result = $ctag->hash();

			if(isset($list[$ctag->name()])){
				if(!is_array($list[$ctag->name()])) $list[$ctag->name()] = array($list[$ctag->name()]);
				$list[$ctag->name()][] = $result;
			}else{
				$list[$ctag->name()] = $result;
			}
			$src = substr($src,strpos($src,$ctag->plain()) + strlen($ctag->plain()));
		}
		return (!empty($list)) ? $list : $src;
		/***
			$html = text('
				<div>aaaa</div>
				<div>bbbb</div>
				<div>cccc</div>
			');
			$tag = Tag::anyhow($html);
			eq(array("div"=>array("aaaa","bbbb","cccc")),$tag->hash());

			$tag = new Tag("hoge","aaa");
			eq("aaa",$tag->hash());

		 	$src = text('
						<tag aaa="bbb" selected>
							<abc>
								<def var="123">
									<ghi selected>hoge</ghi>
									<ghi>
										<jkl>rails</jkl>
										<mno>ruby</mno>
									</ghi>
									<ghi ab="true">django</ghi>
								</def>
							</abc>
						</tag>
					');
			Tag::setof($tag,$src,"tag");
			eq(array(
					"aaa"=>"bbb",
					"abc"=>array(
						"def"=>array(
							"var"=>"123",
							"ghi"=>array(
								"hoge",
								array(
									"jkl"=>"rails",
									"mno"=>"ruby"
								),
								array(
									"ab"=>"true",
								),
							)
						)
				)
			),$tag->hash());
		*/
	}
	final protected function setParam($name,$value){
		$this->param[strtolower($name)] = array($name,(is_bool($value) ? (($value) ? "true" : "false") : $value));
	}
	/**
	 * パラメータを取得
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return string
	 */
	final public function inParam($name,$default=null){
		$name = strtolower($name);
		$result = (isset($this->param[$name])) ? $this->param[$name] : null;
		return ($result === null) ? $default : $result[1];
		/***
			$tag = new Tag("hoge","abc");
			$tag->add("aaa","123")->add("bbb","456");
			eq("123",$tag->inParam("aaa","hoge"));
			eq("123",$tag->inParam("aAa","hoge"));
			eq("hoge",$tag->inParam("ccc","hoge"));
		 */
	}
	/**
	 * 開始タグを取得
	 * @return string
	 */
	public function start(){
		$param = $attr = "";
		foreach($this->arParam() as $p) $param .= " ".$p[0]."=\"".$p[1]."\"";
		foreach($this->arAttr() as $value) $attr .= (($value[0] == "<") ? "" : " ").$value;
		return "<".$this->name().$param.$attr.(($this->isClose_empty() && !$this->isValue()) ? " /" : "").">";
		/***
			$tag = new Tag("hoge","abc");
			$tag->add("aaa","123")->add("bbb","456");
			eq('<hoge aaa="123" bbb="456">',$tag->start());
			$tag = new Tag("hoge");
			$tag->add("aaa","123")->add("bbb","456");
			eq('<hoge aaa="123" bbb="456" />',$tag->start());
		 */
	}
	/**
	 * 終了タグを取得
	 * @return string
	 */
	public function end(){
		return (!$this->isClose_empty() || $this->isValue()) ? sprintf("</%s>",$this->name()) : "";
		/***
			$tag = new Tag("hoge","abc");
			eq('</hoge>',$tag->end());
			$tag = new Tag("hoge");
			eq('',$tag->end());
		 */
	}
	/**
	 * xmlとして取得
	 * @param string $encoding
	 * @return string
	 */
	public function get($encoding=null){
		return ((empty($encoding)) ? "" : "<?xml version=\"1.0\" encoding=\"".$encoding."\" ?>\n").$this->start().$this->value().$this->end();
		/***
			$tag = new Tag("hoge","abc");
			$tag->add("aaa","123")->add("bbb","456");
			eq('<hoge aaa="123" bbb="456">abc</hoge>',$tag->get());
			$result = text('
							<?xml version="1.0" encoding="utf-8" ?>
							<hoge aaa="123" bbb="456">abc</hoge>
						');
			eq($result,$tag->get("utf-8"));
		 */
		/***
			$tag = new Tag("textarea");
			eq("<textarea />",$tag->get());
			
			$tag = new Tag("textarea");
			$tag->close_empty(false);
			eq("<textarea></textarea>",$tag->get());
		 */
	}
	/**
	 * xmlとし出力する
	 * @param string $encoding
	 * @param string $name
	 */
	public function output($encoding=null,$name=null){
		Log::disable_display();
		header(sprintf("Content-Type: application/xml%s",(empty($name) ? "" : sprintf("; name=%s",$name))));
		print($this->get($encoding));
		exit;
	}
	/**
	 * 指定のタグを探索する
	 * @param string $tag_name
	 * @param int $offset
	 * @param int $length
	 * @return TagIterator
	 */
	public function in($tag_name,$offset=0,$length=0){
		return new TagIterator($tag_name,$this->value(),$offset,$length);
		/***
			$src = "<tag><a b='1' /><a>abc</a><b>0</b><a b='1' /><a /></tag>";
			if(Tag::setof($tag,$src,"tag")){
				$list = array();
				foreach($tag->in("a") as $a){
					$list[] = $a;
					eq("a",$a->name());
				}
				eq(4,sizeof($list));
			}
			$html = text('
				<div>aaaa</div>
				<div style="background: url(http://example.jp/example.png);">bbbb</div>
				<div>cccc</div>
			');
			Tag::setof($tag, '<div>'.$html.'</div>', 'div');
			$divs = array();
			foreach($tag->in("div") as $d){
				$divs[] = $d;
			}
			eq(3, count($divs));

			$html = text('
				<div>aaaa</div>
				<div>bbbb</div>
				<div>cccc</div>
			');
			Tag::setof($tag, '<div>'.$html.'</div>', 'div');
			$divs = array();
			foreach($tag->in("div") as $d){
				$divs[] = $d;
			}
			eq(3, count($divs));

			Tag::setof($tag,"<tag><data1 /><data2 /><data1 /><data3 /><data3 /><data2 /><data4 /></tag>","tag");
			$result = array();
			foreach($tag->in(array("data2","data3")) as $d){
				$result[] = $d;
			}
			eq("data2",$result[0]->name());
			eq("data3",$result[1]->name());
			eq("data3",$result[2]->name());
			eq("data2",$result[3]->name());
		*/
		/***
			# length_test
			Tag::setof($tag,"<tag><data1 p='1' /><data2 /><data1 p='2' /><data3 /><data3 /><data1 p='3' /><data1 p='4' /><data2 /><data4 /><data1 p='5' /></tag>","tag");
			$result = array();
			foreach($tag->in("data1",1,2) as $d){
				$result[] = $d;
			}
			eq(2,sizeof($result));
		*/
	}
	
	/**
	 * 指定のタグをすべて返す
	 * @param string $tag_name
	 * @param int $offset
	 * @param int $length
	 * @return array(Tag)
	 */
	public function in_all($tag_name,$offset=0,$length=0){
		$result = array();
		foreach($this->in($tag_name,$offset,$length) as $tag) $result[] = $tag;
		return $result;
		/***
			# length_test
			Tag::setof($tag,"<tag><data1 p='1' /><data2 /><data1 p='2' /><data3 /><data3 /><data1 p='3' /><data1 p='4' /><data2 /><data4 /><data1 p='5' /></tag>","tag");
			$result = $tag->in_all("data1",1,2);
			eq(2,sizeof($result));
		*/
	}
	/**
	 * パスで検索する
	 * @param string $path
	 * @param string $arg
	 * @return mixed
	 */
	public function f($path,$arg=null){
		$paths = explode(".",$path);
		$last = (strpos($path,"(") === false) ? null : array_pop($paths);
		$tag = clone($this);
		$route = array();
		if($arg !== null) $arg = (is_bool($arg)) ? (($arg) ? "true" : "false") : strval($arg);

		foreach($paths as $p){
			$pos = 0;
			if(preg_match("/^(.+)\[([\d]+?)\]$/",$p,$matchs)) list($tmp,$p,$pos) = $matchs;
			$tags = $tag->in_all($p,$pos,1);
			if(!isset($tags[0]) || !($tags[0] instanceof self)){
				$tag = null;
				break;
			}
			$route[] = $tag = $tags[0];
		}
		if($tag instanceof self){
			if($arg === null){
				switch($last){
					case "": return $tag;
					case "plain()": return $tag->plain();
					case "value()": return $tag->value();
					default:
						if(preg_match("/^(param|attr|in_all|in)\((.+?)\)$/",$last,$matchs)){
							list($null,$type,$name) = $matchs;
							switch($type){
								case "in_all": return $tag->in_all(trim($name));
								case "in": return $tag->in(trim($name));
								case "param": return $tag->inParam($name);
								case "attr": return $tag->isAttr($name);
							}
						}
						return null;
				}
			}
			if($arg instanceof self) $arg = $arg->get();
			if(is_bool($arg)) $arg = ($arg) ? "true" : "false";
			krsort($route,SORT_NUMERIC);
			$ltag = $rtag = null;
			$f = true;

			foreach($route as $r){
				$ltag = clone($r);
				if($f){
					switch($last){
						case "value()":
							$replace = $arg;
							break;
						default:
							if(preg_match("/^(param|attr|in_all|in)\((.+?)\)$/",$last,$matchs)){
								list($null,$type,$name) = $matchs;
								switch($type){
									case "param":
										$r->param($name,$arg);
										$replace = $r->get();
										break;
									case "attr":
										($arg === "true") ? $r->attr($name) :$r->rmAttr($name);
										$replace = $r->get();
										break;
									default:
										return null;
								}
							}
					}
					$f = false;
				}
				$r->value(empty($rtag) ? $replace : str_replace($rtag->plain(),$replace,$r->value()));
				$replace = $r->get();
				$rtag = clone($ltag);
			}
			$this->value(str_replace($ltag->plain(),$replace,$this->value()));
			return null;
		}
		return null;
		/***
			$src = "<tag><abc><def var='123'><ghi selected>hoge</ghi></def></abc></tag>";
			if(Tag::setof($tag,$src,"tag")){
				eq("hoge",$tag->f("abc.def.ghi.value()"));
				eq("123",$tag->f("abc.def.param(var)"));
				eq(true,$tag->f("abc.def.ghi.attr(selected)"));
				eq("<def var='123'><ghi selected>hoge</ghi></def>",$tag->f("abc.def.plain()"));
				eq(null,$tag->f("abc.def.xyz"));
			}
		 	$src = text('
						<tag>
							<abc>
								<def var="123">
									<ghi selected>hoge</ghi>
									<ghi>
										<jkl>rails</jkl>
									</ghi>
									<ghi ab="true">django</ghi>
								</def>
							</abc>
						</tag>
					');
			Tag::setof($tag,$src,"tag");
			eq("django",$tag->f("abc.def.ghi[2].value()"));
			eq("rails",$tag->f("abc.def.ghi[1].jkl.value()"));
			$tag->f("abc.def.ghi[2].value()","python");
			eq("python",$tag->f("abc.def.ghi[2].value()"));

			eq("123",$tag->f("abc.def.param(var)"));
			eq("true",$tag->f("abc.def.ghi[2].param(ab)"));
			$tag->f("abc.def.ghi[2].param(cd)",456);
			eq("456",$tag->f("abc.def.ghi[2].param(cd)"));

			eq(true,$tag->f("abc.def.ghi[0].attr(selected)"));
			eq(false,$tag->f("abc.def.ghi[1].attr(selected)"));
			$tag->f("abc.def.ghi[1].attr(selected)",true);
			eq(true,$tag->f("abc.def.ghi[1].attr(selected)"));
		*/
	}
	/**
	 * idで検索する
	 *
	 * @param string $name
	 * @return Tag
	 */
	public function id($name){
		if(preg_match("/<.+[\s]*id[\s]*=[\s]*([\"\'])".preg_quote($name)."\\1/",$this->value(),$match,PREG_OFFSET_CAPTURE)){
			if(self::setof($tag,substr($this->value(),$match[0][1]))) return $tag;
		}
		return null;
		/***
			$src = text('
						<aaa>
							<bbb id="DEF"></bbb>
							<ccc id="ABC">
								<ddd id="XYZ">hoge</ddd>
							</ccc>
						</aaa>
					');
			$tag = Tag::anyhow($src);
			eq("ddd",$tag->id("XYZ")->name());
			eq(null,$tag->id("xyz"));
		 */
	}

	/**
	 * ユニークな名前でTagとして作成する
	 * @param string $plain
	 * @return Tag
	 */
	final static public function anyhow($plain){
		$uniq = uniqid("Anyhow_");
		if(self::setof($tag,"<".$uniq.">".$plain."</".$uniq.">",$uniq)) return $tag;
		return new Exception();
		/***
		 	$src = "hoge";
			$tag = Tag::anyhow($src);
			eq("hoge",$tag->value());
		 */
	}
	/**
	 * Tagとして正しければTagインスタンスを作成する
	 * @param mixed $var
	 * @param string $plain
	 * @param string $name
	 * @return boolean
	 */
	final static public function setof(&$var,$plain,$name=""){
		if(empty($name) && preg_match("/<([\w\:\-]+)[\s][^>]*?>|<([\w\:\-]+)>/is",$plain,$parse)){
			$name = str_replace(array("\r\n","\r","\n"),"",(empty($parse[1]) ? $parse[2] : $parse[1]));
		}
		$qname = preg_quote($name);
		if(preg_match("/<(".$qname.")([\s][^>]*?)>|<(".$qname.")>/is",$plain,$parse,PREG_OFFSET_CAPTURE)){
			$var = new self();
			$var->pos = $parse[0][1];
			$balance = 0;
			$params = "";

			if(substr($parse[0][0],-2) == "/>"){
				$var->name = $parse[1][0];
				$var->plain = $parse[0][0];
				$params = $parse[2][0];
			}else if(preg_match_all("/<[\/]{0,1}".$qname."[\s][^>]*[^\/]>|<[\/]{0,1}".$qname."[\s]*>/is",$plain,$list,PREG_OFFSET_CAPTURE,$var->pos)){
				foreach($list[0] as $arg){
					if(($balance += (($arg[0][1] == "/") ? -1 : 1)) <= 0 &&
							preg_match("/^(<[\s]*(".$qname.")([\s]*[^>]*)>)(.*)(<[\s]*\/\\2[\s]*>)$/is",
								substr($plain,$var->pos,($arg[1] + strlen($arg[0]) - $var->pos)),
								$match
							)
					){
						$var->plain = $match[0];						
						$var->name = $match[2];
						$var->value = (empty($match[4])) ? null : $match[4];
						$params = $match[3];
						break;
					}
				}
			}
			if(!isset($var->plain)){
				$var->name = $parse[1][0];
				$var->plain = $parse[0][0];
				$params = $parse[2][0];
			}
			if(!empty($params)){
				if(preg_match_all("/[\s]+([\w\-\:]+)[\s]*=[\s]*([\"\'])([^\\2]*?)\\2/ms",$params,$param)){
					foreach($param[0] as $id => $value){
						$var->param($param[1][$id],$param[3][$id]);
						$params = str_replace($value,"",$params);
					}
				}
				if(preg_match_all("/([\w\-]+)/",$params,$attr)){
					foreach($attr[1] as $value) $var->attr($value);
				}
			}
			return true;
		}
		return false;
		/***
			$src = 'AAA<hoge aaa="123" BbB="456" selected><EE>ee</EE></hoge>ZZZZ';
			if(eq(true,Tag::setof($tag,$src))){
				eq('<EE>ee</EE>',$tag->value());
				eq(array("aaa"=>array("aaa","123"),"bbb"=>array("BbB","456")),$tag->arParam());
			}
			eq(false,Tag::setof($src,"abc"));
		*/
		/***
			# no_end
			$src = '<ae>';
			if(eq(true,Tag::setof($tag,$src,"ae"))){
				eq("<ae>",$tag->plain());
			}
			$src = '<aa><bb><ae abc="123"></bb></aa><ae>456</qe>';
			if(eq(true,Tag::setof($tag,$src,"ae"))){
				eq('<ae abc="123">',$tag->plain());
				eq("123",$tag->inParam("abc"));
			}
		 */
	}
	/**
	 * 指定のタグで閉じていないものを閉じる
	 * @param string $src
	 * @param string $name
	 * @return string
	 */
	static public function xhtmlnize($src,$name){
		/***
			eq("<img src='hoge' />",Tag::xhtmlnize("<img src='hoge'>","img"));
			eq("<img src='hoge' />",Tag::xhtmlnize("<img src='hoge' />","img"));
			eq("<a><br /></a>",Tag::xhtmlnize("<a><br></a>","br"));
			eq("<br /><img src='hoge' /><br />",Tag::xhtmlnize("<br><img src='hoge'><br>","img","br"));
			eq("<br /><brc><br />",Tag::xhtmlnize("<br><brc><br>","br"));
			eq("<meta name='description' />\n<title>a</title>",Tag::xhtmlnize("<meta name='description' />\n<title>a</title>","meta"));
		 */
		$args = func_get_args();
		array_shift($args);
		foreach($args as $name){
			if(preg_match_all(sprintf("/(<%s>)|(<%s[\s][^>]*[^\/]>)/is",$name,$name),$src,$link)){
				foreach($link[0] as $value) $src = str_replace($value,substr($value,0,-1)." />",$src);
			}
		}
		return $src;
	}
	/**
	 * CDATA形式にして返す
	 * @param string $value
	 * @return string
	 */
	static public function xmltext($value){
		if(is_string($value) && strpos($value,"<![CDATA[") === false && (preg_match("/<.+>/s",$value) || preg_match("/\&[^#\da-zA-Z]+;/i",$value))) return "<![CDATA[".$value."]]>";
		return $value;
		/***
			eq("<![CDATA[<abc />]]>",Tag::xmltext("<abc />"));
		 */
	}
	/**
	 * CDATA形式から値を取り出す
	 * @param string $value
	 * @return string
	 */
	static public function cdata($value){
		if(preg_match_all("/<\!\[CDATA\[(.+?)\]\]>/ims",$value,$match)){
			foreach($match[1] as $key => $v) $value = str_replace($match[0][$key],$v,$value);
		}
		return $value;
		/***
			eq("<abc />",Tag::cdata("<![CDATA[<abc />]]>"));
		 */
	}
	/**
	 * XMLコメントを削除する
	 * @param string $src
	 * @return string
	 */
	static public function uncomment($src){
		return preg_replace("/<!--.+?-->/s","",$src);
		/***
			$text = text('
							abc
							<!--
								comment
							-->
							def
						');
			eq("abc\n\ndef",Tag::uncomment($text));
		*/
	}
}
?>