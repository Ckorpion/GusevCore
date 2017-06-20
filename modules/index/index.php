<?
   class index extends GC {
      public function init($page, $params) {
         $page['HTML'] = $this -> getPage($page['name'] . '?' . $page['name'], array(
            'version' => $this -> getCtx('_GC/version'),
            'date' => $this -> getCtx('_GC/versionDate') 
         ))['HTML'];

         return $page;
      }
   }
?>