<?php

/*
 * 追格商城小程序
 * Author: 追格
 * Help document: https://www.zhuige.com/product/sc.html
 * github: https://github.com/longwenjunjie/zhuige_shop
 * gitee: https://gitee.com/longwenjunj/zhuige_shop
 * License：GPL-2.0
 * Copyright © 2022 www.zhuige.com All rights reserved.
 */

//
// 商品页
//
CSF::createSection($prefix, array(
    'id' => 'goods',
    'title' => '商品设置',
    'icon'  => 'fas fa-shopping-bag',
    'fields' => array(

        array(
            'id'    => 'switch_comment',
            'type'  => 'switcher',
            'title' => '开启/停用',
            'subtitle' => '是否允许评论',
            'default' => '1'
        ),

        array(
            'id'    => 'switch_comment_verify',
            'type'  => 'switcher',
            'title' => '开启/停用',
            'subtitle' => '评论是否需要审核',
            'default' => '1'
        ),

    )
));
