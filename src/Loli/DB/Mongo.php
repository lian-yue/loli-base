<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-09 07:56:37
/*	Updated: UTC 2015-02-07 13:03:01
/*
/* ************************************************************************** */
namespace Loli\DB;
use MongoClient, MongoException, MongoId, MongoCode;
class_exists('Loli\DB\Base') || exit;
class Mongo extends Base{

	private $_error = 0;
	private $_errno = false;



	// 排序用的
	private $sort = [];


	public function error() {
		if (!$this->link) {
			return false;
		}
		return $this->_error;
	}


	public function errno() {
		if (!$this->link) {
			return false;
		}
		return $this->_errno;
	}


	public function connect($args) {
		try {
			$MongoClient = new MongoClient(!empty($args['link']) ? $args['link'] : ('mongodb://'. (empty($args['host']) ? MongoClient::DEFAULT_HOST : $args['host']) .':' . (empty($args['port']) ? MongoClient::DEFAULT_PORT : $args['port'])));
			$link = $MongoClient->selectDB($args['name']);
			if (!empty($args['pass'])) {
				$link->authenticate($args['user'], $args['pass']);
			}
		} catch (MongoException $e) {
			$this->_error = $e->getMessage();
			$this->_errno = $e->getCode();
			$this->debug && $this->exitError('MongoClient()');
			return false;
		}
		return $link;
	}



	public function tables() {
		if (!$link = $this->link(false)) {
			return false;
		}
		$tables = [];
		try {
			$collections = $this->data['getCollectionNames()']  = $link->getCollectionNames();
			foreach ($collections as $collection) {
				$tables[] = $collection;
			}
		 } catch (MongoException $e) {
			$this->_error = $e->getMessage();
			$this->_errno = $e->getCode();
			$this->debug && $this->exitError($query_str);
			return false;
		}
		return $tables;
	}


	public function exists($table) {
		if (($tables = $this->tables()) === false) {
			return false;
		}
		return in_array($table, $tables) ? 1 : 0;
	}


	public function truncate($table) {
		if (!$table || !is_string($table) || $table{0} == '$') {
			return false;
		}
		if (!$link = $this->link(false)) {
			return false;
		}

		try {
			$r = $this->data[$query_str = $table.'.remove()'] = $link->{$table}->remove();
		} catch (MongoException $e) {
			$this->_error = $e->getMessage();
			$this->_errno = $e->getCode();
			$this->debug && $this->exitError($query_str);
			return false;
		}
		if (empty($r['ok'])) {
			$this->_error = empty($r['err']) ? (empty($r['errmsg']) ? 'Truncate' : $r['errmsg']) : $r['err'];
			$this->_errno = empty($r['code']) ? -1 : $r['code'];
			$this->debug && $this->exitError($query_str);
			return false;
		}
		return empty($v['n']) ? 0 : $v['n'];
	}


	public function drop($table) {
		if (!$table || !is_string($table) || $table{0} == '$') {
			return false;
		}
		if (!$link = $this->link(false)) {
			return false;
		}

		try {
			$r = $this->data[$query_str = $table.'.drop()'] = $link->{$table}->drop();
		} catch (MongoException $e) {
			$this->_error = $e->getMessage();
			$this->_errno = $e->getCode();
			$this->debug && $this->exitError($query_str);
			return false;
		}
		return empty($r['ok']) ? 0 : 1;
	}


	public function create($args) {
		if (empty($args['create'])) {
			if (empty($args['collection'])) {
				return false;
			}
			$args['create'] = $args['collection'];
		}
		if (!($r = $this->_command($a = array_intersect_key($args, ['create' => '', 'capped' => '', 'autoIndexId' => '', 'size' => '', 'max' => '', 'flags' => '', 'usePowerOf2Sizes' => '']))) || empty($r['ok'])) {
			return false;
		}
		$this->_command(['dropIndexes' => $args['create'], 'index' => '*']);
		empty($args['indexes']) || $this->_command(['createIndexes' => $args['create'], 'indexes' => $args['indexes']]);
		return true;
	}


