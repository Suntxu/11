<?php
namespace app\admin\library;

/**
 * 均衡分配任务，各个任务进程要与实际开的进程一致
 */
class Workertask
{
	/*
	需要在0号进程刷新的注册商ID
	*/
	protected $need_refresh = [66,84];
	
	//新过户任务
	protected $new_infomodification_count = [66 => [2,100000], 67 => [1,100000] , 68 => [1,100000], 71 => [1,100000], 72 => [1,100000],73 => [1,100000],74 => [1,100000],75 => [1,100000],77 => [1,100000],78 => [1,100000],79 => [1,100000],84 => [2,100000],83 => [1,100000],85 => [1,100000],88 => [1,100000],91 => [1,100000],97 => [1,100000],96 => [1,100000],103 => [1,100000],107 => [1,100000],106 => [1,100000],108 => [1,100000],109 => [1,100000],110 => [1,100000],86 => [1,100000],111 => [1,100000],112 => [1,100000],113 => [1,100000],114 => [1,100000]];
    
    /**
     * DNS修改（进程数量、每个任务分配数量）
     * 这里0号进程固定为刷新批量修改的任务，所以存入任务时，从1号进程开始
     */
    protected $dnsmodification_count = [66 => [2,100000], 67 => [1,100000] , 68 => [1,100000], 71 => [1,100000], 72 => [1,100000],73 => [1,100000],74 => [1,100000],75 => [1,100000],77 => [1,100000],79 => [1,100000],78 => [1,100000],84 => [2,100000],83 => [1,100000],85 => [1,100000],86 => [1,100000],88 => [1,100000],91 => [1,100000],97 => [1,100000],96 => [1,100000],103 => [1,100000],107 => [1,100000],106 => [1,100000],108 => [1,100000],109 => [1,100000],110 => [1,100000],111 => [1,100000],112 => [1,100000],113 => [1,100000],114 => [1,100000]];
    
    /**
     * 一口价、Push域名时需要删除解析记录、改回原DNS，这里暂时只开一个进程，后面可能会加
     */
    protected $unset_domain_count = [1,1000];
    
    /**
     * 注册任务进程 至少要2个进程，因为第一个进程默认处理退款（注册失败）
     * 阿里云、西数、RRP，分开
     */
    protected $reg_domain_count = [66 => [2,100000], 67 => [1,100000] , 68 => [1,100000],75 => [1,100000],79 => [1,100000],74 => [1,100000],83 => [1,100000]];
    
    /**
     * redis操作类
     */
    protected $redis;
    public function __construct($redis)
    {
        $this->redis = $redis;
    }
	
	/*
	批量续费任务分配（新，老的准备删除）
	*/
	protected $renew_count_new = [66 => [2,100000],72 => [1,100000],67 => [1,100000],74 => [1,100000],77 => [1,100000],71 => [1,100000],83 => [1,100000],107 => [1,100000],88 => [1,100000],78 => [1,100000],86 => [1,100000],91 => [1,100000],109 => [1,100000]];
	public function renewdomain_new($id,$info,$reg_id){
		$into = false;//任务插入结果
        $task_count = [];
		$start = 0;
		if(in_array($reg_id,$this->need_refresh)){//有需要刷新任务的注册商时，从1号进程开始存入任务
			$start = 1;
		}
        for($i = $start;$i < $this->renew_count_new[$reg_id][0]; $i++){
			$key = $reg_id.'_'.$i;
            $sub_task = $this->redis->lLen('nrenew_task_id_'.$key);
            if($sub_task < $this->renew_count_new[$reg_id][1]){
				$this->redis->hMset('woker_nrenew_'.$reg_id.'_'.$id,$info);//任务详情
                $this->redis->RPush('nrenew_task_id_'.$key,$id);//每个进程里存入10个任务
                $into = true;
                break;
            }
            $task_count[$i] = $sub_task;
        }
        if(!$into){
            $min = min($task_count);
            $key = array_search($min,$task_count);
			$this->redis->hMset('woker_nrenew_'.$reg_id.'_'.$id,$info);//任务详情
            $this->redis->RPush('nrenew_task_id_'.$reg_id.'_'.$key,$id);
        }
	}
    
    /**
     *  一口价、Push域名时需要删除解析记录、改回原DNS
     */
    public function unsetdomain($id,$info){
        $into = false;//任务插入结果
        $task_count = [];
        for($i = 0;$i < $this->unset_domain_count[0]; $i++){
            $sub_task = $this->redis->lLen('unset_task_id_'.$i);
            if($sub_task < $this->unset_domain_count[1]){
				$this->redis->hMset('unset_info_'.$id,$info);//任务详情
                $this->redis->RPush('unset_task_id_'.$i,$id);//每个进程里存入10个任务
                $into = true;
                break;
            }
            $task_count[$i] = $sub_task;
        }
        if(!$into){
            $min = min($task_count);
            $key = array_search($min,$task_count);
			$this->redis->hMset('unset_info_'.$id,$info);//任务详情
            $this->redis->RPush('unset_task_id_'.$key,$id);
        }
    }
  
