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
/*	Updated: UTC 2015-02-28 14:33:34
/*
/* ************************************************************************** */
namespace Loli\DB;
use MongoClient, MongoException, MongoResultException, MongoCursorException, MongoCursorTimeoutException, MongoConnectionException, MongoGridFSException, MongoDuplicateKeyException, MongoProtocolException, MongoExecutionTimeoutException, MongoWriteConcernException, MongoId, MongoCode;
class_exists('Loli\DB\Base') || exit;
class Mongo extends Base{

	// 排序用的
	private $_sort = [];

	/**
	 * 链接数据库
	 * @param  [type] $args [description]
	 * @return [type]       [description]
	 */
	public function connect(array $args) {
		try {

			$server = empty($args['link']) ? 'mongodb://' . (empty($args['host']) ? MongoClient::DEFAULT_HOST : $args['host']) .':' . (empty($args['port']) ? MongoClient::DEFAULT_PORT : $args['port']) : $args['link'];

			// 链接到服务器
			$client = new MongoClient($server);

			// 表名为空
			empty($args['name']) && $this->addLog('MongoClient('. $server .').selectDB()', 'Database name can not be empty', 2);

			// 链接到表
			$link = $client->selectDB($args['name']);

			// 有密码的
			if (!empty($args['pass'])) {
				$auth = @$link->authenticate($args['user'], $args['pass']);

				// 链接失败
				empty($auth['ok']) && $this->addLog('this.MongoClient('. $server .').selectDB('. $args['name'] .')->authenticate()', $auth['errmsg'], 2);
			}
		} catch (MongoException $e) {

			// 链接错误
			$this->addLog('this.MongoClient('. $server .').selectDB('. $args['name'] .')', $e->getMessage(), 2, $e->getCode());
		}

		return $link;
	}


	public function ping() {
		return true;
	}



	public function tables() {
		// http://docs.mongodb.org/manual/reference/method/db.getCollectionNames/
		// http://php.net/manual/en/mongodb.getcollectionnames.php
		$link = $this->link(false);
		$tables = [];
		$queryString = 'this.getCollectionNames()';
		try {
			// 遍历所有集合
			$collections = $link->getCollectionNames();
			$this->addLog($queryString, $collections);
			foreach ($collections as $collection) {
				$tables[] = $collection;
			}
		 } catch (MongoException $e) {
		 	// 错误
			$this->addLog($queryString, $e->getMessage(), 1, $e->getCode());
		}
		return $tables;
	}


	public function exists($table) {
		return in_array($table, $this->tables(), true) ? $table : false;
	}


	public function truncate($table) {
		// http://docs.mongodb.org/manual/reference/method/db.collection.remove/
		// http://php.net/manual/en/mongocollection.remove.php
		$link = $this->link(false);

		$queryString = $table.'.remove()';
		try {
			$results = $link->{$table}->remove();
			$this->addLog($queryString, $results);
		} catch (MongoException $e) {
			$this->addLog($queryString, $e->getMessage(), 1, $e->getCode());
		}
		empty($results['ok']) && $this->addLog($queryString, $results, 1);
		return empty($results['n']) ? 0 : $results['n'];
	}


	public function drop($table) {
		// http://docs.mongodb.org/manual/reference/command/drop/
		// http://docs.mongodb.org/manual/reference/method/db.collection.drop/
		// http://php.net/manual/en/mongocollection.drop.php
		// 没有表 返回 false
		if (!$this->exists($table)) {
			return false;
		}
		$link = $this->link(false);
		$queryString = $table.'.drop()';

		// 删除
		try {
			$results = $link->{$table}->drop();
			$this->addLog($queryString, $results);
		} catch (MongoException $e) {
			// 错误
			$this->addLog($queryString, $e->getMessage(), 1, $e->getCode());
		}
		// 删除成功是否
		return !empty($results['ok']);
	}


