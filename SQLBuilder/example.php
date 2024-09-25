<?php

$mysql = Connection::Get('mysql');

$fieldArray = [];
$fieldArray['key1'] = 'value';
$fieldArray['key2'] = 'value';
SQLBuilder::Get($mysql)->insert('table', $fieldArray);

$whereArray = [];
$whereArray['key1'] = 'value';
$whereArray['key2'] = 'value';
$limit = 1;
SQLBuilder::Get($mysql)->delete('table', $whereArray, $limit);

SQLBuilder::Get($mysql)->update('table', $fieldArray, $whereArray, $limit);


// v2
$query = new SQLBuilder_Select($mysql, 'table');
$query->addField('*');
$query->removeField('*');
$query->addField('key2');
$query->addField('key1', false); // non-escape
$query->addField(['key1', 'key2']);
$query->addWhere('key', 'value');
$query->addWhere("key", "> 100", false); // no escape
$query->setOrderBy("key ASC");
$query->setLimit(100);
$query->setLimit(0, 100);
$query->make(); // в строку
$query->execute(); // вызвать и вернуть результат
// можно передать в начало в конструктор, сделать типа фабрики
// $query = SQLBuilder::Select($mysql);
// $query = SQLBuilder::Get($mysql)->select(); в таком случае в конструктор select'a передастся то что надо и там уже будет указатель на mysql
// и тогда можно делать toString, а можно execute чтобы вызвать что нужно
// а может new SQLBuilder_Select($mysql, ...) - ведь оно не хуже?