    /**
     * 批量注册时的任务分配 进程数量暂定为5个，5个进程同时处理任务，每个任务最低任务为 3 个
     * 这些数量只是暂定
     * 这里改成按注册商分配任务，方便开发注册接口
     */
    public function resworker($id,$info){
        $into = false;//任务插入结果
        $task_count = [];
        for($i = 0;$i < $this->reg_domain_count[$info['reg_id']][0]; $i++){
            $sub_task = $this->redis->lLen($info['reg_id'].'_reg_task_id_'.$i);
            if($sub_task < $this->reg_domain_count[$info['reg_id']][1]){
				$this->redis->hMset($info['reg_id'].'_woker_reg_info_'.$id,$info);//任务详情
                $this->redis->RPush($info['reg_id'].'_reg_task_id_'.$i,$id);//每个进程里存入10个任务
                $into = true;
                break;
            }
            $task_count[$i] = $sub_task;
        }
        if(!$into){
            $min = min($task_count);
            $key = array_search($min,$task_count);
			$this->redis->hMset($info['reg_id'].'_woker_reg_info_'.$id,$info);//任务详情
            $this->redis->RPush($info['reg_id'].'_reg_task_id_'.$key,$id);
        }
    }
	
	/**
     * 批量模板任务（新）
     */
    public function new_infomodification_work($id,$info,$reg_id){
        $into = false;//任务插入结果
        $task_count = [];
		$start = 0;
		if(in_array($reg_id,$this->need_refresh)){//有需要刷新任务的注册商时，从1号进程开始存入任务
			$start = 1;
		}
        for($i = $start;$i < $this->new_infomodification_count[$reg_id][0]; $i++){
			$key = $reg_id.'_'.$i;
            $sub_task = $this->redis->lLen('infomodification_task_id_'.$key);//注册商_任务id
            if($sub_task < $this->new_infomodification_count[$reg_id][1]){
				$this->redis->hMset('woker_infomodification_'.$reg_id.'_'.$id,$info);//任务详情
                $this->redis->RPush('infomodification_task_id_'.$key,$id);//每个进程里存入10个任务
                $into = true;
                break;
            }
            $task_count[$i] = $sub_task;
        }
        if(!$into){
            $min = min($task_count);
            $key = array_search($min,$task_count);
			$this->redis->hMset('woker_infomodification_'.$reg_id.'_'.$id,$info);//任务详情
            $this->redis->RPush('infomodification_task_id_'.$reg_id.'_'.$key,$id);
        }
    }
    
    /**
     * 批量修改DNS任务 开3个进程 每个进程10个任务
     */
    public function dnsmodification_work($id,$info,$reg_id){
        $into = false;//任务插入结果
        $task_count = [];
		$start = 0;
		if(in_array($reg_id,$this->need_refresh)){//有需要刷新任务的注册商时，从1号进程开始存入任务
			$start = 1;
		}
        for($i = $start;$i < $this->dnsmodification_count[$reg_id][0]; $i++){
			$key = $reg_id.'_'.$i;
            $sub_task = $this->redis->lLen('dnsmodification_task_id_'.$key);
            if($sub_task < $this->dnsmodification_count[$reg_id][1]){
				$this->redis->hMset('woker_dnsmodification_'.$reg_id.'_'.$id,$info);//任务详情
                $this->redis->RPush('dnsmodification_task_id_'.$key,$id);//每个进程里存入10个任务
                $into = true;
                break;
            }
            $task_count[$i] = $sub_task;
        }
        if(!$into){
            $min = min($task_count);
            $key = array_search($min,$task_count);
			$this->redis->hMset('woker_dnsmodification_'.$reg_id.'_'.$id,$info);//任务详情
            $this->redis->RPush('dnsmodification_task_id_'.$reg_id.'_'.$key,$id);
        }
    }
    
	/**
     * 新批量解析任务
     */
	protected $dnsbatchadd_count_new = [66 => [1,1], 67 => [1,100000], 78 => [1,100000],84 => [2,1],74 => [1,100000],107 => [1,100000]];
	public function dnsbatchadd_work_batch($id,$info,$reg_id){
        $into = false;//任务插入结果
        $task_count = [];
        for($i = 0;$i < $this->dnsbatchadd_count_new[$reg_id][0]; $i++){
			$key = $reg_id.'_'.$i;
            $sub_task = $this->redis->lLen('batchaddjxdns_task_id_'.$key);
            if($sub_task < $this->dnsbatchadd_count_new[$reg_id][1]){
				$this->redis->hMset('batchwoker_addjxdns_'.$reg_id.'_'.$id,$info);
                $this->redis->RPush('batchaddjxdns_task_id_'.$key,$id);
                $into = true;
                break;
            }
            $task_count[$i] = $sub_task;
        }
        if(!$into){
            $min = min($task_count);
            $key = array_search($min,$task_count);
			$this->redis->hMset('batchwoker_addjxdns_'.$reg_id.'_'.$id,$info);
            $this->redis->RPush('batchaddjxdns_task_id_'.$reg_id.'_'.$key,$id);
        }
    }
	