	public function create($args) {
		// http://docs.mongodb.org/manual/reference/command/create/
		// http://docs.mongodb.org/manual/reference/method/db.createCollection/
		// http://php.net/manual/en/mongodb.createcollection.php

		// 创建查询字符串 日志
		$queryString = 'this.create(' . json_encode($args) .')';

		// 没 create 并且有集合 使用集合
		if (empty($args['create']) && !empty($args['collection'])) {
			$args['create'] = $args['collection'];
		}

		// 集合名为空
		empty($args['create']) && $this->addLog($queryString, 'Collection named empty', 1);
		if ($this->exists($args['create'])) {
			return false;
		}

		// 执行 command
		$results = $this->_command(array_intersect_key($args, ['create' => '', 'capped' => '', 'autoIndexId' => '', 'size' => '', 'max' => '', 'flags' => '', 'usePowerOf2Sizes' => '']));

		// 创建错误
		empty($results['ok']) && $this->addLog($queryString, $results, 1);

		// 删除 索引
		$this->_command(['dropIndexes' => $args['create'], 'index' => '*']);

		// 创建索引
		empty($args['indexes']) || $this->_command(['createIndexes' => $args['create'], 'indexes' => $args['indexes']]);

		return true;
	}




	public function insert($args) {
		$this->insertID = false;

		// http://docs.mongodb.org/manual/reference/command/insert/
		// http://docs.mongodb.org/manual/reference/method/db.collection.insert/
		// http://php.net/manual/en/mongocollection.batchinsert.php
		if (isset($args['ordered']) && !isset($args['writeConcern']['continueOnError'])) {
			$args['writeConcern']['continueOnError'] = !$args['ordered'];
		}

		if (empty($args['insert']) && !empty($args['collection'])) {
			$args['insert'] = $args['collection'];
		}
		$args += ['insert' => '', 'documents' => [], 'writeConcern' => []];
		extract($args, EXTR_SKIP);

		$queryString = $insert.'.batchInsert('. json_encode($documents) . ', ' .  json_encode($writeConcern).')';

		// 集合为空
		$insert || $this->addLog($queryString, 'Collection named empty', 1);

		// 文档为空
		$documents || $this->addLog($queryString, 'Insert the documents is empty', 1);

		// 整理数据
		foreach ($documents as $key => &$document) {
			is_numeric($key) || $this->addLog($queryString, 'Keys into the document format', 1);
			is_array($document) || is_object($document) || $this->addLog($queryString, 'Insert the document content', 1);
			$document = (array) $document;
			ksort($document);
			($document = array_unnull($document)) || $this->addLog($queryString, 'Insert the document is empty', 1);
			$document = (array) $this->_idObject($document);
		}
		unset($document);


		$link = $this->link(false);


		++self::$querySum;
		try {
			$queryString = $insert.'.batchInsert('. json_encode($documents) . ', ' .  json_encode($writeConcern).')';
			$results = $link->{$insert}->batchInsert($documents, $writeConcern);
			$this->addLog($queryString, $results);
		} catch (MongoException $e) {
			$this->addLog($queryString, $e->getMessage(), 1, $e->getCode());
		}

		// 插入失败的
		empty($results['ok']) && $this->addLog($queryString, $results, 1);

		// 读最后个插入的id
		try {
			if ($document = $this->_toString(end($documents))) {
				$this->insertID = is_array($document) ? $document['_id'] : $document->_id;
			}
		} catch (Exception $e) {
		}
		// 返回成功插入的数量
		return count($documents);
	}



