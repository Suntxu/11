<?php

namespace app\worker\controller;

use think\worker\Server;
use think\Db;
use Workerman\Lib\Timer;
use app\admin\library\Redis;

/**
 * 离线下载任务
 */
class Offline extends Server
{
	protected $processes = 1;
	private $redis = null;

    /**
     * 每个进程启动
     * @param $worker
     */
    public function onWorkerStart($worker)
    {
		$this->redis = new Redis(['select' => 7]);
		Timer::add(3600, function(){$this->clearexport();});
		Timer::add(20, function(){$this->ExportDomain();});
    }
	//定时删除导出列表
	public function clearexport(){

		$time = time() - (86400 * 7);
		$list =  Db::name('domain_export')->where("endtime <= $time")->field('path,id')->select();
		foreach($list as $v){
			@unlink(ROOT_PATH . 'public' . DS . 'uploads/offline/'.$v['path']);
			Db::name('domain_export')->where("id = ".$v['id'])->delete();
		}
		var_dump('end_clearexport');
	}
	/**
	 * 字符转换（utf-8 => GBK）
	 */
	public function utfToGbk($data)
	{
		return iconv('utf-8', 'GBK', $data);
	}
	
	/*
	域名导出
	*/
	public function ExportDomain(){
		global $reserve_db;
		$task = $this->redis->lLen('export_domain_operate_id');

		if($task > 0){

			$taskid = $this->redis->lRange('export_domain_operate_id',0,-1);
			foreach($taskid as $v){

				$action = $this->redis->get('export_domain_action_'.$v);
				$actions = explode('_',$action);
				if($actions[0] == 'reserve'){ //预定库
                    $db2 = Db::connect($reserve_db)->name('domain_pro_reserve');
                }

				$data = $this->redis->hgetall('export_domain_operate_id_'.$v);
                if($data){
                    $sql = json_decode($data['sql'],true);
                    //写入头部
                    $head = $this->redis->hgetall('export_domain_head_'.$v);

                    $fileData = $this->utfToGbk(implode(',',$head)) . "\n";

                    $user_path = ROOT_PATH . 'public' . DS . 'uploads/offline/';

                    $sj = time();

                    $f= date('YmdHis',$sj).'_'.$data['userid'].'_'.str_replace('/','-',$data['name']).'.csv';
                    $filename = $f;

                    file_put_contents($user_path.$filename, $fileData);

                    $count = 0;
                    foreach($sql as $k => $sv){

                        $inputdata = '';
                        if(isset($db2)){
                            $d = $db2->query($sv);
                        }else{
                            $d = Db::query($sv);
                        }

                        $count += count($d);

                        foreach($d as $dv){

                            foreach($dv as &$vv){
                                if(strlen($vv) == 10 && is_numeric($vv)){
                                    $vv = date('Y-m-d H:i:s',$vv);
                                }
                            }

                            $inputdata .= implode(',', $dv )."\n";
                        }

                        file_put_contents($user_path.$filename, $this->utfToGbk($inputdata),FILE_APPEND);
                    }

                    Db::name('domain_export')->where(['id' => $v])->update(['status' => 1,'endtime' => $sj,'num' => $count,'path' => $f]);

                }

				$this->redis->del('export_domain_operate_id_'.$v);

				$this->redis->lrem('export_domain_operate_id',0,$v);

				$this->redis->del('export_domain_head_'.$v);
				$this->redis->del('export_domain_action_'.$v);
				unset($sql,$fileData,$data);
                file_put_contents('logs.txt','end_ExportDomain_'.$v."\n",FILE_APPEND);
			}
		}
	}
}