<?php
namespace app\admin\controller\oldrecord;
use app\common\controller\Backend;
use think\Db;
/**
 * 访问量统计
 *
 * @icon fa fa-user
 */
class Stotal extends Backend
{
    protected $noNeedRight = ['*'];
    protected $model = null;

    public function _initialize()
    {
        global $remodi_db;
        $this->model = Db::connect($remodi_db);

        parent::_initialize();
    }
    /**
     * 查看
     */
    public function index()
    {
        $tableName = $this->getRecordYearTableName('total_20');
        //设置过滤方法
        if ($this->request->isAjax())
        {   

            list($where, $sort, $order, $offset, $limit,$group,$special_condition,$admin) = $this->buildparams();
            
            $aInfo = $this->getAdminNickname();

            $def = ' t.link = '.$this->auth->id;

            if($admin){
                $admin_id = isset($aInfo[$admin]) ? $aInfo[$admin] : 0;
                $def = ' t.link = '.$admin_id;
            }

            if(empty($special_condition)){
                $special_condition = $tableName[0];
            }

            $table = $this->model->name('total_'.$special_condition);

            if(empty($group)){
                $total = $table->alias('t')->join('domain_link l','t.lid=l.id','left')->where($where)->where($def)->count();

                $list = $table->alias('t')->join('domain_link l','t.lid=l.id','left')
                    ->field('t.*,l.alink')->where($where)->where($def)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            }else{
                if($group == 'cookie'){
                    $gr = 't.cookie';
                }else{
                    $gr = 't.ip,DATE_FORMAT(t.create_time,"%Y-%m-%d")';
                }
                $total = $table->alias('t')->join('domain_link l','t.lid=l.id','left')
                    ->where($where)->where($def)
                    ->group($gr)
                    ->count();
                $list = $table->alias('t')->join('domain_link l','t.lid=l.id','left')
                    ->field('t.*,l.alink')
                    ->where($where)->where($def)
                    ->order($sort,$order)
                    ->group($gr)
                    ->limit($offset, $limit)
                    ->select();
            }

            $sp = $this->getChannelName();
            $aInfo = array_flip($aInfo);
            foreach($list as &$v){
                $v['top'] = isset($sp[$v['top']]) ? $sp[$v['top']] : '--';
                $v['spec'] = isset($aInfo[$v['link']]) ? $aInfo[$v['link']] : '--';

            }

            $result = array("total" => $total,"rows" => $list);
            return json($result);
        }
        
        $this->view->assign(['gro'=>$this->request->get('gro'),'tableName' => $tableName]);
        return $this->view->fetch();
    }

}
