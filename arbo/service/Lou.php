<?php
import("core.Http");
import("core.Tag");
/**
 * ルー大柴
 *
 * @see http://lou5.jp/
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Lou{
	/**
	 * 色やフォントはそのまま、ルー語に変換する 
	 *
	 * @param string
	 * @return string
	 */	
	static public function trans_text($message){
		return str_replace("<br />","",self::trans($message,1));
	}
	/**
	 * 色やフォントもルーブログぽく、ルー語に変換する
	 *
	 * @param string
	 * @return string
	 */
	static public function trans_html($message){
		return self::trans($message,2);
	}
	static private function trans($message,$mode){
		$http = new Http();
		$http->vars("v",$mode);
		$http->vars("text",$message);

		if(Tag::setof($tag,$http->do_post("http://lou5.jp/")->body(),"body")){
			foreach($tag->in('p') as $p){
				if($p->inParam("class") == "large align-left box") $message = $p->value();
			}
		}
		return $message;
    }
}
?>