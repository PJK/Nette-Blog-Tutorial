<?php

class CommentsModel
{
	public static function fetchAll($postId)
	{
		return dibi::query('
			SELECT *
			FROM [comments]
			WHERE [post_id] = %i', $postId
		);
	}

	public static function insert($data)
	{
		dibi::query('
			INSERT INTO [comments]', $data
		);

		return dibi::getInsertId();
	}
}