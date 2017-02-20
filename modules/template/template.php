<?
   class template extends GC {
      public function init($page, $params) {
         $page['resource'] = array(
            'css!template'
         );

         $page['HTML'] = $this -> getPage($page['name'] . '?' . $page['name'], $params)['HTML'];

         return $page;
      }
   }
?>