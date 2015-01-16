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
/*	Updated: UTC 2015-01-16 09:43:30
/*
/* ************************************************************************** */
namespace Loli;
class Page{

	// 总共数量
	public static $count = 0;

	public static $limit = 0;

	public static $maxLimit = 50;

	public static $defaultLimit = 10;


	public static $offset = false;

	public static $maxOffset = 0;

	public static $isOffset = -1;



	// url 连接
	public static $url = false;

	// 更多使用的
	public static $more = [];

	// 上一页
	public static $prev = 'Prev';

	// 下一页
	public static $next = 'Next';

	// 点符号
	public static $dot = '&hellip;';

	public static $info = true;

	public static $end = 1;

	public static $mid = 3;

	// 保留的参数
	public static $query = ['$limit', '$orderby', '$order'];
	public static function lang($a) {
		return Lang::get($a, ['page', 'default']);
	}
	public static function count(){
		return self::$count;
	}
	// 每页数量
	public static function limit() {
		if (self::$limit) {
			return self::$limit;
		}
		return self::$limit = ($limit = absint(r('$limit'))) && $limit <= self::$maxLimit ? $limit : self::$defaultLimit;
	}

	// 偏移
	public static function offset() {
		if (self::$offset !== false) {
			return self::$offset;
		}
		if (self::isOffset()) {
			return self::$offset = absint(r('$offset'));
		}
		return self::$offset = empty($_REQUEST['$page']) || $_REQUEST['$page'] < 0 ? 0 : ($_REQUEST['$page'] - 1) * self::limit();
	}

	// 是否允许偏移
	public static function isOffset() {
		if (self::$isOffset == -1) {
			self::$isOffset = (self::$url && strpos('offset=' . self::value(), self::$url)) || (isset($_REQUEST['$offset']) && (!self::$url || !strpos('page=' . self::value(), self::$url)));
		}
		return self::$isOffset;
	}

	// key 的值
	public static function value() {
		static $uniqid;
		if (!isset($uniqid)) {
			$uniqid = uniqid(mt_rand(), true);
		}
		return $uniqid;
	}

	// 当前页面
	public static function current() {
		return ceil(self::offset() / self::limit()) + 1;
	}

	// 最大页面
	public static function maximum() {
		return  ($r = ceil(self::count() / self::limit())) < 1 ? 1 : $r + (self::offset() % self::limit() ? 1 : 0);
	}


	public static function get($type = 'plain') {
		if (!self::count() < 0 || ($maximum = self::maximum()) <= 1) {
			return false;
		}
		$current = self::current();

		$value = self::value();
		$url = self::url();

		// 选择页面信息
		if (self::count() && self::$info) {
			$arr[] = ['name' => $current .' / ' . $maximum, 'class' => ['info'], 'url' => ''];
		}

		if (self::isOffset()) {
			$limit = self::limit();
			$offset = self::offset();
			$count = self::count();

			// 上一页
			if ($offset > 0 && self::$prev) {
				$arr[] = ['name' => self::lang(self::$prev), 'class' => ['prev'], 'url' => strtr($url, [$value => max($offset - $limit, 0)])];
			}

			for ($i = 1; $i <= $maximum; $i++) {
				if ($maximum == 1) {
				} elseif ($i == $current) {
					$arr[] = ['name' => $i, 'class' => ['current', 'page-' . $i], 'url' => strtr($url, [$value => max(($offset % $limit) + (($i - 2) * $limit), 0)])];
					$dot = true;
				} else {
					if (($i <= self::$end || ($current && $i >= $current - self::$mid && $i <= $current + self::$mid) || $i > $maximum - self::$end)) {
						$arr[] = ['name' => $i, 'class' => ['page-' . $i], 'url' => strtr($url, [$value => max(($offset % $limit) + (($i - 2) * $limit), 0)])];
						$dot = true;
					} elseif (self::$dot && $dot) {
						$arr[] = ['name' => self::lang(self::$dot), 'class' => ['dot'], 'url' => ''];
						$dot = false;
					}
				}
			}


			// 下一页
			if (self::$next && ($offset + $limit) < $count) {
				$arr[] = ['name' => self::lang(self::$next), 'class' => ['next'], 'url' => strtr($url, [$value => $offset + $limit])];
			}
		} else {
			$current = self::current();

			// 上一页
			if ($current > 1 && self::$prev) {
				$arr[] = ['name' => self::lang(self::$prev), 'class' => ['prev'], 'url' => strtr($url, [$value => $current - 1])];
			}

			for ($i = 1; $i <= $maximum; ++$i) {
				if ($maximum == 1) {
				} elseif ($i == $current) {
					$arr[] = ['name' => $i, 'class' => ['current', 'page-' . $i], 'url' => strtr($url, [$value => $i])];
					$dot = true;
				} else {
					if (($i <= self::$end || ($current && $i >= $current - self::$mid && $i <= $current + self::$mid) || $i > $maximum - self::$end)) {
						$arr[] = ['name' => $i, 'class' => ['page-' . $i], 'url' => strtr($url, [$value => $i])];
						$dot = true;
					} elseif (self::$dot && $dot) {
						$arr[] = ['name' => self::lang(self::$dot), 'class' => ['dot'], 'url' => ''];
						$dot = false;
					}
				}
			}

			// 下一页
			if (self::$next && $current < $maximum) {
				$arr[] = ['name' => self::lang(self::$next), 'class' => ['next'], 'url' => strtr($url, [$value => $current + 1])];
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

	public static function url($more = false) {
		$value = self::value();
		if (!self::$url) {
			$parse = parse_url(current_url());
			$parse['query'] = empty($parse['query']) ? [] : parse_string($parse['query']);
			$parse['query'][self::isOffset() ? '$offset': '$page'] = $value;
			$parse['query'] = merge_string($parse['query']);
			self::$url = merge_url($parse);
		}
		if (strpos(self::$url, $value) === false) {
			self::$url .= (strpos(self::$url, '?') === false ? '?' : '&') . urlencode(self::isOffset() ? '$offset': '$page').'=' . $value;
		}
		$url = self::$url;

		if (self::$query) {
			$parse = parse_url($url);
			$parse['query'] = empty($parse['query']) ? [] : parse_string($parse['query']);
			$g = [];
			foreach (self::$query as $v) {
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


	public static function prev() {
		if (($current = self::current()) > 1) {
			return strtr(self::url(), [self::value() => self::isOffset() ? max(self::offset() - self::limit(), 0) : $current - 1]);
		}
		return false;
	}

	public static function next() {
		if (($count = self::offset() + self::limit()) < self::count()) {
			return strtr(self::url(), [self::value() => self::isOffset() ? $count : self::current() + 1]);
		}
		return self::more();
	}

	public static function more() {
		if (!self::$more ||self::count() < self::limit()) {
			return false;
		}
		$parse = parse_url(current_url());
		$parse['query'] = empty($parse['query']) ? [] : parse_string($parse['query']);
		unset($parse['query']['$offset'], $parse['query']['$page']);
		$parse['query'] = self::$more + $parse['query'];
		$parse['query'] = merge_string($parse['query']);
		return merge_url($parse);
	}
}
