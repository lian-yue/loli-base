<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-03-10 08:00:28
/*	Updated: UTC 2015-04-11 01:41:40
/*
/* ************************************************************************** */
namespace Loli\DB;
abstract class Cursor{

	const ASC = 1;

	const DESC = -1;

	// 数据库对象
	protected $DB;

	// 链接的协议
	protected $protocol;

	// 链接的数据库 or 链接 的 ID
	protected $database;

	// 是否要执行 false = 数据语句信息
	protected $execute = true;

	// 数据回调
	protected $callback;

	// 是否用从据库
	protected $slave;

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

	// 创建 or 插入 or 写入 or 更新 value
	protected $values = [];

	// 插入 or 写入 or 更新 文档
	protected $documents = [];

	// 选项
	protected $options = [];

	// 查询 unions
	protected $unions = [];

	// 缓存信息
	protected $data;

	// 缓存时间
	protected $cache = [0, 0];

	// 有修改表的日志  [database=>[表名=>执行时间戳]]
	private static $_logs = [];

	/**
	 * __construct
	 * @param Base   $DB
	 * @param array|string $tables
	 * @param array  $indexs 索引
	 */
	public function __construct(Base $DB, $tables = [], array $indexs = []) {
		$this->DB = $DB;
		$this->database = $DB->database();
		$this->protocol = $DB->protocol();
		$this->tables((array)$tables);
	}

	/**
	 * slave 主从设置
	 * @param  boolean|null $slave
	 * @return this
	 */
	public function slave($slave) {
		$this->slave = $slave;
		return $this;
	}

