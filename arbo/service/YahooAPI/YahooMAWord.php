<?php
import("core.Tag");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class YahooMAWord extends Object{
	static private $ROMAN = array(
			"pya","pyu","pyo","pa","pi","pu","pe","po",
			"bya","byu","byo","ba","bi","bu","be","bo",
			"vya","vyu","vyo","va","vi","vu","ve","vo",
			"dya","dyu","dyo","da","di","du","de","do",
			"zya","zyu","zyo","za","zi","zu","ze","zo",
			"jya","jyu","jyo","je",
			"ja","ju","jo","ji",
			"gya","gyu","gyo","ga","gi","gu","ge","go",
			"kya","kyu","kyo","ka","ki","ku","ke","ko",
			"sya","syu","syo","sa","shi","si","su","se","so",
			"cha","chu","cho","tha","thu","tho","ta","chi","ti","tsu","tu","te","to",
			"cya","cyu","cyo","ca","ci","csu","ce","co",
			"nya","nyu","nyo","na","ni","nu","ne","no",
			"hya","hyu","hyo","ha","hi","hu","he","ho",
			"mya","myu","myo","ma","mi","mu","me","mo",
			"ya","yu","yo",
			"rya","ryu","ryo","ra","ri","ru","re","ro",
			"wa","wi","we","wo",
			"a","i","u","e","o",
			"n","nn","-"
		);
		static private $HIRGANA = array(
			"ぴゃ","ぴゅ","ぴょ","ぱ","ぴ","ぷ","ぺ","ぽ",
			"びゃ","びゅ","びょ","ば","び","ぶ","べ","ぼ",
			"う゛ぇ","う゛ぉ","びう゛ゃ","う゛ゅ","う゛ょ","う゛ぁ","う゛ぃ","う゛",
			"ぢゃ","ぢゅ","ぢょ","だ","ぢ","づ","で","ど",
			"じゃ","じゅ","じょ","ざ","じ","ず","ぜ","ぞ",
			"じゃ","じゅ","じぇ","じょ",
			"じゃ","じゅ","じょ","じ",
			"ぎゃ","ぎゅ","ぎょ","が","ぎ","ぐ","げ","ご",
			"きゃ","きゅ","きょ","か","き","く","け","こ",
			"しゃ","しゅ","しょ","さ","し","し","す","せ","そ",
			"ちゃ","ちゅ","ちょ","ちゃ","ちゅ","ちょ","た","ち","ち","つ","つ","て","と",
			"ちゃ","ちゅ","ちょ","か","し","く","せ","こ",
			"にゃ","にゅ","にょ","な","に","ぬ","ね","の",
			"ひゃ","ひゅ","ひょ","は","ひ","ふ","へ","ほ",
			"みゃ","みゅ","みょ","ま","み","む","め","も",
			"や","ゆ","よ",
			"りゃ","りゅ","りょ","ら","り","る","れ","ろ",
			"わ","うぃ","うぇ","を",
			"あ","い","う","え","お",
			"ん","ん","ー"
		);
	static protected $__count__ = "type=number";

	/** 出現数 */
	protected $count;
	/** 表記 */
	protected $surface;
	/** 読み */
	protected $reading;
	/** 品詞 */
	protected $pos;
	private $hiragana;
	private $katakana;
	private $roman;

	static public function __import__(){
		mb_internal_encoding("UTF8");
	}
	static public function parse(Tag $word_list){
		$result = array();
		foreach($word_list->in("word") as $word){
			$obj = new self();
			$obj->count($word->f("count.value()"));
			$obj->surface($word->f("surface.value()"));
			$obj->reading($word->f("reading.value()"));
			$obj->pos($word->f("pos.value()"));
			$result[] = $obj;
		}
		return $result;
	}
	/**
	 * すべて平仮名で読みを返す
	 *
	 * @return string
	 */
	public function hiragana(){
		if(!isset($this->hiragana)) $this->hiragana = ($this->alpha_to_kana($this->roman_to_kana($this->number_to_kana($this->reading()))));
		return $this->hiragana;
		/***
			$obj = new YahooMAWord();
			$obj->reading(123);
			eq("ひゃくにじゅうさん",$obj->hiragana());

			$obj = new YahooMAWord();
			$obj->reading(1234);
			eq("せんにひゃくさんじゅうよん",$obj->hiragana());

			$obj = new YahooMAWord();
			$obj->reading(12345);
			eq("いちまんにせんさんびゃくよんじゅうご",$obj->hiragana());

			$obj = new YahooMAWord();
			$obj->reading(123456);
			eq("じゅうにまんさんぜんよんひゃくごじゅうろく",$obj->hiragana());

			$obj = new YahooMAWord();
			$obj->reading(1234567);
			eq("ひゃくにじゅうさんまんよんせんごひゃくろくじゅうなな",$obj->hiragana());

			$obj = new YahooMAWord();
			$obj->reading(12345678);
			eq("せんにひゃくさんじゅうよんまんごせんろっぴゃくななじゅうはち",$obj->hiragana());

			$obj = new YahooMAWord();
			$obj->reading(123456789);
			eq("いちおくにせんさんびゃくよんじゅうごまんろくせんななひゃくはちじゅうきゅう",$obj->hiragana());

			$obj = new YahooMAWord();
			$obj->reading(1111);
			eq("せんひゃくじゅういち",$obj->hiragana());

			$obj = new YahooMAWord();
			$obj->reading(3456);
			eq("さんぜんよんひゃくごじゅうろく",$obj->hiragana());
		 */
	}
	/**
	 * すべてカタカナで読みを返す
	 *
	 * @return string
	 */
	public function katakana(){
		if(!isset($this->katakana)) $this->katakana = mb_convert_kana($this->hiragana(),"KVC");
		return $this->katakana;
		/***
			$obj = new YahooMAWord();
			$obj->reading(123);
			eq("ヒャクニジュウサン",$obj->katakana());
		*/
	}
	/**
	 * ローマ字で返す
	 *
	 * @return string
	 */
	public function roman(){
		if(!isset($this->roman)) $this->roman = $this->kana_to_roman($this->hiragana());
		return $this->roman;
		/***
			$obj = new YahooMAWord();
			$obj->reading(123);
			eq("hyakunizyuusan",$obj->roman());
		*/
	}
	protected function setPos($value){
		switch($value){
			case "形容詞": $this->pos = 1; break;
			case "形容動詞": $this->pos = 2; break;
			case "感動詞": $this->pos = 3; break;
			case "副詞": $this->pos = 4; break;
			case "連体詞": $this->pos = 5; break;
			case "接続詞": $this->pos = 6; break;
			case "接頭辞": $this->pos = 7; break;
			case "接尾辞": $this->pos = 8; break;
			case "名詞": $this->pos = 9; break;
			case "動詞": $this->pos = 10; break;
			case "助詞": $this->pos = 11; break;
			case "助動詞": $this->pos = 12; break;
			case "特殊": $this->pos = 13; break;
		}
	}
	/**
	 * 名詞か
	 *
	 * @return boolean
	 */
	public function isNoun(){
		return ($this->pos === 9);
	}
	/**
	 * 動詞か
	 *
	 * @return boolean
	 */
	public function isVerb(){
		return ($this->pos === 10);
	}

	private function number_to_kana($sentence){
		if(preg_match_all("/[\d]+/",$sentence,$matches)){
			$nos = array("","いち","に","さん","よん","ご","ろく","なな","はち","きゅう");
			$digitAs = array("","じゅう","ひゃく","せん");
			$digitBs = array("","じゅう","びゃく","ぜん");
			$digitCs = array("","じゅう","ぴゃく","せん");
			$digits = array("","まん","おく","ちょう","まんちょう","おくちょう","まんおくちょう","けい");

			foreach($matches[0] as $value){
				$reading = "";

				if(intval(str_repeat("9",8*4)) < intval($value)){
					$nos[0] = "ぜろ";

					for($i=strlen($value)-1,$y=0;$i>=0;$i--,$y++){
						$reading = $nos[intval($value[$i])].$reading;
					}
				}else{
					for($i=strlen($value)-1,$y=0;$i>=0;$i--,$y++){
						if($value[$i] != 0){
							switch($value[$i]){
								case 1:
									$reading = (($y%4 != 0) ? "" : $nos[intval($value[$i])]).$digitAs[$y%4].(($y%4 == 0) ? $digits[floor($y/4)] : "").$reading;
									break;
								case 3:
									$reading = $nos[intval($value[$i])].$digitBs[$y%4].(($y%4 == 0) ? $digits[floor($y/4)] : "").$reading;
									break;
								case 6:
									$reading = (($y%4 == 2) ? "ろっ" : $nos[intval($value[$i])]).$digitCs[$y%4].(($y%4 == 0) ? $digits[floor($y/4)] : "").$reading;
									break;
								case 8:
									$reading = (($y%4 > 1) ? "はっ" : $nos[intval($value[$i])]).$digitCs[$y%4].(($y%4 == 0) ? $digits[floor($y/4)] : "").$reading;
									break;
								default:
									$reading = $nos[intval($value[$i])].$digitAs[$y%4].(($y%4 == 0) ? $digits[floor($y/4)] : "").$reading;
							}
						}
					}
				}
				if(!empty($reading)) $sentence = str_replace($value,$reading,$sentence);
			}
			$sentence = str_replace("0","ぜろ",$sentence);
		}
		return $sentence;
	}
	private function roman_to_kana($sentence){
		return str_replace(self::$ROMAN,self::$HIRGANA,strtolower($sentence));
	}
	private function kana_to_roman($sentence){
		return str_replace(self::$HIRGANA,self::$ROMAN,strtolower($sentence));
	}
	private function alpha_to_kana($sentence){
		$from = array("a","b","c","d","e","f","g","h","i","j","k","l","m","n","o","p","q","r","s","t","u","v","w","x","y","z");
		$to = array("えー","びー","しー","でぃー","いー","えふ","じー","えっち","あい","じぇい","けー","える","えむ","えぬ","おー","ぴー","きゅー","あーる","えす","てぃー","ゆー","ぶい","だぶりゅう","えっくす","わい","ぜっと");
		return str_replace($from,$to,strtolower($sentence));
	}
}
?>