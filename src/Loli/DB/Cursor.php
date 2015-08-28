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
namespace Loli\DB;
class_exists('Loli\DB\Base') || exit;
class Cursor{

	// 数据库对象
	protected $DB;

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

	// 创建 or 插入 or 写入 or 更新 value
	protected $values = [];

	// 插入 or 写入 or 更新 文档
	protected $documents = [];

	// 选项
	protected $options = [];

	// 查询 unions
	protected $unions = [];

	// 缓存时间
	protected $cache = [0, 0];

	// 自动递增id
	protected $insertID;

	// 主键
	protected $primary = [];

	// 主键
	protected $primaryCache = 0;

	// 过滤
	protected $callback = false;

	// 构造器对象
	protected $builders = [
		'mongo' => 'Mongo',
		'mongodb' => 'Mongo',
		'redis' => 'Redis',
	];


	// 查询构造器
	protected $data = [];

	/**
	 * args 取得参数
	 * @param  string $name
	 * @return mixed
	 */
	public function __get($name) {
		return $this->$name;
	}




	public function DB(Base $DB) {
		$this->data = [];
		$this->DB = $DB;
	}


	public function insertID($insertID) {
		$this->data = [];
		$this->insertID = $insertID;
	}


	/**
	 * readonly 主从设置
	 * @param  boolean|null $readonly
	 * @return this
	 */
	public function readonly($readonly) {
		$this->data = [];
		$this->readonly = $readonly;
		return $this;
	}

	/**
	 * execute 是否返回的是执行
	 * @param  boolean $execute
	 * @return this
	 */
	public function execute($execute) {
		$this->data = [];
		$this->execute = $execute;
		return $this;
	}



	/**
	 * cache 设置缓存
	 * @param  integer  $ttl
	 * @param  integer $refresh
	 * @return this
	 */
	public function cache($ttl = 0, $refresh = 0) {
		$this->data = [];
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
		$this->data = [];
		$this->indexs[$column] = $value;
		return $this;
	}


