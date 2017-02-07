<?php

//namespace Curl;

require __DIR__ . '/ArrayUtil.php';
require __DIR__ . '/CaseInsensitiveArray.php';
require __DIR__ . '/Curl.php';

class MultiCurl
{

    /*
     * $this->baseUrl = $url;
     */
    public $baseUrl = null;

    /*
     * $this->multiCurl = curl_multi_init();
     */
    public $multiCurl;

    /*
     * $curl->id = $this->nextCurlId++;
     * $this->curls[$curl->id] = $curl;
     */
    private $curls = array();

    /*
     * $this->activeCurls[$curl->id] = $curl;
     */
    private $activeCurls = array();

    /*
     * public function start()
    {
        if ($this->isStarted) {
            return;
        }
     *
     */
    private $isStarted = false;

    /*
     * $concurrency = $this->concurrency;
     *
     */
    private $concurrency = 25;

    /*
     * $curl->id = $this->nextCurlId++;
     * $this->curls[$curl->id] = $curl;
     */
    private $nextCurlId = 0;

    /*
     * $this->beforeSendFunction = $callback;
     */
    private $beforeSendFunction = null;

    /*
     * $this->successFunction = $callback;
     */
    private $successFunction = null;

    /*
     * $this->errorFunction = $callback;
     */
    private $errorFunction = null;
    private $completeFunction = null;

    private $cookies = array();

    /*
     * $this->headers = new CaseInsensitiveArray();
     */
    private $headers = array();

    /*
     * $this->options[$option] = $value;
     */
    private $options = array();

    private $jsonDecoder = null;
    private $xmlDecoder = null;

    /**
     * Construct
     *
     * @access public
     * @param  $base_url
     */
    public function __construct($base_url = null)
    {
        /*
         * curl_multi_init — 返回一个新cURL批处理句柄 允许并行地处理批处理cURL句柄。
         */
        $this->multiCurl = curl_multi_init();
        $this->headers = new CaseInsensitiveArray();
        $this->setUrl($base_url);
    }

    /**
     * Set Url
     *
     * @access public
     * @param  $url
     */
    public function setUrl($url)
    {
        $this->baseUrl = $url;
    }

    /**
     * Success
     *
     * @access public
     * @param  $callback
     */
    /*
     * $multi_curl->success(function($instance) {
            echo 'call to "' . $instance->url . '" was successful.' . "\n";
            echo 'response:' . "\n";
            var_dump($instance->response);
        });
     *
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
    /*
     * $multi_curl->error(function($instance) {
            echo 'call to "' . $instance->url . '" was unsuccessful.' . "\n";
            echo 'error code: ' . $instance->errorCode . "\n";
            echo 'error message: ' . $instance->errorMessage . "\n";
        });
     *
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

    /*
     * $multi_curl->complete(function($instance) {
            echo 'call completed' . "\n";
        });
     */
    public function complete($callback)
    {
        $this->completeFunction = $callback;
    }

    /**
     * Add Get
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return object
     */
    /*
     * $multi_curl->addGet('https://www.google.com/search', array('q' => 'hello world',));
     *
     * $multi_curl->addGet('https://duckduckgo.com/', array('q' => 'hello world',));
     *
     * $multi_curl->addGet('https://www.bing.com/search', array('q' => 'hello world',));
     *
     */
    public function addGet($url, $data = array())
    {
        if (is_array($url)) {
            $data = $url;
            $url = $this->baseUrl;
        }
        $curl = new Curl();
        $curl->setUrl($url, $data);

        /*
         * CURLOPT_CUSTOMREQUEST	请求时，使用自定义的 Method 来代替"GET"或"HEAD"。
         * 对 "DELETE" 或者其他更隐蔽的 HTTP 请求有用。 有效值如 "GET"，"POST"，"CONNECT"等等；
         * 也就是说，不要在这里输入整行 HTTP 请求。例如输入"GET /index.html HTTP/1.0\r\n\r\n"是不正确的。
         */
        $curl->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');

        /*
         * CURLOPT_HTTPGET	TRUE 时会设置 HTTP 的 method 为 GET，由于默认是 GET，所以只有 method 被修改时才需要这个选项。
         */
        $curl->setOpt(CURLOPT_HTTPGET, true);
        $this->queueHandle($curl);
        return $curl;
    }

    /**
     * Queue Handle
     *
     * @access private
     * @param  $curl
     */
    private function queueHandle($curl)
    {
        // Use sequential ids to allow for ordered post processing.
        $curl->id = $this->nextCurlId++;
        $this->curls[$curl->id] = $curl;
    }

