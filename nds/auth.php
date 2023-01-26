<?php
require("connect.php");
if (!empty($_POST)) {
    $pass = $_POST['password'];
    $login = $_POST['login'];
    $result = mysqli_query($connect, "SELECT * FROM users WHERE
    `login`='$login' AND
    `password`= '$pass'
");


    if (!$result || mysqli_num_rows($result) == 0) {
        echo "Такой пользователь не существует.";
        exit;
    }

    session_start();
    $_SESSION["user"] = mysqli_fetch_assoc($result);
    header("Location: homepage.php");
}
$content = "";
$content.= "
<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta http-equiv='X-UA-Compatible' content='IE=edge'>
    <link rel='preconnect' href='https://fonts.googleapis.com'>
    <link rel='preconnect' href='https://fonts.gstatic.com' crossorigin>
    <link href='https://fonts.googleapis.com/css2?family=Oswald:wght@200&display=swap' rel='stylesheet'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <link rel='stylesheet' href='style.css'>
    <title>Вход</title>
</head>
<body>
<header>
 <img src = 'pics/logo.svg' alt='наш логотип'>
</header>
<main>
<div class='formstyle'>
    <form method='POST'>
        <div class='labels1'>
            Вход
        </div>
        <div class='frame'>
            <div class='fram4'>
                <label>Почта (логин)</label>
                <input class='inputforms' type='text' name='login'>
            </div>
            <div class='fram45'>
                <div class='fram5'>
                    <label>Пароль</label>
                    <input class='inputforms' type='password' name='password'>
                </div>
                <div class='butons'>
                    <button type='submit'>Войти</button>
                    <div class = 'text'> Нет аккаунта? </div > <a class = 'linked' href = 'register.php'>Зарегистрироваться</a>
                </div>
            </div>
        </div>
    </form>
</div>
<footer id='footer1'>
        <div class='frame46624'>
            <div class='frame1419'>
                <div class='parking777'>Парковка777</div>
                <a id='link1' href='https://data.mos.ru/opendata/7704786030-platnye-parkovki-zakrytogo-tipa?pageNumber=1&versionNumber=4&releaseNumber=30'>Платные парковки закрытого типа</a>
                <a id='link2' href='https://data.mos.ru/opendata/7704786030-platnye-parkovki-na-ulichno-dorojnoy-seti'>Платные парковки на улично-дорожной сети</a>
            </div>

        </div>
        <hr>
        <div class='frame46625'>
            <div class='maxim'>
                2023 Maxim Syrov
            </div>
            <div class='frame1438'>
                <a href='https://t.me/moximmilian'><img id='#telegram' src='pics/Telegram.svg'></a>
                <a href='https://vk.com/moximillian'><img id=' #vk' src='pics/vk.svg'></a>
                <a href='https://github.com/moximilian/parking_slots_2023'><img id=' #git' src='pics/Git-logo.svg'></a>
            </div>

        </div>
    </footer>
</main>
</body>
</html>
";
echo $content;
?>