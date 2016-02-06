<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-05-23 11:48:42
/*
/* ************************************************************************** */
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-03-10 08:00:28
/*	Updated: UTC 2015-05-23 11:48:42
/*
/* ************************************************************************** */
namespace Loli\Database;
use IteratorAggregate;

class Cursor implements IteratorAggregate{

	// 数据库对象
	protected $database;

	// class name
	protected $class = Document::class;

	// 是否要执行 false = 数据语句信息
	protected $execute = true;

	// 是否用从据库
	protected $readonly;

	// indexs 索引 重命名 键信息的
	protected $indexs = [];

	// 列创建用
	protected $columns = [];

	// 表名
	protected $tables = [];

	// 表头
	protected $fields = [];

	// 查询语句
	protected $querys = [];

	// 插入 or 写入 or 更新 文档
	protected $documents = [];

	// 选项
	protected $options = [];

	// 查询 unions
	protected $unions = [];

	// 缓存时间
	protected $cache = [0, 0, true];

	// 自动递增id
	protected $insertId;

	// 主键
	protected $primary = [];

	// 主键缓存有效期
	protected $primaryCache = 0;

	// 构造器对象
	protected $builder = false;


	protected $current = 0;


	protected $increment = 0;


	// 构造器对象
	protected static $buildersClass = [
		'mongo' => 'Mongo',
		'mongodb' => 'Mongo',
		'redis' => 'Redis',
	];


	/**
	 * args 取得参数
	 * @param  string $name
	 * @return mixed
	 */
	public function &reference($name) {
		return $this->$name;
	}

	public function database(Base $database) {
		$this->builder = false;
		$this->database = $database;
		return $this;
	}

	public function className($name) {
		Base::className($name);
		$this->class = $name;
		return $this;
	}


	public function insertId($insertId) {
		$this->insertId = $insertId;
		return $this;
	}


	/**
	 * readonly 主从设置
	 * @param  boolean|null $readonly
	 * @return this
	 */
	public function readonly($readonly) {
		$this->readonly = $readonly;
		return $this;
	}


	/**
	 * execute 是否返回的是执行
	 * @param  boolean $execute
	 * @return this
	 */
	public function execute($execute) {
		$this->execute = $execute;
		return $this;
	}



	/**
	 * cache 设置缓存
	 * @param  integer  $ttl
	 * @param  integer $refresh
	 * @return this
	 */
	public function cache($ttl = 0, $refresh = 0, $empty = true) {
		$this->cache = [$ttl, $refresh, $empty];
		return $this;
	}

	/**
	 * indexs 设置索引
	 * @param  array  $indexs
	 * @return this
	 */
	public function indexs(array $indexs) {
		++$this->increment;
		$this->indexs = array_merge($this->indexs, $indexs);
		return $this;
	}

	/**
	 * index
	 * @param  string $column
	 * @param  array|string|null $value
	 * @return this
	 */
	public function index($column, $value) {
		++$this->increment;
		$this->indexs[$column] = $value;
		return $this;
	}


	/**
	 * tables 选择表多个表
	 * @param  array  $tables 选择的表 数组
	 * @return $this
	 */
	public function tables(array $tables) {
		++$this->increment;
		$this->tables = array_merge($this->tables, $tables);
		return $this;
	}


	/**
	 * table 选择表
	 * @param  string              $table  表名
	 * @param  string|null         $alias  表别名
	 * @param  string|null         $join   join 参数
	 * @param  array|string|null   $on
	 * @param  array               $data 附加参数
	 * @return this
	 */
	public function table($table, $alias = NULL, $join = NULL, $on = NULL, array $data = []) {
		++$this->increment;
		if ($table instanceof Param) {
			$alias === NULL || $table->__set('alias', $alias);
			$join === NULL || $table->__set('join', $join);
			$on === NULL || $table->__set('on', $on);
			$table->merge($data);
			$this->tables[] = $table;
		} else {
			$this->tables[] = new Param($data + ['value'=> $table, 'alias'=> $alias, 'join' => $join, 'on' => $on]);
		}
		return $this;
	}


	/**
	 * columns 字段
	 * @param  array  $columns
	 * @return this
	 */
	public function columns(array $columns) {
		++$this->increment;
		$this->columns = array_merge($this->columns, $columns);
		return $this;
	}

	/**
	 * column  添加字段
	 * @param  string             $name   字段名
	 * @param  string|null        $type   字段类型
	 * @param  array|integer|null $length 字段长度
	 * @param  array              $data 附加参数
	 * @return this
	 */
	public function column($name, $type = NULL, $length = NULL, array $data = []) {
		++$this->increment;
		if ($name instanceof Param) {
			$type === NULL || $name->__set('type', $type);
			$length === NULL || $name->__set('length', $length);
			$name->merge($data);
			$this->columns[] = $name;
		} else {
			$this->columns[] = new Param($data + ['name' => $name, 'type' => $type, 'length' => $length]);
		}
		return $this;
	}

