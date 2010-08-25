<?php

/**
 * dibi - tiny'n'smart database abstraction layer
 * ----------------------------------------------
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @license    http://dibiphp.com/license  dibi license
 * @link       http://dibiphp.com
 * @package    dibi
 */



/**
 * Reflection metadata class for a database.
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @package    dibi
 *
 * @property-read string $name
 * @property-read array $tables
 * @property-read array $tableNames
 */
class DibiDatabaseInfo extends DibiObject
{
	/** @var IDibiReflector */
	private $driver;

	/** @var string */
	private $name;

	/** @var array */
	private $tables;



	public function __construct(IDibiReflector $driver, $name)
	{
		$this->driver = $driver;
		$this->name = $name;
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}



	/**
	 * @return array of DibiTableInfo
	 */
	public function getTables()
	{
		$this->init();
		return array_values($this->tables);
	}



	/**
	 * @return array of string
	 */
	public function getTableNames()
	{
		$this->init();
		$res = array();
		foreach ($this->tables as $table) {
			$res[] = $table->getName();
		}
		return $res;
	}



	/**
	 * @param  string
	 * @return DibiTableInfo
	 */
	public function getTable($name)
	{
		$name = DibiTranslator::substitute($name);
		$this->init();
		$l = strtolower($name);
		if (isset($this->tables[$l])) {
			return $this->tables[$l];

		} else {
			throw new DibiException("Database '$this->name' has no table '$name'.");
		}
	}



	/**
	 * @param  string
	 * @return bool
	 */
	public function hasTable($name)
	{
		$name = DibiTranslator::substitute($name);
		$this->init();
		return isset($this->tables[strtolower($name)]);
	}



	/**
	 * @return void
	 */
	protected function init()
	{
		if ($this->tables === NULL) {
			$this->tables = array();
			foreach ($this->driver->getTables() as $info) {
				$this->tables[strtolower($info['name'])] = new DibiTableInfo($this->driver, $info);
			}
		}
	}

}



/**
 * Reflection metadata class for a database table.
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @package    dibi
 *
 * @property-read string $name
 * @property-read bool $view
 * @property-read array $columns
 * @property-read array $columnNames
 * @property-read array $foreignKeys
 * @property-read array $indexes
 * @property-read DibiIndexInfo $primaryKey
 */
class DibiTableInfo extends DibiObject
{
	/** @var IDibiReflector */
	private $driver;

	/** @var string */
	private $name;

	/** @var bool */
	private $view;

	/** @var array */
	private $columns;

	/** @var array */
	private $foreignKeys;

	/** @var array */
	private $indexes;

	/** @var DibiIndexInfo */
	private $primaryKey;



	public function __construct(IDibiReflector $driver, array $info)
	{
		$this->driver = $driver;
		$this->name = $info['name'];
		$this->view = !empty($info['view']);
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}



	/**
	 * @return bool
	 */
	public function isView()
	{
		return $this->view;
	}



	/**
	 * @return array of DibiColumnInfo
	 */
	public function getColumns()
	{
		$this->initColumns();
		return array_values($this->columns);
	}



	/**
	 * @return array of string
	 */
	public function getColumnNames()
	{
		$this->initColumns();
		$res = array();
		foreach ($this->columns as $column) {
			$res[] = $column->getName();
		}
		return $res;
	}



	/**
	 * @param  string
	 * @return DibiColumnInfo
	 */
	public function getColumn($name)
	{
		$name = DibiTranslator::substitute($name);
		$this->initColumns();
		$l = strtolower($name);
		if (isset($this->columns[$l])) {
			return $this->columns[$l];

		} else {
			throw new DibiException("Table '$this->name' has no column '$name'.");
		}
	}



	/**
	 * @param  string
	 * @return bool
	 */
	public function hasColumn($name)
	{
		$name = DibiTranslator::substitute($name);
		$this->initColumns();
		return isset($this->columns[strtolower($name)]);
	}



	/**
	 * @return array of DibiForeignKeyInfo
	 */
	public function getForeignKeys()
	{
		$this->initForeignKeys();
		return $this->foreignKeys;
	}



