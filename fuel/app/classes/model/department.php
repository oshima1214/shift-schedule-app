<?php

namespace App\Model;

class Department
{
	/**
	 * 論理削除されていない部署を全件取得する
	 *
	 * @return array
	 */
	public static function find_all()
	{
		return \DB::select('id', 'name')
			->from('departments')
			->where('deleted_at', null)
			->order_by('id', 'asc')
			->execute()
			->as_array();
	}

	/**
	 * @param int $id
	 * @return array|null
	 */
	public static function find(int $id)
	{
		$row = \DB::select('id', 'name')
			->from('departments')
			->where('id', $id)
			->where('deleted_at', null)
			->execute()
			->current();

		// 該当なしのとき current() は null を返すため、真偽値で判定する
		return $row ? $row : null;
	}
}