	/**
	 * fields 选择字段多个
	 * @param  array  $fields 字段数组
	 * @return this
	 */
	public function fields(array $fields) {
		++$this->increment;
		$this->fields = array_merge($this->fields, $fields);
		return $this;
	}

	/**
	 * field 选择单个字段
	 * @param  string      $field    字段名
	 * @param  string|null $alias    别名 重命名
	 * @param  string|null $function 字段函数
	 * @param  array       $data   附加参数
	 * @return this
	 */
	public function field($field, $alias = NULL, $function = NULL, array $data = []) {
		++$this->increment;
		if ($field instanceof Param) {
			$alias === NULL || $field->__set('alias', $alias);
			$function === NULL || $field->__set('function', $function);
			$field->merge($data);
			$this->fields[] = $field;
		} else {
			$this->fields[] = new Param($data + ['value'=> $field, 'function' => $function, 'alias'=> $alias]);
		}
		return $this;
	}

	/**
	 * querys 选择查询
	 * @param  array  $querys 写入的查询数组
	 * @return this
	 */
	public function querys(array $querys) {
		++$this->increment;
		$this->querys = array_merge($this->querys, $querys);
		return $this;
	}

	/**
	 * query 添加查询 信息
	 * @param  string      $column  字段
	 * @param  *           $value   值
	 * @param  string|null $compare 运算符
	 * @param  string|null $function 函数
	 * @param  array       $data  附加变量
	 * @return this
	 */
	public function query($column, $value = NULL, $compare = NULL, $function = NULL, array $data = []) {
		++$this->increment;
		if ($column instanceof Param) {
			$value === NULL || $column->__set('value', $value);
			$compare === NULL || $column->__set('compare', $compare);
			$function === NULL || $column->__set('function', $function);
			$column->merge($data);
			$this->querys[] = $column;
		} else {
			$this->querys[] = new Param($data + ['column' => $column, 'function' => $function, 'column'=> $column, 'value'=> $value, 'compare' => $compare]);
		}
		return $this;
	}



	/**
	 * values 插入
	 * @param  array   $values     插入的数组
	 * @return this
	 */
	public function values($values) {
		if ($values instanceof Param) {
			throw new Exception('cursor.values', 'Class name can not be (Param)');
		}
		if (!$this->documents) {
			$this->documents[] = new $this->class;
		}
		++$this->increment;
		end($this->documents)->write($values);
		return $this;
	}



	/**
	 * value 插入
	 * @param  string  $name       插入的名称
	 * @param  *       $value      插入的值
	 * @return this
	 */
	public function value($name, $value) {
		if (!$this->documents) {
			$this->documents[] = new $this->class;
		}
		++$this->increment;
		end($this->documents)->__set($name, $value);
		return $this;
	}


	/**
	 * documents 写入的文档多个
	 * @param  array  $documents 二维数组
	 * @return this
	 */
	public function documents($documents) {
		if (is_array($documents) || $documents instanceof Results) {
			foreach ($documents as $document) {
				$this->document($document);
			}
		}
		return $this;
	}



	/**
	 * document 写入的文档
	 * @param  array  $document 数组
	 * @return $this
	 */
	public function document($document) {
		if ($document instanceof Param) {
			throw new Exception('cursor.document', 'Class name can not be (Param)');
		}
		++$this->increment;
		$this->documents[] = $document instanceof $this->class ? $document : new $this->class($document);
		return $this;
	}


	/**
	 * 添加多个选项
	 * @param  array  $options 选项数组 选项名 => 选项值
	 * @return $this
	 */
	public function options(array $options) {
		++$this->increment;
		$this->options = array_merge($this->options, $options);
		return $this;
	}

	/**
	 * 添加单个选项
	 * @param  string $name  选项名称
	 * @param  *      $value   值
	 * @param  array  $data  附加变量
	 * @return this
	 */
	public function option($name, $value = NULL, array $data = []) {
		++$this->increment;
		if ($name instanceof Param) {
			$value === NULL || $name->__set('value', $value);
			$name->merge($data);
			$this->options[] = $name;
		} else {
			$this->options[] = new Param($data + ['name' => $name, 'value' => $value]);
		}
		return $this;
	}