	public function insert($args) {
		if (empty($args['insert'])) {
			if (empty($args['collection'])) {
				return false;
			}
			$args['insert'] = $args['collection'];
		}
		if (empty($args['documents'])) {
			return false;
		}
		$documents = [];
		foreach ($args['documents'] as $k => $v) {
			if (is_string($v) || is_bool($v) || is_int($v) || !is_numeric($k)) {
				return false;
			}
			$v = (array) $v;
			ksort($v);
			if ($v = array_unnull($v)) {
				if (!$v = $this->_idObject($v)) {
					return false;
				}
				$documents[] = (array) $v;
			}
		}
		if (!$documents) {
			return false;
		}
		if (!$link = $this->link(false)) {
			return false;
		}
		$args['documents'] = $documents;

		// writeConcern 的选项
		$writeConcern = empty($args['writeConcern']) ? [] : $args['writeConcern'];
		if (isset($args['ordered']) && !isset($writeConcern['continueOnError'])) {
			$writeConcern['continueOnError'] = !$args['ordered'];
		}

		++self::$querySum;
		try {
			$r = $this->data[$query_str = $args['insert'].'.batchInsert('. json_encode($args['documents']) . ', ' .  json_encode($writeConcern).')'] = $link->{$args['insert']}->batchInsert($args['documents'], $writeConcern);
		} catch (MongoException $e) {
			$this->_error = $e->getMessage();
			$this->_errno = $e->getCode();
			$this->debug && $this->exitError($query_str);
			return false;
		}
		if (!empty($r['err']) || !empty($r['errmsg'])) {
			$this->_error = empty($r['err']) ? $r['errmsg'] : $r['err'];
			$this->_errno = empty($r['code']) ? -1 : $r['code'];
			$this->debug && $this->exitError($query_str);
			return false;
		}
		if (empty($r['ok'])) {
			return false;
		}
	 	if ($row = $this->_toString(end($args['documents']))) {
	 		$this->insertID = is_array($row) ? $row['_id'] : $row->_id;
	 	}
		return count($args['documents']);
	}



	public function replace($args) {
		if (empty($args['replace'])) {
			if (empty($args['collection'])) {
				return false;
			}
			$args['replace'] = $args['collection'];
		}
		if (empty($args['documents'])) {
			return false;
		}
		$documents = [];
		foreach ($args['documents'] as $k => $v) {
			if (is_string($v) || is_bool($v) || is_int($v) || !is_numeric($k)) {
				return false;
			}
			$v = (array) $v;
			ksort($v);
			if ($v = array_unnull($v)) {
				if (!$v = $this->_idObject($v)) {
					return false;
				}
				$documents[] = (array) $v;
			}
		}
		if (!$documents) {
			return false;
		}
		if (!$link = $this->link(false)) {
			return false;
		}
		$args['documents'] = $documents;

		// writeConcern 的选项
		$writeConcern = empty($args['writeConcern']) ? [] : $args['writeConcern'];
		$writeConcernString = json_encode($writeConcern);

		$r = 0;
		$row = false;
		foreach ($args['documents'] as $v) {
			++self::$querySum;
			try {
				$save = $this->data[$query_str = $args['replace'].'.save('. json_encode($v) . ', ' .  $writeConcernString.')'] = $link->{$args['replace']}->save($v, $writeConcern);
			} catch (MongoException $e) {
				$this->_error = $e->getMessage();
				$this->_errno = $e->getCode();
				$this->debug && $this->exitError($query_str);
				continue;
			}
			if (!empty($save['err']) || !empty($save['errmsg'])) {
				$this->_error = empty($save['err']) ? $save['errmsg'] : $save['err'];
				$this->_errno = empty($save['code']) ? -1 : $save['code'];
				$this->debug && $this->exitError($query_str);
				if (!isset($args['ordered']) || $args['ordered']) {
					return false;
				}
				continue;
			}
			if (empty($save['ok'])) {
				if (!isset($args['ordered']) || $args['ordered']) {
					return false;
				}
				continue;
			}
			$row = $v;
			++$r;
		}

		if ($row = $this->_toString($row)) {
	 		$this->insertID = is_array($row) ? $row['_id'] : $row->_id;
	 	}
		return $r;
	}


