<?php
function smarty_resource_string_source($tpl_name,&$tpl_source,&$smarty){
    $tpl_source = $tpl_name;
    return true;
}

function smarty_resource_string_timestamp($tpl_name,&$tpl_timestamp,&$smarty){
    $tpl_timestamp = $tpl_name;
    return true;
}

function smarty_resource_string_secure($tpl_name,&$smarty){
    return true;
}

function smarty_resource_string_trusted(){
    return;
}