	/**
	 * @return array of DibiIndexInfo
	 */
	public function getIndexes()
	{
		$this->initIndexes();
		return $this->indexes;
	}



	/**
	 * @return DibiIndexInfo
	 */
	public function getPrimaryKey()
	{
		$this->initIndexes();
		return $this->primaryKey;
	}



	/**
	 * @return void
	 */
	protected function initColumns()
	{
		if ($this->columns === NULL) {
			$this->columns = array();
			foreach ($this->driver->getColumns($this->name) as $info) {
				$this->columns[strtolower($info['name'])] = new DibiColumnInfo($this->driver, $info);
			}
		}
	}



	/**
	 * @return void
	 */
	protected function initIndexes()
	{
		if ($this->indexes === NULL) {
			$this->initColumns();
			$this->indexes = array();
			foreach ($this->driver->getIndexes($this->name) as $info) {
				foreach ($info['columns'] as $key => $name) {
					$info['columns'][$key] = $this->columns[strtolower($name)];
				}
				$this->indexes[strtolower($info['name'])] = new DibiIndexInfo($info);
				if (!empty($info['primary'])) {
					$this->primaryKey = $this->indexes[strtolower($info['name'])];
				}
			}
		}
	}



	/**
	 * @return void
	 */
	protected function initForeignKeys()
	{
		throw new NotImplementedException;
	}

}



/**
 * Reflection metadata class for a result set.
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @package    dibi
 *
 * @property-read array $columns
 * @property-read array $columnNames
 */
class DibiResultInfo extends DibiObject
{
	/** @var IDibiReflector */
	private $driver;

	/** @var array */
	private $columns;

	/** @var array */
	private $names;



	public function __construct(IDibiReflector $driver)
	{
		$this->driver = $driver;
	}



	/**
	 * @return array of DibiColumnInfo
	 */
	public function getColumns()
	{
		$this->initColumns();
		return array_values($this->columns);
	}



	/**
	 * @param  bool
	 * @return array of string
	 */
	public function getColumnNames($fullNames = FALSE)
	{
		$this->initColumns();
		$res = array();
		foreach ($this->columns as $column) {
			$res[] = $fullNames ? $column->getFullName() : $column->getName();
		}
		return $res;
	}



	/**
	 * @param  string
	 * @return DibiColumnInfo
	 */
	public function getColumn($name)
	{
		$name = DibiTranslator::substitute($name);
		$this->initColumns();
		$l = strtolower($name);
		if (isset($this->names[$l])) {
			return $this->names[$l];

		} else {
			throw new DibiException("Result set has no column '$name'.");
		}
	}



	/**
	 * @param  string
	 * @return bool
	 */
	public function hasColumn($name)
	{
		$name = DibiTranslator::substitute($name);
		$this->initColumns();
		return isset($this->names[strtolower($name)]);
	}



	/**
	 * @return void
	 */
	protected function initColumns()
	{
		if ($this->columns === NULL) {
			$this->columns = array();
			foreach ($this->driver->getColumnsMeta() as $info) {
				$this->columns[] = $this->names[$info['name']] = new DibiColumnInfo($this->driver, $info);
			}
		}
	}

}



/**
 * Reflection metadata class for a table or result set column.
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @package    dibi
 *
 * @property-read string $name
 * @property-read string $fullName
 * @property-read DibiTableInfo $table
 * @property-read string $type
 * @property-read mixed $nativeType
 * @property-read int $size
 * @property-read bool $unsigned
 * @property-read bool $nullable
 * @property-read bool $autoIncrement
 * @property-read mixed $default
 */
class DibiColumnInfo extends DibiObject
{
	/** @var array */
	private static $types;

	/** @var IDibiReflector */
	private $driver;

	/** @var array (name, nativetype, [table], [fullname], [size], [nullable], [default], [autoincrement], [vendor]) */
	private $info;

	/** @var string */
	private $type;



	public function __construct(IDibiReflector $driver, array $info)
	{
		$this->driver = $driver;
		$this->info = $info;
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->info['name'];
	}



	/**
	 * @return string
	 */
	public function getFullName()
	{
		return $this->info['fullname'];
	}



