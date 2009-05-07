<?php
Rhaco::import("core.File");
Rhaco::import("core.Text");
Rhaco::import("ext.Info");
/**
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Setup extends Object{
	static public $path;
	protected $pathinfo;

	static public function __import__(){
		$debug = debug_backtrace();
		self::$path = dirname($debug[2]["file"]);		
	}

	/**
	 * poファイルからmoファイルの作成
	 * @param string $po_filename
	 * @param string $mo_filename
	 */
	static public function mo_generate($path){
		$pos = array();
		if(is_dir($path)){
			foreach(File::ls($path,true) as $file){
				if($file->ext() == ".po") $pos[] = $file->fullname();
			}
		}else if(is_file($path)){
			$pos[] = $path;
		}
		foreach($pos as $po_filename){
			$output_path = preg_replace("/^(.+)\.po$/","\\1",$po_filename).".mo";
			$po_list = self::po_read($po_filename);
			$count = sizeof($po_list);
			$ids = implode("\0",array_keys($po_list))."\0";
			$keyoffset = 28 + 16 * $count;
			$valueoffset = $keyoffset + strlen($ids);
			$value_src = "";

			$output_src = pack('Iiiiiii',0x950412de,0,$count,28,(28 + ($count * 8)),0,0);
			foreach($po_list as $id => $value){
				$len = strlen($id);
				$output_src .= pack("i",$len);
				$output_src .= pack("i",$keyoffset);
				$keyoffset += $len + 1;

				$len = strlen($value);
				$value_src .= pack("i",$len);
				$value_src .= pack("i",$valueoffset);
				$valueoffset += $len + 1;
			}
			$output_src .= $value_src;
			$output_src .= $ids;
			$output_src .= implode("\0",$po_list)."\0";
			File::write($output_path,$output_src);
		}
	}
	/**
	 * クラスファイルからgettext文字列を抜き出してpotファイルを作成する
	 * @param string $path
	 * @param string $lc_messages_path
	 */
	static public function po_generate($path,$lc_messages_path){
		$messages = array();
		foreach(File::ls($path,true) as $file){
			if($file->isClass()){
				ob_start();
				include_once($file->fullname());
				Rhaco::import($file->oname());
				ob_get_clean();
				$ref = Info::reflection($file->oname());
				foreach($ref->methods as $method){
					foreach($method->gettext as $text){
						$messages[$text->str]["#: ".str_replace($path,"",$file->fullname()).":".$text->line] = true;
					}
				}
			}
		}
		ksort($messages,SORT_STRING);
		$output_src = sprintf(Text::plain('
						# SOME DESCRIPTIVE TITLE.
						# Copyright (C) YEAR THE PACKAGE\'S COPYRIGHT HOLDER
						# This file is distributed under the same license as the PACKAGE package.
						# FIRST AUTHOR <EMAIL@ADDRESS>, YEAR.
						#
						#, fuzzy
						msgid ""
						msgstr ""
						"Project-Id-Version: PACKAGE VERSION\n"
						"Report-Msgid-Bugs-To: \n"
						"POT-Creation-Date: %s\n"
						"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
						"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
						"Language-Team: LANGUAGE <team@exsample.com>\n"
				'),date("Y-m-d H:iO"))."\n\n";
		foreach($messages as $str => $lines){
			$output_src .= "\n".implode("\n",array_keys($lines))."\n";
			$output_src .= "msgid \"".$str."\"\n";
			$output_src .= "msgstr \"\"\n";
		}
		File::write(File::absolute($lc_messages_path,"messages.pot"),$output_src);

		//Rhaco::import("ext.Setup");
		//Setup::po_generate(dirname(__FILE__),Rhaco::selfpath()."/resources/locale/");
		//Setup::mo_generate(Rhaco::selfpath()."/resources/locale/");
		//Test::each_flush();
	}
	static private function po_read($po_filename){
		$file = new File($po_filename);
		$po_list = array();
		$msgId = "";
		$isId = false;

		foreach(explode("\n",$file->get()) as $line){
			if(!preg_match("/^[\s]*#/",$line)){
				if(preg_match("/msgid[\s]+([\"\'])(.+)\\1/",$line,$match)){
					$msgId = $match[2];
					$isId = true;
				}else if(preg_match("/msgstr[\s]+([\"\'])(.+)\\1/",$line,$match)){
					$po_list[$msgId] = $match[2];
					$isId = false;
				}else if(preg_match("/([\"\'])(.+)\\1/",$line,$match)){
					if(!empty($msgId)){
						if($isId){
							$msgId .= $match[2];
						}else{
							if(!isset($po_list[$msgId])) $po_list[$msgId] = "";
							$po_list[$msgId] .= $match[2];
						}
					}
				}
			}
		}
		ksort($po_list,SORT_STRING);
		return $po_list;
	}
	
	/**
	 * スペース4をTABに置換する
	 *
	 * @param string $path
	 */
	static public function s2t($path,$ext="php,html"){
		$conds = array();
		foreach(explode(",",$ext) as $e){
			$e = trim($e);
			if(!empty($e)) $conds[] = "\\.".$e;
		}
		foreach(File::ls($path,true) as $file){
			if(preg_match("/".implode("|",$conds)."$/",$file->oname())){
				$pre = $value = $file->get();
				$value = preg_replace("/([\"\']).*[\040]{4}?\\1/e",'str_replace(str_repeat(" ",4),"|__"."SPACE4"."__|","\\0")',$value);
				$value = str_replace(str_repeat(" ",4),"\t",$value);
				$value = str_replace("|__"."SPACE4"."__|",str_repeat(" ",4),$value);
				if($pre !== $value) File::write($file->fullname(),$value);
			}
		}
	}
	/**
	 * TABをスペース4に置換する
	 *
	 * @param string $path
	 */
	static public function t2s($path,$ext="php,html"){
		$conds = array();
		foreach(explode(",",$ext) as $e){
			$e = trim($e);
			if(!empty($e)) $conds[] = "\\.".$e;
		}
		foreach(File::ls($path,true) as $file){
			if(preg_match("/".implode("|",$conds)."$/",$file->oname())){
				$pre = $value = $file->get();
				$value = preg_replace("/([\"\']).*\t?\\1/e",'str_replace("\t","|__"."TAB4"."__|","\\0")',$value);
				$value = str_replace("\t",str_repeat(" ",4),$value);
				$value = str_replace("|__"."TAB4"."__|","\t",$value);
				if($pre !== $value) File::write($file->fullname(),$value);
			}
		}
	}
	
	/**
	 * 指定のディレクトリ内の.phpファイルのCRLFをLFに変換する
	 * 
	 * @param string $path
	 */
	static public function crlf2lf($path){
		foreach(File::ls($path,true) as $file){
			if($file->ext() == ".php") File::write($file->fullname(),str_replace(array("\r\n","\r"),"\n",$file->get()));
		}
	}
}
?>