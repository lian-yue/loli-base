<?php
namespace Loli\Http\Message;


use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\ServerRequestInterface;

use GuzzleHttp\Psr7\Request;
class ServerRequest extends Request implements ServerRequestInterface {

	private $serverParams = [];

	private $cookieParams = [];

	private $queryParams = [];

	private $uploadedFiles = [];

	private $parsedBody = NULL;

	private $attributes = [];

	private static $methodsList = ['HEAD', 'GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'PURGE', 'OPTIONS', 'TRACE'];

	private static $parsedBodyLength = 2097152;

	public function __construct($method, $uri, array $headers = [], $body = '', $protocolVersion = '1.1', array $parsedBody = null, array $uploadedFiles = [], array $serverParams = []) {
		if (!in_array($method = strtoupper($method), self::$methodsList, true)) {
			throw new \InvalidArgumentException('Unknown method');
		}
		$this->validateUploadedFiles($uploadedFiles);
		$this->serverParams  = $serverParams;
		$this->uploadedFiles  = $uploadedFiles;
		parent::__construct($method, $uri, $headers, is_array($body) ? http_build_query($body, null, '&') : $body, $protocolVersion);


		// query
		if ($queryString = $this->getUri()->getQuery()) {
			parse_str($queryString, $params);
			$this->queryParams = $params;
		}

		// cookie
		if ($cookieString = $this->getHeaderLine('Cookie')) {
			parse_str(preg_replace('/;\s*/', '&', $cookieString), $params);
			$this->cookieParams = $params;
		}

		// post
		if ($parsedBody !== null) {
			$this->parsedBody = to_array($parsedBody);
		} elseif (is_array($body)) {
			$this->parsedBody = to_array($body);
		} elseif (in_array($contentType = strtolower($this->getHeaderLine('Content-Type')), ['application/json', 'text/json'], true)) {
			$stream = $this->getBody();
			if ($stream->getSize() <= $stream) {
				$contents = $stream->getContents();
				$contents = json_decode($stream->getContents());
				$this->parsedBody = $contents ?: [];
			}
		} elseif ($contentType === 'application/x-www-form-urlencoded') {
			$stream = $this->getBody();
			if ($stream->getSize() <= $stream) {
				if ($contents = $stream->getContents()) {
					parse_str($contents, $parsedBody);
					$this->parsedBody = $parsedBody;
				} else {
					$this->parsedBody = [];
				}
			}
		}
    }



	public function getServerParams() {
		return $this->serverParams;
	}

	public function getCookieParams() {
		return $this->cookieParams;
	}

	public function withCookieParams(array $cookieParams) {
		$request = clone $this;
		$request->cookieParams = $cookieParams;
		return $request;
	}

	public function getQueryParams() {
		return $this->queryParams;
	}

	public function withQueryParams(array $queryParams) {
		$request = clone $this;
		$request->queryParams = $queryParams;
		return $request;
	}

	public function getUploadedFiles() {
		return $this->uploadedFiles;
	}

	public function withUploadedFiles(array $uploadedFiles) {
		$request = clone $this;
		$request->uploadedFiles = $uploadedFiles;
		return $request;
	}

	public function getParsedBody() {
		return $this->parsedBody;
	}

	public function withParsedBody($parsedBody) {
		if ($parsedBody !== NULL && is_scalar($parsedBody)) {
			throw new \InvalidArgumentException(__METHOD__ . '('. gettype($parsedBody) .') Unsupported Data Types');
		}
		$request = clone $this;
		$request->parsedBody = to_array($data);
		return $request;
	}

	public function getAttributes() {
		return $this->attributes;
	}

	public function getAttribute($name, $default = null) {
		if (isset($this->attributes[$name])) {
			return $this->attributes[$name];
		}
		return $default;
	}

	public function withAttribute($name, $value) {
		$request = clone $this;
		$request->attributes[$name] = $value;
		return $request;
	}

	public function withoutAttribute($name) {
		if (!isset($this->attributes[$name])) {
			return $this;
		}
		$request = clone $this;
		unset($request->attributes[$name]);
		return $request;
	}

	private function validateUploadedFiles(array $uploadedFiles) {
		foreach ($uploadedFiles as $file) {
			if (is_array($file)) {
				$this->validateUploadedFiles($file);
				continue;
			}
			if (!$file instanceof UploadedFileInterface) {
				throw new \InvalidArgumentException('Invalid leaf in uploaded files structure');
			}
		}
   }
}
