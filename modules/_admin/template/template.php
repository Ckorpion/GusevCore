<?
   class _admin_template extends GC  {
      public function init($page, $params) {
         GLOBAL $DB;
         
         $page['resource'] = array(
            'css!_admin/template'
         );

         $params['year'] = date('Y');

         $params['isGCOnlineVisible'] = false;
         $params['isGCLogsVisible'] = false;
         $params['isAuthVisible'] = false;

         if ($_SESSION['auth']) {
            $GCOnline = $this -> getAPI('_admin/GCOnline', 'GCOnline');
            $GCOnline -> auth('other');

            if (isset($GCOnline -> DB)) {
               $response = $GCOnline -> DB -> query('SHOW TABLES LIKE "GCOnline_scripts";') -> fetch_assoc();
               $params['isGCOnlineVisible'] = !!$response;
            }

            if (isset($DB)) {
               $response = $DB -> query('SHOW TABLES LIKE "GC_logs";') -> fetch_assoc();
               $params['isGCLogsVisible'] = !!$response;
            }

            $params['isAuthVisible'] = true;
         }

         $page['HTML'] = $this -> getPage('_admin/template?template', $params)['HTML'];

         return $page;
      }
   }
?>