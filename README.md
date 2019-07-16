# mango-office

установка <br>
composer require yahve89/mango-office

использование <br>
include_once 'Mango.php';

$mango = new Mango\Mango('Уникальный код АТС', 'Ключ для создания подписи');<br>

получить список всех пользователей<br>
$mango->userList();

получить текущего пользователя<br>
$mango->userList('добавочный номер пользователя');

получить статистику пользователя за указанный период<br>
$mango->reportList('начальная дата', 'конечная дата', 'внутренний номер абонента');

получить статистику всех пользователей за указанный период<br>
$mango->reportList('начальная дата', 'конечная дата');

скачать запись разговора<br>
$mango->downloadAudio('уникальный идентификатор записи');

скачать запись разговора<br>
$mango->downloadAudio('уникальный идентификатор записи');

воспроизвести запись разговора<br>
$mango->downloadAudio('уникальный идентификатор записи', 'play');
