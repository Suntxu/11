<?php

//配置文件
return [
    'url_common_param'       => true,
    'url_html_suffix'        => '',
    'controller_auto_search' => true,
    'reserve_cost' => [ //预定价格设置
        '.com' => 38,
        '.cn' => 12,
    ],
    'task_split_date' => '2019-12-31 09:30:00', //任务表历史查询时间线
    'self_username' => [   //自己的用户
    	'97977130@qq.com',
    	'97977131@qq.com',
    	'97977132@qq.com',
    	'97977133@qq.com',
    	'201055555@qq.com',
    	'201155555@qq.com',
    	'201755555@qq.com',
    	'shop@huaimi.com',
    	'cncom@qq.com',
    	'13346282198@163.com',
        'dhdlggg90@hotmail.com',
        'twzhuanyong@163.com',
        'hs@huaimi.com',
        'huaimiwangluo@huaimi.com',
        'jingjiazhuanyong@huaimi.com',
        'jingjiaweiyue@huaimi.com',
    ],
    'self_marketing' => [    //自己的推广大使账户
        '97997716@qq.com' => '刘晓杨',
        '97997726@qq.com' => '刘梦芸',
        '97997718@qq.com' => '徐婧雪',
        '642463049@qq.com' => '怀俊晨',
        '97997706@qq.com' => '顾述涛',
        '97997705@qq.com' => '丁敏敏',
        '97997701@qq.com' => '张金雷',
        '1064667777@qq.com' => '怀风敏',
        '97887732@qq.com' => '朱文娇',
        '747008466@qq.com' => '张德华',
        '97887750@qq.com' => '王鑫',
        '3225627273@qq.com' => '杨杨',
        '1670197915@qq.com' => '朱昭阳',
    ],

    //多通道域名预定注册商id
    'mult_domain_reserve_zcs_id' => [
        66 => ['name' => '阿里云','api' => [26 => 'bohai5839'] ],
    ],
    
    //外部注册商
    'out_register' => [
        1 => 'godaddy','namebright','netcn','nsi','xb','xw','22cn','dy','zgsj','bizcn',
        'nh','rb','pheenix','maff','moniker','enom','cndns','mydomain','encirca','zzy',
        'aligj','hexonet','nawang','namesilo','17ex','reg','gathernames','66cn','nwhk','gname',
        'ename','35hl',
        106 => 'gd'
    ],

    //域名转入 失败选项
    'domain_shift_fail_select' => [
        '提交域名数量或域名列表不一致，执行失败！',
        '原注册商未收到转入域名列表，请确认后重新提交！',
        '转入域名列表包含“被墙，hold”状态域名，执行失败！',
    ],

    //域名转回 失败选项
    'domain_into_fail_select' => [
        '转回目标账号未绑定实名支付宝，执行失败！',
        '转回目标账号不存在，请提供有效账号再次提交转回！',
        '转回域名列表中包括“过户中”状态域名，无法操作转回！',
        '转回注册商需要提供账号和ID双重认证才可转回！',
    ],

    'domain_dctype' => [ //域名细分类
        'sz3sz' => ['$AAA$' => 'AAA', '$AAB$' => 'AAB', '$ABB$' => 'ABB', '$ABA$' => 'ABA'],
        'sz4sz' => ['$AAAA$' => 'AAAA', '$AAAB$' => 'AAAB', '$ABBB$' => 'ABBB', '$AABB$' => 'AABB', '$ABAB$' => 'ABAB', '$ABBA$' => 'ABBA', '$AABA$' => 'AABA', '$ABAA$' => 'ABAA', '$AREA$' => '电话区号'],
        'sz5sz' => [
            '$AAAAA$' => 'AAAAA', '$AAAAB$' => 'AAAAB', '$ABBBB$' => 'ABBBB', '$AAABB$' => 'AAABB', '$AABBB$' => 'AABBB', '$AABBA$' => 'AABBA', '$ABBAA$' => 'ABBAA', '$AABAA$' => 'AABAA',
            '$ABBBA$' => 'ABBBA', '$ABABA$' => 'ABABA', '$AAABA$' => 'AAABA', '$ABAAA$' => 'ABAAA', '$AABAB$' => 'AABAB', '$ABAAB$' => 'ABAAB', '$ABABB$' => 'ABABB', '$ABBAB$' => 'ABBAB',
            '$ABCCC$' => 'ABCCC', '$AAABC$' => 'AAABC', '$AABBC$' => 'AABBC', '$ABBCC$' => 'ABBCC', '$AABCC$' => 'AABCC', '$ABABC$' => 'ABABC', '$ABCBC$' => 'ABCBC',
            '$ABCBA$' => 'ABCBA', '$ABBBC$' => 'ABBBC', '$AABAC$' => 'AABAC', '$AABCA$' => 'AABCA', '$AABCB$' => 'AABCB', '$ABACC$' => 'ABACC', '$ABCAA$' => 'ABCAA',
            '$ABCBB$' => 'ABCBB', '$ABAAC$' => 'ABAAC', '$ABBAC$' => 'ABBAC', '$ABBCA$' => 'ABBCA', '$ABBCB$' => 'ABBCB', '$ABCCA$' => 'ABCCA', '$ABCCB$' => 'ABCCB',
            '$ABACA$' => 'ABACA', '$ABACB$' => 'ABACB', '$ABCAB$' => 'ABCAB', '$ABCAC$' => 'ABCAC',
        ],
        'sz6sz' => [
            '$POST$' => '邮编', '$AAAAAA$' => 'AAAAAA', '$AAAAAB$' => 'AAAAAB', '$ABBBBB$' => 'ABBBBB', '$AAAABB$' => 'AAAABB', '$AABBBB$' => 'AABBBB', '$ABBBBA$' => 'ABBBBA',
            '$ABAAAA$' => 'ABAAAA', '$AAAABA$' => 'AAAABA', '$AAABBB$' => 'AAABBB', '$AABBAA$' => 'AABBAA', '$AABAAB$' => 'AABAAB', '$ABBABB$' => 'ABBABB', '$ABAABA$' => 'ABAABA',
            '$ABABAB$' => 'ABABAB', '$AAABAA$' => 'AAABAA', '$AAABAB$' => 'AAABAB', '$AAABBA$' => 'AAABBA', '$AABAAA$' => 'AABAAA', '$AABBBA$' => 'AABBBA', '$ABAAAB$' => 'ABAAAB',
            '$ABABBB$' => 'ABABBB', '$ABBAAA$' => 'ABBAAA', '$ABBBAA$' => 'ABBBAA', '$ABBBAB$' => 'ABBBAB', '$AABABA$' => 'AABABA', '$AABABB$' => 'AABABB', '$AABBAB$' => 'AABBAB',
            '$ABAABB$' => 'ABAABB', '$ABABAA$' => 'ABABAA', '$ABABBA$' => 'ABABBA', '$ABBAAB$' => 'ABBAAB', '$ABBABA$' => 'ABBABA', '$AAAABC$' => 'AAAABC', '$ABBBBC$' => 'ABBBBC',
            '$ABCCCC$' => 'ABCCCC', '$AAABBC$' => 'AAABBC', '$AABBBC$' => 'AABBBC', '$ABBCCC$' => 'ABBCCC', '$AABBCC$' => 'AABBCC', '$ABCCBA$' => 'ABCCBA', '$ABCABC$' => 'ABCABC',
        ],
        'zm3zm' => ['#AAA#' => 'AAA', '#AAB#' => 'AAB', '#ABB#' => 'ABB', '#ABA#' => 'ABA'],
        'zm4zm' => ['#AAAA#' => 'AAAA', '#AAAB#' => 'AAAB', '#ABBB#' => 'ABBB', '#AABB#' => 'AABB', '#ABAB#' => 'ABAB', '#ABBA#' => 'ABBA', '#AABA#' => 'AABA', '#ABAA#' => 'ABAA'],
        'zm5zm' => [
            '#AAAAA#' => 'AAAAA', '#AAAAB#' => 'AAAAB', '#ABBBB#' => 'ABBBB', '#AAABB#' => 'AAABB', '#AABBB#' => 'AABBB', '#AABBA#' => 'AABBA', '#ABBAA#' => 'ABBAA', '#AABAA#' => 'AABAA',
            '#ABBBA#' => 'ABBBA', '#ABABA#' => 'ABABA', '#AAABA#' => 'AAABA', '#ABAAA#' => 'ABAAA', '#AABAB#' => 'AABAB', '#ABAAB#' => 'ABAAB', '#ABABB#' => 'ABABB', '#ABBAB#' => 'ABBAB',
            '#ABCCC#' => 'ABCCC', '#AAABC#' => 'AAABC', '#AABBC#' => 'AABBC', '#ABBCC#' => 'ABBCC', '#AABCC#' => 'AABCC', '#ABABC#' => 'ABABC', '#ABCBC#' => 'ABCBC',
            '#ABCBA#' => 'ABCBA', '#ABBBC#' => 'ABBBC', '#AABAC#' => 'AABAC', '#AABCA#' => 'AABCA', '#AABCB#' => 'AABCB', '#ABACC#' => 'ABACC', '#ABCAA#' => 'ABCAA',
            '#ABCBB#' => 'ABCBB', '#ABAAC#' => 'ABAAC', '#ABBAC#' => 'ABBAC', '#ABBCA#' => 'ABBCA', '#ABBCB#' => 'ABBCB', '#ABCCA#' => 'ABCCA', '#ABCCB#' => 'ABCCB',
            '#ABACA#' => 'ABACA', '#ABACB#' => 'ABACB', '#ABCAB#' => 'ABCAB', '#ABCAC#' => 'ABCAC',
        ],
        'zm6zm' => [
            '#AAAAAA#' => 'AAAAAA', '#AAAAAB#' => 'AAAAAB', '#ABBBBB#' => 'ABBBBB', '#AAAABB#' => 'AAAABB', '#AABBBB#' => 'AABBBB', '#ABBBBA#' => 'ABBBBA', '#ABAAAA#' => 'ABAAAA',
            '#AAAABA#' => 'AAAABA', '#AAABBB#' => 'AAABBB', '#AABBAA#' => 'AABBAA', '#AABAAB#' => 'AABAAB', '#ABBABB#' => 'ABBABB', '#ABAABA#' => 'ABAABA', '#ABABAB#' => 'ABABAB',
            '#AAABAA#' => 'AAABAA', '#AAABAB#' => 'AAABAB', '#AAABBA#' => 'AAABBA', '#AABAAA#' => 'AABAAA', '#AABBBA#' => 'AABBBA', '#ABAAAB#' => 'ABAAAB', '#ABABBB#' => 'ABABBB',
            '#ABBAAA#' => 'ABBAAA', '#ABBBAA#' => 'ABBBAA', '#ABBBAB#' => 'ABBBAB', '#AABABA#' => 'AABABA', '#AABABB#' => 'AABABB', '#AABBAB#' => 'AABBAB', '#ABAABB#' => 'ABAABB',
            '#ABABAA#' => 'ABABAA', '#ABABBA#' => 'ABABBA', '#ABBAAB#' => 'ABBAAB', '#ABBABA#' => 'ABBABA', '#AAAABC#' => 'AAAABC', '#ABBBBC#' => 'ABBBBC', '#ABCCCC#' => 'ABCCCC',
            '#AAABBC#' => 'AAABBC', '#AABBBC#' => 'AABBBC', '#ABBCCC#' => 'ABBCCC', '#AABBCC#' => 'AABBCC', '#ABCCBA#' => 'ABCCBA', '#ABCABC#' => 'ABCABC',
        ],
        'za3za' => ['*NNL*' => 'NNL', '*LNN*' => 'LNN', '*LLN*' => 'LLN', '*NLL*' => 'NLL', '*LNL*' => 'LNL', '*NLN*' => 'NLN'],
        'za4za' => ['*NNLL*' => 'NNLL', '*LLNN*' => 'LLNN', '*NNNL*' => 'NNNL', '*LLLN*' => 'LLLN'],
    ],
];
