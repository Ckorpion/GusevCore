<?
   session_start();

   class _admin_update extends GC {
      public $API = array('upGC', 'upGCOnline', 'createTableLogs', 'initGCOnline');
      /*
       * Обновляет GusevCore
       */
      public function upGC() {
         if (!$_SESSION['auth']) {
            return;
         }

         $params = array('GCVersion' => $this -> getCtx('_GC/version'));
         $files = $this -> api('GCUpdate', $params);
         $this -> setup($files['response']);  

         $this -> log('GusevCore is upgraded!');
      }

      /*
       * Создает таблицу логов
       */
      public function createTableLogs() {
         GLOBAL $DB;

         if (!$_SESSION['auth']) {
            return;
         }

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
         $DB -> query($sql);

         $this -> log('Logs is ready!');
      }

      /*
       * Обновляет GCOnline
       */
      public function upGCOnline() {
         if (!$_SESSION['auth']) {
            return;
         }

         $files = $this -> api('GCOnlineUpdate');
         $this -> setup($files['response']);  

         $this -> log('GCOnline is upgraded!');
      }

      /*
       * Устанавливает GCOnline
       */
      public function initGCOnline($data) {
         if (!$_SESSION['auth']) {
            return;
         }

         if (isset($data['password']) && preg_match("/^[\da-z]+$/", $data['password'])) {
            $update = $this -> getAPI('_admin/update');
            $GCOnline = $this -> getAPI('_admin/GCOnline', 'GCOnline');
            $GCOnline -> auth('browser', $data['password']);
         }

         $this -> log('GCOnline is ready!');
      }

      /*
       * Выполняет API запрос к gusevcore.ru/api.php
       * @param method {String} - Название метода
       * @param params {Array} - Параметры
       * @return {Array} - Список папок и файлов для обновления
       */
      public function api($method, $params = array()) {
         GLOBAL $GCF, $CONFIG;

         if (!$_SESSION['auth']) {
            return;
         }

         $server = $GCF -> isValid($CONFIG -> update_server) ? $CONFIG -> update_server : 'test';
         $curl = curl_init();
         curl_setopt($curl, CURLOPT_MAXREDIRS, 1);
         curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
         curl_setopt($curl, CURLOPT_URL, 'http://gusevcore.ru/api.php?server=' . $server . '&method=' . $method);
         curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
         $response = curl_exec($curl);
         $response = json_decode($response, true);
         curl_close($curl);

         return $response;
      }

      /*
       * Выполняет установку 
       * @params files {Array} - Список папок и файлов для обновления
       */
      private function setup($files) {
         // Копирует файлы
         for ($i = 0; $i < count($files); $i++) { 
            $this -> copyFile($files[$i]);
         }
      }

      /*
       * Копирует файл
       * @param url {String} - Ссылка на файл
       */
      private function copyFile($url) {
         GLOBAL $GCF, $CONFIG;

         $server = $GCF -> isValid($CONFIG -> update_server) ? $CONFIG -> update_server : 'test';
         $params = '&url=' . $url;
         $link = 'http://gusevcore.ru/api.php?server=' . $server . '&method=getFile' . $params;
         copy($link, $url);
      }

   }
?>