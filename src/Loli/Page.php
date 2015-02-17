<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-18 07:04:24
/*	Updated: UTC 2015-02-16 13:55:31
/*
/* ************************************************************************** */
namespace Loli;
class Page{

	// 总共数量
	public $count = 0;

	public $limit = 0;

	public $maxLimit = 50;

	public $defaultLimit = 10;


	public $offset = false;

	public $maxOffset = 0;

	public $isOffset = -1;



	// url 连接
	public $url = false;

	// 更多使用的
	public $more = [];

	// 上一页
	public $prev = 'Prev';

	// 下一页
	public $next = 'Next';

	// 点符号
	public $dot = '&hellip;';

	public $info = true;

	public $end = 1;

	public $mid = 3;

	// 保留的参数
	public $query = ['$limit', '$orderby', '$order'];



	public function __construct($url = false, $offset = false, $limit = false, $maxLimit = 50, $maxOffset = 0) {
		$this->url = $url;
		$this->offset = $offset;
		$this->limit = $limit;
		$this->maxLimit = $maxLimit;
		$this->maxOffset = $maxOffset;
	}


	public function lang($a) {
		return Lang::get($a, ['page', 'default']);
	}


	// 总共数量
	public function count() {
		return $this->count;
	}

	// 每页数量
	public function limit() {
		if ($this->limit) {
			return $this->limit;
		}
		$limit = (int) r('$limit', 0);
		return $this->limit = $limit > 0 && $limit <= $this->maxLimit ? $limit : $this->defaultLimit;
	}

	// 偏移
	public function offset() {
		if ($this->offset === false) {
			if ($this->isOffset()) {
				$offset = ($offset = (int) r('$offset', 0)) < 0 ? 0 : $offset;
			} else {
				$offset = ($page = (int) r('$page', 1)) < 0 ? 0 : ($page - 1) * $this->limit();
			}
			if ($offset && $this->maxOffset && $offset > $this->maxOffset) {
				$offset = $this->maxOffset;
			}
			 $this->offset = $offset;
		}
		return $this->offset;
	}

	// 是否允许偏移
	public function isOffset() {
		if ($this->isOffset == -1) {
			$this->isOffset = ($this->url && strpos('offset=' . $this->value(), $this->url)) || (isset($_REQUEST['$offset']) && (!$this->url || !strpos('page=' . $this->value(), $this->url)));
		}
		return $this->isOffset;
	}

	// key 的值
	public function value() {
		$uniqid;
		if (!isset($uniqid)) {
			$uniqid = uniqid(mt_rand(), true);
		}
		return $uniqid;
	}

	// 当前页面
	public function current() {
		return ceil($this->offset() / $this->limit()) + 1;
	}

	// 最大页面
	public function maximum() {
		return  ($r = ceil($this->count() / $this->limit())) < 1 ? 1 : $r + ($this->offset() % $this->limit() ? 1 : 0);
	}


