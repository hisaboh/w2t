<?php
import("core.Date");

class GoogleCalendarAPI extends Http{
	private $auth;
	private $sid;
	private $lsid;
	private $email;
	
	protected function __new__($email=null,$password=null){
		$this->email = def("arbo.service.GoogleAPI@email",$email);
		$password = def("arbo.service.GoogleAPI@password",$password);

		$this->vars("Email",$this->email);
		$this->vars("Passwd",$password);
		$this->vars("accountType","GOOGLE");
		$this->vars("source","Google-Contact-Lister");
		$this->vars("service","cl");

		$this->do_post("https://www.google.com/accounts/ClientLogin");
		foreach(explode("\n",$this->body()) as $line){
			if(strpos($line,"SID=") === 0){
				$this->sid = trim(substr($line,4));
			}else if(strpos($line,"LSID=") === 0){
				$this->lsid = trim(substr($line,5));
			}else if(strpos($line,"Auth=") === 0){
				$this->auth = trim(substr($line,5));
			}
		}
		if(empty($this->auth)) throw new Exception("auth");
	}
	private function auth_header(){
		$this->header("Content-Type","application/atom+xml");
		$this->header("Authorization","GoogleLogin auth=".$this->auth);
	}
	private function create_entry($title,$content,$start=null,$end=null,$where=null){
		$start = (empty($start)) ? time() : Date::parse_date($start);
		$end = (empty($end)) ? ($start + 3600) : Date::parse_date($end);
		$entry = R(new Tag("entry"))->add("xmlns","http://www.w3.org/2005/Atom")->add("xmlns:gd","http://schemas.google.com/g/2005");
		$entry->add(R(new Tag("title",$title))->add("type","text"));
		$entry->add(R(new Tag("content",$content))->add("type","text"));
		$entry->add(R(new Tag("gd:when"))->add("startTime",Date::format_w3c($start))->add("endTime",Date::format_w3c($end)));
		$entry->add(R(new Tag("category"))->add("scheme","http://schemas.google.com/g/2005#kind")->add("term","http://schemas.google.com/g/2005#event"));
		if(!empty($where)) $entry->add(R(new Tag("gd:where"))->add("valueString",$where));
		return $entry->get();
	}
	public function add_event($title,$content,$start=null,$end=null,$where=null){
		$raw = $this->create_entry($title,$content,$start,$end,$where);
		$this->auth_header();
		$this->raw($raw);
		
		$this->status_redirect(false);
		$this->do_post("http://www.google.com/calendar/feeds/default/private/full");

		if(preg_match("/Location:[\040](.*)/i",$this->head(),$redirect_url)){
			$this->auth_header();
			$this->raw($raw);
			$this->do_post($redirect_url[1]);
		}
		$this->status_redirect(true);
	}
	public function calendars(){
		$this->auth_header();
		$this->do_get("http://www.google.com/calendar/feeds/".$this->email);
//		return $this->body();
	}
}
?>