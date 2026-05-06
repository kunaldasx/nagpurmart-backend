<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => '这些凭据与我们的记录不符。',
    'password' => '提供的密码不正确。',
    'throttle' => '登录尝试次数过多。请在 :seconds 秒后重试。',
    'verify_store_info' => '要验证商店，请点击商店表格中的眼睛按钮',
    'quantity_step_size_gte_minimum_order_quantity' => '数量步长必须大于或等于最小订单数量。',
    'quantity_step_size_lte_total_allowed_quantity' => '数量步长必须小于或等于总允许数量。',
    'minimum_order_quantity_lte_total_allowed_quantity' => '最小订单数量必须小于或等于总允许数量。',
    'google_api_key_not_found' => '未找到 Google API 密钥。请从设置 > 认证 > Google API 密钥添加',
    'created_successfully' => '配送区域创建成功。',
    'creation_error' => '创建配送区域时发生错误。',
    'invalid_boundary_json' => '无效的边界 JSON 格式。',
    'internal_server_error' => '内部服务器错误',

    // General Messages
    'something_went_wrong' => '出了些问题。请重试',
    'invalid_coordinates' => '无效的坐标！请选择有效的地址',
    'required' => '此字段是必需的。',
    'integer' => '此字段必须是整数。',
    'string' => '此字段必须是字符串。',
    'max' => '此字段超过了最大允许长度。',
    'min' => '此字段低于最小允许值。',

    // Cart Messages
    'cart_is_empty' => '您的购物车是空的',
    'cart_item_not_found' => '未找到购物车商品',
    'item_added_to_cart_successfully' => '商品已成功添加到购物车',
    'item_removed_from_cart_successfully' => '商品已成功从购物车中移除',

    // Order Messages
    'order_created_successfully' => '订单创建成功',
    'order_not_found' => '未找到订单',
    'order_retrieved_successfully' => '订单检索成功',
    'orders_retrieved_successfully' => '订单检索成功',

    // Language Names
    'languages' => [
        'english' => '英语',
        'spanish' => '西班牙语',
        'french' => '法语',
        'german' => '德语',
        'chinese' => '中文',
    ],
];
