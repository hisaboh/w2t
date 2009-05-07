<?php
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class SimpleAuth{
	public function condition(Request $request){
		return ($request->isPost() && $request->inVars("login") === Rhaco::def("generic.module.SimpleAuth@user")
				&& md5(sha1($request->inVars("password"))) === Rhaco::def("generic.module.SimpleAuth@password"));
	}
	public function invalid(Request $request){
		$flow = new Flow();
		$flow->output(Rhaco::module_path("templates/login.html"));
	}
	public function after(Request $request){
	}
}
?>