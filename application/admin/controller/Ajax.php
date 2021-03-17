<?php

namespace app\admin\controller;

use app\common\controller\Backend;
use fast\Random;
use think\addons\Service;
use think\Cache;
use think\Config;
use think\Db;
use think\Lang;
use app\admin\library\OssApi;
use fast\Http;

/**
 * Ajax异步请求接口
 * @internal
 */
class Ajax extends Backend
{

    protected $noNeedLogin = ['lang'];
    protected $noNeedRight = ['*'];
    protected $layout = '';

    public function _initialize()
    {
        parent::_initialize();

        //设置过滤方法
        $this->request->filter(['strip_tags', 'htmlspecialchars']);
    }

    /**
     * 用户中心上传文件接口
     */
    public function uploadUser(){

        $type = $this->request->get('flag');
        if(empty($type)){
            $this->error('类型错误,接口调用失败!');
        }

        $file = $this->request->file('file');
        if(empty($file)){
            $this->error('请上传文件');
        } 
        if($type == 'real'){
            $validate = ['size'=>1048576,'ext'=>'jpg','mixsize' => 56320,'type' => 'image/jpeg'];
            $path = 'users/alireal/';
            $dPath = 'alireal';
        }
        $tempUrl = ROOT_PATH . 'public/uploads' . DS . 'temp';

        $info = $file->validate($validate)->move($tempUrl,true,true);
        if($info){

            $fn = fopen($info->getPathname(),'r');
            $img = 'data:image/jpeg;base64,'.chunk_split(base64_encode(stream_get_contents($fn)));
            fclose($fn);
            $time = time();
            $token = 'wad_eiqdas_d!@w';
            $sign = md5('time='.$time.'&token='.$token.'&path='.$dPath);
            $param = ['path' => $dPath,'sign' => $sign,'time' => $time,'type' => $type,'img' => $img];
            $res = Http::post(WEBURL.'api/apioperate/saveBaseFile',$param);
            $res1 = json_decode($res,true);
            if($res1['code'] == 0){
                // $uploadDir = date('Ymd').'/'.uniqid().'.jpg';
                $OssBecket = new OssApi();
                $loadUrl = $path.$res1['msg'];
                $res = $OssBecket->uploadFile(OSS_BUCKET_NAME,$loadUrl,$tempUrl.'/'.$info->getsaveName());
                @unlink($tempUrl.'/'.$info->getsaveName());

                if($res['code'] == 1){
                    return json(['code' => 0,'msg' => $res['msg'] ]);
                }
                return json(['code' => 1,'msg' => '上传成功','data' => ['url' => $res1['msg'] ] ]);
            }
            return json(['code' => 0,'msg' => $res1['msg'] ]);
           
        }else{
            return json(['code' => 0,'msg' => $file->getError()]);
        }
    }
    /**
     * 加载语言包
     */
    public function lang()
    {
        header('Content-Type: application/javascript');
        $controllername = input("controllername");
        //默认只加载了控制器对应的语言名，你还根据控制器名来加载额外的语言包
        $this->loadlang($controllername);
        return jsonp(Lang::get(), 200, [], ['json_encode_param' => JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE]);
    }

