<?php 
$connect = mysqli_connect('std-mysql.ist.mospolytech.ru:3306', 'std_2101_parkings2023', '123123123', 'std_2101_parkings2023');
//$connect = mysqli_connect('localhost', 'root', 'root', 'parkings');
if($connect->connect_errno) exit('Ошибка подключения к БД');
?>
