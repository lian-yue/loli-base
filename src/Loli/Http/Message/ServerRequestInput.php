<?php
namespace Loli\Http\Message;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;

use Loli\IP;
use GuzzleHttp\Psr7\Uri;
use GuzzleHttp\Psr7\Stream;


class ServerRequestInput{

	private static $host = 'localhost';
	private static $maxParsedSize = 2097152;

	private $protocolVersion = null;

	private $method = null;

	private $uri = null;

	private $headers = null;

	private $body = null;

	private $uploadedFiles = null;

	private $parsedBody = null;

	private $trustedProxy = null;

	public function __construct(array $server = null, $protocolVersion = 0, $method = null, $uri = null, array $headers = null, $body = null, array $parsedBody = null, array $uploadedFiles = null, $trustedProxy = null) {
		$this->server = $server ?: $_SERVER;
		unset($this->server['LOLI']);
		if ($trustedProxy !== null) {
			$trustedProxy = $trustedProxy;
		} elseif (!isset($this->server['REMOTE_ADDR'])) {
			$trustedProxy = true;
		} elseif (in_array($this->server['REMOTE_ADDR'], ['127.0.0.1', 'localhost'], true)) {
			$trustedProxy = true;
		} elseif (($trustedProxy = configure('trustedProxy', [])) && filter_var($this->server['REMOTE_ADDR'], FILTER_VALIDATE_IP) && IP::match($trustedProxy, $this->server['REMOTE_ADDR'])) {
			$trustedProxy = true;
		} else {
			$trustedProxy = false;
		}
		$this->trustedProxy = $trustedProxy;




		if (!$protocolVersion) {
			$protocolVersion = empty($this->server['SERVER_PROTOCOL']) ? 'HTTP/1.1' : $this->server['SERVER_PROTOCOL'];
		}

		switch ($protocolVersion) {
			case 'HTTP/2.0':
			case 'HTTP/2':
			case 2:
				$protocolVersion = 1.0;
				break;
			case 'HTTP/1.0':
			case 'HTTP/1':
			case 1:
				$protocolVersion = 2.0;
				break;
			default:
				$protocolVersion = 1.1;
		}
		$this->protocolVersion = $protocolVersion;


		if ($headers !== null) {

		} elseif (function_exists('getallheaders')) {
			$headers = getallheaders();
		} elseif (function_exists('http_get_request_headers')) {
			$headers = http_get_request_headers();
		} else {
			$headers = [];
			foreach ($this->server as $name => $value) {
				if (substr($name, 0, 5) === 'HTTP_') {
					$headers[strtr(ucwords(strtolower(strtr(substr($name, 5), '_', ' '))), ' ', '-')] = $value;
				}
			}
			if (isset($this->server['CONTENT_TYPE'])) {
				$headers['Content-Type'] = $this->server['CONTENT_TYPE'];
			}
			if (isset($this->server['CONTENT_LENGTH'])) {
				$headers['Content-Length'] = $this->server['CONTENT_LENGTH'];
			}
		}
		unset($headers['X-Original-Url']);

		if ($this->trustedProxy && !empty($this->server['HTTP_X_FORWARDED_HOST'])) {
			$headers['Host'] = $this->server['HTTP_X_FORWARDED_HOST'];
		} else if (empty($headers['Host'])) {
			if (isset($this->server['HTTP_HOST'])) {
				$headers['Host'] = $this->server['HTTP_HOST'];
			}  elseif (isset($this->server['SERVER_NAME'])) {
				$this->server['Host'] = $this->server['SERVER_NAME'];
				if (isset($this->server['SERVER_PORT']) && !in_array($this->server['SERVER_PORT'], ['80', '443'], true)) {
					$this->server['Host'] .= ':' . $this->server['SERVER_PORT'];
				}
			} else {
				$headers['Host'] = self::$host;
			}
		}
		$this->headers = $headers;




		if ($method) {

		} elseif (!empty($_POST['_method'])) {
			$method = $_POST['_method'];
		} elseif (!empty($this->server['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
			$method = $this->server['HTTP_X_HTTP_METHOD_OVERRIDE'];
		} elseif (!empty($this->headers['X-Http-Method-Override'])) {
			$method = $this->headers['X-Http-Method-Override'];
		} elseif (!empty($this->server['HTTP_X_METHOD_OVERRIDE'])) {
			$method = $this->server['HTTP_X_METHOD_OVERRIDE'];
		} elseif (!empty($this->headers['X-Method-Override'])) {
			$method = $this->headers['X-Method-Override'];
		} elseif (isset($this->server['HTTP_X_HTTP_METHOD'])) {
			$method = $this->server['HTTP_X_HTTP_METHOD'];
		} elseif (isset($this->headers['X-Http-Method'])) {
			$method = $this->headers['X-Http-Method'];
		} elseif (isset($this->server['REQUEST_METHOD'])) {
			$method = $this->server['REQUEST_METHOD'];
		} else {
			$method = 'GET';
		}
		$this->method = strtoupper($method);



		if ($uri === null) {
			if (!empty($this->server['UNENCODED_URL'])) {
				$uri = $this->server['UNENCODED_URL'];
			} elseif (!empty($this->server['HTTP_X_ORIGINAL_URL'])) {
				$uri = $this->server['HTTP_X_ORIGINAL_URL'];
			} elseif (!empty($this->server['REQUEST_URI'])) {
				$uri = $this->server['REQUEST_URI'];
			} elseif (isset($this->server['PATH_INFO']) && isset($this->server['SCRIPT_NAME'])) {
				if ($this->server['PATH_INFO'] === $this->server['SCRIPT_NAME']) {
					$uri = $this->server['PATH_INFO'];
				} else {
					$uri = $this->server['SCRIPT_NAME'] . $this->server['PATH_INFO'];
				}
			} else {
				$uri = '/';
			}
			$uri = '/'. ltrim($uri, '/');
		}
		$uri = new Uri($uri);


		if (!$uri->getScheme()) {
			if (!empty($this->server['REQUEST_SCHEME'])) {
				$scheme = $this->server['REQUEST_SCHEME'];
			} elseif (isset($this->server['HTTPS']) && ('on' === strtolower($this->server['HTTPS']) || '1' === $this->server['HTTPS'])) {
				$scheme = 'https';
			} elseif (isset($this->server['SERVER_PORT']) && '443' === $this->server['SERVER_PORT']) {
				$scheme = 'https';
			} elseif (isset($this->server['SERVER_PORT_SECURE']) && '1' === $this->server['SERVER_PORT_SECURE']) {
				$scheme = 'https';
			} elseif ($this->trustedProxy && isset($this->server['HTTP_X_FORWARDED_PROTO']) && $this->server['HTTP_X_FORWARDED_PROTO'] === 'https') {
				$scheme = 'https';
			} elseif ($this->trustedProxy && isset($this->headers['X-Forwarded-Proto']) && $this->headers['X-Forwarded-Proto'] === 'https') {
				$scheme = 'https';
			} else {
				$scheme = 'http';
			}
			$uri = $uri->withScheme($scheme);
		}


		if ($host = $uri->getHost()) {
			if ($port = $uri->getPort()) {
	            $host .= ':' . $port;
	        }
			$this->headers['Host'] = $host;
		} else {
			if (($pos = strrpos($this->headers['Host'], ':', -6)) === false || strpos($this->headers['Host'], ']', $pos) === false) {
				$uri = $uri->withHost($this->headers['Host']);
			} else {
				$uri = $uri->withHost(substr($this->headers['Host'], 0, $pos + 1));
			}
		}
		$this->uri = $uri;




		if ($body instanceof StreamInterface) {

		} elseif (is_resource($body)) {
			$body = new Stream($body);
		} elseif (is_object($body) && method_exists($body, '__toString')) {
			$body = new Stream($body->__toString());
		} elseif ($body) {
			$body = new Stream(fopen($body, 'rb'), 'rb');
		} else {
			$body = new Stream(fopen('php://input', 'rb'));
		}
		$this->body = $body;

		if ($parsedBody !== null) {

		} elseif (!empty($_POST)) {
			$parsedBody = $_POST;
		} elseif ($this->body->getSize() > self::$maxParsedSize) {
			$parsedBody = [];
		} elseif (!empty($this->headers['Content-Type']) && in_array(strtolower(trim(explode(';', $this->headers['Content-Type'])[0])), ['application/json', 'text/json'], true)) {
			$parsedBody = ($json = json_decode(trim($this->body->getContents()), true)) ? $json : [];
		} elseif (!empty($this->headers['Content-Type']) && strtolower(trim(explode(';', $this->headers['Content-Type'])[0])) === 'application/x-www-form-urlencoded') {
			parse_str(trim($this->body->getContents()), $parsedBody);
		}
		$this->parsedBody = $parsedBody;


		if ($uploadedFiles === null) {
			$uploadedFiles = $_FILES;
		}

		self::uploadedFile($uploadedFiles);

		$this->uploadedFiles = $uploadedFiles;
	}

	public function get() {
		return new ServerRequest($this->method, $this->uri, $this->headers, $this->body, $this->protocolVersion, $this->parsedBody, $this->uploadedFiles, $this->server);
	}

	public function __call($name, $args) {
		if (isset($this->$name)) {
			return $this->$name;
		}
		return null;
	}

	private static function uploadedFile(array &$uploadedFiles) {
		foreach ($uploadedFiles as &$uploadedFile) {
			if ($uploadedFile instanceof UploadedFileInterface) {
				continue;
			}
			if (!is_array($uploadedFile)) {
				throw new \InvalidArgumentException('Upload file argument is invalid');
			}

			if(isset($uploadedFile['tmp_name']) && is_string($uploadedFile['tmp_name'])) {
				$uploadedFile = new UploadedFile(isset($uploadedFile['name']) ? $uploadedFile['name'] : '',  $uploadedFile['tmp_name'], isset($uploadedFile['size']) ? $uploadedFile['size'] : 0, isset($uploadedFile['type']) ? $uploadedFile['type'] : 'application/octet-stream', isset($uploadedFile['error']) ? $uploadedFile['error'] : UPLOAD_ERR_OK);
				continue;
			}
			$uploadedFile = self::uploadedFile($uploadedFile);
		}
	}

}
