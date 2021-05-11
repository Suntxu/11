<?php
namespace app\admin\library;

/**
 * Redis操作类
 */
class Redis
{
    protected static $handler = null;

    // protected $options = [
    //        'host' => '115.29.141.160',
    //        'port' => 6379,
    //        'password' => 'Hm19@woJnE',
    //        'select' => 0,
    //        'timeout' => 0,
    //        'expire' => 0,
    //        'persistent' => false,
    //        'prefix' => '',
    //     ];
    protected $options = [
        'host' => '39.99.203.192',
        'port' => 6379,
        'password' => '9xXq15hjZe4',
        'select' => 0,
        'timeout' => 0,
        'expire' => 0,
        'persistent' => false,
        'prefix' => '',
    ];
    public function __construct($options = [])
    {
        if (!extension_loaded('redis')) {   //判断是否有扩展(如果你的apache没reids扩展就会抛出这个异常)
            throw new \BadFunctionCallException('not support: redis');     
        }
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }
        $func = $this->options['persistent'] ? 'pconnect' : 'connect';     //判断是否长连接
        self::$handler = new \Redis;
        self::$handler->$func($this->options['host'], $this->options['port'], $this->options['timeout']);
 
        if ('' != $this->options['password']) {
            self::$handler->auth($this->options['password']);
        }
 
        if (0 != $this->options['select']) {
            self::$handler->select($this->options['select']);
        }
    }
	public function lock($key,$expire = 5){
        $k = 'LOCK_'.$key;
        $is_lock = self::$handler->setnx($k, time() + $expire);
		// if($k == 'LOCK_buyer_money1'){
			// var_dump($is_lock);
		// }
        if(!$is_lock){//当前操作已锁定时，判断是否死锁，如果锁已过期，则获取新锁
            $time = time();
            $lock_time = self::$handler->get($k);
            // 判断锁是否过期
            if($time > $lock_time){
                $this->unlock($key);
                $is_lock = self::$handler->setnx($k, $time + $expire);
            }
        }
        return $is_lock? true : false;
    }
    
    /**
     * 删除redis锁
     */
    public function unlock($key){
        return self::$handler->del('LOCK_'.$key);
    }
    
    /**
     * 删除键
     */
    public function del($key){
        self::$handler->del($key);
    }


    /**
     * 写入缓存
     * @param string $key 键名
     * @param string $value 键值
     * @param int $exprie 过期时间 0:永不过期
     * @return bool
     */
    public function set($key, $value, $exprie = 0)
    {
        if ($exprie == 0) {
            $set = self::$handler->set($key, $value);
        } else {
            $set = self::$handler->setex($key, $exprie, $value);
        }
        return $set;
    }
    
    /**
     * 写入缓存 哈希表
     * @return bool
     */
    public function hMset($key, $value, $exprie = 0)
    {
        if ($exprie == 0) {
            $set = self::$handler->hMset($key, $value);
        } else {
            $set = self::$handler->hMset($key, $exprie, $value);
        }
        return $set;
    }
 
    /**
     * 读取缓存
     * @param string $key 键值
     * @return mixed
     */
    public function get($key)
    {
        $fun = is_array($key) ? 'Mget' : 'get';
        return self::$handler->{$fun}($key);
    }
    
    /**
     * 返回所有hash表的字段值，为一个关联数组
     * @param string $key
     * @return array|bool
     */
    public function hGetAll($key)
    {
        return self::$handler->hGetAll($key);
    }


    /**
     * 为hash表多个字段设定值。
     * @param string $key
     * @param array|string $value string以','号分隔字段
     * @return array|bool
     */
    public function hMget($key,$field)
    {
        if(!is_array($field))
            $field=explode(',', $field);
        return self::$handler->hMget($key,$field);
    }

    /**
     * 获取值长度
     * @param string $key
     * @return int
     */
    public function lLen($key)
    {
        return self::$handler->lLen($key);
    }
 
    /**
     * 将一个或多个值插入到列表头部
     * @param $key
     * @param $value
     * @return int
     */
    public function LPush($key,$value)
    {
        if(is_array($value)){

            return self::$handler->lPush($key, ...$value);

        }
        return self::$handler->lPush($key, $value);
    }

    /**
     * 将一个或多个值插入到列表尾部
     * @param $key
     * @param $value
     * @return int
     */
    public function RPush($key, $value)
    {
        if(is_array($value)){
            return self::$handler->rPush($key, ...$value);
        }
        return self::$handler->rPush($key, $value);
    }
 
    /**
     * 移出并获取列表的第一个元素
     * @param string $key
     * @return string
     */
    public function lPop($key)
    {
        return self::$handler->lPop($key);
    }
    
    /**
     * 返回队列指定区间的元素
     * @param  $key
     * @param  $start
     * @param  $end
     */
    public function lRange($key,$start,$end)
    {
        return self::$handler->lrange($key,$start,$end);
    }


    /**
     * 删除值为vaule的count个元素
     * PHP-REDIS扩展的数据顺序与命令的顺序不太一样，不知道是不是bug
     * count>0 从尾部开始
     *  >0　从头部开始
     *  =0　删除全部
     * @param  $key
     * @param  $count
     * @param  $value
     */
    public function lRem($key,$count,$value)
    {
        return self::$handler->lRem($key,$value,$count);
    }
    
    /**
     * 清空当前数据库
     * @return bool
     */
    public function flushDB()
    {
        return self::$handler->flushDB();
    }
    /**
     * 给key增加过期时间
     */
    public function expire($key,$second = 60){
        return self::$handler->expire($key,$second);
    }
    /**
     * 获取key值的剩余时间
     */
    public function ttl($key,$msec = false){

        if($msec){ //毫秒
            return self::$handler->pttl($key);
        }else{ //秒
            return self::$handler->ttl($key);
        }
    }
    /**
     * 添加无序集合
     */
    public function sadd($key,$member){
        return self::$handler->sadd($key,$member);
    }
    /**
     * 获取无序集合成员个数
     */
    public function scard($key){
        return self::$handler->scard($key);
    }
    /**
     * 判断member是否属于该集合
     */
    public function sismember($key,$member){
        return self::$handler->sismember($key,$member);
    }

    /**
     * 查询集合中所有成员
     */
    public function smembers($key){
        
        return self::$handler->smembers($key);
    }

    /**
     * 删除无序集合
     */
    public function srem($key,$member){
        return self::$handler->srem($key,$member);
    }

}
