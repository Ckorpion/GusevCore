<?
   /*
    * Класс API
    *
    * Системные функции для работы клиента
    * Список функций:
    *    getPage - Получает страницу сайта
    */
   CLASS API {
      /*
      * Получает страницу сайта
      * @param data (Array) - Параметры для страницы
      *    url (String) - Адрес страницы
      *    name (String) - Название страницы
      *    noRender (Boolean) - Не производить замену параметров
      *    params (Array) - Параметры
      * @param noRender (Boolean) - Не производить замену параметров
      * @return (Array) - Страница
      */
      public function getPage($data = array()) {
         GLOBAL $GC, $CONFIG, $_URL;

         $name = false;
         $noRender = isset($data['noRender']) ? $data['noRender'] : false;
         $params = isset($data['params']) ? $data['params'] : array();

         if  (isset($data['name'])) {
            $name = $data['name'];
         } else if (isset($data['url'])) {
            $url = parse_url($data['url']);
            $page_name = isset($url['path']) ? $url['path'] : '';
            $_URL = $page_name;

            $name = $GC -> getPageName($page_name);    // Получим название страницы в server/html по url
         }

         if (!$name) {
            return false;
         }

         // Поместим GET параметры в глобальную область
         if(isset($url['query'])) parse_str($url['query'], $_GET);

         $page = $GC -> getPage($name, $params, $noRender);     // Подключаем запрашиваемый модуль

         $page['resource'] = $GC -> getResourceUrl($page['resource']);

         if ($page['template'] != $data['template']) {
            $page['reload'] = true;
         }

         return $page;
      }
   }
?>