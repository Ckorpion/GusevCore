<?
   /*
    * Класс GCOnline
    * Version 3.11 of 11.09.2016
    *
    * GCOnline – Планировщик Cron
    * http://gusevcore.ru
    *
    * Список публичных функций:
    *    auth - Получение разрешения на выполнения скриптов
    *    start - Инициализирует функции и начинает отчет времени
    *    stop - Завешает выполнения скрипта
    *    log - Создает лог
    *    getStartTime - Получаем время, когда скрипт был запущен
    *    getNowTime - Получаем текущее время
    *    getDuration - Получает время выполнения скрипта
    *
    * Список приватных функций:
    *    initDB  - Подключаем БД
    *    getConfig  - Получение конфигурации из БД
    *    run - Получает название страницы по её url адресу
    *    getScripts - Получет скрипты для запуска
    *    isAlarm - Проверяет соответствует ли график текущему времени
    *    isTimeAlarm - Проверяет подходит ли единица времени к графику
    *    getRate - Получает рейтинг скрипта необходмого для сортировки
    *    sortScriptsByRate - Метод сортировки для usort
    *    restartScripts - Проверяем на зависание работающие скрипты
    *    code - Преобразует спецсимволы для сохранения в БД
    *    init - Устанавливает GCOnline. Создает таблицы
    *
    * Конфигурация скрипта:
    *    Обязательные:
    *       schedule - График
    *          * * * * * - мин час дни мес год. Запускать каждую минуту
    *          5 * 6,7 * * -  запускать в 5 минут, каждый час, 6 и 7 числа
    *          *\/2 * * * * - запускать каждые 2 минуты. экранирование не нужно
    *       terminate - количество секунд, после которого скрипт можно запускать повторно. понадобится например при зависании
    *       importance - тип запуска.
    *          0 - запускать со всеми
    *          1 - один из типа (type)
    *          2 - один среди всех
    *    Не обязательные:
    *       rate - рейтинг скрипта, учитывается при сортировки
    *       afterTerminate - действие при привышении время terminate
    *          0 - сбросить, запустить по графику
    *          1 - запустить немедленно
    *          2 - остановить, ожидать ручного запуска
    *       password - строка, передается в $argv[3]. можно использовать для валидации запуска
    *       reply - если true, то сохранять весь результат работы скрипта в полу reply в таблице GC_logs
    *
    * Статус скрипта:
    *    -3 - завис, ожидает ручного запуска
    *    -2 - пауза, установленная в ручную
    *    -1 - в ожидании перезагрузки
    *    0 - ожидания графика
    *    1 - выполняется
    *
    * Статус логов:
    *    0 - скрипт выполняется
    *    1 - скрипт успешно выполнился
    *    2 - скрипт завис
    *
    * $argv при вызове скрипта
    *   0 - Ссылка до пользовательского скрипта
    *   1 - Идентификатор скрипта
    *   2 - Адрес GCOnline. Удобно для подключения require $argv[2];
    *   3 - Параметр config -> password
    */
   date_default_timezone_set('Europe/Moscow');
   ob_start();

   class GCOnline {
      public $version = '3.11';
      public $DB;

      // База данных
      private $db_host = 'localhost';     // Хост
      private $db_user = '';              // Пользователь
      private $db_password = '';          // Пароль
      private $db_name = '';              // Название

      // Системные данные
      private $config = array();    // Конфигурация
      private $timeStart = 0;       // Время начала выполнения скрипта
      private $log = '';            // Логи
      private $log_id = 0;          // Идентификатор текущего лога


      // ПУБЛИЧНЫЕ ФУНКЦИИ

      /*
       * Получаем разрешение на запуск планировщика
       * @param act (String) - Тип запуска
       *    cron - запускается из cron. начнет запуск скриптов
       *    script - запускается из скрипта. подключится к БД
       *    other - запускается из иного источника
       *    browser - запускается из браузера, для установки
       * @param password (String) - Пароль
       */
      public function auth($act, $password = false) {
         $this -> initDB();

         // В других случаях нам не нужно запускать скрипты
         if ($act == 'cron') {
            $this -> getConfig();

            if ($this -> config['password'] == $password) {
               $this -> run();
            }
         } else if ($act == 'browser') {
            $this -> init($password);
         }
      }

      /*
       * Инициализирует функции и начинает отчет времени
       * Необходимо вызвать в самом начале скрипта
       */
      public function start() {
         GLOBAL $argv;

         $result = false;                             // Результат запуска
         $this -> timeStart = $this -> getNowTime();  // Установим время старта

         if (is_numeric($argv[1])) {
            // Получаем данные скрипта
            $sql = 'SELECT * FROM `GCOnline_scripts` WHERE `id` = ' . $argv[1] . ' LIMIT 1;';
            $script = $this -> DB -> query($sql) -> fetch_assoc();

            // Если скрипт найден
            if (isset($script['id'])) {
               $script['config'] = json_decode($script['config'], true);

               // Проверим пароль если установлен
               if (isset($script['config']['password'])) {
                  if (isset($argv[3]) && $argv[3] == $script['config']['password']) {
                     $result = true;
                  } else {
                     return false;
                  }
               } else {
                  $result = true;
               }

               if ($script['data'] != '') {
                  $script['data'] = json_decode($script['data'], true);
               }

               $this -> script = $script;
               $this -> log = $this -> log('Начало в ' . date('H:i:s d.m.Y'), false);

               // Изменим состояние скрипта
               $sql = '
                  UPDATE 
                     `GCOnline_scripts` 
                  SET 
                     `status` = 1,
                     `statusTime` = ' . $this -> getStartTime() . '
                  WHERE 
                     `id` = ' . $this -> script['id'] . ' 
                  LIMIT 1;
               ';         
               $this -> DB -> query($sql);

               // Добавим лог
               $sql = '
                  INSERT INTO `GC_logs` (
                     `script_id`,
                     `time_start`,
                     `log`
                  )
                  VALUES 
                     (
                        ' . $this -> script['id'] . ',
                        ' . $this -> getStartTime() . ',
                        "' . $this -> log . '"
                     )
                  ;
               ';
               $this -> DB -> query($sql);

               // Получим идентификатор лога
               $this -> log_id = $this -> DB -> insert_id;
            }
         }

         return $result;
      }

      /*
       * Завешает выполнения скрипта
       * Необходимо вызвать после завершения скрипта
       */
      public function stop() {
         $this -> log .= $this -> log('Конец в ' . date('H:i:s d.m.Y'), false);
         $reply = '';

         // Нужно ли записывать результат в базу
         if (isset($this -> script['config']['reply']) && $this -> script['config']['reply']) {
            $reply = $this -> code(ob_get_contents());
         }

         // Изменим статус лога
         $sql = '
            UPDATE 
               `GC_logs` 
            SET 
               `duration` = ' . $this -> getDuration() . ',
               `status` = 1,
               `log` = "' . $this -> log . '",
               `reply` = "' . $reply . '"
            WHERE 
               `id` = ' . $this -> log_id . ' 
            LIMIT 1;
         ';
         $this -> DB -> query($sql);

         // Изменим статус скрипта
         $sql = '
            UPDATE 
               `GCOnline_scripts` 
            SET 
               `status` = 0,
               `statusTime` = ' . $this -> getNowTime() . '
            WHERE 
               `id` = ' . $this -> script['id'] . ' 
            LIMIT 1;
         ';
         $this -> DB -> query($sql);
      }

      /*
       * Создает лог
       * @param text (String) - Текст лога
       * @param isSave (Boolean) - Нужно ли сохранить лог
       * @return (String) - Лог со времением выполнения скрипта. Пример: 0.05|текст;
       */
      public function log($text, $isSave = true) {
         $log = $this -> getDuration() . '|' . $this -> code($text) . ';';

         // Если необходимо сохраним лог
         if ($isSave) {
            $this -> log .= $log;
            $sql = '
               UPDATE 
                  `GC_logs` 
               SET 
                  `log` = "' . $this -> log . '"
               WHERE 
                  `id` = ' . $this -> log_id . ' 
               LIMIT 1;
            ';
            $this -> DB -> query($sql);
         }

         return $log;
      }

      /*
       * Получаем время, когда скрипт был запущен
       * @return (Double) - Начальное время
       */
      public function getStartTime() {
         return $this -> timeStart;
      }

      /*
       * Получаем текущее время
       * @return (double) - Текущее время
       */
      public function getNowTime() {
         return microtime(true);
      }

      /*
       * Получает время выполнения скрипта
       * @return (Double) - Время выполнения
       */
      public function getDuration() {
         return intval(($this -> getNowTime() - $this -> timeStart) * 1000) / 1000;
      }


      // СПИСОК ПРИВАТНЫХ ФУНКЦИЙ

      /*
       * Подключаем базу данных
       */
      private function initDB() {
         if ($this -> db_user) {
            $mysqli = new mysqli($this -> db_host, $this -> db_user, $this -> db_password, $this -> db_name);
            if (mysqli_connect_errno()) {
               echo ('Не удалось подключиться');
               exit();
            }
            $mysqli -> set_charset('utf8');

            $this -> DB = $mysqli;
         }
      }

      /*
       * Получаем конфигурацию
       */
      private function getConfig() {
         $sql = '
            SELECT * FROM `GCOnline` WHERE `id` IN (
               "password",
               "maxRun"
            );
         ';
         $result = $this -> DB -> query($sql);
         while ($info = $result -> fetch_assoc()) {
            $this -> config[$info['id']] = $info['value'];
         }
      }

      /*
       * Запускаем планировщик
       */
      private function run() {
         // Получаем скрипты для запуска
         $scripts = $this -> getScripts();      // Получим скрипты
         $runed = $scripts['runed'];
         $scripts = $scripts['toStart'];

         // Запускаем скрипты
         if (count($scripts) > 0) {
            $i = 0;
            while (isset($scripts[$i]) AND $i <= $this -> config['maxRun']) {
               // Информация передаваемая скрипту в аргументах
               $data = $scripts[$i]['id'];
               if (isset($scripts[$i]['config']['password'])) {
                  $data .= ' ' . $scripts[$i]['config']['password'];
               }
               // Формируем команду
               $command = array(
                  'php',
                  $scripts[$i]['url'],       // Ссылка до скрипта
                  $scripts[$i]['id'],        // Идентификатор скрипта
                  __FILE__                   // Адрес GCOnline
               );
               // Если установлен пароль, добавим его
               if (isset($scripts[$i]['config']['password'])) {
                  array_push($command, $scripts[$i]['config']['password']);
               }
               array_push($command, '> /dev/null &');   // Вид ответа
               // array_push($command, '2>&1');

               $command = implode(' ', $command);
               // Выполняем
               echo shell_exec($command);
               $i++;
            }
         }

         // Проверяем работающие скрипты
         $this -> restartScripts($runed);
      }

      /*
       * Получет скрипты для запуска
       * @return (Array)
       *    toStart (Array) - Скрипты для запуска
       *    runed (Array) - Скрипты уже запущенные
       */
      private function getScripts() {
         $scripts = array();           // Скрипты подходящие для запуска
         $runed = array();             // Уже запущенные скрипты
         $allTypes = array();          // Все запущенные типы
         $isRunImportance2 = false;    // Запущен скрипт с важностью 2
         $noTypes = array();           // Типы которые нельзя больше запускать
         $toStart = array();           // Скрипты, которые будем запускать

         // Получаем скрипты не на паузе
         $sql = 'SELECT * FROM `GCOnline_scripts` WHERE `status` >= -1 ORDER BY `statusTime` ASC;';
         $result = $this -> DB -> query($sql);
         while ($script = $result -> fetch_assoc()) {
            $script['config'] = json_decode($script['config'], true);

            // готов к запуску или требуется перезагрузка
            if ($script['status'] <= 0) {
               if ($this -> isAlarm($script)) {                            // Подходит ли расписанию        
                  $script['config']['rate'] = $this -> getRate($script);   // Получим рейтинг для сортировки
                  array_push($scripts, $script);
               }
            } else {                                                       // Скрипт в настоящее время выполняется или зависший
               array_push($runed, $script);
               array_push($allTypes, $script['type']);

               if ($script['config']['importance'] == 2) {
                  $isRunImportance2 = true;
               } else if ($script['config']['importance'] == 1) {
                  array_push($noTypes, $script['type']);
               }
            }
         }

         // Если запущен важный скрипт(важность 2), то ничего не запускаем
         if ($isRunImportance2) {
            $scripts = array();
         }

         // сортируем скрипты по рейтингу
         usort($scripts, array('GCOnline', 'sortScriptsByRate'));

         // Выбираем каккие скрипты будем запускать
         $i = 0;
         while (isset($scripts[$i])) {
            if (
               !in_array($scripts[$i]['type'], $noTypes) AND
               ($scripts[$i]['config']['importance'] != 1 OR !in_array($scripts[$i]['type'], $allTypes)) AND 
               ($scripts[$i]['config']['importance'] != 2 OR count($toStart) == 0) 
            ) {
               array_push($toStart, $scripts[$i]);
               array_push($allTypes, $scripts[$i]['type']);
               if ($scripts[$i]['config']['importance'] == 1) {   // Больше скрипты с таким же типом запускать нельзя
                  array_push($noTypes, $scripts[$i]['type']);
               }
               if ($scripts[$i]['config']['importance'] == 2) {   // Бельше никакие скрипты запускать нельзя
                  break;
               }
            }
            $i++;
         }

         return array(
            'toStart' => $toStart,
            'runed' => $runed
         );
      }

      /*
       * Проверяет соответствует ли график текущему времени
       * @param script (Array) - Скрипт
       * @return (Boolean) - Подходил ли графику
       */
      private function isAlarm($script) {
         $result = false;        // Соответствует ли текущее время расписанию скрипта

         $schedule = explode(' ', $script['config']['schedule']);
         $year = $this -> isTimeAlarm($schedule[4], 'Y', $script);
         $month = $this -> isTimeAlarm($schedule[3], 'm', $script);
         $day = $this -> isTimeAlarm($schedule[2], 'd', $script);
         $hour = $this -> isTimeAlarm($schedule[1], 'H', $script);
         $minute = $this -> isTimeAlarm($schedule[0], 'i', $script);

         // Если подходит по времени
         if ($year AND $month AND $day AND $hour AND $minute) {
            $result = true;
         }

         // Если нужна перезагрузка
         if ($script['status'] == -1) {
            $result = true;
         }

         return $result;
      }

      /*
       * Проверяет подходит ли текущяя единица времени к графику
       * @param timeAlarm (Integer) - Единица времени из графика
       * @param timeType (String) - тип времени
       * @param script (Array) - Скрипт
       * @return (Boolean) - Подходил ли единица времени
       */
      private function isTimeAlarm($timeAlarm, $timeType, $script) {
         $result = false;        // Подходил ли единица времени

         if ($timeAlarm == '*') {                        // В любое время
            $result = true;
         } else if (is_numeric($timeAlarm)) {            // Один раз в указанное время
            if (date($timeType) + 0 == $timeAlarm) {
               $result = true;
            }
         } else {                                        // В время по формуле
            $every = explode('/', $timeAlarm);  // Каждый промежуток времени
            $some = explode(',', $timeAlarm);   // Перечисляемое время

            if (count($every) == 2) {                 // Каждый промежуток времени
               if ($script['statusTime'] == 0) {      // Скрипт еще ни разу не запускался
                  $result = true;
               } else {
                  $interval = date($timeType) - date($timeType, strtotime(0));
                  if ($interval % $every[1] == 0) {   // Прошел необходимый интервал времени
                     $result = true;
                  }
               }
            } else if (count($some) > 1) {            // Перечисляемое время
               $i = 0;
               while (isset($some[$i])) {
                  if ($some[$i] == date($timeType)) {
                     $result = true;
                  }
                  $i++;
               }
            }
         }

         return $result;
      }

      /*
       * Получает рейтинг скрипта необходмого для сортировки
       * @param script (Array) - Скрипт
       * @return (integer) - Рейтинг срипта
       */
      private function getRate($script) {
         $rate = 0;

         // Если нужна перезагрузка
         if ($script['status'] == -1) {
            $rate += 100;
         }

         // Если в конфигурации указан рейтинг, используем его
         if (isset($script['config']['rate'])) {
            $rate = $script['config']['rate'];
         }

         return $rate;
      }

      /*
       * Метод сортировки для usort
       * @param script1 (Array) - Скрипт 1
       * @param script2 (Array) - Скрипт 2
       * @return (Integer) - Результат сравнения
       */
      private function sortScriptsByRate($script1, $script2) {
         $result = 0;
         if ($script1['config']['rate'] == $script2['config']['rate']) {
            if ($script1['statusTime'] < $script2['statusTime']) {
               $result = -1;
            } else if ($script1['statusTime'] > $script2['statusTime']) {
               $result = 1;
            }
         } else if ($script1['config']['rate'] > $script2['config']['rate']) {
            $result = -1;
         } else {
            $result = 1;
         }
         return $result;
      }

      /*
       * Проверяем на зависание работающие скрипты
       * @param scripts (Array) - Работающие скрипты
       */
      private function restartScripts($scripts) {
         $restart = array();     // Перезагрузить скрипты
         $update = array();      // Обновить скрипты
         $stop = array();        // Остановить до ручного запуска

         $i = 0;
         while (isset($scripts[$i])) {
            $timeBad = $this -> getNowTime() - $scripts[$i]['config']['terminate'];
            if ($scripts[$i]['statusTime'] < $timeBad) {
               $act = 'update';
               if (isset($scripts[$i]['config']['afterTerminate'])) {
                  if ($scripts[$i]['config']['afterTerminate'] == 1) {
                     $act = 'restart';
                  } else {
                     $act = 'stop';
                  }
               }

               array_push($$act, $scripts[$i]['id']);

               // Пометим лог скрипта, как зависший
               $sql = '
                  UPDATE 
                     `GC_logs` 
                  SET 
                     `status` = 2 
                  WHERE 
                     `script_id` = ' . $scripts[$i]['id'] . ' AND 
                     `status` = 0
                  ;
               ';
               $this -> DB -> query($sql);
            }
            $i++;
         }

         // Перезагружаем скрипты
         if (count($restart) > 0) {
            $sql = 'UPDATE `GCOnline_scripts` SET `status` = -1 WHERE `id` IN (' . implode(',', $restart) . ');';
            $this -> DB -> query($sql);
         }

         // Обновляем скрипты
         if (count($update) > 0) {
            $sql = 'UPDATE `GCOnline_scripts` SET `status` = 0 WHERE `id` IN (' . implode(',', $update) . ');';
            $this -> DB -> query($sql);
         }

         // Останавливаем скрипты
         if (count($stop) > 0) {
            $sql = 'UPDATE `GCOnline_scripts` SET `status` = -3 WHERE `id` IN (' . implode(',', $stop) . ');';
            $this -> DB -> query($sql);
         }
      }

      /*
       * Преобразует спецсимволы для сохранения в БД
       * @param text (String) - Текст
       * @return (String) - Кодированный текст
       */
      private function code($text) {
         $text = str_replace('<','&#060', $text);
         $text = str_replace('>','&#062', $text);
         $text = str_replace('"', '&#034', $text);
         $text = str_replace('\\', '&#8260', $text);

         return $text;
      }

      /*
       * Устанавливает GCOnline. Создает таблицы
       */
      public function init($password) {
         if (!$this -> DB) {
            echo 'Set the data of the database';
            return;
         }

         if (!preg_match("/^[\da-z]+$/", $password)) {
            echo 'Incorrect password';
            return;
         }

         if ($this -> DB -> query('SHOW TABLES LIKE "GCOnline";') -> fetch_assoc()) {
            echo 'Not need to install';
            return;
         }

         // Установим таблицу с конфигурацией
         $sql = '
            CREATE TABLE IF NOT EXISTS `GCOnline` (
               `id` varchar(32) NOT NULL,
               `value` text NOT NULL,
               PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
         ';
         $this -> DB -> query($sql);
         // Добавим конфигурацию
         $sql = '
            INSERT INTO `GCOnline` (`id`, `value`) VALUES
            ("maxRun", "10"),
            ("password", "' . $password . '");
         ';
         $this -> DB -> query($sql);

         // Установим таблицу со скриптами
         $sql = '
            CREATE TABLE IF NOT EXISTS `GCOnline_scripts` (
               `id` int(11) NOT NULL AUTO_INCREMENT,
               `type` varchar(32) NOT NULL,
               `title` varchar(32) NOT NULL,
               `description` varchar(256) NOT NULL,
               `config` text NOT NULL,
               `data` text NOT NULL,
               `status` int(1) NOT NULL,
               `url` varchar(256) NOT NULL,
               `statusTime` double NOT NULL,
               PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=10;
         ';
         $this -> DB -> query($sql);

         // Установим таблицу с логами
         $sql = '
            CREATE TABLE IF NOT EXISTS `GC_logs` (
               `id` int(11) NOT NULL AUTO_INCREMENT,
               `script_id` int(11) NOT NULL,
               `time_start` double NOT NULL,
               `duration` double NOT NULL,
               `status` int(1) NOT NULL,
               `log` text NOT NULL,
               `reply` text NOT NULL,
               PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
         ';
         $this -> DB -> query($sql);

         // Добавим крон
         $cron = shell_exec('crontab -l');
         $cron = $cron . '* * * * * php -q ' . __DIR__ . '/GCOnline.php ' . $password . ' >/dev/null 2>&1';
         file_put_contents('/tmp/crontab.txt', $cron . PHP_EOL);
         exec('crontab /tmp/crontab.txt');

         echo 'ready';
      }
   }

   // Определим тип запуска
   $password = false;
   if (isset($argv) && isset($argv[1]) && $argv[1] != __FILE__) {
      $act = 'cron';       // Запущен из cron
      $password = $argv[1];
   } else if (isset($argv) && isset($argv[1]) && $argv[1] == __FILE__) {
      $act = 'script';     // Запущен из скрипта
   } else if ($_GET['act'] == 'init') {
      $act = 'browser';    // Запущен из браузера для установки
      $password = $_GET['password'];
   } else {
      $act = 'other';
   }

   if ($act != 'other') {
      $GCOnline = new GCOnline();
      $GCOnline -> auth($act, $password);
   }
?>