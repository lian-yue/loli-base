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
/*	Updated: UTC 2015-03-24 04:18:32
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

	// 有修改表的日志  /*[数据库 or ID=>[表名=>执行时间戳]]*/
	private static $_logs = [];

	public function __construct(Base $DB, $tables = [], array $indexs = []) {
		$this->DB = $DB;
		$this->database = $DB->database();
		$this->protocol = $DB->protocol();
		$this->tables((array)$tables);
	}


	public function slave($slave) {
		$this->slave = $slave;
		return $this;
	}

	// 读取主从同步
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

	// 写入主从同步
	protected function setSlave($ttl = 2) {
		if (empty($this->data['uses'])) {
			return $this;
		}
		foreach ($this->data['uses'] as $table) {
			$this->_logs[$this->database][$table] = time() + ($ttl < 2 ? 2 : $ttl);
		}
		return $this;
	}

	public function callbacks(array $callbacks = []) {
		$this->callbacks = $callbacks;
		return $this;
	}

	public function callback($name, callback $callback = NULL) {
		$this->callbacks[$name] = $callback;
		return $this;
	}

	public function execute($execute) {
		$this->execute = $execute;
		return $this;
	}

	public function cache($ttl, $refresh = 0) {
		$this->cache = [$ttl, $refresh];
		return $this;
	}


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

	public function index($column, $value) {
		return $this->indexs([$column=>$value]);
	}

	/**
	 * 选择表多个表
	 * @param  array  $tables 选择的表 诉诸
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
	 * 选择表多个
	 * @param  [type] $table  选择的表
	 * @param  [type] $alias  别名 重命名
	 * @param  [type] $join   选择的表 join 参数
	 * @param  [type] $on     选择的表 on 参数
	 * @param  array  $params 附加参数
	 * @return $this
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
	 * 选择字段多个
	 * @param  array  $fields 字段数组
	 * @return $this
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
	 * 选择单个字段
	 * @param  [type] $field    字段名
	 * @param  [type] $alias    别名 重命名
	 * @param  [type] $function 字段函数
	 * @param  array  $params   附加参数
	 * @return $this
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
	 * 添加查询 信息
	 * @param  array  $querys 写入的查询数组
	 * @param  array  $indexs 写入的数组 索引方法
	 * @return  $this
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
	 * 添加查询 信息
	 * @param  [type] $column  字段
	 * @param  [type] $value   值
	 * @param  [type] $compare 运算符
	 * @param  [type] $function 函数
	 * @param  array  $params  附加变量
	 * @return $this
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
	 * 插入 values
	 * @param  array   $values     插入的数组
	 * @param  boolean $toDocument 是否写入成 文档
	 * @return $this
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
	 * 插入 value
	 * @param  [type]  $name       插入的名称
	 * @param  [type]  $value      插入的值
	 * @param  array   $params     附加参数
	 * @param  boolean $toDocument 是否写 成文档
	 * @return $this
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
	 * 写入的文档多个
	 * @param  array  $documents 二维数组
	 * @return $this
	 */
	public function documents(array $document) {
		$this->data = [];
		foreach ($documents as $document) {
			$this->document($document);
		}
		return $this;
	}



	/**
	 * 写入的文档
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
	 * @param  [type] $name  选项名称
	 * @param  [type] $value   值
	 * @param  array  $params  附加变量
	 * @return $this
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
	 * @param  [type] $columns  字段
	 * @return $this
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
	 * 偏移选项
	 * @param  [type] $offset 偏移位置
	 * @return $this
	 */
	public function offset($offset) {
		$this->data = [];
		$this->options[] = new Param(['name'=> 'offset', 'value'=> $offset]);
		return $this;
	}

	/**
	 * 限制选项
	 * @param  [type] $limit 限制数量
	 * @return $this
	 */
	public function limit($limit) {
		$this->data = [];
		$this->options[] = new Param(['name'=> 'limit', 'value'=> $limit]);
		return $this;
	}

	public function unions(array $unions, $all = NULL) {
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

	public function union($union, $all = NULL) {
		$this->data = [];
		if ($union instanceof Param) {
			$this->unions[] = $union;
		} else {
			$this->unions[] = new Param(['value' => $union, 'all' => $all]);
		}
		return $this;
	}


	// 判断表是否存在
	abstract public function exists();

	// 创建表
	abstract public function create();

	// 清空表
	abstract public function truncate();

	// 删除表
	abstract public function drop();

	// 插入字段
	abstract public function insert();

	// 更新字段
	abstract public function update();

	// 删除字段
	abstract public function delete();

	// 读取表
	abstract public function select();

	// 数量
	abstract public function count();


	abstract public function deleteCacheSelect();


	abstract public function deleteCacheCount();
}