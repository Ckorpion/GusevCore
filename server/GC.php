<?
   /**
    * Класс GC
    * Version 3.13 of 11.09.2016
    *
    * GusevCore – Веб сервер для сайтов и приложений
    * http://gusevcore.ru
    *
    * Список публичных функций:
    *    getPage - Подключает модуль(страницу)
    *    replaceParams - Заменяет параметры в коде
    *    getAPI  - Подключаем API модуля
    *    log - Записывает лог в базу
    *    getStartTime - Получаем время, когда скрипт был запущен
    *    getNowTime - Получаем текущее время
    *    getDuration - Получает время выполнения скрипта
    *    getPathByName - Получает каталог модуля по названию
    *    getPageName - Получает название страницы по её url адресу
    *    template - Вставляет страницу в template
    *    getResourceUrl - Преобразует ресурсы в ссылки
    *    initUserScripts - Инициализация пользовательских скриптов
    *    GC - Инициализация класса
    *    endSession - Завершает сессию
    *
    * Список приватных функций:
    *    getHTML - Получаем HTML блок
    *    paramsRender - Обрабатывает параметры для шаблона
    *    replaceComponents - Подключает компоненты описанные в HTML коде
    *    getDefaultPage - Возращает стандартную модель страницы
    *    getResource - Преобразует ресурсы в HTML теги
    */
   class GC {

      private $blocks = array();          // КЭШ HTML блоков для getHTML
      public $version = '3.13';           // Версия установки, используется при обновлении
      public $versionDate = '11.09.2016'; // Версия установки, используется при обновлении

      // Системные страницы
      public $system_pages = array(
         array('_admin', '_admin')
      );

      public $resource = array();   // Ресурсы
      private $isLogEnable = 0;     // Доступ к логам, 0 нужно узнать, -1 доступа нет, 1 доступ есть
      private $log_id = 0;          // Идентификатор лога, 0 - не создан, -1 - нет доступа к логам
      private $log = '';            // Текст лога всей сессии
      private $timeStart = 0;       // Время начала выполнения скрипта


      // ПУБЛИЧНЫЕ ФУНКЦИИ

      /**
       * Подключает модуль(страницу)
       * @param name (String) - Название страницы в server/html
       * @param params (Array) - Параметры модуля
       * @param noRender (Boolean) - Не производить замену параметров
       * @param clear (Boolean) - отчистить незаполненные параметры
       * @return (Array) - Результат модуля
       *    name - Название модуля
       *    resource - Набор ресурсов для отображения HTML страницы(скрипты и стили)
       *    HTML - Код страницы
       *    meta - Мета теги
       *       title - Заголовок страницы
       *       description - Описание страницы
       */
      public function getPage($name, $params = array(), $noRender = false, $clear = false) {
         GLOBAL $DB, $GCF;

         // Установим информацию о странице
         $page = $this -> getDefaultPage();
         $page['name'] = $name;
         $page['className'] = $GCF -> codeText($name, array('/' => '_'));

         // Получим каталог с модулем
         $path = $this -> getPathByName($name);
         $modul_name = $path['modul_name'];
         $path = $path['path'];

         // Проверим нужен нам шаблон или модуль
         if (count(explode('?', $modul_name)) > 1) {
            $page['HTML'] = $this -> getHTML($path . explode('?', $modul_name)[1], $params, $noRender, $clear);
         } else {
            $isConnect = include 'html/'. $path . $modul_name . '.php';
            if (!$isConnect) {
               return false;
            }
            $class_name = $GCF -> codeText(substr($path, 0, -1), array('/' => '_'));
            $modul = new $class_name();
            $newPage = $modul -> init($page, $params);
            $page = $newPage ? $newPage : $page;
         }
         // Смержим ресурсы
         $this -> resource = array_merge($this -> resource, $page['resource']);

         // Обработаем компоненты
         $page = $this -> replaceComponents($page, $noRender);

         return $page;
      }

      /**
       * Заменяет параметры в коде
       * @param html (String) - HTML код
       * @param params (Object) - объекст с парметрами и значениями
       * @param clear (Boolean) - отчистить незаполненные параметры
       * @return (String) - HTML с вставленными параметрами
       */
      public function replaceParams($html, $params, $clear = false) {
         GLOBAL $GCF;

         $html = $GCF -> format($html, $this -> paramsRender($params));

         if ($clear) {
            $html = preg_replace("/{{.+}}/im", '', $html);
         }

         return $html;
      }

      /**
       * Подключаем модуль
       * @param name (String) - Название модуля
       * @param className (String) - Название класса
       * @return (Class OR boolean) - Модуль или false если модуль не найден
       */
      public function getAPI($name, $className = false) {
         GLOBAL $GCF;

         $path = $this -> getPathByName($name);
         $modul_name = $path['modul_name'];
         $path = $path['path'];

         if (!$className) {
            $className = $GCF -> codeText($name, array('/' => '_'));
         }

         if (file_exists('server/html/'. $path . $modul_name . '.php')) {
            require_once('html/'. $path . $modul_name . '.php');
            return new $className();
         } else {
            return null;
         }
      }

      /**`
       * Создает лог
       * @param text (String) - Текст лога
       * @param isNew (Boolean) - Нужно ли создать как новый лог
       * @param status (Integer) - Статус лога, учитывается только у логов вне сессии
       * @param reply (String) - Результат, учитывается только у логов вне сессии
       * @param isSave (Boolean) - Нужно ли сохранить лог
       * @return (String || Boolean) - Лог со времением выполнения скрипта. Пример: 0.05|текст; Если доступа к логам нет, то false
       */
      public function log($text, $isNew = false, $status = 0, $reply = '', $isSave = true) {
         GLOBAL $DB, $GCF;

         // Проверим доступ
         if ($isSave && $this -> isLogEnable == 0) {
            if (!$DB || !($DB -> query('SHOW TABLES LIKE "GC_logs";') -> fetch_assoc())) {
               $this -> isLogEnable = -1;
               return false;
            } else {
               $this -> isLogEnable = 1;
            }
         } else if ($this -> isLogEnable == -1) {
            return false;
         }

         // Обработаем данные
         $log = $this -> getDuration() . '|' . $GCF -> codeText($text, 'toBase') . ';';
         $reply = $GCF -> codeText($reply, 'toBase');
         if (!is_numeric($status)) {
            return false;
         }
         if (!$isNew) {
            $status = 0;
            $reply = '';
            $this -> log .= $log;
         }

         // Если записывать не надо
         if (!$isSave) {
            return $log;
         }

         // Пишится лог в первые или новый
         if ($this -> log_id == 0 || $isNew) {
            $sql = '
               INSERT INTO `GC_logs` (
                  `time_start`,
                  `status`,
                  `log`,
                  `reply`
               )
               VALUES 
                  (
                     ' . $this -> getStartTime() . ',
                     ' . $status . ',
                     "' . $log . '",
                     "' . $reply . '"
                  )
               ;
            ';
            $DB -> query($sql);

            // Получим идентификатор лога если это лог сессии
            if (!$isNew) {
               $this -> log_id = $DB -> insert_id;
            }
            return $log;
         } else {
            $sql = '
               UPDATE 
                  `GC_logs` 
               SET 
                  `duration` = ' . $this -> getDuration() . ',
                  `log` = "' . $this -> log . '"
               WHERE 
                  `id` = ' . $this -> log_id . ' 
               LIMIT 1;
            ';
            $DB -> query($sql);

            return $log;
         }
      }

      /**
       * Получаем время, когда скрипт был запущен
       * @return (Double) - Начальное время
       */
      public function getStartTime() {
         return $this -> timeStart;
      }

      /**
       * Получаем текущее время
       * @return (double) - Текущее время
       */
      public function getNowTime() {
         return microtime(true);
      }

      /**
       * Получает время выполнения скрипта
       * @return (Double) - Время выполнения
       */
      public function getDuration() {
         return intval(($this -> getNowTime() - $this -> timeStart) * 1000) / 1000;
      }

      /**
       * Получает каталог модуля по названию
       * @param name (String) - Название монуля
       * @return (String) - Каталог модуля
       */
      public function getPathByName($name) {
         $path = explode('/', $name);
         $modul_name = array_splice($path, -1)[0];
         $path = count($path) > 0 ? implode('/', $path) . '/' : '';
         $path = $path .  explode('?', $modul_name)[0] . '/';

         return array('path' => $path, 'modul_name' => $modul_name);
      }

      /**
       * Получает название страницы по её url адресу
       * @param url (String) - URL адрес
       * @return (String || Boolean) - Название страницы или false, если такой нет
       */
      public function getPageName($url) {
         GLOBAL $CONFIG;

         $result = false;
         $website_path = substr($_SERVER['SCRIPT_NAME'], 0, -10);
         if (substr($website_path, 0, 1) == '/') {
            $website_path = substr($website_path, 1);
         }
         $website_path = str_replace('/', '\/', $website_path);
         $website_path != '' ? $path = '\/' . $website_path . '\/' : $path = '';

         $i = 0;
         while (isset($CONFIG -> website_pages[$i]) AND $result === false) {
            if ($path == '') $path = '\/';
            $pattern = '/^(' . $path . $CONFIG -> website_pages[$i][0] . '|' . $CONFIG -> website_pages[$i][0] . ')$/';
            if (preg_match($pattern, $url)) {
               $result = $CONFIG -> website_pages[$i][1];
            }

            $i++;
         }

         return $result;
      }

      /**
       * Вставляет страницу в template
       * @param (Array) - Модуль страницы
       * @return (String) - HTML код
       */
      public function template($page) {
         $data = array(
            'name' => $page['name'],
            'className' => $page['className'],
            'template' => $page['template'],
            'title' => $page['meta']['title'],
            'description' => $page['meta']['description'],
            'HTML' => $page['HTML']
         );

         $template = $this -> getPage($page['template'], $data)['HTML'];

         $resource = $this -> getResource($this -> resource);  // Получим ресурсы

         return $this -> replaceParams($template, array('resource' => $resource));
      }

      /**
       * Преобразует ресурсы в ссылки
       * @param resource (Array) - Ресурсы
       * @return (Array)
       *    type - Тип реусрса
       *    url - Ссылка на ресурс
       */
      public function getResourceUrl($resource) {
         $resource = array_unique($resource);
         sort($resource);
         $urls = array();
         $rootUrl = substr($_SERVER['SCRIPT_NAME'], 0, -10) . '/server/html/';
         $i = 0;

         while (isset($resource[$i])) {
            $item = explode('!', $resource[$i]);

            $path = explode('/', $item[1]);
            $path_name = explode('?', array_splice($path, -1)[0]);
            $path = count($path) > 0 ? implode('/', $path) . '/' : '';
            $path = $path .  $path_name[0] . '/';
            $path_name = count($path_name) > 1 ? $path_name[1] : $path_name[0];
            $path = $path . $path_name;

            array_push($urls, array('type' => $item[0], 'url' => $rootUrl . $path . '.' . $item[0]));
            $i++;
         }
         
         return $urls;
      }

      public function initUserScripts() {
         GLOBAL $CONFIG;

         // Подключим пользовательские библиотеки
         if (isset($CONFIG -> scripts)) {
            $scripts = $CONFIG -> scripts;
            $i = 0;
            while (isset($scripts[$i])) {
               require $scripts[$i];
               $i++;
            }
         }
      }

      /**
       * Завершает сессию
       */
      public function endSession() {
         // Если были использованы логи, завершим
         if ($this -> log_id != 0) {
            $this -> log('End session');
         }
      }


      // СПИСОК ПРИВАТНЫХ ФУНКЦИЙ

      /**
       * Получаем HTML блок
       * @param name (String) - Название блока
       * @param params(Array) - Массив с параметрами для блока
       * @param noRender (Boolean) - Не производить замену параметров
       * @param clear (Boolean) - отчистить незаполненные параметры
       * @return (String) - HTML блок
       */
      private function getHTML($path, $params = array(), $noRender, $clear) {
         GLOBAL $CONFIG;

         // Проверяем если блок в КЭШ, если есть то берем от туда
         if(isset($this -> blocks[$path])) {
            $block = $this -> blocks[$path];
         } else {
            // Скачиваем блок
            $block = file_get_contents('server/html/' . $path . '.html');
            // Сохраним в КЭШ
            if ($block) {
               $this -> blocks[$name] = $block;
            } else {
               return;
            }
         }

         if (!$noRender) {
            // Заменим параметры
            $block = $this -> replaceParams($block, $params, $clear);
         }

         return $block;
      }

      /**
       * Обрабатывает параметры для шаблона
       * @param params (Array) - Массив с параметрами
       * @param path (String) - Путь параметра
       * @return (Array) - Массив с обработанными параметрами
       */
      private function paramsRender($params, $path = '') {
         $newParams = array();

         foreach ($params as $key => $value) {
            if (is_array($value)) {
               $newParams = array_merge($newParams, $this -> paramsRender($value, $path . $key . '/'));
            } else {
               $newParams['{{' . $path . $key . '}}'] = $value;
            }
         }

         return $newParams;
      }

      /**
       * Подключает компоненты описанные в HTML коде
       * Вызывается из getHTML, при обработке страницы
       * @param page (Array) - Модуль страницы
       * @param noRender (Boolean) - Не производить замену параметров
       * @return (Array) - Модуль страницы
       */
      private function replaceComponents($page, $noRender) {
         GLOBAL $GCF;

         $component = '/\<component\spath\=\"([^\"]+)\"\>((.|\n)*?)\<\/component\>/im';
         preg_match_all($component, $page['HTML'], $components);
         
         $i = 0;
         while (isset($components[1][$i])) {
            if ($components[2][$i] != '') {
               $params = $GCF -> xmlToArray('<xml>' . $components[2][$i] . '</xml>');
            } else {
               $params = array();
            }
            $block = $this -> getPage($components[1][$i], $params, $noRender);
            if (!$block) {
               $block = array('HTML' => '');
            } else {
               $this -> resource = array_merge($this -> resource, $block['resource']);
            }
            $page['HTML'] = $GCF -> str_replace_once($components[0][$i], $block['HTML'], $page['HTML']);

            $i++;
         }

         return $page;
      }

      /**
       * Возращает стандартную модель страницы
       * @return (Array) - Модель страницы
       */
      private function getDefaultPage() {
         GLOBAL $CONFIG;

         return array(
            'name' => '',                       // Название модуля
            'className' => '',                  // CSS класс страницы
            'resource' => array(),              // Ресурсы
            'HTML' => '',                       // HTML код
            'meta' => $CONFIG -> meta,          // Мета информация
            'template' => $CONFIG -> template   // Главный модуль шаблона
         );
      }

      /**
       * Преобразует ресурсы в HTML теги
       * @param resource (Array of String) - Набор JS и CSS ресурсов
       * @return (String) - HTML код
       */
      private function getResource($resource) {
         $urls = $this -> getResourceUrl($resource);
         $html = '';

         $i = 0;
         while (isset($urls[$i])) {
            if ($urls[$i]['type'] == 'js') {
               $html .= '<script type="text/javascript" src="' . $urls[$i]['url'] . '"></script>';
            } else {
               $html .= '<link type="text/css" rel="stylesheet" href="' . $urls[$i]['url'] . '">';
            }
            $i++;
         }
         
         return $html;
      }

      /**
       * Инициализация класса
       */
      public function GC() {
         GLOBAL $CONFIG, $GCF;

         // Начнем отчет времени
         $this -> timeStart = $this -> getNowTime();
         $this -> log('Start session', false, 0, '', false);

         // Получим полный список страниц сайта
         if (!isset($CONFIG -> website_pages)) $CONFIG -> website_pages = array();
         $CONFIG -> website_pages = array_merge($CONFIG -> website_pages, $this -> system_pages);

         // Установим пользовательские заголовки
         if (isset($CONFIG -> headers)) {
            $headers = $CONFIG -> headers;
            $i = 0;
            while (isset($headers[$i])) {
               header($headers[$i]);
               $i++;
            }
         }
      }
   }

   $GC = new GC();
   $GC -> initUserScripts();
?>