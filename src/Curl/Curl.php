<?php

//namespace Curl;

//use Curl\ArrayUtil;

require __DIR__ . '/ArrayUtil.php';
require __DIR__ . '/CaseInsensitiveArray.php';

class Curl
{
    /*
     * $user_agent = 'PHP-Curl-Class/' . self::VERSION . ' (+https://github.com/php-curl-class/php-curl-class)';
     */
    const VERSION = '7.2.0';

    /*
     * $this->setTimeout(self::DEFAULT_TIMEOUT);
     */
    const DEFAULT_TIMEOUT = 30;

    /*
     * $this->curl = curl_init();
     *
     * curl_set_opt($curl->curl, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1');
     * curl_close($curl->curl);
     */
    public $curl;

    /*
     * $this->id = uniqid('', true);
     */
    public $id = null;

    /*
     * $this->error = $this->curlError || $this->httpError;
     *
     * $curl->error
     */
    public $error = false;

    /*
     * $this->errorCode = $this->error ? ($this->curlError ? $this->curlErrorCode : $this->httpStatusCode) : 0;
     *
     * $curl->errorCode
     */
    public $errorCode = 0;

    /*
     * $this->errorMessage = $this->curlError ? $this->curlErrorMessage : $this->httpErrorMessage;
     *
     * $curl->errorMessage
     */
    public $errorMessage = null;

    /*
     * $this->curlError = !($this->curlErrorCode === 0);
     */
    public $curlError = false;

    /*
     * $this->curlErrorCode = curl_errno($this->curl);
     */
    public $curlErrorCode = 0;

    /*
     * $this->curlErrorMessage = curl_error($this->curl);
     */
    public $curlErrorMessage = null;

    /*
     * $this->httpError = in_array(floor($this->httpStatusCode / 100), array(4, 5));
     */
    public $httpError = false;

    /*
     * $this->httpStatusCode = $this->getInfo(CURLINFO_HTTP_CODE);
     */
    public $httpStatusCode = 0;

    /*
     * $this->httpErrorMessage = $this->responseHeaders['Status-Line'];
     */
    public $httpErrorMessage = null;

    /*
     * public function __construct($base_url = null)
     * $this->baseUrl = $url;
     */
    public $baseUrl = null;

    /*
     * return $url . (empty($data) ? '' : '?' . http_build_query($data, '', '&'));
     * $this->url = $this->buildURL($url, $data);
     */
    public $url = null;

    /*
     * $this->requestHeaders = $this->parseRequestHeaders($this->getInfo(CURLINFO_HEADER_OUT));
     *
     * $curl->requestHeaders
     */
    public $requestHeaders = null;

    /*
     * $this->responseHeaders = $this->parseResponseHeaders($this->rawResponseHeaders);
     *
     * $curl->responseHeaders
     * $curl->responseHeaders['Content-Type']
     * $curl->responseHeaders['CoNTeNT-TyPE']
     */
    public $responseHeaders = null;

    /*
     * $this->rawResponseHeaders .= $header;
     */
    public $rawResponseHeaders = '';

    /*
     * $this->responseCookies = array();
     *
     * $this->responseCookies[$cookie[1]] = trim($cookie[2], " \n\r\t\0\x0B");
     *
     */
    public $responseCookies = array();

    /*
     * $this->response = $this->parseResponse($this->responseHeaders, $this->rawResponse);
     *
     * $curl->response
     */
    public $response = null;

    /*
     * $this->rawResponse = curl_exec($this->curl);
     */
    public $rawResponse = null;

    /*
     * $this->beforeSendFunction = $callback;
     */
    public $beforeSendFunction = null;

    /*
     * $this->downloadCompleteFunction = $mixed_filename;
     *
     * $this->downloadCompleteFunction = function ($fh) use ($download_filename, $filename) {
                rename($download_filename, $filename);
            };
     */
    public $downloadCompleteFunction = null;

    /*
     * $this->call($this->successFunction);
     */
    public $successFunction = null;

    /*
     * $this->call($this->errorFunction);
     */
    public $errorFunction = null;

    /*
     * $this->call($this->completeFunction);
     */
    public $completeFunction = null;

    /*
     * $this->downloadComplete($this->fileHandle);
     */
    public $fileHandle = null;

    /*
     * 发送请求时的cookie
     * $this->cookies[implode('', $name_chars)] = implode('', $value_chars);
     */
    private $cookies = array();

    /*
     * 请求头信息
     * $this->headers = new CaseInsensitiveArray();
     *
     * $curl->setHeader('X-Requested-With', 'XMLHttpRequest');
     *
     * public function setHeader($key, $value)
     * {
     *
     * $this->headers[$key] = $value;
     *
     */
    private $headers = array();

    /*
     * $this->setOpt(CURLOPT_USERAGENT, $user_agent);
     * $this->options[$option] = $value;
     */
    private $options = array();

    /*
     * $this->jsonDecoder = function ($response) use ($args) {}
     */
    private $jsonDecoder = null;

    /*
     * preg_match($this->jsonPattern, $response_headers['Content-Type'])
     */
    private $jsonPattern = '/^(?:application|text)\/(?:[a-z]+(?:[\.-][0-9a-z]+){0,}[\+\.]|x-)?json(?:-[a-z]+)?/i';

    /*
     * $this->xmlDecoder = function ($response) {}
     */
    private $xmlDecoder = null;

    /*
     * preg_match($this->xmlPattern, $response_headers['Content-Type'])
     */
    private $xmlPattern = '~^(?:text/|application/(?:atom\+|rss\+)?)xml~i';

    /*
     * setDefaultDecoder($decoder = 'json')
     */
    private $defaultDecoder = null;

    public static $RFC2616 = array(
        // RFC2616: "any CHAR except CTLs or separators".
        // CHAR           = <any US-ASCII character (octets 0 - 127)>
        // CTL            = <any US-ASCII control character
        //                  (octets 0 - 31) and DEL (127)>
        // separators     = "(" | ")" | "<" | ">" | "@"
        //                | "," | ";" | ":" | "\" | <">
        //                | "/" | "[" | "]" | "?" | "="
        //                | "{" | "}" | SP | HT
        // SP             = <US-ASCII SP, space (32)>
        // HT             = <US-ASCII HT, horizontal-tab (9)>
        // <">            = <US-ASCII double-quote mark (34)>
        '!', '#', '$', '%', '&', "'", '*', '+', '-', '.', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B',
        'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
        'Y', 'Z', '^', '_', '`', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q',
        'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '|', '~',
    );
    public static $RFC6265 = array(
        // RFC6265: "US-ASCII characters excluding CTLs, whitespace DQUOTE, comma, semicolon, and backslash".
        // %x21
        '!',
        // %x23-2B
        '#', '$', '%', '&', "'", '(', ')', '*', '+',
        // %x2D-3A
        '-', '.', '/', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', ':',
        // %x3C-5B
        '<', '=', '>', '?', '@', 'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q',
        'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', '[',
        // %x5D-7E
        ']', '^', '_', '`', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r',
        's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '{', '|', '}', '~',
    );

    private static $deferredProperties = array(
        'effectiveUrl',
        'rfc2616',
        'rfc6265',
        'totalTime',
    );

