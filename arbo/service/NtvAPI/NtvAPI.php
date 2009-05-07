<?php
import("core.Http");
module("NtvProgramResult");
module("NtvNewsResult");
/**
 * 日テレAPI
 * @see http://www.ntv.co.jp/appli/api/index.html
 */
class NtvAPI extends Http{
	protected $_base_url_ = "http://appli.ntv.co.jp/ntv_WebAPI/";
	protected $_api_key_name_ = "key";
	protected $referer_domain;

	/**
	 * @param string $referer_domain api keyを取得したときに登録したドメイン
	 */
	protected function __new__($referer_domain){
		$this->referer_domain = $referer_domain;	
	}
	public function program_cast($cast,$period_start=null,$period_end=null){
		$this->set_api_key(def("arbo.service.NtvAPI@program_api_key"));
		$this->vars("cast",mb_convert_encoding($cast,"SJIS",mb_detect_encoding($cast)));
		if(!empty($period_start)){
			$this->vars("period_start",$period_start);
			$this->vars("period_end",(empty($period_end) ? $period_start : $period_end));
		}
		$this->header("Referer",$this->referer_domain);
		$this->do_get("program");
		return NtvProgramResult::parse_list($this->body());
		/***
			$api = new NtvAPI(def("arbo.service.NtvAPI@test_domain"));
			try{
				$result = $api->program_cast("加藤浩次","20090316");
				eq(true,true);
			}catch(Exception $e){
				eq(false,$e->getMessage());
			}
		 */
	}
	public function program_title($title,$period_start=null,$period_end=null){
		$this->set_api_key(def("arbo.service.NtvAPI@program_api_key"));
		$this->vars("title",mb_convert_encoding($title,"SJIS",mb_detect_encoding($title)));
		if(!empty($period_start)){
			$this->vars("period_start",$period_start);
			$this->vars("period_end",(empty($period_end) ? $period_start : $period_end));
		}
		$this->header("Referer",$this->referer_domain);		
		$this->do_get("program");
		return NtvProgramResult::parse_list($this->body());
		/***
			$api = new NtvAPI(def("arbo.service.NtvAPI@test_domain"));
			try{
				$result = $api->program_title("しゃべくり","20090316");
				eq(true,true);
			}catch(Exception $e){
				eq(false,$e->getMessage());
			}
		*/
	}
	public function news($word,$period_start=null,$period_end=null){
		$this->set_api_key(def("arbo.service.NtvAPI@news_api_key"));
		$this->vars("word",mb_convert_encoding($word,"SJIS",mb_detect_encoding($word)));
		if(!empty($period_start)){
			$this->vars("period_start",$period_start);
			$this->vars("period_end",(empty($period_end) ? $period_start : $period_end));
		}
		$this->header("Referer",$this->referer_domain);		
		$this->do_get("news");
		return NtvNewsResult::parse_list($this->body());
		/***
			$api = new NtvAPI(def("arbo.service.NtvAPI@test_domain"));
			try{
				$result = $api->news("シャトル");
				eq(true,true);
			}catch(Exception $e){
				eq(false,$e->getMessage());
			}
		*/
	}
}
?>