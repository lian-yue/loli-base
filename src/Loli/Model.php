<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-02-02 07:00:30
/*
/* ************************************************************************** */
namespace Loli;
use Loli\Database\Param;
use Loli\Database\Document;
use Loli\Database\QueryException;
class Model extends Document{

	// indexs 索引 重命名 键信息的
	protected static $indexs = [];

	// 列创建用
	protected static $columns = [];

	// 表名
	protected static $tables = [];


	protected static $table;

	// 自动递增id
	protected static $insertId;

	// 主键
	protected static $primary = [];

	// 主键缓存有效期
	protected static $primaryCache = 0;

	// 模块组
	protected static $group = 'model';

	// 模块验证表格
	protected static $rules = [];

	public static function __callStatic($name, $args) {
		return static::database()->$name(...$args);
	}

	public static function database() {
		return Database::__callStatic(static::$group, [])->tables(static::$tables ? static::$tables : (array)static::$table)->className(static::class)->indexs(static::$indexs)->columns(static::$columns)->insertId(static::$insertId)->primary(static::$primary, static::$primaryCache);
	}



	public static function validator($data = [], array $rules = [], $merge = false, $message = null) {
		static $static = [];
		if (!isset($static[static::class])) {
			$static[static::class] = (new Validator(static::$rules, static::$group === 'default' ? static::$group : [static::$group, 'default']))->model(static::class);
		}
		if (func_num_args()) {
			return $static[static::class]->make($data, $rules, $merge, $message);
		}
		return $static[static::class];
	}





	protected static function columnInfo($name) {
		static $static = [];
		if (!isset($static[static::class])) {
			$columns = [];
			foreach (static::$columns as $key => $value) {
				if (!empty($value['name'])) {
					$key = $value['name'];
				}
				$columns[$key] = (object) ['readonly' => !empty($value['readonly']), 'process' => !isset($value['process']) || $value['process'], 'hidden' => !empty($value['hidden']), 'type' => empty($value['type']) ? 'text' : $value['type']];
			}
			$static[static::class] = $columns;
		}
		if (empty($static[static::class][$name])) {
			throw new QueryException(static::class . '::columnInfo('. $name .')', 'Unknown column');
		}
		return $static[static::class][$name];
	}



	protected function primary() {
		if (!static::$primary) {
			throw new QueryException('Model.primary()', 'Primary key cannot be empty');
		}
		$args = [];
		foreach (static::$primary as $primary) {
			$value = $this[$primary];
			if ($value === null) {
				throw new QueryException('Model.primary()', $this);
			}
			$args[] = $value;
		}
		return $args;
	}

	public function __set($name, $value) {
		if ($name === null) {
			throw new QueryException('Model.__set(null)', 'The column name cannot be null');
		}
		if ($value !== null && !$value instanceof Param && static::columnInfo($name)->process) {
			switch (static::columnInfo($name)->type) {
				case 'timestamp':
				case 'datetime':
					if (!$value instanceof DateTime) {
						$value = new DateTime($value);
					}
					break;
				case 'json':
				case 'array':
					if (is_array($value)) {

					} elseif (is_object($value)) {
						$value = to_array($value);
					} elseif ($value) {
						$value = json_decode($value, true);
					} else {
						$value = [];
					}
					break;
				case 'boolean':
					$value = (boolean) $value;
					break;
				case 'tinyint':
				case 'smallint':
				case 'mediumint':
				case 'int':
				case 'integer':
				case 'bigint':
					$value = (int) $value;
					break;
				case 'float':
				case 'real':
				case 'double':
				case 'decimal':
					$value = (float) $value;
			}
		}
		return parent::__set($name, $value);
	}

	public function select() {
		if (!$select = static::database()->selectRow(...$this->primary())) {
			throw new QueryException('Model.select()', $this);
		}
		$this->clear()->merge($select);
		return $this;
	}

	public function insert() {
		$cursor = static::database();
		if (!$cursor->values($this->toArray())->insert()) {
			throw new QueryException('Model.insert()', $this);
		}

		if (static::$insertId) {
			$this[static::$insertId] = $cursor->lastInsertId();
		}
		$this->select();
		return $this;
	}

	public function update() {
		$update = [];
		foreach ($this as $key => $value) {
			if (!static::columnInfo($key)->readonly && !in_array($key, static::$primary, true)) {
				$update[$key] = $value;
			}
		}
		static::database()->values($update)->update(...$this->primary());
		$this->select();
		return $this;
	}

	public function delete() {
		static::database()->delete(...$this->primary());
		$this->clear();
		return $this;
	}


	public function can($name, ...$args) {
		return true;
	}

	public function cant(...$args) {
		return !$this->can(...$args);
	}

	public function throwCan(...$args) {
		if (!$this->cant(...$args)) {
			throw new Message(['message' => 'permission_denied', 'code' => 'Permission'], 403);
		}
	}

	public function jsonSerialize() {
		$data = parent::jsonSerialize();
		foreach ($data as $key => $value) {
			if (static::columnInfo($key)->hidden) {
				unset($data[$key]);
			}
		}
		return $data;
	}
}
