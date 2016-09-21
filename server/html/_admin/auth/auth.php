<?
   class _admin_auth {
      public function init($page, $params) {
         GLOBAL $GC;

         $page['HTML'] = $GC -> getPage('_admin/auth?auth', $params)['HTML'];

         return $page;
      }

      public function auth($data) {
         GLOBAL $CONFIG;
         session_start();

         if ($data['password'] == $CONFIG -> admin_password AND $CONFIG -> admin_password != '') {
            $_SESSION['auth'] = true;
            return array('auth' => true);
         } else {
            $_SESSION['auth'] = false;
            return array('auth' => false);
         }
      }

      public function logout($data) {
         GLOBAL $CONFIG;
         session_start();

         $_SESSION['auth'] = false;
      }
   }
?>