<?php
/* ************************************************************************** */
/*
/*	Lian Yue
/*
/*	Url: www.lianyue.org
/*	Email: admin@lianyue.org
/*	Author: Moon
/*
/*	Created: UTC 2014-04-18 18:43:50
/*	Updated: UTC 2015-01-04 09:50:18
/*
/* ************************************************************************** */
namespace Loli;

class IP{

	/**
	*	ip 转换 数字
	*
	*	1 参数 ipv 地址
	*
	*	返回值 数字 类型字符串
	**/
	public static function long($ip) {
		if (strpos($ip, ':') === false) {
			return bindec(decbin(ip2long($ip)));
		}
		return gmp_strval(gmp_init(self::binary($ip), 2), 10);
	}


	/**
	*	ip 匹配
	*
	*	1 参数 ip 数组    支持  127.0.0.1     ::1     127.0.0.1/16    ::1/16    127.0.0.1-127.0.0.255   ::1-::f
	*	2 参数 匹配的ip
	*
	*	返回值 匹配到的ip数组 or false
	**/
	public static function match($network, $ip) {
		if (!$network || !$ip) {
			return false;
		}

		// 整理匹配数组
		$network = array_filter(array_map('trim', is_array($network) ? $network : explode("\n", $network)));

		// 是否 ipv6
		$ipv6 = (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6);

		// ascii 编码
		$pton = inet_pton($ip);

		// 2 进制
		$binary = self::binary($ip);

		foreach ($network as $v) {
			if ($ip == $v) {
				return $v;
			}
			if (((bool)strpos($v, ':')) === $ipv6) {
				if ($pos = strpos($v, '-')) {
					// ffff:fff0:: - ffff:ffff::
					// 192.168.0.1 - 192.168.0.255
					if ($pton >= inet_pton(trim(substr($v, 0, $pos))) && $pton <= inet_pton(trim(substr($v, $pos + 1)))) {
						return $v;
					}
				} elseif ($pos = strpos($v, '/')) {
					// 2407:f600::/32
					// 192.168.0.1/32
					$from = self::binary(trim(substr($v, 0, $pos)));
					$mask = trim(substr($v, $pos + 1));
					if (substr($from, 0, $mask) == substr($binary, 0, $mask)) {
						return $v;
					}
				} elseif ($pton == inet_pton($v)) {
					return $v;
				}
			}
		}
		return false;
	}

	/**
	*	ip 转换 成二进制
	*
	*	1 参数 ip 地址
	*
	*	返回值 二进制字符串
	**/
	public static function binary($ip) {
		$pton = inet_pton($ip);
		$n = strlen($pton) - 1;
		$r = '';
		while ($n >= 0) {
			$bin = sprintf("%08b",ord($pton[$n]));
			$r = $bin . $r;
			--$n;
		}
		return $r;
	}

	/**
	*	ip 和 CIDR 返回 开始 IP 和结束ip
	*
	*	1 参数 ip
	*	2 参数 cidr
	*	3 参数 是否返回值 2进制字符串
	*
	*	返回值 数组
	**/
	public static function cidr($ip, $cidr = 0, $is_binary = false) {
		$binary = self::binary($ip);
		$len = strlen($binary);
		$cidr = ($cidr = absint($cidr)) > $len ? $len : $cidr;
		$binary = substr($binary, 0, $cidr);

		$a[] = str_pad($binary, $len, '0', STR_PAD_RIGHT);
		$a[] = str_pad($binary, $len, '1', STR_PAD_RIGHT);

		if (!$is_binary) {
			foreach ($a as &$v) {
				$temp = '';
				foreach (str_split($v, 8) as $vv) {
					$temp .= chr(bindec($vv));
				}
				$v = strtolower(inet_ntop($temp));
			}
		}
		return $a;
	}



