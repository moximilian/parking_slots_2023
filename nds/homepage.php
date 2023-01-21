<?php
require("connect.php");
require("session.php");
/* При изменении радио кнопки о смене датасета меняется тип таблицы для всех последующих запросов*/
if (isset($_POST['myratio'])) {
    $radioVal = $_POST["myratio"];
    if ($radioVal == "closed") {
        $type = 'closedtype';
    } else if ($radioVal == "outside") {
        $type = 'outsidetype';
    }
} else {
    $type = 'closedtype';
}
/* когда срабатывает ссылка занять место парковочное, передается айди парковки из датасета с клиента*/
if (isset($_GET['id'])) {
    $sql1 = "SELECT `FreeSpaces`,`CountSpaces` FROM `$_GET[type]` WHERE`ID` = $_GET[id]";
    $sqlQuir = mysqli_query($connect, $sql1);
    $result = mysqli_fetch_assoc($sqlQuir);
    /* получается количество свободных и занятых мест с парковки */
    $freespaces = $result['FreeSpaces'];
    $allspaces = $result['CountSpaces'];
    // дальше два условия при добавлении или при удалении из базы данных занятого места
    if ($_GET['action'] == 'add') {
        $sql1 = "SELECT * FROM `users` WHERE `ID` = $session_user[ID]";
        $res = mysqli_query($connect, $sql1);
        $ress = mysqli_fetch_array($res);
        //проверка на то, что у пользователя еще нет занятого места в другой парковке
        if ($ress['Id_park'] == null || $ress['Id_park'] == 0) {
            //в таблице пользователя обновляется номер занятой парковки
            $sql1 = "UPDATE `users` SET `Id_park` = $_GET[id], `type_park` = '$_GET[type]' WHERE `ID` = $session_user[ID]";
            mysqli_query($connect, $sql1);
            if ($freespaces + 1 <= $allspaces) {
                $freespaces++;
            }
        }
        // в базе данных парковке увеличивается место на одно
        $sql1 = "UPDATE `$_GET[type]`SET `FreeSpaces` = $freespaces WHERE `ID` = $_GET[id]";
        mysqli_query($connect, $sql1);
    }
    if ($_GET['action'] == 'delete') {
        $sql1 = "SELECT * FROM `users` WHERE `ID` = $session_user[ID]";
        $res = mysqli_query($connect, $sql1);
        $ress = mysqli_fetch_array($res);
        //проверка на то, что пользватель действительно занимает место
        //а также номер освобождаемой парковки соответствует с номером парковки пользователя из базы данных
        if (($ress['Id_park'] != null || $ress['Id_park'] != 0) && $ress['Id_park'] == $_GET['id']) {
            $sql1 = "UPDATE `users` SET `Id_park` = 0, `type_park` = 'null' WHERE `ID` = $session_user[ID]";
            mysqli_query($connect, $sql1);
            if ($freespaces - 1 >= 0) {
                $freespaces--;
            }
        }
        // в базе данных парковке уменьшается место на одно
        $sql1 = "UPDATE `$_GET[type]`SET `FreeSpaces` = $freespaces WHERE `ID` = $_GET[id]";
        mysqli_query($connect, $sql1);
    }
    $type = $_GET['type'];
}
//дефолтный запрос на случай если ни один из фильтров не применен
$sql = "SELECT * FROM `$type` WHERE 1";


// если выбран ТОЛЬКО фильтр поиска по району улице или адресу срабатывает функция
if (isset($_POST['submit']) && isset($_POST['district'])) {
    $dis = htmlentities($_POST['district']);

    FilterByDistrict($dis);
}


//применяется запрос по содержанию ячейки информации из инпута
function FilterByDistrict($district)
{
    $GLOBALS['sql'] = "SELECT * FROM `$GLOBALS[type]` WHERE LOCATE('$district', `District`) OR  LOCATE('$district', `Address`)";
}

