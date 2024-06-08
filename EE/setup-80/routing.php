<?php
ClassLoader::Get()->registerDirectory(__DIR__.'/content/');

EE::Get()->getRouting()->registerRoute('/', 'index');
EE::Get()->getRouting()->registerRoute('/page1/', 'page1');
EE::Get()->getRouting()->registerRoute('/page2/', 'page2');

