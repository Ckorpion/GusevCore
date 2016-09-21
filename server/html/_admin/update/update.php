<?
   session_start();

   class _admin_update {
      /*
       * Обновляет GusevCore
       */
      public function GC() {
         GLOBAL $GC;

         if ($_SESSION['auth'] == true) {
            $params = array('GCVersion' => $GC -> version);
            $files = $this -> api('GCUpdate', $params);
            $this -> setup($files['response']);  
         }

         $GC -> log('GusevCore is upgraded!');
      }

      /*
       * Создает таблицу логов
       */
      public function createTableLogs() {
         GLOBAL $GC, $DB;

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

         $GC -> log('Logs is ready!');
      }

      /*
       * Обновляет GCOnline
       */
      public function GCOnline() {
         GLOBAL $GC;

         if ($_SESSION['auth'] == true) {
            $files = $this -> api('GCOnlineUpdate');
            $this -> setup($files['response']);  
         }

         $GC -> log('GCOnline is upgraded!');
      }

      /*
       * Устанавливает GCOnline
       */
      public function initGCOnline($data) {
         GLOBAL $GC;

         if ($_SESSION['auth'] == true && isset($data['password']) && preg_match("/^[\da-z]+$/", $data['password'])) {
            $update = $GC -> getAPI('_admin/update');
            $GCOnline = $GC -> getAPI('_admin/GCOnline', 'GCOnline');
            $GCOnline -> auth('browser', $data['password']);
         }

         $GC -> log('GCOnline is ready!');
      }

      /*
       * Выполняет API запрос к gusevcore.ru/api.php
       * @param method (String) - Название метода
       * @param params (Array) - Параметры
       * @return (Array) - Список папок и файлов для обновления
       */
      public function api($method, $params = array()) {
         GLOBAL $GCF, $CONFIG;

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
       * @params files (Array) - Список папок и файлов для обновления
       */
      private function setup($files) {
         // Копирует файлы
         $i = 0;
         while (isset($files[$i])) {
            $this -> copyFile($files[$i]);
            $i++;
         }
      }

      /*
       * Копирует файл
       * @param url (String) - Ссылка на файл
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