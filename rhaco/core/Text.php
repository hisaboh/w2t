<?php
/**
 * テキスト処理
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Text{
	static public function __funcs__(){
		function text($text){
			return Text::plain($text);
		}
		function json($text){
			return Text::parse_json($text);
		}
		function yaml($text){
			return Text::parse_yaml($text);
		}
		function seem($text){
			return Text::seem($text);
		}
		function text_encode($value,$enc="UTF-8"){
			return Text::encode($value,$enc);
		}
	}
	static public function __import__(){
		Rhaco::def("core.Text@detect_order","JIS,eucjp-win,sjis-win,UTF-8");
	}
	/**
	 * ヒアドキュメントのようなテキストを生成する
	 * １行目のインデントに合わせてインデントが消去される
	 * @param string $text
	 * @return string
	 */
	final public static function plain($text){
		$lines = explode("\n",$text);
		if(sizeof($lines) > 2){
			array_shift($lines);
			array_pop($lines);
			return preg_match("/^([\040\t]+)/",$lines[0],$match) ? preg_replace("/^".$match[1]."/m","",implode("\n",$lines)) : implode("\n",$lines);
		}
		return $text;
		/***
			$text = Text::plain('
							aaa
							bbb
						');
			eq("aaa\nbbb",$text);
		 */
	}
	/**
	 * Jsonに変換して取得
	 * @param mixed $variable
	 * @return string
	 */
	static public function to_json($variable){
		/***
		 * $variable = array(1,2,3);
		 * eq("[1,2,3]",Text::to_json($variable,true));
		 * $variable = "ABC";
		 * eq("\"ABC\"",Text::to_json($variable,true));
		 * $variable = 10;
		 * eq(10,Text::to_json($variable,true));
		 * $variable = 10.123;
		 * eq(10.123,Text::to_json($variable,true));
		 * $variable = true;
		 * eq("true",Text::to_json($variable,true));
		 *
		 * $variable = array('foo', 'bar', array(1, 2, 'baz'), array(3, array(4)));
		 * eq('["foo","bar",[1,2,"baz"],[3,[4]]]',Text::to_json($variable,true));
		 *
		 * $variable = array("foo"=>"bar",'baz'=>1,3=>4);
		 * eq('{"foo":"bar","baz":1,"3":4}',Text::to_json($variable,true));
		 *
		 */
		switch(gettype($variable)){
			case "boolean": return ($variable) ? "true" : "false";
			case "integer": return intval(sprintf("%d",$variable));
			case "double": return floatval(sprintf("%f",$variable));
			case "array":
				$list = array();
				$tmp = $variable;
				ksort($tmp,SORT_STRING);
				if($tmp === $variable){
					foreach($variable as $key => $value) $list[] = self::to_json($value);
					return sprintf("[%s]",implode(",",$list));
				}
				foreach($variable as $key => $value) $list[] = sprintf("\"%s\":%s",$key,self::to_json($value));
				return sprintf("{%s}",implode(",",$list));
			case "object":
				$list = array();
				foreach((($variable instanceof Object) ? $variable->hash() : get_object_vars($variable)) as $key => $value){
					$list[] = sprintf("\"%s\":%s",$key,self::to_json($value));
				}
				return sprintf("{%s}",implode(",",$list));
			case "string":
				return sprintf("\"%s\"",addslashes($variable));
			default:
		}
		return "null";
	}

	/**
	 * JsonからPHPの変数に変換
	 * @param string $json
	 * @return mixed
	 */
	static public function parse_json($json){
		/***
		 * $variable = "ABC";
		 * eq($variable,Text::parse_json('"ABC"'));
		 * $variable = 10;
		 * eq($variable,Text::parse_json(10));
		 * $variable = 10.123;
		 * eq($variable,Text::parse_json(10.123));
		 * $variable = true;
		 * eq($variable,Text::parse_json("true"));
		 * $variable = false;
		 * eq($variable,Text::parse_json("false"));
		 * $variable = null;
		 * eq($variable,Text::parse_json("null"));
		 * $variable = array(1,2,3);
		 * eq($variable,Text::parse_json("[1,2,3]"));
		 * $variable = array(1,2,array(9,8,7));
		 * eq($variable,Text::parse_json("[1,2,[9,8,7]]"));
		 * $variable = array(1,2,array(9,array(10,11),7));
		 * eq($variable,Text::parse_json("[1,2,[9,[10,11],7]]"));
		 *
		 * $variable = array("A"=>"a","B"=>"b","C"=>"c");
		 * eq($variable,Text::parse_json('{"A":"a","B":"b","C":"c"}'));
		 * $variable = array("A"=>"a","B"=>"b","C"=>array("E"=>"e","F"=>"f","G"=>"g"));
		 * eq($variable,Text::parse_json('{"A":"a","B":"b","C":{"E":"e","F":"f","G":"g"}}'));
		 * $variable = array("A"=>"a","B"=>"b","C"=>array("E"=>"e","F"=>array("H"=>"h","I"=>"i"),"G"=>"g"));
		 * eq($variable,Text::parse_json('{"A":"a","B":"b","C":{"E":"e","F":{"H":"h","I":"i"},"G":"g"}}'));
		 *
		 * $variable = array("A"=>"a","B"=>array(1,2,3),"C"=>"c");
		 * eq($variable,Text::parse_json('{"A":"a","B":[1,2,3],"C":"c"}'));
		 * $variable = array("A"=>"a","B"=>array(1,array("C"=>"c","D"=>"d"),3),"C"=>"c");
		 * eq($variable,Text::parse_json('{"A":"a","B":[1,{"C":"c","D":"d"},3],"C":"c"}'));
		 *
		 * $variable = array(array("a"=>1,"b"=>array("a","b",1)),array(null,false,true));
		 * eq($variable,Text::parse_json('[ {"a" : 1, "b" : ["a", "b", 1] }, [ null, false, true ] ]'));
		 *
		 * eq(null,Text::parse_json("[1,2,3,]"));
		 * eq(null,Text::parse_json("[1,2,3,,,]"));
		 * eq(array(1,null,3),Text::parse_json("[1,[1,2,],3]"));
		 * eq(null,Text::parse_json('{"A":"a","B":"b","C":"c",}'));
		 */
		if(!is_string($json)) return $json;
		$json = self::seem($json);
		if(!is_string($json)) return $json;
		$json = preg_replace("/[\s]*([,\:\{\}\[\]])[\s]*/","\\1",
						preg_replace("/[\"].*?[\"]/esm",'str_replace(array(",",":","{","}","[","]"),array("#B#","#C#","#D#","#E#","#F#","#G#"),"\\0")',
							str_replace(array("\\\"","\$"),array("#A#","#H"),trim($json))));
		if(preg_match("/^\"([^\"]*?)\"$/",$json)){
			return str_replace(array("#A#","#B#","#C#","#D#","#E#","#F#","#G#","#H#"),array("\\\"",",",":","{","}","[","]","\$"),substr($json,1,-1));
		}
		$start = substr($json,0,1);
		$end = substr($json,-1);
		if(($start == "[" && $end == "]") || ($start == "{" && $end == "}")){
			$hash = ($start == "{");
			$src = substr($json,1,-1);
			$list = array();
			while(strpos($src,"[") !== false){
				list($value,$start,$end) = self::block($src,"[","]");
				if($value === null) return null;
				$src = str_replace("[".$value."]",str_replace(array("[","]",","),array("#AA#","#AB","#AC"),"[".$value."]"),$src);
			}
			while(strpos($src,"{") !== false){
				list($value,$start,$end) = self::block($src,"{","}");
				if($value === null) return null;
				$src = str_replace("{".$value."}",str_replace(array("{","}",","),array("#BA#","#BB","#AC"),"{".$value."}"),$src);
			}
			foreach(explode(",",$src) as $value){
				if($value === "") return null;
				$value = str_replace(array("#AA#","#AB","#BA#","#BB","#AC"),array("[","]","{","}",","),$value);

				if($hash){
					list($key,$var) = explode(":",$value,2);
					$index = self::parse_json($key);
					if($index === null) $index = $key;
					$list[$index] = self::parse_json($var);
				}else{
					$list[] = self::parse_json($value);
				}
			}
			return $list;
		}
		return null;
	}
	/**
	 * 指定の開始文字／終了文字でくくられた部分を取得
	 * ブロックの中身,ブロックの開始位置,ブロックの終了位置を返す
	 * @param string $src
	 * @param string $start
	 * @param string $end
	 * @return array(string,int,int)
	 */
	static public function block($src,$start,$end){
		/***
		 * $src = "xyz[abc[def]efg]hij";
		 * $rtn = Text::block($src,"[","]");
		 * eq(array("abc[def]efg",3,16),$rtn);
		 * eq("[abc[def]efg]",substr($src,$rtn[1],$rtn[2] - $rtn[1]));
		 *
		 * $src = "[abc[def]efg]hij";
		 * eq(array("abc[def]efg",0,13),Text::block($src,"[","]"));
		 *
		 * $src = "[abc[def]efghij";
		 * eq(array(null,0,15),Text::block($src,"[","]"));
		 *
		 * $src = "[abc/def/efghij";
		 * eq(array("def",4,9),Text::block($src,"/","/"));
		 *
		 * $src = "[abc|def|efghij";
		 * eq(array("def",4,9),Text::block($src,"|","|"));
		 *
		 * $src = "[abc<abc>def</abc>efghij";
		 * eq(array("def",4,18),Text::block($src,"<abc>","</abc>"));
		 *
		 * $src = "[abc<abc>def<abc>efghij";
		 * eq(array("def",4,17),Text::block($src,"<abc>","<abc>"));
		 *
		 * $src = "[<abc>abc<abc>def</abc>efg</abc>hij";
		 * $rtn = Text::block($src,"<abc>","</abc>");
		 * eq(array("abc<abc>def</abc>efg",1,32),$rtn);
		 * eq("<abc>abc<abc>def</abc>efg</abc>",substr($src,$rtn[1],$rtn[2] - $rtn[1]));
		 */
		$eq = ($start == $end);
		if(preg_match_all("/".(($end == null || $eq) ? preg_quote($start,"/") : "(".preg_quote($start,"/").")|(".preg_quote($end,"/").")")."/sm",$src,$match,PREG_OFFSET_CAPTURE)){
			$count = 0;
			$pos = null;

			foreach($match[0] as $key => $value){
				if($value[0] == $start){
					$count++;
					if($pos === null) $pos = $value[1];
				}else if($pos !== null){
					$count--;
				}
				if($count == 0 || ($eq && ($count % 2 == 0))) return array(substr($src,$pos + strlen($start),($value[1] - $pos - strlen($start))),$pos,$value[1] + strlen($end));
			}
		}
		return array(null,0,strlen($src));
	}
	/**
	 * シンプルなyamlからphpに変換
	 * @param string $src
	 * @return array
	 */
	static public function parse_yaml($src){
		$src = preg_replace("/([\"\'])(.+)\\1/me",'str_replace(array("#",":"),array("__SHAPE__","__COLON__"),"\\0")',$src);
		$src = preg_replace("/^([\t]+)/me",'str_replace("\t"," ","\\1")',str_replace(array("\r\n","\r","\n"),"\n",$src));
		$src = preg_replace("/#.+$/m","",$src);
		$stream = array();

		if(!preg_match("/^[\040]*---(.*)$/m",$src)) $src = "---\n".$src;
		if(preg_match_all("/^[\040]*---(.*)$/m",$src,$match,PREG_OFFSET_CAPTURE | PREG_SET_ORDER)){
			$blocks = array();
			$size = sizeof($match) - 1;

			foreach($match as $c => $m){
				$obj = new stdClass();
				$obj->header = ltrim($m[1][0]);
				$obj->nodes = array();
				$node = array();
				$offset = $m[0][1] + mb_strlen($m[0][0]);
				$block = ($size == $c) ? mb_substr($src,$offset) :
											mb_substr($src,$offset,$match[$c+1][0][1] - $offset);
				foreach(explode("\n",$block) as $key => $line){
					if(!empty($line)){
						if($line[0] == " "){
							$node[] = $line;
						}else{
							self::yamlnodes($obj,$node);
							$result = self::yamlnode($node);
							$node = array($line);
						}
					}
				}
				self::yamlnodes($obj,$node);
				array_shift($obj->nodes);
				$stream[] = $obj;
			}
		}
		return $stream;
		/***
			$yml = text('
						--- hoge
						a: mapping
						foo: bar
						---
						- a
						- sequence
					');
			$obj1 = (object)array("header"=>"hoge","nodes"=>array("a"=>"mapping","foo"=>"bar"));
			$obj2 = (object)array("header"=>"","nodes"=>array("a","sequence"));
			$result = array($obj1,$obj2);
			eq($result,Text::parse_yaml($yml));

			$yml = text('
						---
						This: top level mapping
						is:
							- a
							- YAML
							- document
					');
			$obj1 = (object)array("header"=>"","nodes"=>array("This"=>"top level mapping","is"=>array("a","YAML","document")));
			$result = array($obj1);
			eq($result,Text::parse_yaml($yml));

			$yml = text('
						--- !recursive-sequence &001
						- * 001
						- * 001
					');
			$obj1 = (object)array("header"=>"!recursive-sequence &001","nodes"=>array("* 001","* 001"));
			$result = array($obj1);
			eq($result,Text::parse_yaml($yml));

			$yml = text('
						a sequence:
							- one bourbon
							- one scotch
							- one beer
					');
			$obj1 = (object)array("header"=>"","nodes"=>array("a sequence"=>array("one bourbon","one scotch","one beer")));
			$result = array($obj1);
			eq($result,Text::parse_yaml($yml));

			$yml = text('
						a scalar key: a scalar value
					');
			$obj1 = (object)array("header"=>"","nodes"=>array("a scalar key"=>"a scalar value"));
			$result = array($obj1);
			eq($result,Text::parse_yaml($yml));

			$yml = text('
						- a plain string
						- -42
						- 3.1415
						- 12:34
						- 123 this is an error
					');
			$obj1 = (object)array("header"=>"","nodes"=>array("a plain string",-42,3.1415,"12:34","123 this is an error"));
			$result = array($obj1);
			eq($result,Text::parse_yaml($yml));

			$yml = text('
						- >
						 This is a multiline scalar which begins on
						 the next line. It is indicated by a single
						 carat.
					');
			$obj1 = (object)array("header"=>"","nodes"=>array("This is a multiline scalar which begins on the next line. It is indicated by a single carat."));
			$result = array($obj1);
			eq($result,Text::parse_yaml($yml));

			$yml = text('
						- |
						 QTY  DESC		 PRICE TOTAL
						 ===  ====		 ===== =====
						 1  Foo Fighters  $19.95 $19.95
						 2  Bar Belles	$29.95 $59.90
					');
			$rtext = text('
						QTY  DESC		 PRICE TOTAL
						===  ====		 ===== =====
						1  Foo Fighters  $19.95 $19.95
						2  Bar Belles	$29.95 $59.90
						');
			$obj1 = (object)array("header"=>"","nodes"=>array($rtext));
			$result = array($obj1);
			eq($result,Text::parse_yaml($yml));

			$yml = text('
						-
						  name: Mark McGwire
						  hr:   65
						  avg:  0.278
						-
						  name: Sammy Sosa
						  hr:   63
						  avg:  0.288
					');
			$obj1 = (object)array("header"=>"","nodes"=>array(
													array("name"=>"Mark McGwire","hr"=>65,"avg"=>0.278),
													array("name"=>"Sammy Sosa","hr"=>63,"avg"=>0.288)));
			$result = array($obj1);
			eq($result,Text::parse_yaml($yml));

			$yml = text('
						hr:  65	# Home runs
						avg: 0.278 # Batting average
						rbi: 147   # Runs Batted In
					');
			$obj1 = (object)array("header"=>"","nodes"=>array("hr"=>65,"avg"=>0.278,"rbi"=>147));
			$result = array($obj1);
			eq($result,Text::parse_yaml($yml));

			$yml = text('
						name: Mark McGwire
						accomplishment: >
						  Mark set a major league
						  home run record in 1998.
						stats: |
						  65 Home Runs
						  0.278 Batting Average
					');
			$obj1 = (object)array("header"=>"","nodes"=>array(
												"name"=>"Mark McGwire",
												"accomplishment"=>"Mark set a major league home run record in 1998.",
												"stats"=>"65 Home Runs\n0.278 Batting Average"));
			$result = array($obj1);
			eq($result,Text::parse_yaml($yml));
		*/
	}
	static private function yamlnodes(&$obj,$node){
		$result = self::yamlnode($node);
		if(is_array($result) && sizeof($result) == 1){
			if(isset($result[1])){
				$obj->nodes[] = array_shift($result);
			}else{
				$obj->nodes[key($result)] = current($result);
			}
		}else{
			$obj->nodes[] = $result;
		}
	}
	static private function yamlnode($node){
		$result = $child = $sequence = array();
		$line = $indent = 0;
		$isseq = $isblock = $onblock = $ischild = $onlabel = false;
		$name = "";
		$node[] = null;

		foreach($node as $value){
			if(!empty($value) && $value[0] == " ") $value = substr($value,$indent);
			switch($value[0]){
				case "[":
				case "{":
					return $value;
					break;
				case " ":
					if($indent == 0 && preg_match("/^[\040]+/",$value,$match)){
						$indent = strlen($match[0]) - 1;
						$value = substr($value,$indent);
					}
					if($isseq){
						if($onlabel){
							$result[$name] .= (($onblock) ? (($isblock) ? "\n" : " ") : "").ltrim(substr($value,1));
						}else{
							$sequence[$line] .= (($onblock) ? (($isblock) ? "\n" : " ") : "").ltrim(substr($value,1));
						}
						$onblock = true;
					}else{
						$child[] = substr($value,1);
					}
					break;
				case "-":
					$line++;
					$value = ltrim(substr($value,1));
					$isseq = $isblock = false;
					switch(trim($value)){
						case "": $ischild = true;
						case "|": $isblock = true; $onblock = false;
						case ">": $value = ""; $isseq = true;
					}
					$sequence[$line] = self::yamlunescape($value);
					break;
				default:
					if(empty($value) && !empty($sequence)){
						if($ischild){
							foreach($sequence as $key => $seq) $sequence[$key] = self::yamlnode(explode("\n",$seq));
							return $sequence;
						}
						return (sizeof($sequence) == 1) ? $sequence[1] : array_merge($sequence);
					}else if($name != "" && !empty($child)){
						$result[$name] = self::yamlnode($child);
					}
					$onlabel = false;
					if(substr(rtrim($value),-1) == ":"){
						$name = ltrim(self::yamlunescape(substr(trim($value),0,-1)));
						$result[$name] = null;
					}else if(strpos($value,":") !== false){
						list($tmp,$value) = explode(":",$value);
						$tmp = self::yamlunescape(trim($tmp));
						switch(trim($value)){
							case "|": $isblock = true; $onblock = false;
							case ">": $isseq = $onlabel = true; $result[$name = $tmp] = ""; break;
							default: $result[$tmp] = self::yamlunescape(ltrim($value));
						}
					}
					$child = array();
					$indent = 0;
			}
		}
		return $result;
	}
	static private function yamlunescape($value){
		return self::seem(preg_replace("/^(['\"])(.+)\\1.*$/","\\2",str_replace(array("__SHAPE__","__COLON__"),array("#",":"),$value)));
	}
	/**
	 * 文字列をそれっぽい型にして返す
	 * @param string $value
	 * @return mixed
	 */
	static public function seem($value){
		if(!is_string($value)) throw new InvalidArgumentException("not string");
		if(is_numeric(trim($value))) return (strpos($value,".") !== false) ? floatval($value) : intval($value);
		switch(strtolower($value)){
			case "null": return null;
			case "true": return true;
			case "false": return false;
			default: return $value;
		}
		/***
			eq(null,Text::seem("null"));
			eq(null,Text::seem("NULL"));
			eq(true,Text::seem("true"));
			eq(true,Text::seem("True"));
			eq(false,Text::seem("false"));
			eq(false,Text::seem("FALSE"));
			eq(100,Text::seem("100"));
			eq(100.05,Text::seem("100.05"));
			eq("abc",Text::seem("abc"));
		 */
	}
	static public function _($message){
		$message = gettext($message);
		if(func_num_args() > 1){
			$args = func_get_args();
			if(preg_match_all("/\{([\d]+)\}/",$message,$match)){
				foreach(array_flip($match[1]) as $param => $null){
					$message = str_replace("{".$param."}",(isset($args[$param]) ? $args[$param] : ""),$message);
				}
			}
			return $message;
		}
		return "";
		/***
			Rhaco::setlocale("ja_JP");
			eq("データベースコントローラ [mysql]",Text::_("database controler [{1}]","mysql"));
		 */
	}
	static public function _n($single,$plural,$n){
		if(func_num_args() > 3){
			$args = func_get_args();
			array_shift($args);
			array_shift($args);
			array_shift($args);
			array_unshift($args,ngettext($single,$plural,$n));
		}
		return call_user_func_array(array("Text","_"),$args);
	}
	/**
	 * 文字列中に指定した文字列がすべて存在するか
	 *
	 * @param string $str
	 * @param string $query
	 * @param string $delimiter
	 * @return boolean
	 */
	static public function match($str,$query,$delimiter=" "){
		foreach(explode($delimiter,$query) as $q){
			if(mb_strpos($str,$q) === false) return false;
		}
		return true;
		/***
			eq(true,Text::match("abcdefghijklmn","abc ghi"));
			eq(true,Text::match("abcdefghijklmn","abc_ghi","_"));
			eq(true,Text::match("あいうえおかきくけこ","うえ け"));
		 */
	}
	/**
	 * 大文字小文字を区別せず、文字列中に指定した文字列がすべて存在するか
	 *
	 * @param string $str
	 * @param string $query
	 * @param string $delimiter
	 * @return boolean
	 */
	static public function imatch($str,$query,$delimiter=" "){
		foreach(explode($delimiter,$query) as $q){
			if(mb_stripos($str,$q) === false) return false;
		}
		return true;
		/***
			eq(true,Text::imatch("abcdefghijklmn","aBc ghi"));
			eq(true,Text::imatch("abcdefghijklmn","abc_gHi","_"));
			eq(true,Text::imatch("あいうえおかきくけこ","うえ け"));
		 */
	}

	/**
	 *  $value中に$searchが存在するか
	 *
	 * @param string $value
	 * @param string $search 正規表現文字列
	 * @return boolean
	 */
	static public function exsist($value,$search){
		/***
			eq(true,Text::exsist("aaabbbccc","aaa"));
			eq(true,Text::exsist("aaa/bbb/ccc","a/b"));
			eq(false,Text::exsist("aaa/bbb/ccc","a/b/c"));
		*/
		return (preg_match("/".preg_quote($search,"/")."/",$value)) ? true : false;
	}
	/**
	 * 文字列配列をtrimする
	 * @pram string $value ,....
	 *
	 * @return array
	 */
	static public function trim(){
		/***
			eq(array("aaa","bbb","ccc"),Text::trim("  aaa ","bbb","ccc   "));
		*/
		$args = func_get_args();
		$result = array();
		foreach($args as $arg) $result[] = trim($arg);
		return $result;
	}
	static public function uld($src){
		/***
		 * eq("a\nb\nc\n",Text::uld("a\r\nb\rc\n"));
		 */
		return str_replace(array("\r\n","\r"),"\n",$src);
	}
	static public function uncomment($src){
		return preg_replace("/\/\*.+?\*\//s","",$src);
	}
	/**
	 * HTMLデコードした文字列を返す
	 * @param string $value
	 * @return string
	 */
	static public function htmldecode($value){
		if(!empty($value) && is_string($value)){
			$value = mb_convert_encoding($value,"UTF-8",mb_detect_encoding($value));
			$value = preg_replace("/&#[xX]([0-9a-fA-F]+);/eu","'&#'.hexdec('\\1').';'",$value);
			$value = mb_decode_numericentity($value,array(0x0,0x10000,0,0xfffff),"UTF-8");
			$value = html_entity_decode($value,ENT_QUOTES,"UTF-8");
			$value = str_replace(array("\\\"","\\'","\\\\"),array("\"","\'","\\"),$value);
		}
		return $value;
		/***
		 * eq("ほげほげ",Text::htmldecode("&#12411;&#12370;&#12411;&#12370;"));
		 * eq("&gt;&lt;ほげ& ほげ",Text::htmldecode("&amp;gt;&amp;lt;&#12411;&#12370;&amp; &#12411;&#12370;"));
		 */
	}
	/**
	 * htmlエンコードをする
	 * @param string $value
	 * @return string
	 */
	final static public function htmlencode($value){
		if(!empty($value) && is_string($value)){
			$value = mb_convert_encoding($value,"UTF-8",mb_detect_encoding($value));
			return htmlentities($value,ENT_QUOTES,"UTF-8");
		}
		return $value;
		/***
			eq("&lt;abc aa=&#039;123&#039; bb=&quot;ddd&quot;&gt;あいう&lt;/abc&gt;",Text::htmlencode("<abc aa='123' bb=\"ddd\">あいう</abc>"));
		 */
	}
	/**
	 * 文字エンコード
	 *
	 * @param string $value
	 * @param string $enc
	 * @return string
	 */
	final static public function encode($value,$enc="UTF-8"){
		return mb_convert_encoding($value,$enc,mb_detect_encoding($value,Rhaco::def("core.Text@detect_order"),true));
	}
}
?>