	/**
	 * tables 选择表多个表
	 * @param  array  $tables 选择的表 数组
	 * @return $this
	 */
	public function tables(array $tables) {
		$this->data = [];
		$this->tables = array_merge($this->tables, $tables);
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
			$alias === NULL || $table->setParam('alias', $alias);
			$join === NULL || $table->setParam('join', $join);
			$on === NULL || $table->setParam('on', $on);
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
		$this->data = [];
		$this->columns = array_merge($this->columns, $columns);
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
		$this->data = [];
		if ($name instanceof Param) {
			$type === NULL || $name->setParam('type', $type);
			$length === NULL || $name->setParam('length', $length);
			$name->setParams($params);
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
		$this->fields = array_merge($this->fields, $fields);
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
			$alias === NULL || $field->setParam('alias', $alias);
			$function === NULL || $field->setParam('function', $function);
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
		$this->querys = array_merge($this->querys, $querys);
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
			$value === NULL || $column->setParam('value', $value);
			$compare === NULL || $column->setParam('compare', $compare);
			$function === NULL || $column->setParam('function', $function);
			$column->setParams($params);
			$this->querys[] = $column;
		} else {
			$this->querys[] = new Param($params + ['column' => $column, 'function' => $function, 'column'=> $column, 'value'=> $value, 'compare' => $compare]);
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
		$this->values = array_merge($this->values, $values);
		if ($toDocument && $this->values) {
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
			$value === NULL || $name->setParam('value', $value);
			$name->setParams($params);
			$this->values[] = $name;
		} else {
			$this->values[] = new Param($params + ['name' => $name, 'value' => $value]);
		}
		if ($toDocument && $this->values) {
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
		$this->options = array_merge($this->options, $options);
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
			$value === NULL || $name->setParam('value', $value);
			$name->setParams($params);
			$this->options[] = $name;
		} else {
			$this->options[] = new Param($params + ['name' => $name, 'value' => $value]);
		}
		return $this;
	}



	/**
	 * 分组选项
	 * @param  array|string $columns  字段
	 * @return this
	 */
	public function group($columns, $function = NULL) {
		$this->data = [];
		if ($columns instanceof Param) {
			$columns = [$columns];
		} else {
			$columns = (array) $columns;
		}
		foreach ($columns as $value) {
			if ($value instanceof Param) {
				$value->setParam('name', 'group');
				$function === NULL || $value->setParam('function', $function);
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
				$value->setParam('name', 'order');
				$order === NULL || $value->setParam('order', $order);
				$function === NULL || $value->setParam('function', $function);
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
		$this->data = [];
		foreach ($unions as $union) {
			if ($union instanceof Param || !$all) {
				$all && $union->setParam('all', $all);
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
		if ($union instanceof Param || !$all) {
			$all && $union->setParam('all', $all);
			$this->unions[] = $union;
		} else {
			$this->unions[] = new Param(['value' => $union, 'all' => $all]);
		}
		return $this;
	}


	public function primary(array $primary) {
		$this->primary = $primary;
	}


	/**
	 * callback 设置回调
	 * @param  boolean   $callback
	 * @return this
	 */
	public function callback($callback) {
		$this->callback = $callback;
		return $this;
	}




	protected function read(Row &$value) {

	}

	protected function write(Iterator $value = NULL) {

	}

	protected function success($name, Iterator $value = NULL) {

	}

	public function getUseTables() {
		if (empty($this->data['builder'])) {
			return [];
		}
		return $this->data['builder']->getUseTables();
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
		$name = strtolower($name);

		if ($this->values) {
			$this->documents[] = $this->values;
			$this->values = [];
		}


		// 私有变量
		if ($name{0} === '_') {
			throw new Exception('this._?', 'No access to this method');
		}

		// 无数据库信息
		if (!$this->DB) {
			throw new Exception('this.DB', 'No database objects');
		}

		// 主键设定
		if ($args) {
			if (count($args) !== count($this->primary)) {
				throw new Exception('this.' . $name, 'The primary key is not the same number of parameters');
			}
			$i = 0;
			foreach($this->primary as $primary) {
				$this->query($primary, $args[$i], '=');
				++$i;
			}
			$this->offset(0)->limit(1)->cache($this->primaryCache);
		}


		// 构造器
		if (empty($this->data['builder'])) {
			$builderName = __NAMESPACE__ .'\\'. (isset($this->builders[$this->DB->protocol()]) ? $this->builders[$this->DB->protocol()] : 'SQLBuilder');
			$this->data['builder'] = new $builderName($this);
		}

		// 无效的方法
		if (!method_exists($this->data['builder'], $name)) {
			throw new Exception('this.' . $name, 'The method is not registered');
		}

		if ($this->callback) {
			switch ($name) {
				case 'insert':
					// 插入
					$this->write();
					$result = $this->data['builder']->insert();
					$this->success($name);
					break;
				case 'update':
					// 更新
					$execute = $this->execute;
					$this->execute = true;
					$select = $this->data['builder']->select();
					$this->execute = $execute;
					$this->write($select);
					$result = $this->data['builder']->update();
					$this->data['builder']->deleteCacheSelect();
					$this->data['builder']->deleteCacheCount();
					$this->success($name, $select);
					break;
				case 'delete':
					// 删除
					$execute = $this->execute;
					$this->execute = true;
					$select = $this->data['builder']->select();
					$this->execute = $execute;
					$result = $this->data['builder']->delete();
					$this->data['builder']->deleteCacheSelect();
					$this->data['builder']->deleteCacheCount();
					$this->success($name, $select);
					break;
				case 'select':
					$result = $this->data['builder']->select();
					if (!is_string($result)) {
						foreach($result as &$value) {
							$this->read($value);
						}
					}
				default:
					$result = $this->data['builder']->$name();
			}
		} else {
			$result = $this->data['builder']->$name();
		}
		return $result;
	}


}