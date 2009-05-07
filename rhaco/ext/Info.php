<?php
/**
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Info{
	static private $DOC_START = "/***";

	static public function __funcs__(){
		function help($class,$method=null){
			Info::help($class,$method,true);
		}
	}
	/**
	 * リフレクション(Reflection**クラスではなく）を返す
	 *
	 * @param string $path
	 * @return stdClass
	 */
	final public static function reflection($path){
		if(is_object($path)) $path = get_class($path);
		$class = $path;
		if(false !== ($pos = strrpos($class,"."))) $class = substr($class,($pos !== false) ? $pos + 1 : $pos);
		if(!class_exists($class)) Rhaco::import($path);

		$obj = new Object();
		$ref = new ReflectionClass($class);
		$obj->name = $ref->getName();
		$obj->path = str_replace("\\","/",$ref->getFileName());
		$obj->document = trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array("/**","*/"),"",$ref->getDocComment())));
		$obj->test = array();
		$obj->methods = array();
		$values = file($obj->path);
		$lastline = $ref->getStartLine();
		$obj->valid_line = sizeof(explode("\n",preg_replace("/^[\s\t\n\r]*/sm","",preg_replace("/\/\*.*?\*\//s","",implode($values)))));

		foreach($ref->getMethods() as $method){
			if($method->getFileName() == $obj->path){
				$mobj = new stdClass();
				$mobj->name = $method->getName();
				$mobj->line = $method->getStartLine();
				$mobj->document = trim(preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array("/**","*/"),"",$method->getDocComment())));
				$mobj->test = array();
				$mobj->gettext = array();
				$mobj->public = $method->isPublic();
				$mobj->protected = $method->isProtected();
				$mobj->private = $method->isPrivate();
				$mobj->static = $method->isStatic();
				$mobj->final = $method->isFinal();

				$src = implode(array_slice($values,$method->getStartLine(),($method->getEndLine() - $method->getStartLine())));
				if(preg_match_all("/\/[\*]+.+?\*\//s",$src,$comments,PREG_OFFSET_CAPTURE)){
					foreach($comments[0] as $value){
						if(substr($value[0],0,4) == self::$DOC_START && isset($value[0][5]) && $value[0][5] != "*"){
							$test_block = preg_replace("/^[\s]*\*[\s]{0,1}/m","",str_replace(array(self::$DOC_START,"*/"),"",$value[0]));
							$mobj->test[$method->getStartLine() + substr_count(substr($src,0,$value[1]),"\n")] = (object)array("block"=>$test_block,"name"=>((preg_match("/^[\s]*#(.+)/",$test_block,$match)) ? trim($match[1]) : null));
						}
					}
				}
				if(preg_match_all("/_\(([\"\'])(.+?)\\1/",$src,$match,PREG_OFFSET_CAPTURE)){
					foreach($match[2] as $key => $value){
						$mobj->gettext[] = (object)array("str"=>$value[0],"line"=>$method->getStartLine() + substr_count(substr($src,0,$match[0][$key][1]),"\n") + 1);
					}
				}
				if(preg_match_all("/[\W]gettext\(([\"\'])(.+?)\\1/",$src,$match,PREG_OFFSET_CAPTURE)){
					foreach($match[2] as $key => $value){
						$mobj->gettext[] = (object)array("str"=>$value[0],"line"=>$method->getStartLine() + substr_count(substr($src,0,$match[0][$key][1]),"\n") + 1);
					}
				}
				$obj->methods[$method->getName()] = clone($mobj);
				$lastline = $method->getEndLine();
			}
		}
		$src = implode(array_slice($values,$lastline,($ref->getEndLine() - $lastline)));
		if(preg_match_all("/\/[\*]+.+?\*\//s",$src,$comments,PREG_OFFSET_CAPTURE)){
			foreach($comments[0] as $value){
				if(substr($value[0],0,4) == self::$DOC_START && isset($value[0][5]) && $value[0][5] != "*"){
					$test_block = preg_replace("/^[\040\t]*\*[\040\t]{0,1}/m","",str_replace(array(self::$DOC_START,"*/"),"",$value[0]));
					$obj->test[$lastline + substr_count(substr($src,0,$value[1]),"\n")] = (object)array("block"=>$test_block,"name"=>((preg_match("/^[\s]*#(.+)/",$test_block,$match)) ? trim($match[1]) : null));
				}
			}
		}
		unset($src,$values,$lastline,$class);
		return $obj;
	}
	/**
	 * ヘルプを表示
	 *
	 * @param string $class クラス名
	 * @param string $method メソッド名
	 * @param boolean $flush 出力するか
	 * @return string
	 */
	final public static function help($class,$method=null,$flush=true){
		if(!class_exists($class) && Rhaco::import($class)){
			$pos = strrpos($class,".");
			$class = substr($class,($pos !== false) ? $pos + 1 : $pos);
		}
		$ref = self::reflection($class);
		$doc = "\nHelp in class ".$class." ";
		$docs = array();
		$tab = "  ";

		if(empty($method) || !isset($ref->methods[$method])){
			$doc .= ":\n";
			$doc .= $tab.str_replace("\n","\n".$tab,$ref->document)."\n\n";
			$public = array();
			$static = array();
			$protected = array();

			foreach($ref->methods as $name => $m){
				if($m->public){
					if(substr($name,0,2) != "__"){
						if($m->static){
							$static[] = $name;
						}else{
							$public[] = $name;
						}
					}
				}else if($m->protected && !$m->final && !$m->static){
					$protected[] = $name;
				}
			}
			$doc .= $tab."Valid line: \n";
			$doc .= $tab.$tab.$ref->valid_line." lines";
			$doc .= "\n\n";
			$doc .= $tab."Static methods defined here:\n";
			$doc .= $tab.$tab.implode("\n".$tab.$tab,$static);
			$doc .= "\n\n";
			$doc .= $tab."Methods defined here:\n";
			$doc .= $tab.$tab.implode("\n".$tab.$tab,$public);
			$doc .= "\n\n";
			$doc .= $tab."Methods list you can override:\n";
			$doc .= $tab.$tab.implode("\n".$tab.$tab,$protected);
		}else{
			$m = $ref->methods[$method];
			$doc .= "in method ".$method.":\n";
			$doc .= $tab.str_replace("\n","\n".$tab,$m->document)."\n\n";
			$doc .= "\n";
			$doc .= $tab."doctest:\n";
			foreach($m->test as $line => $value){
				$doc .= $tab.str_replace("\n","\n".$tab,str_replace("\t",$tab,$value))."\n\n";
			}
		}
		if($flush) print($doc);
		return $doc;
	}
}
?>