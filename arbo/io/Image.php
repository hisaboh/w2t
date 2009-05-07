<?php
import("core.Command");
import("core.File");
/**
 * @see http://ffmpeg.org/
 *
 * @author Kazutaka Tokushima
 * @author riaf <riafweb@gmail.com>
 * @license New BSD License
 */
class Image extends Object{
	static private $imagemagick_convert;
	static private $imagemagick_mogrify;
	
	static protected $__width__ = "type=number";
	static protected $__height__ = "type=number";
	static protected $__type__ = "type=number";
	protected $resource;
	protected $width;
	protected $height;
	protected $type;

	static public function __import__(){
		extension_load("gd");
		self::$imagemagick_convert = def("arbo.io.Image@convert","/usr/bin/convert");
		self::$imagemagick_mogrify = def("arbo.io.Image@mogrify","/usr/bin/mogrify");
	}
	protected function __del__(){
		if(is_resource($this->resource)) imagedestroy($this->resource);
	}
	protected function __init__(){
		$this->type = IMAGETYPE_JPEG;
	}
	protected function setResource($resource,$type=null){
		$this->resource = $resource;
		$this->width = imagesx($this->resource);
		$this->height = imagesy($this->resource);
		if($type !== null) $this->type = $type;
	}
	/**
	 * 文字列から新規インスタンスを返す
	 *
	 * @param string $src
	 * @return Image
	 */
	static public function parse($src){
		$self = new self();
		$self->resource(imagecreatefromstring($src));
		return $self;
	}
	/**
	 * ファイル名から新規インスタンスを返す
	 *
	 * @param string $filename
	 * @return Image
	 */
	static public function load($filename){
		$size = getimagesize($filename);
		if($size === false) throw new Exception("invalid data");
		$self = new self();
		switch($size[2]){
			case IMAGETYPE_GIF:
				$self->resource(imagecreatefromgif($filename),IMAGETYPE_GIF);
				break;
			case IMAGETYPE_JPEG:
				$self->resource(imagecreatefromjpeg($filename),IMAGETYPE_JPEG);
				break;
			case IMAGETYPE_PNG:
				$self->resource(imagecreatefrompng($filename),IMAGETYPE_PNG);
				break;
			case IMAGETYPE_WBMP:
				$self->resource(imagecreatefromwbmp($filename),IMAGETYPE_WBMP);
				break;
			default:
				throw new Exception("invalid data");
		}
		return $self;
	}
	private function image_resize($dst_width,$dst_height){
		switch($this->type){
			case IMAGETYPE_GIF:
				$dst_image = imagecreate($dst_width,$dst_height);
				$tcolor = imagecolorallocate($dst_image,255,255,255);
				imagecolortransparent($dst_image,$tcolor);
				imagefilledrectangle($dst_image,0,0,$dst_width,$dst_height,$tcolor);
				break;
			default:
				$dst_image = imagecreatetruecolor($dst_width,$dst_height);
				break;
		}
		imagecopyresized($dst_image,$this->resource,0,0,0,0,$dst_width,$dst_height,$this->width,$this->height);
		imagedestroy($this->resource);
		$this->width = $dst_width;
		$this->height = $dst_height;
		$this->resource = $dst_image;
		return $this;
	}
	/**
	 * リサイズを行う
	 *
	 * @param int $x
	 * @param int $y
	 * @return Image
	 */
	public function resize($x, $y){
		return $this->resize_width($x)->resize_height($y);
	}
	/**
	 * 幅指定のリサイズを行う
	 *
	 * @param int $width
	 * @param boolean $keep
	 * @return Image
	 */
	public function resize_width($width,$keep=false){
		$dst_height = $keep ? $this->height : ($this->height / ($this->width / $width));
		return $this->image_resize($width,$dst_height);
	}
	/**
	 * 縦指定のリサイズを行う
	 *
	 * @param int $height
	 * @param boolean $keep
	 * @return Image
	 */
	function resize_height($height,$keep=false){
		$dst_width  = $keep ? $this->width : ($this->width / ($this->height / $height));
		return $this->image_resize($dst_width,$height);
	}
	/**
	 * 画像が指定サイズより大きい場合にリサイズを行う
	 *
	 * @param int $x
	 * @param int $y
	 * @return Image
	 */
	public function fit($x,$y){
		return $this->resize_width($x)->resize_height($y);
	}

	/**
	 * 画像の横が指定サイズより大きい場合にリサイズを行う
	 *
	 * @param int $x
	 * @return Image
	 */
	function fit_width($x){
		if($x < $this->width) $this->resize_width($x);
		return $this;
	}

	/**
	 * 画像の縦が指定サイズより大きい場合にリサイズを行う
	 *
	 * @param int $y
	 * @return Image
	 */
	function fit_height($y){
		if($y < $this->height) $this->resize_height($y);
		return $this;
	}

	/**
	 * ファイルに出力する
	 *
	 * @param string $filename
	 * @param int $type
	 * @return string
	 */
	public function write($filename,$type=null){
		File::mkdir(dirname($filename));
		if(is_null($type)) $type = $this->type;
		$bool = false;
		$ext = image_type_to_extension($type);
		if(!preg_match("/".preg_quote($ext)."$/i",$filename)) $filename = $filename.$ext;

		switch($type){
			case IMAGETYPE_GIF: $bool = imagegif($this->resource,$filename); break;
			case IMAGETYPE_JPEG: $bool = imagejpeg($this->resource,$filename); break;
			case IMAGETYPE_PNG: $bool = imagepng($this->resource,$filename); break;
			case IMAGETYPE_WBMP: $bool = imagewbmp($this->resource,$filename); break;
		}
		if(!$bool) throw new Exception("invalid type");
		return $filename;
	}
	/**
	 * イメージを取得する
	 *
	 * @param int $type
	 * @return binary
	 */
	public function read($type){
		if(is_null($type)) $type = $this->type;
		ob_start();
			$this->output_image($type);
		return ob_get_clean();
	}
	private function output_image($type){
		switch($type){
			case IMAGETYPE_GIF: return imagejpeg($this->resource);
			case IMAGETYPE_JPEG: return imagegif($this->resource);
			case IMAGETYPE_PNG: return imagepng($this->resource);
			case IMAGETYPE_WBMP: return imagewbmp($this->resource);
		}
		throw new Exception("invalid type");
	}
	/**
	 * 標準出力に出力する
	 *
	 * @param int $type
	 */
	public function output($type=null){
		if(is_null($type)) $type = $this->type;
		header('Content-Type: '.image_type_to_mime_type($type));
		return $this->output_image($type);
	}
	static public function all_resize($input_dir){
		$cmd = new Command(sprintf("%s resize 20% %s",self::$imagemagick_mogrify,File::path($input_dir,"*")));
		if($cmd->isStderr()) throw new Exception($cmd->stderr());
	}
	static public function anime_gif($input_dir,$output_filename,$delay=50){
		if(!is_dir($input_dir)) throw new Exception($input_dir." not found");
		if(!preg_match("/\.gif$/i",$output_filename)) $output_filename = $output_filename.".gif";
		$cmd = new Command(sprintf("%s -delay %d -loop 0 %s %s",self::$imagemagick_convert,$delay,File::path($input_dir,"*"),$output_filename));
		if(!is_file($output_filename)) throw new Exception($cmd->stderr());
		return $output_file;
	}
}
?>