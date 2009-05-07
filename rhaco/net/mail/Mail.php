<?php
Rhaco::import("core.File");
/**
 * メール送信に関する情報を制御する
 *
 * @author Kazutaka Tokushima
 * @author Kentaro YABE
 * @license New BSD License
 */
class Mail extends Object{
	static protected $__to__ = "type=string{}";
	static protected $__cc__ = "type=string{}";
	static protected $__bcc__ = "type=string{}";
	static protected $__attach__ = "type=File{}";
	static protected $__image__ = "type=File{}";
	protected $subject;
	protected $to;
	protected $cc;
	protected $bcc;
	protected $attach;
	protected $image;
	protected $message;
	protected $html;
	protected $from;
	protected $name;
	protected $return_path;

	private $eol = "\n";
	private $encode = "jis";
	private $boundary = array("mixed"=>"mixed","alternative"=>"alternative","related"=>"related");
	
	static public function __import__(){
		mb_language(("neutral" == mb_language()) ? "Japanese" : mb_language());
	}
	protected function __new__($from=null,$name=null,$eol=false){
		$this->eol = ($eol) ? "\r\n" : "\n";
		$this->from($from,$name);
		$this->return_path($from);
		$this->boundary = array("mixed"=>"----=_Part_".uniqid("mixed"),"alternative"=>"----=_Part_".uniqid("alternative"),"related"=>"----=_Part_".uniqid("related"));
	}
	protected function setFrom($mail,$name=null){
		$this->from = $mail;
		$this->name = $name;
	}
	protected function setTo($mail,$name=""){
		$this->to[$mail] = $this->address($mail,$name);
		/***
			$mail = new Mail();
			$mail->to("test1@rhaco.org","abc");
			$mail->to("test2@rhaco.org");
			$mail->to("test3@rhaco.org","ghi");
			eq(array('test1@rhaco.org'=>'"abc" <test1@rhaco.org>',
					'test2@rhaco.org'=>'"test2@rhaco.org" <test2@rhaco.org>',
					'test3@rhaco.org'=>'"ghi" <test3@rhaco.org>',)
					,$mail->to());
		*/
	}
	protected function setCc($mail,$name=""){
		$this->cc[$mail] = $this->address($mail,$name);
		/***
			$mail = new Mail();
			$mail->cc("test1@rhaco.org","abc");
			$mail->cc("test2@rhaco.org");
			$mail->cc("test3@rhaco.org","ghi");
			eq(array('test1@rhaco.org'=>'"abc" <test1@rhaco.org>',
					'test2@rhaco.org'=>'"test2@rhaco.org" <test2@rhaco.org>',
					'test3@rhaco.org'=>'"ghi" <test3@rhaco.org>',)
					,$mail->cc());
		*/
	}
	protected function setBcc($mail,$name=""){
		$this->bcc[$mail] = $this->address($mail,$name);
		/***
			$mail = new Mail();
			$mail->bcc("test1@rhaco.org","abc");
			$mail->bcc("test2@rhaco.org");
			$mail->bcc("test3@rhaco.org","ghi");
			eq(array('test1@rhaco.org'=>'"abc" <test1@rhaco.org>',
					'test2@rhaco.org'=>'"test2@rhaco.org" <test2@rhaco.org>',
					'test3@rhaco.org'=>'"ghi" <test3@rhaco.org>',)
					,$mail->bcc());
		*/
	}
	protected function setReturn_path($mail){
		$this->return_path = $mail;
		/***
			$mail = new Mail();
			$mail->return_path("test1@rhaco.org");
			$mail->return_path("test2@rhaco.org");
			eq("test2@rhaco.org",$mail->return_path());
		*/
	}
	protected function setSubject($subject){
		$this->subject = str_replace("\n","",str_replace(array("\r\n","\r"),"\n",$subject));
		/***
			$mail = new Mail();
			$mail->subject("改行は\r\n削除される");
			eq("=?ISO-2022-JP?B?GyRCMn45VCRPOm89fCQ1JGwkaxsoQg==?=", $mail->subject());
		 */
	}
	protected function getSubject(){
		return $this->jis($this->subject);
	}
	protected function setAttach($filename,$src,$type="application/octet-stream"){
		$this->attach[] = array(new File($filename,$src),$type);
	}
	protected function setImage($filename,$src,$type="application/octet-stream"){
		$this->image[$filename] = array(new File($filename,$src),$type);
	}
	protected function setMessage($message){
		$this->message = $this->encode($message);
	}
	protected function setHtml($message){
		$this->html = $this->encode($message);
		if($this->message === null) $this->message(strip_tags($message));
	}
	protected function header(){
		$send = "";
		$send .= $this->line("MIME-Version: 1.0");
		$send .= $this->line("To: ".$this->implode_address($this->to));
		$send .= $this->line("From: ".$this->address($this->from,$this->name));
		if(!empty($this->cc)) $send .= $this->line("Cc: ".$this->implode_addressimp($this->cc));
		if(!empty($this->bcc)) $send .= $this->line("Bcc: ".$this->implode_address($this->bcc));
		if(!empty($this->return_path)) $send .= $this->line("Return-Path: ".$this->return_path);
		$send .= (empty($this->subject)) ? "" : $this->line("Subject: ".$this->subject());
		$send .= $this->line("Date: ".date("D, d M Y H:i:s O",time()));

		if(!empty($this->attach)){
			$send .= $this->line(sprintf("Content-Type: multipart/mixed; boundary=\"%s\"",$this->boundary["mixed"]));
		}else if(!empty($this->html)){
			$send .= $this->line(sprintf("Content-Type: multipart/alternative; boundary=\"%s\"",$this->boundary["alternative"]));
		}else{
			$send .= $this->meta("plain");
		}
		return $send;
	}
	protected function implode_address($list){
		return trim(implode(",".$this->eol." ",is_array($list) ? $list : array($list)));
	}
	protected function body(){
		$send = "";
		$isattach = (!empty($this->attach));
		$ishtml = (!empty($this->html));

		if($isattach){
			$send .= $this->line("--".$this->boundary["mixed"]);

			if($ishtml){
				$send .= $this->line(sprintf("Content-Type: multipart/alternative; boundary=\"%s\"",$this->boundary["alternative"]));
				$send .= $this->line();
			}
		}
		$send .= (!$ishtml) ? (($isattach) ? $this->meta("plain").$this->line() : "").$this->line($this->message) : $this->alternative();
		if($isattach){
			foreach($this->attach as $attach){
				$send .= $this->line("--".$this->boundary["mixed"]);
				$send .= $this->attach_string($attach);
			}
			$send .= $this->line("--".$this->boundary["mixed"]."--");
		}
		return $send;
	}
	private function alternative(){
		$send = "";
		$send .= $this->line("--".$this->boundary["alternative"]);
		$send .= $this->meta("plain");
		$send .= $this->line();
		$send .= $this->line($this->encode($this->message));
		$send .= $this->line("--".$this->boundary["alternative"]);
		if(empty($this->image)) $send .= $this->meta("html");
		$send .= $this->line($this->encode((empty($this->image)) ? $this->line().$this->html : $this->related()));
		$send .= $this->line("--".$this->boundary["alternative"]."--");
		return $send;
	}
	private function related(){
		$send = $this->line().$this->html;
		$html = $this->html;
		foreach(array_keys($this->image) as $name){
			// tags
			$preg = '/(\s)(src|href)\s*=\s*(["\']?)' . preg_quote($name) . '\3/';
			$replace = sprintf('\1\2=\3cid:%s\3', $name);
			$html = mb_eregi_replace(substr($preg,1,-1),$replace,$html);
			// css
			$preg = '/url\(\s*(["\']?)' . preg_quote($name) . '\1\s*\)/';
			$replace = sprintf('url(\1cid:%s\1)', $name);
			$html = mb_eregi_replace(substr($preg,1,-1),$replace,$html);
		}
		if($html != $this->html){
			$send = "";
			$send .= $this->line(sprintf("Content-Type: multipart/related; boundary=\"%s\"",$this->boundary["related"]));
			$send .= $this->line();
			$send .= $this->line("--".$this->boundary["related"]);
			$send .= $this->meta("html");
			$send .= $this->line();
			$send .= $this->line($this->encode($html));
			
			foreach($this->image as $image){
				$send .= $this->line("--".$this->boundary["related"]);
				$send .= $this->attach_string($image,true);
			}
			$send .= $this->line("--".$this->boundary["related"]."--");
		}
		return $send;
	}
	private function jis($str){
		if(preg_match("/^[\w- ]+$/i",$str)) return $str;
		return sprintf("=?ISO-2022-JP?B?%s?=",base64_encode(mb_convert_encoding($str,"JIS",mb_detect_encoding($str))));
	}
	private function meta($type){
		switch(strtolower($type)){
			case "html": $type = "text/html"; break;
			default: $type = "text/plain";
		}
		switch($this->encode){
			case "utf8":
				return $this->line(sprintf("Content-Type: %s; charset=\"utf-8\"",$type)).
						$this->line("Content-Transfer-Encoding: 8bit");
			case "sjis":
				return $this->line(sprintf("Content-Type: %s; charset=\"iso-2022-jp\"",$type)).
						$this->line("Content-Transfer-Encoding: base64");
			default:
				return $this->line(sprintf("Content-Type: %s; charset=\"iso-2022-jp\"",$type)).
						$this->line("Content-Transfer-Encoding: 7bit");
		}
	}
	private function encode($message){
		switch($this->encode){
			case "utf8": return mb_convert_encoding($message,"UTF8",mb_detect_encoding($message));
			case "sjis": return mb_convert_encoding(base64_encode(mb_convert_encoding($message,"SJIS",mb_detect_encoding($message)),"JIS"));
			default: return mb_convert_encoding($message,"JIS",mb_detect_encoding($message));
		}
	}
	private function line($value=""){
		return $value.$this->eol;
	}
	private function attach_string($list,$id=false){
		list($file,$type) = $list;
		$send = "";
		$send .= $this->line(sprintf("Content-Type: %s; name=\"%s\"",(empty($type) ? "application/octet-stream" : $type),$file->name()));
		$send .= $this->line(sprintf("Content-Transfer-Encoding: base64"));
		if($id) $send .= $this->line(sprintf("Content-ID: <%s>", $file->name()));
		$send .= $this->line();
		$send .= $this->line(trim(chunk_split(base64_encode($file->get()),76,$this->eol)));
		return $send;
	}
	private function address($mail,$name){
		return '"'.(empty($name) ? $mail : $this->jis($name)).'" <'.$mail.'>';
	}

	/**
	 * 送信内容を取得
	 * @param boolean $eol
	 * @return string
	 */
	public function manuscript($eol=true){
		$pre = $this->eol;
		$this->eol = ($eol) ? "\r\n" : "\n";
		$send = $this->header().$this->line().$this->body();
		$this->eol = $pre;
		return $send;
	}
	/**
	 * メールを送信する
	 * @param string $subject
	 * @param string $message
	 * @return boolean
	 */
	public function send($subject=null,$message=null){
		if (!$this->isTo()) throw new Exception("undefine to");
		if (!$this->isFrom()) throw new Exception("undefine from");
		if($subject !== null) $this->subject($subject);
		if($message !== null) $this->message($message);
		return mail(null,($this->isSubject() ? null : $this->subject()),$this->body(),trim($this->header()));
	}
}
?>