	public function update($args) {
		if (empty($args['update'])) {
			if (empty($args['collection'])) {
				return false;
			}
			$args['update'] = $args['collection'];
		}
		if (empty($args['updates'])) {
			return false;
		}
		$updates = [];
		foreach ($args['updates'] as $k => $v) {
			if (is_string($v) || is_bool($v) || is_int($v) || !is_numeric($k)) {
				return false;
			}
			$v = (array) $v;
			if (!isset($v['q']) || ($v['q'] && !($v['q'] = $this->_idObject($v['q'], true)))) {
				return false;
			}
			if (empty($v['u']) || !($v['u'] = array_unnull($v['u']))) {
				return false;
			}
			foreach ($v['u'] as $kk => $vv) {
				if ($kk && $kk{0} == '$' && $vv) {
					if (!$vv = $this->_idObject($vv)) {
						return false;
					}
				} elseif ('_id' == $kk) {
					if  (!$vv = $this->_id($vv)) {
						return false;
					}
				}
				$v['u'][$kk] = $vv;
			}
			$updates[] = $v;
		}
		if (!$updates) {
			return false;
		}
		$args['updates'] = $updates;
		if (!$link = $this->link(false)) {
			return false;
		}
		$writeConcern = empty($args['writeConcern']) ? [] : $args['writeConcern'];
		$r = [];
		foreach ($args['updates'] as $v) {
			++self::$querySum;
			$options = array_intersect_key($v, ['upsert' => '']) + $writeConcern;
			if (isset($v['multi'])) {
				$options['multiple'] = $v['multi'];
			}
			try {
				$update = $this->data[$query_str = $args['update'].'.update('. json_encode($v['q']) . ', ' .  json_encode($v['u']) . ', ' . json_encode($options) .')'] = $link->{$args['update']}->update($v['q'], $v['u'], $options);
			} catch (MongoException $e) {
				$this->_error = $e->getMessage();
				$this->_errno = $e->getCode();
				$this->debug && $this->exitError($query_str);
				if (!isset($args['ordered']) || $args['ordered']) {
					return false;
				}
			}

			if (empty($update['ok'])) {
				if (!isset($args['ordered']) || $args['ordered']) {
					return false;
				}
				continue;
			}
			$r[] = $update;
		}
		$n = 0;
		foreach($r as $k => $v) {
			if (!empty($v['n'])) {
				$n += $v['n'];
			}
		}
		if ($n) {
			return $n;
		}
		foreach($r as $k => $v) {
			if (!empty($v['nModified'])) {
				$n += $v['nModified'];
			}
		}
		return $n;

		/*
		// cmd 的目前没那命令
		$args['updates'] = $updates;
		if (!($r = $this->_command($a = array_intersect_key($args, ['update' => '', 'updates' => '', 'ordered' => '', 'writeConcern' => '']))) || empty($r['ok'])) {
			return false;
		}
		if (!empty($r['n'])) {
			return $r['n'];
		}

		if (!empty($r['nModified'])) {
			return $r['nModified'];
		}
		return 0;
		*/
	}

	public function delete($args) {
	 	if (empty($args['delete'])) {
			if (empty($args['collection'])) {
				return false;
			}
			$args['delete'] = $args['collection'];
		}
		if (empty($args['deletes'])) {
			return false;
		}

		$deletes = [];
		foreach ($args['deletes'] as $k => $v) {
			if (is_string($v) || is_bool($v) || is_int($v) || !is_numeric($k)) {
				return false;
			}
			$v = (array) $v;
			if (!isset($v['q']) || ($v['q'] && !($v['q'] = $this->_idObject($v['q'], true)))) {
				return false;
			}
			$deletes[] = $v;
		}
		if (!$deletes) {
			return false;
		}
		$args['deletes'] = $deletes;
		if (!$link = $this->link(false)) {
			return false;
		}
		$writeConcern = empty($args['writeConcern']) ? [] : $args['writeConcern'];
		$r = [];
		foreach ($args['deletes'] as $v) {
			$options = $writeConcern;
			if (isset($v['limit'])) {
				$options['justOne'] = $v['limit'] == 1;
			}
			try {
				$remove = $this->data[$query_str = $args['delete'].'.remove('. json_encode($v['q']) . ', ' . json_encode($options) .')'] = $link->{$args['delete']}->remove($v['q'], $options);
			} catch (MongoException $e) {
				$this->_error = $e->getMessage();
				$this->_errno = $e->getCode();
				$this->debug && $this->exitError($query_str);
				if (!isset($args['ordered']) || $args['ordered']) {
					return false;
				}
			}
			if (empty($remove['ok'])) {
				if (!isset($args['ordered']) || $args['ordered']) {
					return false;
				}
				continue;
			}
			$r[] = $remove;
		}


		$n = 0;
		foreach($r as $k => $v) {
			if (!empty($v['n'])) {
				$n += $v['n'];
			}
		}
		return $n;


		/*
		// cmd 的目前没命令
		if (!($r = $this->_command($a = array_intersect_key($args, ['delete' => '', 'deletes' => '', 'ordered' => '', 'writeConcern' => '']))) || empty($r['ok'])) {
			return false;
		}
		if (!empty($r['n'])) {
			return $r['n'];
		}
		return 0;
		*/
	}

