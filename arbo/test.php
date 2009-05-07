<?php
include_once(dirname(__FILE__)."/__settings__.php");
application_settings(__FILE__,null,dirname(dirname(__FILE__)));

import("core.Log");
import("core.Flow");
import("core.File");
import("ext.Test");
import("ext.Setup");

header_output_text();

$flow = new Flow();
if($flow->isVars("class")){
	test($flow->inVars("class"),$flow->inVars("method"),$flow->inVars("block"));
}else{
	if(!$flow->isVars("full")) Test::exec_type(Test::FAIL);
	tests(root_path());
}
?>