	/**
	*	ip 查询
	*
	*	1 参数 ip地址
	*
	*	返回值 bool
	**/
	public static function whois($ip) {
		$query = [];
		$ip .= "\n";
		$hostname = 'whois.iana.org';
		$port = 43;
		for ($i = 0; $i < 4; ++$i) {

			// 次服务器已查询过了
			if (isset($query[$hostname])) {
				break;
			}

			// 连接到服务器
			if (!$sock = fsockopen($hostname, $port, $errNum, $errStr, 6)) {
				return false;
			}

			// 发送ip 信息
			fputs($sock, $ip);

			// 获取返回值
			$data = '';
			while(!feof($sock)) {
				$data .= fgets($sock);
			}
			fclose($sock);

			// 没有数据
			if (!$data) {
				break;
			}

			$query[$hostname] = $data;
			if (!preg_match("/(?:^|\n)\s*(?:whois|refer|ReferralServer)\s*\:\s*(?:r?whois\:\/\/)?(whois\.arin\.net|whois\.lacnic\.net|whois\.ripe\.net|whois\.apnic\.net|whois\.afrinic\.net|rwhois\.frontiernet\.net)(?:\:(\d+))?\s*(?:\n|$|#|%)/i", $data, $matches) && $i) {
				break;
			}

			$hostname = empty($matches[1]) ? 'whois.arin.net' : $matches[1];
			$port = empty($matches[2]) ? 43 : $matches[2];
		}



		// 整理数据
		foreach ($query as $k => $v) {
			$temp = '';
			foreach (explode("\n", $v) as $vv) {
				$temp .= !($vv = trim($vv)) || in_array($vv{0},['#', '%', '~', '*']) ? "\n" : trim(explode('#', preg_replace('/^(?:\s*network\s*\:\s*)?(.+?)\s*(?:;I)?\s*\:\s*(.+)/i', '$1:$2', $vv))[0]) . "\n";
			}
			$query[$k] = preg_replace("/(\n\n)+/", "\n\n", trim($temp));
		}


		// IP 还没分配的
		foreach ($query as $v) {
			if (preg_match('/^No match found for|^Unallocated resource/i', $v)) {
				return false;
			}
		}

		// 过滤中间的
		$host = false;
		foreach ($query as $k => $v) {
			if (in_array($k, ['whois.lacnic.net', 'whois.ripe.net', 'whois.apnic.net', 'whois.afrinic.net'])) {
				if ($host) {
					unset($query[$host]);
				}
				$host = $k;
			}
		}


		foreach ($query as $k => $v) {
			// 过滤没用的
			$query[$k] = preg_replace("/\w+\:(\~|\#|\%|\*).+/i", '_:_', $v);
		}

		/*
		whois.arin.net  	美洲
		whois.lacnic.net	拉丁美洲和加勒比海
		whois.ripe.net		欧洲
		whois.apnic.net		亚洲
		whois.afrinic.net	非洲
		*/

		$_query = $query = array_reverse($query);


		foreach ($query as $k => $v) {
			// 过滤负责人
			$query[$k] = preg_replace("/(^|\n\n)(?:person|irt|route|nic\-hdl)\:.+?($|\n\n)/is", '$1_:_$2', $v);
		}

		// ip 段
		$inetnum = [];
		foreach ($query as $k => $v) {
			if (preg_match_all("/(?:^|\n)(?:(?:inetnum|inet6num|NetRange|CIDR|route6|Auth\-Area|IP\-Network)\:|[0-9a-z,. ]+\s+LVLT\-[a-z]{2,10}\-[0-9a-z-]+\s+\(NET\-[0-9a-z-]+\)\s+)((?:\d{1,3}(?:\.\d{1,3}){0,3})|[0-9a-z:]+(?:\:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})?)\s*([\/-])\s*((?:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})|[0-9a-z:]+(?:\:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})?|\d+)/i", $v, $matches)) {
				end($matches[0]);
				$k = key($matches[0]);
				if (preg_match('/\d+\.\d+/', $matches[1][$k])) {
					$matches[1][$k] = explode('.', $matches[1][$k]);
					while (count($matches[1][$k]) < 4) {
						$matches[1][$k][] = '0';
					}
					$matches[1][$k] = implode('.', $matches[1][$k]);
				}
				$inetnum = $matches[2][$k] == '/'  ? self::cidr($matches[1][$k], $matches[3][$k]) : [strtolower($matches[1][$k]), strtolower($matches[3][$k])];
				break;
			}
		}

		if (empty($inetnum)) {
			return false;
		}

		// 网络 名称 or 公司名称
		$netname = '';
		foreach ($query as $v) {
			if (preg_match_all("/(?:^|\n)(?:NetName|Net\-Name|ownerid|OrgId)\:(.+)/i", $v, $matches) || preg_match_all("/(?:^|\n)(?:OrgName|Org\-Name|organisation)\:(.+)/i", $v, $matches) || preg_match_all("/(?:^|\n)([0-9a-z,. ]+)\s+LVLT\-[a-z]{2,10}\-[0-9a-z-]+\s+\(NET\-[0-9a-z-]+\)\s+/i", $v, $matches)) {
				$netname = strtoupper(end($matches[1]));
				break;
			}
		}



		// 国家
		$country = '';
		foreach ($query as $v) {
			if (preg_match_all("/(?:^|\n)(?:Country|Country\-Code)\:([a-z]{2})/i", $v, $matches)) {
				$country = strtoupper(end($matches[1]));
				break;
			}
		}


		// 地址
		$address = '';
		foreach ($query as $v) {
			foreach (array_reverse(explode("\n\n", $v)) as $vv) {
				foreach (explode("\n", $vv) as $vvv) {
					if (count($vvv = explode(':', $vvv, 2)) && strtolower(trim($vvv[0])) == 'address') {
						$address .= trim($vvv[1]) ."\n";
					} elseif ($address) {
						break 2;
					}
				}
			}
		}
		if (!$address) {
			foreach ($_query as $v) {
				foreach (array_reverse(explode("\n\n", $v)) as $vv) {
					foreach (explode("\n", $vv) as $vvv) {
						if (count($vvv = explode(':', $vvv, 2)) && strtolower(trim($vvv[0])) == 'address') {
							$address .= trim($vvv[1]) ."\n";
						} elseif ($address) {
							break 2;
						}
					}
				}
			}
		}



		// 城市
		foreach ($query as $v) {
			if (preg_match_all("/(?:^|\n)(?:City)\:(.+)/i", $v, $matches)) {
				$address .= trim(end($matches[1])) .  " City\n";
				break;
			}
		}

		// 省
		foreach ($query as $v) {
			if (preg_match_all("/(?:^|\n)(?:StateProv)\:(.+)/i", $v, $matches)) {
				$address .= strtoupper(trim(end($matches[1]))) . " StateProv\n";
				break;
			}
		}

		$address = trim($address);



		// 描述
		$descr = '';
		foreach ($query as $v) {
			foreach (array_reverse(explode("\n\n", $v)) as $vv) {
				foreach (explode("\n", $vv) as $vvv) {
					if (count($vvv = explode(':', $vvv, 2)) && strtolower(trim($vvv[0])) == 'descr') {
						$descr .= trim($vvv[1]) ."\n";
					} elseif ($descr) {
						break 2;
					}
				}
			}
		}
		if (!$address) {
			foreach ($_query as $v) {
				foreach (array_reverse(explode("\n\n", $v)) as $vv) {
					foreach (explode("\n", $vv) as $vvv) {
						if (count($vvv = explode(':', $vvv, 2)) && strtolower(trim($vvv[0])) == 'descr') {
							$descr .= trim($vvv[1]) ."\n";
						} elseif ($descr) {
							break 2;
						}
					}
				}
			}
		}
		$descr = trim($descr);


		return ['i' => $inetnum[0], 'n' => $inetnum[1], 'netname' => $netname, 'country' => $country, 'descr' => $descr, 'address' => $address];
	}

	/**
	*	当前ip地址
	*
	*	无参数
	*
	*	返回当前用户的ip 地址
	**/
	public static function current(){
		return current_ip();
	}
}

