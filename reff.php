<?php
require __DIR__ . '/vendor/autoload.php';
require 'function/reff_model.php';

$newData = new ReffModel;
$newData->InsertData();

?>