	public function row($args, $slave = true) {
		return ($r = $this->results(['limit' => 1] + $args, $slave)) ? reset($r) : false;
	}


	public function results($args, $slave = true) {
		$command = empty($args['command']) ? '' : $args['command'];
		if (!$link = $this->link($slave)) {
			return [];
		}



		// aggregate
		if ($command == 'aggregate') {
			try {
				if (empty($args['aggregate'])) {
					if (empty($args['collection'])) {
						return [];
					}
					$args['aggregate'] = $args['collection'];
				}
				$pipeline = empty($args['pipeline']) ? [] : $args['pipeline'];
				$options = array_intersect_key($args, ['allowDiskUse' => '', 'explain' => '', 'cursor' => '', 'maxTimeMS' => '']);
				$keys = [];
				foreach ($pipeline as $k => $v) {
					if(!is_int($k)) {
						unset($pipeline[$k]);
						if ($k && $k{0} == '$') {
							$pipeline[][$k] = $v;
							$keys[] = $k;
						}
						continue;
					}
					foreach ($v as $k => $v) {
						$keys[] = $k;
					}
				}

				if (!in_array('$limit', $keys) && !empty($args['limit'])) {
					$pipeline[]['$limit'] = $args['limit'];
				}

				if (!in_array('$sort', $keys) && !empty($args['sort'])) {
					$pipeline[]['$sort'] = $args['sort'];
				}

				if (!in_array('$skip', $keys) && !empty($args['skip'])) {
					$pipeline[]['$skip'] = $args['skip'];
				}
				if (!in_array('$project', $keys)&& !empty($args['fields'])) {
					$pipeline[]['$project'] = $args['fields'];
				}
				if (!in_array('$match', $keys) && !empty($args['query'])) {
					$pipeline[]['$match'] = $args['query'];
				}
				foreach($pipeline as $k => $v) {
					foreach ($v as $kk => $vv) {
						if ($kk == '$match' && $vv) {
							if (!$pipeline[$k][$kk] = $this->_idObject($vv, true)) {
								return [];
							}
						}
					}
				}
				++self::$querySum;
				$query_str = $args['aggregate'] .'.aggregateCursor('. json_encode($pipeline) .', '. json_encode($options) .')';
				$this->data[$query_str] = $cursor = $link->{$args['aggregate']}->aggregatecursor($pipeline, $options);
				$r = [];
				foreach ($cursor as $v) {
					++self::$queryRow;
					$r[] = (object) $this->_toString($v);
				}
			} catch (MongoException $e) {
				$this->_error = $e->getMessage();
				$this->_errno = $e->getCode();
				$this->debug && $this->exitError($query_str);
			}
			return $r;
		}


		// distinct
		if ($command == 'distinct') {
			try {
				if (empty($args['distinct'])) {
					if (empty($args['collection'])) {
						return [];
					}
					$args['distinct'] = $args['collection'];
				}
				if (isset($args['query'])) {
					if (!is_array($args['query']) && !is_object($args['query'])) {
						return [];
					}
					if ($args['query'] && !($args['query'] = $this->_idObject($args['query'], true))) {
						return [];
					}
				} else {
					$args['query'] = [];
				}
				++self::$querySum;
				$query_str = $args['distinct'] .'.distinct('. $args['key'] .', '. json_encode($args['query']) .')';
				$this->data[$query_str] = $distinct = $link->{$args['distinct']}->distinct($args['key'], $args['query']);
			} catch (MongoException $e) {
				$this->_error = $e->getMessage();
				$this->_errno = $e->getCode();
				$this->debug && $this->exitError($query_str);
			}
			return $distinct ? $distinct : [];
		}


		// group
		if ($command == 'group') {
			try {
				if (empty($args['ns'])) {
					if (empty($args['collection'])) {
						return [];
					}
					$args['ns'] = $args['collection'];
				}
				if (!isset($args['cond']) && isset($args['query'])) {
					$args['cond'] = $args['query'];
				}
				if (isset($args['cond'])) {
					if (!is_array($args['cond']) && !is_object($args['cond'])) {
						return [];
					}
					if ($args['cond'] && !($args['cond'] = $this->_idObject($args['cond'], true))) {
						return [];
					}
				}
				if (!isset($args['key']) && isset($args['$keyf'])) {
					$args['key'] = $args['$keyf'];
					if (!$args['key'] instanceof MongoCode) {
						$args['key'] = new MongoCode($args['key']);
					}
				}
				if (empty($args['$reduce']) || !$args['$reduce'] instanceof MongoCode) {
					$args['$reduce'] = new MongoCode(empty($args['$reduce']) ? 'function(){}' : $args['$reduce']);
				}
				if (!empty($args['finalize']) && !$args['finalize'] instanceof MongoCode) {
					$args['finalize'] = new MongoCode($args['finalize']);
				}
				$args += ['initial' => []];
				$options = [];
				if (!empty($args['finalize'])) {
					$options['finalize'] = $args['finalize'];
				}
				if (!empty($args['cond'])) {
					$options['condition'] = $args['cond'];
				}
				++self::$querySum;
				$query_str = $args['ns'] .'.group('. json_encode($args['key']) .', '. json_encode($args['initial']) .', '. json_encode($args['$reduce']) .', '. json_encode($options) .')';
				$this->data[$query_str] = $group = $link->{$args['ns']}->group($args['key'], $args['initial'], $args['$reduce'], $options);
			} catch (MongoException $e) {
				$this->_error = $e->getMessage();
				$this->_errno = $e->getCode();
				$this->debug && $this->exitError($query_str);
				return [];
			}
			if (!empty($r['err']) || !empty($r['errmsg'])) {
				$this->_error = empty($r['err']) ? $r['errmsg'] : $r['err'];
				$this->_errno = empty($r['code']) ? -1 : $r['code'];
				$this->debug && $this->exitError($query_str);
				return [];
			}
			if (empty($group['retval'])) {
				return [];
			}
			$r = [];
			foreach ($group['retval'] as $v) {
				++self::$queryRow;
				$r[] = (object) $this->_toString($v);
			}
			// mongodb 的 group 不支持排序 添加支持
			if (!empty($args['sort'])) {
				$this->sort = $args['sort'];
				usort($r,[$this, 'sort']);
			}
			return $r;
		}





		// 常规查询
		try {
			if (empty($args['collection']) || !isset($args['query']) || (!is_array($args['query']) && !is_object($args['query']))) {
				return [];
			}
			if ($args['query'] && !($args['query'] = $this->_idObject($args['query'], true))) {
				return [];
			}
			$args['fields'] = empty($args['fields']) ? [] : $args['fields'];

			$query_str = $args['collection'] . '.find('. json_encode($args['query']) . ', '. json_encode($args['fields']).')';
			$cursor = $link->{$args['collection']}->find($args['query'], $args['fields']);
			if (!empty($args['options'])) {
				foreach ($args['options'] as $k => $v) {
					$cursor = $cursor->addOption($k, $v);
					$query_str .= '.addOption('. $k .', '. (is_array($v) || is_object($v) ? json_encode($v)  : $v) .')';
				}
			}
			if (!empty($args['sort'])) {
				$cursor = $cursor->sort($args['sort']);
				$query_str .= '.sort('. json_encode($args['sort']) .')';
			}
			if (!empty($args['skip'])) {
				$cursor = $cursor->skip($args['skip']);
				$query_str .= '.skip('. $args['skip'] .')';
			}
			if (!empty($args['limit'])) {
				$cursor = $cursor->limit($args['limit']);
				$query_str .= '.limit('. $args['limit'] .')';
			}
			if ($this->debug) {
				$this->data[$query_str.'.explain()'] = $cursor->explain();
			}
			foreach ($cursor as $v) {
				++self::$queryRow;
				$r[] = (object) $this->_toString($v);
			}
			$this->data[$query_str] = $r;
		} catch (MongoException $e) {
			$this->_error = $e->getMessage();
			$this->_errno = $e->getCode();
			$this->debug && $this->exitError($query_str);
			$r = [];
		}

		return $r;
	}

