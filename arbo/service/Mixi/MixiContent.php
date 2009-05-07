<?php
import("core.Tag");
module("MixiUser");
/**
 * @author Kazutaka Tokushima
 * @license New BSD License
 */
class MixiContent extends Object{
	static protected $__updated__ = "type=timestamp";
	static protected $__author__ = "type=MixiUser";
	protected $term;
	protected $title;
	protected $content;
	protected $updated;
	protected $author;
	protected $link;
	
	static public function parse(Tag $entry){
		$self = new self();
		$self->term($entry->f("category.param(term)"));
		$self->link($entry->f("link.param(href)"));
		$self->content($entry->f("content.value()"));
		$self->updated($entry->f("updated.value()"));
		
		$user = new MixiUser();
		$user->name($entry->f("author.name.value()"));
		$user->link($entry->f("author.uri.value()"));
		$self->author($user);
		return $self;
	}
}
?>