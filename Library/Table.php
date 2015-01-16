<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-12 06:47:03
/*	Updated: UTC 2015-01-03 10:41:29
/*
/* ************************************************************************** */
namespace Loli;
class Table{



	/**
	*	form table 表单
	*
	*	1 参数 arr 数组
	*	2 参数 是否显示 默认 true
	*	3 参数 附加class
	**/
	public static function form($a, $echo = true, $class = '') {
		$class = (array) $class;
		$class[] = 'table-form';
		$r = '<table class="' . implode(' ', $class) .'" >';
		$r .= '<tbody>';
		$i = 0;
		foreach ($a as $k => $v) {
			$v = is_string($v) ? ['value' => $v, 'class' => []] : $v + ['class' => []];
			if (isset($v['value']) && is_array($v['value'])) {
				$v['value'] = Form::get($v['value'], false);
			}

			$class =  [$i % 2 ? 'odd' : 'even'];
			if ($v['class']) {
				$class = array_merge($class, (array) $v['class']);
			}
			if (!is_numeric($k)) {
				$class[] ='tr-'. $k;
			}
			if (isset($v['title']) || isset($v['value'])) {
				$r .= '<tr class="'. implode(' ', $class) .'">';
				if (isset($v['title'])) {
					$r .= '<th class="title" ' . (isset($v['value']) ? '' : 'colspan="2"') . '>' .$v['title']. '</th>';
				}
				if (isset($v['value'])) {
					$r .= '<td class="value" ' . (isset($v['title']) ? '' : 'colspan="2"') . '>' .$v['value']. '</td>';
				}
				$r .= '</tr>';
			}
			++$i;
		}
		$r .= '</tbody>';
		$r .= '</table>';
		if (!$echo) {
			return $r;
		}
		echo $r;
	}


	/**
	*	table 表单
	*
	*	1 参数 表单内容
	*	2 参数 表头
	*	3 参数 是否显示
	*	4 参数 class
	**/
	public static function lists($tbody = [], $thead = [], $echo = true, $class = '') {
		$class = (array) $class;
		$class[] = 'table-lists';
		$r = '<table class="'. implode(' ', $class) .'" >';
		if ($thead) {
			$r .= '<thead>';
			$r .= '<tr>';
			foreach ($thead as $k => $v) {
				if (is_array($v)) {
					$v += ['url' => '', 'value' => ''];


					// 自动设置url
					if (!$v['url'] || is_array($v['url'])) {
						$parse = parse_url(current_url());
						$parse['query'] = empty($parse['query']) ? [] : parse_string($parse['query']);
						if (is_array($v['url'])) {
							$parse['query'] = array_intersect_key($parse['query'],  ['$order' => null] + array_flip($v['url']));
						}
						$parse['query']['$orderby'] = $k;
						$parse['query']['$order'] = empty($parse['query']['$order']) || strtoupper($parse['query']['$order']) != 'ASC' ? 'ASC' : 'DESC';
						$parse['query'] = merge_string($parse['query']);
						$v['url'] = merge_url($parse);
					} else {
						$parse = parse_url($v['url']);
					}
					$parse['query'] = empty($parse['query']) ? [] : parse_string($parse['query']);
					$order = '';
					if (g('$orderby') == $k) {
						if ($parse['query'] && !empty($parse['query']['$order']) && strtoupper($parse['query']['$order']) == 'ASC') {
							$order = 'desc';
						} else {
							$order = 'asc';
						}
					}
					$r .= '<td class=" td-'. $k .' '. $order .'"><a href="'. $v['url'] .'"><span>' . $v['value'] . '</span><span class="sorting"></span></a></td>';
				} else {
					$r .= '<td class="td-'. $k .'"><span>' .$v. '</span></td>';
				}
			}
			$r .= '</tr>';
			$r .= '</thead>';
		}
		$r .= '<tbody>';
		$i = 0;

		$all_key = array_keys($thead ? $thead : reset($tbody));
		foreach ($tbody as $k => $v) {
			$class =  [$i % 2 ? 'odd' : 'even'];
			if (!is_numeric($k)) {
				$class[] = $k;
			}
			$r .= '<tr class="' .implode(' ', $class). '" >';
			foreach ($all_key as $vv) {
				$r .= '<td class="td-'. $vv .'">' . (isset($v[$vv]) ? $v[$vv] : '') . '</td>';
			}
			$r .= '</tr>';
			$i++;
		}
		$r .= '</tbody>';
		$r .= '</table>';
		if (!$echo) {
			return $r;
		}
		echo $r;
	}
}