    /**
     * 上传文件
     */
    public function upload()
    {
        Config::set('default_return_type', 'json');
        $file = $this->request->file('file');
        if (empty($file)) {
            $this->error(__('No file upload or server upload limit exceeded'));
        }
       
        //判断是否已经存在附件
        $sha1 = $file->hash();

        $upload = Config::get('upload');
        
        preg_match('/(\d+)(\w+)/', $upload['maxsize'], $matches);
        $type = strtolower($matches[2]);
        $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
        $size = (int)$upload['maxsize'] * pow(1024, isset($typeDict[$type]) ? $typeDict[$type] : 0);
        $fileInfo = $file->getInfo();
        $suffix = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        $suffix = $suffix ? $suffix : 'file';
        $mimetypeArr = explode(',', strtolower($upload['mimetype']));
        $typeArr = explode('/', $fileInfo['type']);
        //验证文件后缀
        if ($upload['mimetype'] !== '*' &&
            (
                !in_array($suffix, $mimetypeArr)
                || (stripos($typeArr[0] . '/', $upload['mimetype']) !== false && (!in_array($fileInfo['type'], $mimetypeArr) && !in_array($typeArr[0] . '/*', $mimetypeArr)))
            )
        ) {
            $this->error(__('Uploaded file format is limited'));
        }
        $replaceArr = [
            '{year}'     => date("Y"),
            '{mon}'      => date("m"),
            '{day}'      => date("d"),
            // '{hour}'     => date("H"),
            // '{min}'      => date("i"),
            // '{sec}'      => date("s"),
            '{random}'   => Random::alnum(16),
            '{random32}' => Random::alnum(32),
            '{filename}' => $suffix ? substr($fileInfo['name'], 0, strripos($fileInfo['name'], '.')) : $fileInfo['name'],
            '{suffix}'   => $suffix,
            '{.suffix}'  => $suffix ? '.' . $suffix : '',
            '{filemd5}'  => md5_file($fileInfo['tmp_name']),
        ];
        $savekey = $upload['savekey'];
        $savekey = str_replace(array_keys($replaceArr), array_values($replaceArr), $savekey);

        // $uploadDir = substr($savekey, 0, strripos($savekey, '/') + 1);
        // $fileName = substr($savekey, strripos($savekey, '/') + 1);
        //可自定义上传路径
        $zdypath = $this ->request->get('filepath');
        $hz = $this ->request->get('hz');
        $fileName = $hz?$hz: substr($savekey, strripos($savekey, '/') + 1);
        $storage = 'local';
        //入库文件存在本地
        if(empty($zdypath)){
            $uploadDir = trim(substr($savekey, 0, strripos($savekey, '/') + 1),'/');
            // $uploadDir = $upload['rootpath'];
            $splInfo = $file->validate(['ext'=>'jpg,jpeg,png','size' => $size])->move(ROOT_PATH . '/public/' . $uploadDir.'/', $fileName);


        }else if($zdypath == 'oss') {

            $uploadDir = date('Ymd');
            $OssBecket = new OssApi();
            $loadUrl = $uploadDir.'/'.$fileName;
            $res = $OssBecket->uploadFile(OSS_BUCKET_NAME,$loadUrl,$file->getPathname());
            $storage = 'ali';
            if($res['code'] == 1){
                $this->error($res['msg']);
            }

        }else{
            $uploadDir = $upload['rootpath'].$zdypath.date('Ymd');
            $splInfo = $file->validate(['size' => $size])->move(ROOT_PATH . '/public' . $uploadDir, $fileName);
        }
        // $uploadDir = $zdypath ? $mypath : $fapath;
        $retrunUrl = '/'.$uploadDir .'/'. $fileName;

        if ($splInfo) {
            $imagewidth = $imageheight = 0;
            if (in_array($suffix, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'])) {
                $imgInfo = getimagesize($splInfo->getPathname());
                $imagewidth = isset($imgInfo[0]) ? $imgInfo[0] : $imagewidth;
                $imageheight = isset($imgInfo[1]) ? $imgInfo[1] : $imageheight;
            }
            $params = array(
                'admin_id'    => (int)$this->auth->id,
                'user_id'     => 0,
                'filesize'    => $fileInfo['size'],
                'imagewidth'  => $imagewidth,
                'imageheight' => $imageheight,
                'imagetype'   => $suffix,
                'imageframes' => 0,
                'mimetype'    => $fileInfo['type'],
                'url'         => $retrunUrl,
                'uploadtime'  => time(),
                'storage'     => $storage,
                'sha1'        => $sha1,
            );
            $attachment = model("attachment");
            $attachment->data(array_filter($params));
            $attachment->save();
            \think\Hook::listen("upload_after", $attachment);

            //如果是自定义路径 就传过去一个特殊参数
            $this->success(__('Upload successful'), null, [
                'url' => $retrunUrl,
            ]);
        } else {
            // 上传失败获取错误信息
            $this->error($file->getError());
        }
    }
    /**
     * 上传文件---markdown
     */
    public function uploadMake()
    {
        Config::set('default_return_type', 'json');
        $file = $this->request->file('file0');
        if (empty($file)) {
            $this->error(__('No file upload or server upload limit exceeded'));
        }
        //判断是否已经存在附件
        $sha1 = $file->hash();

        $upload = Config::get('upload');

        preg_match('/(\d+)(\w+)/', $upload['maxsize'], $matches);
        $type = strtolower($matches[2]);
        $typeDict = ['b' => 0, 'k' => 1, 'kb' => 1, 'm' => 2, 'mb' => 2, 'gb' => 3, 'g' => 3];
        $size = (int)$upload['maxsize'] * pow(1024, isset($typeDict[$type]) ? $typeDict[$type] : 0);
        $fileInfo = $file->getInfo();
        $suffix = strtolower(pathinfo($fileInfo['name'], PATHINFO_EXTENSION));
        $suffix = $suffix ? $suffix : 'file';

        $mimetypeArr = explode(',', strtolower($upload['mimetype']));
        $typeArr = explode('/', $fileInfo['type']);
        //验证文件后缀
        if ($upload['mimetype'] !== '*' &&
            (
                !in_array($suffix, $mimetypeArr)
                || (stripos($typeArr[0] . '/', $upload['mimetype']) !== false && (!in_array($fileInfo['type'], $mimetypeArr) && !in_array($typeArr[0] . '/*', $mimetypeArr)))
            )
        ) {
            $this->error(__('Uploaded file format is limited'));
        }
        $replaceArr = [
            '{year}'     => date("Y"),
            '{mon}'      => date("m"),
            '{day}'      => date("d"),
            '{hour}'     => date("H"),
            '{min}'      => date("i"),
            '{sec}'      => date("s"),
            '{random}'   => Random::alnum(16),
            '{random32}' => Random::alnum(32),
            '{filename}' => $suffix ? substr($fileInfo['name'], 0, strripos($fileInfo['name'], '.')) : $fileInfo['name'],
            '{suffix}'   => $suffix,
            '{.suffix}'  => $suffix ? '.' . $suffix : '',
            '{filemd5}'  => md5_file($fileInfo['tmp_name']),
        ];
        $savekey = $upload['savekey'];
        $savekey = str_replace(array_keys($replaceArr), array_values($replaceArr), $savekey);

        // $uploadDir = substr($savekey, 0, strripos($savekey, '/') + 1);
        // $fileName = substr($savekey, strripos($savekey, '/') + 1);
        //可自定义上传路径
        $zdypath = $this ->request->get('filepath');
        $hz = $this ->request->get('hz');
        $mypath = $upload['rootpath'].$zdypath;
        $fapath = substr($savekey, 0, strripos($savekey, '/') + 1);
        $uploadDir = $zdypath ? $mypath : $fapath;
        $fileName = $hz?$hz: substr($savekey, strripos($savekey, '/') + 1);
        $splInfo = $file->validate(['size' => $size])->move(ROOT_PATH . '/public' . $uploadDir, $fileName);
        if ($splInfo) {
            $imagewidth = $imageheight = 0;
            if (in_array($suffix, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf'])) {
                $imgInfo = getimagesize($splInfo->getPathname());
                $imagewidth = isset($imgInfo[0]) ? $imgInfo[0] : $imagewidth;
                $imageheight = isset($imgInfo[1]) ? $imgInfo[1] : $imageheight;
            }
            return [ltrim('/uploads',$uploadDir).$splInfo->getSaveName()];
            //如果是自定义路径 就传过去一个特殊参数
            $this->success(__('Upload successful'), null, [
                'url' => $uploadDir . $splInfo->getSaveName(),
            ]);
        } else {
            // 上传失败获取错误信息
            $this->error($file->getError());
        }
    }


    /**
     * 通用排序
     */
    public function weigh()
    {
        //排序的数组
        $ids = $this->request->post("ids");
        //拖动的记录ID
        $changeid = $this->request->post("changeid");
        //操作字段
        $field = $this->request->post("field");
        //操作的数据表
        $table = $this->request->post("table");
        //排序的方式
        $orderway = $this->request->post("orderway", 'strtolower');
        $orderway = $orderway == 'asc' ? 'ASC' : 'DESC';
        $sour = $weighdata = [];
        $ids = explode(',', $ids);
        $prikey = 'id';
        $pid = $this->request->post("pid");
        //限制更新的字段
        $field = in_array($field, ['weigh']) ? $field : 'weigh';

        // 如果设定了pid的值,此时只匹配满足条件的ID,其它忽略
        if ($pid !== '') {
            $hasids = [];
            $list = Db::name($table)->where($prikey, 'in', $ids)->where('pid', 'in', $pid)->field('id,pid')->select();
            foreach ($list as $k => $v) {
                $hasids[] = $v['id'];
            }
            $ids = array_values(array_intersect($ids, $hasids));
        }

        //直接修复排序
        $one = Db::name($table)->field("{$field},COUNT(*) AS nums")->group($field)->having('nums > 1')->find();
        if ($one) {
            $list = Db::name($table)->field("$prikey,$field")->order($field, $orderway)->select();
            foreach ($list as $k => $v) {
                Db::name($table)->where($prikey, $v[$prikey])->update([$field => $k + 1]);
            }
            $this->success();
        } else {
            $list = Db::name($table)->field("$prikey,$field")->where($prikey, 'in', $ids)->order($field, $orderway)->select();
            foreach ($list as $k => $v) {
                $sour[] = $v[$prikey];
                $weighdata[$v[$prikey]] = $v[$field];
            }
            $position = array_search($changeid, $ids);
            $desc_id = $sour[$position];    //移动到目标的ID值,取出所处改变前位置的值
            $sour_id = $changeid;
            $desc_value = $weighdata[$desc_id];
            $sour_value = $weighdata[$sour_id];
            //echo "移动的ID:{$sour_id}\n";
            //echo "替换的ID:{$desc_id}\n";
            $weighids = array();
            $temp = array_values(array_diff_assoc($ids, $sour));
            foreach ($temp as $m => $n) {
                if ($n == $sour_id) {
                    $offset = $desc_id;
                } else {
                    if ($sour_id == $temp[0]) {
                        $offset = isset($temp[$m + 1]) ? $temp[$m + 1] : $sour_id;
                    } else {
                        $offset = isset($temp[$m - 1]) ? $temp[$m - 1] : $sour_id;
                    }
                }
                $weighids[$n] = $weighdata[$offset];
                Db::name($table)->where($prikey, $n)->update([$field => $weighdata[$offset]]);
            }
            $this->success();
        }
    }

    /**
     * 清空系统缓存
     */
    public function wipecache()
    {
        $type = $this->request->request("type");
        switch ($type) {
            case 'content' || 'all':
                rmdirs(CACHE_PATH, false);
                Cache::clear();
                if ($type == 'content')
                    break;
            case 'template' || 'all':
                rmdirs(TEMP_PATH, false);
                if ($type == 'template')
                    break;
            case 'addons' || 'all':
                Service::refresh();
                if ($type == 'addons')
                    break;
        }

        \think\Hook::listen("wipecache_after");
        $this->success();
    }

    /**
     * 读取分类数据,联动列表
     */
    public function category()
    {
        $type = $this->request->get('type');
        $pid = $this->request->get('pid');
        $where = ['status' => 'normal'];
        $categorylist = null;
        if ($pid !== '') {
            if ($type) {
                $where['type'] = $type;
            }
            if ($pid) {
                $where['pid'] = $pid;
            }

            $categorylist = Db::name('category')->where($where)->field('id as value,name')->order('weigh desc,id desc')->select();
        }
        $this->success('', null, $categorylist);
    }

    /**
     * 读取省市区数据,联动列表
     */
    public function area()
    {
        $province = $this->request->get('province');
        $city = $this->request->get('city');
        $where = ['pid' => 0, 'level' => 1];
        $provincelist = null;
        if ($province !== '') {
            if ($province) {
                $where['pid'] = $province;
                $where['level'] = 2;
            }
            if ($city !== '') {
                if ($city) {
                    $where['pid'] = $city;
                    $where['level'] = 3;
                }
                $provincelist = Db::name('area')->where($where)->field('id as value,name')->select();
            }
        }
        $this->success('', null, $provincelist);
    }

}
