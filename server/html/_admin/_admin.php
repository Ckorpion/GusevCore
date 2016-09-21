<?
   session_start();

   class _admin {
      public function init($page, $params) {
         GLOBAL $GC, $CONFIG;

         if ($CONFIG -> admin_password == '') {
            $page['HTML'] = $GC -> getPage('_admin/auth?setPassword')['HTML'];
         } else if ($_SESSION['auth'] != true) {
            $page = $GC -> getPage('_admin/auth');
         } else if (!isset($_GET['page'])) {
            $page = $GC -> getPage('_admin/main');
         } else {
            $page = $GC -> getPage('_admin/' . $_GET['page']);
         }

         $page['resource'] = array_merge($page['resource'], array(
            'js!_admin',
            'css!_admin'
         ));
         $page['template'] = '_admin/template';
         
         return $page;
      }
   }
?>