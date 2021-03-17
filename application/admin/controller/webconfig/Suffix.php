<?php

namespace app\admin\controller\webconfig;

use app\common\controller\Backend;
use think\Db;
use think\Validate;
use app\admin\common\Fun;
/**
 * 后缀设置
 *
 * @icon fa fa-user
 */
class Suffix extends Backend
{
    /**
     * @var \app\admin\model\HZ
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_houzhui');
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax())
        {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('h')->where($where)->count();
            //获取已优惠的数量
            $field = ',(select sum(success_num)  from '.PREFIX.'reg_discount where hz = h.name1 and time between h.yhsj1 and h.yhsj2  ) as snum';
            $list = $this->model->alias('h')
                    ->field('name1,xh,ysje,money,yhsj1,yhsj2,xfmoney,xfxfyg,xfsj1,xfsj2,regbrokerage,sj,id,res_pirce,discounts,cost,aid'.$field)
                    ->where($where)->order($sort,$order)->limit($offset, $limit)
                    ->select();
            $apis = $this->getApis(-1);
            foreach($list as &$v){
                $v['tit'] =  empty($apis[$v['aid']]['tit']) ? '--' : $apis[$v['aid']]['tit'];
                
                $v['snum'] = ($v['discounts'] == 0) ? 0 : $v['discounts'] - $v['snum'];

                $v['aregister'] = '查看';

            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        return $this->view->fetch();
    }
    
    /**
     * 添加
     */
    public function add()
    {
        if ($this->request->isPost()){
            $requ = $this->request->post();
            $params = $requ['row'];
            $pageHz = $requ['page'];
            $recy = $requ['recy'];
            $news = $this->request->post('news');
            $xf = $requ['xf'];
            //规则验证
            $rule = [
                'row[name1]'  => 'require|max:10',
                'row[ysje]' => 'require|number',
                'page[title]' => 'require',
                'news'  => 'require',
            ];
            $msg = [
                'row[name1].require' => '后缀必须填写',
                'row[name1].max'     => '后缀最多不能超过10个字符',
                'row[ysje].require' => '注册原始价格必须填写',
                'row[ysje].number' => '请输入有效的注册原始价格价格',
                'page[title].require' => '请输入页面标题名字',
                'news.require'  => '请输入对应的资讯分类',
            ];
            $data = [
                'row[name1]'  => $params['name1'],
                'row[ysje]'  => $params['ysje'],
                'page[title]' => $pageHz['title'],
                'news' => $news,
            ];
            $validate = new Validate($rule, $msg);
            if(!$validate->check($data)){
                $this->error($validate -> getError());
            }
            //回收判断
            if (isset($recy['status']) &&$recy['status'] == 0){
                // if($recy['recycle_price'] == 0){
                //     $this->error('请设置回收基础价格');
                // }
                if(empty($recy['datemin'])){
                    $this->error('请设置回收最小到期时间');
                }
                if($recy['datemin'] < 1){
                    $this->error('最小到期时间必须大于或等于1天');
                }
            }
            //查找 后缀是否唯一
            $num = $this->model->where(['name1'=>$params['name1']])->count();
            if($num > 0){
                $this->error('后缀'.$params['name1'].'已存在');
            }
            
            $time = Fun::ini()->DateToTime(['yhsj1'=>$params['yhsj1'],'yhsj2'=>$params['yhsj2'],'xfsj1'=>$params['xfsj1'],'xfsj2'=>$params['xfsj2']]);
            $time['sj'] = date('Y-m-d H:i:s');
            $data = array_merge($params,$time);
            //插入帮助分类-默认48
            $newa = Db::name('domain_newstype')->where(['pid' => 48,'name1' => $news])->value('id');
            if($newa > 0){
                $this->error('后缀资讯'.$news.'已存在');
            }
            // $pageHz['imgpath'] = strstr($pageHz['imgpath'],'?',true);
            $recy['name'] = $params['name1'];
            $sj = time();
            try{
                Db::startTrans();
                $pageHz['hid'] = $this->model ->insertGetId($data);
                $pageHz['ntypeid'] = Db::name('domain_newstype')->insertGetId(['name1' => $news,'pid' => 48,'xh' => 0,'zt' => 1,'sj' => $time['sj']]);
                Db::name('suffix_page')->insert($pageHz);
                Db::name('recycle_config')->insert($recy);
                $recy['auth_id'] = $this->auth->id;
                $recy['create_time'] = $sj;
                Db::name('recycle_config_record')->insert($recy);
                //批量插入
                $count = Db::name('domain_renew_config')->where(['hz'=>$params['name1']])->count();
                if($count == 0){
                    foreach ($xf as $v){
                        $v['hz'] = $params['name1'];
                        Db::name('domain_renew_config')->insert($v);
                    }
                }
                //插入设置记录页面
                unset($data['id'],$data['sj'],$data['domain_intro']);
                $data['create_time'] = $sj;
                $data['auth_id'] = $this->auth->id;
                Db::name('domain_houzhui_record')->insert($data);
                Db::commit();
            }catch(\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success('添加成功');
        }
        $renew = Db::name('category')
            ->where(['type' => 'api', 'status' => 'normal'])
            ->field("id as cid,name,0 money,0 status")
            ->select();
        $this->view->assign([
            'api' => $this->getApis(-1),
            'renew' => $renew,
        ]);
        return $this->view->fetch();
    }
    
    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
         if ($this->request->isPost()){

            $requ = $this->request->post();
            $params = $requ['row'];
            $pageHz = $requ['page'];
            $recy = $requ['recy'];
            $news = $requ['news'];
            $xf = $requ['xf'];
          
            if ($params){
                //规则验证
                $rule = [
                    'row[ysje]' => 'require|number',
                    'page[title]' => 'require',
                    'news[name1]'  => 'require',
                ];
                $msg = [
                    'row[ysje].require' => '注册原始价格必须填写',
                    'row[ysje].number' => '请输入有效的注册原始价格价格',
                    'page[title].require' => '请输入页面标题名字',
                    'news[name1].require'  => '请输入对应的资讯分类',
                  
                ];
                $data = [
                    'row[ysje]'  => $params['ysje'],
                    'page[title]' => $pageHz['title'],
                    'news[name1]' => $news['name1'],
                  
                ];
                $validate = new Validate($rule, $msg);
                if(!$validate->check($data)){
                    $this->error($validate -> getError());
                }
              
                //回收判断
                if (isset($recy['status']) &&$recy['status'] == 0){
                    // if($recy['recycle_price'] == 0){
                    //     $this->error('请设置回收基础价格');
                    // }
                    if(empty($recy['datemin'])){
                        $this->error('请设置回收最小到期时间');
                    }
                    if($recy['datemin'] < 1){
                        $this->error('最小到期时间必须大于或等于1天');
                    }
                }
                $time = Fun::ini()->DateToTime(['yhsj1'=>$params['yhsj1'],'yhsj2'=>$params['yhsj2'],'xfsj1'=>$params['xfsj1'],'xfsj2'=>$params['xfsj2']]);
                $time['sj'] = date('Y-m-d H:i:s');
                $data = array_merge($params,$time);
            
                //插入资讯分类-默认48
                $nid = Db::name('domain_newstype')->where(['pid' => 48,'name1' => $news['name1']])->where('id','<>',$news['id'])->value('id');
                if($nid > 0){
                    $this->error('后缀资讯'.$news['name1'].'已存在');
                }
             
                $pageHz['imgpath'] = str_replace('/uploads','', $pageHz['imgpath'] );
               
                $recy['name'] = $data['name1'];
                try{

                    Db::startTrans();
                    $this->model->where(['id'=>$params['id']])->update($data);
                    if(empty($pageHz['id'])){
                        $pageHz['ntypeid'] = Db::name('domain_newstype')->insertGetId(['name1' => $news['name1'],'pid' => 48,'xh' => 0,'zt' => 1,'sj' => $time['sj']]);
                        $pageHz['hid'] = $params['id'];
                        Db::name('suffix_page')->insert($pageHz);
                    }else{
                        Db::name('domain_newstype')->update($news);
                        Db::name('suffix_page')->update($pageHz);
                    }
                    $sj = time();
                    //插入设置记录页面
                    unset($data['id'],$data['sj'],$data['domain_intro']);
                    $data['create_time'] = $sj;
                    $data['auth_id'] = $this->auth->id;
                    Db::name('domain_houzhui_record')->insert($data);
                    
                    // //判断回收设置                    
                    $flag = Db::name('recycle_config')->where('name',$data['name1'])->count();
                    if($flag){
                        Db::name('recycle_config')->where('name',$data['name1'])->update($recy);
                    }else{
                        Db::name('recycle_config')->insert($recy);
                    }
                    $recy['auth_id'] = $this->auth->id;
                    $recy['create_time'] = $sj;
                    Db::name('recycle_config_record')->insert($recy);
                    //批量插入或者更新
                    $zcs = Db::name('domain_renew_config')->where(['hz'=>$params['name1']])->column('zcs');
                    foreach ($xf as $v){
                        //zcs 和 hz 都存在时才更新
                        if(in_array($v['zcs'], $zcs)){
                            Db::name('domain_renew_config')->where(['zcs'=>$v['zcs'], 'hz'=>$params['name1']])->update($v);
                        }else{
                            $v['hz'] = $params['name1'];
                            Db::name('domain_renew_config')->insert($v);
                        }
                    }
                    Db::commit();
                }catch (\Exception $e){
                    Db::rollback();
                    $this->error($e->getMessage());
                }
                $this->success('修改成功');
            }else{
                $this->error('缺少数据');
            }
        }
        $data = $this->model->alias('h')
            ->join('suffix_page p','p.hid=h.id','left')
            ->join('domain_newstype n','n.id=p.ntypeid','left')
            ->join('recycle_config c','c.name = h.name1','left')
            ->field('h.*,p.id as pid,p.title,p.hzdesc,p.imgpath,p.desc,n.name1 as nname1,n.id as nid,p.seotit,h.discounts,c.recycle_price,c.recycle_inc,c.datemin,c.datemax,c.max_money,c.status')
            ->where(['h.id'=>$ids])
            ->find();
            $data['imgpath'] = '/uploads'.$data['imgpath'];
        $time = Fun::ini()->DateToTime(['yhsj1'=>$data['yhsj1'],'yhsj2'=>$data['yhsj2'],'xfsj1'=>$data['xfsj1'],'xfsj2'=>$data['xfsj2']],false);
        $data = array_merge($data,$time);
        $renew = Db::name('category')
            ->alias('c')
            ->join('domain_renew_config r',"c.id = r.zcs and (r.hz = '{$data['name1']}' or r.hz = null)",'left')
            ->where(['c.type' => 'api', 'c.status' => 'normal'])
            ->group('c.id')
            ->field("c.id as cid,c.name,r.id,IFNULL(r.zcs,c.id) as zcs,IFNULL(r.money,0) as money,IFNULL(r.status,0) as status,IFNULL(r.hz,'{$data['name1']}') as hz")
            ->select();
        $this->view->assign([
            'data'=>$data,
            'api' => $this->getApis(-1),
            'renew' => $renew,
        ]);
        return $this->view->fetch();
    }
    /**
     * 删除
     */
    public function del($ids='')
    {
       //return $this->request->param();
       if($ids){
            try{
                $hz = Db::name('domain_houzhui')
                    ->where([
                        'id' => ['in',$ids]
                    ])
                    ->column('name1');
                Db::startTrans();
                Db::name('domain_renew_config')
                    ->where([
                        'hz' => ['in',implode(',',$hz)]
                    ])
                    ->delete();
                $this->model->delete($ids);
                Db::name('suffix_page')->whereIn('hid',$ids)->delete();
                Db::name('recycle_config')->whereIn('name',$hz)->delete();
                Db::commit();
            }catch (\Exception $e){
                Db::rollback();
                $this->error($e->getMessage());
            }
            $this->success('删除成功');
       }else{
            $this->error('缺少重要参数');
       }
    }
    /**
     * 点击后价格修改ajax
     */
    public function updateMoney()
    {
        $data = $this->request->post();
        $field = isset($data['field']) ? trim($data['field']) : '';
        if($field){
            $this->model->where(['id'=>intval($data['id'])])->update([$field=>$data['val']]);

            $suffix = $this->model->where(['id' => $data['id']])->value('name1');

            Db::name('domain_houzhui_record')->insert([$field => $data['val'],'name1' => $suffix,'auth_id' => $this->auth->id,'create_time' => time()]);

            echo $data['val'];
        }else{
            echo '更新失败';
        }
    }

}
