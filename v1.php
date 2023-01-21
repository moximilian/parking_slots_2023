<?php
require("connect.php");
require("session.php");
/*function GetCoordClosedPark($connect)
{*/
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
//echo $type;
if (isset($_GET['id'])) {
    $sql1 = "SELECT `FreeSpaces`,`CountSpaces` FROM `$_GET[type]` WHERE`ID` = $_GET[id]";
    echo $sql1;
    $sqlQuir = mysqli_query($connect, $sql1);
    //echo mysqli_affected_rows($connect);
    $result = mysqli_fetch_assoc($sqlQuir);
    $freespaces = $result['FreeSpaces'];
    $allspaces = $result['CountSpaces'];
    if ($_GET['action'] == 'add') {
        if ($freespaces + 1 <= $allspaces) {
            $freespaces++;
        }
        $sql1 = "UPDATE `$_GET[type]`SET `FreeSpaces` = $freespaces WHERE `ID` = $_GET[id]";
        mysqli_query($connect, $sql1);
    }
    if ($_GET['action'] == 'delete') {
        if ($freespaces - 1 >= 0) {
            $freespaces--;
            echo 'did';
        }
        $sql1 = "UPDATE `$_GET[type]`SET `FreeSpaces` = $freespaces WHERE `ID` = $_GET[id]";
        mysqli_query($connect, $sql1);
    }
    $type = $_GET['type'];

    

}
$sql = "SELECT * FROM `$type` WHERE 1";



if (isset($_POST['submit']) && isset($_POST['district'])) {
    $dis = htmlentities($_POST['district']);

    FilterByDistrict($dis);
}

function FilterByStreet($street)
{
    $GLOBALS['sql'] = "SELECT * FROM `$GLOBALS[type]` WHERE LOCATE('$street', `Address`)";
}

if (isset($_POST['street']) && $_POST['street'] != "") {
    $strt = htmlentities($_POST['street']);
    FilterByStreet($strt);
}

function FilterByDistrict($district)
{
    $GLOBALS['sql'] = "SELECT * FROM `$GLOBALS[type]` WHERE LOCATE('$district', `District`)";
}
if (isset($_POST['distance'])) {
    $distance = htmlentities($_POST['distance']);
    FilterByDistance($distance);
}

function FilterByDistance($dist)
{
    /* SELECT * FROM `closedtype` WHERE SQRT(POWER(Abs(`Latitude_WGS84`-55.818837),2) + POWER(Abs(`Longitude_WGS84` -37.664653),2))*1507/0.208432 < 1000*/
    //echo $dist;
    $GLOBALS['sql'] = "SELECT * FROM `$GLOBALS[type]` WHERE SQRT(POWER(Abs(`Latitude_WGS84`-55.818837),2) + POWER(Abs(`Longitude_WGS84` -37.664653),2))*1507/0.208432 < ($dist/10)";
    if (isset($_POST['district']) && $_POST['district'] != "") {
        $GLOBALS['sql'] = "SELECT * FROM `$GLOBALS[type]` WHERE SQRT(POWER(Abs(`Latitude_WGS84`-55.818837),2) + POWER(Abs(`Longitude_WGS84` -37.664653),2))*1507/0.208432 < ($dist/10) AND LOCATE('$_POST[district]', `District`) ";
        return;
    }
    if (isset($_POST['street']) && $_POST['street'] != "") {
        //echo $_POST['street'];
        $GLOBALS['sql'] = "SELECT * FROM `$GLOBALS[type]` WHERE SQRT(POWER(Abs(`Latitude_WGS84`-55.818837),2) + POWER(Abs(`Longitude_WGS84` -37.664653),2))*1507/0.208432 < ($dist/10) AND LOCATE('$_POST[street]', `Address`) ";
    }
}
//echo $sql;