//если был применен только фильтр дистанции или еще применен фильтр по поиску района срабатывает другая функция
if (isset($_POST['distance'])) {
    $distance = htmlentities($_POST['distance']);
    FilterByDistance($distance);
}
//применяется запрос содержащий в себе три условие, одно из которых обязательно - дистанция и второе не обязательное - наличие адреса в поиске
//процесс нахождения дистанции от точки геолокации(установлена локально моя статичная геолокация)
//является по сути своей нахождением гипотенузы от двух катетов - координат.
//дистанция получается с погрешностью +- 500 метров потому что не учитывается искривление земли
function FilterByDistance($dist)
{
    $GLOBALS['sql'] = "SELECT * FROM `$GLOBALS[type]` WHERE SQRT(POWER(Abs(`Latitude_WGS84`-55.818837),2) + POWER(Abs(`Longitude_WGS84` -37.664653),2))*1507/0.208432 < ($dist/10)";
    if (isset($_POST['district']) && $_POST['district'] != "") {
        $GLOBALS['sql'] = "SELECT * FROM `$GLOBALS[type]` WHERE SQRT(POWER(Abs(`Latitude_WGS84`-55.818837),2) + POWER(Abs(`Longitude_WGS84` -37.664653),2))*1507/0.208432 < ($dist/10) AND ( LOCATE('$_POST[district]', `District`) OR LOCATE('$_POST[district]', `Address`)) ";
        return;
    }
}

$result = mysqli_query($connect, $sql);
$welcoming = "";
$showplace = "";
//в случае если итоговый запрос с примененым фильтрами не получил ни одной строки, выводится следующее сообщение о некорректности введенных данных
if (mysqli_affected_rows($connect) == 0) {
    $result = mysqli_query($connect, "SELECT * FROM `$type` WHERE 1");
    $showplace = '
        <div class="showplace">
            <div class = "frame46617">
                <img src="pics/place.svg" id="place">
                <div class="litering">
                    Некорректно введены данные
                </div>
            </div>
        </div>
        ';
}
//в случае если пользователь пытается принудительно перейти на главную страницу без авторизации - будет выброшено сообщение о необходимости авторизоваться

if (empty($session_user)) {
    $result = mysqli_query($connect, "SELECT * FROM `$type` WHERE 1");
    $welcoming =   '<div class = "frame29">
                        <div class = "frame25"> Необходимо авторизоваться</div>
                        <a href = "auth.php">  
                            <button class = "button_exit"> 
                                Авторизуйтесь 
                            </button>
                        </a>
                    </div>';
    //иначе его будет встречать приветствие 
} else {
    $welcoming =   '<div class = "frame29">
                        <div class = "frame25"> Добро пожаловать, <div class ="bold">' . $session_user['Name'] . '</div></div>
                        <a href = "quit.php">  
                            <button class = "button_exit"> 
                                Выйти 
                            </button>
                        </a>
                    </div>';
}
//массив хранящий адреса полученных паковок
$address = [];
//процесс получения массивов всех данных о парковках для вывода их на карту
while ($row = mysqli_fetch_array($result)) {
    $coords[] = $row['Coordinates'];
    array_push($address, $row['Address']);
    $countAll[] = $row['CountSpaces'];
    $countFree[] = $row['FreeSpaces'];
    $names[] = $row['Name'];
    $ids[] = $row['ID'];
}
//если пользователь авторизирован будет запущен процесс нахождения его занятой парковки для 
//отображения её на карте красным цветом
if (!empty($session_user)) {
    $sql1 = "SELECT * FROM `users` WHERE `ID` = $session_user[ID]";
    $resultt = mysqli_query($connect, $sql1);
    $resulting = mysqli_fetch_array($resultt);
    //айди парковки, необходимой окрасить в красный
    $id_parking_red = $resulting['Id_park'];
    //запрос для получения информации об этой парковки
    $sql1 = "SELECT $GLOBALS[type].ID as ID, $GLOBALS[type].Name as Name, $GLOBALS[type].Address as Address FROM `$GLOBALS[type]` JOIN `users` on $GLOBALS[type].ID = users.Id_park WHERE users.ID = $session_user[ID]";
    $resultt = mysqli_query($connect, $sql1);
    $resulting = mysqli_fetch_array($resultt);
    //если парковка найдена в базе данных, то выводится уведомление с адресом этой парковки
    if (mysqli_num_rows($resultt) == 1) {
        $showplace = '
        <div class="showplace">
            <div class = "frame46617">
                <img src="pics/place.svg" id="place">
                <div class="litering">
                    Ваше место на парковке по адресу <div class="bold">' . $resulting['Address'] . '</div>
                </div>
            </div>
        </div>
        ';
    }
}


