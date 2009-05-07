<?php
/**
 * 日付関係ユーティリティ
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Date{
	/**
	 * 指定した日時を加算したタイムスタンプを取得
	 *
	 * @param int $time
	 * @param int $seconds
	 * @param int $minutes
	 * @param int $hours
	 * @param int $day
	 * @param int $month
	 * @param int $year
	 * @return int
	 */
	static public function add($time,$seconds=0,$minutes=0,$hours=0,$day=0,$month=0,$year=0){
		/***
		 * eq(time()+1,Date::add(time(),1,0));
		 * eq(time()+60,Date::add(time(),0,1));
		 * eq(time()+3600,Date::add(time(),0,0,1));
		 * eq(time()-1,Date::add(time(),-1,0));
		 * eq(time()-60,Date::add(time(),0,-1));
		 * eq(time()-3600,Date::add(time(),0,0,-1));
		 *
		 */
		$dateList = getdate(intval(self::parse_date($time)));
		return mktime($dateList["hours"] + $hours,
						$dateList["minutes"] + $minutes,
						$dateList["seconds"] + $seconds,
						$dateList["mon"] + $month,
						$dateList["mday"] + $day,
						$dateList["year"] + $year
					);
	}

	/**
	 * 日を加算する
	 *
	 *
	 * @param int $time
	 * @param int $int
	 * @return int
	 */
	static public function add_day($add,$time=null){
		/***
		 * 	$time = time();
		 * 	eq(date("Y-m-d H:i:s",$time+(3600*24)),date("Y-m-d H:i:s",Date::add_day(1,$time)));
		 * 	eq(date("Y-m-d H:i:s",$time-(3600*24)),date("Y-m-d H:i:s",Date::add_day(-1,$time)));
		*/
		return self::add((($time === null) ? time() : $time),0,0,0,$add);
	}
	
	/**
	 * 時を加算する
	 *
	 * @param int $time
	 * @param int $add
	 * @return int
	 */
	static public function add_hour($add,$time=null){
		/***
			$time = time();
			eq(date("Y-m-d H:i:s",$time+3600),date("Y-m-d H:i:s",Date::add_hour(1,$time)));
			eq(date("Y-m-d H:i:s",$time-3600),date("Y-m-d H:i:s",Date::add_hour(-1,$time)));
		*/
		return self::add((($time === null) ? time() : $time),0,0,$add);
	}
	/**
	 * 日付文字列からタイムスタンプを取得する
	 *
	 * @param string $str
	 * @return int
	 */
	static public function parse_date($str){
		/***
		 * eq(-297993600,Date::parse_date("1960-07-23 05:00:00+05:00"));
		 * eq("1960-07-23 09:00:00",date("Y-m-d H:i:s",Date::parse_date("1960-07-23 05:00:00+05:00")));
		 * eq("1976-07-23 09:00:00",date("Y-m-d H:i:s",Date::parse_date("1976-07-23 05:00:00+05:00")));
		 * eq("2005-08-15 09:52:01",date("Y-m-d H:i:s",Date::parse_date("2005-08-15T01:52:01+0100")));
		 * eq("2005-08-15 10:01:01",date("Y-m-d H:i:s",Date::parse_date("Mon, 15 Aug 2005 01:01:01 UTC")));
		 * eq(null,Date::parse_date(null));
		 * eq(null,Date::parse_date(0));
		 * eq(null,Date::parse_date(""));
		 * eq("2005-03-02 00:00:00",date("Y-m-d H:i:s",Date::parse_date("2005/02/30 00:00:00")));
		*/
		if(empty($str)) return null;
		return (ctype_digit($str)) ? (int)$str : strtotime($str);
	}

	/**
	 * 時間文字列からタイムスタンプを取得する
	 *
	 * @param string $str
	 * @return int
	 */
	static public function parse_time($str){
		/***
		 * eq(3661,Date::parse_time("01:01:01"));
		 * eq(3661,Date::parse_time("1:1:1"));
		 * eq(61,Date::parse_time("0:1:1"));
		 * eq(null,Date::parse_time("0/1/1"));
		 * eq(0,Date::parse_time("00:00:00"));
		 * eq(0,Date::parse_time("0"));
		 * eq(null,Date::parse_time(""));
		 * eq(null,Date::parse_time(null));
		 * eq(null,Date::parse_time("00/00/00"));
		 * eq(726.4,Date::parse_time("00:12:06.40"));
		 */
		if(preg_match("/^(\d+):(\d+):(\d+)$/",$str,$match)) return ((int)$match[1] * 3600) + ((int)$match[2] * 60) + ((int)$match[3]);
		if(preg_match("/^(\d+):(\d+):(\d+)(\.[\d]+)$/",$str,$match)) return (float)((((int)$match[1] * 3600) + ((int)$match[2] * 60) + ((int)$match[3])).$match[4]);
		if(preg_match("/[^\d]/",$str)) return null;
		return (is_numeric($str)) ? (float)$str : null;
	}
	
	/**
	 * 日付文字列からintdateを取得する
	 *
	 * @param string $str
	 * @return int
	 */
	static public function parse_int($str){
		/***
		 * eq(20080401,Date::parse_int("2008/04/01"));
		 * eq(20080401,Date::parse_int("2008-04-01"));
		 * eq(20080401,Date::parse_int("2008-04/01"));
		 * eq(20080401,Date::parse_int("2008-4-1"));
		 * eq(2080401,Date::parse_int("2080401"));
		 * eq(null,Date::parse_int("2008A04A01"));
		 * eq(intval(date("Ymd")),Date::parse_int(time()));
		 * eq(19000401,Date::parse_int("1900-4-1"));
		 * eq(19001010,Date::parse_int("1900/10/10"));
		 * eq(10101,Date::parse_int("1/1/1"));
		 * eq(19601110,Date::parse_int("1960/11/10"));
		 */
		if(preg_match("/[^\d\/\-]/",$str)) return null;
		if(strlen(preg_replace("/[^\d]/","",$str)) > 8) $str = self::format($str,"Y/m/d");
		if(preg_match("/^(\d+)[^\d](\d+)[^\d](\d+)$/",$str,$match)) $str = sprintf("%d%02d%02d",intval($match[1]),intval($match[2]),intval($match[3]));
		return ($str > 0) ? intval($str) : null;
	}
	
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format($time,$format=""){
		/***
		 * eq("2007/07/19",Date::format("2007-07-18T16:16:31+00:00","Y/m/d"));
		 */
		$format = str_replace(array("YYYY","MM","DD"),array("Y","m","d"),$format);
		$time = self::parse_date($time);
		if(empty($time)) return "";
		if(empty($format)) $format = "Y/m/d H:i:s";
		return date($format,$time);
	}
	
	/**
	 * 整形された時間文字列を取得
	 *
	 * @param int $time
	 * @return string
	 */
	static public function format_time($time){
		/***
		 * eq("01:01:01",Date::format_time(3661));
		 * eq("00:01:01",Date::format_time(61));
		 * eq("300:01:01",Date::format_time(1080061));
		 * eq("00:00:00",Date::format_time(0));
		 */
		$time = self::parse_time($time);
		if(!is_numeric($time)) return "";
		return sprintf("%02d:%02d:%02d",intval($time/3600),intval(($time%3600)/60),intval(($time%3600)%60));
	}
	
	/**
	 * 整形された日付文字列を取得
	 *
	 * @param int $intdate
	 * @return string
	 */
	static public function format_date($intdate){
		/***
		 * eq("2008/04/07",Date::format_date(20080407));
		 * eq("208/04/07",Date::format_date(2080407));
		 */
		$date = self::parse_int($intdate);
		if(preg_match("/^([\d]+)([\d]{2})([\d]{2})$/",$date,$match)) return sprintf("%d/%02d/%02d",$match[1],$match[2],$match[3]);
		return "";
	}
	
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_full($time){
		/***
		 *  eq("2007/07/19 01:16:31 (Thu)",Date::format_full(Date::parse_date("2007-07-18T16:16:31+00:00")));
		 */
		return self::format($time,"Y/m/d H:i:s (D)");
	}
	/**
	 * (GMT)日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_atom($time){
		/***
		 * eq("2007-07-18T16:16:31Z",Date::format_atom(Date::parse_date("2007-07-18T16:16:31+00:00")));
		 */
		return self::format($time - date("Z"),"Y-m-d\TH:i:s\Z");
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_cookie($time){
		/***
		 * eq("Thu, 19 Jul 2007 01:16:31 JST",Date::format_cookie(Date::parse_date("2007-07-18T16:16:31+00:00")));
		 */
		return self::format($time,"D, d M Y H:i:s T");
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_ISO8601($time){
		/***
		 * eq("2007-07-19T01:16:31+0900",Date::format_ISO8601(Date::parse_date("2007-07-18T16:16:31+00:00")));
		 */
		return self::format($time,"Y-m-d\TH:i:sO");
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_RFC822($time){
		/***
		 * eq("Thu, 19 Jul 2007 01:16:31 JST",Date::format_RFC822(Date::parse_date("2007-07-18T16:16:31+00:00")));
		 */
		return self::format($time,"D, d M Y H:i:s T");
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_RFC850($time){
		/***
		 * eq("Thursday, 19-Jul-07 01:16:31 JST",Date::format_RFC850(Date::parse_date("2007-07-18T16:16:31+00:00")));
		 */
		return self::format($time,"l, d-M-y H:i:s T");
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_RFC1036($time){
		/***
		 * eq("Thursday, 19-Jul-07 01:16:31 JST",Date::format_RFC1036(Date::parse_date("2007-07-18T16:16:31+00:00")));
		 */
		return self::format($time,"l, d-M-y H:i:s T");
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_RFC1123($time){
		/***
		 * eq("Thu, 19 Jul 2007 01:16:31 JST",Date::format_RFC1123(Date::parse_date("2007-07-18T16:16:31+00:00")));
		 */
		return self::format($time,"D, d M Y H:i:s T");
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_RFC2822($time){
		/***
		 * eq("Thu, 19 Jul 2007 01:16:31 +0900",Date::format_RFC2822(Date::parse_date("2007-07-18T16:16:31+00:00")));
		 */
		return self::format($time,"D, d M Y H:i:s O");
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_rss($time){
		/***
		 * eq("Thu, 19 Jul 2007 01:16:31 JST",Date::format_rss(Date::parse_date("2007-07-18T16:16:31+00:00")));
		 */
		return self::format($time,"D, d M Y H:i:s T");
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @param string $format
	 * @return string
	 */
	static public function format_w3c($time){
		/***
		 * eq("2007-07-19T01:16:31+09:00",Date::format_w3c(Date::parse_date("2007-07-18T16:16:31+00:00")));
		 */
		$time = self::parse_date($time);
		if($time === null) return "";
		$tzd = date("O",$time);
		$tzd = $tzd[0].substr($tzd,1,2).":".substr($tzd,3,2);
		return self::format($time,"Y-m-d\TH:i:s").$tzd;
	}
	/**
	 * 日付書式にフォーマットした文字列を取得
	 *
	 * @param int $time
	 * @return string
	 */
	static public function format_pdf($time){
		/***
		* eq("D:20070719011631+09'00'",Date::format_pdf(Date::parse_date("2007-07-18T16:16:31+00:00")));
		*/
		$tzd = date("O",$time);
		$tzd = $tzd[0].substr($tzd,1,2)."'".substr($tzd,3,2)."'";
		return "D:".self::format($time,"YmdHis").$tzd;
	}
	
	/**
	 * 日付比較 ==
	 *
	 * @param mixed $timestampA
	 * @param mixed $timestampB
	 * @return boolean
	 */
	static public function eq($timestampA,$timestampB){
		/***
		 * assert(Date::eq("2008/03/31","2008/03/31"));
		 * assert(!Date::eq("2008/03/31","2008/03/30"));
		 */
		return self::parse_date($timestampA) == self::parse_date($timestampB);
	}
	/**
	 * 日付比較 >
	 *
	 * @param mixed $timestampA
	 * @param mixed $timestampB
	 * @return boolean
	 */
	static public function gt($timestampA,$timestampB){
		/***
		 * assert(Date::gt("2008/03/31","2008/03/30"));
		 * assert(!Date::gt("2008/03/30","2008/03/31"));
		 * assert(!Date::gt("2008/03/31","2008/03/31"));
		 */
		return self::parse_date($timestampA) > self::parse_date($timestampB);
	}
	
	/**
	 * 日付比較 >=
	 *
	 * @param mixed $timestampA
	 * @param mixed $timestampB
	 * @return boolean
	 */
	static public function gte($timestampA,$timestampB){
		/***
		 * assert(Date::gte("2008/03/31","2008/03/30"));
		 * assert(!Date::gte("2008/03/30","2008/03/31"));
		 * assert(Date::gte("2008/03/31","2008/03/31"));
		 */
		return self::parse_date($timestampA) >= self::parse_date($timestampB);
	}
	
	/**
	 * 年齢の算出
	 *
	 * @param int $intdate
	 * @param int $time
	 * @return int
	 */
	static public function age($intdate,$time=null){
		/***
		 * eq(5,Date::age(20001010,Date::parse_date("2005/01/01")));
		 * eq(6,Date::age(20001010,Date::parse_date("2005/10/10")));
		 * eq(5,Date::age(20001010,Date::parse_date("2005/10/9")));
		 * eq(5,Date::age(20001010,Date::parse_date("2005/10/11")));
		 */
		if($time === null) $time = time();
		$intdate = intval(preg_replace("/[^\d]/","",$intdate));
		$a = intval(substr(self::format($time,"Ymd"),0,-4)) - intval(substr($intdate,0,-4));
		if(self::gte(self::parse_date("2000".substr($intdate,-4)),self::parse_date("2000".substr($time,-4)))) $a += 1;
		return $a;
	}
	
	/**
	 * 曜日の算出
	 *
	 * @param mixed $date intdate / string date
	 * @return int 0:日 1:月 2:火 3:水 4:木 5:金 6:土
	 */
	static public function weekday($date){
		/***
		 * eq(0,Date::weekday(19050129));
		 * eq(1,Date::weekday(18890211));
		 * eq(2,Date::weekday(20050927));
		 * eq(3,Date::weekday(17890304));
		 * eq(4,Date::weekday(15880721));
		 * eq(5,Date::weekday(18681023));
		 * eq(6,Date::weekday(16001021));
		 * eq(0,Date::weekday("1905-01-29"));
		 * eq(1,Date::weekday("1889/02/11"));
		 */
		if(!is_numeric($date)){
			$date = self::parse_int($date);
		}
		if(is_null($date)) return;
		$year = intval(floor($date / 10000));
		$month = intval(floor(($date % 10000) / 100));
		$day = intval($date % 100);
		if($month == 1 || $month == 2){
			$year--;
			$month += 12;
		}
		return ($year + intval($year/4) - intval($year/100) + intval($year/400) + intval((13*$month+8)/5) + $day) % 7;
	}
}
?>