$result = mysqli_query($connect, $sql);
if (mysqli_affected_rows($connect) == 0) {
    $result = mysqli_query($connect, "SELECT * FROM `$type` WHERE 1");
    echo '<div id="error">Неправильно введены данные</div>';
}
if (empty($session_user)) {
    $result = mysqli_query($connect, "SELECT * FROM `$type` WHERE 1");
    echo '<a href = "auth.php"> Необходимо авторизоваться </a>';
} else {
    echo 'Добро пожаловать, ' . $session_user['Name'] . '<br><a href = "quit.php">Выйти</a>';
}
$address = [];
while ($row = mysqli_fetch_array($result)) {
    $coords[] = $row['Coordinates'];
    //$address[] = $row['Address'];
    array_push($address, $row['Address']);
    $countAll[] = $row['CountSpaces'];
    $countFree[] = $row['FreeSpaces'];
    $names[] = $row['Name'];
}
/*}*/




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
            /*add(new ymaps.Placemark([55.687086, 37.529789], {
            balloonContent: 'цвет <strong>влюбленной жабы</strong>'
        }, {
            preset: 'islands#circleIcon',
            iconColor: '#3caa3c'
        })) */
            var coords = <?= json_encode($coords) ?>;
            var address = <?= json_encode($address) ?>;
            var countAll = <?= json_encode($countAll) ?>;
            var countFree = <?= json_encode($countFree) ?>;
            var names = <?= json_encode($names) ?>;

            var pointsL = [];
            coords.forEach(addDot);

            /*var addressL=[];
            address.forEach(changeaddress);

            function changeaddress(item){
                let length = pointsL.length;
                for(var i=0;i<length;i++){
                    addressL.push(item);
                }
            }*/

            function addDot(item) {
                let newitem = item.split(',');
                var numarray = [];
                let length = newitem.length;
                for (var i = 0; i < length; i++) {
                    numarray.push(parseFloat(newitem[i]));
                }
                pointsL.push(numarray);
                /*var myPlacemark = new ymaps.Placemark(numarray);
                myMap.geoObjects.add(myPlacemark);*/
            }
            clusterer = new ymaps.Clusterer({
                    preset: 'twirl#invertedVioletClusterIcons',
                    groupByCoordinates: false,
                    clusterDisableClickZoom: true
                }),
                getPointData = function(index) {
                    document.cookie = "index = " + index;
                    return {
                        balloonContentBody: '<h1>' + names[index] + '</h1><br>Адресс: <strong>' + address[index] +
                            '</strong><br> <h3>Свободно <strong>' + (countAll[index] - countFree[index]) +
                            '/' + countAll[index] + '</h3></strong><br><a href=homepage.php?id=' + (index + 1) + '&action=add&type=<?php echo $GLOBALS['type'] ?>> Занять место </a><br><a href=homepage.php?id=' + (index + 1) + '&action=delete&type=<?php echo $GLOBALS['type'] ?>> Освободить место </a>',
                        clusterCaption: '<strong>' + names[index] + '</strong>'
                    };
                },
                getPointOptions = function() {
                    return {
                        preset: 'twirl#violetIcon'
                    };
                },
                points = pointsL,
                geoObjects = [];
            for (var i = 0, len = pointsL.length; i < len; i++) {
                geoObjects[i] = new ymaps.Placemark(pointsL[i], getPointData(i));
            }
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

    <script>
        let elem = form.elements.one; // <input name="one"> element
        alert(elem.value); // 1
    </script>
    <div id="map"></div>
    <div id="filter">
        <form action="" method="POST" name="my">
            Введите район <input type="text" name="district" id="district" <?php if (isset($_POST['district']))
                                                                                echo 'value = "' . $_POST['district'] . '"'; ?>></input><br>
            Введите адрес или улицу <input type="text" name="street" id="street" <?php if (isset($_POST['street'])) echo 'value = "' . $_POST['street'] . '"'; ?>></input><br>
            <input type="radio" id="closed" name="myratio" value="closed" <?php if (isset($_POST['myratio']) && $_POST['myratio'] == 'closed') echo ' checked="checked"'; ?>>
            <label for="closed">Парковки закрытого типа</label><br>
            <input type="radio" id="outside" name="myratio" value="outside" <?php if (isset($_POST['myratio']) && $_POST['myratio'] == 'outside') echo ' checked="checked"'; ?>>
            <label for="outside">Парковки на улично-дорожной сети</label><br><br>
            <input type="range" min="0" max="25000" step="100" <?php if (isset($_POST['distance']))
                                                                    echo 'value = "' . $_POST['distance'] . '"'; ?>id="inputt" name="distance"> <br>
            Расстояние <output id="value"></output> м<br>
            <script>
                const value = document.querySelector("#value");
                const input = document.querySelector("#inputt");
                value.textContent = input.value;
                input.addEventListener("input", (event) => {
                    value.textContent = event.target.value;
                });
            </script>
            <input type="submit" name="submit" value="send"></input>
            <input type="hidden" id="mydata">
        </form>
    </div>
    <div id="test">
    </div>
    <script>
        var ans = "";
        for (var i = 0; i < 72; i++) {
            ans += addressL[i];
        }
        document.getElementById("test").innerText = ans;
    </script>
</body>

</html>