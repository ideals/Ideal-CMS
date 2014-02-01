<?php
/*
	This is phpSitemapNG, a php script that creates your personal google sitemap
	It can be downloaded from http://enarion.net/google/
	License: GPL

	Tobias Kluge, enarion.net

	TODO handle getting and sending of cookies
			Format - in header: "Set-Cookie: $cookie_name=$cookie_value; path=$cookie_path"

*/

define("PSNG_CRAWLER_MAX_FILESIZE", 100000); // only 100k data will be scanned
define("PSNG_CRAWLER_MAX_GETFILE_TIME", 10); //timeout in seconds as a float value

class Crawler
{
    public $urlError = '';
    public $textError = '';
    var $host = '';
    var $protocol = '';
    var $forbiddenKeys = array();
    var $forbidden_dir = array();
    var $forbidden_files = array();
    var $forbiddenPage = array();

    var $fileCounter = 0;
    var $url = '';
    var $withWWW = false;
    var $cur_item = 0;
    var $keys = array();

    var $files = array();
    var $visitedUrls = array();
    var $beforeTimeout;
    var $todo = array();
    var $base = '';
    var $cookies = array();
    var $error = false;
    var $errType;

    function __construct($host, $deadline, $timeout, $delay)
    {
        $this->timeout = $timeout;
        $this->delay = $delay;
        $this->deadline = $deadline;
        $url = parse_url($host);
        if ($url != false) {
            if ($url['scheme'] != "") {
                $this->protocol = $url['scheme'];
            } else {
                $this->protocol = "http";
            }
            $this->host = $url['host'];
            if (substr($this->host, 0, 3) == 'www')
                $this->withWWW = true;
        }

        $this->url = $this->protocol . '://' . $this->host . '/';
        $this->todo[] = array($this->url, '');
        $MET = ini_get("max_execution_time");
        if ($MET == 0) {
            $MET = 60;
        }
        // Если deadline за пределами от 1 до max_execution_time, ставим его в max_execution_time - 1 секунда(на крайний случай)
        if (($this->deadline > 1) && ($this->deadline < ($MET - 1))) {
            $this->deadline = $this->deadline + $this->microtime_float();
        } else {
            $this->deadline = (ini_get("max_execution_time") + $MET) + $this->microtime_float();
        }

//		debug('', 'Crawler created for host '.$this->host.' with protocol '.$this->protocol);
    }

    /**
     * crawles all files that are in the todo list
     * algorithm: breadth first search (former algorithm: dfs)
     */
    function start()
    {
        reset($this->todo);
        while (($this->deadline - $this->microtime_float()) > 1) {

            // Пока не будут обработаны все url сайта
            $url = array_pop($this->todo);
            if (is_null($url) || $url == '')
                break;
            $res = $this->_getFilesForURL($url);
            if ($res === false) {
                $this->urlError = $url;
            }
            if (strval($res) != 'skipMe') {
                usleep(intval($this->delay * 1000000));
            }
            if ($this->error) {
                $this->urlError = $url;
                return false;
            }
        }

        ksort($this->files);
        reset($this->files);
        return count($this->files);
    }

