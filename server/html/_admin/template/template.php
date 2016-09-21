<?
   class _admin_template {
      public function init($page, $params) {
         GLOBAL $GC;

         $params['year'] = date('Y');

         $page['HTML'] = $GC -> getPage('_admin/template?template', $params)['HTML'];

         return $page;
      }
   }
?>