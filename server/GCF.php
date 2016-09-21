<?
   /**
    * Класс GCF
    *
    * Список функций:
    *    isValid - Проверка существования переменной с пользовательской функцией
    *    numValue - Склонение числительных
    *    codeText - Кодировка текста
    *    peopleDate - Преобразование timestamp в понятную дату
    *    str_replace_once - Заменяет первое вхождение в строке подстроки
    *    xmlToArray - Переводить XML разметку в массив
    *    format - Вставляет в строку параметры
    */
   class GCF {
      /**
       * Проверка существования переменной с пользовательской функцией
       * @param value () - Переменная любого типа
       * @param callback (Function) - Пользовательская функция для проверки переменной
       * @param data (Array) - Дополнительные данные
       * @return (Boolean) - Существует и удовлетворяет ли условиям функция
       */
      public function isValid($value = false, $callback = false, $data = array()) {
         $result = false;
         if (isset($value)) {
            $result = $callback ? $callback($value, $data) : !!$value;
         }

         return $result;
      }

      /**
       * Склонение числительных
       * @param number (Integer) - Число
       * @param titles (Array) - Массив строк склоненных к 1, 2, 5
       * @return (String) - Склоненная строка
       */
      public function numValue($number, $titles) {
         $cases = array(2, 0, 1, 1, 1, 2);
         return $titles[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
      }

      /**
       * Кодировка текста
       * @param data (String OR Array) - Текст или массив текста, который нужно закодировать
       * @param type (String OR Array) - Тип кодировки
       *    toBase (String) - Перед записью в БД
       *    baseToText (String) - Перед выводом в текстовое поле из БД
       *    textToHtml (String) - Из текстового поля на экран
       *    baseToHtml (String) - Перед выводом в страницу из БД
       *    afterRequest (String) - После запроса с клиента
       *    (Array) - Набор символов и соответствующие замены
       * @param names (Array) - Список ключей для замены
       * @return (String OR Array) - Кодированная строка
       */
      public function codeText($data, $type, $names = false) {
         $_data = gettype($data) == 'string' ? array('text' => $data) : $data;
         $types = array(
            'toBase' => array(
               '<' => '&#060;',
               '>' => '&#062;',
               '"' => '&#034;',
               '\\' => '&#8260;'
            ),
            'baseToText' => array(
               '&#060;' => '<',
               '&#062;' => '>',
               '&#034;' => '"'
            ),
            'baseToHtml' => array(
               '&#060;' => '<',
               '&#062;' => '>',
               '&#034;' => '"',
               "\n" => '<br>'
            ),
            'textToHtml' => array(
               '<' => '&#060;',
               '<' => '&#062;',
               '"' => '&#034;',
               "\n" => '<br>'
            ),
            'afterRequest' => array(
               '&#043;' => '+',
               'iamp;' => '&'
            )
         );
         if (gettype($type) == 'array') {
            $types['user'] = $type;
            $type = 'user';
         }

         $keys = !$names ? array_keys($_data) : $names;
         $i = 0;
         while (isset($keys[$i])) {
            $_data[$keys[$i]] = $this -> format($_data[$keys[$i]], $types[$type]);
            $i++;
         }

         if (gettype($data) == 'string') $_data = $_data['text'];

         return $_data;
      }

      /**
       * Преобразование timestamp в понятную дату
       * @param date (Integer) - Дата в формате timestamp
       * @return (String) - Дата в понятном виде
       */
      public function peopleDate($date) {
         $str = '';
         $month = array('янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек');
         $newDate = time();
         $minutes = date('i', $date);

         if (date('j-n-Y', $newDate) == date('j-n-Y', $date)) {
            $str = 'Сегодня';
         } elseif (date('j-n-Y', $newDate) == date('j-n-Y', ($date + 86400))) {
            $str = 'Вчера';
         } else {
            $str = date('j', $date) . ' ' . $month[date('n', $date) - 1];
         }

         if (date('Y', $newDate) <> date('Y', $date))
            $str .= ' ' . date('Y', $date);

         if ($minutes < 10)
            $minutes = '0' . $minutes;

         $str .= ' в ' . date('H:i', $date);

         return $str;
      }

      /**
       * Заменяет первое вхождение в строке подстроки
       * @param search (String) - Подстрока, которую нужно заменить
       * @param replace (String) - Подстрока, на которую нужно заменить
       * @param subject (String) - Строка в которой производится замена
       * @return (String) - Строка после замены
       */
      public function str_replace_once($search, $replace, $subject) {
         $pos = strpos($subject, $search);
         if ($pos === false) return $subject;
         return substr_replace($subject, $replace, $pos, strlen($search));
      }

      /**
       * Переводить XML разметку в массив
       * @param xml (String OR XMLElement) - XML разметка
       * @param arr (Array OR Null) - Выходной массив
       * @return (Array) - Массив
       */
      public function xmlToArray($xml, $arr = array()) {
         if (is_string($xml)) {
            $xml = new SimpleXMLElement($xml);
         }

         foreach((array) $xml as $index => $node) {
            $arr[$index] = (is_object($node)) ? $this -> xmlToArray($node) : (string) $node;
            if (count($arr[$index]) == 0) {
               $arr[$index] = '';
            }
         }

         return $arr;
      }

      /**
       * Вставляет в строку параметры
       * @param str (String) - Строка
       * @param params (Array) - обекст с параметрами и значениями
       * @return (String) - Отформатированная строка
       */
      public function format($str, $params) {
         foreach ($params as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
               $str = str_replace($key, $value, $str);
            }
         }

         return $str;
      }
   }

   $GCF = new GCF();
?>