<?php
/**
 * @package Nette++
 * @author Pavel Kalvoda
 */

abstract class Table {
	/** @var string jmeno tabulku */
	public static $name;

	/** @var boolean prim. key AI? */
	public static $autoIncrement = FALSE;

	/**
	 *
	 * @param <type> $where
	 * @param <type> $order
	 * @param <type> $limit
	 * @param <type> $offset
	 * @return DibiResult
	 */

	public static function fetchAll($where=NULL,$order=NULL,$limit=NULL,$offset=NULL) {
		return dibi::query(
			'SELECT * FROM %n', self::$name,
            '%if', isset($where), 'WHERE %and', isset($where) ? $where : array(), '%end',
            '%if', isset($order), 'ORDER BY %by', $order, '%end',
            '%if', isset($limit), 'LIMIT %i %end', $limit,
            '%if', isset($offset), 'OFFSET %i %end', $offset
		);
	}
	
	/**
	 *
	 * @param array $data
	 * @return DibiFluent
	 */

	public static function insert(array $data) {
		$res = dibi::insert(self::$name, $data);
		if (self::$autoIncrement) {
			return $res->insertId;
		} else {
			return TRUE;
		}
	}

	public static function getDataSource() {
		return dibi::dataSource(self::$name);
	}
}