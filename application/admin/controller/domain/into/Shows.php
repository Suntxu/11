<?php
namespace app\admin\controller\domain\into;
use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 域名转回记录
 *
 * @icon fa fa-user
 */
class Shows extends Backend
{
    protected $model = null;
    /**
     * @var \app\admin\model\HZ
     */
    protected $fun = null;
    public function _initialize()
    {
        parent::_initialize();
        $this->model = Db::name('domain_into');
    }
    /**
     * 查看
     */
    public function index()
    {
        //设置过滤方法
        if ($this->request->isAjax()) {   
            list($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->alias('n')->join('domain_pro_n p','n.domian=p.tit','left')
                    ->where($where)
                    ->count();
            $list = $this->model->alias('n')->join('domain_pro_n p','n.domian=p.tit','left')
                        ->field('n.domian,p.zcsj,p.dqsj,n.api_id,p.zcs')->where($where)
                        ->order($sort,$order)->limit($offset, $limit)
                        ->select();

            $apis = $this->getApis(-1);
            $cates = $this->getCates();
            foreach($list as $k => &$v){
                $v['name'] = empty($cates[$v['zcs']]) ? '--' : $cates[$v['zcs']];
                $v['tit'] = empty($apis[$v['api_id']]) ? '--' : $apis[$v['api_id']]['tit'];
            }
            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        $this->view->assign('ids',$this->request->get('id'));
        return $this->view->fetch();
    }
    /**
     * 下载txt全部域名
     */ 
    public function download(){
        $bid = intval($this->request->get('pid'));
        $domain = $this->model->where(['bid'=>$bid])->column('domian');
        
        $filename = time().rand(1000,9999).'.txt';
        Fun::ini()->txtFile($domain,$filename);
    }
}