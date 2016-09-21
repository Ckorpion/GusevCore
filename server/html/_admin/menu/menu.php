<?
   class _admin_menu {
      public function init($page, $params) {
         GLOBAL $GC, $DB;

         $GCOnline = $GC -> getAPI('_admin/GCOnline', 'GCOnline');
         $GCOnline -> auth('other');

         if ($_SESSION['auth']) {

            if (isset($GCOnline -> DB)) {
               $response = $GCOnline -> DB -> query('SHOW TABLES LIKE "GCOnline_scripts";') -> fetch_assoc();
               if ($response) {
                  $params['isGCOnlineVisible'] = '_admin_menu-boxItemShow';
               }
            }

            if (isset($DB)) {
               $response = $DB -> query('SHOW TABLES LIKE "GC_logs";') -> fetch_assoc();
               if ($response) {
                  $params['isGCLogsVisible'] = '_admin_menu-boxItemShow';
               }
            }

            $params['isAuthVisible'] = '_admin_menu-boxItemShow';

         }

         $page['HTML'] = $GC -> getPage('_admin/menu?menu', $params)['HTML'];

         return $page;
      }
   }
?>