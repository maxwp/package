<?php
function smarty_function_pricetotext($params) {
    return StringUtils_Converter::FloatToMoney($params['price'], $params['lang']);
}