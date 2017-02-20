<?
   class _admin_logs extends GC {
      public $API = array('reply');
      
      public function init($page, $params) {
         GLOBAL $GCF, $DB;

         $logs = '';
         $status = array(
            0 => 'Process',
            1 => 'Ready',
            2 => 'Aborted'
         );
         $join = '';
         $select = '';
         if ($DB -> query('SHOW TABLES LIKE "GCOnline_scripts";') -> fetch_assoc()) {
            $select = ', `scripts`.`title` AS `script_title`';
            $join = 'LEFT JOIN  `GCOnline_scripts` AS `scripts` ON `scripts`.`id` = `script_id`';
         }

         $sql = '
            SELECT 
               `GC_logs`.*
               ' . $select . '
            FROM 
               `GC_logs` 
            ' . $join . '
            ORDER BY 
               `time_start` DESC
            LIMIT 50
            ;
         ';
         $result = $DB -> query($sql);
         while ($log = $result -> fetch_assoc()) {
            $log['script_title'] = isset($log['script_title']) ? $log['script_title'] : 'GusevCore';
            $log['status'] = $log['script_id'] > 0 ? $status[$log['status']] : $log['status'];
            $log['time_start'] = $GCF -> peopleDate($log['time_start']);
            $log['log'] = $this -> renderLog($log['log']);
            $log['reply'] = $log['reply'] != '' ? '<a class="showReply" onclick="admin.logs.showReply(' . $log['id'] . ');">Show</a>' : '-';
            $logs .= $this -> getPage('_admin/logs?log', $log)['HTML'];
         }

         $page['HTML'] = $this -> getPage('_admin/logs?logs', array(
            'logs' => $logs
         ))['HTML'];

         return $page;
      }

      private function renderLog($log) {
         $log = explode(';', $log);
         $html = '';
         
         for ($i = 0; $i < count(log); $i++) { 
            $data = explode('|', $log[$i]);
            $html .= '
               <div class="lines">
                  <div class="line logText" style="width: 60px;">' . $data[0] . '</div>
                  <div class="line logText">' . $data[1] . '</div>
               </div>
            ';
         }

         return $html;
      }

      public function reply($data) {
         GLOBAL $DB;

         if (is_numeric($data['id'])) {
            $log =  $DB -> query('SELECT * FROM `GC_logs` WHERE `id` = ' . $data['id'] . ';') -> fetch_assoc();

            if (isset($log['id'])) {
               echo $log['reply'];
            }
         }
      }
   }
?>