	public function replace($args) {
		$this->insertID = false;

		// http://docs.mongodb.org/manual/reference/method/db.collection.save/
		// http://php.net/manual/en/mongocollection.save.php
		if (isset($args['ordered']) && !isset($args['writeConcern']['continueOnError'])) {
			$args['writeConcern']['continueOnError'] = !$args['ordered'];
		}
		if (!isset($args['writeConcern']['continueOnError'])) {
			$args['writeConcern']['continueOnError'] = false;
		}

		if (empty($args['save'])) {
			if (!empty($args['replace'])) {
				$args['save'] = $args['replace'];
			} elseif (empty($args['collection'])) {
				$args['save'] = $args['collection'];
			}
		}

		$args += ['save' => '', 'documents' => [], 'writeConcern' => []];
		extract($args, EXTR_SKIP);


		$writeConcernString = json_encode($writeConcern);
		$queryString = $save.'.batchReplace('. json_encode($documents) . ', ' .  $writeConcernString.')';

		// 集合为空
		$save || $this->addLog($queryString, 'Collection named empty', 1);

		// 文档为空
		$documents || $this->addLog($queryString, 'Replace the documents is empty', 1);

		// 整理数据
		foreach ($documents as $key => &$document) {
			is_numeric($key) || $this->addLog($queryString, 'Keys into the document format', 1);
			is_array($document) || is_object($document) || $this->addLog($queryString, 'Replace the document content', 1);
			$document = (array) $document;
			ksort($document);
			($document = array_unnull($document)) || $this->addLog($queryString, 'Replace the document is empty', 1);
			$document = (array) $this->_idObject($document);
		}
		unset($document);

		$link = $this->link(false);

		$results = [];
		foreach ($documents as $document) {
			++self::$querySum;
			try {
				$queryString = $save.'.save('. json_encode($document) . ', ' .  $writeConcernString.')';
				$result = $link->{$save}->save($document, $writeConcern);
				$this->addLog($queryString, $result);
			} catch (MongoException $e) {
				if ($writeConcern['continueOnError']) {
					try {
						$this->addLog($queryString, $e->getMessage(), 1, $e->getCode());
					} catch (Exception $e) {
					}
				} else {
					$this->addLog($queryString, $e->getMessage(), 1, $e->getCode());
				}
				continue;
			}

			// 插入错误
			if (empty($save['ok'])) {
				if ($writeConcern['continueOnError']) {
					try {
						$this->addLog($queryString, $result, 1);
					} catch (Exception $e) {
					}
				} else {
					$this->addLog($queryString, $result, 1);
				}
				continue;
			}
			$results[] = $document;
		}

		// 读最后个插入的id
		try {
			if ($document = $this->_toString(end($documents))) {
				$this->insertID = is_array($document) ? $document['_id'] : $document->_id;
			}
		} catch (Exception $e) {
		}
		return count($results);


	}