?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Загруженность парковок</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://api-maps.yandex.ru/2.1/?apikey=2904d8f3-a8c6-46b4-b9fe-15df60fa36b8&lang=ru_RU" type="text/javascript">
    </script>
    <script type="text/javascript">
        // Функция ymaps.ready() будет вызвана, когда
        // загрузятся все компоненты API, а также когда будет готово DOM-дерево.
        ymaps.ready(init);

        function init() {
            // Создание карты.
            var geolocation = ymaps.geolocation;
            coordination = [55.818837, 37.664653];
            var myMap = new ymaps.Map("map", {
                // Координаты центра карты.
                // Порядок по умолчанию: «широта, долгота».
                // Чтобы не определять координаты центра карты вручную,
                // воспользуйтесь инструментом Определение координат.
                center: [55.818837, 37.664653],
                // Уровень масштабирования. Допустимые значения:
                // от 0 (весь мир) до 19.
                zoom: 12
            });

            // координаты

            myMap.geoObjects.add(
                new ymaps.Placemark(
                    coordination, {
                        // В балуне: страна, город, регион.
                        strokeColor: "ff0000",
                        balloonContentHeader: 'Россия',
                        balloonContent: 'г.Москва',
                        balloonContentFooter: 'Рижский проезд 15к1'
                    }, {
                        preset: 'islands#circleIcon',
                        iconColor: '#ff0000'
                    }
                )
            );
            //процесс получения данных из базы данных в клиент пользователя через json_encode
            var coords = <?= json_encode($coords) ?>;
            var address = <?= json_encode($address) ?>;
            var countAll = <?= json_encode($countAll) ?>;
            var countFree = <?= json_encode($countFree) ?>;
            var names = <?= json_encode($names) ?>;
            var ids = <?= json_encode($ids) ?>;
            var id_parking_red = <?= json_encode($id_parking_red) ?>;
            var pointsL = [];
            coords.forEach(addDot);

            function addDot(item) {
                let newitem = item.split(',');
                var numarray = [];
                let length = newitem.length;
                for (var i = 0; i < length; i++) {
                    numarray.push(parseFloat(newitem[i]));
                }
                pointsL.push(numarray);
            }
            clusterer = new ymaps.Clusterer({
                    preset: 'twirl#invertedVioletClusterIcons',
                    groupByCoordinates: false,
                    clusterDisableClickZoom: true
                }),
                getInfo = function(idx, idx_red) {
                    if (idx == idx_red) {
                        return '<h4>Вы заняли эту парковку</h4><br>';
                    } else return '';
                },
                //в балун каждой парковки выводитя информация об этой парковки из бд, а так же две ссылки на добовление или удаление занятого места
                getPointData = function(index) {
                    document.cookie = "index = " + index;
                    return {
                        balloonContentBody: '<h1>' + names[index] + '</h1><br>Адресс: <strong>' + address[index] +
                            '</strong><br> <h3>Свободно <strong>' + (countAll[index] - countFree[index]) +
                            '/' + countAll[index] + '</h3></strong><br>' + getInfo(ids[index], id_parking_red) + '<a href="homepage.php?id=' + (ids[index] ) + '&action=add&type=<?php echo $GLOBALS['type'] ?>"> Занять место </a><br><a href="homepage.php?id=' + (ids[index] ) + '&action=delete&type=<?php echo $GLOBALS['type'] ?>"> Освободить место </a>',
                        clusterCaption: '<strong>' + names[index] + '</strong>'
                    };
                },
                // при отрисовке в случае если какая-то парковка является той парковкой, занятой пользователем - её цвет обращается в красный
                getcolor = function(id_red, id_user) {
                    if (parseInt(id_user) == parseInt(id_red)) {
                        return '#ff0000';
                    } else {
                        return '#00bfff';
                    }
                },
                getPointOptions = function(index) {

                    return {
                        preset: 'twirl#violetIcon',
                        iconColor: getcolor(id_parking_red, index)
                    };
                },
                points = pointsL,
                geoObjects = [];
            for (var i = 0, len = pointsL.length; i < len; i++) {

                geoObjects[i] = new ymaps.Placemark(pointsL[i], getPointData(i), getPointOptions(ids[i]));

            }
            // кластеризация множетсва точек на карте
            /**
             * Так же можно менять опции кластеризатора.
             */
            clusterer.options.set({
                gridSize: 80,
                clusterDisableClickZoom: true
            });

            clusterer.add(geoObjects);

            /**
             * Поскольку кластеры добавляются асинхронно,
             * дождемся их добавления, чтобы выставить карте область, которую они занимают.
             * Используем метод once чтобы сделать это один раз.
             */
            clusterer.events.once('objectsaddtomap', function() {
                myMap.setBounds(clusterer.getBounds());
            });

            /**
             * Кластеризатор, расширяет коллекцию, что позволяет использовать один обработчик
             * для обработки событий всех геообъектов.
             * Выведем текущий геообъект, на который навели курсор, поверх остальных.
             */
            clusterer.events
                // Можно слушать сразу несколько событий, указывая их имена в массиве.
                .add(['mouseenter', 'mouseleave'], function(e) {
                    var target = e.get('target'), // Геообъект - источник события.
                        eType = e.get('type'), // Тип события.
                        zIndex = Number(eType === 'mouseenter') * 1000; // 1000 или 0 в зависимости от типа события.

                    target.options.set('zIndex', zIndex);
                });

            /**
             * После добавления массива геообъектов в кластеризатор,
             * работать с геообъектами можно, имея ссылку на этот массив.
             */
            clusterer.events.add('objectsaddtomap', function() {
                for (var i = 0, len = geoObjects.length; i < len; i++) {
                    var geoObject = geoObjects[i],
                        /**
                         * Информацию о текущем состоянии геообъекта, добавленного в кластеризатор,
                         * а также ссылку на кластер, в который добавлен геообъект, можно получить с помощью метода getObjectState.
                         * @see http://api.yandex.ru/maps/doc/jsapi/2.x/ref/reference/Clusterer.xml#getObjectState
                         */
                        geoObjectState = clusterer.getObjectState(geoObject),
                        // признак, указывающий, находится ли объект в видимой области карты
                        isShown = geoObjectState.isShown,
                        // признак, указывающий, попал ли объект в состав кластера
                        isClustered = geoObjectState.isClustered,
                        // ссылка на кластер, в который добавлен объект
                        cluster = geoObjectState.cluster;

                    if (window.console) {
                        console.log('Геообъект: %s, находится в видимой области карты: %s, в составе кластера: %s', i, isShown, isClustered);
                    }
                }
            });

            myMap.geoObjects.add(clusterer);
        }
    </script>
