<?php

namespace f2r\SimpleHttp;


class CurlAsyncExec extends CurlExec
{
    public function request($url, array $curlOpt)
    {
        $response = null;
        if (static::$debugHook !== null) {
            $response = call_user_func(static::$debugHook, $url, $curlOpt);
        }

        if ($response !== null) {
            return $response;
        }

        $logger = $this->options->getLogger();
        $logMessage = 'Async request URL: ';
        $curlOpt[CURLOPT_FOLLOWLOCATION] = true;
        $curlOpt[CURLOPT_MAXREDIRS] = $this->options->getFollowRedirectCount();
        $curlOpt[CURLOPT_REDIR_PROTOCOLS] = CURLPROTO_HTTP + CURLPROTO_HTTPS;
        $curlOpt[CURLOPT_RETURNTRANSFER] = true;
        $curlOpt[CURLOPT_HEADER] =true;
        $this->throwOnForbiddenProtocol($url);
        $this->throwOnForbiddenHost($url);
        $this->throwOnInvalidCharacter($url);

        $asyncWaitDelay = $this->options->getAsyncWaitDelay();
        $ch = curl_init();
        curl_setopt_array($ch, $curlOpt);
        curl_setopt($ch, CURLOPT_URL, $url);
        $logger->debug($logMessage . $url);

        $mh = curl_multi_init();
        curl_multi_add_handle($mh, $ch);
        do {
            curl_multi_exec($mh, $running);
            $select = curl_multi_select($mh, 0);
        } while ($select < 0 and $running);

        $callback = function () use ($mh, $ch, $asyncWaitDelay, $url) {
            do {
                curl_multi_exec($mh, $running);
                if ($running === 1) {
                    usleep($asyncWaitDelay);
                }
            } while ($running);

            $raw = curl_multi_getcontent($ch);
            $result = $this->decomposeRawResponse($raw);
            $info = curl_getinfo($ch);
            $error = curl_error($ch);
            $this->throwOnError($error, $result, $url);
            curl_multi_remove_handle($mh, $ch);
            curl_multi_close($mh);

            return new Response(
                new HeaderResponse($result['headers']),
                $result,
                new CurlInfo($info)
            );
        };
        return new AsyncResponse($callback);
    }
}