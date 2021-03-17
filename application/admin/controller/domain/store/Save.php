<?php

namespace app\admin\controller\domain\store;

use app\common\controller\Backend;
use think\Exception;
use think\Db;
use PHPExcel_IOFactory;
use app\admin\common\Fun;
use app\admin\library\Redis;

use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

set_time_limit(0);

require_once APP_PATH.'/../vendor/box/spout/src/Spout/Autoloader/autoload.php';

/**
 * 系统配置
 *
 * @icon fa fa-cogs
 * @remark 可以在此增改系统的变量和分组,也可以自定义分组和变量,如果需要删除请从数据库中删除
 */
class Save extends Backend
{

    /**
     * @var \app\common\model\Config
     */
    // protected $noNeedRight = ['check'];
    
    private $aliyun_DNS = [68,71,72,73,75,77,79,83,85,88,91,108,112];


    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        if ($this->request->isPost()) {

            $params = $this->request->post();

            if(empty($params['domain'])){
                $this->error('请上传文件');
            }
            if(empty($params['zcs'])){
                $this->error('请选注册商');
            }
            if(empty($params['appid'])){
                $this->error('请选对应的API');
            }
            if(empty($params['uid'])){
                $this->error('请输入用户名');
            }
            $userid = Db::name('domain_user')->where('uid',$params['uid'])->value('id');
            if(empty($userid)){
                $this->error('用户名不存在');
            }
            $pa = 'uploads/'.substr(ltrim($params['domain'],'/'),0,strpos(ltrim($params['domain'],'/'),'?'));
            if(!file_exists($pa)){
                $this->error('文件打开失败,请重新上传！');
            }
            if(filesize($pa) > 2097152){
                $this->error('上传的文件不得超过2M');
            }

            $ext = strtolower(strrchr($pa,'.'));
            
            $time = time();

            if($ext == '.xlsx'){
                $reader = ReaderFactory::create(Type::XLSX);
                //进行读取文件 并上传
                // $reader = ReaderEntityFactory::createXLSXReader();
                list($insertsql,$domainArr,$msg) = $this->getSpoutSql($reader,$pa,$time,$userid,$params);

            }elseif($ext == '.csv'){
                $reader = ReaderFactory::create(Type::CSV);
                // $reader = ReaderEntityFactory::createCSVReader();
                list($insertsql,$domainArr,$msg) = $this->getSpoutSql($reader,$pa,$time,$userid,$params);

            }elseif($ext == '.xls'){ //兼容老版本
                
                $objPHPExcel = PHPExcel_IOFactory::load($pa);
                
                list($insertsql,$domainArr,$msg) = $this->getExcelSql($objPHPExcel,$time,$userid,$params);

            }else{

                $this->error('请上传正确类型的文件');

            }

            if($insertsql){

                $sql = 'INSERT IGNORE INTO '.PREFIX.'domain_pro_n(userid,inserttime,zt,tit,len,zcsj,dqsj,hz,zcs,api_id,special,parse_status) values ';
                
                $yarr = $this->checkDomainExists($domainArr);
                $domainArr = array_diff($domainArr,$yarr);

                //存入redis 阿里云解析
                if(in_array($params['zcs'],$this->aliyun_DNS)){
                    $redis = new Redis(['select' => 1]);
                    //存入redis
                    $redis->lpush('aliyun_add_domain', $domainArr);
                }

                //存入检测类型接口
                $dredis = new Redis();

                $dredis->lpush('operate_save_domain_json',json_encode($domainArr));
                
                //聚名网重新添加模板
                if($params['zcs'] == 107){
                    file_put_contents(APP_PATH.'/../public/outrecord.txt',"入库时间:".date('Y-m-d H:i:s')."\n",FILE_APPEND );
                    foreach($domainArr as $v){
                        $res = $dredis->lpush('domain_reset_create_template',$params['appid'].'_'.$v);
                        if($res){
                            $filll = "内容:".$params['appid'].'_'.$v."\n";
                        }else{
                            $filll = "内容: 未插入 ".$params['appid'].'_'.$v."\n";
                        }
                        file_put_contents(APP_PATH.'/../public/logs/outrecord.txt',$filll,FILE_APPEND);
                    }
                }
                $apis = $this->getApis();
                Db::execute($sql.rtrim($insertsql,','));
                 //写入域名操作记录
                Db::name('domain_operate_record')->insert([
                    'tit' => implode(',',$domainArr),
                    'operator_id' => $this->auth->id,
                    'create_time' => $time,
                    'type' => 4,
                    'value' => $apis[$params['appid']].'_手动入库_用户:'.$params['uid'],
                ]);

                $msg = '成功入库'.count($domainArr).'个域名,域名已存在数据库中：'.implode(',',$yarr).$msg;
            }
            $this->success($msg,'reload');
        }
        $this->view->assign('zcs',$this->getCates('api',false));
        return $this->view->fetch();
    }

    //检测域名是否存在库里面
    public function checkDomainExists($domainArr){
        //查看域名是否在数据库里面
        $flter = [];
        $domains = array_chunk($domainArr,500);
        foreach($domains as $k => $v){
            $chunkfi = Db::name('domain_pro_n')->whereIn('tit',$v)->column('tit');
            if($chunkfi){
                $flter = array_merge($flter,$chunkfi);
//                $flter .= implode(',',$chunkfi).',';
            }
        }
        //提示弹出
        return $flter;
        // $flter = explode(',',rtrim($flter,','));
//        if($flter){
//            $this->error('以下域名请先出库后再来进行此操作:'.$flter);
//        }
    }


    //兼容xls格式
    public function getExcelSql($objPHPExcel,$time,$userid,$params ){

        $sheetCount =  $objPHPExcel->getSheetCount();

        $insertsql = '';
        $msg = '';
        $domainArr = [];

        //以下注册商使用的阿里云dns
        if(in_array($params['zcs'],$this->aliyun_DNS)){
            $st = 0;
        }else{
            $st = 1;
        }

        for($i=0;$i<$sheetCount;$i++){
            //获取每张表的长度和列数
            $_currentSheet = $objPHPExcel -> getSheet($i);
            $_allRow = $_currentSheet->getHighestRow(); //获取Excel中信息的行数
            if($_allRow > 12000){
                $this->error('上传的文件不得超过12000行数据');
            }
            $_allColumn = $_currentSheet->getHighestColumn();//获取Excel的列数
            //遍历每一行数据
            for($j=2;$j<=$_allRow;$j++){
                $tit = strip_tags($_currentSheet->getCellByColumnAndRow(0, $j)->getValue()); //域名
                $tit = strtolower(preg_replace('/\s+/','',$tit));               
                // $type = strip_tags($_currentSheet->getCellByColumnAndRow(1, $j)->getValue());//类型
                $create_time = strip_tags($_currentSheet->getCellByColumnAndRow(4, $j)->getValue()) ; //创建时间
              
                $gqsj = strip_tags($_currentSheet->getCellByColumnAndRow(5, $j)->getValue()) ; //过期时间
                if(empty($tit) && empty($create_time) && empty($gqsj)){
                    continue;
                }
                if(!strrpos($tit,'.')){
                    $msg.= '工作簿'.$i.'第'.$j.'行域名格式不正确,已为您过滤！';
                    continue;
                }

                if(is_numeric($create_time)){
                    if($params['zcs'] == 71){
                        $n = intval(($create_time-25569)*3600*24) - 31622400;
                    }else{
                        $n = intval(($create_time-25569)*3600*24);
                    } 
                    $create_time = gmdate('Y-m-d H:i:s',$n);
                }else{
                    $create_time = str_replace('/','-',$create_time);
                    if(!preg_match('/^\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{1,2}(:\d{1,2})?$/s',$create_time) || !preg_match('/^\d{4}-\d{2}-\d{2}$/s',$create_time)){
                        $n = strtotime($create_time);
                        if(empty($n)){
                            $msg.= '工作簿'.$i.'第'.$j.'行创建时间格式不正确,已为您过滤！';
                            continue;
                        }else{
                            $create_time = date('Y-m-d H:i:s',$n);
                        }
                    }
                }
                if(is_numeric($gqsj)){
                    $e = intval(($gqsj-25569)*3600*24);
                    $gqsj = gmdate('Y-m-d H:i:s',$e);
                }else{
                    $gqsj = str_replace('/','-',$gqsj);
                    if(!preg_match('/^\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{1,2}(:\d{1,2})?$/s',$gqsj) || !preg_match('/^\d{4}-\d{2}-\d{2}$/s',$gqsj)){
                        $e = strtotime($gqsj);
                        if(empty($e)){
                            $msg.= '工作簿'.$i.'第'.$j.'行到期时间格式不正确,已为您过滤！';
                            continue;
                        }else{
                            $gqsj = date('Y-m-d H:i:s',$e);
                        }
                    }
                }
                if(strtotime($gqsj) < $time){
                    $msg.= '工作簿'.$i.'第'.$j.'行域名已过期,已为您过滤！';
                    continue;
                }
                if(empty($tit)  || empty($create_time) || empty($gqsj)){
                    $msg.='工作簿'.$i.'第'.$j.'行数据不完整,已为您过滤！';
                    continue;
                }
                $domainArr[] = $tit;
                // $tyid=Fun::ini()->returnymfl($tit);
                $ymhz=preg_split("/\./",$tit);
                $ymhzv=str_replace($ymhz[0],"",$tit);
                // 域名转入选择
                // if($params['zcs'] == 71){
                //     $userid = 2397;
                // }else{
                //     $userid = 15;
                // }
                $insertsql .= " ({$userid},'$time',9,'$tit',".strlen($ymhz[0]).",'$create_time','$gqsj','$ymhzv','{$params['zcs']}','{$params['appid']}','{$params['special']}',{$st}),";
            }
        }
        return [$insertsql,$domainArr,$msg];

    }



    //新插件
    public function getSpoutSql($reader,$pa,$time,$userid,$params ){

        $insertsql = '';
        $msg = '';
        $domainArr = [];
        
        $reader->open($pa);
        $iterator = $reader->getSheetIterator();
        // $iterator->rewind(); //回到第一个元素
        // $sheet1 = $iterator->current();

        if(in_array($params['zcs'],$this->aliyun_DNS)){
            $st = 0;
        }else{
            $st = 1;
        }

        foreach ($reader->getSheetIterator() as $i => $sheet) { //工作表

            foreach($sheet->getRowIterator() as $k => $item){ //每一行

                if($k === 1){
                    continue;
                }
                //域名
                $tit = isset($item[0]) ? strip_tags($item[0]) : ''; //域名

                //注册时间
                $create_time = isset($item[4]) ? $item[4] : '';

                //过期时间  
                $expre_time = isset($item[5]) ? $item[5] : '';

                if(empty($tit)  || empty($create_time) || empty($expre_time)){
                    $msg.='工作簿'.$i.'第'.$k.'行数据不完整,已为您过滤！';
                    continue;
                }

                if(!strpos($tit,'.')){
                    $msg.='工作簿'.$i.'第'.$k.'行域名格式不正确,已为您过滤！';
                    continue;
                }

                $tit = strtolower(preg_replace('/\s+/','',$tit));

                //注册时间

                if(is_object($create_time)){

                    $zcsj = $create_time->format('Y-m-d H:i:s');

                }elseif(is_numeric($create_time)){

                    if($params['zcs'] == 71){
                        $n = intval(($create_time-25569)*3600*24) - 31622400;
                    }else{
                        $n = intval(($create_time-25569)*3600*24);
                    } 

                    $zcsj = gmdate('Y-m-d H:i:s',$n);
                }else{

                    $create_time = str_replace('/','-',$create_time);

                    if(!preg_match('/^\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{1,2}(:\d{1,2})?$/s',$create_time) || !preg_match('/^\d{4}-\d{2}-\d{2}$/s',$create_time)){
                        $n = strtotime($create_time);
                        if(empty($n)){
                            $msg.= '工作簿'.$i.'第'.$k.'行创建时间格式不正确,已为您过滤！';
                            continue;
                        }else{
                            $zcsj = date('Y-m-d H:i:s',$n);
                        }
                    }else{
                        $msg.= '工作簿'.$i.'第'.$k.'行创建时间格式不正确,已为您过滤！';
                        continue;
                    }
                }

                //过期时间
                if(is_object($expre_time)){

                    $gqsj = $expre_time->format('Y-m-d H:i:s');

                }elseif(is_numeric($expre_time)){

                    $e = intval(($expre_time-25569)*3600*24);

                    $gqsj = gmdate('Y-m-d H:i:s',$e);

                }else{

                    $expre_time = str_replace('/','-',$expre_time);
                    if(!preg_match('/^\d{4}-\d{1,2}-\d{1,2} \d{1,2}:\d{1,2}(:\d{1,2})?$/s',$expre_time) || !preg_match('/^\d{4}-\d{2}-\d{2}$/s',$expre_time)){
                        $n = strtotime($expre_time);
                        if(empty($n)){
                            $msg.= '工作簿'.$i.'第'.$k.'行创建时间格式不正确,已为您过滤！';
                            continue;
                        }else{
                            $gqsj = date('Y-m-d H:i:s',$n);

                        }
                    }else{
                        $msg.= '工作簿'.$i.'第'.$k.'行创建时间格式不正确,已为您过滤！';
                        continue;
                    }
                }

                $domainArr[] = $tit;
                
                $ymhz = preg_split("/\./",$tit);
                $ymhzv = str_replace($ymhz[0],"",$tit);
                // 域名转入选择
                // if($params['zcs'] == 71){
                //     $userid = 2397;
                // }else{
                //     $userid = 15;
                // }
                $insertsql .= " ({$userid},'$time',9,'$tit',".strlen($ymhz[0]).",'$zcsj','$gqsj','$ymhzv','{$params['zcs']}','{$params['appid']}','{$params['special']}',{$st}),";

              
            }  
                   
        }

        if(count($domainArr) > 12000){

            $this->error('上传的文件不得超过12000行数据');

        }

        return [$insertsql,$domainArr,$msg];

    }


    // 加载API
    public function getApi(){
        $id = $this->request->post('id');
        if($id){
            $apis = $this->getApis(-1);
            $arr = [];
            foreach($apis as $k => $v){
                if($v['regid'] == $id){
                    $arr[$k]['id'] = $v['id'];
                    $arr[$k]['tit'] = $v['tit'];
                }
            }
            return json(['code'=>0,'res' => $arr]);
        }
        return json(['code'=>1,'msg'=>'加载失败']);
    }

}