    /**
     * Start
     *
     * @access public
     */
    public function start()
    {
        if ($this->isStarted) {
            return;
        }

        $this->isStarted = true;

        $concurrency = $this->concurrency;
        if ($concurrency > count($this->curls)) {
            $concurrency = count($this->curls);
        }

        for ($i = 0; $i < $concurrency; $i++) {
            /*
             * array_shift — 将数组开头的单元移出数组
             */
            $this->initHandle(array_shift($this->curls));
        }

        do {
            /*
             * curl_multi_select — 等待所有cURL批处理中的活动连接
             * 成功时返回描述符集合中描述符的数量。失败时，select失败时返回-1，否则返回超时(从底层的select系统调用).
             */
            curl_multi_select($this->multiCurl);

            /*
             * curl_multi_exec — 运行当前 cURL 句柄的子连接
             * 处理在栈中的每一个句柄。无论该句柄需要读取或写入数据都可调用此方法。
             *
             * 参数
             * mh 由 curl_multi_init() 返回的 cURL 多个句柄。
             * still_running 一个用来判断操作是否仍在执行的标识的引用。
             *
             * 返回值
             * 一个定义于 cURL 预定义常量中的 cURL 代码。
             *
             */
            curl_multi_exec($this->multiCurl, $active);

            /*
             * curl_multi_info_read — 获取当前解析的cURL的相关传输信息
             */
            while (!($info_array = curl_multi_info_read($this->multiCurl)) === false) {
                if ($info_array['msg'] === CURLMSG_DONE) {
                    foreach ($this->activeCurls as $key => $ch) {
                        if ($ch->curl === $info_array['handle']) {
                            // Set the error code for multi handles using the "result" key in the array returned by
                            // curl_multi_info_read(). Using curl_errno() on a multi handle will incorrectly return 0
                            // for errors.
                            $ch->curlErrorCode = $info_array['result'];
                            $ch->exec($ch->curl);

                            unset($this->activeCurls[$key]);

                            // Start a new request before removing the handle of the completed one.
                            if (count($this->curls) >= 1) {
                                $this->initHandle(array_shift($this->curls));
                            }

                            /*
                             * curl_multi_remove_handle — 移除curl批处理句柄资源中的某个句柄资源
                             */
                            curl_multi_remove_handle($this->multiCurl, $ch->curl);

                            break;
                        }
                    }
                }
            }

            if (!$active) {
                $active = count($this->activeCurls);
            }
        } while ($active > 0);

        $this->isStarted = false;
    }

