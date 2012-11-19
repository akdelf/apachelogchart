#!/usr/local/bin/php
<?php

require '../aplogparser.php';

# если проблемы с timezone добавляем эту строчку
date_default_timezone_set("Europe/Moscow");

# указываем путь до файла лога и параметры БД
$logparser = new aplogparser('/var/log/apache2/access.log.1');
$logparser->db('arg_static', 'root', 'MyPass1', 'localhost', 'mysql');

#получаем данные за указанное время
//$logparser->topparser(220);


/** 
* формируем страницу с текущими результатами
* - указываем файл шаблона (по умолчанию view/toplinks.phtml)
* - указываем файл в который аположить результат (по умолчанию pub/toplinks.html)
* - указываем кол-во элементов топа которыке хотим видеть (по умолчанию 20)
*/
$logparser->render(20, 'view/toplinks.phtml', 'pub/toplinks.html');
