<?
   class template {
      public function init($page, $params) {
         GLOBAL $GC;

         $page['HTML'] = $GC -> getPage($page['name'] . '?' . $page['name'], $params)['HTML'];

         return $page;
      }
   }
?>