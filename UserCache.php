<?php

use Interfaces\CacheableInterface;

class UserCache implements CacheableInterface
{
    protected $user;

    /**
     * UserCache constructor.
     * @param Memcached $cache
     * @param CacheableInterface $user
     */
    public function __construct(Memcached $cache, CacheableInterface $user)
    {
        $this->cache = $cache;
        $this->user = $user;
    }

    /**
     * @param $data
     * @return string
     */
    public function getCacheKey($data){
        $key = get_class($this->user);
        if(is_scalar($data)){
            $key .= $data;
        }
        else{
            $key .= md5(json_encode($data));
        }
        return $key;
    }

    /**
     * @param $key
     * @param $data
     * @return bool
     */
    public function cacheSet($key, $data){
        return $this->cache->set($key, $data);
    }

    /**
     * @param $key
     * @return mixed
     */
    public function cacheGet($key){
        return $this->cache->get($key);
    }

    /**
     * @param $key
     * @return bool
     */
    public function cacheRemove($key){
        return $this->cache->delete($key);
    }

    /**
     * @param $conds
     * @return mixed
     */
    public function find($conds)
    {
        $key = $this->getCacheKey($conds);
        $result = $this->cacheGet($key);
        if(!$result){
            $result = $this->user->find($conds);
            $this->cacheSet($key, $result);
        }
        return $result;
    }

    /**
     * @param $id
     * @return mixed
     */
    public function findOne($id)
    {
        $key = $this->getCacheKey($id);
        $result = $this->cacheGet($key);
        if(!$result){
            echo $result;
            $result = $this->user->findOne($id);
            $this->cacheSet($key, $result);
        }
        return $result;
    }

    /**
     * @return mixed
     */
    public function save()
    {
        $key = $this->getPrimaryKey();
        $res = $this->user->save();
        if($key){ /*Record already exists*/
            $this->cacheRemove($this->getCacheKey($key));
        }
        return $res;
    }

    /**
     * @return mixed
     */
    public function getPrimaryKey()
    {
        return $this->user->getPrimaryKey();
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if(method_exists($this->user, $name)){
            return $this->user->{$name}(...$arguments);
        }
    }

}