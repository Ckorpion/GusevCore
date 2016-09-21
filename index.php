<?
   /*
    * GC - GusevCore
    *
    * Веб сервер для сайтов и приложений
    * http://gc.gusevgroup.ru
    * 
    * Основной скрипт index.php, принимающий все запросы
    * Если в GET или POST запросе содержится параметр "method" – будет выполнятся API функция
    * иначе открытие template с запрашиваемой страницы
    */
   header('Content-type: text/html; charset=UTF-8');
   require 'server/config.php';  // Конфигурация сайта
   require 'server/GCF.php';     // Дополнительные функции
   require 'server/GC.php';      // Ядро, содержит основную логику

   $_POST = file_get_contents('php://input');
   $_POST = json_decode($_POST, true);

   if (!isset($_POST['method']) AND !isset($_GET['method'])) {
      // Получаем адрес запрашиваемой страницы
      $url = parse_url($_SERVER['REQUEST_URI'])['path'];
      $path = substr($_SERVER['SCRIPT_NAME'], 0, -10);
      $_URL = $url;
      // Если сайт не в корне, обрежим каталог
      if ($path) {
        $url = str_replace($path . '/', '', $url);
      }
      $name = $GC -> getPageName($url);   // Получает название страницы по URL из config.php

      if (!$name) {
        header('HTTP/1.1 404 Not Found');
        exit();    
      }
      
      $page = $GC -> getPage($name);      // Получим запрашиваемую страницу
      $GC -> endSession();                // Сообщим, что все готово
      echo $GC -> template($page);        // Вставим её в teamplate и вернем
   } else {
      $params = isset($_GET['method']) ? $_GET : $_POST; // Получаем параметры запроса
      // method содержит название модуля и функции через точку
      $method = explode('.', $params['method']);
      $name = $method[0];

      // Проверяем корректрость method, системная функция init запрещена для вызова через api
      if (count($method) == 2 AND $method[1] != 'init') {
         // Определим тип api пользовательский или системный
         if ($method[0] != 'api') {
            $API = $GC -> getAPI($name);
         } else {
            require 'server/api.php';
            $API = new API();
         }
         $response = $API -> $method[1]($params);

         // Если ответ это массив - преобразуем в JSON
         if (gettype($response) == 'array') {
            $response = json_encode($response, JSON_UNESCAPED_UNICODE);
         }

         $response = $response ? $response : false;
      
         $GC -> endSession();                // Сообщим, что все готово
         // Вернем пользователю
         echo $response;
      }
   }
?>