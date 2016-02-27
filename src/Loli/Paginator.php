<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2016-01-27 05:40:11
/*
/* ************************************************************************** */
namespace Loli;
use JsonSerializable;
use Psr\Http\Message\UriInterface;


use GuzzleHttp\Psr7\Uri;

class Paginator implements JsonSerializable{
	protected $uri;

	protected $key = 'page';

	protected $current = 1;

	protected $limit = 20;

	protected $total = 0;

	protected $max = 0;

	protected $for = 3;

	protected $ellipsis = true;

	public function __construct($uri = null, $current = false, $limit = 20) {
		$this->uri = $uri ? $uri : Route::request()->getUri();
		$this->current = $current ? $current : Route::request()->getParam($this->key, 1);
		$this->limit = $limit;
	}

	public function __get($name) {
		switch ($name) {
			case 'start':
				return 1;
				break;
			case 'end':
				$end = intval($this->total / $this->limit) + 1;
				if ($this->max) {
					$end = $this->max;
				}
				return $end;
				break;
			case 'prev':
				if ($this->current > 1) {
					return $this->current - 1;
				}
				return false;
				break;
			case 'next':
				if ($this->end > $this->current) {
					return 1 + $this->current;
				}
				return false;
				break;
			case 'items':
				$current = $this->current;
				$min = max(1, $current - $this->for);
				$max = min($current + $this->for, $this->end);

				$items = [];

				$items[] = ['type' => 'prev', 'value' => self::translate('&laquo; Previous'), 'uri' => ($prev = $this->prev) ? $this->uri($prev) : false];

				if ($this->ellipsis && $min > 1) {
					$items[] = ['type' => 'ellipsis', 'value' => self::translate('...')];
				}
				for ($i = $min; $i < $max; $i++) {
					$items[] = ['type' => $current === $i ? 'current' : 'uri', 'value' => $i, 'uri' => $this->uri($i)];
				}
				if ($this->ellipsis && $max < $current) {
					$items[] = ['type' => 'ellipsis', 'value' => self::translate('...')];
				}
				$items[] = ['type' => 'next', 'value' => self::translate('Next &raquo;'), 'uri' => ($next = $this->next) ? $this->uri($next) : false];

				return $items;
				break;
			default:
				if (isset($this->$name)) {
					return $this->$name;
				}
		}
		return NULL;
	}


	public function __set($name, $value) {
		switch ($name) {
			case 'uri':
				if (!$value instanceof UriInterface) {
					$value = new Uri($value);
				}
				$this->uri = $value;
				break;
			case 'key':
				$this->key = to_string($value);
				break;
			case 'limit':
				if ($value < 1) {
					$value = 1;
				}
				$this->limit = (int) $value;
				break;
			case 'current':
				if ($value < 1) {
					$value = 1;
				}
				if ($this->max && $value > $this->max) {
					$value = $this->max;
				}
				$this->current = (int) $value;
				break;
			case 'total':
				if ($value < 0) {
					$value = 0;
				}
				$this->total = (int) $value;
				break;
			case 'ellipsis':
				$this->ellipsis = (int) $value;
				break;
			case 'max':
				$this->__set('max', $this->current);
				$this->max = (int) $value;
			default:
				throw new Exception(__METHOD__. '('. $name .') Paginator set name');
		}
	}


	public function uri($page) {
		if ($query = $this->uri->getQuery()) {
			parse_str($query, $queryParams);
		} else {
			$queryParams = [];
		}
		$queryParams[$this->key] = $page;
		return $this->uri->withQuery(http_build_query($query, null, '&'));
	}


	public function jsonSerialize() {
		$array = [];
		foreach (['start', 'end', 'prev', 'next', 'uri', 'key', 'current', 'limit','total', 'items'] as $name) {
			$array[$name] = $this->__get($name);
		}
		$array['uri'] = $this->uri('{page}');
		$array['items'] = $this->items;
		return $array;
	}

	public function __toString() {
		$results = '<ul class="pagination">';
		foreach($this->items as $item) {
			if (empty($item['uri'])) {
				$results .= '<li class="disabled '. $item['type'] .'"><span>'. $item['value'] .'</span></li>';
			} else {
				$class = $item['type'];
				if ($item['type'] === 'current') {
					$class .= ' active';
				}
				$results .= '<li class="'. $class .'"><a href="'. $item['uri'] .'" ' . (in_array($item['type'], ['prev', 'next'], true) ? 'rel="'. $item['type'] .'"' : '') . '>'. $item['value'] .'</span></li>';
			}
		}
		$results .='</ul>';
	}

	public function __call($name, $args) {
		switch (substr($name, 0, 3)) {
			case 'get':
				if ($this->__isset($name = snake(substr($name, 3)))) {
					return $this->__get($name);
				}
				break;
			case 'add':
				if (!$this->__isset($name = snake(substr($name, 3)))) {
					$this->__set($name, $args ? $args[0] : NULL);
				}
				return $this;
				break;
			case 'set':
				$this->__set(snake(substr($name, 3)), $args ? $args[0] : NULL);
				return $this;
				break;
			default:
				if (($value = $this->__get($name)) && ($value instanceof Closure || (is_object($value) && method_exists($value, '__invoke')))) {
					return $value(...$args);
				}
		}
		throw new Exception(__METHOD__. '('. $name .') Method does not exist');
	}

	public static function translate($text, $original = true) {
		return Language::translate($text, ['paginator', 'default'], $original);
	}
}
