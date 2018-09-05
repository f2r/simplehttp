<?php
namespace f2r\CurlStub;

class CurlStub {
    static private $instance;
    private $callables = [];

    static public function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setCallback($name, callable $callable)
    {
        $this->callables[$name] = $callable;
        return $this;
    }

    public function __call($name, $args)
    {
        if (isset($this->callables[$name])) {
            return call_user_func_array($this->callables[$name], $args);
        }
        return null;
    }
}

function curl_exec($ch)
{
    return CurlStub::getInstance()->curl_exec($ch);
}

function curl_init($url = null)
{
    return CurlStub::getInstance()->curl_init($url);
}

function curl_setopt($ch, $option, $value)
{
    return CurlStub::getInstance()->curl_setopt($ch, $option, $value);
}

function curl_setopt_array($ch, $options)
{
    return CurlStub::getInstance()->curl_setopt_array($ch, $options);
}

function curl_getinfo($ch, $option = null)
{
    return CurlStub::getInstance()->curl_getinfo($ch, $option);
}

function curl_error($ch)
{
    return CurlStub::getInstance()->curl_error($ch);
}

function curl_close($ch)
{
    return CurlStub::getInstance()->curl_close($ch);
}

function loadFile($file)
{
    $code = file_get_contents($file);
    $code = preg_replace('`(\\\\?(curl_\w+))\(`', '\\' . __NAMESPACE__ . '\\\\$2(', $code);
    $code = str_replace('<?php', '', $code);
    eval($code);
}