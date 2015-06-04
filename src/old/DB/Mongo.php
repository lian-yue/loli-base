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
	 * @return [type]   [description]
	 */
	public function connect(array $args) {
		try {

			$server = empty($args['link']) ? 'mongodb://' . (empty($args['host']) ? MongoClient::DEFAULT_HOST : $args['host']) .':' . (empty($args['port']) ? MongoClient::DEFAULT_PORT : $args['port']) : $args['link'];

			// 链接到服务器
			$client = new MongoClient($server);

			// 表名为空
			if (empty($args['name'])) {
				throw new ConnectException('MongoClient('. $server .').selectDB()', 'Database name can not be empty');
			}
			// 链接到表
			$link = $client->selectDB($args['name']);

			// 有密码的
			if (!empty($args['pass'])) {
				$auth = @$link->authenticate($args['user'], $args['pass']);

				// 链接失败
				if (empty($auth['ok'])) {
					throw new ConnectException('this.MongoClient('. $server .').selectDB('. $args['name'] .')->authenticate()', $auth);
				}
			}
		} catch (MongoException $e) {
			throw new ConnectException('this.MongoClient('. $server .')', $e->getMessage(), $e->getCode());
		}
		return $link;
	}


	public function ping($slave = NULL) {
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
			$this->addLog($queryString);
		 	throw new Exception($queryString, $e->getMessage(), $e->getCode());
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
			$this->addLog($queryString);
		 	throw new Exception($queryString, $e->getMessage(), $e->getCode());
		}
		if (empty($results['ok'])) {
			throw new Exception($queryString, $results);
		}
		return empty($results['n']) ? 0 : $results['n'];
	}


	public function drop($table) {
		// http://docs.mongodb.org/manual/reference/command/drop/
		// http://docs.mongodb.org/manual/reference/method/db.collection.drop/
		// http://php.net/manual/en/mongocollection.drop.php
		// 没有表 返回 false
		$link = $this->link(false);
		$queryString = $table.'.drop()';

		// 删除
		try {
			$results = $link->{$table}->drop();
			$this->addLog($queryString, $results);
		} catch (MongoException $e) {
			$this->addLog($queryString);
		 	throw new Exception($queryString, $e->getMessage(), $e->getCode());
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
		if (empty($args['create'])) {
			throw new Exception($queryString, 'Collection named empty');
		}
		// 执行 command
		$results = $this->_command(array_intersect_key($args, ['create' => '', 'capped' => '', 'autoIndexId' => '', 'size' => '', 'max' => '', 'flags' => '', 'usePowerOf2Sizes' => '']));

		// 创建错误
		if (empty($results['ok'])) {
			throw new Exception($queryString, $results['errmsg']);
		}
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
		if (!$insert) {
			throw new Exception($queryString, 'Collection named empty');
		}
		// 文档为空
		if (!$documents) {
			throw new Exception($queryString, 'Insert the documents is empty');
		}

		// 整理数据
		foreach ($documents as $key => &$document) {
			if (!is_int($key)) {
				throw new Exception($queryString, 'Keys into the document format');
			}
			if (!is_array($document) && !is_object($document)) {
				throw new Exception($queryString, 'Insert the document content');
			}
			$document = (array) $document;
			ksort($document);
			if (!$document = array_unnull($document)) {
				throw new Exception($queryString, 'Insert the document is empty');
			}
			$document = (array) $this->_IDToObject($document);
		}
		unset($document);


		$link = $this->link(false);


		++self::$querySum;
		try {
			$queryString = $insert.'.batchInsert('. json_encode($documents) . ', ' .  json_encode($writeConcern).')';
			$results = $link->{$insert}->batchInsert($documents, $writeConcern);
			$this->addLog($queryString, $results);
		} catch (MongoException $e) {
			$this->addLog($queryString);
		 	throw new Exception($queryString, $e->getMessage(), $e->getCode());
		}

		// 插入失败的
		if (empty($results['ok'])) {
			throw new Exception($queryString, $results);
		}

		// 读最后个插入的id
		try {
			if ($document = $this->_IDToString(end($documents))) {
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
			} elseif (!empty($args['collection'])) {
				$args['save'] = $args['collection'];
			}
		}

		$args += ['save' => '', 'documents' => [], 'writeConcern' => []];
		extract($args, EXTR_SKIP);


		$writeConcernString = json_encode($writeConcern);
		$queryString = $save.'.batchReplace('. json_encode($documents) . ', ' .  $writeConcernString.')';

		// 集合为空
		if (!$save) {
			throw new Exception($queryString, 'Collection named empty');
		}
		// 文档为空
		if (!$documents) {
			throw new Exception($queryString, 'Replace the documents is empty');
		}

		// 整理数据
		foreach ($documents as $key => &$document) {
			if (!is_int($key)) {
				throw new Exception($queryString, 'Keys into the document format');
			}
			if (!is_array($document) && !is_object($document)) {
				throw new Exception($queryString, 'Replace the document content');
			}
			$document = (array) $document;
			ksort($document);
			if (!$document = array_unnull($document)) {
				throw new Exception($queryString, 'Replace the document is empty');
			}
			$document = (array) $this->_IDToObject($document);
		}
		unset($document);

		$link = $this->link(false);

		$results = [];
		foreach ($documents as $document) {
			++self::$querySum;
			try {
				$queryString = $save.'.save('. json_encode($document) . ', ' . $writeConcernString.')';
				$result = $link->{$save}->save($document, $writeConcern);
				$this->addLog($queryString, $result);
			} catch (MongoException $e) {
				$this->addLog($queryString);
				try {
	 				throw new Exception($queryString, $e->getMessage(), $e->getCode());
				} catch (Exception $e) {
					if (!$writeConcern['continueOnError']) {
						throw $e;
					}
				}
				continue;
			}

			// 插入错误
			if (empty($save['ok'])) {
				try {
	 				throw new Exception($queryString, $result);
				} catch (Exception $e) {
					if (!$writeConcern['continueOnError']) {
						throw $e;
					}
				}
				continue;
			}
			$results[] = $document;
		}

		// 读最后个插入的id
		try {
			if ($document = $this->_IDToString(end($documents))) {
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

		$queryString = 'this.update('. $update. ', ' . json_encode($updates).', '. ($writeConcern['continueOnError'] ? false : true) .', '. json_encode($writeConcern) .')';

		// 集合为空
		if (!$update) {
			throw new Exception($queryString, 'Collection named empty');
		}
		// 文档为空
		if (!$updates) {
			throw new Exception($queryString, 'Updates the documents is empty');
		}

		foreach ($updates as $key => &$value) {
			if (!is_int($key)) {
				throw new Exception($queryString, 'Keys into the document format');
			}
			if (!is_array($value) && !is_object($value)) {
				throw new Exception($queryString, 'Update the parameters');
			}
			$value = (array) $value;
			if (!isset($value['q']) && isset($value['query'])) {
				$value['q'] = $value['query'];
			}
			if (!isset($value['u']) && isset($value['update'])) {
				$value['u'] = $value['update'];
			}

			if (!isset($value['q'])) {
				throw new Exception($queryString, 'Update query is empty');
			}
			if (!is_array($value['q']) && !is_object($value['q'])) {
				throw new Exception($queryString, 'Update query format');
			}
			$value['q'] = $this->_IDToObject($value['q'], true);


			if (empty($value['u'])) {
				throw new Exception($queryString, 'Update document is empty');
			}
			if (!is_array($value['u']) && !is_object($value['u'])) {
				throw new Exception($queryString, 'Update document format');
			}

			if (!$value['u'] = array_unnull((array)$value['u'])) {
				throw new Exception($queryString, 'Update document is empty');
			}
			foreach ($value['u'] as $k => &$v) {
				if ($k && $k{0} == '$' && $v) {
					$v = $this->_IDToObject($v);
				} elseif ('_id' == $k) {
					$v = $this->_ID($v);
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
				$queryString = $update.'.update('. json_encode($value['q']) . ', ' . json_encode($value['u']) . ', ' . json_encode($options) .')';
				$result = $link->{$update}->update($value['q'], $value['u'], $options);
				$this->addLog($queryString, $result);
			} catch (MongoException $e) {
				$this->addLog($queryString);
				try {
	 				throw new Exception($queryString, $e->getMessage(), $e->getCode());
				} catch (Exception $e) {
					if (!$writeConcern['continueOnError']) {
						throw $e;
					}
				}
				continue;
			}

			if (empty($result['ok'])) {
				try {
	 				throw new Exception($queryString, $result);
				} catch (Exception $e) {
					if (!$writeConcern['continueOnError']) {
						throw $e;
					}
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

		$queryString = 'this.delete('. $delete. ', ' . json_encode($deletes).', '. ($writeConcern['continueOnError'] ? false : true) .', '. json_encode($writeConcern) .')';

		// 集合为空
		if (!$delete) {
			throw new Exception($queryString, 'Collection named empty');
		}
		// 文档为空
		if (!$deletes) {
			throw new Exception($queryString, 'Deletes the querys is empty');
		}



		foreach ($deletes as $key => &$value) {
			if (!is_int($key)) {
				throw new Exception($queryString, 'Keys into the document format');
			}
			if (!is_array($value) && !is_object($value)) {
				throw new Exception($queryString, 'Delete the parameters');
			}
			$value = (array) $value;
			if (!isset($value['q']) && isset($value['query'])) {
				$value['q'] = $value['query'];
			}
			if (!isset($value['q'])) {
				throw new Exception($queryString, 'Delete query is empty');
			}
			if (!is_array($value['q']) && !is_object($value['q'])) {
				throw new Exception($queryString, 'Delete query format');
			}
			$value['q'] = $this->_IDToObject($value['q'], true);
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
				$this->addLog($queryString);
				try {
	 				throw new Exception($queryString, $e->getMessage(), $e->getCode());
				} catch (Exception $e) {
					if (!$writeConcern['continueOnError']) {
						throw $e;
					}
				}
				continue;
			}

			if (empty($result['ok'])) {
				try {
	 				throw new Exception($queryString, $result);
				} catch (Exception $e) {
					if (!$writeConcern['continueOnError']) {
						throw $e;
					}
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



	public function select($args, $slave = true) {
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


			if (!$collection) {
				throw new Exception($queryString, 'Collection named empty');
			}
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
						$value = $this->_IDToObject($value, true);
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
					$results[] = (object) $this->_IDToString($result);
				}
				$this->addLog($queryString, $results);
			} catch (MongoException $e) {
				$this->addLog($queryString);
		 	throw new Exception($queryString, $e->getMessage(), $e->getCode());
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
			if (!$distinct) {
				throw new Exception($queryString, 'Collection named empty');
			}
			if (!is_array($query) && !is_object($query)) {
				throw new Exception($queryString, 'Query format');
			}
			$query = $this->_IDToObject($query, true);;


			try {
				++self::$querySum;
				$queryString = $distinct .'.distinct('. $key .', '. json_encode($query) .')';
				$results = $link->{$distinct}->distinct($key, $query);
				$this->addLog($queryString, $results);
			} catch (MongoException $e) {
				$this->addLog($queryString);
		 		throw new Exception($queryString, $e->getMessage(), $e->getCode());
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


			if (!$ns) {
				throw new Exception($queryString, 'Collection named empty');
			}
			if (!is_array($cond) && !is_object($cond)) {
		 		throw new Exception($queryString, 'Condition format');
			}
			$cond = $this->_IDToObject($cond, true);

			if (empty($key)) {
				throw new Exception($queryString, 'Key the is empty');
			}

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
				$this->addLog($queryString);
		 		throw new Exception($queryString, $e->getMessage(), $e->getCode());
			}
			$res = [];
			if (!empty($results['retval'])) {
				foreach ($results['retval'] as $retval) {
					++self::$queryRow;
					$res[] = (object) $this->_IDToString($retval);
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

		if (!$collection) {
			throw new Exception($queryString, 'Collection named empty');
		}

		if (!is_array($query) && !is_object($query)) {
			throw new Exception($queryString, 'Query format');
		}

		$query = $this->_IDToObject($query, true);


		// 常规查询
		try {
			$queryString = $collection . '.find('. json_encode($query) . ', '. json_encode($fields).')';
			$cursor = $link->{$collection}->find($query, $fields);
			if ($options) {
				foreach ($options as $name => $value) {
					$queryString .= '.addOption('. $name .', '. (is_array($value) || is_object($value) ? json_encode($value) : $value) .')';
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
				$results[] = (object) $this->_IDToString($value);
			}
			$this->addLog($queryString, $results);
		} catch (MongoException $e) {
			$this->addLog($queryString);
		 	throw new Exception($queryString, $e->getMessage(), $e->getCode());
		}
		return $results;
	}


	public function distinct($query, $slave = true) {
		return ($results = $this->_query($query, $slave)) ? $results : [];
	}
	public function aggregate($query, $slave = true) {
		return ($results = $this->_query($query, $slave)) ? $results : [];
	}
	public function group($query, $slave = true) {
		return ($results = $this->_query($query, $slave)) ? $results : [];
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

		if (!$count) {
			throw new Exception($queryString, 'Collection named empty');
		}
		if (!is_array($query) && !is_object($query)) {
			throw new Exception($queryString, 'Query format');
		}

		$query = $this->_IDToObject($query, true);

		$link = $this->link($slave);


		++self::$querySum;
		try {
			$options = array_intersect_key($options, ['hint' => '', 'limit' => '', 'skip' => '', 'maxTimeMS' => '']);

			$queryString = $count.'.count('. json_encode($query) . ', '. json_encode($options) .')';
			$result = $link->{$count}->count($query, $options);
			$this->addLog($queryString, $results);
		} catch (MongoException $e) {
			$this->addLog($queryString);
		 	throw new Exception($queryString, $e->getMessage(), $e->getCode());
		}
		return $result;
	}


	public function startTransaction() {
		throw new Exception('this.startTransaction()', 'Mongodb no transaction');
	}
	public function commit() {
		throw new Exception('this.commit()', 'Mongodb no transaction');
	}
	public function rollback() {
		throw new Exception('this.rollback()', 'Mongodb no transaction');
	}

	private function _IDToObject($args, $call = false, $logical = false) {
		if (!$call) {
			if (isset($args['_id'])) {
				$args['_id'] = $this->_ID($args['_id'], $call);
			} elseif (isset($args->_id)) {
				$args->_id = $this->_ID($args->_id, $call);
			}
		} else {
			foreach ($args as $k => &$v) {
				if ($k === '_id') {
					$v = $this->_ID($v, $call);
				} elseif ($logical || ($k && $k{0} == '$')) {
					if ($v && (is_array($v) || is_object($v)) && ($logical || in_array($k, ['$gt', '$gte', '$in', '$lt', '$lte', '$ne', '$nin', '$and', '$nor', '$not', '$or', '$mod', '$all', '$elemMatch']))) {
						$v = $this->_IDToObject($v, $call, in_array($k, ['$and', '$nor', '$not', '$or']));
					}
				}
			}
		}
		return $args;
	}

	private function _IDToString($args) {
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

	private function _ID($_id, $call = false) {
		if ($_id instanceof MongoId) {
			return $_id;
		}
		if ($call && (is_array($_id) || is_object($_id))) {
			foreach ($_id as &$v) {
				if (is_array($v) || is_object($v)) {
					$v = $this->_ID($v, true);
				} else {
					if (!$v instanceof MongoId) {
						try {
							$v = new MongoId($v);
						} catch (MongoException $e) {
							throw new Exception('this.MongoId('. (is_array($v) || is_object($v) ? json_encode($v) : $v).')', $e->getMessage(), $e->getCode());
						}
					}
				}
			}
		} else {
			try {
				$_id = new MongoId($_id);
			} catch (MongoException $e) {
				throw new Exception('this.MongoId('. (is_array($v) || is_object($v) ? json_encode($v) : $v).')', $e->getMessage(), $e->getCode());
			}
		}
		return $_id;
	}


	private function _command(array $command, array $options = []) {
		$queryString = 'this.command('.json_encode($command).', '. json_encode($options).' )';
		if (!$command) {
			throw new Exception($queryString, 'Command is empty');
		}
		$link = $this->link(false);

		++self::$querySum;
		try {
			$results = $link->command($command, $options);
			$this->addLog($queryString, $results);
		} catch (MongoException $e) {
			$this->addLog($queryString);
		 	throw new Exception($queryString, $e->getMessage(), $e->getCode());
		}
		if (!empty($results['errmsg'])) {
		 	throw new Exception($queryString, $results['errmsg'], empty($results['code']) ? 0 : $results['code']);
		}
		if (!empty($results['err'])) {
		 	throw new Exception($queryString, $results['err']);
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