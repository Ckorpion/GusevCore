<?
   class _admin_main {
      public $API = array('initGCOnline');
      
      public function init($page, $params) {
         GLOBAL $GC, $DB;

         $update = $GC -> getAPI('_admin/update');
         $GCOnline = $GC -> getAPI('_admin/GCOnline', 'GCOnline');
         $GCOnline -> auth('other');

         $params['GCVersion'] = $GC -> version;
         $params['GCDate'] = $GC -> versionDate;
         $params['GCOnlineVersion'] = $GCOnline -> version;

         $newGC = $update -> api('getGCVersion')['response'];
         $newGCOnline = $update -> api('getGCOnlineVersion')['response'];
         
         if ($newGC != $GC -> version) {
            $params['newGC'] = '
               <div>
                  <div class="updateItem">Available new GusevCore version ' . $newGC . '</div>
                  <button onclick="admin.upgrade(\'GC\');">Upgrade GusevCore</button>
               </div>
            ';
         }
         if ($newGCOnline != $GCOnline -> version) {
            if ($GCOnline -> version) {
               $params['newGCOnline'] = '
                  <div>
                     <div class="updateItem">Available new GCOnline version ' . $newGCOnline . '</div>
                     <button onclick="admin.upgrade(\'GCOnline\');">Upgrade GCOnline</button>
                  </div>
               ';
            } else {
               $params['newGCOnline'] = '
                  <div>
                     <div class="updateItem">A new product GCOnline</div>
                     <a href="http://gusevcore.ru" targer="_blank"><button>More</button></a>
                  </div>
               ';
            }
         }

         if (!isset($DB)) {
            $params['setDB'] = '<div>To use the log, set the data of the database in server/config.php</div>';
         } else {
            if (!$DB -> query('SHOW TABLES LIKE "GC_logs";') -> fetch_assoc()) {
               $params['setGClogs'] = '
                  <div>
                     <div class="updateItem">To use logs, create a table "GC_logs"</div> 
                     <button onclick="admin.createTableLogs();">Create table</button>
                  </div>
               ';
            }
         }

         if (!$GCOnline -> DB) {
            $params['setDBGCOnline'] = '<div>To use the GCOnline, set the data of the database in server/html/_admin/GCOnline/GCOnline.php</div>';
         } else {
            if (!$GCOnline -> DB -> query('SHOW TABLES LIKE "GCOnline";') -> fetch_assoc()) {
               $params['initGCOnline'] = '
                  <div>
                     <div class="updateItem">To use GCOnline, must install</div> 
                     <button onclick="admin.initGCOnline();">Install GCOnline</button>
                  </div>
               ';
            }
         }

         $page['HTML'] = $GC -> getPage('_admin/main?main', $params, false, true)['HTML'];

         return $page;
      }

      public function initGCOnline() {
         GLOBAL $GC;

         echo $GC -> getPage('_admin/main?GCOnlineInit')['HTML'];
      }
   }
?>