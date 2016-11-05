<?
   class template {
      public function init($page, $params) {
         GLOBAL $GC;

         $page['resource'] = array(
            'css!template'
         );

         $page['HTML'] = $GC -> getPage($page['name'] . '?' . $page['name'], $params)['HTML'];

         return $page;
      }
   }
?>