	public function get($type = 'plain') {
		if (!$this->count() < 0 || ($maximum = $this->maximum()) <= 1) {
			return false;
		}
		$current = $this->current();

		$value = $this->value();
		$url = $this->url();

		// 选择页面信息
		if ($this->count() && $this->info) {
			$arr[] = ['name' => $current .' / ' . $maximum, 'class' => ['info'], 'url' => ''];
		}

		if ($this->isOffset()) {
			$limit = $this->limit();
			$offset = $this->offset();
			$count = $this->count();

			// 上一页
			if ($offset > 0 && $this->prev) {
				$arr[] = ['name' => $this->lang($this->prev), 'class' => ['prev'], 'url' => strtr($url, [$value => max($offset - $limit, 0)])];
			}

			for ($i = 1; $i <= $maximum; $i++) {
				if ($maximum == 1) {
				} elseif ($i == $current) {
					$arr[] = ['name' => $i, 'class' => ['current', 'page-' . $i], 'url' => strtr($url, [$value => max(($offset % $limit) + (($i - 2) * $limit), 0)])];
					$dot = true;
				} else {
					if (($i <= $this->end || ($current && $i >= $current - $this->mid && $i <= $current + $this->mid) || $i > $maximum - $this->end)) {
						$arr[] = ['name' => $i, 'class' => ['page-' . $i], 'url' => strtr($url, [$value => max(($offset % $limit) + (($i - 2) * $limit), 0)])];
						$dot = true;
					} elseif ($this->dot && $dot) {
						$arr[] = ['name' => $this->lang($this->dot), 'class' => ['dot'], 'url' => ''];
						$dot = false;
					}
				}
			}


			// 下一页
			if ($this->next && ($offset + $limit) < $count) {
				$arr[] = ['name' => $this->lang($this->next), 'class' => ['next'], 'url' => strtr($url, [$value => $offset + $limit])];
			}
		} else {
			$current = $this->current();

			// 上一页
			if ($current > 1 && $this->prev) {
				$arr[] = ['name' => $this->lang($this->prev), 'class' => ['prev'], 'url' => strtr($url, [$value => $current - 1])];
			}

			for ($i = 1; $i <= $maximum; ++$i) {
				if ($maximum == 1) {
				} elseif ($i == $current) {
					$arr[] = ['name' => $i, 'class' => ['current', 'page-' . $i], 'url' => strtr($url, [$value => $i])];
					$dot = true;
				} else {
					if (($i <= $this->end || ($current && $i >= $current - $this->mid && $i <= $current + $this->mid) || $i > $maximum - $this->end)) {
						$arr[] = ['name' => $i, 'class' => ['page-' . $i], 'url' => strtr($url, [$value => $i])];
						$dot = true;
					} elseif ($this->dot && $dot) {
						$arr[] = ['name' => $this->lang($this->dot), 'class' => ['dot'], 'url' => ''];
						$dot = false;
					}
				}
			}

			// 下一页
			if ($this->next && $current < $maximum) {
				$arr[] = ['name' => $this->lang($this->next), 'class' => ['next'], 'url' => strtr($url, [$value => $current + 1])];
			}
		}

		if ($type == 'array') {
			return $r;
		}
		$r = [];
		foreach ($arr as $k => $v) {
			$v['tag'] = $v['url'] ? 'a' : 'span';
			$v['url'] = $v['url'] ? 'href="'. $v['url'] .'"' : '';
			$v['class'] = implode(' ', $v['class']);
			$r[$k] = '<' .$v['tag'].' class="' .$v['class'] . ' page" '.$v['url'].'>' . $v['name'] . '</'. $v['tag']. '>';
		}
		if ($type == 'list') {
			return  '<ul class="page-nav"><li>' . join('</li><li>', $r) .  '</li></ul>';
		}
		return join('', $r);
	}

	public function url($more = false) {
		$value = $this->value();
		if (!$this->url) {
			$parse = parse_url(current_url());
			$parse['query'] = empty($parse['query']) ? [] : parse_string($parse['query']);
			$parse['query'][$this->isOffset() ? '$offset': '$page'] = $value;
			$parse['query'] = merge_string($parse['query']);
			$this->url = merge_url($parse);
		}
		if (strpos($this->url, $value) === false) {
			$this->url .= (strpos($this->url, '?') === false ? '?' : '&') . urlencode($this->isOffset() ? '$offset': '$page').'=' . $value;
		}
		$url = $this->url;

		if ($this->query) {
			$parse = parse_url($url);
			$parse['query'] = empty($parse['query']) ? [] : parse_string($parse['query']);
			$g = [];
			foreach ($this->query as $v) {
				if (isset($_REQUEST[$v]) && !isset($parse['query'][$v])) {
					$g[$v] = $_REQUEST[$v];
				}
			}
			if ($g && ($g = merge_string($g))) {
				$url .= $parse['query'] ? '&' . $g : '?' . $g;
			}
		}
		return $url;
	}


	public function prev() {
		if (($current = $this->current()) > 1) {
			return strtr($this->url(), [$this->value() => $this->isOffset() ? max($this->offset() - $this->limit(), 0) : $current - 1]);
		}
		return false;
	}

	public function next() {
		if (($count = $this->offset() + $this->limit()) < $this->count()) {
			return strtr($this->url(), [$this->value() => $this->isOffset() ? $count : $this->current() + 1]);
		}
		return $this->more();
	}

	public function more() {
		if (!$this->more ||$this->count() < $this->limit()) {
			return false;
		}
		$parse = parse_url(current_url());
		$parse['query'] = empty($parse['query']) ? [] : parse_string($parse['query']);
		unset($parse['query']['$offset'], $parse['query']['$page']);
		$parse['query'] = $this->more + $parse['query'];
		$parse['query'] = merge_string($parse['query']);
		return merge_url($parse);
	}
}