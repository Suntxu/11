<?php

namespace app\admin\controller\spread\expand;

use app\common\controller\Backend;
use think\Db;

/**
 *推广--渠道管理
 *
 * @icon fa fa-user
 */
class Channel extends Backend
{
    /**
     * UserÄ£ÐÍ¶ÔÏó
     */
    protected $model = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Channel');
    }

    /**
     * ²é¿´
     */
    public function index()
    {

        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model
                    ->where($where)
                    ->order($sort, $order)
                    ->count();
            $list = $this->model
                    ->where($where)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $list = collection($list)->toArray();

            $mid = array_column($list,'id');
            $ddzje = Db::name('domain_dingdang')->where('ifok = 1 and topspreader != 0')->whereIn('channel',$mid)->group('channel')->column('sum(money1)','channel');
            $reguser = Db::name('domain_user')->where('topspreader != 0')->whereIn('channel',$mid)->group('channel')->column('count(*)','channel');
            $domainje = Db::name('flow_record')->alias('f')->join('domain_user u','u.id=f.userid')
                    ->where('f.product = 0 and f.subtype = 2 and u.topspreader != 0')->whereIn('u.channel',$mid)
                    ->group('u.channel')
                    ->column('sum(f.money)','u.channel');
            //获取订单金额
            $jy = $this->getOrderMoney($mid);
            //获取uv
            $uv = $this->getUv($mid);
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm1 = 'SELECT sum(money1) as n FROM '.PREFIX.'domain_dingdang WHERE topspreader != 0 AND ifok=1';
                //域名交易花费金额
                $conm2 = 'SELECT sum(money) as n,sum(final_money) as f FROM '.PREFIX.'domain_order  WHERE topid != 0 AND status = 1 AND sptype = 1';
                //注册域名花费金额
                $conm3 = 'SELECT sum(money) as n FROM '.PREFIX.'flow_record WHERE  product = 0 and subtype = 2 AND userid IN ( SELECT id FROM '.PREFIX.'domain_user WHERE channel <> 0 AND  topspreader <> 0 )';
            }else{
                $conm1 = 'SELECT sum(money1) as n FROM '.PREFIX.'domain_dingdang WHERE topspreader != 0 AND ifok=1 AND channel IN ( SELECT id FROM '.PREFIX.'spread_channel '.$sql.' )';
                $conm2 = 'SELECT sum(`money`) as n, sum(final_money) as f FROM '.PREFIX.'domain_order o left join '.PREFIX.'domain_user u ON o.userid=u.id WHERE o.topid != 0 AND o.status=1 AND o.sptype = 1 AND u.channel IN ( SELECT id FROM '.PREFIX.'spread_channel '.$sql.' )';
                // //获取渠道的ID
                $conm3 = 'SELECT sum(money) as n FROM '.PREFIX.'flow_record WHERE product = 0 and subtype = 2 and userid in( SELECT id FROM '.PREFIX.'domain_user WHERE  topspreader <> 0 AND channel IN( SELECT id FROM '.PREFIX.'spread_channel '.$sql.' ) )';
            }

            $res1 = Db::query($conm1);
            $res2 = Db::query($conm2);
            $res3 = Db::query($conm3);
            $cates = $this->getCates('spread');

            foreach($list as $k => $v){
                $list[$k]['category_text'] = $cates[$v['category_id']];
                $list[$k]['ddzje'] = isset($ddzje[$v['id']]) ? sprintf('%.2f',$ddzje[$v['id']]) : 0;
                $list[$k]['czzje'] = isset($jy[$v['id']]) ? sprintf('%.2f',$jy[$v['id']]['n']) : 0;
                $list[$k]['dzje'] = sprintf('%.2f',$res2[0]['n']);
                $list[$k]['czje'] = sprintf('%.2f',$res1[0]['n']);
                // 订单实付金额
                $list[$k]['sfje'] = isset($jy[$v['id']]) ? sprintf('%.2f',$jy[$v['id']]['f']) : 0;
                $list[$k]['sfzje'] = sprintf('%.2f',abs($res2[0]['f']));
                // 域名注册金额
                $list[$k]['domainje'] = isset($domainje[$v['id']]) ? abs($domainje[$v['id']]) : 0;
                $list[$k]['domainzje'] = sprintf('%.2f',abs($res3[0]['n']));
                // 获取本渠道下的用户人数
                $list[$k]['reguser'] = isset($reguser[$v['id']]) ?  $reguser[$v['id']] : 0;
                $list[$k]['uv'] = isset($uv[$v['id']]) ? $uv[$v['id']] : 0;
            }

            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }

    /**
     * ±à¼­
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get($ids);
        if (!$row)
            $this->error(__('No Results were found'));
        
        $adminIds = $this->getDataLimitAdminIds();
        if (is_array($adminIds)) {
            if (!in_array($row[$this->dataLimitField], $adminIds)) {
                $this->error(__('You have no permission'));
            }
        }
        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params) {
                if(empty($params['category_id'])){
                    $this->error('请选择渠道类别');
                }
                try {
                    //ÊÇ·ñ²ÉÓÃÄ£ÐÍÑéÖ¤
                    if ($this->modelValidate) {
                        $name = basename(str_replace('\\', '/', get_class($this->model)));
                        $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.edit' : true) : $this->modelValidate;
                        $row->validate($validate);
                    }
                    
                    $result = $row->allowField(true)->save($params);
                    if ($result !== false) {
                        $this->success();
                    } else {
                        $this->error($row->getError());
                    }
                }
                catch (\think\exception\PDOException $e) {
                    $this->error($e->getMessage());
                }
            }
            $this->error(__('Parameter %s can not be empty', ''));
        }
        $this->view->assign([
            'row' => $row,
            'data' => $this->getCates('spread',false),
        ]);
        return $this->view->fetch();
    }

    /**
     * Ìí¼Ó
     */
    public function add(){
        if ($this->request->isPost()){
            $params = $this->request->post("row/a");
            if ($params){
                if(empty($params['category_id'])){
                    $this->error('请选择渠道类别');
                }
                if ($this->dataLimit && $this->dataLimitFieldAutoFill) {
                    $params[$this->dataLimitField] = $this->auth->id;
                }
                if ($this->modelValidate) {
                    $name = basename(str_replace('\\', '/', get_class($this->model)));
                    $validate = is_bool($this->modelValidate) ? ($this->modelSceneValidate ? $name . '.add' : true) : $this->modelValidate;
                    $this->model->validate($validate);
                }
                
                $params['admin_id'] = $this->auth->id;
                $result = $this->model->allowField(true)->save($params);
                if ($result !== false) {
                    $this->success();
                } else {
                    $this->error($this->model->getError());
                }
            }
        }
        $this->view->assign('data',$this->getCates('spread',false));
        return $this->view->fetch();
    }

    /**
     * 获取订单金额
     */
    private function getOrderMoney($mid){
        $jy =  Db::name('domain_order')->alias('o')->join('domain_user u','o.userid=u.id')->field('sum(o.money) as n, sum(o.final_money) as f,u.channel')
            ->where(' o.status = 1 and sptype = 1 and o.topid != 0')->whereIn('u.channel',$mid)
            ->group('u.channel')
            ->select();
        $arr = [];
        foreach($jy as $v){
            $arr[$v['channel']] = $v;
        }
        return $arr;
    }

    /**
     * 获取UV
     */
    private function getUv($mid){
        if(empty($mid)){
            return [];
        }
        global $remodi_db;
        $rmodel = Db::connect($remodi_db);
        // 获取访问量统计表 一共几个
        $total_num = $this->getRecordYearTableName('total_20');
        // 获取本渠道下的独立访问量
        //往年
        $arr = [];
        $fun = [];
        foreach($total_num as $vv){
            $fun[] = $rmodel->name('total_'.$vv)->whereIn('top',$mid)->group('top')->column('count(DISTINCT cookie)','top');
        }
        $totals = Db::name('total_'.date('Y'))->whereIn('top',$mid)->group('top')->column('count(DISTINCT cookie)','top');
        $fun = array_merge($fun,[$totals]);
        foreach($fun as $v){
            foreach($v as $kk => $vv){
                if(isset($arr[$kk])){
                    $arr[$kk] += $vv;
                }else{
                    $arr[$kk] = $vv;
                }
            }
        }
        return $arr;
    }

}
