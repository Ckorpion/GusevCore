<?
   /*
    * Класс CONFIG
    * Version 4.0 of 19.02.2017
    *
    * Конфигурация сайта
    */
   class CONFIG {
      private $db_host = 'localhost';     // Хост
      private $db_user = '';              // Пользователь
      private $db_password = '';          // Пароль
      private $db_name = '';              // Название

      public $admin_password = '';        // Пароль для обновления GC
      public $update_server = 'test';     // Тип обновлнения

      public $template = 'template';      // Главный модуль шаблона

      // Мета теги: title и description
      public $meta = array(
         'title' => 'GusevCore',
         'description' => '
            GusevCore – платформа для упрощения разработки универсальных проектов.
            Идеально подходит для создания сайтов, мобильных веб-приложений, приложений в социальных сетях. 
         '
      );

      // Заголовки устанавливаеются при инициализации GC
      public $headers = array(
         'Content-type: text/html; charset=UTF-8'
      );

      // Подключаются библиотеки при инициализации GC
      public $scripts = array(
      );

      // Страницы доступные пользователю
      // [0] - Регулярное вырожение, [1] - название модуля в server/html
      public $website_pages = array(
         array('_admin', '_admin'),    // Админ панель
         array('', 'index')
      );

      // Подключение БД
      private function initDB() {
         if ($this -> db_user) {
            $mysqli = new mysqli($this -> db_host, $this -> db_user, $this -> db_password, $this -> db_name);
            if (mysqli_connect_errno()) {
               echo ('Не удалось подключиться');
               exit();
            }
            $mysqli -> set_charset('utf8');

            return $mysqli;
         }
      }

      // Инициализация класса
      public function CONFIG() {
         GLOBAL $DB;
         // Подключим базу данных
         $DB = $this -> initDB();
      }
   }
   
   $CONFIG = new CONFIG();
?>