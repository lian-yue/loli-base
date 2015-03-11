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
/*	Updated: UTC 2015-03-11 15:25:55
/*
/* ************************************************************************** */
namespace Loli\DB;
class Statement {
	protected $statement;

	protected $tables = [];

	protected $fields = [];

	protected $querys = [];

	protected $values = [];

	protected $documents = [];

	protected $options = [];

	protected $slave = NULL;

	public function __construct(Base $base, $statement, $tables = [], $slave = NULL) {
		$this->slave = $slave;
		$this->statement = $statement;
		$this->tables((array)$tables);
	}

	/**
	 * 选择表多个表
	 * @param  array  $tables 选择的表 诉诸
	 * @return $this
	 */
	public function tables(array $tables) {
		foreach ($tables as $name => $table) {
			if ($table instanceof Param) {
				$this->tables[] = $table;
				continue;
			}
			if (is_array($table) && !empty($table['value'])) {
				$this->tables[] = new Param(['value' => $table['value'], 'name' => isset($table['name']) ? $table['name'] : (is_int($name) ? NULL : $name), 'join' => empty($table['join']) ? NULL : $table['join'], 'on' => empty($table['on']) ? NULL : (array) $table['on']]);
				continue;
			}
			if ($table) {
				$this->tables[] = new Param(['value' => $table, 'name' => is_int($name) ? NULL : $name]);
				continue;
			}
		}
		return $this;
	}

	/**
	 * 选择表多个
	 * @param  [type] $table  选择的表
	 * @param  [type] $name   选择的表  as 重命名
	 * @param  [type] $on     选择的表 on 参数
	 * @param  array  $params 附加参数
	 * @return $this
	 */
	public function table($table, $name = NULL, $join = NULL, $on = NULL, array $params = []) {
		if ($table instanceof Param) {
			$table->setParams($params);
			$this->tables[] = $table;
		} else {
			$this->tables[] = new Param($params + ['value'=>$table, 'name'=>$name, 'join' => $join, 'on' => $on]);
		}
		return $this;
	}

	/**
	 * 选择字段多个
	 * @param  array  $fields 字段数组
	 * @return $this
	 */
	public function fields(array $fields) {
		foreach ($fields as $name => $field) {
			if ($field instanceof Param) {
				$this->fields[] = $field;
				continue;
			}

			if (is_array($field) && !empty($field['value'])) {
				$this->fields[] = new Param(['value' => $field['value'], 'name' => isset($field['name']) ? $field['name'] : (is_int($name) ? NULL : $name)]);
				continue;
			}

			if ($field) {
				$this->fields[] = new Param(['value' => $field, 'name' => is_int($name) ? NULL : $name]);
				continue;
			}
		}
		return $this;
	}

	/**
	 * 选择单个字段
	 * @param  [type] $field    字段名
	 * @param  [type] $name     重命名名
	 * @param  array  $params   附加参数
	 * @return $this
	 */
	public function field($field, $name = NULL, array $params = []) {
		if ($field instanceof Param) {
			$field->setParams($params);
			$this->fields[] = $field;
		} else {
			$this->fields[] = new Param($params + ['value'=> $field, 'name'=> $name]);
		}
		return $this;
	}

	/**
	 * 添加查询 信息
	 * @param  array  $querys 写入的查询数组
	 * @param  array  $indexs 写入的数组 索引方法
	 * @return  $this
	 */
	public function querys(array $querys, array $indexs = []) {
		foreach ($querys as $column => $value) {
			if ($value instanceof Param) {
				$this->querys[] = $value;
				continue;
			}
			$this->querys[] = new Param(['value' => $value] + (	isset($indexs[$column]) ? (is_array($indexs[$column]) ? $indexs[$column] : ['compare' => $indexs[$column]]) : []) + ['column' => $column]);
		}
		return $this;
	}
	/**
	 * 添加查询 信息
	 * @param  [type] $column  字段
	 * @param  [type] $value   值
	 * @param  [type] $compare 运算符
	 * @param  array  $params  附加变量
	 * @return $this
	 */
	public function query($column, $value = NULL, $compare = NULL, array $params = []) {
		if ($column instanceof Param) {
			$column->setParams($params);
			$this->querys[] = $column;
		} else {
			$this->querys[] = new Param($params + ['column'=> $column, 'value'=> $value, 'compare' => $compare]);
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
		foreach ($values as $name => $value) {
			if ($value instanceof Param) {
				$this->values[] = $value;
				continue;
			}
			$this->values[] = new Param(['name' => $name 'value' => $value]);
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
	 * @param  boolean $toDocument 是否写 成文档
	 * @return $this
	 */
	public function value($name, $value = NULL, $toDocument = false) {
		if ($name instanceof Param) {
			$this->values[] = $name;
		} else {
			$this->values[] = new Param(['name' => $name 'value' => $value]);
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
		foreach ($document as $name => &$value) {
			if (!$value instanceof Param) {
				$value = new Param(['name' => $name 'value' => $value]);
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
	 * @param  [type] $fields  字段
	 * @param  array  $params 附加选项
	 * @return $this
	 */
	public function group($fields, array $params = array()) {
		if ($fields instanceof Param) {
			$fields->setParams(['name' => 'group'], $params);
			$this->options[] = $fields;
		} else {
			$this->options[] = new Param(['name'=> 'group'] + $params + ['value'=> $fields]);
		}
		return $this;
	}

	/**
	 * 偏移选项
	 * @param  [type] $offset 偏移位置
	 * @return $this
	 */
	public function offset($offset) {
		$this->options[] = new Param(['name'=> 'offset', 'value'=> $offset]);
		return $this;
	}

	/**
	 * 限制选项
	 * @param  [type] $limit 限制数量
	 * @return $this
	 */
	public function limit($limit) {
		$this->options[] = new Param(['name'=> 'limit', 'value'=> $limit]);
		return $this;
	}

	/**
	 * 返回数量 插入影响的数量 或 查询 的统计
	 * @param  boolean $sum true 的话 统计全部 否则统计返回的 只对查询有效
	 * @return int
	 */
	abstract public function count($sum = true);

	abstract public function execute();

	abstract public function results();

	abstract public function __toString();
}