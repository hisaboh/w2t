<?php
Rhaco::import("core.Tag");
Rhaco::import("core.Http");
Rhaco::import("core.File");
Rhaco::import("core.Paginator");
Rhaco::import("core.Templf");
/**
 * テンプレートを処理する
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Template extends Object{
	static private $BASE_PATH;
	static private $BASE_URL;
	static private $CACHE = false;
	static private $CACHE_TIME = 86400;
	static protected $__vars__ = "type=variable{}";
	static protected $__statics__ = "type=string{}";
	static protected $__ab__ = "type=boolean";
	protected $statics = array();
	protected $vars = array();
	protected $url;
	protected $path;
	protected $filename;
	private $selected_template;

	static public function __import__(){
		self::$BASE_PATH = Rhaco::def("core.Template@path",Rhaco::path("templates"));
		self::$BASE_URL = Rhaco::def("core.Template@url",Rhaco::url("templates"));
		self::$CACHE = Rhaco::def("core.Template@cache",false);
		self::$CACHE_TIME = Rhaco::def("core.Template@time",86400);
	}
	protected function __new__($dict=null){
		if(!empty($dict)) $this->dict($dict);
		$this->statics["t"] = "Templf";
	}
	protected function __cp__($obj){
		if($obj instanceof Object){
			foreach($obj->access_members() as $name => $value) $this->vars($name,$obj->{"fm".ucfirst($name)}());
		}else if(is_array($obj)){
			foreach($obj as $name => $value) $this->vars($name,$value);
		}else{
			throw new InvalidArgumentException("cp");
		}
	}
	protected function setUrl($url){
		if($url !== null && substr($url,-1) !== "/") $url .= "/";
		$this->url = $url;
	}
	protected function setPath($path){
		if($path !== null && substr($path,-1) !== "/") $path .= "/";
		$this->path = $path;
	}
	protected function setFilename($filename){
		if(empty($this->path)) $this->path(self::$BASE_PATH);
		$this->filename = File::absolute($this->path,$filename);
	}
	protected function verifyFilename($path=null){
		if($path === null) $path = $this->filename;
		$path = File::absolute(self::$BASE_PATH,$path);
		return (!empty($path) && is_file($path));
	}
	/**
	 * ファイルから生成する
	 * @param string $filename
	 * @return string
	 */
	public function read($filename,$template_name=null){
		$this->filename($filename);
		$this->selected_template = $template_name;
		$cfilename = $this->filename.$this->selected_template;

		if(!self::$CACHE || File::isExpiry($cfilename,self::$CACHE_TIME)){
			if(strpos($filename,"://") === false){
				$src = $this->parse(File::read($this->filename));
			}else{
				if(empty($this->url)) $this->url = $this->filename;
				$src = $this->parse(Http::read($this->filename));
			}
			if(self::$CACHE) File::cwrite($cfilename,$src);
		}else{
			$src = File::cread($cfilename);
		}
		$src = $this->exec($src);
		$this->call_modules("after_read_template",$src);
		return $this->replace_ptag($src);
	}
	/**
	 * printする
	 *
	 * @param string $filename
	 */
	public function output($filename,$template_name=null){
		print($this->read($filename,$template_name));
		exit;
	}
	/**
	 * 文字列から生成する
	 * @param string $src
	 * @return string
	 */
	public function execute($src,$template_name=null){
		$this->selected_template = $template_name;
		$src = $this->replace_ptag($this->exec($this->parse($src)));
		return $src;
		/***
			$src = text('
				<body>
					{$abc}{$def}
						{$ghi}	{$hhh["a"]}
					<a href="./hoge.html">{$abc}</a>
					<img src="../img/abc.png"> {$ooo.yyy}
					<form action="{$ooo.xxx}">
					</form>
				</body>
			');
			$result = text('
				<body>
					AAA
						B	1
					<a href="http://rhaco.org/tmp/hoge.html">AAA</a>
					<img src="http://rhaco.org/img/abc.png"> fuga
					<form action="index.php">
					</form>
				</body>
			');
			$obj = new stdClass();
			$obj->xxx = "index.php";
			$obj->yyy = "fuga";

			$template = new Template("url=http://rhaco.org/tmp");
			$template->vars("abc","AAA");
			$template->vars("def",null);
			$template->vars("ghi","B");
			$template->vars("ooo",$obj);
			$template->vars("hhh",array("a"=>1,"b"=>2));
			eq($result,$template->execute($src));
		*/
	}
	private function replace_ptag($src){
		return str_replace(array("__PHP_TAG_ESCAPE_START__","__PHP_TAG_ESCAPE_END__"),array("<?","?>"),$src);
	}
	private function parse($src){
		if(empty($this->url)) $this->url(self::$BASE_URL);
		$src = preg_replace("/([\w])\->/","\\1__PHP_ARROW__",$src);
		if(preg_match_all("/<\?(?!php[\s\n])[\w]+ .*?\?>/s",$src,$null)){
			foreach($null[0] as $value) $src = str_replace($value,"__PHP_TAG_ESCAPE_START__".substr($value,2,-2)."__PHP_TAG_ESCAPE_END__",$src);
		}
		$this->call_modules("init_template",$src);
		$src = $this->rtinvalid($this->rtcomment($this->rtblock($this->rttemplate($src),$this->filename)));
		$this->call_modules("before_template",$src);
		$src = $this->htmlList($src);
		$src = $this->htmlForm($src);
		$src = $this->rtpager($src);
		$src = $this->rtloop($src);
		$this->call_modules("after_template",$src);
		$src = str_replace("__PHP_ARROW__","->",$src);
		$src = $this->parserPluralMessage($src);
		$src = $this->parseMessage($src);
		$src = $this->parsePrintVariable($src);
		foreach($this->statics as $key => $value) $src = $this->toStaticVariable($value,$key,$src);
		$php = array(" ?>","<?php ","->");
		$str = array("PHP_TAG_END","PHP_TAG_START","PHP_ARROW");
		return str_replace($str,$php,$this->parseUrl(str_replace($php,$str,$src),File::absolute($this->url,File::relative($this->path,dirname($this->filename)))));
		/***
			uc($filter,'
				public function init_template($src){
					$src = "====\n".$src."\n====";
				}
				public function before_template($src){
					$src = "____\n".$src."\n____";
				}
				public function after_template($src){
					$src = "####\n".$src."\n####";
				}
			');
			$src = text('
					hogehoge
				');
			$result = text('
					####
					____
					====
					hogehoge
					====
					____
					####
				');
			$template = new Template();
			$template->add_modules(new $filter());
			eq($result,$template->execute($src));
		 */
	}
	final private function rttemplate($src){
		$values = array();
		$bool = false;
		while(Tag::setof($tag,$src,"rt:template")){
			$src = str_replace($tag->plain(),"",$src);
			$values[$tag->inParam("name")] = $tag->value();
			$src = str_replace($tag->plain(),"",$src);
			$bool = true;
		}
		if(!empty($this->selected_template)){
			if(!array_key_exists($this->selected_template,$values)) throw new Exception("undef rt:template ".$this->selected_template);
			return $values[$this->selected_template];
		}
		return ($bool) ? implode($values) : $src;
		/***
			$template = new Template();
			$src = text('
				AAAA
				<rt:template name="aa">
					aa
				</rt:template>
				BBBB
				<rt:template name="bb">
					bb
				</rt:template>
				CCCC
				<rt:template name="cc">
					cc
				</rt:template>
			');
			eq("	bb\n",$template->execute($src,"bb"));
		 */
	}
	final private function rtcomment($src){
		while(Tag::setof($tag,$src,"rt:comment")) $src = str_replace($tag->plain(),"",$src);
		return $src;
	}
	final private function rtblock($src,$filename){
		if(strpos($src,"rt:block") !== false || strpos($src,"rt:extends") !== false){
			$blocks = $paths = array();
			while(Tag::setof($xml,$this->rtcomment($src),"rt:extends")){
				$bxml = Tag::anyhow($src);
				foreach($bxml->in("rt:block") as $block){
					$name = $block->inParam("name");
					if(!empty($name) && !array_key_exists($name,$blocks)){
						$blocks[$name] = $block->value();
						$paths[$name] = $filename;
					}
				}
				if($xml->isParam("href")){
					$src = File::read($filename = File::absolute(dirname($filename),$xml->inParam("href")));
					$this->filename = $filename;
				}else{
					$src = File::read($this->filename);
				}
				$this->selected_template = $xml->inParam("name");
				$src = $this->rttemplate($src);
			}
			if(empty($blocks)){
				$bxml = Tag::anyhow($src);
				foreach($bxml->in("rt:block") as $block) $src = str_replace($block->plain(),$block->value(),$src);
			}else{
				while(Tag::setof($xml,$src,"rt:block")){
					$xml = Tag::anyhow($src);
					foreach($xml->in("rt:block") as $block){
						$name = $block->inParam("name");
						$src = str_replace($block->plain(),
								((array_key_exists($name,$blocks)) ?
										$this->parseUrl($blocks[$name],File::absolute($this->url,File::relative($this->path,dirname($paths[$name])))) :
										$block->value())
									,$src);
					}
				}
			}
		}
		return $src;
		/***
			ftmp("core/template/base.html",'
					=======================
					<rt:block name="aaa">
					base aaa
					</rt:block>
					<rt:block name="bbb">
					base bbb
					</rt:block>
					<rt:block name="ccc">
					base ccc
					</rt:block>
					<rt:block name="ddd">
					base ddd
					</rt:block>
					=======================
				');
			ftmp("core/template/extends1.html",'
					<rt:extends href="base.html" />

					<rt:block name="aaa">
					extends1 aaa
					</rt:block>

					<rt:block name="ddd">
					extends1 ddd
					<rt:loop param="abc" var="ab">
						{$loop_key}:{$loop_counter} {$ab}
					</rt:loop>
					<rt:if param="abc">
					aa
					</rt:if>
					<rt:if param="aa" value="1">
					bb
					</rt:if>
					<rt:if param="aa" value="2">
					bb
					<rt:else />
					cc
					</rt:if>
					<rt:if param="zz">
					zz
					</rt:if>
					<rt:if param="aa">
					aa
					</rt:if>
					<rt:if param="tt">
					true
					</rt:if>
					<rt:if param="ff">
					false
					</rt:if>
					</rt:block>
				');
			ftmp("core/template/sub/extends2.html",'
					<rt:extends href="../extends1.html" />

					<rt:block name="aaa">
					<a href="hoge/fuga.html">fuga</a>
					<a href="{$newurl}/abc.html">abc</a>
					sub extends2 aaa
					</rt:block>

					<rt:block name="ccc">
					sub extends2 ccc
					</rt:block>
				');

			$template = new Template("url=http://rhaco.org,path=".ftpath()."/core/template");
			$template->vars("newurl","http://hoge.ho");
			$template->vars("abc",array(1,2,3));
			$template->vars("aa",1);
			$template->vars("zz",null);
			$template->vars("ff",false);
			$template->vars("tt",true);
			$result = $template->read("sub/extends2.html");
			$ex = text('
						=======================

						<a href="http://rhaco.org/sub/hoge/fuga.html">fuga</a>
						<a href="http://hoge.ho/abc.html">abc</a>
						sub extends2 aaa


						base bbb


						sub extends2 ccc


						extends1 ddd
							0:1 1
							1:2 2
							2:3 3
						aa
						bb
						cc
						aa
						true

						=======================
					');
			eq($ex,$result);
		 */
	}
	final private function rtloop($src){
		if(strpos($src,"rt:loop") !== false){
			while(Tag::setof($tag,$src,"rt:loop")){
				$param = ($tag->isParam("param")) ? $this->getVariableString($this->parsePlainVariable($tag->inParam("param"))) : "";
				$var = "\$".$tag->inParam("var","loop_var");
				$key = "\$".$tag->inParam("key","loop_key");
				$counter = "\$".$tag->inParam("counter","loop_counter");
				$offset = $tag->inParam("offset",1);
				$limit = $tag->inParam("limit",0);
				$reverse = (strtolower($tag->inParam("reverse") === "true"));
				$uniq = uniqid("");
				$countname = "\$_COUNT_".$uniq;
				$offsetname	= "\$_OFFSET_".$uniq;
				$limitname = "\$_LIMIT_".$uniq;
				$varname = "\$_".$uniq;
	
				$src = str_replace(
							$tag->plain(),
							sprintf("<?php try{ ?>"
									."<?php "
										." %s=1; %s=%d; %s=%d; %s=%s;"
										." if(is_array(%s)){"
											."if(%s) krsort(%s);"
											." foreach(%s as %s => %s){"
												." if(%s <= %s){"
													." %s=%s;"
									." ?>"
													."%s"
									."<?php "
												." }"
												." %s++;"
												." if(%s > 0 && %s >= %s){break;}"
											." }"
										." }"
										." unset(%s,%s,%s,%s,%s,%s,%s);"
									." ?>"
									."<?php }catch(Exception \$e){ Exceptions::add(\$e,'core.Template'); } ?>"
									,$countname,$offsetname,$offset,$limitname,(($limit>0) ? ($offset + $limit) : 0),$varname,$param
									,$varname
									,(($reverse) ? "true" : "false"),$varname
									,$varname,$key,$var
									,$offsetname,$countname
									,$counter,$countname
									,$tag->value()
									,$countname
									,$limitname,$countname,$limitname
									,$var,$counter,$key,$countname,$offsetname,$limitname,$varname
							)
							,$src
						);
			}
		}
		return $this->rtif($src);
		/***
			$src = text('
						<rt:loop param="abc">
						{$loop_counter}: {$loop_key} => {$loop_var}
						</rt:loop>
						hoge
					');
			$result = text('
							1: A => 456
							2: B => 789
							3: C => 010
							hoge
						');
			$template = new Template();
			$template->vars("abc",array("A"=>"456","B"=>"789","C"=>"010"));
			eq($result,$template->execute($src));
		*/
		/***
			$src = text('
						<rt:loop param="abc" offset="2" limit="1">
						{$loop_counter}: {$loop_key} => {$loop_var}
						</rt:loop>
						hoge
					');
			$result = text('
							2: B => 789
							hoge
						');
			$template = new Template();
			$template->vars("abc",array("A"=>"456","B"=>"789","C"=>"010"));
			eq($result,$template->execute($src));
		*/
	}
	final private function rtif($src){
		if(strpos($src,"rt:if") !== false){
			while(Tag::setof($tag,$src,"rt:if")){
				if(!$tag->isParam("param")) throw new Exception("if");
				$arg1 = $this->getVariableString($this->parsePlainVariable($tag->inParam("param")));
	
				if($tag->isParam("value")){
					$arg2 = $this->parsePlainVariable($tag->inParam("value"));
					if($arg2 == "true" || $arg2 == "false" || ctype_digit($arg2)){
						$cond = sprintf("<?php if(%s === %s || %s === \"%s\"){ ?>",$arg1,$arg2,$arg1,$arg2);
					}else{
						if($arg2[0] != "\$") $arg2 = "\"".$arg2."\"";
						$cond = sprintf("<?php if(%s === %s){ ?>",$arg1,$arg2);
					}
				}else{
					$uniq = uniqid("\$I");
					$cond = sprintf("<?php %s=%s; ?>",$uniq,$arg1)
								.sprintf("<?php if(%s !== null && %s !== false && ( (!is_string(%s) && !is_array(%s)) || (is_string(%s) && %s !== \"\") || (is_array(%s) && !empty(%s)) ) ){ ?>",$uniq,$uniq,$uniq,$uniq,$uniq,$uniq,$uniq,$uniq);
				}
				$src = str_replace(
							$tag->plain()
							,"<?php try{ ?>".$cond
								.preg_replace("/<rt\:else[\s]*\/>/i","<?php }else{ ?>",$tag->value())
							."<?php } ?>"."<?php }catch(Exception \$e){ Exceptions::add(\$e,'core.Template'); } ?>"
							,$src
						);
			}
		}
		return $src;
		/***
			$src = text('<rt:if param="abc">hoge</rt:if>');
			$result = text('hoge');
			$template = new Template();
			$template->vars("abc",true);
			eq($result,$template->execute($src));

			$src = text('<rt:if param="abc" value="xyz">hoge</rt:if>');
			$result = text('hoge');
			$template = new Template();
			$template->vars("abc","xyz");
			eq($result,$template->execute($src));
		*/
	}
	final private function rtpager($src){
		if(strpos($src,"rt:pager") !== false){
			while(Tag::setof($tag,$src,"rt:pager")){
				$param = $this->getVariableString($this->parsePlainVariable($tag->inParam("param","paginator")));
				$func = sprintf("<?php try{ ?><?php if(%s instanceof Paginator){ ?>",$param);
				if($tag->isValue()){
					$func .= $tag->value();
				}else{
					$uniq = uniqid("");
					$name = "\$_PAGER_".$uniq;
					$counter_var = "\$_COUNTER_".$uniq;
					$tagtype = $tag->inParam("tag");
					$href = $tag->inParam("href","?");
					$stag = (empty($tagtype)) ? "" : "<".$tagtype.">";
					$etag = (empty($tagtype)) ? "" : "</".$tagtype.">";
					$navi = array_change_key_case(array_flip(explode(",",$tag->inParam("navi","prev,next,first,last,counter"))));
					$counter = $tag->inParam("counter",50);
					if(isset($navi["prev"])) $func .= sprintf('<?php if(%s->isPrev()){ ?>%s<a href="%s{%s.query(%s.prev())}">%s</a>%s<?php } ?>',$param,$stag,$href,$param,$param,gettext("Prev"),$etag);
					if(isset($navi["first"])) $func .= sprintf('<?php if(%s->isFirst(%d)){ ?>%s<a href="%s{%s.query(%s.first())}">{%s.first()}</a>%s%s...%s<?php } ?>',$param,$counter,$stag,$href,$param,$param,$param,$etag,$stag,$etag);
					if(isset($navi["counter"])){
						$func .= sprintf('<?php for(%s=%s->whFirst(%d);%s<=%s->whLast(%d);%s++){ ?>',$counter_var,$param,$counter,$counter_var,$param,$counter,$counter_var);
						$func .= sprintf('%s<?php if(%s == %s->current()){ ?><strong>{%s}</strong><?php }else{ ?><a href="%s{%s.query(%s)}">{%s}</a><?php } ?>%s',$stag,$counter_var,$param,$counter_var,$href,$param,$counter_var,$counter_var,$etag);
						$func .= "<?php } ?>";
					}
					if(isset($navi["last"])) $func .= sprintf('<?php if(%s->isLast(%d)){ ?>%s...%s%s<a href="%s{%s.query(%s.last())}">{%s.last()}</a>%s<?php } ?>',$param,$counter,$stag,$etag,$stag,$href,$param,$param,$param,$etag);
					if(isset($navi["next"])) $func .= sprintf('<?php if(%s->isNext()){ ?>%s<a href="%s{%s.query(%s.next())}">%s</a>%s<?php } ?>',$param,$stag,$href,$param,$param,gettext("Next"),$etag);
				}
				$func .= "<?php } ?><?php }catch(Exception \$e){ Exceptions::add(\$e,'core.Template'); } ?>";
				$src = str_replace($tag->plain(),$func,$src);
			}
		}
		return $this->rtloop($src);
		/***
			$template = new Template();

			$template->vars("paginator",new Paginator(10,2,100));
			$src = '<rt:pager param="paginator" counter="3" tag="span" />';
			$result = text('<span><a href="?page=1&">Prev</a></span><span><a href="?page=1&">1</a></span><span>...</span><span><a href="?page=1&">1</a></span><span><strong>2</strong></span><span><a href="?page=3&">3</a></span><span>...</span><span><a href="?page=10&">10</a></span><span><a href="?page=3&">Next</a></span>');
			eq($result,$template->execute($src));

			$template->vars("paginator",new Paginator(10,1,100));
			$src = '<rt:pager param="paginator" counter="3" />';
			$result = text('<strong>1</strong><a href="?page=2&">2</a><a href="?page=3&">3</a>...<a href="?page=10&">10</a><a href="?page=2&">Next</a>');
			eq($result,$template->execute($src));

			$template->vars("paginator",new Paginator(10,10,100));
			$src = '<rt:pager param="paginator" counter="3" tag="span" />';
			$result = text('<span><a href="?page=9&">Prev</a></span><span><a href="?page=1&">1</a></span><span>...</span><span><a href="?page=8&">8</a></span><span><a href="?page=9&">9</a></span><span><strong>10</strong></span>');
			eq($result,$template->execute($src));
		*/
	}
	final private function rtinvalid($src){
		if(strpos($src,"rt:invalid") !== false){
			while(Tag::setof($tag,$src,"rt:invalid")){
				$param = $this->parsePlainVariable($tag->inParam("param","exceptions"));
				$var = $this->parsePlainVariable($tag->inParam("var","exception"));
				$class = $this->parsePlainVariable($tag->inParam("class","exceptions"));
				$type = $this->parsePlainVariable($tag->inParam("type","ul"));
				if($param[0] !== "\$") $param = "\"".$param."\"";
				$param_name = "\$P".uniqid("");
				$value = $tag->value();
				
				if(empty($value)){
					switch($type){
						case "ul":
							$value = sprintf("<ul class=\"%s\" rt:param=\"%s\" rt:var=\"%s\">"
											."<li>{\$%s}</li>"
										."</ul>",$class,$param_name,$var,$var);
							break;
						case "plain":
							$value = sprintf("<rt:loop param=\"%s\" var=\"%s\">\n"
												."{\$%s}"
											."</rt:loop>\n",$param_name,$var,$var);
							break;
					}
				}
				$src = str_replace(
							$tag->plain(),
							sprintf("<?php if(Exceptions::invalid(%s)){ ?>"
										."<?php %s = Exceptions::messages(%s); ?>"
										."%s"
									."<?php } ?>",$param,$param_name,$param,$value),
							$src);
			}
		}
		return $src;
	}
	final private function parserPluralMessage($src){
		if(preg_match_all("/_p\("."(([\"\']).+?\\2)".","."(([\"\']).+?\\4)".","."(([\d]+)|(\{\\\$.+\}))"."\)/",$src,$match)){
			$stringList = array(); // 1 3 5
			foreach($match[1] as $key => $value) $stringList[$match[0][$key]] = sprintf("<?php try{ ?>"."<?php print(ngettext(%s,%s,%d)); ?>"."<?php }catch(Exception \$e){Exceptions::add(\$e,'core.Template'); } ?>",
																					$value,$match[3][$key],$this->parsePlainVariable($match[5][$key]));
			foreach($stringList as $baseString => $string) $src = str_replace($baseString,$string,$src);
			unset($stringList,$match);
		}
		return $src;
	}
	final private function parseMessage($src){
		if(preg_match_all("/_\((([\"\']).+?\\2)\)/",$src,$match)){
			$stringList = array();
			foreach($match[1] as $key => $value) $stringList[$match[0][$key]] = sprintf("<?php try{ ?>"."<?php print(gettext(%s)); ?>"."<?php }catch(Exception \$e){ Exceptions::add(\$e,'core.Template'); } ?>",$value);
			foreach($stringList as $baseString => $string) $src = str_replace($baseString,$string,$src);
			unset($stringList,$match);
		}
		return $src;
	}
	final private function parsePrintVariable($src){
		foreach($this->matchVariable($src) as $variable){
			$name = $this->parsePlainVariable($variable);
			$value = "<?php try{ ?>"."<?php print(".$name."); ?>"."<?php }catch(Exception \$e){ Exceptions::add(\$e,'core.Template'); } ?>";
			$src = str_replace(array($variable."\n",$variable),array($value."\n\n",$value),$src);
		}
		return $src;
	}
	final private function toStaticVariable($class,$var,$src){
		return str_replace("\$".$var."->",$class."::",$src);
	}
	final private function matchVariable($src){
		$hash = array();
		while(preg_match("/({(\\$[\$\w][^\t]*)})/s",$src,$vars,PREG_OFFSET_CAPTURE)){
			list($value,$pos) = $vars[1];
			if($value == "") break;
			if(substr_count($value,"}") > 1){
				for($i=0,$start=0,$end=0;$i<strlen($value);$i++){
					if($value[$i] == "{"){
						$start++;
					}else if($value[$i] == "}"){
						if($start == ++$end){
							$value = substr($value,0,$i+1);
							break;
						}
					}
				}
			}
			$length	= strlen($value);
			$src = substr($src,$pos + $length);
			$hash[sprintf("%03d_%s",$length,$value)] = $value;
		}
		krsort($hash,SORT_STRING);
		return $hash;
	}
	final private function parsePlainVariable($src){
		while(true){
			$array = $this->matchVariable($src);
			if(sizeof($array) <= 0)	break;
			foreach($array as $variable){
				$tmp = $variable;
				if(preg_match_all("/([\"\'])([^\\1]+)\\1/",$variable,$match)){
					foreach($match[2] as $value) $tmp = str_replace($value,str_replace(".","__PERIOD__",$value),$tmp);
				}
				$src = str_replace($variable,str_replace(".","->",substr($tmp,1,-1)),$src);
			}
		}
		return str_replace("[]","",str_replace("__PERIOD__",".",$src));
	}
	final private function parseUrl($src,$base){
		if(substr($base,-1) !== "/") $base = $base."/";
		if(preg_match_all("/<[^<]+?[\s](href|src|action)[\s]*=[\s]*([\"\'])([^\\2]+?)\\2[^>]*?>/msi",$src,$match)){
			foreach($match[1] as $key => $param){
				if(!preg_match("/(^javascript:)|(^mailto:)|(^[\w]+:\/\/)|(^[#\?])|(^PHP_TAG_START)|(^\{\\$)/",$match[3][$key])){
					$src = str_replace(
						$match[0][$key],
						str_replace($match[3][$key],File::absolute($base,$match[3][$key]),$match[0][$key]),
						$src
					);
				}
			}
		}
		return $src;
	}
	final private function getVariableString($src){
		return ($src[0] == "$") ? $src : "\$".$src;
	}

	final private function htmlForm($src){
		$tag = Tag::anyhow($src);
		foreach($tag->in("form") as $obj){
			if($this->isReference($obj)){
				foreach($obj->in(array("input","select","textarea")) as $tag){
					if(!$tag->isParam("rt:ref") && ($tag->isParam("name") || $tag->isParam("id"))){
						switch(strtolower($tag->inParam("type","text"))){
							case "button":
							case "submit":
								break;
							case "file":
								$obj->param("enctype","multipart/form-data");
								$obj->param("method","post");
								break;
							default:
								$tag->param("rt:ref","true");
								$obj->value(str_replace($tag->plain(),$tag->get(),$obj->value()));
						}
					}
				}
			}
			$src = str_replace($obj->plain(),$obj->get(),$src);
		}
		return $this->htmlInput($src);
	}
	final private function htmlInput($src){
		$tag = Tag::anyhow($src);
		foreach($tag->in(array("input","textarea","select")) as $obj){
			if("" != ($originalName = $obj->inParam("name",$obj->inParam("id","")))){
				$type = strtolower($obj->inParam("type","text"));
				$name = $this->parsePlainVariable($this->getFormVariableName($originalName));
				$ename = $this->toFormElementName($originalName);
				$lname = strtolower($obj->name());
				$change = false;

				if(substr($originalName,-2) !== "[]" && ($type == "checkbox" || $obj->isAttr("multiple"))){
					$obj->param("name",$ename."[]");
					$change = true;
				}else if($obj->inParam("name") !== $ename){
					$obj->param("name",$ename);
					$change = true;
				}
				if($obj->isParam("rt:param")){
					switch($lname){
						case "select":
							$value = sprintf("<rt:loop param=\"%s\" var=\"%s\" counter=\"%s\" key=\"%s\" offset=\"%s\" limit=\"%s\">"
											."<option value=\"{\$%s}\">{\$%s}</option>"
											."</rt:loop>"
											,$obj->inParam("rt:param"),$obj->inParam("rt:var","loop_var"),$obj->inParam("rt:counter","loop_counter"),$obj->inParam("rt:key","loop_key"),$obj->inParam("rt:offset",0),$obj->inParam("rt:limit",0)
											,$obj->inParam("rt:key","loop_key"),$obj->inParam("rt:var","loop_var")
										);
							$obj->value($this->rtloop($value));
							if($obj->isParam("rt:null")) $obj->value('<option value="">'.$obj->inParam("rt:null").'</option>'.$obj->value());
					}
					$obj->rmParam("rt:param","rt:var","rt:counter","rt:offset","rt:limit","rt:null");
					$change = true;
				}
				if($this->isReference($obj)){
					switch($lname){
						case "textarea":
							$obj->value(sprintf("{\$t.htmlencode(%s)}",((preg_match("/^{\$(.+)}$/",$originalName,$match)) ? "{\$\$".$match[1]."}" : "{\$".$originalName."}")));
							break;
						case "select":
							$select = $obj->value();
							foreach($obj->in("option") as $option){
								$value = $this->parsePlainVariable($option->inParam("value"));
								if($value[0] != "\$") $value = sprintf("'%s'",$value);
								$option->rmAttr("selected");
								$option->attr($this->check_selected($name,$value,"selected"));
								$select = str_replace($option->plain(),$option->get(),$select);
							}
							$obj->value($select);
							break;
						case "input":
							switch($type){
								case "checkbox":
								case "radio":
									$value = $this->parsePlainVariable($obj->inParam("value","true"));
									$value = (substr($value,0,1) != "\$") ? sprintf("'%s'",$value) : $value;
									$obj->rmAttr("checked");
									$obj->attr($this->check_selected($name,$value,"checked"));
									break;
								case "text":
								case "hidden":
								case "password":
									$obj->param("value",sprintf("{\$t.htmlencode(%s)}",
																((preg_match("/^\{\$(.+)\}$/",$originalName,$match)) ?
																	"{\$\$".$match[1]."}" :
																	"{\$".$originalName."}")));
							}
							break;
					}
					$change = true;
				}
				if($change){
					switch($lname){
						case "textarea":
						case "select":
							$obj->close_empty(false);
					}
					$src = str_replace($obj->plain(),$obj->get(),$src);
				}
			}
		}
		return $src;
		/***
			$src = text('
						<form rt:ref="true">
							<input type="text" name="aaa" />
							<input type="checkbox" name="bbb" value="hoge" />hoge
							<input type="checkbox" name="bbb" value="fuga" />fuga
							<input type="checkbox" name="eee" value="true" />foo
							<input type="checkbox" name="fff" value="false" />foo
							<input type="submit" />
							<textarea name="aaa"></textarea>

							<select name="ddd" size="5" multiple>
								<option value="123">123</option>
								<option value="456">456</option>
								<option value="789">789</option>
							</select>
							<select name="XYZ" rt:param="xyz"></select>
						</form>
					');
			$result = text('
						<form>
							<input type="text" name="aaa" value="hogehoge" />
							<input type="checkbox" name="bbb[]" value="hoge" checked />hoge
							<input type="checkbox" name="bbb[]" value="fuga" />fuga
							<input type="checkbox" name="eee[]" value="true" checked />foo
							<input type="checkbox" name="fff[]" value="false" checked />foo
							<input type="submit" />
							<textarea name="aaa">hogehoge</textarea>

							<select name="ddd[]" size="5" multiple>
								<option value="123">123</option>
								<option value="456" selected>456</option>
								<option value="789" selected>789</option>
							</select>
							<select name="XYZ"><option value="A">456</option><option value="B" selected>789</option><option value="C">010</option></select>
						</form>
						');
			$template = new Template();
			$template->vars("aaa","hogehoge");
			$template->vars("bbb","hoge");
			$template->vars("XYZ","B");
			$template->vars("xyz",array("A"=>"456","B"=>"789","C"=>"010"));
			$template->vars("ddd",array("456","789"));
			$template->vars("eee",true);
			$template->vars("fff",false);
			eq($result,$template->execute($src));

			$src = text('
						<form rt:ref="true">
							<select name="ddd" rt:param="abc">
							</select>
						</form>
					');
			$result = text('
						<form>
							<select name="ddd"><option value="123">123</option><option value="456" selected>456</option><option value="789">789</option></select>
						</form>
						');
			$template = new Template();
			$template->vars("abc",array(123=>123,456=>456,789=>789));
			$template->vars("ddd","456");
			eq($result,$template->execute($src));

			$src = text('
						<form rt:ref="true">
						<rt:loop param="abc" var="v">
						<input type="checkbox" name="ddd" value="{$v}" />
						</rt:loop>
						</form>
					');
			$result = text('
							<form>
							<input type="checkbox" name="ddd[]" value="123" />
							<input type="checkbox" name="ddd[]" value="456" checked />
							<input type="checkbox" name="ddd[]" value="789" />
							</form>
						');
			$template = new Template();
			$template->vars("abc",array(123=>123,456=>456,789=>789));
			$template->vars("ddd","456");
			eq($result,$template->execute($src));
			
		*/
		/***
			# textarea
			$src = text('
							<form>
								<textarea name="hoge"></textarea>
							</form>
						');
			$template = new Template();
			eq($src,$template->execute($src));
		 */
	}
	final private function check_selected($name,$value,$selected){
		return sprintf("<?php if("
					."isset(%s) && (%s === %s "
										." || (ctype_digit(%s) && %s == %s)"
										." || ((%s == \"true\" || %s == \"false\") ? (%s === (%s == \"true\")) : false)"
										." || in_array(%s,((is_array(%s)) ? %s : (is_null(%s) ? array() : array(%s))),true) "
									.") "
					."){print(' ".$selected."');} ?>"
					,$name,$name,$value
					,$name,$name,$value
					,$value,$value,$name,$value
					,$value,$name,$name,$name,$name
				);
	}
	final private function htmlList($src){
		$tag = Tag::anyhow($src);
		foreach($tag->in(array("table","ul","ol")) as $obj){
			if($obj->isParam("rt:param")){
				$name = strtolower($obj->name());
				$param = $obj->inParam("rt:param");
				$null = strtolower($obj->inParam("rt:null"));
				$value = sprintf("<rt:loop param=\"%s\" var=\"%s\" counter=\"%s\" key=\"%s\" offset=\"%s\" limit=\"%s\" reverse=\"%s\">"
								,$param,$obj->inParam("rt:var","loop_var"),$obj->inParam("rt:counter","loop_counter")
								,$obj->inParam("rt:key","loop_key"),$obj->inParam("rt:offset","0"),$obj->inParam("rt:limit","0")
								,$obj->inParam("rt:reverse","false")
							);
				$rawvalue = $obj->value();
				if($name == "table" && Tag::setof($t,$rawvalue,"tbody")){
					$t->value($value.$this->tableTrEvenodd($t->value(),(($name == "table") ? "tr" : "li"),$obj->inParam("rt:counter","loop_counter"))."</rt:loop>");
					$value = str_replace($t->plain(),$t->get(),$rawvalue);
				}else{
					$value = $value.$this->tableTrEvenodd($rawvalue,(($name == "table") ? "tr" : "li"),$obj->inParam("rt:counter","loop_counter"))."</rt:loop>";
				}
				$obj->value($this->htmlList($value));
				$obj->rmParam("rt:param","rt:key","rt:var","rt:counter","rt:offset","rt:limit","rt:null");
				$src = str_replace($obj->plain(),
						($null === "true") ? $this->rtif(sprintf("<rt:if param=\"%s\">",$param).$obj->get()."</rt:if>") : $obj->get(),
						$src);
			}
		}
		return $src;
		/***
		 	$src = text('
						<table rt:param="xyz" rt:var="o">
						<tr class="odd"><td>{$o["B"]}</td></tr>
						</table>
					');
			$result = text('
							<table><tr class="odd"><td>222</td></tr>
							<tr class="even"><td>444</td></tr>
							<tr class="odd"><td>666</td></tr>
							</table>
						');
			$template = new Template();
			$template->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
			eq($result,$template->execute($src));
		*/
		/***
		 	$src = text('
						<table rt:param="xyz" rt:var="o">
						<tr><td>{$o["B"]}</td></tr>
						</table>
					');
			$result = text('
							<table><tr><td>222</td></tr>
							<tr><td>444</td></tr>
							<tr><td>666</td></tr>
							</table>
						');
			$template = new Template();
			$template->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
			eq($result,$template->execute($src));
		*/
		/***
		 	$src = text('
						<table rt:param="xyz" rt:var="o" rt:offset="1" rt:limit="1">
						<tr><td>{$o["B"]}</td></tr>
						</table>
					');
			$result = text('
							<table><tr><td>222</td></tr>
							</table>
						');
			$template = new Template();
			$template->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
			eq($result,$template->execute($src));
		*/
		/***
		 	$src = text('
						<table rt:param="xyz" rt:var="o" rt:offset="1" rt:limit="1">
						<thead>
							<tr><th>hoge</th></tr>
						</thead>
						<tbody>
							<tr><td>{$o["B"]}</td></tr>
						</tbody>
						</table>
					');
			$result = text('
							<table>
							<thead>
								<tr><th>hoge</th></tr>
							</thead>
							<tbody>	<tr><td>222</td></tr>
							</tbody>
							</table>
						');
			$template = new Template();
			$template->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
			eq($result,$template->execute($src));
		*/
		/***
		 	$src = text('
						<table rt:param="xyz" rt:null="true">
						<tr><td>{$o["B"]}</td></tr>
						</table>
					');
			$template = new Template();
			$template->vars("xyz",array());
			eq("",$template->execute($src));
		*/
		/***
		 	$src = text('
						<ul rt:param="xyz" rt:var="o">
							<li class="odd">{$o["B"]}</li>
						</ul>
					');
			$result = text('
							<ul>	<li class="odd">222</li>
								<li class="even">444</li>
								<li class="odd">666</li>
							</ul>
						');
			$template = new Template();
			$template->vars("xyz",array(array("A"=>"111","B"=>"222"),array("A"=>"333","B"=>"444"),array("A"=>"555","B"=>"666")));
			eq($result,$template->execute($src));
		*/
		/***
		 	$src = text('
						<rt:loop param="abc" var="a">
						<ul rt:param="{$a}" rt:var="b">
						<li>
						<ul rt:param="{$b}" rt:var="c">
						<li>{$c}<rt:loop param="xyz" var="z">{$z}</rt:loop></li>
						</ul>
						</li>
						</ul>
						</rt:loop>
					');
			$result = text('
							<ul><li>
							<ul><li>A12</li>
							<li>B12</li>
							</ul>
							</li>
							</ul>
							<ul><li>
							<ul><li>C12</li>
							<li>D12</li>
							</ul>
							</li>
							</ul>

						');
			$template = new Template();
			$template->vars("abc",array(array(array("A","B")),array(array("C","D"))));
			$template->vars("xyz",array(1,2));
			eq($result,$template->execute($src));
		*/
	}
	final private function tableTrEvenodd($src,$name,$counter){
		$tag = Tag::anyhow($src);
		foreach($tag->in($name) as $tr){
			$class = $tr->inParam("class");
			if($class == "even" || $class == "odd"){
				$tr->param("class","{\$t.evenodd(\$".$counter.")}");
				$src = str_replace($tr->plain(),$tr->get(),$src);
			}
		}
		return $src;
	}
	final private function toFormElementName($name){
		return (preg_match("/(.+)\.(.+)?$/",$name,$variableName)) ? sprintf("%s[%s]",$variableName[1],$variableName[2]) : $name;
	}
	final private function getFormVariableName($name){
		return (strpos($name,"[") && preg_match("/^(.+)\[([^\"\']+)\]$/",$name,$match)) ?
			"{\$".$match[1]."[\"".$match[2]."\"]"."}" : "{\$".$name."}";
	}
	final private function isReference(&$tag){
		$bool = ($tag->inParam("rt:ref") === "true");
		$tag->rmParam("rt:ref");
		return $bool;
	}
	private function exec($src){
		$__template_eval_src__ = $src;
		ob_start();
			extract($this->vars);
			eval("?>".$__template_eval_src__);
			unset($__template_eval_src__);
		return ob_get_clean();
	}
}
?>