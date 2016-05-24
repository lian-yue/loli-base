<?php
namespace Loli\Http\Message;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Loli\IP;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Stream;


class ResponseOutput{
	private $response;

	private $request;

	public function __construct(ResponseInterface $response, ServerRequestInterface $request = null) {
		$this->response = $response;
		$this->request = $request;
	}

	public function sendStatus() {
		if (headers_sent()) {
			return $this;
		}
		switch ($this->response->getProtocolVersion()) {
			case '1.0':
			case 1.0:
			case 1:
				$protocolVersion = '1.0';
				break;
			case '2.0':
			case 2.0:
			case 2:
				$protocolVersion = '2.0';
				break;
			default:
				$protocolVersion = '1.1';
		}
		header('HTTP/'. $protocolVersion .' '. $this->response->getStatusCode() .' ' . $this->response->getReasonPhrase());
		return $this;
	}

	public function sendHeaders() {
		if (headers_sent()) {
			return $this;
		}
		if (!$this->response->getHeader('Content-Type')) {
			$this->response =  $this->response->withHeader('Content-Type', 'text/html');
		}
		foreach ($this->response->getHeaders() as $name => $values) {
			$name = strtr(ucwords(strtr($name, '-', ' ')), ' ', '-');
			$replace = true;
			foreach ($values as $value) {
				$this->sendHeader($name, $value, $replace);
				$replace = false;
			}
		}
		return $this;
	}


	protected function sendHeader($name, $value, $replace = true) {
		if (headers_sent()) {
			return $this;
		}
		$value = trim($value,  " \t\n\r\0\x0B;");
		switch ($name) {
			case 'Cache-Control':
				$replace = true;
				break;
			case 'Content-Type':
				$replace = true;
				if (strpos($value, ';') === false && ($arrays = explode('/', strtolower($value))) && (in_array($arrays[0], ['text'], true) || (isset($arrays[1]) && in_array($arrays[1], ['javascript', 'x-javascript', 'js', 'json', 'plain', 'html', 'xml', 'css'], true)))) {
					$value = $value . '; charset=UTF-8';
				}
				break;
			case 'Content-Disposition':
				$replace = true;
				if (preg_match('/\s*(?:([0-9a-z_-]+)\s*;)?\s*filename\s*=\s*("[^"]+"|[^;]+)/i', $value, $matches)) {
					$type = trim($matches[1]);
					$filename = trim($matches[2], " \t\n\r\0\x0B\"");
					if (!$this->request || !($userAgent = $this->request->getHeader('User-Agent')) || strpos($userAgent, 'MSIE ') !== false || (strpos($userAgent, 'Trident/') !== false && strpos($userAgent, 'rv:') !== false && strpos($userAgent, 'opera') === false)) {
						$filename = str_replace(['+', '"'], ['%20', ''], urlencode($filename));
					}

					$value = [$type];
					if ($filename) {
						$value[] = 'filename="' . $filename . '"';
						$value[] = 'filename*=UTF-8 \'\'"'.$filename.'"';
					}
					$value = implode('; ', array_filter($value));
				}
				break;
			case 'Location':
				$replace = true;
				if (substr($value, 0, 2) === '//') {
					$value = ($this->request ? $this->request->getScheme() : 'http') . ':'. $value;
				}
				break;
		}
		header($name . ': '. $value, $replace);
		return $this;
	}

	public function sendBody() {
		$stream = $this->response->getBody();
		$stream->rewind();
		while (!$stream->eof()) {
			echo $stream->read(65536);
		}
		return $this;
	}

	public function send() {
		return $this->sendStatus()->sendHeaders()->sendBody();
	}

}
