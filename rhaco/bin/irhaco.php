<?php
mb_language("Japanese");
$isrhaco = false;
$settings = dirname(__FILE__)."/__settings__.php";
if(is_file($settings)){
	$isrhaco = true;
	require($settings);
}else{
	$rp = "";
	$rhacopath = (isset($_SERVER["argc"]) && $_SERVER["argc"] == 2) ? $_SERVER["argv"][1] : "";
	if(!empty($rhacopath) && substr($rhacopath,-1) != "/") $rhacopath .= "/";
	if($rp == "" && is_file($rhacopath."__rhaco__.php")) $rp = $rhacopath."__rhaco__.php";
	if($rp == "" && is_file(dirname(dirname(__FILE__))."/rhaco/__rhaco__.php")) $rp = dirname(dirname(__FILE__))."/rhaco/__rhaco__.php";
	if($rp == "" && is_file(dirname(__FILE__)."/modules/rhaco/__rhaco__.php")) $rp = dirname(__FILE__)."/modules/rhaco/__rhaco__.php";
	if($rp != "" && include_once($rp)) $isrhaco = true;
	if($isrhaco) application_settings(__FILE__);
}
if($isrhaco){
	import("core.Log");
	import("ext.Test");
	import("ext.Setup");
	import("ext.Info");
}
if(!empty($_SERVER["HTTP_USER_AGENT"])){
	import("generic.Crud");
	R(new Crud())->handler()->output();
}
$__encode__ = (substr(PHP_OS,0,3) == 'WIN') ? "SJIS" : "UTF8";
$__multi__ = 0;
$__store__ = "";

while(true){
	print(">> ");

	$__buffer__ = "";
	$fp = fopen("php://stdin","r");
	while(substr($__buffer__,-1) != "\n" && substr($__buffer__,-1) != "\r\n"){
		$__buffer__ .= fgets($fp,4096);
	}
	fclose($fp);
	$__buffer__ = substr(str_replace("\r\n","\n",$__buffer__),0,-1);
	
	if($__buffer__ != ""){
		if($__buffer__ == "."){
			break;
		}else{
			$check = preg_replace("/([\"\']).+?\\1/","",$__buffer__);
			$__multi__ = $__multi__ + substr_count($check,"{") - substr_count($check,"}");
			$__store__ .= $__buffer__;

			if($__multi__ == 0){
				$__store__ = trim($__store__);

				if(strpos($__store__,"\n") === false){
					$__args__ = explode(" ",$__store__);
					if(ctype_alnum($__args__[0]) && function_exists($__args__[0])){
						$__f__ = array_shift($__args__);

						if(sizeof($__args__) > 0){
							$__a__ = array();
							foreach($__args__ as $__arg__){
								if(!empty($__arg__)){
									if($__arg__[0] != "\$") $__arg__ = "\"".str_replace("\"","\\\"",$__arg__)."\"";
									$__a__[] = $__arg__;
								}
							}
							$__f__ .= "(".implode(",",$__a__).");";
						}else{
							$__f__ .= "();";
						}
						$__store__ = $__f__;
					}
					unset($__f__,$__a__,$__args__,$__arg__);
				}
				if($__store__ != ""){
					ob_start();
						eval($__store__);
					$print = ob_get_clean();
					while(0 != ob_get_level()) ob_get_clean();

					if("" !== $print){
						print(mb_convert_encoding($print,$__encode__,mb_detect_encoding($print))."\n");
					}
					$__store__ = "";
				}
			}
		}
	}
}
?>