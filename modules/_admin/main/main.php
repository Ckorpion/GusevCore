<?
   class _admin_main extends GC {
      public $API = array('initGCOnline');
      
      public function init($page, $params) {
         GLOBAL $DB;

         $update = $this -> getAPI('_admin/update');
         $GCOnline = $this -> getAPI('_admin/GCOnline', 'GCOnline');
         $GCOnline -> auth('other');

         $params['GCVersion'] = $this -> getCtx('_GC/version');
         $params['GCDate'] = $this -> getCtx('_GC/versionDate');
         $params['GCOnlineVersion'] = $GCOnline -> version;

         // Показывать ли кнопку обновления GusevCore
         $params['newGC'] = $update -> api('getGCVersion')['response'];
         $params['isVisibleNewGC'] = $params['newGC'] > $params['GCVersion'];

         // Показывать ли кнопку обновления GCOnline
         $params['newGCOnline'] = $update -> api('getGCOnlineVersion')['response'];
         $params['isVisibleNewGCOnline'] = $params['newGCOnline'] > $params['GCOnlineVersion'];

         $params['isVisibleSetDB'] = !isset($DB);
         $params['isVisibleSetGClogs'] = !$params['isVisibleSetDB'] && !$DB -> query('SHOW TABLES LIKE "GC_logs";') -> fetch_assoc();

         $params['isVisibleSetDBGCOnline'] = !$GCOnline -> DB;
         $params['isVisibleInitGCOnline'] = !$params['isVisibleSetDBGCOnline'] && !$GCOnline -> DB -> query('SHOW TABLES LIKE "GCOnline";') -> fetch_assoc();

         $page['HTML'] = $this -> getPage('_admin/main?main', $params)['HTML'];

         return $page;
      }

      public function initGCOnline() {
         echo $this -> getPage('_admin/main?GCOnlineInit')['HTML'];
      }
   }
?>