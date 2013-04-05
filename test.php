<?php

$data = unserialize(file_get_contents('D:\Temp\test 1\core\dokuwiki\lang\en.ser'));

header('Content-Type: text/plain; Charset=UTF-8');
print_r($data);

 
