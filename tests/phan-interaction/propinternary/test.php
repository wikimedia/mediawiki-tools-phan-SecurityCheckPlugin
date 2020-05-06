<?php

// See also: https://github.com/phan/phan/issues/3910

function extractRevisionInfo() {
	$comment = getComment();
	$comment = $comment ? $comment->text : ''; // Avoid: PhanTypeExpectedObjectPropAccess
}

function getComment() : ?CommentStoreComment {
	return new CommentStoreComment();
}

class CommentStoreComment {
	/** @var string */
	public $text;
}
