<?php
namespace Library\Db;

use \Memcached;

/**
 * Class MemcachedDb
 * Wrapper for memcached
 * @package Library\Db
 */
class MemcachedDb
{
    /**
     * Default life time for cached value
     *
     * @var int
     */
    const DEFAULT_TTL = 300;
    /**
     * Memcached prefix
     * @var string
     */
    private $_prefix;

    /**
     * @var \Memcached
     */
    private $_memcached;

    /**
     * @param $connections
     * @param $prefix
     */
    function __construct($connections, $prefix)
    {
        $this->_prefix = $prefix;

        $this->_memcached = new Memcached();

        if (Memcached::HAVE_IGBINARY) {
            $this->instance()->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_IGBINARY);
        }

        $this->instance()->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
        $this->instance()->setOption(Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
        $this->instance()->setOption(Memcached::OPT_NO_BLOCK, true);
        $this->instance()->setOption(Memcached::OPT_TCP_NODELAY, true);
        $this->instance()->setOption(Memcached::OPT_COMPRESSION, true);
        $this->instance()->setOption(Memcached::OPT_CONNECT_TIMEOUT, 2);

        if (!empty($connections)) {
            $this->_memcached->addServers($connections);
        }
    }

    /**
     * @param $key
     * @param $value
     * @param int $ttl
     * @return bool
     */
    public function set($key, $value, $ttl = self::DEFAULT_TTL)
    {
        return $this->instance()->set(
            $this->_prefix . $key,
            $value,
            $ttl
        );
    }

    /**
     * @param $key
     * @param null $cache_cb
     * @param null $cas_token
     * @return mixed
     */
    public function get($key, $cache_cb = null, &$cas_token = null)
    {
        return $this->instance()->get(
            $this->_prefix . $key,
            $cache_cb,
            $cas_token
        );
    }

    /**
     * @param $key
     * @param $time
     * @return bool
     */
    public function delete($key, $time = 0)
    {
        return $this->instance()->delete(
            $this->_prefix . $key,
            $time
        );
    }

    /**
     * @param int $delay
     * @return bool
     */
    public function flush($delay = 0)
    {
        $this->_memcached->flush($delay);
    }

    /**
     * @return array
     */
    public function fetch()
    {
        return $this->instance()->fetchAll();
    }

    /**
     * @return Memcached
     */
    public function instance()
    {
        return $this->_memcached;
    }

    /**
     * @return array
     */
    public function serverList()
    {
        return $this->instance()->getServerList();
    }
}