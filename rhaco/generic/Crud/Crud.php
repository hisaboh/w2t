<?php
Rhaco::import("core.Flow");
Rhaco::import("db.Dao");
Rhaco::import("db.ActiveMapper");
Rhaco::module("CrudTools");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Crud extends Flow{
	private function get_class($name){
		$this->search_model();
		if(class_exists($name)) return new $name($this->to_dict("primary"));
		return ACTIVE_TABLE($name,null,$this->to_dict("primary"));
	}
	public function do_find($name){
		if($this->login()){
			$class = $this->get_class($name);
			$order = $this->inVars("order");
			$paginator = new Paginator(10,$this->inVars("page"));
			$this->vars("object_list",$class->find_page($this->inVars("query"),$paginator,$order,$this->inVars("porder")));
			$this->vars("paginator",$paginator->cp(array("query"=>$this->inVars("query"),"porder"=>$order)));
			$this->vars("porder",$order);
			$this->vars("model",$class);
			$this->vars("f",new CrudTools($name));
			$this->template(Rhaco::module_path("templates/find.html"));
		}
		return $this;
	}
	public function do_detail($name){
		if($this->login()){
			$class = $this->get_class($name);
			$this->vars("object",$class->sync());
			$this->vars("model",$class);
			$this->vars("f",new CrudTools($name));
			$this->template(Rhaco::module_path("templates/detail.html"));
		}
		return $this;
	}
	public function do_drop($name){
		if($this->login()){
			$class = $this->get_class($name);
			$obj = $class->sync();
	
			if($this->isPost()){
				$obj->set_model($this->vars());
				$obj->delete();
				C($obj)->commit();
				$tools = new CrudTools($name);
				Http::redirect(Http::referer());
			}
		}
		return $this->do_find($name);
	}
	public function do_update($name){
		if($this->login()){
			$class = $this->get_class($name);
			$obj = $class->sync();
			$tools = new CrudTools($name);
	
			if($this->isPost()){
				try{
					$obj->set_model($this->vars());
					$obj->save();
					C($obj)->commit();
					Http::redirect($tools->link_find());
				}catch(Exception $e){}
			}else{
				$this->cp($obj);
			}
			$this->vars("model",$class);
			$this->vars("f",$tools);
			$this->template(Rhaco::module_path("templates/update.html"));
		}
		return $this;
	}
	public function do_create($name){
		if($this->login()){
			$class = $this->get_class($name);
			$tools = new CrudTools($name);
	
			if($this->isPost()){
				try{
					$class->set_model($this->vars());
					$class->save();
					C($class)->commit();
					Http::redirect($tools->link_find());
				}catch(Exception $e){}
			}else{
				$this->cp($class);
			}
			$this->vars("model",$class);
			$this->vars("f",$tools);
			$this->template(Rhaco::module_path("templates/update.html"));
		}
		return $this;
	}
	public function models(){
		if($this->login()){
			$this->vars("models",$this->search_model());
			$this->vars("f",new CrudTools(""));
			$this->template(Rhaco::module_path("templates/models.html"));
		}
		return $this;
	}
	private function search_model(){
		$models = array();
		foreach(File::ls(Rhaco::path(),true) as $file){
			if($file->isClass()){
				ob_start();
					include_once($file->fullname());
				ob_end_clean();
				if(is_implements_of($file->oname(),"Model")) $models[] = $file;
			}
		}
		return $models;
	}
	public function handler(array $urlconf=array()){
		if(empty($urlconf)){
			$urlconf = array(
				"^/(.+?)/create[/]*$"=>"method=do_create",
				"^/(.+?)/update[/]*$"=>"method=do_update",
				"^/(.+?)/detail[/]*$"=>"method=do_detail",
				"^/(.+?)/drop[/]*$"=>"method=do_drop",
				"^/(.+?)[/]*$"=>"method=do_find",
				""=>"method=models",
			);
		}
		parent::handler($urlconf);
		return $this;
	}
}
?>