	public function count($args, $slave = true) {
		if (empty($args['collection']) || !isset($args['query']) || (!is_array($args['query']) && !is_object($args['query']))) {
			return false;
		}
		if (!$link = $this->link($slave)) {
			return false;
		}
		if ($args['query'] && !($args['query'] = $this->_idObject($args['query'], true))) {
			return false;
		}
		++self::$querySum;
		try {
			$args['fields'] = empty($args['fields']) ? [] : $args['fields'];
			$cursor = $link->{$args['collection']}->find($args['query'], $args['fields']);
			$query_str = $args['collection'].'.find('. json_encode($args['query']) . ', '. json_encode($args['fields']).')';
			if (!empty($args['options'])) {
				foreach ($args['options'] as $k => $v) {
					$cursor = $cursor->addOption($k, $v);
					$query_str .= '.addOption('. $k .', '. (is_array($v) || is_object($v) ? json_encode($v)  : $v) .')';
				}
			}
			if ($this->debug) {
				$this->data[$query_str.'.explain()'] = $cursor->explain();
			}

			$query_str .= '.count(true)';
			$this->data[$query_str] = $count = $cursor->count(true);
		} catch (MongoException $e) {
			$this->_error = $e->getMessage();
			$this->_errno = $e->getCode();
			$this->debug && $this->exitError($query_str);
			return false;
		}
		return $count;
	}