	/**
     * 新批量解析任务（大批量切割）
     */
	public function dnsbatchadd_work_chunk($id,$info,$reg_id){
        $into = false;//任务插入结果
        $task_count = [];
        for($i = 0;$i < $this->dnsbatchadd_count_new[$reg_id][0]; $i++){
			$key = $reg_id.'_'.$i;
            $sub_task = $this->redis->lLen('chunkaddjxdns_task_id_'.$key);
            if($sub_task < $this->dnsbatchadd_count_new[$reg_id][1]){
				$this->redis->hMset('chunkwoker_addjxdns_'.$reg_id.'_'.$id,$info);
                $this->redis->RPush('chunkaddjxdns_task_id_'.$key,$id);
                $into = true;
                break;
            }
            $task_count[$i] = $sub_task;
        }
        if(!$into){
            $min = min($task_count);
            $key = array_search($min,$task_count);
			$this->redis->hMset('chunkwoker_addjxdns_'.$reg_id.'_'.$id,$info);
            $this->redis->RPush('chunkaddjxdns_task_id_'.$reg_id.'_'.$key,$id);
        }
    }
	
	/**
     * 批量删除解析
     */
	protected $dnsbatchdel_count = [66 => [1,1], 67 => [1,100000], 78 => [1,100000],74 => [1,100000],107 => [1,100000]];
    public function dnsbatchdel_work($id,$info,$reg_id){
        $into = false;//任务插入结果
        $task_count = [];
        for($i = 0;$i < $this->dnsbatchdel_count[$reg_id][0]; $i++){
			$key = $reg_id.'_'.$i;
            $sub_task = $this->redis->lLen('deljxdns_task_id_'.$key);
            if($sub_task < $this->dnsbatchdel_count[$reg_id][1]){
				$this->redis->hMset('woker_deljxdns_'.$reg_id.'_'.$id,$info);
                $this->redis->RPush('deljxdns_task_id_'.$key,$id);
                $into = true;
                break;
            }
            $task_count[$i] = $sub_task;
        }
        if(!$into){
            $min = min($task_count);
            $key = array_search($min,$task_count);
			$this->redis->hMset('woker_deljxdns_'.$reg_id.'_'.$id,$info);
            $this->redis->RPush('deljxdns_task_id_'.$reg_id.'_'.$key,$id);
        }
    }
	
	/**
     * 批量删除解析记录（用于用户主动删除记录）
     */
    public function dnsbatchdel_work_new($id,$info,$reg_id){
        $into = false;//任务插入结果
        $task_count = [];
        for($i = 0;$i < $this->dnsbatchdel_count[$reg_id][0]; $i++){
            $key = $reg_id.'_'.$i;
            $sub_task = $this->redis->lLen('batchdeljxdns_task_id_'.$key);
            if($sub_task < $this->dnsbatchdel_count[$reg_id][1]){
                $this->redis->hMset('batchwoker_deljxdns_'.$reg_id.'_'.$id,$info);
                $this->redis->RPush('batchdeljxdns_task_id_'.$key,$id);
                $into = true;
                break;
            }
            $task_count[$i] = $sub_task;
        }
        if(!$into){
            $min = min($task_count);
            $key = array_search($min,$task_count);
            $this->redis->hMset('batchwoker_deljxdns_'.$reg_id.'_'.$id,$info);
            $this->redis->RPush('batchdeljxdns_task_id_'.$reg_id.'_'.$key,$id);
        }
    }
	
	/**
     * 批量预订
     */
    protected $reservebatch_count = [1 => [1,10000]];//预订通道
    public function reserve_work($id,$info,$reg_id){
        $into = false;//任务插入结果
        $task_count = [];
        for($i = 0;$i < $this->reservebatch_count[$reg_id][0]; $i++){
            $key = $reg_id.'_'.$i;
            $sub_task = $this->redis->lLen('reserve_task_id_'.$key);
            if($sub_task < $this->reservebatch_count[$reg_id][1]){
                $this->redis->hMset('woker_reserve_'.$reg_id.'_'.$id,$info);
                $this->redis->RPush('reserve_task_id_'.$key,$id);
                $into = true;
                break;
            }
            $task_count[$i] = $sub_task;
        }
        if(!$into){
            $min = min($task_count);
            $key = array_search($min,$task_count);
            $this->redis->hMset('woker_reserve_'.$reg_id.'_'.$id,$info);
            $this->redis->RPush('reserve_task_id_'.$reg_id.'_'.$key,$id);
        }
    }
}

