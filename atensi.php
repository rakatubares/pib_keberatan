<?php
require __DIR__ . '/vendor/autoload.php';
require 'function/atensi_model.php';

$newData = new AtensiModel;
$data = $newData->SaveAtensi();

header('Content-type:application/json');
echo json_encode($data);
?>