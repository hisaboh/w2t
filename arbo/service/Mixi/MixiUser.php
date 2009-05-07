<?php
import("core.Tag");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class MixiUser extends Object{
	static protected $__updated__ = "type=timestamp";
	protected $name;
	protected $image;
	protected $relation;
	protected $updated;
	protected $link;

	static public function parse(Tag $entry){
		$self = new self();
		$author = $entry->f("author");
		if($author !== null){
			$self->name($author->f("name.value()"));
			$self->image($author->f("tracks:image.value()"));
			$self->relation($author->f("tracks:relation.value()"));
		}else{
			$self->name($entry->f("title.value()"));
			$self->image($entry->f("icon.value()"));
			$self->relation("friends");
		}
		$self->updated($entry->f("updated.value()"));
		$self->link($entry->f("link.param(href)"));
		return $self;
	}
}
?>