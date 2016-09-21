<?
   class index {
      public function init($page) {
         GLOBAL $GC;

         $page['resource'] = array(
            'css!index'
         );

         $page['HTML'] = $GC -> getPage($page['name'] . '?' . $page['name'], array(
            'version' => $GC -> version,
            'date' => $GC -> versionDate
         ))['HTML'];

         return $page;
      }
   }
?>