</head>

<body>
    <!-- верстка страницы  -->
    <header>
        <img src='pics/logo.svg' alt='наш логотип'>
        <?php echo $welcoming ?>
    </header>
    <main>
        <?php if ($showplace != "")
            echo $showplace;
        ?>
        <div <?php if ($showplace != "")
                    echo 'id="filter2"';
                else
                    echo 'id="filter"';  ?>>
            <form action="homepage.php" method="POST" name="my">
                <div class="frame46619">
                    <div class='frames3'>
                        <div class='texted'>Введите район, адрес или улицу парковки</div>
                        <input type="text" name="district" id="district" <?php if (isset($_POST['district'])) echo 'value = "' . $_POST['district'] . '"'; ?>></input>
                    </div>
                    <div class='parkings'>
                        <div class="texteds">Тип парковки</div>
                        <div class='frame34'>
                            <div class='option1'>
                                <input type="radio" id="closed" name="myratio" value="closed" <?php if (isset($_POST['myratio']) && $_POST['myratio'] == 'closed') echo ' checked="checked"'; ?>>
                                <div class='lebeles'><label for="closed">Крытая</label></div>
                            </div>
                            <div class='option2'>
                                <input type="radio" id="outside" name="myratio" value="outside" <?php if (isset($_POST['myratio']) && $_POST['myratio'] == 'outside') echo ' checked="checked"'; ?>>
                                <div class='lebeles'><label for="outside">На улице</label></div>
                            </div>
                        </div>
                    </div>
                    <div class='frame46612'>
                        <div class='distance'>Расстояние, м </div>
                        <div class='frame46602'>
                            <input type="range" min="0" max="25000" step="100" <?php if (isset($_POST['distance'])) echo 'value = "' . $_POST['distance'] . '"'; ?>id="inputt" name="distance">
                            <output id="value"></output>
                        </div>
                    </div>
                    <script>
                        const value = document.querySelector("#value");
                        const input = document.querySelector("#inputt");
                        value.textContent = input.value;
                        input.addEventListener("input", (event) => {
                            value.textContent = event.target.value;
                        });
                    </script>
                </div>
                <input id='buttonfind' type="submit" name="submit" value="Показать парковочные места"></input>
            </form>
        </div>
        <div id="map"></div>
    </main>
    <footer <?php if ($showplace != "")
            echo 'id="footer2"';
        else
            echo 'id="footer1"';  ?>>
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
</body>
</html>