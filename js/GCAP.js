/*
 * Класс GCAP
 *
 * http://gusevcore.ru
 *
 * Для работы с Ajax структурой сайта
 *
 * Список функций:
 *    init - Инициализируем фунции
 *    TP - Ajax переключение страниц
 *    parseLink - Перебираем ссылки с класом link для использования ajax
 *
 * Список системных функций:
 *    initConfig - Устанавливаем конфигурацию
 *    setResponse - Применяем полученую страницу
 *    getPageName - Возращает идентификатор текущей страницы
 *
 * Список событий:
 *    onPageReady - Ajax cтраница загружена
 *    onPageLoading - Начата загрузка Ajax страницы
 */

GCAP = {
   /*
    * Инициализируем фунции, добавляет функции в GCF
    * @param config {object} - конфигурация с информацией о структуре сайта
    *    default_block {string} - полный идентификатор блока, куда загружать страницы
    *    default_path {string} - корневой каталог сайта, если сайт расположен в корне домена, то пусто
    */
   init: function(config) {
      // Устанавливаем конфигурацию
      GCAP.initConfig(config);

      // Событие перехода по истории страниц
      window.addEventListener('popstate', function(event) {
         if(event.state) {
            GCAP.TP(event.state.url, 2, false, event);
         }
      }, false);

      // Подписываемся, на загрузку страниц, что бы обрабоать новые ссылки
      GCF.eventCar.subscribe('onPageReady', function() {
         GCAP.parseLink();
      });

      // Редакируем историю
      window.history.replaceState({'url': this.getPageName() + location.search}, '');

      // Сообщаем о загрузке страницы
      GCF.eventCar.send('onPageReady', {pageName: GCAP.getPageName()});
   },

   /*
    * Ajax переключение страниц
    * @param button {Element || String} - ссылка{teg <a>} или строка с url
    * @param method {Integer} - тип истории 1: новая страница, 2: переход по истории
    * @param selector {String || Boolean} - полный селектор блока для вставки страницы, если false, то будет GCAP.config.default_block
    * @param event {event} - события вызывающее функцию
    */
   TP: function(button, method, selector, event) {
      method = method || 1;
      // Проверяем доступно ли Ajax функции
      if (GCF.getXH() && (!event || (event && !event.button) || method == 2)) {
         selector = selector || GCAP.config.default_block;
         // Получаем URL
         var url = typeof button == 'string' ? button : GCAP.getPageName(button.pathname) + button.search + button.hash;

         GCF.eventCar.send('onPageLoading', {url: url});
         
         if(method == 1) {
            history.pushState({'url': url}, '', url);
         }
         // Запрашиваем страницу
         GCF.AJ('', {
            'method': 'api.getPage',
            'url': url,
            'template': document.body.dataset.template
         }, function(response) {
            if (response && !response.reload) {
               // Если новая страница, то сохраняем в истории
               // Устанавливаем страницу
               GCAP.setResponse(response, selector);
               scrollTo(0, 0);
            } else {
               location.href = url;
            }
         }, true);
         if (event) {
            if (event.preventDefault) {
               event.preventDefault()
            } else {
               event.returnValue = false;
            }
         }
         return false;
      }
   },

   /*
    * Перебираем ссылки с класом link для использования ajax
    */
   parseLink:  function() {
      GCF.elemsCall('a.link', function(link) {
         link.addEventListener('click', function(event) {
            return GCAP.TP(this, 1, false, event);
         }, false);
         link.removeClassName('link');
      });
   },


   // СИСТЕМНЫЕ ФУНКЦИИ

   /*
    * Устанавливаем конфигурацию
    * @param config {Object} - конфигурация с информацией о структуре сайта. См. GCAP.init();
    */
   initConfig: function(config) {
      config = config || {}; // Локальная конфигурация

      GCAP.config = {
         'default_block': '#GC', // Блок в который загружать страницы
         'default_path': '', // Корень сайта
         'default_page': 'index' // Страница по уполчанию(корень сайта)
      }
      if(config.default_path) {
         config.default_path += '/';
      }
      GCF.forEach(config, function(value, key) {
         GCAP.config[key] = config[key];
      });
   },

   /*
    * Применяем полученую страницу
    * @param response {String} - строка с json результат GCAP.TP()
    * @param selector {String} - полный сеоектор блога для вставки страницы, еесли false, то будет GCAP.config.default_block
    */
   setResponse: function(response, selector) {
      var 
         i = 0,
         resource = [],
         types = {css: 'link[href="', js: 'script[src="'};

      document.title = response.meta.title;

      // Помещаем полученный HTMl в блок
      GCF.Q(selector).innerHTML = '<div class="GC-page GC-page-' + response.className + '">' + response.HTML + '</div>';

      // Подключаем ресурсы
      for (var i = 0; i < response.resource.length; i++) {
         resource = response.resource[i];
         if (!GCF.Q(types[resource.type] + resource.url + '"]'))  {
            if (resource.type == 'js') {
               var res = document.createElement('script');
               res.type = 'text/javascript';
               res.src = resource.url; 
            } else {
               var res = document.createElement('link');
               res.rel = 'stylesheet';
               res.type = 'text/css';
               res.href = resource.url;
            }

            document.head.appendChild(res);
         }
      }

      // Сообщаем о загрузке страницы
      GCF.eventCar.send('onPageReady', {pageName: GCAP.getPageName()});
   },

   /*
    * Возращает идентификатор текущей страницы
    * param url {String} - url[Не обязательно]
    * return getPageName (string) - идентификатор текущей страницы
    */
   getPageName: function(url) {
      url = url || location.pathname
      return url.substring(1).slice(GCAP.config.default_path.length) || GCAP.config.default_page;
   }
}