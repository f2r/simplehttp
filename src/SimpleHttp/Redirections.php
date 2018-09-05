<?php
namespace f2r\SimpleHttp;


class Redirections implements \Countable, \IteratorAggregate
{
    /**
     * @var RedirectUrl[]
     */
    private $redirections;

    /**
     * @var string
     */
    private $from;

    public function __construct(string $from)
    {
        $this->redirections = [];
        $this->from = $from;
    }

    public function addRedirect(RedirectUrl $redirectUrl)
    {
        $redirectUrl->setSourceUrl($this->from);
        $this->from = $redirectUrl->getUrl();
        $this->redirections[] = $redirectUrl;
        return $this;
    }

    public function count()
    {
        return count($this->redirections);
    }


    public function getIterator()
    {
        return new \ArrayIterator($this->redirections);
    }
}