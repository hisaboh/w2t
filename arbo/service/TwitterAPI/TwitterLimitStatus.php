<?php
import("core.Text");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class TwitterLimitStatus extends Object{
	static protected $__hourly_limit__ = "type=number";
	static protected $__reset_time__ = "type=timestamp";
	static protected $__reset_time_in_secondsme__ = "type=number";
	static protected $__remaining_hits__ = "type=number";
	protected $hourly_limit;
	protected $reset_time;
	protected $reset_time_in_seconds;
	protected $remaining_hits;

	static public function parse($response){
		if(!is_array($response)) Tag::setof($response,$response,"hash");
		$obj = new self();
		return $obj->cp($response);
	}
}
?>