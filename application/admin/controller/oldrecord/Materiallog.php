<?php
namespace app\admin\controller\oldrecord;
use app\common\controller\Backend;
use think\Db;
use app\admin\common\Fun;
/**
 * 素材统计
 *
 * @icon fa fa-user
 */
class Materiallog extends Backend
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

    	$tableName = $this->getRecordYearTableName('domain_promotion_material_log_20');

        //设置过滤方法
        if ($this->request->isAjax())
        {
            list($where, $sort, $order, $offset, $limit,$group,$special_condition) = $this->buildparams();
             
            if(empty($special_condition)){
                $special_condition = $tableName[0];
            }

            $def = '';
            if($group){
                $def = 'm.link = "'.$group.'" or l.other = "'.$group.'"  ';
            }

            $table = $this->model->name('domain_promotion_material_log_'.$special_condition);

            $total = $table->alias('l')->join('domain_promotion_material m','l.mid=m.id')->join('domain_user u','u.id=l.puserid')->join('domain_user u1','u1.id=l.userid','left')
                    ->where($where)->where($def)
                    ->count();

            $list = $table->alias('l')->join('domain_promotion_material m','l.mid=m.id')->join('domain_user u','u.id=l.puserid')->join('domain_user u1','u1.id=l.userid','left')
                    ->field('u.uid,u1.uid as uuid,m.title,l.mark,l.type,l.ip,l.ctime,m.link,l.other,l.mid')
                    ->where($where)->where($def)
                    ->order($sort, $order)
                    ->limit($offset, $limit)
                    ->select();
            
            //根据条件统计总金额
            $sql = $this -> setWhere();
           
           
            if(strlen($sql) == 12){
                $def = $def ? 'where '.$def : '';
                $conm = 'SELECT count(case l.type=1 when 1 then 0 end) as wzc,count(case l.type=2 when 1 then 0 end) as zc FROM '.PREFIX.'domain_promotion_material_log_'.$special_condition.' l left join  '.PREFIX.'domain_promotion_material m on m.id=l.mid '.$def;
            }else{
                $def = $def ? ' and '.$def : '';

                $conm = 'SELECT count(case l.type=1 when 1 then 0 end) as wzc,count(case l.type=2 when 1 then 0 end) as zc FROM '.PREFIX.'domain_promotion_material_log_'.$special_condition.' l left join  '.PREFIX.'domain_promotion_material m on m.id=l.mid inner join '.PREFIX.'domain_user u on u.id=l.puserid left join '.PREFIX.'domain_user u1 on u1.id=l.userid '.$sql.$def;
            }
            $res = $this->model->query($conm);
            $fun = Fun::ini();
            $marketings = \think\Config::get('self_marketing');
            foreach($list as $k => $v){
                $list[$k]['u1.uid'] = $v['uuid'];
                $list[$k]['u.uid'] = $v['uid'];

                if($list[$k]['mid'] == 9){
                    $list[$k]['group'] = $v['other'];
                }else{
                    $list[$k]['group'] = $v['link'];
                }

                if(isset($marketings[$v['uid']])){
                    $list[$k]['u.uid'].= ' -- '.$marketings[$v['uid']];
                }
                $list[$k]['l.type'] = $fun->getStatus($v['type'],[1=>'未注册','已注册']);
                $list[$k]['yzc'] = $res[0]['zc'];
                $list[$k]['wzc'] = $res[0]['wzc'];
               
            }

            $result = array("total" => $total, "rows" => $list);
            return json($result);
        }
        
        $this->view->assign([
            'id' => $this->request->get('mid'),
            'tableName' => $tableName,
        ]);
        return $this->view->fetch();
    }
}
