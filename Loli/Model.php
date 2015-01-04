<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-08 08:27:43
/*	Updated: UTC 2015-01-04 16:12:48
/*
/* ************************************************************************** */
namespace Loli;
trait Model{

	protected static $__DATA = [];

	protected $_DATA = [];

	public static $__COUNT = 0;

	public $__ID;

	final public static function __reg($key, $args = []) {
        if (isset(self::$__DATA[$key]) || !$args) {
            return false;
        }
        self::$__DATA[$key] = $args;
        return true;
    }

	final public static function __has($k) {
        return isset(self::$__DATA[$k]);
    }


	final public static function __remove($key) {
		if (empty(self::$__DATA[$key])) {
			return false;
		}
		unset(self::$__DATA[$key]);
		return true;
	}


	final public function _reg($key, $args = []) {
        if (isset($this->_DATA[$key]) || !$args) {
            return false;
        }
        $this->_DATA[$key] = $args;
        return true;
    }

	final public function _has($k) {
        return isset($this->_DATA[$k]);
    }


	final public function _remove($key) {
		if (empty($this->_DATA[$key])) {
			return false;
		}
		unset($this->_DATA[$key]);
		if (isset($this->$key)) {
			unset($this->$key);
		}
		return true;
	}



	public function __get($key) {
		++self::$__COUNT;

		// sub 的
		if (isset($this->_DATA[$key])) {
			$this->__ID || trigger_error('Unknown module ID', E_USER_ERROR);
			$ID = $this->__ID;
			$ID .= '\\' . $key;
			if (!empty($this->_DATA[$key]['file'])) {
				$this->$key = require $this->_DATA[$key]['file'];
			}
			if (!empty($this->_DATA[$key]['value'])) {
				$this->$key = $this->_DATA[$key]['value'];
			} elseif (!empty($this->_DATA[$key]['call'])) {
				$this->$key = call_user_func_array($this->_DATA[$key]['call'], $this, $key);
			} elseif (!empty($this->_DATA[$key]['class'])) {
				$this->$key = isset($this->_DATA[$key]['arg']) ? new $this->_DATA[$key]['class']($this->_DATA[$key]['arg']) : new $this->_DATA[$key]['class'];
			} elseif (!isset($this->$key) || $this->$key === 1 || $this->$key === true) {
				$class = 'Model\\' . $ID;
				$this->$key = isset($this->_DATA[$key]['arg']) ? new $class($this->_DATA[$key]['arg']) : new $class;
			}

			if ($this->$key instanceof Model) {
				$this->$key->__ID = $ID;
			}
			do_array_call('Model.' . $ID, [&$this->$key, $this]);
			if ($this->$key instanceof Model && !$this->$key->__ID) {
				$this->$key->__ID = $ID;
			}
			return $this->$key;
		}


		if (empty(self::$__DATA[$key]['run'])) {
			if (empty(self::$__DATA[$key])) {
				trigger_error('Not found Model: '.$key, E_USER_ERROR);
			}
			self::$__DATA[$key]['run'] = true;
			if (isset(self::$__DATA[$key]['value'])) {
				if (!empty(self::$__DATA[$key]['file'])) {
					require self::$__DATA[$key]['file'];
				}
			} else {
				if (!empty(self::$__DATA[$key]['file'])) {
					self::$__DATA[$key]['value'] = require self::$__DATA[$key]['file'];
				}
				if (!empty(self::$__DATA[$key]['call'])) {
					self::$__DATA[$key]['value'] = call_user_func_array(self::$_DATA[$key]['call'], $this, $key);
				} elseif (!empty(self::$__DATA[$key]['class'])) {
					self::$__DATA[$key]['value'] = isset(self::$__DATA[$key]['arg']) ? new self::$__DATA[$key]['class'](self::$__DATA[$key]['arg']) : new self::$__DATA[$key]['class'];;
				} elseif (!isset(self::$__DATA[$key]['value']) || self::$__DATA[$key]['value'] === 1 || self::$__DATA[$key]['value'] === true) {
					$class = 'Model\\' . $key;
					self::$__DATA[$key]['value'] = isset(self::$__DATA[$key]['arg']) ? new $class(self::$__DATA[$key]['arg']) : new $class;
				}
			}

			if (self::$__DATA[$key]['value'] instanceof Model) {
				self::$__DATA[$key]['value']->__ID = $key;
			}
			do_array_call('Model.' . $key, [&self::$__DATA[$key]['value'], $this]);
			if (self::$__DATA[$key]['value'] instanceof Model && !self::$__DATA[$key]['value']->__ID) {
				 self::$__DATA[$key]['value']->__ID = $key;
			}
		}
		return self::$__DATA[$key]['value'];
	}

	public function __call($key, $args) {
		return call_user_func_array($this->$key, $args);
	}
}