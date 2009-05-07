<?php
Rhaco::import("core.File");
Rhaco::import("core.Log");
/**
 *
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class Command extends Object{
	protected $resource;
	protected $stdout;
	protected $stderr;
	protected $end_code;
	private $proc;
	private $close = true;

	protected function __new__($command=null){
		if(!empty($command)){
			$this->open($command);
			$this->close();
		}
	}
	public function open($command,$out_file=null,$error_file=null){
		Log::debug($command);
		$this->close();
		
		if(!empty($out_file)) File::write($out_file);
		if(!empty($error_file)) File::write($error_file);
		$out = (empty($out_file)) ? array("pipe","w") : array("file",$out_file,"w");
		$err = (empty($error_file)) ? array("pipe","w") : array("file",$error_file,"w");
		$this->proc = proc_open($command,array(array("pipe","r"),$out,$err),$this->resource);
		$this->close = false;
	}
	public function write($command){
		Log::debug($command);
		fwrite($this->resource[0],$command."\n");
	}
	public function gets(){
		if(isset($this->resource[1])){
			$value = fgets($this->resource[1]);
			$this->stdout .= $value;
			return $value;
		}
	}
	public function getc(){
		if(isset($this->resource[1])){
			$value = fgetc($this->resource[1]);
			$this->stdout .= $value;
			return $value;
		}
	}
	public function close(){
		if(!$this->close){
			if(isset($this->resource[0])) fclose($this->resource[0]);
			if(isset($this->resource[1])){
				while(!feof($this->resource[1])) $this->stdout .= fgets($this->resource[1]);
				fclose($this->resource[1]);
			}
			if(isset($this->resource[2])){
				while(!feof($this->resource[2])) $this->stderr .= fgets($this->resource[2]);
				fclose($this->resource[2]);
			}
			$this->end_code = proc_close($this->proc);
			$this->close = true;
		}
	}
	protected function __del__(){
		$this->close();
	}
	protected function __str__(){
		return $this->out;
	}
	static public function out($command){
		$self = new self($command);
		return $self->stdout();
	}
	static public function error($command){
		$self = new self($command);
		return $self->stderr();
	}
}
?>