	public function start() {
	 	return false;
    }
    public function commit() {
    	return false;
    }
    public function rollback() {
    	return false;
    }

	private function _idObject($args, $call = false, $logical = false) {
		if (!$call) {
			if (isset($args['_id'])) {
				if (!$args['_id'] = $this->_id($args['_id'], $call)) {
					return false;
				}
			} elseif (isset($args->_id)) {
				if (!$args->_id = $this->_id($args->_id, $call)) {
					return false;
				}
			}
		} else {
			foreach ($args as $k => &$v) {
				if ($k === '_id') {
					if (!$v = $this->_id($v, $call)) {
						return false;
					}
				} elseif ($logical || ($k && $k{0} == '$')) {
					if ($v && (is_array($v) || is_object($v)) && ($logical || in_array($k, ['$gt', '$gte', '$in', '$lt', '$lte', '$ne', '$nin', '$and', '$nor', '$not', '$or', '$mod', '$all', '$elemMatch']))) {
						if (!$v = $this->_idObject($v, $call, in_array($k, ['$and', '$nor', '$not', '$or']))) {
							return false;
						}
					}
				}
			}
		}
		return $args;
	}

	private function _toString($args) {
		if (isset($args['_id'])) {
			if ($args['_id'] instanceof MongoId) {
				$args['_id'] = $args['_id']->__toString();
			}
		} elseif (isset($args->_id)) {
			if ($args->_id instanceof MongoId) {
				$args->_id = $args->_id->__toString();
			}
		}
		return $args;
	}

	private function _id($_id, $call = false) {
		if ($_id instanceof MongoId) {
			return $_id;
		}
		if ($call && (is_array($_id) || is_object($_id))) {
			foreach ($_id as &$v) {
				if (is_array($v) || is_object($v)) {
					if (!$v = $this->_id($v, true)) {
						return false;
					}
				} else {
					if (!$v instanceof MongoId) {
						try {
							$v = new MongoId($v);
						} catch (MongoException $e) {
							$this->_error = $e->getMessage();
							$this->_errno = $e->getCode();
							$this->debug && $this->exitError('MongoId()');
							return false;
						}
					}
				}
			}
		} else {
			if (!$_id) {
				return false;
			}
			try {
				$_id = new MongoId($_id);
			} catch (MongoException $e) {
				$this->_error = $e->getMessage();
				$this->_errno = $e->getCode();
				$this->debug && $this->exitError('MongoId()');
				return false;
			}
		}
		return $_id;
	}


	private function _command($command, $options = []) {
		if (!$command || !($link = $this->link(false))) {
			return false;
		}
		++self::$querySum;
		try {
			$r = $this->data[$query_str = 'this.command('.json_encode($command).', '. json_encode($options).' )'] = $link->command($command, $options);
		} catch (MongoException $e) {
			$this->_error = $e->getMessage();
			$this->_errno = $e->getCode();
			$this->debug && $this->exitError($query_str);
			return false;
		}
		if (!empty($r['err']) || !empty($r['errmsg']) ) {
			$this->_error = empty($r['err']) ? $r['errmsg'] : $r['err'];
			$this->_errno = empty($r['code']) ? -1 : $r['code'];
			$this->debug && $this->exitError($query_str);
			return false;
		}
		return $r;
	}


	public function sort($a, $b) {
		foreach ($this->sort as $k => $v) {
			if (isset($a->{$k}) && isset($b->{$k})) {
				if ($a->{$k} > $b->{$k}) {
					return $v > 0 ? 1 : -1;
				}
				if ($a->{$k} < $b->{$k}) {
					return $v > 0 ? -1 : 1;
				}
			}
			if (!isset($a->{$k}) && isset($b->{$k})) {
				return $v > 0 ? -1 : 1;
			}
			return $v > 0 ? 1 : -1;
		}
		return 0;
	}
}