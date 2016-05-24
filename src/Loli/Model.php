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

	// 主键缓存有效期
	protected static $primaryCache = 0;

	// 模块组
	protected static $group = 'model';

	// 模块验证表格
	protected static $rules = [];

	protected $_sets = [];

	public static function __callStatic($name, $args) {
		return static::database()->$name(...$args);
	}

	public static function database() {
		return Database::__callStatic(static::$group, [])->tables(static::$tables ? static::$tables : (array)static::$table)->className(static::class)->indexs(static::$indexs)->columns(static::$columns)->insertId(static::columnInsertId())->primary(static::columnPrimary(), static::$primaryCache);
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


	protected static function methods() {
        static $static;
        if (isset($static[static::class])) {
            return $static[static::class];
        }
        $methods = [];
        foreach (get_class_methods(static::class) as $method) {
            if (substr($method, 0, 12) === 'getAttribute') {
                $methods[$name] =  true;
            }
        }
        return $static[static::class] = $methods;
    }

	public static function columnInsertId() {
		static $static = [];
		if (!isset($static[static::class])) {
			$insertId = false;
			foreach (static::$columns as $name => $value) {
				if (!empty($value['increment'])) {
					$insertId = empty($value['name']) ? $name : $value['name'];
					break;
				}
			}
			$static[static::class] = $insertId;
		}
		return $static[static::class];
	}


	public static function columnPrimary() {
		static $static = [];
		if (!isset($static[static::class])) {
			$columns = [];
			foreach (static::$columns as $name => $value) {
				if (!isset($value['primary'])) {
					continue;
				}
				if (!empty($value['name'])) {
					$name = $value['name'];
				}
				$columns[$name] = $value['primary'];
			}
			if ($columns) {
				asort($columns, SORT_NUMERIC);
				$columns = array_keys($columns);
			}
			$static[static::class] = $columns;
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
            return false;
		}
		return $static[static::class][$name];
	}


	protected static function parsedType($name, $value) {
        if ($value === null || !($info = static::columnInfo($name))) {
            return $value;
        }
		switch ($info->type) {
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
			case 'object':
				if (is_object($value)) {

				} elseif (is_array($value)) {
					$value = (object) $value;
				} elseif ($value) {
					if ($value = @unserialize($value)) {
						$value = (object) $value;
					} else {
						$value = new \stdClass;
					}
				} else {
					$value = new \stdClass;
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
		return $value;
	}



	protected  function defaultValues() {
		$values = [];
		foreach (static::$columns as $key => $value) {
			if (!empty($value['null'])) {
				continue;
			}
			if (!empty($value['name'])) {
				$key = $value['name'];
			}
			if (isset($value['value'])) {
				$values[$key] = $value['value'];
				continue;
			}
			switch ($value['type']) {
				case 'timestamp':
				case 'datetime':
					$value = new DateTime('now');
					break;
				case 'json':
				case 'array':
					$value = [];
					break;
				case 'object':
					$value = new \stdClass;
					break;
				case 'boolean':
					$value = false;
					break;
				case 'boolean':
					$value = false;
					break;
				case 'tinyint':
				case 'smallint':
				case 'mediumint':
				case 'int':
				case 'integer':
				case 'bigint':
					$value = 0;
					break;
				case 'float':
				case 'real':
				case 'double':
				case 'decimal':
					$value = 0.00;
					break;
				default:
					$value = '';
			}
			$values[$key] = $value;
		}
		return $values;
	}



	protected function primaryParams() {
		$params = [];
		foreach (static::columnPrimary() as $name) {
			$value = $this[$name];
			if ($value === null) {
				throw new QueryException(static::class .'::primaryParams()', $this);
			}
			$params[] = $value;
		}

		if (!$params) {
			throw new QueryException(static::class . '::primaryParams()', 'Primary key cannot be empty');
		}

		return $params;
	}

    public function __construct(...$args) {
        parent::__construct(...$args);
    }



    public function __get($name) {
        $value = parent::__get($name);
        if (method_exists($this,  $method = 'getAttribute' . studly($name))) {
            $value = $this->$method($value);
        }
        return $value;
    }

	public function __set($name, $value) {
		if ($name === null || !$name) {
			throw new QueryException('Model.__set(null)', 'The column name cannot be null');
		}

		if ($name{0} === '_') {
			return;
		}

        $value = static::parsedType($name, $value);
        if ($value === $this->__get($name)) {
            return;
        }

        if (method_exists($this,  $method = 'setAttribute' . studly($name))) {
            $value = $this->$method($value);
        }

        if ($this->_sets !== null) {
            $this->_sets[] = $name;
        }
		return parent::__set($name, $value);
	}

	public function select() {
		if (!$select = static::database()->selectOne(...$this->primaryParams())) {
			throw new QueryException('Model.select()', $this);
		}
        $this->_sets = null;
		$this->clear()->merge($select);
        $this->_sets = [];
		return $this;
	}

	public function insert() {
		$cursor = static::database();
        $data = [];
        foreach ($this as $name => $value) {
            if (!static::columnInfo($name)) {
                throw new QueryException(static::class . '::update ()', 'Unknown column('. $name .')');
            }
            $data[$name] = $value;
        }
        $data += $this->defaultValues();
		if (!$cursor->values($data)->insert()) {
			throw new QueryException('Model.insert()', $this);
		}

		if ($insertId = static::columnInsertId()) {
			$this[$insertId] = $cursor->lastInsertId();
		}
		$this->select();
		return $this;
	}

	public function update() {
		$update = [];
		foreach ($this->_sets as $name) {
            if (!$info = static::columnInfo($name)) {
                throw new QueryException(static::class . '::update ()', 'Unknown column('. $name .')');
            }
            if (static::columnInfo($name)->readonly || in_array($name, static::columnPrimary(), true)) {
                continue;
            }
            $update[$name] = static::parsedType($name, $this[$name]);
		}
        if (!$update) {
            throw new QueryException(static::class . '::update ()', 'Update data is empty');
        }
		static::database()->values($update)->update(...$this->primaryParams());
		$this->select();
		return $this;
	}

	public function delete() {
		static::database()->delete(...$this->primaryParams());
		$this->clear();
        $this->_sets = [];
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

    public function toArray() {
        $this->jsonSerialize();
    }

	public function jsonSerialize() {
		$data = parent::jsonSerialize();
		foreach ($data as $key => $value) {
			if (($info = static::columnInfo($key)) && $info->hidden) {
				unset($data[$key]);
			}
		}
        foreach (static::methods() as $key => $value) {
            $data[$key] = $this->$key;
        }
		return $data;
	}
}
