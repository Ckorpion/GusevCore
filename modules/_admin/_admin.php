<?
   session_start();

   class _admin extends GC {
      public $API = array('auth', 'logout');

      public function init($page, $params) {
         GLOBAL $CONFIG;

         if (!$CONFIG -> admin_password) {
            $page['HTML'] = $this -> getPage('_admin/auth?setPassword')['HTML'];
         } else if (!$_SESSION['auth']) {
            $page['HTML'] = $this -> getPage('_admin/auth?auth')['HTML'];
         } else if (!isset($_GET['page'])) {
            $page = $this -> getPage('_admin/main');
         } else {
            $page = $this -> getPage('_admin/' . $_GET['page']);
         }

         $page['resource'] = array_merge($page['resource'], array(
            'js!_admin',
            'css!_admin'
         ));
         $page['template'] = '_admin/template';

         return $page;
      }

      public function auth($data) {
         GLOBAL $CONFIG;

         $result = ($data['password'] == $CONFIG -> admin_password AND $CONFIG -> admin_password);
         $_SESSION['auth'] = $result;
         
         return array('auth' => $result);
      }

      public function logout() {
         $_SESSION['auth'] = false;
      }
   }
?>