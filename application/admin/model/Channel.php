<?php

namespace app\admin\model;

use think\Model;

class Channel extends Model
{
    // ����
    protected $name = 'spread_channel';
    
    // �Զ�д��ʱ����ֶ�
    protected $autoWriteTimestamp = 'int';

    // ����ʱ����ֶ���
    protected $createTime = 'createtime';
    //protected $updateTime = 'updatetime';
    
    // ׷������
    protected $append = [

    ];
    
}