    /**
     * Init Handle
     *
     * @access private
     * @param  $curl
     * @throws \ErrorException
     */
    private function initHandle($curl)
    {
        // Set callbacks if not already individually set.
        if ($curl->beforeSendFunction === null) {
            $curl->beforeSend($this->beforeSendFunction);
        }
        if ($curl->successFunction === null) {
            $curl->success($this->successFunction);
        }
        if ($curl->errorFunction === null) {
            $curl->error($this->errorFunction);
        }
        if ($curl->completeFunction === null) {
            $curl->complete($this->completeFunction);
        }

        $curl->setOpts($this->options);
        $curl->setHeaders($this->headers);

        foreach ($this->cookies as $key => $value) {
            $curl->setCookie($key, $value);
        }

        $curl->setJsonDecoder($this->jsonDecoder);
        $curl->setXmlDecoder($this->xmlDecoder);

        /*
         * curl_multi_add_handle — 向curl批处理会话中添加单独的curl句柄
         */
        $curlm_error_code = curl_multi_add_handle($this->multiCurl, $curl->curl);
        if (!($curlm_error_code === CURLM_OK)) {
            throw new \ErrorException('cURL multi add handle error: ' . curl_multi_strerror($curlm_error_code));
        }

        $this->activeCurls[$curl->id] = $curl;
        $curl->call($curl->beforeSendFunction);
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
     * Set Opt
     *
     * @access public
     * @param  $option
     * @param  $value
     */
    public function setOpt($option, $value)
    {
        $this->options[$option] = $value;
    }

    /**
     * Set Opts
     *
     * @access public
     * @param  $options
     */
    public function setOpts($options)
    {
        foreach ($options as $option => $value) {
            $this->setOpt($option, $value);
        }
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
    public function setHeader($key, $value)
    {
        $this->headers[$key] = $value;
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
    }

    /**
     * Set Cookie
     *
     * @access public
     * @param  $key
     * @param  $value
     */
    public function setCookie($key, $value)
    {
        $this->cookies[$key] = $value;
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
            $this->cookies[$key] = $value;
        }
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
     */

    /**
     * Add Delete
     *
     * @access public
     * @param  $url
     * @param  $query_parameters
     * @param  $data
     *
     * @return object
     */
    public function addDelete($url, $query_parameters = array(), $data = array())
    {
        if (is_array($url)) {
            $data = $query_parameters;
            $query_parameters = $url;
            $url = $this->baseUrl;
        }
        $curl = new Curl();
        $curl->setUrl($url, $query_parameters);
        $curl->setOpt(CURLOPT_CUSTOMREQUEST, 'DELETE');
        $curl->setOpt(CURLOPT_POSTFIELDS, $curl->buildPostData($data));
        $this->queueHandle($curl);
        return $curl;
    }

    /**
     * Add Download
     *
     * @access public
     * @param  $url
     * @param  $mixed_filename
     *
     * @return object
     */
    public function addDownload($url, $mixed_filename)
    {
        $curl = new Curl();
        $curl->setUrl($url);

        // Use tmpfile() or php://temp to avoid "Too many open files" error.
        if (is_callable($mixed_filename)) {
            $callback = $mixed_filename;
            $curl->downloadCompleteFunction = $callback;
            $curl->fileHandle = tmpfile();
        } else {
            $filename = $mixed_filename;
            $curl->downloadCompleteFunction = function ($instance, $fh) use ($filename) {
                file_put_contents($filename, stream_get_contents($fh));
            };
            $curl->fileHandle = fopen('php://temp', 'wb');
        }

        $curl->setOpt(CURLOPT_FILE, $curl->fileHandle);
        $curl->setOpt(CURLOPT_CUSTOMREQUEST, 'GET');
        $curl->setOpt(CURLOPT_HTTPGET, true);
        $this->queueHandle($curl);
        return $curl;
    }

    /**
     * Add Head
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return object
     */
    public function addHead($url, $data = array())
    {
        if (is_array($url)) {
            $data = $url;
            $url = $this->baseUrl;
        }
        $curl = new Curl();
        $curl->setUrl($url, $data);
        $curl->setOpt(CURLOPT_CUSTOMREQUEST, 'HEAD');
        $curl->setOpt(CURLOPT_NOBODY, true);
        $this->queueHandle($curl);
        return $curl;
    }

    /**
     * Add Options
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return object
     */
    public function addOptions($url, $data = array())
    {
        if (is_array($url)) {
            $data = $url;
            $url = $this->baseUrl;
        }
        $curl = new Curl();
        $curl->setUrl($url, $data);
        $curl->removeHeader('Content-Length');
        $curl->setOpt(CURLOPT_CUSTOMREQUEST, 'OPTIONS');
        $this->queueHandle($curl);
        return $curl;
    }

    /**
     * Add Patch
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return object
     */
    public function addPatch($url, $data = array())
    {
        if (is_array($url)) {
            $data = $url;
            $url = $this->baseUrl;
        }
        $curl = new Curl();
        $curl->setUrl($url);
        $curl->removeHeader('Content-Length');
        $curl->setOpt(CURLOPT_CUSTOMREQUEST, 'PATCH');
        $curl->setOpt(CURLOPT_POSTFIELDS, $data);
        $this->queueHandle($curl);
        return $curl;
    }

    /**
     * Add Post
     *
     * @access public
     * @param  $url
     * @param  $data
     * @param  $follow_303_with_post
     *     If true, will cause 303 redirections to be followed using GET requests (default: false).
     *     Note: Redirections are only followed if the CURLOPT_FOLLOWLOCATION option is set to true.
     *
     * @return object
     */
    public function addPost($url, $data = array(), $follow_303_with_post = false)
    {
        if (is_array($url)) {
            $follow_303_with_post = (bool)$data;
            $data = $url;
            $url = $this->baseUrl;
        }

        $curl = new Curl();

        if (is_array($data) && empty($data)) {
            $curl->removeHeader('Content-Length');
        }

        $curl->setUrl($url);

        /*
         * For post-redirect-get requests, the CURLOPT_CUSTOMREQUEST option must not
         * be set, otherwise cURL will perform POST requests for redirections.
         */
        if (!$follow_303_with_post) {
            $curl->setOpt(CURLOPT_CUSTOMREQUEST, 'POST');
        }

        $curl->setOpt(CURLOPT_POST, true);
        $curl->setOpt(CURLOPT_POSTFIELDS, $curl->buildPostData($data));
        $this->queueHandle($curl);
        return $curl;
    }

    /**
     * Add Put
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return object
     */
    public function addPut($url, $data = array())
    {
        if (is_array($url)) {
            $data = $url;
            $url = $this->baseUrl;
        }
        $curl = new Curl();
        $curl->setUrl($url);
        $curl->setOpt(CURLOPT_CUSTOMREQUEST, 'PUT');
        $put_data = $curl->buildPostData($data);
        if (is_string($put_data)) {
            $curl->setHeader('Content-Length', strlen($put_data));
        }
        $curl->setOpt(CURLOPT_POSTFIELDS, $put_data);
        $this->queueHandle($curl);
        return $curl;
    }

    /**
     * Add Search
     *
     * @access public
     * @param  $url
     * @param  $data
     *
     * @return object
     */
    public function addSearch($url, $data = array())
    {
        if (is_array($url)) {
            $data = $url;
            $url = $this->baseUrl;
        }
        $curl = new Curl();
        $curl->setUrl($url);
        $curl->setOpt(CURLOPT_CUSTOMREQUEST, 'SEARCH');
        $put_data = $curl->buildPostData($data);
        if (is_string($put_data)) {
            $curl->setHeader('Content-Length', strlen($put_data));
        }
        $curl->setOpt(CURLOPT_POSTFIELDS, $put_data);
        $this->queueHandle($curl);
        return $curl;
    }

    /**
     * Add Curl
     *
     * Add a Curl instance to the handle queue.
     *
     * @access public
     * @param  $curl
     *
     * @return object
     */
    public function addCurl(Curl $curl)
    {
        $this->queueHandle($curl);
        return $curl;
    }



    /**
     * Close
     *
     * @access public
     */
    public function close()
    {
        foreach ($this->curls as $curl) {
            $curl->close();
        }

        if (is_resource($this->multiCurl)) {
            curl_multi_close($this->multiCurl);
        }
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
     * Set Basic Authentication
     *
     * @access public
     * @param  $username
     * @param  $password
     */
    public function setBasicAuthentication($username, $password = '')
    {
        $this->setOpt(CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        $this->setOpt(CURLOPT_USERPWD, $username . ':' . $password);
    }

    /**
     * Set Concurrency
     *
     * @access public
     * @param  $concurrency
     */
    public function setConcurrency($concurrency)
    {
        $this->concurrency = $concurrency;
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
        $this->setOpt(CURLOPT_HTTPAUTH, CURLAUTH_DIGEST);
        $this->setOpt(CURLOPT_USERPWD, $username . ':' . $password);
    }

    /**
     * Set Port
     *
     * @access public
     * @param  $port
     */
    public function setPort($port)
    {
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
        $this->setOpt(CURLOPT_CONNECTTIMEOUT, $seconds);
    }

    /**
     * Set Cookie String
     *
     * @access public
     * @param  $string
     */
    public function setCookieString($string)
    {
        $this->setOpt(CURLOPT_COOKIE, $string);
    }

    /**
     * Set Cookie File
     *
     * @access public
     * @param  $cookie_file
     */
    public function setCookieFile($cookie_file)
    {
        $this->setOpt(CURLOPT_COOKIEFILE, $cookie_file);
    }

    /**
     * Set Cookie Jar
     *
     * @access public
     * @param  $cookie_jar
     */
    public function setCookieJar($cookie_jar)
    {
        $this->setOpt(CURLOPT_COOKIEJAR, $cookie_jar);
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
    public function setReferrer($referrer)
    {
        $this->setOpt(CURLOPT_REFERER, $referrer);
    }

    /**
     * Set Timeout
     *
     * @access public
     * @param  $seconds
     */
    public function setTimeout($seconds)
    {
        $this->setOpt(CURLOPT_TIMEOUT, $seconds);
    }

    /**
     * Set User Agent
     *
     * @access public
     * @param  $user_agent
     */
    public function setUserAgent($user_agent)
    {
        $this->setOpt(CURLOPT_USERAGENT, $user_agent);
    }

    /**
     * Unset Header
     *
     * Remove extra header previously set using Curl::setHeader().
     *
     * @access public
     * @param  $key
     */
    public function unsetHeader($key)
    {
        unset($this->headers[$key]);
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
     * Verbose
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
            $this->setOpt(CURLINFO_HEADER_OUT, false);
        }
        $this->setOpt(CURLOPT_VERBOSE, $on);
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

}