	/**
	 * 分组选项
	 * @param  array|string $columns  字段
	 * @return this
	 */
	public function group($columns, $function = NULL) {
		++$this->increment;
		if ($columns instanceof Param) {
			$columns = [$columns];
		} else {
			$columns = (array) $columns;
		}
		foreach ($columns as $value) {
			if ($value instanceof Param) {
				$value->__set('name', 'group');
				$function === NULL || $value->__set('function', $function);
				$this->options[] = $value;
			} else {
				$this->options[] = new Param(['name'=> 'group', 'value'=> $value, 'function' => $function]);
			}
		}
		return $this;
	}


	/**
	 * order
	 * @param  array|string $columns
	 * @param  integer|null $order
	 * @return this
	 */
	public function order($columns, $order = NULL, $function = NULL) {
		++$this->increment;
		if ($columns instanceof Param) {
			$columns = [$columns];
		} elseif ($order !== NULL) {
			$tmp = [];
			foreach ((array)$columns as $column) {
				$tmp[$column] = $order;
			}
			$columns = $tmp;
		} else {
			$columns = (array) $columns;
		}
		foreach ($columns as $column => $value) {
			if ($value instanceof Param) {
				$value->__set('name', 'order');
				$order === NULL || $value->__set('order', $order);
				$function === NULL || $value->__set('function', $function);
				$this->options[] = $value;
			} else {
				$this->options[] = new Param(['name'=> 'order', 'value'=> $value, 'column' => $column, 'function' => $function]);
			}
		}
		return $this;
	}

	/**
	 * offset
	 * @param  integer $offset 偏移位置
	 * @return this
	 */
	public function offset($offset) {
		return $this->option('offset', $offset);
	}

	/**
	 * limit
	 * @param  integer $limit 限制数量
	 * @return this
	 */
	public function limit($limit) {
		return $this->option('limit', $limit);
	}


	/**
	 * unions
	 * @param  array   $unions
	 * @param  boolean $all
	 * @return this
	 */
	public function unions(array $unions, $all = false) {
		++$this->increment;
		foreach ($unions as $union) {
			if ($union instanceof Param || !$all) {
				$all && $union->__set('all', $all);
				$this->unions[] = $union;
				continue;
			}
			$this->unions[] = new Param(['value' => $union, 'all' => $all]);
		}
		return $this;
	}

	/**
	 * union
	 * @param  Param|Cursor   $union
	 * @param  boolean $all
	 * @return this
	 */
	public function union($union, $all = false) {
		++$this->increment;
		if ($union instanceof Param || !$all) {
			$all && $union->__set('all', $all);
			$this->unions[] = $union;
		} else {
			$this->unions[] = new Param(['value' => $union, 'all' => $all]);
		}
		return $this;
	}


	public function primary(array $primary, $primaryCache = 0) {
		$this->primary = $primary;
		$this->primaryCache = $primaryCache;
		return $this;
	}



	public function getIterator() {
        return $this->select();
    }

	/**
	 * __invoke
	 * @param  string $name
	 * @param  array  $args
	 * @return array|integer|boolean|object
	 */
	public function __invoke($name, $args) {
		return $this->__call('select', $args);
	}

	public function __call($name, array $args) {

		// 无数据库信息
		if (!$this->database) {
			throw new Exception('cursor.database', 'No database objects');
		}

		// 构造器
		if (!$this->builder) {
			$builderClass = __NAMESPACE__ .'\\'. (isset(self::$buildersClass[$this->database->protocol()]) ? self::$buildersClass[$this->database->protocol()] : 'SQLBuilder');
			$this->builder = new $builderClass($this);
			$this->current = $this->increment;
		} elseif ($this->increment !== $this->current) {
			$this->builder->clear();
			$this->current = $this->increment;
		}


		// 构造器无方法
		if (!method_exists($this->builder, $name)) {
			$result = $this->database->$name(...$args);
			if ($result === $this->database) {
				return $this;
			}
			return $result;
		}

		// 主键设定
		$lowerName = strtolower($name);
		if ($args && in_array($lowerName, ['update', 'delete', 'select', 'selectrow', 'count', 'insert'], true)) {
			if (count($args) !== count($this->primary)) {
				throw new Exception('cursor.' . $name, 'The primary key is not the same number of parameters');
			}
			$i = 0;
			foreach($this->primary as $primary) {
				$this->query($primary, $args[$i], '=');
				++$i;
			}
			$cache = $this->cache;
			$this->offset(0)->limit(1)->cache($this->primaryCache, 0, false);
			$result = $this->builder->$name(...$args);
		} else {
			$result = $this->builder->$name();
		}


		if (in_array($lowerName, ['update', 'delete'])) {
			$this->builder->deleteCacheSelectRow();
			$this->builder->deleteCacheSelect();
			$this->builder->deleteCacheCount();
		}
		if (!empty($cache)) {
			$this->cache = $cache;
		}
		return $result;
	}
}