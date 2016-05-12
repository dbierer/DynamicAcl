<?php
// recommended to run this from the command line
// for best results: start in your ZF2 project root directory
// looks for the "*/module/*/*/module.config.php" files

include 'ListControllersAndActions.php';
$list = new ListControllersAndActions();
echo json_encode($list->main());