    /**
     * Construct
     *
     * @access public
     * @param  $base_url
     * @throws \ErrorException
     */
    /*
     * $curl = new Curl();
     */
    public function __construct($base_url = null)
    {
        if (!extension_loaded('curl')) {
            throw new \ErrorException('cURL library is not loaded');
        }

        $this->curl = curl_init();
        //var_dump($this->curl);
        /*
         * resource(7) of type (curl)
         */

        $this->id = uniqid('', true);
        //var_dump($this->id);
        /*
         * string(23) "58993601d8c188.59412617"
         */

        $this->setDefaultUserAgent();
        $this->setDefaultJsonDecoder();
        $this->setDefaultXmlDecoder();
        $this->setDefaultTimeout();

        /*
         * CURLINFO_HEADER_OUT	TRUE 时追踪句柄的请求字符串。
         */
        $this->setOpt(CURLINFO_HEADER_OUT, true);

        /*
         * CURLOPT_HEADERFUNCTION	设置一个回调函数，这个函数有两个参数，
         * 第一个是cURL的资源句柄，
         * 第二个是输出的 header 数据。
         * header数据的输出必须依赖这个函数，返回已写入的数据大小
         */
        $this->setOpt(CURLOPT_HEADERFUNCTION, array($this, 'headerCallback'));

        /*
         * CURLOPT_RETURNTRANSFER	TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出。
         */
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
        $this->headers = new CaseInsensitiveArray();
        $this->setUrl($base_url);
    }

    /**
     * Build Url
     *
     * @access private
     * @param  $url
     * @param  $data
     *
     * @return string
     */
    private function buildURL($url, $data = array())
    {
        return $url . (empty($data) ? '' : '?' . http_build_query($data, '', '&'));
    }

    /**
     * Set Url
     *
     * @access public
     * @param  $url
     * @param  $data
     */
    public function setUrl($url, $data = array())
    {
        $this->baseUrl = $url;
        $this->url = $this->buildURL($url, $data);

        /*
         * CURLOPT_URL	需要获取的 URL 地址，也可以在curl_init() 初始化会话的时候
         */
        $this->setOpt(CURLOPT_URL, $this->url);
    }

    /**
     * Header Callback
     *
     * @access public
     * @param  $ch
     * @param  $header
     *
     * @return integer
     */
    public function headerCallback($ch, $header)
    {
        if (preg_match('/^Set-Cookie:\s*([^=]+)=([^;]+)/mi', $header, $cookie) === 1) {
            $this->responseCookies[$cookie[1]] = trim($cookie[2], " \n\r\t\0\x0B");
        }
        $this->rawResponseHeaders .= $header;
        return strlen($header);
    }

    /**
     * Set Timeout
     *
     * @access public
     * @param  $seconds
     */
    public function setTimeout($seconds)
    {
        /*
         * CURLOPT_TIMEOUT	允许 cURL 函数执行的最长秒数。
         */
        $this->setOpt(CURLOPT_TIMEOUT, $seconds);
    }

    /**
     * Set Default Timeout
     *
     * @access public
     */
    public function setDefaultTimeout()
    {
        $this->setTimeout(self::DEFAULT_TIMEOUT);
    }

    /**
     * Set Default XML Decoder
     *
     * @access public
     */
    public function setDefaultXmlDecoder()
    {
        $this->xmlDecoder = function ($response) {
            /*
             * simplexml_load_string — Interprets a string of XML into an object
             */
            $xml_obj = @simplexml_load_string($response);
            if (!($xml_obj === false)) {
                $response = $xml_obj;
            }
            return $response;
        };
    }

    /**
     * Set Default JSON Decoder
     *
     * @access public
     * @param  $assoc
     * @param  $depth
     * @param  $options
     */
    public function setDefaultJsonDecoder()
    {
        /*
         * func_get_args — 返回一个包含函数参数列表的数组
         */
        $args = func_get_args();
        $this->jsonDecoder = function ($response) use ($args) {
            /*
             * array_unshift — 在数组开头插入一个或多个单元
             */
            array_unshift($args, $response);

            // Call json_decode() without the $options parameter in PHP
            // versions less than 5.4.0 as the $options parameter was added in
            // PHP version 5.4.0.
            if (version_compare(PHP_VERSION, '5.4.0', '<')) {
                /*
                 * array_slice — 从数组中取出一段
                 */
                $args = array_slice($args, 0, 3);
            }

            /*
             * call_user_func_array — 调用回调函数，并把一个数组参数作为回调函数的参数
             */
            $json_obj = call_user_func_array('json_decode', $args);
            if (!($json_obj === null)) {
                $response = $json_obj;
            }
            return $response;
        };
    }

    /**
     * Set Opt
     *
     * @access public
     * @param  $option
     * @param  $value
     *
     * @return boolean
     */
    /*
     * CURLOPT_FOLLOWLOCATION	TRUE 时将会根据服务器返回 HTTP 头中的 "Location: " 重定向。
     * （注意：这是递归的，"Location: " 发送几次就重定向几次，除非设置了 CURLOPT_MAXREDIRS，限制最大重定向次数。）。
     *
     * $curl->setOpt(CURLOPT_FOLLOWLOCATION, true);
     *
     *
     * CURLOPT_ENCODING	HTTP请求头中"Accept-Encoding: "的值。 这使得能够解码响应的内容。
     * 支持的编码有"identity"，"deflate"和"gzip"。如果为空字符串""，会发送所有支持的编码类型。
     * $curl->setOpt(CURLOPT_ENCODING , 'gzip');
     */
    public function setOpt($option, $value)
    {
        /*
         * CURLOPT_RETURNTRANSFER	TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出。
         */
        $required_options = array(
            CURLOPT_RETURNTRANSFER => 'CURLOPT_RETURNTRANSFER',
        );

        if (in_array($option, array_keys($required_options), true) && !($value === true)) {
            trigger_error($required_options[$option] . ' is a required option', E_USER_WARNING);
        }

        $success = curl_setopt($this->curl, $option, $value);
        if ($success) {
            $this->options[$option] = $value;
        }
        return $success;
    }

