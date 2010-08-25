<?php

class PostsModel
{
	public static function fetchAll()
	{
		return dibi::query('
			SELECT *
			FROM [posts]
			ORDER BY [date]', dibi::DESC
		);
	}

	public static function fetchSingle($id)
	{
		return dibi::fetch('
			SELECT *
			FROM [posts]
			WHERE [id] = %i', $id
		);
	}
}