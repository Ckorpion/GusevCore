<?
   /**
    * GusevCore
    * Version 4.02 of 21.05.2017
    *
    * Платформа для веб-разработки
    * http://gusevcore.ru
    *
    * Список публичных функций:
    *    getPage - Подключает страницу
    *    getAPI - Подключаем API модуля
    *    getCtx - Получить контекст
    *    setCtx - Записать контекст
    *    fastRender - Выполнить обработку быстрой записи
    *    getPageName - Получает название страницы по её url адресу
    *    log - Записывает лог в базу
    *    getStartTime - Получаем время, когда скрипт был запущен
    *    getNowTime - Получаем текущее время
    *    getDuration - Получает время выполнения скрипта
    */
   class GC {
      private $context = array('_GC' => array(
         'version' => '4.02',
         'versionDate' => '21.05.2017',
         // Логирование
         'log' => array(
            'timeStart' => null,       // Время начала выполнения скрипта
            'log_id' => null,          // Идентификатор лога
            'isLogEnable' => 0,        // Доступ к логам, 0 нужно узнать, -1 доступа нет, 1 доступ есть
            'log' => ''                // Текст лога всей сессии
         ),
         // Страница
         'page' => array(
            'blocks' => array(),       // КЭШ HTML шаблонов
            'resource' => array(),     // Ресурсы
            'modules' => array(),      // КЭШ модулей
            'params' => array()        // Параметры страниц
         )
      ));
      
      public function start() {
         GLOBAL $GCF, $CONFIG, $_START, $_URL, $_PATH;

         $this -> setCtx('_GC/log/timeStart', $this -> getNowTime());   // Начнем отчет времени
         $_POST = json_decode(file_get_contents('php://input'), true);  // Получим POST запрос

         require __DIR__ . '/config.php';                               // Подключим конфигурацию
         require __DIR__ . '/GCF.php';                                  // Подключим дополнительные функции

         $this -> log('Start session', false, 0, '', false);            // Создадим лог о старте
         
         for ($i = 0; $i < count($CONFIG -> headers); $i++) {           // Установим заголовки
            header($CONFIG -> headers[$i]);
         }
         for ($i = 0; $i < count($CONFIG -> scripts); $i++) {           // Подключим пользовательские библиотеки
            require_once(__DIR__ . '/' .$CONFIG -> scripts[$i]);
         }

         if (isset($_START) && !$_START) {                              // Но обрабатывать запрос
            return;
         }

         $_PATH = explode('/', $_SERVER['SCRIPT_NAME']);                // Получим корень сайта
         unset($_PATH[count($_PATH) - 1]);
         $_PATH = implode('/', $_PATH);

         if (!isset($_POST['method']) AND !isset($_GET['method'])) {    // Запрос страницы
            // Получаем адрес запрашиваемой страницы
            $url = parse_url($_SERVER['REQUEST_URI'])['path'];
            $_URL = $url;
            $url = str_replace($_PATH . '/', '', $url);                 // Обрежим каталог
            $page_name = $this -> getPageName($url);                    // Получает название страницы по URL из config.php

            if (!$page_name) {                                          // Страница не найдена
               // TODO дать возможность обработки 404
               header('HTTP/1.1 404 Not Found');
               exit();   
            }
            $page = $this -> getPage($page_name);                       // Получим запрашиваемую страницу
            echo $this -> template($page);                              // Вставим её в teamplate и вернем
         } else {                                                       // Вызов метода
            $params = isset($_GET['method']) ? $_GET : $_POST;          // Получаем параметры запроса
            // method содержит название модуля и функции через точку
            $method = explode('.', $params['method']);
            $name = $method[0];

            // Проверяем корректрость method
            if (count($method) == 2) {
               if ($params['method'] != 'api.getPage') {                // Пользовательский метод
                  $API = $this -> getAPI($name);
                  // Проверим наличие класса и объявление метода в $API в классе
                  if (!isset($API -> API) || !in_array($method[1], $API -> API)) {
                     echo json_encode(array('error' => array('code' => 2, 'msg' => 'Method not found')));
                     exit();
                  }
                  $response = $API -> {$method[1]}($params);            // Выполняем метод
               } else {                                                 // Системный метод
                  $page_name = false;
                  $options = isset($params['options']) ? $params['options'] : array();
                  $data = isset($params['params']) ? $params['params'] : array();

                  if  (isset($params['name'])) {                        // Уазано название страницы
                     $page_name = $params['name'];
                  } else if (isset($params['url'])) {                   // Указанна ссылка
                     $url = parse_url($params['url']);
                     if(isset($url['query'])) {
                        parse_str($url['query'], $_GET);
                     }
                     $url = isset($url['path']) ? $url['path'] : '';
                     $url = str_replace($_PATH . '/', '', $url);
                     $_URL = $url;

                     $page_name = $this -> getPageName($url);           // Получим название страницы в modules по url
                  }
                  if ($page_name) {                                     // Страница найдена
                     $response = $this -> getPage($page_name, $data, $options);
                     // Обработаем ссылкаи на ресурсы
                     $response['resource'] = $this -> getResourceUrl($response['resource']);
                     // Если шаблоны различаются, необхадима перезагрузка
                     $response['reload'] = $response['template'] != $params['template'];
                  } else {
                     $response = false;
                  }
               }

               if (is_array($response)) {                               // Если ответ это массив - преобразуем в JSON
                  $response = json_encode($response, JSON_UNESCAPED_UNICODE);
               }
               $response = $response ? $response : false;
               echo $response;
            }
         }

         $this -> endSession();                                         // Сообщим, что все готово
      }

      /**
       * Подключает страницу
       * @param name {String} - Название страницы в modules
       * @param params {Array} - Параметры модуля
       * @param options {Array} - Опции рендера
       *    noRender {Boolean} - Не производить замену параметров
       *    clear {Boolean} - Отчистить незаполненные параметры
       *    fast {Boolean} - Быстрый режим
       * @return {Array} - Результат модуля
       *    name - Название модуля
       *    resource - Набор ресурсов для отображения HTML страницы{скрипты и стили}
       *    HTML - Код страницы
       *    meta - Мета теги
       */
      public function getPage($name, $params = array(), $options = array()) {
         GLOBAL $GCF;

         $page = $this -> getDefaultPage();                             // Установим информацию о странице
         $page['name'] = $name;
         $page['className'] = $GCF -> codeText($name, array('/' => '_'));

         $path = $this -> getPathModuleByName($name);                   // Получим каталог с модулем
         $modul_name = $path['modul_name'];
         $path = $path['path'];

         if (count(explode('?', $modul_name)) > 1) {                    // Проверим нужен нам шаблон или модуль
            $page['HTML'] = $this -> getHTML($path . explode('?', $modul_name)[1], $params, $options);
         } else {
            $module = $this -> getAPI($name);
            if (!$module) {
               return false;
            }
            $newPage = $module -> init($page, $params);
            $page = $newPage ? $newPage : $page;
         }
         // Смержим ресурсы
         $this -> setCtx('_GC/page/resource', array_merge($this -> getCtx('_GC/page/resource'), $page['resource']));

         $page = $this -> replaceComponents($page, $options);           // Обработаем компоненты

         return $page;
      }

      /**
       * Подключаем модуль
       * @param name {String} - Название модуля
       * @param class_name {String} - Название класса
       * @return {Class || boolean} - Модуль или false если модуль не найден
       */
      public function getAPI($name, $class_name = false) {
         GLOBAL $GCF;

         $modules = $this -> getCtx('_GC/page/modules');                // Получим названия уже подключенных модулей

         if (!in_array($name, $modules)) {                              // Модуль еще не подключен
            $path = $this -> getPathModuleByName($name);
            $modul_name = $path['modul_name'];
            $path = $path['path'];

            if (file_exists(__DIR__ . '/modules/'. $path . $modul_name . '.php')) {
               require_once(__DIR__ . '/modules/'. $path . $modul_name . '.php');
               array_push($modules, $name);
               $this -> setCtx('_GC/page/modules', $modules);           // Сохраним название модуля в списке подключенных
            } else {
               return null;
            }
         }

         if (!$class_name) {
            $class_name = $GCF -> codeText($name, array('/' => '_'));
         }

         return new $class_name();                                      // Вернем новый экземпляр модуля
      }

      /**
       * Получить контекст
       * @param name {String} - Имя контекста
       * @param local {Boolean} - Использовать ли локальный контекст модуля
       * @return {*} - Значение контекста
       */
      public function getCtx($name, $local = false) {
         GLOBAL $GC;

         $self = $local ? $this : $GC;
         return $self -> getBaseCtxByName($name);                       // Получим значение контекста
      }

      /**
       * @param name {String} - Имя контекста
       * @param value {*} - Значение контекста
       * @param local {Boolean} - Использовать ли локальный контекст модуля
       */
      public function setCtx($name, $value, $local = false) {
         GLOBAL $GC;

         $self = $local ? $this : $GC;
         
         $base =& $self -> getBaseCtxByName($name, true);            // Получим базовый объект контекста
         $name = explode('/', $name);
         $base[$name[count($name) - 1]] = $value;                    // Запишем значение
      }

      /**
       * Выполнить обработку быстрой записи
       * @param html {String} - HTML код
       * @return {String} - HTML код
       */
      public function fastRender($html) {
         $params = array();
         $paramsPage = $this -> getCtx('_GC/page/params', true);     // Получим сохраненные параметры

         for ($i = 0; $i < count($paramsPage); $i++) { 
            $params = array_merge($params, $paramsPage[$i]);
         }

         return $this -> replaceParams($html, $params);              // Заменим параметры
      }

      /**
       * Получает название страницы по её url адресу
       * @param url {String} - URL адрес
       * @return {String || Boolean} - Название страницы или false, если такой нет
       */
      public function getPageName($url) {
         GLOBAL $CONFIG;

         for ($i = 0; $i < count($CONFIG -> website_pages); $i++) { 
            $pattern = '/^(' . $path . $CONFIG -> website_pages[$i][0] . '|' . $CONFIG -> website_pages[$i][0] . ')$/';
            if (preg_match($pattern, $url)) {
               return $CONFIG -> website_pages[$i][1];
            }
         }

         return false;
      }

      /**
       * Создает лог
       * @param text {String} - Текст лога
       * @param noSession {Boolean} - Вне сессии. Создается новый лог
       * @param status {Integer} - Статус лога
       * @param reply {String} - Результат
       * @param isSave {Boolean} - Нужно ли сохранить лог
       * @return {String || Boolean} - Лог со времением выполнения скрипта. Пример: 0.05|текст; Если доступа к логам нет, то false
       */
      public function log($text, $noSession = false, $status = 0, $reply = '', $isSave = true) {
         GLOBAL $DB, $GCF;

         // Проверим доступ
         if ($isSave && $this -> getCtx('_GC/log/isLogEnable') == 0) {
            // Создана ли таблица
            if (!$DB || !($DB -> query('SHOW TABLES LIKE "GC_logs";') -> fetch_assoc())) {
               $this -> setCtx('_GC/log/isLogEnable', -1);           // Нет доступа к логам
            } else {
               $this -> setCtx('_GC/log/isLogEnable', 1);            // Есть доступ к логам
            }
         }

         // Обработаем данные
         $log = $this -> getDuration() . '|' . $GCF -> codeText($text, 'toBase') . ';';
         $reply = $GCF -> codeText($reply, 'toBase');
         if (!is_numeric($status)) {
            return false;
         }
         $this -> setCtx('_GC/log/log', $this -> getCtx('_GC/log/log') . $log);

         if (!$isSave) {                                             // Если записывать не надо
            return $log;
         }

         // Пишится лог в первые или новый
         if ($this -> getCtx('_GC/log/log_id') == null || $noSession) {
            $sql = '
               INSERT INTO `GC_logs` (
                  `time_start`,
                  `status`,
                  `log`,
                  `reply`
               )
               VALUES (
                  ' . $this -> getStartTime() . ',
                  ' . $status . ',
                  "' . $log . '",
                  "' . $reply . '"
               );
            ';
            if (!$noSession) {                                       // Получим идентификатор лога если это лог сессии
               $this -> setCtx('_GC/log/log_id', $DB -> insert_id);
            }
         } else {
            $sql = '
               UPDATE `GC_logs` 
               SET 
                  `duration` = ' . $this -> getDuration() . ',
                  `log` = "' . $this -> getCtx('_GC/log/log') . '",
                  `status` = ' . $status . '
               WHERE 
                  `id` = ' . $this -> getCtx('_GC/log/log_id') . ' 
               LIMIT 1;
            ';
         }
         $DB -> query($sql);

         return $log;
      }

      /**
       * Получаем время, когда скрипт был запущен
       * @return {Double} - Начальное время
       */
      public function getStartTime() {
         return $this -> getCtx('_GC/log/timeStart');
      }

      /**
       * Получаем текущее время
       * @return {Double} - Текущее время
       */
      public function getNowTime() {
         return microtime(true);
      }

      /**
       * Получает время выполнения скрипта
       * @return {Double} - Время выполнения
       */
      public function getDuration() {
         return intval(($this -> getNowTime() - $this -> getCtx('_GC/log/timeStart')) * 1000) / 1000;
      }


      // СПИСОК ПРИВАТНЫХ ФУНКЦИЙ

      /**
       * Получаем HTML блок
       * @param name {String} - Название блока
       * @param params {Array} - Массив с параметрами для блока
       * @param options {Array} - Опции рендера. Из getPage
       * @return {String} - HTML блок
       */
      private function getHTML($path, $params = array(), $options) {
         GLOBAL $GCF;

         $block = $this -> getCtx('_GC/page/blocks/' . $path);       // Проверяем если блок в КЭШ, берем от туда
         if (!$block) {
            // Скачиваем блок
            $block = file_get_contents(__DIR__ . '/modules/' . $path . '.html');
            if ($block) {
               $this -> setCtx('_GC/page/blocks/' . $path, $block);  // Сохраним в КЭШ
            } else {
               return;
            }
         }

         if (!isset($options['noRender']) || !$options['noRender']) {
            // Заменим параметры
            $html = $this -> replaceParams($block, $params, $options);
         }

         return $html;
      }

      /**
       * Заменяет параметры в коде
       * @param html {Array || String} - Блок из getHTML или HTML код
       * @param params {Object} - Объекст с парметрами и значениями
       * @param options {Array} - Опции рендера. Из getPage
       * @return {String} - HTML с вставленными параметрами
       */
      private function replaceParams($html, $params, $options = array()) {
         GLOBAL $GCF;

         if (isset($options['fast']) && $options['fast']) {          // Если режим fast
            // Заменим разом параметры на ссылки на контекст
            // Сохраним параметры в контексте
            // Для обратной замены функция fastRender
            $paramIndex = count($this -> getCtx('_GC/page/params'));
            $keysOld = array_keys($params);
            $paramsNew = array();
            for ($i = 0; $i < count($keysOld); $i++) { 
               $paramsNew['_GC/page/params/' . $paramIndex . '/' . $keysOld[$i]] = $params[$keysOld[$i]];
            }
            $this -> setCtx('_GC/page/params/' . $paramIndex, $paramsNew, true);
            $keysOld = implode('|', $keysOld);
            $html = preg_replace('/({{(' . $keysOld . ')}})/im', '{{_GC/page/params/' . $paramIndex . '/$2}}', $html);
         } else {                                                    // Обычный режим, хаменяем параметры значениями
            $html = $GCF -> format($html, $this -> paramsRender($params));
            $html = $this -> logicRender($html);
         }

         if (isset($options['clear']) && $options['clear']) {
            $html = preg_replace("/{{(?!_GC){1}.+}}/imU", '', $html);// Отчистим не замененные параметры
         }

         return $html;
      }

      /**
       * Обрабатывает параметры для шаблона
       * @param params {Array} - Массив с параметрами
       * @param path {String} - Путь параметра
       * @return {Array} - Массив с обработанными параметрами
       */
      private function paramsRender($params, $path = '') {
         $newParams = array();

         foreach ($params as $key => $value) {
            if (is_array($value)) {
               $newParams = array_merge($newParams, $this -> paramsRender($value, $path . $key . '/'));
            } else {
               if (preg_match('/{{.+}}/im', $value)) {
                  $value = preg_replace('/{{.+}}/im', '', $value);   // Удалим параметры из параметров
               }
               $newParams['{{' . $path . $key . '}}'] = $value;
            }
         }

         return $newParams;
      }

      /**
       * Подключает компоненты описанные в HTML коде
       * Вызывается из getHTML, при обработке страницы
       * @param page {Array} - Модуль страницы
       * @param options {Array} - Опции рендера
       * @return {Array} - Модуль страницы
       */
      private function replaceComponents($page, $options) {
         GLOBAL $GCF;

         $component = '/\<component\spath\=\"([^\"]+)\"\>((.|\n)*?)\<\/component\>/im';
         preg_match_all($component, $page['HTML'], $components);     // Найдем все компоненты
         
         for ($i = 0; $i < count($components[1]); $i++) { 
            if ($components[2][$i] != '') {                          // Если не пусто, значит есть опции
               $params = $GCF -> xmlToArray('<xml>' . $components[2][$i] . '</xml>');
            } else {
               $params = array();
            }
            // Получим компонент 
            $block = $this -> getPage($components[1][$i], $params, $options);
            if (!$block) {
               $block = array('HTML' => '');
            } else {
               // Сохраним ресурсы в КЭШ
               $this -> setCtx('_GC/page/resource', array_merge($this -> getCtx('_GC/page/resource'), $block['resource']));
            }
            $page['HTML'] = $GCF -> str_replace_once($components[0][$i], $block['HTML'], $page['HTML']);
         }

         return $page;
      }

      /**
       * Преобразует ресурсы в HTML теги
       * @param resource {Array of String} - Набор JS и CSS ресурсов
       * @return {String} - HTML код
       */
      private function getResource($resource) {
         $urls = $this -> getResourceUrl($resource);
         $html = '';

         for ($i = 0; $i < count($urls); $i++) {
            if ($urls[$i]['type'] == 'js') {
               $html .= '<script type="text/javascript" src="' . $urls[$i]['url'] . '"></script>';
            } else {
               $html .= '<link type="text/css" rel="stylesheet" href="' . $urls[$i]['url'] . '">';
            }
         }
         
         return $html;
      }

      /**
       * Преобразует ресурсы в ссылки
       * @param resource {Array} - Ресурсы
       * @return {Array}
       *    type - Тип реусрса
       *    url - Ссылка на ресурс
       */
      private function getResourceUrl($resource) {
         GLOBAL $_PATH;

         $resource = array_unique($resource);
         sort($resource);
         $urls = array();
         $rootUrl = $_PATH . '/modules/';

         for ($i=0; $i < count($resource); $i++) { 
            $item = explode('!', $resource[$i]);

            $path = explode('/', $item[1]);
            $path_name = explode('?', array_splice($path, -1)[0]);
            $path = count($path) > 0 ? implode('/', $path) . '/' : '';
            $path = $path .  $path_name[0] . '/';
            $path_name = count($path_name) > 1 ? $path_name[1] : $path_name[0];
            $path = $path . $path_name;

            array_push($urls, array('type' => $item[0], 'url' => $rootUrl . $path . '.' . $item[0]));
         }
         
         return $urls;
      }

      /**
       * Вставляет страницу в template
       * @param {Array} - Модуль страницы
       * @return {String} - HTML код
       */
      private function template($page) {
         $data = array(
            'name' => $page['name'],
            'className' => $page['className'],
            'template' => $page['template'],
            'HTML' => $page['HTML']
         );
         $data = array_merge($data, $page['meta']);

         $template = $this -> getPage($page['template'], $data)['HTML'];
         $resource = $this -> getResource($this -> getCtx('_GC/page/resource'));

         return $this -> replaceParams($template, array('resource' => $resource));
      }

      /**
       * Получает каталог модуля по названию
       * @param name {String} - Название монуля
       * @return {String} - Каталог модуля
       */
      private function getPathModuleByName($name) {
         $path = explode('/', $name);
         $modul_name = array_splice($path, -1)[0];
         $path = count($path) > 0 ? implode('/', $path) . '/' : '';
         $path = $path .  explode('?', $modul_name)[0] . '/';

         return array('path' => $path, 'modul_name' => $modul_name);
      }

      /**
       * Возращает стандартную модель страницы
       * @return {Array} - Модель страницы
       */
      private function getDefaultPage() {
         GLOBAL $CONFIG;

         return array(
            'name' => '',                                            // Название модуля
            'className' => '',                                       // CSS класс страницы
            'resource' => array(),                                   // Ресурсы
            'HTML' => '',                                            // HTML код
            'meta' => $CONFIG -> meta,                               // Мета информация
            'template' => $CONFIG -> template                        // Главный модуль шаблона
         );
      }

      /**
       * Находит и обрабатывает логику в html
       * @param $html {String} - HTML код 
       * @return {String} - HTML код 
       */
      private function logicRender($html = '') {
         preg_match_all('/{{\?((.|\n)*?){{\?}}/im', $html, $logics);
         $logics = array_unique($logics[0]);
         sort($logics);

         // Переберем
         for ($i = 0; $i < count($logics); $i++) {
            $result = '';  // Результат

            preg_match_all('/{{\?((.|\n)*?)}}/im', $logics[$i], $blocksLogic);   // Блоки с логикой
            preg_match_all('/}}((.|\n)*?){{/im', $logics[$i], $blocksContext);   // Бллоки с данными

            // Переберем блоки с логикой
            for ($b = 0; $b < count($blocksLogic[0]); $b++) { 
               $code = '';
               if (mb_substr($blocksLogic[1][$b], 0, 1) == '?' && mb_strlen($blocksLogic[1][$b]) > 1) { // else if
                  $code = mb_substr($blocksLogic[1][$b], 1);
               } else if ($blocksLogic[1][$b] == '?') {                                                 // else
                  $result = $blocksContext[1][$b];
               } else if ($blocksLogic[1][$b]) {                                                        // if
                  $code = $blocksLogic[1][$b];
               }

               // Если есть результат
               // Или нет кода, значит все лог. блоки пройдены, есловия все не подошли
               if ($result || !$code) {
                  break;
               } else {
                  // Проверяем условие
                  $codeResult = false;
                  eval('if (' . $code . ') {$codeResult = true;}');
                  if ($codeResult) {
                     // Есловие подходит завершаем проверку
                     $result = $blocksContext[1][$b];
                     break;
                  }
               }
            }

            // Заменим HTML логику на результат
            $html = str_replace($logics[$i], $result, $html);
         }

         return $html;
      }

      /**
       * Возвращает базу контекста
       * @param name {String} - Имя контекста
       * @param isCreate {Boolean} - Происходт ли создание
       * @return {Array || *} - База или значение контекста
       */
      private function &getBaseCtxByName($name = '', $isCreate = false) {
         $base =& $this -> context;                                  // Вернем связанный объект
         $name = explode('/', $name);
         $len = $isCreate ? count($name) - 1 : count($name);

         for($i = 0; $i < $len; $i++) {
            if ($isCreate) {                                         // Если создание
               // Такого контекста нет
               if (!isset($base[$name[$i]]) || !is_array($base[$name[$i]])) {
                  $base[$name[$i]] = array();                        // Создадим
               }
            } else if (!isset($base[$name[$i]])) {
               return;
            }
            
            $base =& $base[$name[$i]];
         }

         return $base;
      }

      /**
       * Завершает сессию
       */
      private function endSession() {
         // Если были использованы логи, завершим
         if ($this -> getCtx('_GC/log/log_id') != null) {
            $this -> log('End session');
         }
      }
   }

   $GC = new GC();
   $GC -> start();
?>