    function microtime_float()
    {
        list ($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    function getTodo()
    {
        return $this->todo;
    }

    function getFiles()
    {
        return $this->files;
    }

    function getDone()
    {
        return $this->visitedUrls;
    }

    function setTodo($todo)
    {
        $this->todo = $todo;
    }

    function setFiles($files)
    {
        if (is_array($files))
            $this->files = $files;
    }

    function setDone($done)
    {
        $this->done = $done;
    }

    function setDirectory($dir)
    {
        $this->path = $dir;
    }

    /**
     * returns number of files
     */
    function size()
    {
        if (!isset($this->countFiles)) {
            $this->countFiles = count($this->files);
        }

        return $this->countFiles;
    }

    function hasFinished()
    {
        return (count($this->todo) == 0);
    }

    /**
     * returns TRUE when the current item is not the last item
     * behaves like in java
     */
    function hasNext()
    {
        if ($this->size() > $this->cur_item)
            return true;
        return false;
    }

    /**
     * returns the current item
     * behaves like in java
     */
    function getNext()
    {
        if (count($this->keys) == 0)
            $this->keys = array_keys($this->files);
        if ($this->hasNext()) {
            $tmp = $this->files[$this->keys[$this->cur_item]];
            $this->cur_item++;
            return $tmp;
        }
        return NULL;
    }

    /**
     * adds list of links extracted from this file $url
     */
    function _getFilesForURL($url)
    {
        $url_from = $url[1];
        $url = $url[0];
        $url = str_replace(' ', '%20', "$url");
        $url = str_replace('\'', '&apos;', "$url");
        $url = str_replace('"', '&quot;', "$url");
        $url = str_replace('>', '&gt;', "$url");
        $url = str_replace('<', '&lt;', "$url");
        $this->visitedUrls[] = $url;

//		debug($url, '<b>Scanning url</b>');
        // if allready in list of files, return
        if (array_key_exists($url, $this->files)) {
//			debug($url, "File already in list of files");
            return 'skipMe';
        }

        // check for non local file links that refers to another host

        if (!($this->_isLocal($url))) {
//			debug($url, 'The url does not match the current host '.$this->host.', only relative links are allowed at the moment!');
            return 'skipMe';
        }

        // Отображаем сообщение о url, который собираемся сканировать
        $this->info($this->beforeTimeout . '. ' . $url);

        // fetch content for given url
        $res = $this->_getURL($url);
        if (!$res) {
            $this->error = true;
            return false;
        }
        // extract headers
        $info = $this->_handleHeaders($res['header']);
        $res = $res['content'];

        if ($info['http_status'] >= '400' && $info['http_status'] < '599') {
            $this->textError = "webpage {$url} is not accessible. error # {$info['http_status']} from {$url_from}";
            $this->info($this->textError);
            if ($info['http_status'] == '503') {
                $this->error = true;
            }
            if ($info['http_status'] == '404') {
                $this->errType = '404';
                $this->error = true;
                return false;
            }
            // we have an error - webpage is not accessible, just leave it
            return;
        }

        // if not allready in list of files, add it
        if (!array_key_exists($url, $this->files) && $info['location'] == '') {
            $info['file'] = $url;
            $this->files[$url] = $info;
            $this->fileCounter++;
//			debug($url, 'Successful added url');
        } elseif ($info['location'] == '') {
//			debug($url, "File already in list of files");
            return;
        } else {
//			debug($url, "Url is only a redirect (http 302)");
        }

        // check location tag (when got a 302 response from webserver)
        $result = array();
        if ($info['location'] != '') {
            $res = '<a href="' . $info['location'] . '"> </a>';
        } else {
            $this->beforeTimeout++;
            $this->info(" Status: " . $info['http_status']);
        }
        // remove html comments
        $a_begin = 0;
        while (true) {
            $a_begin = strpos($res, '<!--', $a_begin);
            if ($a_begin === false) break; // no comment tag found, break

            $a_end = strpos($res, '-->', $a_begin + 3);
            if ($a_end === false) break; // no comment end tag found, break

            $a_end += 3;
            $res = substr_replace($res, '', $a_begin, ($a_end - $a_begin));
        }

        // contribution by vvkov
//		preg_match_all("/<[Aa][ \r\n\t]{1}[^>]*[Hh][Rr][Ee][Ff][^=]*=[ '\"\n\r\t]*([^ \"'>]+)[^>]*>/",$res ,$urls);
        preg_match_all("/<[Aa][^>]*[Hh][Rr][Ee][Ff]=['\"]?([^\"'>]+)[^>]*>/", $res, $urls); // update by TK, 2005-07-27
        $urls_count = count($urls[1]);

        if (preg_match("/<[Bb][Aa][Ss][Ee][^>]*[Hh][Rr][Ee][Ff]=['\"]?([^\"'>]+)[^>]*>/", $res, $matches)) {
            $this->base = $matches[1];
        }

        $ts_begin = $this->microtime_float();
        while ((($ts_middle = ($this->microtime_float() - $ts_begin)) < PSNG_CRAWLER_MAX_GETFILE_TIME) && ($urls_count > 0)) {
            $thisurl = trim(str_replace('&amp;', '&', $urls[1][--$urls_count]));
            if ($thisurl == '' || (strcasecmp(substr($thisurl, 0, strlen('javascript:')), 'javascript:') == 0)) continue;
            // filter out links to fragment ids (same resource) - added mk/2005-11-13
            if ('#' == $thisurl{0}) continue;
            // debug('_'.$thisurl.'_','Extracted url');
            //print '_'.$thisurl.'_'." Extracted url\n";

            $absUrl1 = $this->_absolute($thisurl, $url);
            //debug('_'.$absUrl1.'_', 'After _absolute');
            //print '_'.$absUrl1.'_'. " After _absolute \n\n";
            $absUrl2 = $this->_removeForbiddenKeys($absUrl1);

            // remove "//"
            $start = (strpos($absUrl2, '//') + 3);
            $end = strpos($absUrl2, '?', $start);
            if ($end === false) $end = strlen($absUrl2);
            $absUrl = substr($absUrl2, 0, $start) . str_replace('//', '/', substr($absUrl2, $start, ($end - $start))) . substr($absUrl2, $end);
            //debug($absUrl, "Computed absUrl");

            if ($this->_isLocal($absUrl)) {
                $result[] = $absUrl;
            }
        }
        $result = array_unique($result);

        foreach ($result as $id => $file) {
            if (!in_array($file, $this->visitedUrls) && !array_key_exists($file, $this->files)) {
                // check forbidden files
                if ($this->checkFileName($file)) continue;
                // check forbidden directories
                if ($this->checkDirectoryName($file)) continue;
                //debug($file, 'Adding URL to todo list');

                // add file to todo list
                array_push($this->todo, array($file, $url));
            } // else: file already in list
        }


        return true;
    }

    function _isLocal($givenURL)
    {
        if (preg_match(',^(ftp://|mailto:|news:|javascript:|telnet:|callto:),i', $givenURL)) return false;

        $url = parse_url($givenURL);

        $startDir = $this->host . $this->path;
        $curentDir = $url["host"] . $url["path"];


        $retproto = (substr($curentDir, 0, strlen($startDir)) == $startDir);

        // debug if (!$retproto) echo ($url["host"] . $url["path"] . "!=" . $this->host . $this->path . "<br>");
        return $retproto;
    }

    /**
     * WAS: only allowed masking char: * (before and/or after search string)
     *
     * TODO check this with more data
     */
    function checkFileName($filename)
    {
        $filename = substr($filename, strrpos($filename, '/') + 1);
        if (is_array($this->forbidden_files) && count($this->forbidden_files) > 0) {
            foreach ($this->forbidden_files as $id => $file) {
                if ($file == '') continue;
                $pos = strpos($filename, $file);
                /*	    		$file_search = '';
                                  if (!(($as = strpos($file, '*')) === FALSE)) {
                                      $file_search = str_replace('*', '', $file);
                                      if ($as == 0) $pos = @strpos($filename, $file_search, (strlen($filename)-strlen($file_search)));
                                      if ($as == strlen($file_search)) $pos = (@strpos($filename, $file_search) != 0);
                                  } else {
                                    $pos = ($filename === $file);
                                  }
                */
                if ($pos === false) continue;
                return true;
            }
        }
        if (is_array($this->forbiddenPage) && count($this->forbiddenPage) > 0) {
            foreach ($this->forbiddenPage as $k => $v) {
                if ($v == '') continue;
                if (strcasecmp($filename, $v) == 0) return true;
            }
        }
        return false;
    }

    function checkDirectoryName($directory)
    {
        $directory = substr($directory, 0, strrpos($directory, '/') + 1); // with last "/"
        if (is_array($this->forbidden_dir) && count($this->forbidden_dir) > 0) {
            foreach ($this->forbidden_dir as $id => $dir) {
                if ($dir == '') continue;
                $pos = strpos($directory, $dir);
                /*	    		$dir_search = '';
                                  if (!(($as = strpos($dir, '*')) === FALSE)) {
                                      $dir_search = str_replace('*', '', $dir);
                                      if ($as == 0) $pos = @strpos($directory, $dir_search, (strlen($directory)-strlen($dir_search)));
                                      if ($as == strlen($dir_search)) $pos = (@strpos($directory, $dir_search) != 0);
                                  } else {
                                    $pos = ($directory === $dir);
                                  }
                */ // echo "directory: $directory, dir: $dir, dir_search: $dir_search, pos: $pos<br>\n";
                if ($pos === false) continue;
                return true;
            }
        }
        return false;
    }

    function _handleHeaders($header)
    {
        $res = array();
        $res['http_status'] = '';
        $res['lastmod'] = '';
        $res['date'] = '';
        $res['size'] = '';
        $res['location'] = '';
        // TODO what about http result? after 'HTTP/' => split(" " ...) => [1]
        if (is_array($header)) {
            foreach ($header as $key => $value) {
                if ($key == '' && substr($value, 0, strlen('HTTP/'))) {
                    $s = split(" ", $value);
                    $res['http_status'] = $s[1];
                } elseif ($key == "Last-Modified") {
                    $res['lastmod'] = strtotime(trim($value)); // no dynamic (php/other script) generated page
                } elseif ($key == "Date") {
                    $res['date'] = strtotime(trim($value));
                } elseif ($key == "Content-Length") {
                    $res['size'] = trim($value);
                } elseif ($key == "Location") {
                    $res['location'] = trim($value);
                } elseif ($key == 'Set-Cookie') {
                    $parts = explode(";", trim($value));
                    $cookie_name = '';
                    $cookie = array();
                    foreach ($parts as $id => $part) {
                        $p = explode('=', trim($part));
                        $cookie[$p[0]] = $p[1];
                        if ($p[0] != 'path' && $p[0] != 'path' && strpos($p[0], 'expires') === false && $p[0] != 'domain') {
                            $cookie_name = $p[0];
                        }
                    }
                    /*				echo "got cookie: ";
                                    print_r($cookie);
                                    echo "<br>\n";
                    */ // add cookie if not already set
                    if (!isset($this->cookies[$cookie_name])) {
                        $this->cookies[$cookie_name] = $cookie;
                        $this->forbiddenKeys[] = $cookie_name;
                    }
                } elseif ($key == "Pragma") {
                    $pragma = trim($value);
                    // TODO по стандартам - сделано всё правильно, но получается если у динамических страниц
                    // не указан lastmod, то частота обновлений будет - always - что не верно
                    // поэтому тут всё закоментил

                    //if ($pragma == "no-cache") { // handle non-cached files -> normaly dynamic created pages
                    //	if (!isset ($res['lastmod'])) $res['lastmod'] = $res['date'];
                    //	$res['changefreq'] = 'always';
                    //}
                }
            }
        }
        // И здесь тоже я закомментил:
        //if ($res['date'] != '' && $res['lastmod'] == '') $res['lastmod'] = $res['date'];
//		debug($header, 'Header');
//		debug($res, 'Extracted information from headers');
        /*
                echo "final cookies: ";
                print_r($this->cookies);
                echo "<br>\n";
        */
        return $res;
    }

    function _removeForbiddenKeys($url)
    {
        $paramsStart = strpos($url, '?');
        if ($paramsStart !== false) { // url has no parameters, don't search for keys
            foreach ($this->forbiddenKeys as $id => $key) {
                if ($key == '') continue; // empty key => ignore it
                $start = strpos($url, $key, $paramsStart);
                while ($start != false) {
                    $end = strpos($url, '&', $start);
                    if ($end !== false) {
                        $url = substr($url, 0, $start) . substr($url, $end + 1);
                    } else {
                        $url = substr($url, 0, $start);
                    }
                    $start = strpos($url, $key, $paramsStart);
                } // else: does not contain key
            }
        }
        // remove anchor links : beginning with # to the end of the url
        // echo "$url<br>\n";
        if (strpos($url, '#') !== false) {
            $url = substr($url, 0, strpos($url, '#'));
        }
        // remove empty & and ?
        while (substr($url, strlen($url) - 1) == "&") {
            $url = substr($url, 0, strlen($url) - 1);
        }
        while (substr($url, strlen($url) - 1) == "?") {
            $url = substr($url, 0, strlen($url) - 1);
        }
        return $url;
    }

    function _getURL($urlString)
    {
        $url = parse_url($urlString);
        $url_scheme = isset($url['scheme']) ? $url['scheme'] : '';
        $url_host = isset($url['host']) ? $url['host'] : '';
        $url_port = isset($url['port']) ? $url['port'] : '';
        $url_path = isset($url['path']) ? $url['path'] : '';
        $url_path = str_replace(' ', '%20', $url_path); // replace spaces in url
        $url_query = isset($url['query']) ? $url['query'] : '';
        $cookie_string = '';
        if (count($this->cookies) > 0) {
            foreach ($this->cookies as $cookie_name => $cookie) {
                // check path - dumb approach (only check if url contains cookie path)
                if (strpos($urlString, $cookie['path'])) {
                    $cookie_string .= $cookie_name . '=' . $cookie[$cookie_name] . '; ';
                }
            }
            if (strlen($cookie_string) > 0) {
                $cookie_string = 'Cookie: ' . $cookie_string . "\r\n";
            }
        }
//		echo "Sending cookie_string: $cookie_string<br>\n";

        if ($url_port == '') {
            if ($url_scheme == 'https') {
                $url_port = "443";
            } else {
                $url_port = "80";
            }
        }
        //		debug($url, 'Parsed URL');
        $fp = fsockopen($url_host, $url_port, $errno, $errstr, $this->timeout);
        if ($fp === false) {
            $this->info($errstr, 'Could not open connection for ' . $urlString . ' (host: ' . $url_host . ', port:' . $url_port . '), Errornumber: ' . $errno);
            return array('header' => array(), 'content' => '');
        }
        $query_encoded = '';
        if ($url_query != '') {
            $query_encoded = '?';
            foreach (split('&', $url_query) as $id => $quer) {
                $v = split('=', $quer);
                if ($v[1] != '') {
                    $query_encoded .= $v[0] . '=' . rawurlencode($v[1]) . '&';
                } else {
                    $query_encoded .= $v[0] . '&';
                }
            }
            $query_encoded = substr($query_encoded, 0, strlen($query_encoded) - 1);
            $query_encoded = str_replace('%2B', '+', $query_encoded);
        }

        $get = "GET " . $url_path . $query_encoded . " HTTP/1.1\r\n";
        $get .= "Host: " . $url_host . "\r\n";
        $get .= "User-Agent: Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.0; phpSitemapNG " . PSNG_VERSION . ")\r\n";
        $get .= "Referer: " . $url_scheme . '://' . $url_host . $url_path . "\r\n";
        $get .= $cookie_string;
        $get .= "Connection: close\r\n\r\n";
        $this->debug(str_replace("\n", "<br>\n", $get), 'GET-Query');
        socket_set_blocking($fp, true);
        fwrite($fp, $get);

        $res = '';
        $head_done = false;
        $ts_begin = $this->microtime_float();
        // source for chunk-decoding: http://www.phpforum.de/archiv_13065_fsockopen@end@chunked@geht@nicht_anzeigen.html

        // get headers
        $currentHeader = '';
        while ('' != ($line = trim(fgets($fp, 1024)))) {
            if (false !== ($pos = strpos($line, ':'))) {
                $currentHeader = substr($line, 0, $pos);
                $header[$currentHeader] = trim(substr($line, $pos + 1));
            } else {
                @$header[$currentHeader] .= $line;
            }
        }

        // check for chunk encoding
        if (isset($header['Transfer-Encoding']) && $header['Transfer-Encoding'] == 'chunked') {
            $chunk = hexdec(fgets($fp, 1024));
        } else {
            $chunk = -1;
        }

        // check file size
        if (isset($header['Content-Length']) && $header['Content-Length'] > PSNG_CRAWLER_MAX_FILESIZE) {
            $this->info($size, "File size " . $header['Content-Length'] . " of " . $urlString . " exceeds file size limit of " . PSNG_CRAWLER_MAX_FILESIZE . " byte!");
            fclose($fp);
            return array('header' => $header, 'content' => '');
        }

        // get content
        $res = '';
        while ($chunk != 0 && !feof($fp)) {
            // echo "chunking...<br>\n";
            if ($chunk > 0) {
                $part = fread($fp, $chunk);
                $chunk -= strlen($part);
                $res .= $part;

                if ($chunk == 0) {
                    if (fgets($fp, 1024) != "\r\n") $this->debug('Error in chunk-decoding');
                    $chunk = hexdec(fgets($fp, 1024));
                }
            } else {
                $res .= fread($fp, 1024);
            }
            // handle local timeout for fetching file
            // Пока реакция отключена, т.к. не сделано продолжение
            if (($ts_middle = $this->microtime_float() - $ts_begin) > PSNG_CRAWLER_MAX_GETFILE_TIME) {
                /// $this->info('error in ' . $urlString . ' handle local timeout for fetching file');
                //return false;
                //break;
            }
        }
        fclose($fp);

        return array('header' => $header, 'content' => $res);
    }

    // based from: http://www.php-faq.de/q/q-regexp-links-absolut.html
    /**
     * Purpose: turn a link $relative found in the resource $absolute
     * (which must be a fully-qualified URI) into another fully-qualified
     * ("absolute") URI.
     * The $absolute parameter is assumed to contain a valid URI *without*
     * a fragment ID part: no checks are done; $relative can be any kind of
     * link found in this resource.
     *
     * Modified by Marjolein Katsma to support links with only a fragment id
     * or with only GET parameters.
     */
    /*	function _absolute($relative, $absolute) {

            // Link ist schon absolut
            if (preg_match(',^(https?://|ftp://|mailto:|news:|javascript:|telnet:|callto:),i', $relative))
            {
                // hostname is not the same (with/without www) than the one used in the link
                if (substr($relative, 0, 4) == 'http')
                {
                    $url = parse_url($relative);
                    if ($url['host'] != $this->host
                            && (
                                (("www.".$url['host']) == $this->host)
                                && $this->withWWW == TRUE
                                || ($url['host'] == ("www.".$this->host))
                                && $this->withWWW == FALSE
                            )
                        )
                    {
                        $r = $relative;													# @@@ not used mk/2005-11-13
                        $relative = str_replace($url['host'], $this->host, $relative); // replace hostname that differs from local
                    }
                    // is pure hostname without path - so add a /
                    if (!isset($url['path']) || ($url['path'] == '' && substr($relative, -1) != '/'))
                    {
                        $relative .= '/';
                    }
                }
                return $relative;
            }

            // parse_url() nimmt die URL auseinander
            // @@@ does not take into account that parse_url() may return FALSE on error! mk/2005-11-13
            $url = parse_url($absolute);
            // dirname() erkennt auf / endende URLs nicht
            if ($url['path'] {(strlen($url['path'])- 1)} == '/')
                $dir = substr($url['path'], 0, strlen($url['path']) - 1);
            else
                $dir = dirname($url['path']);

            // absoluter Link auf dem gleichen Server
            if ($relative{0} == '/') {
                $relative = substr($relative, 1);
                $dir = '';
            }

            // set it to default host // TK
            /* - assumed $url['host'] is set - not necessarily true for all schemes! condition added
             * - corrected tests for return value of strpos (result 0 is a match!!)
             * mk/2005-11-13
             * /
            if (isset($url['host']))
            {
                if ($url['host'] != $this->host &&
                    (strpos($url['host'], $this->host) !== FALSE || strpos($this->host, $url['host']) !== FALSE))
                {
                    $url['host'] = $this->host;
                }
            }

            /* GET-parameter links: replace any existing GET
             * parameters or append to (sanitized) $absolute
             * mk/2005-11-13
             * /
            if ('?' == $relative{0})
            {
                // prepare for building new URL
                $query = $relative;
    echo 'Crawler _absolute: '.'query '.$query.'<br/>';
            }
            /* fragment-id links: should be appended to (sanitized) $absolute
             * mk/2005-11-13
             * /
            elseif ('#' == $relative{0})
            {
                // prepare for building new URL
                $fragment = $relative;
    echo 'Crawler _absolute: '.'fragment '.$fragment.'<br/>';
            }
            // other relative link: build a new path from current directory/path and $relative
            else
            {
                // dirname() erkennt auf / endende URLs nicht
                // assumes $url['path'] is set - not necessarily true! condition added mk/2005-11-13
                if (isset($url['path']))
                {
                    if ('/' == substr($url['path'], -1))
                    {
                        $dir = substr($url['path'], 0, strlen($url['path']) - 1);
    echo 'Crawler _absolute: '.'path '.$url['path'].' ends in / - dir: '.$dir.'<br/>';
                    }
                    else
                    {
                        $dir = dirname($url['path']);
    echo 'Crawler _absolute: '.'path '.$url['path'].' does NOT end in / - dir: '.$dir.'<br/>';
                    }
                }
                else
                {
                    $dir = '/';															# minimal dir to use in URL path
                }

                // absoluter Link auf dem gleichen Server == absolute link to same server/host
                # @@@ mk/2005-11-13 no / between host and relative??
                if ($relative{0} == '/') {
    echo 'Crawler _absolute: '.'absolute link to '.$relative.'<br/>';
                    $relative = substr($relative, 1);
                    $dir = '/';
                } else {
                    // Link fдngt mit ./ an
                    if (substr($relative, 0, 2) == './')
                    {
                        $relative = substr($relative, 2);
                    }
                    // Referenzen auf hцher liegende Verzeichnisse auflцsen
                    else
                    {
                        while (substr($relative, 0, 3) == '../') {
                            $relative = substr($relative, 3);
                            $dir = substr($dir, 0, strrpos($dir, '/'));
                        }
                    }
                }

                // now construct new path mk/2005-11-13
                $path = $dir.$relative;
    echo 'Crawler _absolute: '.'new path '.$path.'<br/>';
            }

            // volle URL zurьckgeben
            // did not support all parts or a URL! - corrected mk/2005-11-13
            $abs  = ('file' == $url['scheme']) ? $url['scheme'].':///' : $url['scheme'].'://';
            $abs .= (isset($url['user'])) ? $abs .= $url['user'].( (isset($url['pass'])) ? ':'.$url['pass'] : '' ).'@' : '';
            $abs .= (isset($url['host'])) ? $url['host'] : '';
            $abs .= (isset($url['port'])) ? ':'.$url['port'] : '';
            $abs .= (isset($path)) ? $path : (isset($url['path']) ? $url['path'] : '/');	# maintain existing path if we didn't build a new one; make sure we have at least a '/'
            $abs .= (isset($query)) ? $query : '';											# append specified query link
            $abs .= (isset($fragment)) ? $fragment : '';									# append specified fragment link

    //mecho 'Crawler _absolute: '.'new url '.$abs.'<br/>';
            return $abs;
        }
    */
    function _absolute($relative, $absolute)
    {
        // Link ist schon absolut
        if (preg_match(',^(https?://|ftp://|mailto:|news:|javascript:|telnet:|callto:),i', $relative)) {
            // hostname is not the same (with/without www) than the one used in the link
            if (substr($relative, 0, 4) == 'http') {
                $url = parse_url($relative);
                if ($url['host'] != $this->host && ((("www." . $url['host']) == $this->host) && $this->withWWW == true || ($url['host'] == ("www." . $this->host)) && $this->withWWW == false)) {
                    $r = $relative;
                    $relative = str_replace($url['host'], $this->host, $relative); // replace hostname that differes from local
                }
                // is pure hostname without path - so add a /
                if (!array_key_exists('path', $url) || $url['path'] == '' && substr($relative, -1) != '/')
                    $relative .= '/';
            }
            return $relative;
        }
        //print "relative $relative\n";
        // parse_url() nimmt die URL auseinander
        $url = parse_url($absolute);

        //print "dir ${url['path']}\n";
        // dirname() erkennt auf / endende URLs nicht
        if ($url['path']{strlen($url['path']) - 1} == '/')
            $dir = substr($url['path'], 0, strlen($url['path']) - 1);
        else {
            $dir = dirname($url['path']);
            if ($relative{0} == '?')
                $relative = basename($url['path']) . $relative;
        }
        //print "dir $dir\n";

        // absoluter Link auf dem gleichen Server
        if ($relative{0} == '/') {
            $relative = substr($relative, 1);
            $dir = '';
        }

        // set it to default host // TK
        if ($url['host'] != $this->host && (strpos($url['host'], $this->host) != false || strpos($this->host, $url['host']) != false)) {
            $url['host'] = $this->host;
        }

        // Link fдngt mit ./ an
        if (substr($relative, 0, 2) == './')
            $relative = substr($relative, 2);

        // Referenzen auf hцher liegende Verzeichnisse auflцsen
        else
            while (substr($relative, 0, 3) == '../') {
                $relative = substr($relative, 3);
                $dir = substr($dir, 0, strrpos($dir, '/'));
            }

        // if base is set, add it.
        if (strlen($this->base)) {
            return $this->base . urldecode($relative);
        }

        // volle URL zurьckgeben
        return sprintf('%s://%s%s/%s', $url['scheme'], $url['host'], $dir, urldecode($relative));
    }


    /* better compare function: contains */
    function _fl_contains($key, $array)
    {
        if (is_array($array) && count($array) > 0) {
            foreach ($array as $id => $val) {
                $pos = @ strpos($key, $val);
                if ($pos === false) continue;
                return true;
            }
        }

        return false;
    }

    /**
     * set list of forbidden directories
     */
    function setForbiddenDirectories($directories = array())
    {
        $this->forbidden_dir = $directories;
    }

    /**
     * set list of forbidden files
     */
    function setForbiddenFiles($files = array())
    {
        $this->forbidden_files = $files;
    }

    /**
     * Страницы которые не нужны в карте сайта
     * @param array $page
     */
    function setForbiddenPages($page = array())
    {
        $this->forbiddenPage = $page;
    }

    function setForbiddenKeys($keys)
    {
        $this->forbiddenKeys = $keys;
        //    	if(!in_array($key, $this->forbiddenKeys)) $this->forbiddenKeys[] = $key;
    }

    function debug($param, $msg = '')
    {
        return;
        echo "\n{$param}\n{$msg}";
    }

    function info($param, $msg = '')
    {
        echo "\n{$param}\n{$msg}";
    }


}

?>