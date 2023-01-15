<?php

$fieldArray = [];
$fieldArray['key1'] = 'value';
$fieldArray['key2'] = 'value';
SQLBuilder::Get()->insert('table', $fieldArray);

$whereArray = [];
$whereArray['key1'] = 'value';
$whereArray['key2'] = 'value';
$limit = 1;
SQLBuilder::Get()->delete('table', $whereArray, $limit);

SQLBuilder::Get()->update('table', $fieldArray, $whereArray, $limit);