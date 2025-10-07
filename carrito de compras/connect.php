<?php

$db_name = 'mysql:host=localhost;dbname=shop_db';
$db_user_name = 'root';
$db_user_pass = '';

$conn = new PDO($db_name, $db_user_name, $db_user_pass);

function create_unique_id(){
    $sets = ['abcdefghijklmnopqrstuvwxyz', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', '1234567890'];
    $all = implode('', $sets);
    $id = $sets[0][random_int(0, 25)]
        . $sets[1][random_int(0, 25)]
        . $sets[2][random_int(0, 9)];
    for ($i = 0; $i < 17; $i++) $id .= $all[random_int(0, strlen($all) - 1)];
    return str_shuffle($id);
}

?>