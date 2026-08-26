<?php

return [
    'required' => ':attribute为必填项。',
    'email' => ':attribute必须是有效的电子邮件地址。',
    'image' => ':attribute必须是图片。',
    'mimes' => ':attribute必须是以下类型的文件：:values。',
    'max' => ['string' => ':attribute不能超过:max个字符。', 'file' => ':attribute不能超过:max KB。', 'array' => ':attribute不能超过:max项。'],
    'integer' => ':attribute必须是整数。', 'numeric' => ':attribute必须是数字。',
    'url' => ':attribute必须是有效的网址。', 'in' => '所选的:attribute无效。',
    'regex' => ':attribute格式无效。',
    'attributes' => ['name' => '姓名', 'email' => '电子邮件', 'phone' => '电话', 'city' => '城市', 'bio' => '简介', 'language' => '语言', 'shop_name' => '店铺名称', 'contribution_title' => '投稿标题', 'field_name' => '字段', 'suggested_value' => '建议值', 'reason' => '原因', 'additional_information' => '补充信息'],
];