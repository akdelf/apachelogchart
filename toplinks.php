#!/usr/local/bin/php
<?php

require 'aplogparser.php';

$logparser = new aplogparser('/var/log/apache2/access_log');
$logparser->db('arg_static', 'root', 'MyPass1');

$logparser->bestpath();