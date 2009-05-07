<?php
require(dirname(dirname(__FILE__))."/rhaco2");
header_output_text();
def("core.Log@disp","debug");
def("db.Db@test_1","type=mysql,dbname=rhacotest1,user=root,password=root,");
def("db.Db@test_2","type=mysql,dbname=rhacotest2,user=root,password=root,");

import("core.Flow");
import("ext.Test");

$flow = new Flow();
if($flow->isVars("class")){
	test($flow->inVars("class"),$flow->inVars("method"),$flow->inVars("block"));
}else{
	if(!$flow->isVars("full")) Test::exec_type(Test::FAIL);
	tests(dirname(dirname(__FILE__)));
}
?>
