<?php
/**
 * This file is part of the BEAR.Resource package
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace BEAR\Resource;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use OutOfBoundsException;
use Traversable;
use Ray\Di\Di\Inject;
use Ray\Di\Di\Scope;

/**
 * Interface for resource adapter provider
 *
 * @Scope("prototype")
 */
final class Request implements RequestInterface, ArrayAccess, IteratorAggregate
{
    use BodyArrayAccessTrait;

    /**
     * object URI scheme
     *
     * @var string
     */
    const SCHEME_OBJECT = 'object';

    /**
     * URI
     *
     * @var string
     */
    public $uri;

    /**
     * Resource object
     *
     * @var \BEAR\Resource\ResourceObject
     */
    public $ro;

    /**
     * Method
     *
     * @var string
     */
    public $method = '';

    /**
     * Query
     *
     * @var array
     */
    public $query = [];

    /**
     * Options
     *
     * @var array
     */
    public $options = [];

    /**
     * Request option (eager or lazy)
     *
     * @var string
     */
    public $in;

    /**
     * Links
     *
     * @var LinkType[]
     */
    public $links = [];

    /**
     * Request Result
     *
     * @var Object
     */
    private $result;

    /**
     * {@inheritDoc}
     *
     * @Inject
     */
    public function __construct(InvokerInterface $invoker)
    {
        $this->invoker = $invoker;
    }

    /**
     * Set
     *
     * @param ResourceObject $ro
     * @param string         $uri
     * @param string         $method
     * @param array          $query
     */
    public function set(ResourceObject $ro, $uri, $method, array $query)
    {
        $this->ro = $ro;
        $this->uri = $uri;
        $this->method = $method;
        $this->query = $query;
    }

    /**
     * {@inheritdoc}
     */
    public function withQuery(array $query)
    {
        $this->query = $query;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addQuery(array $query)
    {
        $this->query = array_merge($this->query, $query);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function __invoke(array $query = null)
    {
        if (!is_null($query)) {
            $this->query = array_merge($this->query, $query);
        }
        $result = $this->invoker->invoke($this);

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function toUri()
    {
        $uri = isset($this->ro->uri) && $this->ro->uri ? $this->ro->uri : $this->uri;
        $parsed = parse_url($uri);
        if ($this->query === []) {
            return $uri;
        }
        if (!isset($parsed['scheme'])) {
            return $uri;
        }
        $fullUri = $parsed['scheme'] . "://{$parsed['host']}{$parsed['path']}?" . http_build_query(
            $this->query,
            null,
            '&',
            PHP_QUERY_RFC3986
        );

        return $fullUri;
    }

    /**
     * {@inheritDoc}
     */
    public function toUriWithMethod()
    {
        return "{$this->method} " . $this->toUri();
    }

    /**
     * Render view
     *
     * @return string
     */
    public function __toString()
    {
        if (is_null($this->result)) {
            $this->result = $this->__invoke();
        }

        return (string)$this->result;
    }

    /**
     * Returns the body value at the specified index
     *
     * @param mixed $offset offset
     *
     * @return mixed
     * @throws OutOfBoundsException
     */
    public function offsetGet($offset)
    {
        if (is_null($this->result)) {
            $this->result = $this->__invoke();
        }
        if (!isset($this->result->body[$offset])) {
            throw new OutOfBoundsException("[$offset] for object[" . get_class($this->result) . "]");
        }

        return $this->result->body[$offset];
    }


    /**
     * Returns whether the requested index in body exists
     *
     * @param mixed $offset offset
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        if (is_null($this->result)) {
            $this->result = $this->__invoke();
        }

        return isset($this->result->body[$offset]);
    }

    /**
     * Get array iterator
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        if (is_null($this->result)) {
            $this->result = $this->__invoke();
        }
        $isArray = (is_array($this->result->body) || $this->result->body instanceof Traversable);
        $iterator = $isArray ? new ArrayIterator($this->result->body) : new ArrayIterator([]);

        return $iterator;
    }

    /**
     * {@inheritdoc}
     */
    public function hash()
    {
        return md5(get_class($this->ro) . $this->method . serialize($this->query) . serialize($this->links));
    }
}