    /**
     * Set Opts
     *
     * @access public
     * @param  $options
     *
     * @return boolean
     *   Returns true if all options were successfully set. If an option could not be successfully set, false is
     *   immediately returned, ignoring any future options in the options array. Similar to curl_setopt_array().
     */
    public function setOpts($options)
    {
        foreach ($options as $option => $value) {
            if (!$this->setOpt($option, $value)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Set User Agent
     *
     * @access public
     * @param  $user_agent
     */
    //$curl->setUserAgent('MyUserAgent/0.0.1 (+https://www.example.com/bot.html)');
    public function setUserAgent($user_agent)
    {
        //var_dump($user_agent);
        /*
         * string(106) "PHP-Curl-Class/7.2.0 (+https://github.com/php-curl-class/php-curl-class) PHP/5.5.9-1ubuntu4.20 curl/7.35.0"
         */

        /*
         * CURLOPT_USERAGENT	在HTTP请求中包含一个"User-Agent: "头的字符串
         */
        $this->setOpt(CURLOPT_USERAGENT, $user_agent);
    }

    /**
     * Set Default User Agent
     *
     * @access public
     */
    public function setDefaultUserAgent()
    {
        $user_agent = 'PHP-Curl-Class/' . self::VERSION . ' (+https://github.com/php-curl-class/php-curl-class)';
        $user_agent .= ' PHP/' . PHP_VERSION;
        $curl_version = curl_version();
        //print_r($curl_version);
        /*
         * Array
            (
                [version_number] => 467712
                [age] => 3
                [features] => 50877
                [ssl_version_number] => 0
                [version] => 7.35.0
                [host] => x86_64-pc-linux-gnu
                [ssl_version] => OpenSSL/1.0.1f
                [libz_version] => 1.2.8
                [protocols] => Array
                    (
                        [0] => dict
                        [1] => file
                        [2] => ftp
                        [3] => ftps
                        [4] => gopher
                        [5] => http
                        [6] => https
                        [7] => imap
                        [8] => imaps
                        [9] => ldap
                        [10] => ldaps
                        [11] => pop3
                        [12] => pop3s
                        [13] => rtmp
                        [14] => rtsp
                        [15] => smtp
                        [16] => smtps
                        [17] => telnet
                        [18] => tftp
                    )

            )
         *
         */

        $user_agent .= ' curl/' . $curl_version['version'];
        $this->setUserAgent($user_agent);
    }

    /*
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     */

    /**
     * Exec
     *
     * @access public
     * @param  $ch
     *
     * @return mixed Returns the value provided by parseResponse.
     */

    /*
     * return $this->exec();
     */
    public function exec($ch = null)
    {
        if ($ch === null) {
            $this->responseCookies = array();
            $this->call($this->beforeSendFunction);
            $this->rawResponse = curl_exec($this->curl);
            $this->curlErrorCode = curl_errno($this->curl);
            $this->curlErrorMessage = curl_error($this->curl);
        } else {
            $this->rawResponse = curl_multi_getcontent($ch);
            $this->curlErrorMessage = curl_error($ch);
        }
        $this->curlError = !($this->curlErrorCode === 0);

        // Include additional error code information in error message when possible.

        /*
         * curl_strerror — Return string describing the given error code
         */
        if ($this->curlError && function_exists('curl_strerror')) {
            $this->curlErrorMessage =
                curl_strerror($this->curlErrorCode) . (
                empty($this->curlErrorMessage) ? '' : ': ' . $this->curlErrorMessage
                );
        }

        /*
         * curl_getinfo
         * $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
         */
        $this->httpStatusCode = $this->getInfo(CURLINFO_HTTP_CODE);
        $this->httpError = in_array(floor($this->httpStatusCode / 100), array(4, 5));
        $this->error = $this->curlError || $this->httpError;
        $this->errorCode = $this->error ? ($this->curlError ? $this->curlErrorCode : $this->httpStatusCode) : 0;

        // NOTE: CURLINFO_HEADER_OUT set to true is required for requestHeaders
        // to not be empty (e.g. $curl->setOpt(CURLINFO_HEADER_OUT, true);).

        /*
         * CURLINFO_HEADER_OUT	TRUE 时追踪句柄的请求字符串。
         */
        if ($this->getOpt(CURLINFO_HEADER_OUT) === true) {
            $this->requestHeaders = $this->parseRequestHeaders($this->getInfo(CURLINFO_HEADER_OUT));
        }
        $this->responseHeaders = $this->parseResponseHeaders($this->rawResponseHeaders);
        $this->response = $this->parseResponse($this->responseHeaders, $this->rawResponse);

        $this->httpErrorMessage = '';
        if ($this->error) {
            if (isset($this->responseHeaders['Status-Line'])) {
                $this->httpErrorMessage = $this->responseHeaders['Status-Line'];
            }
        }
        $this->errorMessage = $this->curlError ? $this->curlErrorMessage : $this->httpErrorMessage;

        if (!$this->error) {
            $this->call($this->successFunction);
        } else {
            $this->call($this->errorFunction);
        }

        $this->call($this->completeFunction);

        // Close open file handles and reset the curl instance.
        if (!($this->fileHandle === null)) {
            $this->downloadComplete($this->fileHandle);
        }

        return $this->response;
    }

    /**
     * Parse Response
     *
     * @access private
     * @param  $response_headers
     * @param  $raw_response
     *
     * @return mixed
     *   Provided the content-type is determined to be json or xml:
     *     Returns stdClass object when the default json decoder is used and the content-type is json.
     *     Returns SimpleXMLElement object when the default xml decoder is used and the content-type is xml.
     */
    private function parseResponse($response_headers, $raw_response)
    {
        $response = $raw_response;
        if (isset($response_headers['Content-Type'])) {
            if (preg_match($this->jsonPattern, $response_headers['Content-Type'])) {
                $json_decoder = $this->jsonDecoder;
                if (is_callable($json_decoder)) {
                    $response = $json_decoder($response);
                }
            } elseif (preg_match($this->xmlPattern, $response_headers['Content-Type'])) {
                $xml_decoder = $this->xmlDecoder;
                if (is_callable($xml_decoder)) {
                    $response = $xml_decoder($response);
                }
            } else {
                $decoder = $this->defaultDecoder;
                if (is_callable($decoder)) {
                    $response = $decoder($response);
                }
            }
        }

        return $response;
    }

    /**
     * Parse Response Headers
     *
     * @access private
     * @param  $raw_response_headers
     *
     * @return array
     */

    //$this->parseResponseHeaders($this->rawResponseHeaders);
    private function parseResponseHeaders($raw_response_headers)
    {
        $response_header_array = explode("\r\n\r\n", $raw_response_headers);
        $response_header  = '';
        for ($i = count($response_header_array) - 1; $i >= 0; $i--) {
            if (stripos($response_header_array[$i], 'HTTP/') === 0) {
                $response_header = $response_header_array[$i];
                break;
            }
        }

        $response_headers = new CaseInsensitiveArray();
        list($first_line, $headers) = $this->parseHeaders($response_header);
        $response_headers['Status-Line'] = $first_line;
        foreach ($headers as $key => $value) {
            $response_headers[$key] = $value;
        }
        return $response_headers;
    }

    /**
     * Parse Request Headers
     *
     * @access private
     * @param  $raw_headers
     *
     * @return array
     */
    private function parseRequestHeaders($raw_headers)
    {
        $request_headers = new CaseInsensitiveArray();
        list($first_line, $headers) = $this->parseHeaders($raw_headers);
        $request_headers['Request-Line'] = $first_line;
        foreach ($headers as $key => $value) {
            $request_headers[$key] = $value;
        }
        return $request_headers;
    }

    /**
     * Parse Headers
     *
     * @access private
     * @param  $raw_headers
     *
     * @return array
     */
    private function parseHeaders($raw_headers)
    {
        $raw_headers = preg_split('/\r\n/', $raw_headers, null, PREG_SPLIT_NO_EMPTY);
        $http_headers = new CaseInsensitiveArray();

        $raw_headers_count = count($raw_headers);
        for ($i = 1; $i < $raw_headers_count; $i++) {
            list($key, $value) = explode(':', $raw_headers[$i], 2);
            $key = trim($key);
            $value = trim($value);
            // Use isset() as array_key_exists() and ArrayAccess are not compatible.
            if (isset($http_headers[$key])) {
                $http_headers[$key] .= ',' . $value;
            } else {
                $http_headers[$key] = $value;
            }
        }

        return array(isset($raw_headers['0']) ? $raw_headers['0'] : '', $http_headers);
    }

    /**
     * Get Opt
     *
     * @access public
     * @param  $option
     *
     * @return mixed
     */
    public function getOpt($option)
    {
        return isset($this->options[$option]) ? $this->options[$option] : null;
    }

    /**
     * Get Info
     *
     * @access public
     * @param  $opt
     *
     * @return mixed
     */
    public function getInfo($opt = null)
    {
        $args = array();
        $args[] = $this->curl;

        if (func_num_args()) {
            $args[] = $opt;
        }

        return call_user_func_array('curl_getinfo', $args);
    }

    /**
     * Call
     *
     * @access public
     */
    //$this->call($this->beforeSendFunction);
    public function call()
    {
        /*
         * func_get_args — 返回一个包含函数参数列表的数组
         */
        $args = func_get_args();

        /*
         * array_shift — 将数组开头的单元移出数组
         */
        $function = array_shift($args);

        /*
         * is_callable — 检测参数是否为合法的可调用结构
         */
        if (is_callable($function)) {

            /*
             * array_unshift — 在数组开头插入一个或多个单元
             */
            array_unshift($args, $this);

            /*
             * call_user_func_array — 调用回调函数，并把一个数组参数作为回调函数的参数
             */
            call_user_func_array($function, $args);
        }
    }

    /**
     * Get
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return mixed Returns the value provided by exec.
     */
    /*
     * $curl->get('https://www.example.com/');
     *
     * $curl->get('https://www.example.com/search', array('q' => 'keyword',));
     */
    public function get($url, $data = array())
    {
        if (is_array($url)) {
            $data = $url;
            $url = $this->baseUrl;
        }
        $this->setUrl($url, $data);

        /*
         * CURLOPT_CUSTOMREQUEST	请求时，使用自定义的 Method 来代替"GET"或"HEAD"。
         * 对 "DELETE" 或者其他更隐蔽的 HTTP 请求有用。 有效值如 "GET"，"POST"，"CONNECT"等等；
         * 也就是说，不要在这里输入整行 HTTP 请求。例如输入"GET /index.html HTTP/1.0\r\n\r\n"是不正确的。
         */
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');

        /*
         * CURLOPT_HTTPGET	TRUE 时会设置 HTTP 的 method 为 GET，
         * 由于默认是 GET，所以只有 method 被修改时才需要这个选项。
         */
        $this->setOpt(CURLOPT_HTTPGET, true);
        return $this->exec();
    }

    /**
     * Post
     *
     * @access public
     * @param  $url
     * @param  $data
     * @param  $follow_303_with_post
     *     If true, will cause 303 redirections to be followed using a POST request (default: false).
     *     Notes:
     *       - Redirections are only followed if the CURLOPT_FOLLOWLOCATION option is set to true.
     *       - According to the HTTP specs (see [1]), a 303 redirection should be followed using
     *         the GET method. 301 and 302 must not.
     *       - In order to force a 303 redirection to be performed using the same method, the
     *         underlying cURL object must be set in a special state (the CURLOPT_CURSTOMREQUEST
     *         option must be set to the method to use after the redirection). Due to a limitation
     *         of the cURL extension of PHP < 5.5.11 ([2], [3]) and of HHVM, it is not possible
     *         to reset this option. Using these PHP engines, it is therefore impossible to
     *         restore this behavior on an existing php-curl-class Curl object.
     *
     * @return string
     *
     * [1] https://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html#sec10.3.2
     * [2] https://github.com/php/php-src/pull/531
     * [3] http://php.net/ChangeLog-5.php#5.5.11
     */
    /*
     * $curl->post(
     * 'https://www.example.com/login/',
     * array('username' => 'myusername','password' => 'mypassword',)
     * );
     */
    public function post($url, $data = array(), $follow_303_with_post = false)
    {
        if (is_array($url)) {
            $follow_303_with_post = (bool)$data;
            $data = $url;
            $url = $this->baseUrl;
        }

        $this->setUrl($url);

        if ($follow_303_with_post) {
            /*
             * CURLOPT_CUSTOMREQUEST	HTTP 请求时，使用自定义的 Method 来代替"GET"或"HEAD"。
             * 对 "DELETE" 或者其他更隐蔽的 HTTP 请求有用。
             * 有效值如 "GET"，"POST"，"CONNECT"等等；也就是说，不要在这里输入整行 HTTP 请求。
             * 例如输入"GET /index.html HTTP/1.0\r\n\r\n"是不正确的。
             */
            $this->setOpt(CURLOPT_CUSTOMREQUEST, 'POST');
        } else {
            if (isset($this->options[CURLOPT_CUSTOMREQUEST])) {
                if ((version_compare(PHP_VERSION, '5.5.11') < 0) || defined('HHVM_VERSION')) {
                    trigger_error(
                        'Due to technical limitations of PHP <= 5.5.11 and HHVM, it is not possible to '
                        . 'perform a post-redirect-get request using a php-curl-class Curl object that '
                        . 'has already been used to perform other types of requests. Either use a new '
                        . 'php-curl-class Curl object or upgrade your PHP engine.',
                        E_USER_ERROR
                    );
                } else {
                    $this->setOpt(CURLOPT_CUSTOMREQUEST, null);
                }
            }
        }

        /*
         * CURLOPT_POST	TRUE 时会发送 POST 请求，
         * 类型为：application/x-www-form-urlencoded，是 HTML 表单提交时最常见的一种。
         */
        $this->setOpt(CURLOPT_POST, true);

        /*
         * CURLOPT_POSTFIELDS	全部数据使用HTTP协议中的 "POST" 操作来发送。
         * 要发送文件，在文件名前面加上@前缀并使用完整路径。 文件类型可在文件名后以 ';type=mimetype' 的格式指定。
         * 这个参数可以是 urlencoded 后的字符串，类似'para1=val1&para2=val2&...'，
         * 也可以使用一个以字段名为键值，字段数据为值的数组。
         * 如果value是一个数组，Content-Type头将会被设置成multipart/form-data。
         *
         * 从 PHP 5.2.0 开始，使用 @ 前缀传递文件时，value 必须是个数组。
         * 从 PHP 5.5.0 开始, @ 前缀已被废弃，文件可通过 CURLFile 发送。
         * 设置 CURLOPT_SAFE_UPLOAD 为 TRUE 可禁用 @ 前缀发送文件，以增加安全性。
         */
        $this->setOpt(CURLOPT_POSTFIELDS, $this->buildPostData($data));
        return $this->exec();
    }

    /**
     * Build Post Data
     *
     * @access public
     * @param  $data
     *
     * @return array|string
     */
    /*
     * array('image' => '@path/to/file.jpg',));
     * array('image' => new CURLFile('path/to/file.jpg'),));
     */
    public function buildPostData($data)
    {
        $binary_data = false;
        if (is_array($data)) {
            // Return JSON-encoded string when the request's content-type is JSON.
            if (isset($this->headers['Content-Type']) &&
                preg_match($this->jsonPattern, $this->headers['Content-Type'])) {
                $json_str = json_encode($data);
                if (!($json_str === false)) {
                    $data = $json_str;
                }
            } else {
                // Manually build a single-dimensional array from a multi-dimensional array as using curl_setopt($ch,
                // CURLOPT_POSTFIELDS, $data) doesn't correctly handle multi-dimensional arrays when files are
                // referenced.
                if (ArrayUtil::is_array_multidim($data)) {
                    $data = ArrayUtil::array_flatten_multidim($data);
                }

                // Modify array values to ensure any referenced files are properly handled depending on the support of
                // the @filename API or CURLFile usage. This also fixes the warning "curl_setopt(): The usage of the
                // @filename API for file uploading is deprecated. Please use the CURLFile class instead". Ignore
                // non-file values prefixed with the @ character.
                foreach ($data as $key => $value) {
                    if (is_string($value) && strpos($value, '@') === 0 && is_file(substr($value, 1))) {
                        $binary_data = true;
                        if (class_exists('CURLFile')) {
                            $data[$key] = new \CURLFile(substr($value, 1));
                        }
                    } elseif ($value instanceof \CURLFile) {
                        $binary_data = true;
                    }
                }
            }
        }

        if (!$binary_data && (is_array($data) || is_object($data))) {
            $data = http_build_query($data, '', '&');
        }

        return $data;
    }

    /**
     * Put
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return string
     */
    /*
     * $curl->put(
     * 'https://api.example.com/user/',
     * array('first_name' => 'Zach', 'last_name' => 'Borboa',)
     * );
     */
    public function put($url, $data = array())
    {
        if (is_array($url)) {
            $data = $url;
            $url = $this->baseUrl;
        }
        $this->setUrl($url);

        /*
         * CURLOPT_CUSTOMREQUEST	HTTP 请求时，使用自定义的 Method 来代替"GET"或"HEAD"。
         * 对 "DELETE" 或者其他更隐蔽的 HTTP 请求有用。 有效值如 "GET"，"POST"，"CONNECT"等等；
         * 也就是说，不要在这里输入整行 HTTP 请求。例如输入"GET /index.html HTTP/1.0\r\n\r\n"是不正确的。
         */
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PUT');
        $put_data = $this->buildPostData($data);

        /*
         * CURLOPT_INFILE	上传文件时需要读取的文件。
         *
         * CURLOPT_INFILESIZE	希望传给远程站点的文件尺寸，字节(byte)为单位。
         * 注意无法用这个选项阻止 libcurl 发送更多的数据，确切发送什么取决于 CURLOPT_READFUNCTION
         *
         */
        if (empty($this->options[CURLOPT_INFILE]) && empty($this->options[CURLOPT_INFILESIZE])) {
            if (is_string($put_data)) {
                $this->setHeader('Content-Length', strlen($put_data));
            }
        }
        if (!empty($put_data)) {

            /*
             * CURLOPT_POSTFIELDS	全部数据使用HTTP协议中的 "POST" 操作来发送。
             * 要发送文件，在文件名前面加上@前缀并使用完整路径。 文件类型可在文件名后以 ';type=mimetype' 的格式指定。
             * 这个参数可以是 urlencoded 后的字符串，类似'para1=val1&para2=val2&...'，
             * 也可以使用一个以字段名为键值，字段数据为值的数组。
             * 如果value是一个数组，Content-Type头将会被设置成multipart/form-data。
             *
             * 从 PHP 5.2.0 开始，使用 @ 前缀传递文件时，value 必须是个数组。
             * 从 PHP 5.5.0 开始, @ 前缀已被废弃，文件可通过 CURLFile 发送。
             * 设置 CURLOPT_SAFE_UPLOAD 为 TRUE 可禁用 @ 前缀发送文件，以增加安全性。
             */
            $this->setOpt(CURLOPT_POSTFIELDS, $put_data);
        }
        return $this->exec();
    }

    /**
     * Patch
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return string
     */
    /*
     * $curl->patch('https://api.example.com/profile/', array('image' => '@path/to/file.jpg',));
     *
     * $curl->patch('https://api.example.com/profile/', array('image' => new CURLFile('path/to/file.jpg'),));
     */
    public function patch($url, $data = array())
    {
        if (is_array($url)) {
            $data = $url;
            $url = $this->baseUrl;
        }

        if (is_array($data) && empty($data)) {
            $this->removeHeader('Content-Length');
        }

        $this->setUrl($url);

        /*
         * CURLOPT_CUSTOMREQUEST	HTTP 请求时，使用自定义的 Method 来代替"GET"或"HEAD"。
         * 对 "DELETE" 或者其他更隐蔽的 HTTP 请求有用。 有效值如 "GET"，"POST"，"CONNECT"等等；
         * 也就是说，不要在这里输入整行 HTTP 请求。例如输入"GET /index.html HTTP/1.0\r\n\r\n"是不正确的。
         */
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'PATCH');

        /*
         * CURLOPT_POSTFIELDS	全部数据使用HTTP协议中的 "POST" 操作来发送。
         * 要发送文件，在文件名前面加上@前缀并使用完整路径。 文件类型可在文件名后以 ';type=mimetype' 的格式指定。
         * 这个参数可以是 urlencoded 后的字符串，类似'para1=val1&para2=val2&...'，
         * 也可以使用一个以字段名为键值，字段数据为值的数组。
         * 如果value是一个数组，Content-Type头将会被设置成multipart/form-data。
         *
         * 从 PHP 5.2.0 开始，使用 @ 前缀传递文件时，value 必须是个数组。
         * 从 PHP 5.5.0 开始, @ 前缀已被废弃，文件可通过 CURLFile 发送。
         * 设置 CURLOPT_SAFE_UPLOAD 为 TRUE 可禁用 @ 前缀发送文件，以增加安全性。
         */
        $this->setOpt(CURLOPT_POSTFIELDS, $this->buildPostData($data));
        return $this->exec();
    }

    /**
     * Delete
     *
     * @access public
     * @param  $url
     * @param  $query_parameters
     * @param  $data
     *
     * @return string
     */
    /*
     * $curl->delete('https://api.example.com/user/', array('id' => '1234',));
     */
    public function delete($url, $query_parameters = array(), $data = array())
    {
        if (is_array($url)) {
            $data = $query_parameters;
            $query_parameters = $url;
            $url = $this->baseUrl;
        }

        $this->setUrl($url, $query_parameters);

        /*
         * CURLOPT_CUSTOMREQUEST	HTTP 请求时，使用自定义的 Method 来代替"GET"或"HEAD"。
         * 对 "DELETE" 或者其他更隐蔽的 HTTP 请求有用。 有效值如 "GET"，"POST"，"CONNECT"等等；
         * 也就是说，不要在这里输入整行 HTTP 请求。例如输入"GET /index.html HTTP/1.0\r\n\r\n"是不正确的。
         */
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'DELETE');

        /*
         * CURLOPT_POSTFIELDS	全部数据使用HTTP协议中的 "POST" 操作来发送。
         * 要发送文件，在文件名前面加上@前缀并使用完整路径。 文件类型可在文件名后以 ';type=mimetype' 的格式指定。
         * 这个参数可以是 urlencoded 后的字符串，类似'para1=val1&para2=val2&...'，
         * 也可以使用一个以字段名为键值，字段数据为值的数组。
         * 如果value是一个数组，Content-Type头将会被设置成multipart/form-data。
         *
         * 从 PHP 5.2.0 开始，使用 @ 前缀传递文件时，value 必须是个数组。
         * 从 PHP 5.5.0 开始, @ 前缀已被废弃，文件可通过 CURLFile 发送。
         * 设置 CURLOPT_SAFE_UPLOAD 为 TRUE 可禁用 @ 前缀发送文件，以增加安全性。
         */
        $this->setOpt(CURLOPT_POSTFIELDS, $this->buildPostData($data));
        return $this->exec();
    }

    /**
     * Download
     *
     * @access public
     * @param  $url
     * @param  $mixed_filename
     *
     * @return boolean
     */
    /*
     * $curl->download('https://www.example.com/image.png', '/tmp/myimage.png');
     */
    public function download($url, $mixed_filename)
    {
        if (is_callable($mixed_filename)) {
            $this->downloadCompleteFunction = $mixed_filename;

            /*
             * tmpfile — 建立一个临时文件
             */
            $fh = tmpfile();
        } else {
            $filename = $mixed_filename;

            // Use a temporary file when downloading. Not using a temporary file can cause an error when an existing
            // file has already fully completed downloading and a new download is started with the same destination save
            // path. The download request will include header "Range: bytes=$filesize-" which is syntactically valid,
            // but unsatisfiable.
            $download_filename = $filename . '.pccdownload';

            $mode = 'wb';
            // Attempt to resume download only when a temporary download file exists and is not empty.
            if (file_exists($download_filename) && $filesize = filesize($download_filename)) {
                $mode = 'ab';
                $first_byte_position = $filesize;
                $range = $first_byte_position . '-';

                /*
                 * CURLOPT_RANGE	以"X-Y"的形式，其中X和Y都是可选项获取数据的范围，以字节计。
                 * HTTP传输线程也支持几个这样的重复项中间用逗号分隔如"X-Y,N-M"。
                 */
                $this->setOpt(CURLOPT_RANGE, $range);
            }
            $fh = fopen($download_filename, $mode);

            // Move the downloaded temporary file to the destination save path.
            $this->downloadCompleteFunction = function ($fh) use ($download_filename, $filename) {
                rename($download_filename, $filename);
            };
        }

        /*
         * CURLOPT_FILE	设置输出文件，默认为STDOUT (浏览器)。
         */
        $this->setOpt(CURLOPT_FILE, $fh);
        $this->get($url);
        $this->downloadComplete($fh);

        return ! $this->error;
    }

    /**
     * Download Complete
     *
     * @access private
     * @param  $fh
     */
    private function downloadComplete($fh)
    {
        if (!$this->error && $this->downloadCompleteFunction) {
            rewind($fh);
            $this->call($this->downloadCompleteFunction, $fh);
            $this->downloadCompleteFunction = null;
        }

        if (is_resource($fh)) {
            fclose($fh);
        }

        // Fix "PHP Notice: Use of undefined constant STDOUT" when reading the
        // PHP script from stdin. Using null causes "Warning: curl_setopt():
        // supplied argument is not a valid File-Handle resource".
        if (!defined('STDOUT')) {
            define('STDOUT', fopen('php://stdout', 'w'));
        }

        // Reset CURLOPT_FILE with STDOUT to avoid: "curl_exec(): CURLOPT_FILE
        // resource has gone away, resetting to default".
        /*
         * CURLOPT_FILE	设置输出文件，默认为STDOUT (浏览器)。
         */
        $this->setOpt(CURLOPT_FILE, STDOUT);

        // Reset CURLOPT_RETURNTRANSFER to tell cURL to return subsequent
        // responses as the return value of curl_exec(). Without this,
        // curl_exec() will revert to returning boolean values.

        /*
         * CURLOPT_RETURNTRANSFER	TRUE 将curl_exec()获取的信息以字符串返回，而不是直接输出。
         */
        $this->setOpt(CURLOPT_RETURNTRANSFER, true);
    }

    /**
     * Close
     *
     * @access public
     */
    public function close()
    {
        if (is_resource($this->curl)) {
            curl_close($this->curl);
        }
        $this->options = null;
        $this->jsonDecoder = null;
        $this->xmlDecoder = null;
        $this->defaultDecoder = null;
    }

    /**
     * Set Basic Authentication
     *
     * @access public
     * @param  $username
     * @param  $password
     */
    //$curl->setBasicAuthentication('username', 'password');
    public function setBasicAuthentication($username, $password = '')
    {
        /*
         * CURLOPT_HTTPAUTH	使用的 HTTP 验证方法。
         * 选项有：
         * CURLAUTH_BASIC、
         * CURLAUTH_DIGEST、
         * CURLAUTH_GSSNEGOTIATE、
         * CURLAUTH_NTLM、
         * CURLAUTH_ANY和
         * CURLAUTH_ANYSAFE。
         *
         * 可以使用 | 位域(OR)操作符结合多个值，cURL 会让服务器选择受支持的方法，并选择最好的那个。
         *
         * CURLAUTH_ANY是 CURLAUTH_BASIC | CURLAUTH_DIGEST | CURLAUTH_GSSNEGOTIATE | CURLAUTH_NTLM 的别名。
         * CURLAUTH_ANYSAFE 是 CURLAUTH_DIGEST | CURLAUTH_GSSNEGOTIATE | CURLAUTH_NTLM 的别名。
         *
         */
        $this->setOpt(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        /*
         * CURLOPT_USERPWD	传递一个连接中需要的用户名和密码，格式为："[username]:[password]"。
         */
        $this->setOpt(CURLOPT_USERPWD, $username . ':' . $password);
    }

    /**
     * Set Digest Authentication
     *
     * @access public
     * @param  $username
     * @param  $password
     */
    public function setDigestAuthentication($username, $password = '')
    {
        /*
         * CURLOPT_HTTPAUTH	使用的 HTTP 验证方法。
         * 选项有：
         * CURLAUTH_BASIC、
         * CURLAUTH_DIGEST、
         * CURLAUTH_GSSNEGOTIATE、
         * CURLAUTH_NTLM、
         * CURLAUTH_ANY和
         * CURLAUTH_ANYSAFE。
         *
         * 可以使用 | 位域(OR)操作符结合多个值，cURL 会让服务器选择受支持的方法，并选择最好的那个。
         *
         * CURLAUTH_ANY是 CURLAUTH_BASIC | CURLAUTH_DIGEST | CURLAUTH_GSSNEGOTIATE | CURLAUTH_NTLM 的别名。
         * CURLAUTH_ANYSAFE 是 CURLAUTH_DIGEST | CURLAUTH_GSSNEGOTIATE | CURLAUTH_NTLM 的别名。
         *
         */
        $this->setOpt(CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);

        /*
         * CURLOPT_USERPWD	传递一个连接中需要的用户名和密码，格式为："[username]:[password]"。
         */
        $this->setOpt(CURLOPT_USERPWD, $username . ':' . $password);
    }

    /**
     * Set Referer
     *
     * @access public
     * @param  $referer
     */
    public function setReferer($referer)
    {
        $this->setReferrer($referer);
    }

    /**
     * Set Referrer
     *
     * @access public
     * @param  $referrer
     */
    //$curl->setReferrer('https://www.example.com/url?url=https%3A%2F%2Fwww.example.com%2F');
    public function setReferrer($referrer)
    {
        /*
         * CURLOPT_REFERER	在HTTP请求头中"Referer: "的内容。
         */
        $this->setOpt(CURLOPT_REFERER, $referrer);
    }

    /**
     * Set Header
     *
     * Add extra header to include in the request.
     *
     * @access public
     * @param  $key
     * @param  $value
     */
    //$curl->setHeader('X-Requested-With', 'XMLHttpRequest');
    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
        $headers = array();
        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }

        /*
         * CURLOPT_HTTPHEADER	设置 HTTP 头字段的数组。
         * 格式： array('Content-type: text/plain', 'Content-length: 100')
         */
        $this->setOpt(CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Set Headers
     *
     * Add extra headers to include in the request.
     *
     * @access public
     * @param  $headers
     */
    public function setHeaders($headers)
    {
        foreach ($headers as $key => $value) {
            $this->headers[$key] = $value;
        }

        $headers = array();
        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }

        /*
         * CURLOPT_HTTPHEADER	设置 HTTP 头字段的数组。
         * 格式： array('Content-type: text/plain', 'Content-length: 100')
         */
        $this->setOpt(CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Set Cookie
     *
     * @access public
     * @param  $key
     * @param  $value
     */
    //$curl->setCookie('key', 'value');
    public function setCookie($key, $value)
    {
        $name_chars = array();

        /*
         * str_split — 将字符串转换为数组
         */
        foreach (str_split($key) as $name_char) {
            if (!isset($this->rfc2616[$name_char])) {

                /*
                 * rawurlencode — 按照 RFC 3986 对 URL 进行编码
                 */
                $name_chars[] = rawurlencode($name_char);
            } else {
                $name_chars[] = $name_char;
            }
        }

        $value_chars = array();

        /*
         * str_split — 将字符串转换为数组
         */
        foreach (str_split($value) as $value_char) {
            if (!isset($this->rfc6265[$value_char])) {

                /*
                 * rawurlencode — 按照 RFC 3986 对 URL 进行编码
                 */
                $value_chars[] = rawurlencode($value_char);
            } else {
                $value_chars[] = $value_char;
            }
        }

        /*
         * implode — 将一个一维数组的值转化为字符串
         */
        $this->cookies[implode('', $name_chars)] = implode('', $value_chars);

        /*
         * CURLOPT_COOKIE	设定 HTTP 请求中"Cookie: "部分的内容。
         * 多个 cookie 用分号分隔，分号后带一个空格(例如， "fruit=apple; colour=red")。
         */
        $this->setOpt(CURLOPT_COOKIE, implode('; ', array_map(function ($k, $v) {
            return $k . '=' . $v;
        }, array_keys($this->cookies), array_values($this->cookies))));
    }

    /**
     * Set Cookies
     *
     * @access public
     * @param  $cookies
     */
    public function setCookies($cookies)
    {
        foreach ($cookies as $key => $value) {
            $name_chars = array();
            foreach (str_split($key) as $name_char) {
                if (!isset($this->rfc2616[$name_char])) {
                    $name_chars[] = rawurlencode($name_char);
                } else {
                    $name_chars[] = $name_char;
                }
            }

            $value_chars = array();
            foreach (str_split($value) as $value_char) {
                if (!isset($this->rfc6265[$value_char])) {
                    $value_chars[] = rawurlencode($value_char);
                } else {
                    $value_chars[] = $value_char;
                }
            }

            $this->cookies[implode('', $name_chars)] = implode('', $value_chars);
        }

        $this->setOpt(CURLOPT_COOKIE, implode('; ', array_map(function ($k, $v) {
            return $k . '=' . $v;
        }, array_keys($this->cookies), array_values($this->cookies))));
    }

    /**
     * Remove Header
     *
     * Remove an internal header from the request.
     * Using `curl -H "Host:" ...' is equivalent to $curl->removeHeader('Host');.
     *
     * @access public
     * @param  $key
     */
    public function removeHeader($key)
    {
        $this->setHeader($key, '');
    }

    /**
     * Before Send
     *
     * @access public
     * @param  $callback
     */
    public function beforeSend($callback)
    {
        $this->beforeSendFunction = $callback;
    }

    /**
     * Set Default Decoder
     *
     * @access public
     * @param  $decoder string|callable
     */
    public function setDefaultDecoder($decoder = 'json')
    {
        if (is_callable($decoder)) {
            $this->defaultDecoder = $decoder;
        } else {
            if ($decoder === 'json') {
                $this->defaultDecoder = $this->jsonDecoder;
            } elseif ($decoder === 'xml') {
                $this->defaultDecoder = $this->xmlDecoder;
            }
        }
    }

    /**
     * Success
     *
     * @access public
     * @param  $callback
     */
    public function success($callback)
    {
        $this->successFunction = $callback;
    }

    /**
     * Error
     *
     * @access public
     * @param  $callback
     */
    public function error($callback)
    {
        $this->errorFunction = $callback;
    }

    /**
     * Complete
     *
     * @access public
     * @param  $callback
     */
    public function complete($callback)
    {
        $this->completeFunction = $callback;
    }


    /*
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     *
     */

    /**
     * Set Max Filesize
     *
     * @access public
     * @param  $bytes
     */
    public function setMaxFilesize($bytes)
    {
        // Make compatible with PHP version both before and after 5.5.0. PHP 5.5.0 added the cURL resource as the first
        // argument to the CURLOPT_PROGRESSFUNCTION callback.
        $gte_v550 = version_compare(PHP_VERSION, '5.5.0') >= 0;
        if ($gte_v550) {

            /*
             * 第一个是cURL的资源句柄，
             * 第二个是预计要下载的总字节（bytes）数。
             * 第三个是目前下载的字节数，
             * 第四个是预计传输中总上传字节数，
             * 第五个是目前上传的字节数。
             *
             */
            $callback = function ($resource, $download_size, $downloaded, $upload_size, $uploaded) use ($bytes) {
                // Abort the transfer when $downloaded bytes exceeds maximum $bytes by returning a non-zero value.
                return $downloaded > $bytes ? 1 : 0;
            };
        } else {
            $callback = function ($download_size, $downloaded, $upload_size, $uploaded) use ($bytes) {
                return $downloaded > $bytes ? 1 : 0;
            };
        }

        $this->progress($callback);
    }

    /**
     * Progress 进展
     *
     * @access public
     * @param  $callback
     */
    public function progress($callback)
    {
        /*
         * CURLOPT_PROGRESSFUNCTION	设置一个回调函数，有五个参数，
         * 第一个是cURL的资源句柄，
         * 第二个是预计要下载的总字节（bytes）数。
         * 第三个是目前下载的字节数，
         * 第四个是预计传输中总上传字节数，
         * 第五个是目前上传的字节数。
         *
         * Note:
         * 只有设置 CURLOPT_NOPROGRESS 选项为 FALSE 时才会调用这个回调函数。
         *
         * 返回非零值将中断传输。 传输将设置 CURLE_ABORTED_BY_CALLBACK 错误
         */
        $this->setOpt(CURLOPT_PROGRESSFUNCTION, $callback);

        /*
         * CURLOPT_NOPROGRESS	TRUE 时关闭 cURL 的传输进度。
         *
         * Note:
         * PHP 默认自动设置此选项为 TRUE，只有为了调试才需要改变设置。
         */
        $this->setOpt(CURLOPT_NOPROGRESS, false);
    }

    /**
     * Head
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return string
     */
    public function head($url, $data = array())
    {
        if (is_array($url)) {
            $data = $url;
            $url = $this->baseUrl;
        }
        $this->setUrl($url, $data);
        /*
         * CURLOPT_CUSTOMREQUEST	HTTP 请求时，使用自定义的 Method 来代替"GET"或"HEAD"。
         * 对 "DELETE" 或者其他更隐蔽的 HTTP 请求有用。 有效值如 "GET"，"POST"，"CONNECT"等等；
         * 也就是说，不要在这里输入整行 HTTP 请求。例如输入"GET /index.html HTTP/1.0\r\n\r\n"是不正确的。
         */
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'HEAD');

        /*
         * CURLOPT_NOBODY	TRUE 时将不输出 BODY 部分。同时 Mehtod 变成了 HEAD。修改为 FALSE 时不会变成 GET。
         */
        $this->setOpt(CURLOPT_NOBODY, true);
        return $this->exec();
    }

    /**
     * Options
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return string
     */
    public function options($url, $data = array())
    {
        if (is_array($url)) {
            $data = $url;
            $url = $this->baseUrl;
        }
        $this->setUrl($url, $data);
        $this->removeHeader('Content-Length');
        /*
         * CURLOPT_CUSTOMREQUEST	HTTP 请求时，使用自定义的 Method 来代替"GET"或"HEAD"。
         * 对 "DELETE" 或者其他更隐蔽的 HTTP 请求有用。 有效值如 "GET"，"POST"，"CONNECT"等等；
         * 也就是说，不要在这里输入整行 HTTP 请求。例如输入"GET /index.html HTTP/1.0\r\n\r\n"是不正确的。
         */
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'OPTIONS');
        return $this->exec();
    }

    /**
     * Search
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return string
     */
    public function search($url, $data = array())
    {
        if (is_array($url)) {
            $data = $url;
            $url = $this->baseUrl;
        }
        $this->setUrl($url);
        /*
         * CURLOPT_CUSTOMREQUEST	HTTP 请求时，使用自定义的 Method 来代替"GET"或"HEAD"。
         * 对 "DELETE" 或者其他更隐蔽的 HTTP 请求有用。 有效值如 "GET"，"POST"，"CONNECT"等等；
         * 也就是说，不要在这里输入整行 HTTP 请求。例如输入"GET /index.html HTTP/1.0\r\n\r\n"是不正确的。
         */
        $this->setOpt(CURLOPT_CUSTOMREQUEST, 'SEARCH');
        $put_data = $this->buildPostData($data);
        /*
         * CURLOPT_INFILE	上传文件时需要读取的文件。
         *
         * CURLOPT_INFILESIZE	希望传给远程站点的文件尺寸，字节(byte)为单位。
         * 注意无法用这个选项阻止 libcurl 发送更多的数据，确切发送什么取决于 CURLOPT_READFUNCTION
         *
         */
        if (empty($this->options[CURLOPT_INFILE]) && empty($this->options[CURLOPT_INFILESIZE])) {
            if (is_string($put_data)) {
                $this->setHeader('Content-Length', strlen($put_data));
            }
        }
        if (!empty($put_data)) {
            $this->setOpt(CURLOPT_POSTFIELDS, $put_data);
        }
        return $this->exec();
    }

    /**
     * Get Cookie
     *
     * @access public
     * @param  $key
     *
     * @return mixed
     */
    public function getCookie($key)
    {
        return $this->getResponseCookie($key);
    }

    /**
     * Get Response Cookie
     *
     * @access public
     * @param  $key
     *
     * @return mixed
     */
    public function getResponseCookie($key)
    {
        return isset($this->responseCookies[$key]) ? $this->responseCookies[$key] : null;
    }

    /**
     * Set Port
     *
     * @access public
     * @param  $port
     */
    public function setPort($port)
    {
        /*
         * CURLOPT_PORT	用来指定连接端口。
         */
        $this->setOpt(CURLOPT_PORT, intval($port));
    }

    /**
     * Set Connect Timeout
     *
     * @access public
     * @param  $seconds
     */
    public function setConnectTimeout($seconds)
    {
        /*
         * CURLOPT_CONNECTTIMEOUT	在尝试连接时等待的秒数。设置为0，则无限等待。
         */
        $this->setOpt(CURLOPT_CONNECTTIMEOUT, $seconds);
    }

    /**
     * Set Cookie String
     *
     * @access public
     * @param  $string
     *
     * @return bool
     */
    public function setCookieString($string)
    {
        /*
         * CURLOPT_COOKIE	设定 HTTP 请求中"Cookie: "部分的内容。
         * 多个 cookie 用分号分隔，分号后带一个空格(例如， "fruit=apple; colour=red")。
         */
        return $this->setOpt(CURLOPT_COOKIE, $string);
    }

    /**
     * Set Cookie File
     *
     * @access public
     * @param  $cookie_file
     *
     * @return boolean
     */
    public function setCookieFile($cookie_file)
    {
        /*
         * CURLOPT_COOKIEFILE	包含 cookie 数据的文件名，
         * cookie 文件的格式可以是 Netscape 格式，或者只是纯 HTTP 头部风格，存入文件。
         * 如果文件名是空的，不会加载 cookie，但 cookie 的处理仍旧启用。
         */
        return $this->setOpt(CURLOPT_COOKIEFILE, $cookie_file);
    }

    /**
     * Set Cookie Jar
     *
     * @access public
     * @param  $cookie_jar
     *
     * @return boolean
     */
    public function setCookieJar($cookie_jar)
    {
        /*
         * CURLOPT_COOKIEJAR	连接结束后，比如，调用 curl_close 后，保存 cookie 信息的文件。
         */
        return $this->setOpt(CURLOPT_COOKIEJAR, $cookie_jar);
    }

    /**
     * Set JSON Decoder
     *
     * @access public
     * @param  $function
     */
    public function setJsonDecoder($function)
    {
        if (is_callable($function)) {
            $this->jsonDecoder = $function;
        }
    }

    /**
     * Set XML Decoder
     *
     * @access public
     * @param  $function
     */
    public function setXmlDecoder($function)
    {
        if (is_callable($function)) {
            $this->xmlDecoder = $function;
        }
    }

    /**
     * Unset Header 还原请求头信息
     *
     * Remove extra header previously set using Curl::setHeader().
     *
     * @access public
     * @param  $key
     */
    public function unsetHeader($key)
    {
        unset($this->headers[$key]);
        $headers = array();
        foreach ($this->headers as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }
        /*
         * CURLOPT_HTTPHEADER	设置 HTTP 头字段的数组。
         * 格式： array('Content-type: text/plain', 'Content-length: 100')
         */
        $this->setOpt(CURLOPT_HTTPHEADER, $headers);
    }

    /**
     * Verbose 详细
     *
     * @access public
     * @param  bool $on
     * @param  resource $output
     */
    public function verbose($on = true, $output = STDERR)
    {
        // Turn off CURLINFO_HEADER_OUT for verbose to work. This has the side
        // effect of causing Curl::requestHeaders to be empty.
        if ($on) {

            /*
             * CURLINFO_HEADER_OUT	TRUE 时追踪句柄的请求字符串。
             */
            $this->setOpt(CURLINFO_HEADER_OUT, false);
        }

        /*
         * CURLOPT_VERBOSE	TRUE 会输出所有的信息，写入到STDERR，或在CURLOPT_STDERR中指定的文件。
         */
        $this->setOpt(CURLOPT_VERBOSE, $on);

        /*
         * CURLOPT_STDERR	错误输出的地址，取代默认的STDERR。
         */
        $this->setOpt(CURLOPT_STDERR, $output);
    }

    /**
     * Destruct
     *
     * @access public
     */
    public function __destruct()
    {
        $this->close();
    }

    /*
     * 读取不可访问属性的值时，__get() 会被调用。
     */
    public function __get($name)
    {
        $return = null;

        /*
         * private static $deferredProperties = array('effectiveUrl','rfc2616','rfc6265','totalTime',);
         *
         */
        if (in_array($name, self::$deferredProperties) && is_callable(array($this, $getter = '__get_' . $name))
        ) {
            $return = $this->$name = $this->$getter();
        }
        return $return;
    }

    /**
     * Get Effective Url
     *
     * @access private
     */
    private function __get_effectiveUrl()
    {
        //return call_user_func_array('curl_getinfo', $args);
        return $this->getInfo(CURLINFO_EFFECTIVE_URL);
    }

    /**
     * Get RFC 2616
     *
     * @access private
     */
    private function __get_rfc2616()
    {
        /*
         * array_fill_keys — 使用指定的键和值填充数组
         */
        return array_fill_keys(self::$RFC2616, true);
    }

    /**
     * Get RFC 6265
     *
     * @access private
     */
    private function __get_rfc6265()
    {
        /*
         * array_fill_keys — 使用指定的键和值填充数组
         */
        return array_fill_keys(self::$RFC6265, true);
    }

    /**
     * Get Total Time
     *
     * @access private
     */
    private function __get_totalTime()
    {
        //return call_user_func_array('curl_getinfo', $args);
        return $this->getInfo(CURLINFO_TOTAL_TIME);
    }

}
