<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2015-02-20 10:39:37
/*	Updated: UTC 2015-02-20 13:06:13
/*
/* ************************************************************************** */
namespace Loli;
class URL{

	private $_scheme = false;
	private $_user = false;
	private $_password = false;
	private $_host = false;
	private $_port = false;
	private $_path = false;
	private $_query = false;
	private $_fragment = false;


	public function __construct($URL = false) {
		$URL && $this->setURL($URL);
	}

	public function getURL() {
		$URL = '';
		if ($this->_scheme) {
			$URL .= $this->_scheme . '://';
		} elseif ($this->_host) {
			$URL .= '//';
		}

		if ($this->_user) {
			$URL .= $this->_user;
		}

		if ($this->_password) {
			$URL .= ':' . $this->_password;
		}

		if ($this->_user || $this->_password) {
			$URL .= '@';
		}
		if ($this->_host) {
			$URL .= $this->_host;
		}
		if ($this->_port) {
			$URL .= ':'. $this->_port;
		}

		$URL .= '/' . ltrim($this->_path, '/');

		if ($this->_query) {
			$URL .= '?'. $this->_query;
		}

		if ($this->_fragment) {
			$URL .= '#'. $this->_fragment;
		}
		return $URL;
	}

	public function setURL($URL) {
		$this->_scheme = $this->_user = $this->_password = $this->_host = $this->_port = $this->_path = $this->_query = $this->_fragment = false;
		if ($array = parse_url($URL)) {
			foreach ($array as $key => $value) {
				$key = $key == 'pass' ? '_password' : '_'. $key;
				$this->$key = $value;
			}
		}
		return $this;
	}






	public function getScheme() {
		return $this->_scheme;
	}

	public function setScheme($scheme) {
		$this->_scheme = $scheme;
		return $this;
	}




	public function getUser() {
		return $this->_user;
	}

	public function setUser($user) {
		$this->_user = $user;
		return $this;
	}






	public function getPassword() {
		return $this->_password;
	}

	public function setPassword($password) {
		$this->_password = $password;
		return $this;
	}




	public function getHost() {
		return $this->_host;
	}

	public function setHost($host) {
		$this->_host = $host;
		return $this;
	}





	public function getPort() {
		return $this->_port;
	}

	public function setPort($port) {
		$this->_port = $port;
		return $this;
	}



	public function getPath() {
		return $this->_path;
	}

	public function setPath($path) {
		$this->_path = $path;
		return $this;
	}











	public function getQuery($name = false) {
		if ($name === false) {
			return $this->_query;
		}
		if ($this->_queryArray === false) {
			$this->_queryArray = parse_string($this->_query);
		}
		if ($name === true) {
			return $this->_queryArray;
		}
		return isset($this->_queryArray[$name]) ? $this->_queryArray[$name] : false;
	}

	public function addQuery($query) {
		$this->_queryArray = false;
		$this->_query = merge_string(($this->_query ? parse_string($this->_query) : []) + parse_string($query));
		return $this;
	}

	public function setQuery($query) {
		$this->_queryArray = false;
		$this->_query = is_array($query) || is_object($query) ? merge_string($query) : $query;
		return $this;
	}







	public function getFragment() {
		return $this->_fragment;
	}

	public function setFragment($fragment) {
		$this->_fragment = $fragment;
		return $this;
	}



	public function __toString() {
		return $this->getURL();
	}
}