<?php
$content = file_get_contents('http://localhost:8000');
echo 'Content length: ' . strlen($content) . PHP_EOL;
echo 'Content: [' . $content . ']' . PHP_EOL;
echo 'Content (hex): ' . bin2hex($content) . PHP_EOL;
?>