	/**
	 * @return bool
	 */
	public function hasTable()
	{
		return !empty($this->info['table']);
	}



	/**
	 * @return DibiTableInfo
	 */
	public function getTable()
	{
		if (empty($this->info['table'])) {
			throw new DibiException("Table name is unknown.");
		}
		return new DibiTableInfo($this->driver, array('name' => $this->info['table']));
	}



	/**
	 * @return string
	 */
	public function getType()
	{
		if ($this->type === NULL) {
			$this->type = self::detectType($this->info['nativetype']);
		}
		return $this->type;
	}



	/**
	 * @return mixed
	 */
	public function getNativeType()
	{
		return $this->info['nativetype'];
	}



	/**
	 * @return int
	 */
	public function getSize()
	{
		return isset($this->info['size']) ? (int) $this->info['size'] : NULL;
	}



	/**
	 * @return bool
	 */
	public function isUnsigned()
	{
		return isset($this->info['unsigned']) ? (bool) $this->info['unsigned'] : NULL;
	}



	/**
	 * @return bool
	 */
	public function isNullable()
	{
		return isset($this->info['nullable']) ? (bool) $this->info['nullable'] : NULL;
	}



	/**
	 * @return bool
	 */
	public function isAutoIncrement()
	{
		return isset($this->info['autoincrement']) ? (bool) $this->info['autoincrement'] : NULL;
	}



	/**
	 * @return mixed
	 */
	public function getDefault()
	{
		return isset($this->info['default']) ? $this->info['default'] : NULL;
	}



	/**
	 * @param  string
	 * @return mixed
	 */
	public function getVendorInfo($key)
	{
		return isset($this->info['vendor'][$key]) ? $this->info['vendor'][$key] : NULL;
	}



	/**
	 * Heuristic type detection.
	 * @param  string
	 * @return string
	 */
	private static function detectType($type)
	{
		static $patterns = array(
			'BYTEA|BLOB|BIN' => dibi::BINARY,
			'TEXT|CHAR' => dibi::TEXT,
			'BYTE|COUNTER|SERIAL|INT|LONG' => dibi::INTEGER,
			'CURRENCY|REAL|MONEY|FLOAT|DOUBLE|DECIMAL|NUMERIC|NUMBER' => dibi::FLOAT,
			'^TIME$' => dibi::TIME,
			'TIME' => dibi::DATETIME, // DATETIME, TIMESTAMP
			'YEAR|DATE' => dibi::DATE,
			'BOOL|BIT' => dibi::BOOL,
		);

		if (!isset(self::$types[$type])) {
			self::$types[$type] = dibi::TEXT;
			foreach ($patterns as $s => $val) {
				if (preg_match("#$s#i", $type)) {
					return self::$types[$type] = $val;
				}
			}
		}
		return self::$types[$type];
	}

}



/**
 * Reflection metadata class for a foreign key.
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @package    dibi
 * @todo
 *
 * @property-read string $name
 * @property-read array $references
 */
class DibiForeignKeyInfo extends DibiObject
{
	/** @var string */
	private $name;

	/** @var array of array(local, foreign, onDelete, onUpdate) */
	private $references;



	public function __construct($name, array $references)
	{
		$this->name = $name;
		$this->references = $references;
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}



	/**
	 * @return array
	 */
	public function getReferences()
	{
		return $this->references;
	}

}



/**
 * Reflection metadata class for a index or primary key.
 *
 * @copyright  Copyright (c) 2005, 2010 David Grudl
 * @package    dibi
 *
 * @property-read string $name
 * @property-read array $columns
 * @property-read bool $unique
 * @property-read bool $primary
 */
class DibiIndexInfo extends DibiObject
{
	/** @var array (name, columns, [unique], [primary]) */
	private $info;


	public function __construct(array $info)
	{
		$this->info = $info;
	}



	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->info['name'];
	}



	/**
	 * @return array
	 */
	public function getColumns()
	{
		return $this->info['columns'];
	}



	/**
	 * @return bool
	 */
	public function isUnique()
	{
		return !empty($this->info['unique']);
	}



	/**
	 * @return bool
	 */
	public function isPrimary()
	{
		return !empty($this->info['primary']);
	}

}