	/**
	 * getSlave  读取使用主从
	 * @return boolean
	 */
	protected function getSlave() {
		if ($this->slave === NULL) {
			$this->slave = $this->DB->slave;
		}
		if (!$this->slave) {
			return false;
		}
		if (!empty($this->_logs[$this->database]) && !empty($this->data['uses'])) {
			foreach ($this->_logs[$this->database] as $table => $time) {
				if (in_array($table, $this->data['uses']) && $time > time()) {
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * setSlave 设置主从
	 * @param  integer $ttl
	 * @return this
	 */
	protected function setSlave($ttl = 2) {
		if (empty($this->data['uses'])) {
			return $this;
		}
		foreach ($this->data['uses'] as $table) {
			$this->_logs[$this->database][$table] = time() + ($ttl < 2 ? 2 : $ttl);
		}
		return $this;
	}

	/**
	 * callback 回调对象
	 * @param  callback|null $callback
	 * @return this
	 */
	public function callback(callback $callback = NULL) {
		$this->callback = $callback;
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
	public function cache($ttl, $refresh = 0) {
		$this->cache = [$ttl, $refresh];
		return $this;
	}

	/**
	 * indexs 设置索引
	 * @param  array  $indexs
	 * @return this
	 */
	public function indexs(array $indexs) {
		$this->data = [];
		foreach ($indexs as $column => $value) {
			if ($value === false || $value === NULL) {
				unset($this->indexs[$column]);
				continue;
			}
			if (is_array($value) || is_object($value)) {
				$this->indexs[$column] = (array) $value;
				continue;
			}
			$this->indexs[$column] = ['compare' => $value];
		}
		return $this;
	}
	/**
	 * index
	 * @param  string $column
	 * @param  array|string|null $value
	 * @return this
	 */
	public function index($column, $value) {
		return $this->indexs([$column=>$value]);
	}

	/**
	 * tables 选择表多个表
	 * @param  array  $tables 选择的表 数组
	 * @return $this
	 */
	public function tables(array $tables) {
		$this->data = [];
		foreach ($tables as $alias => $table) {
			if ($table instanceof Param) {
				$this->tables[] = $table;
				continue;
			}
			if (is_array($table) && !empty($table['value'])) {
				$this->tables[] = new Param(['value' => $table['value'], 'alias' => isset($table['alias']) ? $table['alias'] : (is_int($alias) ? NULL : $alias), 'join' => empty($table['join']) ? NULL : $table['join'], 'on' => empty($table['on']) ? NULL : $table['on']]);
				continue;
			}
			if ($table) {
				$this->tables[] = new Param(['value' => $table, 'alias' => is_int($alias) ? NULL : $alias]);
				continue;
			}
		}
		return $this;
	}

	/**
	 * table 选择表
	 * @param  string              $table  表名
	 * @param  string|null         $alias  表别名
	 * @param  string|null         $join   join 参数
	 * @param  array|string|null   $on
	 * @param  array               $params 附加参数
	 * @return this
	 */
	public function table($table, $alias = NULL, $join = NULL, $on = NULL, array $params = []) {
		$this->data = [];
		if ($table instanceof Param) {
			$table->setParams($params);
			$this->tables[] = $table;
		} else {
			$this->tables[] = new Param($params + ['value'=> $table, 'alias'=> $alias, 'join' => $join, 'on' => $on]);
		}
		return $this;
	}

	/**
	 * columns 字段
	 * @param  array  $columns
	 * @return this
	 */
	public function columns(array $columns) {
		foreach ($columns as $name => $column) {
			if ($column instanceof Param) {
				$this->columns[] = $column;
			} else {
				$this->columns[] = new Param($column + ['name' => $name]);
			}
		}
		return $this;
	}

	/**
	 * column  添加字段
	 * @param  string             $name   字段名
	 * @param  string|null        $type   字段类型
	 * @param  array|integer|null $length 字段长度
	 * @param  array              $params 附加参数
	 * @return this
	 */
	public function column($name, $type = NULL, $length = NULL, array $params = []) {
		if ($name instanceof Param) {
			$this->columns[] = $name;
		} else {
			$this->columns[] = new Param($params + ['name' => $name, 'type' => $type, 'length' => $length]);
		}
		return $this;
	}

	/**
	 * fields 选择字段多个
	 * @param  array  $fields 字段数组
	 * @return this
	 */
	public function fields(array $fields) {
		$this->data = [];
		foreach ($fields as $alias => $field) {
			if ($field instanceof Param) {
				$this->fields[] = $field;
				continue;
			}

			if (is_array($field) && !empty($field['value'])) {
				$this->fields[] = new Param(['value' => $field['value'], 'alias' => isset($field['alias']) ? $field['alias'] : (is_int($alias) ? NULL : $alias)]);
				continue;
			}

			if ($field) {
				$this->fields[] = new Param(['value' => $field, 'alias' => is_int($alias) ? NULL : $alias]);
				continue;
			}
		}
		return $this;
	}

	/**
	 * field 选择单个字段
	 * @param  string      $field    字段名
	 * @param  string|null $alias    别名 重命名
	 * @param  string|null $function 字段函数
	 * @param  array       $params   附加参数
	 * @return this
	 */
	public function field($field, $alias = NULL, $function = NULL, array $params = []) {
		$this->data = [];
		if ($field instanceof Param) {
			$field->setParams($params);
			$this->fields[] = $field;
		} else {
			$this->fields[] = new Param($params + ['value'=> $field, 'function' => $function, 'alias'=> $alias]);
		}
		return $this;
	}

	/**
	 * querys 选择查询
	 * @param  array  $querys 写入的查询数组
	 * @return this
	 */
	public function querys(array $querys) {
		$this->data = [];
		foreach ($querys as $name => $value) {
			if ($value instanceof Param) {
				$this->querys[] = $value;
				continue;
			}
			$this->querys[] = new Param(['name' => $name, 'value' => $value]);
		}
		return $this;
	}
	/**
	 * query 添加查询 信息
	 * @param  string      $column  字段
	 * @param  *           $value   值
	 * @param  string|null $compare 运算符
	 * @param  string|null $function 函数
	 * @param  array       $params  附加变量
	 * @return this
	 */
	public function query($column, $value = NULL, $compare = NULL, $function = NULL, array $params = []) {
		$this->data = [];
		if ($column instanceof Param) {
			$column->setParams($params);
			$this->querys[] = $column;
		} else {
			$this->querys[] = new Param($params + ['function' => $function, 'column'=> $column, 'value'=> $value, 'compare' => $compare]);
		}
		return $this;
	}



	/**
	 * values 插入
	 * @param  array   $values     插入的数组
	 * @param  boolean $toDocument 是否写入成 文档
	 * @return this
	 */
	public function values(array $values, $toDocument = false) {
		$this->data = [];
		foreach ($values as $name => $value) {
			if ($value instanceof Param) {
				$this->values[] = $value;
				continue;
			}
			$this->values[] = new Param(['name' => $name, 'value' => $value]);
		}
		if ($toDocument) {
			$this->documents[] = $this->values;
			$this->values = [];
		}
		return $this;
	}

	/**
	 * value 插入
	 * @param  string  $name       插入的名称
	 * @param  *       $value      插入的值
	 * @param  array   $params     附加参数
	 * @param  boolean $toDocument 是否写 成文档
	 * @return this
	 */
	public function value($name, $value = NULL, array $params = [], $toDocument = false) {
		$this->data = [];
		if ($name instanceof Param) {
			$this->values[] = $name;
		} else {
			$this->values[] = new Param($params + ['name' => $name, 'value' => $value]);
		}
		if ($toDocument) {
			$this->documents[] = $this->values;
			$this->values = [];
		}
		return $this;
	}

	/**
	 * documents 写入的文档多个
	 * @param  array  $documents 二维数组
	 * @return this
	 */
	public function documents(array $document) {
		$this->data = [];
		foreach ($documents as $document) {
			$this->document($document);
		}
		return $this;
	}



	/**
	 * document 写入的文档
	 * @param  array  $document 数组
	 * @return $this
	 */
	public function document(array $document) {
		$this->data = [];
		foreach ($document as $name => &$value) {
			if (!$value instanceof Param) {
				$value = new Param(['name' => $name, 'value' => $value]);
			}
		}
		$this->documents[] = $document;
		return $this;
	}


	/**
	 * 添加多个选项
	 * @param  array  $options 选项数组 选项名 => 选项值
	 * @return $this
	 */
	public function options(array $options) {
		$this->data = [];
		foreach ($options as $name => $value) {
			if ($value instanceof Param) {
				$this->options[] = $value;
				continue;
			}
			$this->options[] = new Param(['name' => $name, 'value' => $value]);
		}
		return $this;
	}

	/**
	 * 添加单个选项
	 * @param  string $name  选项名称
	 * @param  *      $value   值
	 * @param  array  $params  附加变量
	 * @return this
	 */
	public function option($name, $value = NULL, array $params = []) {
		$this->data = [];
		if ($name instanceof Param) {
			$name->setParams($params);
			$this->options[] = $name;
		} else {
			$this->options[] = new Param($params + ['name'=> $name, 'value'=> $value]);
		}
		return $this;
	}

	/**
	 * 分组选项
	 * @param  array|string $columns  字段
	 * @return this
	 */
	public function group($columns) {
		$this->data = [];
		if ($columns instanceof Param) {
			$columns = [$columns];
		} else {
			$columns = (array) $columns;
		}
		foreach ($columns as $value) {
			if ($value instanceof Param) {
				$value->setParams(['name' => 'group']);
				$this->options[] = $value;
			} else {
				$this->options[] = new Param(['name'=> 'group', 'value'=> $value]);
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
	public function order($columns, $order = NULL) {
		$this->data = [];
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
				$value->setParams(['name' => 'order']);
				$this->options[] = $value;
			} else {
				$this->options[] = new Param(['name'=> 'order', 'value'=> $value, 'column' => $column]);
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
		$this->data = [];
		$this->options[] = new Param(['name'=> 'offset', 'value'=> $offset]);
		return $this;
	}

	/**
	 * limit
	 * @param  integer $limit 限制数量
	 * @return this
	 */
	public function limit($limit) {
		$this->data = [];
		$this->options[] = new Param(['name'=> 'limit', 'value'=> $limit]);
		return $this;
	}


	/**
	 * unions
	 * @param  array   $unions
	 * @param  boolean $all
	 * @return this
	 */
	public function unions(array $unions, $all = false) {
		$this->data = [];
		foreach ($unions as $union) {
			if ($union instanceof Param) {
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
		$this->data = [];
		if ($union instanceof Param) {
			$this->unions[] = $union;
		} else {
			$this->unions[] = new Param(['value' => $union, 'all' => $all]);
		}
		return $this;
	}


	/**
	 * exists 判断某个表是否存在
	 * @return boolean
	 */
	abstract public function exists();

	/**
	 * create
	 * @return boolean
	 */
	abstract public function create();

	/**
	 * truncate
	 * @return boolean
	 */
	abstract public function truncate();

	/**
	 * drop
	 * @return boolean
	 */
	abstract public function drop();

	/**
	 * insert
	 * @return integer|array
	 */
	abstract public function insert();


	/**
	 * update
	 * @return integer|array
	 */
	abstract public function update();


	/**
	 * delete
	 * @return integer
	 */
	abstract public function delete();

	/**
	 * select
	 * @return array
	 */
	abstract public function select();

	/**
	 * count
	 * @return integer
	 */
	abstract public function count();


	/**
	 * deleteCacheSelect 删除读取缓存
	 * @param  integer $refresh 延迟刷新时间
	 * @return this
	 */
	abstract public function deleteCacheSelect($refresh = NULL);


	/**
	 * deleteCacheCount 删除数量缓存
	 * @param  integer $refresh 延迟刷新时间
	 * @return this
	 */
	abstract public function deleteCacheCount($refresh = NULL);
}