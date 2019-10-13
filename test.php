<?php
require __DIR__ . '/vendor/autoload.php';

use voku\helper\StopWords;
$stopWords = new StopWords();
$words = $stopWords->getStopWordsFromLanguage('id');
var_dump($words);
?>