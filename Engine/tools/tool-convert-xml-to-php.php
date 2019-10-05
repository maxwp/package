<?php

$fileXML = @$argv[1];
$filePHPResult = @$argv[2];

if (!$fileXML || !$filePHPResult) {
    print "No XML or PHP filenames.";
}

//$currentPath = dirname($fileXML).'/';
//$currentPath = '';
$currentPath = '__DIR__.\'/contents/';

$phpCode = '<?php';
$phpCode .= "\n";

$xml = simplexml_load_string(file_get_contents($fileXML));
foreach ($xml->content as $d) {
    if (trim($d->arguments.'')) {
        $args = explode(',', trim($d->arguments.''));
    } else {
        $args = array();
    }

    $id = trim($d['id'].'');

    // метод регистрации контента
    $register = trim($d['register'].'');
    if (!$register) {
        $register = 'override';
    }

    $role = trim($d->role.'');
    if ($role) {
        $role = explode(',', $role);
    } else {
        $role = array();
    }

    $urlsArray = array();
    foreach ($d->url as $url) {
        $urlsArray[] = trim($url.'');
    }
    if (count($urlsArray) == 1) {
        $urlsArray = $urlsArray[0];
    }

    $cssArray = array();
    foreach ($d->filecss as $css) {
        $css = trim($css.'');
        if (!$css) {
            continue;
        }

        $cssArray[] = $css;
    }

    if (isset($d->filecssremove)) {
        $removeCSS = true;
    } else {
        $removeCSS = false;
    }

    $jsArray = array();
    foreach ($d->filejs as $js) {
        $js = trim($js.'');
        if (!$js) {
            continue;
        }

        $jsArray[] = $js;
    }

    if (isset($d->filejsremove)) {
        $removeJS = true;
    } else {
        $removeJS = false;
    }

    $filephp = trim($d->filephp.'');
    if ($filephp) {
        $filephp = $filephp;
    }

    $filehtml = trim($d->filehtml.'');
    if ($filehtml) {
        $filehtml = $filehtml;
    }

    // массив ранее определенных значений для контента
    $valuesArray = array();
    foreach ((array)$d->values as $k => $v) {
        if (@$v->contentid) {
            $valuesArray[] = array(
            'key' => $k.'',
            'value' => $v->contentid.'',
            'type' => 'content',
            );
        } else {
            $valuesArray[] = array(
            'key' => $k.'',
            'value' => $v.'',
            'type' => 'string',
            );
        }
    }

    $cacheArray = @(array) $d->cache;

    if (!empty($cacheArray)) {
        if (!empty($cacheArray['ttl'])) {
            $ttl = $cacheArray['ttl'];
        } else {
            $ttl = 0;
        }

        if (!empty($cacheArray['type'])) {
            $type = $cacheArray['type'];
        } else {
            $type = false;
        }

        if (!empty($cacheArray['modifier'])) {
            $modifiersArray = $cacheArray['modifier'];
        } else {
            $modifiersArray = array();
        }

        if (!$modifiersArray) {
            $modifiersArray = array();
        } elseif (!is_array($modifiersArray)) {
            $modifiersArray = array($modifiersArray);
        }

        if (!$type) $type = 'content-url';

        if ($type == 'page') {
            // кешировать всю страницу полностью
            if (!$modifiersArray) {
                $modifiersArray[] = 'language';
                $modifiersArray[] = 'url';
                $modifiersArray[] = 'no-auth';
            }
        } elseif ($type == 'page-content') {
            // кешировать всю страницу полностью,
            // если есть юзер - то только контент (для всех)
            if (!$modifiersArray) {
                $modifiersArray[] = 'language';
                $modifiersArray[] = 'url';
            }
        } elseif ($type == 'content-url') {
            // контент в зависимости от URL
            $type = 'content';
            if (!$modifiersArray) {
                $modifiersArray[] = 'language';
                $modifiersArray[] = 'url';
            }
        } elseif ($type == 'content') {
            // контент
            if (!$modifiersArray) {
                $modifiersArray[] = 'language';
            }
        } else {
            throw new Engine_Exception("Unknown cache-type '{$type}'");
        }
        $cacheArray = array();
        $cacheArray['ttl'] = $ttl;
        $cacheArray['type'] = $type;
        $cacheArray['modifiers'] = $modifiersArray;
    }

    $fieldStringArray = array();

    $title = trim($d->title.'');
    if ($title) {
        $fieldStringArray[] = "'title' => '{$title}',";
    }

    if ($urlsArray) {
        if (!is_array($urlsArray)) {
            $fieldStringArray[] = "'url' => '{$urlsArray}',";
        } else {
            $tmp = array();
            foreach ($urlsArray as $x) {
                $tmp[] = "'{$x}'";
            }
            $fieldStringArray[] = "'url' => array(".implode(", ", $tmp)."),";
        }
    }

    if ($filehtml) {
        $fieldStringArray[] = "'filehtml' => {$currentPath}{$filehtml}',";
    }

    if ($filephp) {
        $fieldStringArray[] = "'filephp' => {$currentPath}{$filephp}',";
    }

    $class = trim($d->fileclass.'');
    if ($class) {
        $fieldStringArray[] = "'fileclass' => '{$class}',";
    }

    if ($cssArray) {
        if (count($cssArray) == 1) {
            $fieldStringArray[] = "'filecss' => {$currentPath}{$cssArray[0]}',";
        } else {
            $tmp = array();
            foreach ($cssArray as $x) {
                $tmp[] = "{$currentPath}{$x}'";
            }
            $fieldStringArray[] = "'filecss' => array(".implode(", ", $tmp)."),";
        }
    }

    if ($removeCSS) {
        $fieldStringArray[] = "'filecssremove' => true,";
    }

    if ($jsArray) {
        if (count($jsArray) == 1) {
            $fieldStringArray[] = "'filejs' => {$currentPath}{$jsArray[0]}',";
        } else {
            $tmp = array();
            foreach ($jsArray as $x) {
                $tmp[] = "{$currentPath}{$x}'";
            }
            $fieldStringArray[] = "'filejs' => array(".implode(", ", $tmp)."),";
        }
    }

    if ($removeJS) {
        $fieldStringArray[] = "'filejs' => true,";
    }

    $moveto = trim($d->moveto.'');
    $moveas = trim($d->moveas.'');

    if ($moveto) {
        $fieldStringArray[] = "'moveto' => '{$moveto}',";
    }
    if ($moveas) {
        $fieldStringArray[] = "'moveas' => '{$moveas}',";
    }

    $level = trim($d->level.'');
    if ($level) {
        $fieldStringArray[] = "'level' => '{$level}',";
    }

    if ($role) {
        $tmp = array();
        foreach ($role as $x) {
            $tmp[] = "'{$x}'";
        }
        $fieldStringArray[] = "'role' => array(".implode(", ", $tmp)."),";
    }

    if ($args) {
        $tmp = array();
        foreach ($args as $x) {
            $tmp[] = "'{$x}'";
        }
        $fieldStringArray[] = "'arguments' => array(".implode(", ", $tmp)."),";
    }

    if ($cacheArray) {
        $tmp = array();
        if ($cacheArray['ttl']) {
            $tmp[] = "'ttl' => ".$cacheArray['ttl']."";
        }
        if ($cacheArray['type']) {
            $tmp[] = "'type' => '".$cacheArray['type']."'";
        }
        if ($cacheArray['modifiers']) {
            $tmp2 = array();
            foreach ($cacheArray['modifiers'] as $x) {
                $tmp2[] = "'{$x}'";
            }
            $tmp[] = "'modifiers' => array(".implode(", ", $tmp2).")";
        }
        if ($tmp) {
            $fieldStringArray[] = "'cache' => array(".implode(", ", $tmp)."),";
        }
    }

    $phpCode .= 'Engine::GetContentDataSource()->registerContent(\''.$id.'\', array('."\n".implode("\n", $fieldStringArray)."\n".'), \''.$register.'\');';
    $phpCode .= "\n";
    $phpCode .= "\n";
}

print $phpCode;

file_put_contents($filePHPResult, $phpCode, LOCK_EX);

print "\n\ndone.\n\n";