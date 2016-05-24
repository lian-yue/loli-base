<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-02-01 09:32:01
/*
/* ************************************************************************** */
namespace Loli;
use JsonSerializable;
use Psr\Http\Message\UriInterface;

class Assets implements UriInterface, JsonSerializable{
    private static $schemes = [
		80 => ['' => true, 'https' => true],
		443 => ['' => true, 'http' => true],
	];

    private $scheme = '';

    private $host = '';

    private $port;

    private $path = '';

    private $query = '';

    private $fragment = '';

	public function __construct($path = false, array $querys = []) {
        $this->path = $path;
        $this->query = http_build_query($querys + configure(['assets', 'query'], []), null, '&');
	}


    public function getScheme() {
        return $this->scheme;
    }

    public function getAuthority() {
		if (!$host = $this->getHost()) {
			return '';
		}
		$authority = $host;

		if ($this->port && !isset(self::$schemes[$this->port][$this->getScheme()])) {
			$authority .= ':' . $this->port;
		}

        return $authority;
	}

    public function getUserInfo() {
        return '';
    }

    public function getHost() {
        return $this->host ? $this->host : configure(['assets', 'host']);
    }

    public function getPort() {
		return $this->port;
	}


    public function getPath() {
        if (!$this->path || $this->path{0} === '/') {
            return $this->path;
        }
        if ($base = trim(configure(['assets', 'base']), '/')) {
            return '/' . $base . '/' . $this->path;
        }
        return '/'. $this->path;
	}

    public function getQuery() {
        return $this->query;
    }

    public function getFragment() {
        return $this->fragment;
    }

    public function withScheme($scheme) {
        if ($this->scheme === $scheme) {
            return $this;
        }
        $new = clone $this;
        $new->scheme = $scheme;
		if ($this->port && isset(self::$schemes[$this->port][$scheme])) {
			$new->port = null;
		}
        return $new;
    }

    public function withUserInfo($user, $password = null) {
        return $this;
    }

    public function withHost($host) {
        if ($this->host === $host) {
            return $this;
        }

        $new = clone $this;
        $new->host = $host;
        return $new;
    }

    public function withPort($port) {
		if ($port && isset(self::$schemes[$port][$this->getScheme()])) {
			$port = null;
		}

		if ($this->port === $port) {
			return $this;
		}

		$new = clone $this;
		$new->port = $port;
		return $new;
	}

    public function withPath($path) {
        if ($this->path === $path) {
            return $this;
        }
        $new = clone $this;
        $new->path = $path;
        return $new;
	}

    public function withQuery($query) {
		if ($this->query === $query) {
            return $this;
        }
        $new = clone $this;
        $new->query = $query;
        return $new;
	}


    public function withFragment($fragment) {
		if ($this->fragment === $fragment) {
            return $this;
        }
        $new = clone $this;
        $new->fragment = $fragment;
        return $new;
	}

    public function jsonSerialize() {
		return $this->__toString();
	}

	public function __toString() {
        $uri = '';

        if ($authority = $this->getAuthority()) {
			if ($scheme = $this->getScheme()) {
				$uri .= $scheme . ':';
			}
            $uri .= '//' . $authority;
        }

        if ($path = $this->getPath()) {
			$uri .= $path;
		}
        if ($query = $this->getQuery()) {
			$uri .= '?' . $query;
		}

        if ($this->fragment) {
			$uri .= '#' . $this->fragment;
		}

		return htmlencode(strtr($uri, ['{language}' => Locale::getLanguage(), '%7Blanguage%7D' => Locale::getLanguage()]));
	}
}