	public function update($args) {
		// http://docs.mongodb.org/manual/reference/command/update/
		// http://docs.mongodb.org/manual/reference/method/db.collection.update/
		// http://php.net/manual/en/mongocollection.update.php
		if (isset($args['ordered']) && !isset($args['writeConcern']['continueOnError'])) {
			$args['writeConcern']['continueOnError'] = !$args['ordered'];
		}
		if (!isset($args['writeConcern']['continueOnError'])) {
			$args['writeConcern']['continueOnError'] = false;
		}

		if (empty($args['update']) && !empty($args['collection'])) {
			$args['update'] = $args['collection'];
		}
		$args += ['update' => '', 'updates' => [], 'writeConcern' => []];
		extract($args, EXTR_SKIP);

		$queryString = 'this.update('. $update. ', ' .  json_encode($updates).', '. ($writeConcern['continueOnError'] ? false : true) .', '. json_encode($writeConcern) .')';

		// 集合为空
		$update || $this->addLog($queryString, 'Collection named empty', 1);

		// 文档为空
		$updates || $this->addLog($queryString, 'Updates the documents is empty', 1);

		foreach ($updates as $key => &$value) {
			is_numeric($key) || $this->addLog($queryString, 'Keys into the document format', 1);
			is_array($value) || is_object($value) || $this->addLog($queryString, 'Update the parameters', 1);
			$value = (array) $value;
			if (!isset($value['q']) && isset($value['query'])) {
				$value['q'] = $value['query'];
			}
			if (!isset($value['u']) && isset($value['update'])) {
				$value['u'] = $value['update'];
			}

			isset($value['q']) || $this->addLog($queryString, 'Update query is empty', 1);
			is_array($value['q']) || is_object($value['q']) || $this->addLog($queryString, 'Update query format', 1);
			$value['q'] = $this->_idObject($value['q'], true);


			empty($value['u']) && $this->addLog($queryString, 'Update document is empty', 1);
			is_array($value['u']) || is_object($value['u']) || $this->addLog($queryString, 'Update document format', 1);
			($value['u'] = array_unnull((array)$value['u'])) || $this->addLog($queryString, 'Update document is empty', 1);
			foreach ($value['u'] as $k => &$v) {
				if ($k && $k{0} == '$' && $v) {
					$v = $this->_idObject($v);
				} elseif ('_id' == $k) {
					$v = $this->_id($v);
				}
			}
			unset($v);
		}
		unset($value);


		$link = $this->link(false);


		$results = [];
		foreach ($updates as $value) {
			++self::$querySum;
			$options = array_intersect_key($value, ['upsert' => '']) + $writeConcern;

			// 多个
			if (isset($value['multi'])) {
				$options['multiple'] = $value['multi'];
			}

			try {
				$queryString = $update.'.update('. json_encode($value['q']) . ', ' .  json_encode($value['u']) . ', ' . json_encode($options) .')';
				$result = $link->{$update}->update($value['q'], $value['u'], $options);
				$this->addLog($queryString, $result);
			} catch (MongoException $e) {
				if ($writeConcern['continueOnError']) {
					try {
						$this->addLog($queryString, $e->getMessage(), 1, $e->getCode());
					} catch (Exception $e) {
					}
				} else {
					$this->addLog($queryString, $e->getMessage(), 1, $e->getCode());
				}
				continue;
			}

			if (empty($result['ok'])) {
				if ($writeConcern['continueOnError']) {
					try {
						$this->addLog($queryString, $result, 1);
					} catch (Exception $e) {
					}
				} else {
					$this->addLog($queryString, $result, 1);
				}
				continue;
			}
			$results[] = $result;
		}

		$n = 0;
		foreach($results as $result) {
			if (!empty($result['n'])) {
				$n += $result['n'];
			}
		}
		if ($n) {
			return $n;
		}
		foreach($results as $result) {
			if (!empty($result['nModified'])) {
				$n += $result['nModified'];
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
		// http://docs.mongodb.org/manual/reference/command/delete/
		// http://docs.mongodb.org/manual/reference/method/db.collection.remove/
		// http://php.net/manual/en/mongocollection.remove.php
		if (isset($args['ordered']) && !isset($args['writeConcern']['continueOnError'])) {
			$args['writeConcern']['continueOnError'] = !$args['ordered'];
		}
		if (!isset($args['writeConcern']['continueOnError'])) {
			$args['writeConcern']['continueOnError'] = false;
		}

		if (empty($args['delete']) && !empty($args['collection'])) {
			$args['delete'] = $args['collection'];
		}
		$args += ['delete' => '', 'deletes' => [], 'writeConcern' => []];
		extract($args, EXTR_SKIP);

		$queryString = 'this.delete('. $delete. ', ' .  json_encode($deletes).', '. ($writeConcern['continueOnError'] ? false : true) .', '. json_encode($writeConcern) .')';

		// 集合为空
		$delete || $this->addLog($queryString, 'Collection named empty', 1);

		// 文档为空
		$deletes || $this->addLog($queryString, 'Deletes the querys is empty', 1);



		foreach ($deletes as $key => &$value) {
			is_numeric($key) || $this->addLog($queryString, 'Keys into the document format', 1);
			is_array($value) || is_object($value) || $this->addLog($queryString, 'Delete the parameters', 1);
			$value = (array) $value;
			if (!isset($value['q']) && isset($value['query'])) {
				$value['q'] = $value['query'];
			}
			isset($value['q']) || $this->addLog($queryString, 'Delete query is empty', 1);
			is_array($value['q']) || is_object($value['q']) || $this->addLog($queryString, 'Delete query format', 1);
			$value['q'] = $this->_idObject($value['q'], true);
		}
		unset($value);



		$link = $this->link(false);
		$results = [];
		foreach ($deletes as $value) {
			$options = $writeConcern;
			if (!empty($value['limit'])) {
				$options['justOne'] = $value['limit'] == 1;
			}

			try {
				$queryString = $delete.'.remove('. json_encode($value['q']) . ', ' . json_encode($options) .')';
				$result = $link->{$delete}->remove($value['q'], $options);
				$this->addLog($queryString, $result);
			} catch (MongoException $e) {
				if ($writeConcern['continueOnError']) {
					try {
						$this->addLog($queryString, $e->getMessage(), 1, $e->getCode());
					} catch (Exception $e) {
					}
				} else {
					$this->addLog($queryString, $e->getMessage(), 1, $e->getCode());
				}
				continue;
			}

			if (empty($result['ok'])) {
				if ($writeConcern['continueOnError']) {
					try {
						$this->addLog($queryString, $result, 1);
					} catch (Exception $e) {
					}
				} else {
					$this->addLog($queryString, $result, 1);
				}
				continue;
			}
			$results[] = $result;
		}



		$n = 0;
		foreach($results as $result) {
			if (!empty($result['n'])) {
				$n += $result['n'];
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
		$queryString = 'this.results('. json_encode($args).')';
		$link = $this->link($slave);

		// aggregate
		if ($command == 'aggregate') {
			// http://docs.mongodb.org/manual/reference/command/aggregate/
			// http://docs.mongodb.org/manual/reference/method/db.collection.aggregate/
			// http://php.net/manual/en/mongocollection.aggregate.php
			// http://php.net/manual/en/mongocollection.aggregatecursor.php
			if (empty($args['distinct']) && !empty($args['collection'])) {
				$args['distinct'] = $args['collection'];
			}
			$distinct = empty($args['distinct']) ? '': $args['distinct'];
			$options = array_intersect_key($args, ['allowDiskUse' => '', 'explain' => '', 'cursor' => '', 'maxTimeMS' => '']);


			$collection || $this->addLog($queryString, 'Collection named empty', 1);

			// 记录以存在的键名
			$keys = [];
			foreach ($pipeline as $key => $value) {
				if(is_int($key)) {
					foreach ($value as $key => $value) {
						$keys[] = $key;
					}
				} else {
					unset($pipeline[$key]);
					if ($key && $key{0} == '$') {
						$pipeline[][$key] = $value;
						$keys[] = $key;
					}
				}
			}


			// 替换键名
			foreach (['$limit' => 'limit', '$sort' => 'sort', '$skip' => 'skip', '$project' => 'fields', '$match' => 'query'] as $key => $value) {
				if (!in_array($key, $keys) && !empty($args[$value])) {
					$pipeline[][$key] = $args[$value];
				}
			}

			// 匹配的设置成对象
			unset($value);
			foreach($pipeline as &$foreach) {
				foreach ($foreach as $key => &$value) {
					if ($key == '$match' && $value) {
						$value = $this->_idObject($value, true);
					}
				}
			}
			unset($value);




			try {
				++self::$querySum;
				$queryString = $distinct .'.aggregateCursor('. json_encode($pipeline) .', '. json_encode($options) .')';
				$cursor = $link->{$distinct}->aggregatecursor($pipeline, $options);
				$results = [];
				foreach ($cursor as $result) {
					++self::$queryRow;
					$results[] = (object) $this->_toString($result);
				}
				$this->addLog($queryString, $results);
			} catch (MongoException $e) {
				$this->addLog($queryString, $e->getMessage(), 1, $e->getCode());
			}
			return $results;
		}


		// distinct
		if ($command == 'distinct') {
			// http://docs.mongodb.org/manual/reference/command/distinct/
			// http://docs.mongodb.org/manual/reference/method/db.collection.distinct/
			// http://php.net/manual/en/mongocollection.distinct.php
			if (empty($args['distinct']) && !empty($args['collection'])) {
				$args['distinct'] = $args['collection'];
			}
			if (empty($args['key']) && !empty($args['field'])) {
				$args['key'] = $args['field'];
			}
			$args += ['distinct' => '', 'key' => '', 'query' => []];
			extract($args, EXTR_SKIP);
			$distinct || $this->addLog($queryString, 'Collection named empty', 1);

			is_array($query) || is_object($query) || $this->addLog($queryString, 'Query format', 1);
			$query = $this->_idObject($query, true);;


			try {
				++self::$querySum;
				$queryString = $distinct .'.distinct('. $key .', '. json_encode($query) .')';
				$results = $link->{$distinct}->distinct($key, $query);
				$this->addLog($queryString, $results);
			} catch (MongoException $e) {
				$this->addLog($queryString, $e->getMessage(), 1, $e->getCode());
			}
			return $results ? $results : [];
		}


		// group
		if ($command == 'group') {
			// http://docs.mongodb.org/manual/reference/command/group/
			// http://docs.mongodb.org/manual/reference/method/db.collection.group/
			// http://php.net/manual/en/mongocollection.group.php
			if (empty($args['ns']) && !empty($args['collection'])) {
				$args['ns'] = $args['collection'];
			}

			if (!isset($args['cond']) && !empty($args['query'])) {
				$args['cond'] = $args['query'];
			}


			if (!isset($args['key']) && isset($args['keys'])) {
				$args['key'] = $args['keys'];
			}

			if (!isset($args['key']) && (isset($args['$keyf']) || isset($args['keyf']))) {
				$args['key'] = isset($args['$keyf']) ? $args['$keyf'] : $args['keyf'];
				if (!$args['key'] instanceof MongoCode) {
					$args['key'] = new MongoCode($args['key']);
				}
			}
			if (empty($args['reduce']) && !empty($args['$reduce'])) {
				$args['reduce'] = $args['$reduce'];
			}

			$args += ['ns' => '', 'cond' => [], 'key' => [], 'reduce' => 'function(){}', 'finalize' => '', 'initial' => []];
			extract($args, EXTR_SKIP);


			$ns || $this->addLog($queryString, 'Collection named empty', 1);

			is_array($cond) || is_object($cond) || $this->addLog($queryString, 'Condition format', 1);
			$cond = $this->_idObject($cond, true);

			empty($key) && $this->addLog($queryString, 'Key the is empty', 1);

			if ($reduce instanceof MongoCode) {
				$reduce = new MongoCode($reduce);
			}
			if ($finalize && $finalize instanceof MongoCode) {
				$finalize = new MongoCode($finalize);
			}


			try {
				$options = [];
				if ($finalize) {
					$options['finalize'] = $finalize;
				}
				if ($cond) {
					$options['condition'] = $cond;
				}
				++self::$querySum;
				$queryString = $ns .'.group('. json_encode($key) .', '. json_encode($initial) .', '. $reduce->__toString() .', '. json_encode($options) .')';
				$results = $link->{$ns}->group($key, $initial, $reduce, $options);
				$this->addLog($queryString, $results);
			} catch (MongoException $e) {
				$this->addLog($queryString, $e->getMessage(), 1, $e->getCode());
			}
			$res = [];
			if (!empty($results['retval'])) {
				foreach ($results['retval'] as $retval) {
					++self::$queryRow;
					$res[] = (object) $this->_toString($retval);
				}
				// mongodb 的 group 不支持排序 添加支持
				if (!empty($sort)) {
					$this->_sort = $sort;
					usort($res, [$this, 'sort']);
				}
			}
			return $res;
		}


		$args += ['collection' => '', 'fields' => [], 'query' => [], 'options' => [], 'sort' => [], 'skip' => 0, 'limit' => 0];
		extract($args, EXTR_SKIP);

		$collection || $this->addLog($queryString, 'Collection named empty', 1);

		is_array($query) || is_object($query) || $this->addLog($queryString, 'Query format', 1);

		$query = $this->_idObject($query, true);


		// 常规查询
		try {
			$queryString = $collection . '.find('. json_encode($query) . ', '. json_encode($fields).')';
			$cursor = $link->{$collection}->find($query, $fields);
			if ($options) {
				foreach ($options as $name => $value) {
					$queryString .= '.addOption('. $name .', '. (is_array($value) || is_object($value) ? json_encode($value)  : $value) .')';
					$cursor = $cursor->addOption($name, $value);
				}
			}
			if ($sort) {
				$cursor = $cursor->sort($sort);
				$queryString .= '.sort('. json_encode($sort) .')';
			}
			if ($skip) {
				$cursor = $cursor->skip($skip);
				$queryString .= '.skip('. $skip .')';
			}
			if ($limit) {
				$cursor = $cursor->limit($limit);
				$queryString .= '.limit('. $limit .')';
			}
			$this->explain && $this->addLog($queryString .'.explain()', $cursor->explain());
			$results = [];
			foreach ($cursor as $value) {
				++self::$queryRow;
				$results[] = (object) $this->_toString($value);
			}
			$this->addLog($queryString, $results);
		} catch (MongoException $e) {
			$this->addLog($queryString, $e->getMessage(), 1, $e->getCode());
		}
		return $results;
	}


	public function count($args, $slave = true) {
		// http://docs.mongodb.org/manual/reference/command/count/
		// http://docs.mongodb.org/manual/reference/method/db.collection.count/
		// http://php.net/manual/ja/mongocollection.count.php
		// http://php.net/manual/ja/mongocursor.count.php


		if (empty($args['count']) && !empty($args['collection'])) {
			$args['count'] = $args['collection'];
		}
		$args += ['count' => '', 'query' => []];

		extract($args, EXTR_SKIP);

		$count || $this->addLog($queryString, 'Collection named empty', 1);

		is_array($query) || is_object($query) || $this->addLog($queryString, 'Query format', 1);

		$query = $this->_idObject($query, true);

		$link = $this->link($slave);


		++self::$querySum;
		try {
			$options = array_intersect_key($options, ['hint' => '', 'limit' => '', 'skip' => '', 'maxTimeMS' => '']);

			$queryString = $count.'.count('. json_encode($query) . ', '. json_encode($options) .')';
			$result = $link->{$count}->count($query, $options);
			$this->addLog($queryString, $results);
		} catch (MongoException $e) {
			$this->addLog($queryString, $e->getMessage(), 1, $e->getCode());
			return false;
		}
		return $result;
	}


	public function startTransaction() {
		$this->addLog('startTransaction', 'Mongodb no transaction', 1);
	 	return false;
    }
    public function commit() {
    	$this->addLog('commit', 'Mongodb no transaction', 1);
    	return false;
    }
    public function rollback() {
    	$this->addLog('rollback', 'Mongodb no transaction', 1);
    	return false;
    }

	private function _idObject($args, $call = false, $logical = false) {
		if (!$call) {
			if (isset($args['_id'])) {
				$args['_id'] = $this->_id($args['_id'], $call);
			} elseif (isset($args->_id)) {
				$args->_id = $this->_id($args->_id, $call);
			}
		} else {
			foreach ($args as $k => &$v) {
				if ($k === '_id') {
					$v = $this->_id($v, $call);
				} elseif ($logical || ($k && $k{0} == '$')) {
					if ($v && (is_array($v) || is_object($v)) && ($logical || in_array($k, ['$gt', '$gte', '$in', '$lt', '$lte', '$ne', '$nin', '$and', '$nor', '$not', '$or', '$mod', '$all', '$elemMatch']))) {
						$v = $this->_idObject($v, $call, in_array($k, ['$and', '$nor', '$not', '$or']));
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
					$v = $this->_id($v, true);
				} else {
					if (!$v instanceof MongoId) {
						try {
							$v = new MongoId($v);
						} catch (MongoException $e) {
							$this->addLog('this.MongoId('. (is_array($v) || is_object($v) ? json_encode($v) : $v) .')', $e->getMessage(), 1, $e->getCode());
						}
					}
				}
			}
		} else {
			$_id || $this->addLog('this.MongoId()', '_id is empty', 1);
			try {
				$_id = new MongoId($_id);
			} catch (MongoException $e) {
				$this->addLog('this.MongoId('. (is_array($v) || is_object($v) ? json_encode($v) : $v) .')', $e->getMessage(), 1, $e->getCode());
			}
		}
		return $_id;
	}


	private function _command(array $command, array $options = []) {
		$queryString = 'this.command('.json_encode($command).', '. json_encode($options).' )';
		$command || $this->addLog($queryString, 'Command is empty', 1);
		$link = $this->link(false);

		++self::$querySum;
		try {
			$results = $link->command($command, $options);
			$this->addLog($queryString, $results);
		} catch (MongoException $e) {
			$this->addLog($queryString, $e->getMessage(), 1, $e->getCode());
		}
		if (!empty($results['err']) || !empty($results['errmsg']) ) {
			$this->addLog($queryString, $results, 1);
		}
		return $results;
	}


	public function sort($a, $b) {
		foreach ($this->_sort as $k => $v) {
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