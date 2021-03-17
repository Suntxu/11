<?php

namespace app\admin\controller\spread\expand;

use app\common\controller\Backend;
use fast\Random;
use think\Db;
/**
 * 推广员管理
 *
 * @icon fa fa-user
 */
class Usermanage extends Backend
{

    protected $model = null;

    /**
     * User模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Admin');
        $this->view->assign('groupdata', ['2'=>'推广员']);
    }

    /**
     * 
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax())
        {
           
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            
            //获取用户的ID
            $uids = Db::name('auth_group_access')->where(['group_id'=>2])->column('uid');
            $wh = 'topspreader in ('.implode(',',$uids).' )';
            $total = $this->model->where($where)->whereIn('id',$uids)->count();
            $list = $this->model->where($where)->whereIn('id',$uids)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            $list = collection($list)->toArray();
            $ids = array_column($list,'id');
            //总金额
            $userPay = Db::name('domain_dingdang')->whereIn('topspreader',$ids)->group('topspreader')->column('topspreader,count(distinct userid) as total');
            //已注册用户
            $userUn = Db::name('domain_user')->whereIn('topspreader',$ids)->group('topspreader')->column('topspreader,count(*) as total');
            //已充值金额
            $jine = Db::name('domain_dingdang')->whereIn('topspreader',$ids)->group('topspreader')->where('ifok',1)->column('topspreader,sum(money1) as total');
            //根据条件统计总金额
            $sql = $this -> setWhere();
            if(strlen($sql) == 12){
                $conm = 'SELECT sum(money1) as n FROM '.PREFIX.'domain_dingdang WHERE topspreader != 0 AND ifok=1 ';
            }else{
                $conm = 'SELECT sum(money1) as n FROM '.PREFIX.'domain_dingdang WHERE ifok=1 AND topspreader IN ( SELECT id FROM '.PREFIX.'admin '.$sql.' and '.$wh.' )';
            }
            $res = Db::query($conm);
            foreach($list as $k => $v){
                $list[$k]['createtime'] = date('Y-m-d H:i:s',$v['createtime']);
                $list[$k]['user_pay'] = isset($userPay[$v['id']]) ? $userPay[$v['id']] : 0;
                $list[$k]['user_un'] = isset($userUn[$v['id']]) ? $userUn[$v['id']] : 0;
                $list[$k]['jine'] = isset($jine[$v['id']]) ? sprintf('%.2f',$jine[$v['id']]) : 0;
                $list[$k]['zje'] = sprintf('%.2f',$res[0]['n']);
            }
            $result = array("total" => $total, "rows" => $list);

            return json($result);
        }
        return $this->view->fetch();
    }
    /**
     * 添加
     */
    public function add($flag='')
    {
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                $params['salt'] = Random::alnum();
                $params['password'] = md5(md5($params['password']) . $params['salt']);
                $params['avatar'] = '/assets/img/avatar.png'; //设置新管理员默认头像。
                $result = $this->model->validate('Admin.add')->save($params);
                if ($result === false)
                {
                    $this->error($this->model->getError());
                }
                $group = $this->request->post("group/a");

                //过滤不允许的组别,避免越权
                $group = array_intersect([2], $group);
                $dataset = [];
                foreach ($group as $value)
                {
                    $dataset[] = ['uid' => $this->model->id, 'group_id' => $value];
                }
                model('AuthGroupAccess')->saveAll($dataset);
                $this->success();
            }
            $this->error();
        }
        return $this->view->fetch();
    }
    /**
     * 编辑
     */
    public function edit($ids = NULL)
    {
        $row = $this->model->get(['id' => $ids]);
        if (!$row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost())
        {
            $params = $this->request->post("row/a");
            if ($params)
            {
                if ($params['password'])
                {
                    $params['salt'] = Random::alnum();
                    $params['password'] = md5(md5($params['password']) . $params['salt']);
                }
                else
                {
                    unset($params['password'], $params['salt']);
                }
                //这里需要针对username和email做唯一验证
                $adminValidate = \think\Loader::validate('Admin');
                $adminValidate->rule([
                    'username' => 'require|max:50|unique:admin,username,' . $row->id,
                    'email'    => 'require|email|unique:admin,email,' . $row->id
                ]);
                $result = $row->validate('Admin.edit')->save($params);
                if ($result === false)
                {
                    $this->error($row->getError());
                }

                // 先移除所有权限
                model('AuthGroupAccess')->where('uid', $row->id)->delete();

                $group = $this->request->post("group/a");

                // 过滤不允许的组别,避免越权
                $group = array_intersect([2], $group);

                $dataset = [];
                foreach ($group as $value)
                {
                    $dataset[] = ['uid' => $row->id, 'group_id' => $value];
                }
                model('AuthGroupAccess')->saveAll($dataset);
                $this->success();
            }
            $this->error();
        }
        $grouplist = $this->auth->getGroups($row['id']);
        $groupids = [];
        foreach ($grouplist as $k => $v)
        {
            $groupids[] = $v['id'];
        }
        $this->view->assign("row", $row);
        $this->view->assign("groupids", $groupids);
        return $this->view->fetch();
    }


    /**
     * 删除
     */
    public function del($ids = "")
    {
        if ($ids)
        {
            $this->model->destroy($ids);
            model('AuthGroupAccess')->where('uid', 'in', $ids)->delete();
            $this->success();
            
        }
        $this->error();
    }

}
