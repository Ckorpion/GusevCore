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
 *    pageReady - Вызываем функцию В подключенных скриптах при полной готовности страницы
 *    getPageName - Возращает идентификатор текущей страницы
 *
 * Список событий:
 *    onPageReady - Ajax cтраница загружена
 *    onPageLoading - Начата загрузка Ajax страницы
 */

GCAP = {
   /*
    * Инициализируем фунции, добавляет функции в GCF
    * @param config (object) - конфигурация с информацией о структуре сайта
    *    default_block (string) - полный идентификатор блока, куда загружать страницы
    *    default_path (string) - корневой каталог сайта, если сайт расположен в корне домена, то пусто
    */
   init: function(config) {
      // Устанавливаем конфигурацию
      GCAP.initConfig(config);

      // Событие перехода по истории страниц
      window.addEventListener('popstate', function(e) {
         if(e.state) GCAP.TP('/' + e.state.url, 2, false, e);
      }, false);

      // Подписываемся, на загрузку страниц, что бы обрабоать новые ссылки
      GCF.eventCar.subscribe('onPageReady', function() {
         GCAP.parseLink();
      });

      // Редакируем историю
      window.history.replaceState({'url': location.pathname.substring(1) + location.search}, '');

      // Сообщаем о загрузке страницы
      GCF.eventCar.send('onPageReady', {pageName: GCAP.getPageName()});

      // Сообщаем о готовности страницы
      GCAP.pageReady(GCAP.getPageName());
   },

   /*
    * Ajax переключение страниц
    * @param button (element or string) - ссылка(teg <a>) или строка с url
    * @param method (int) - тип истории 1: новая страница, 2: переход по истории
    * @param selector (string or false) - полный сеоектор блога для вставки страницы, еесли false, то будет GCAP.config.default_block
    * @param event (event) - события вызывающее функцию
    */
   TP: function(button, method, selector, event) {
      method = method || 1;
      // Проверяем доступно ли Ajax функции
      if (GCF.getXH() && (!event || (event && event.button == 0) || method == 2)) {
         selector = selector || GCAP.config.default_block;
         // Получаем URL
         url = typeof(button) == 'string' ? button : button.pathname + button.search + button.hash;
         GCF.eventCar.send('onPageLoading', {url: url});
         
         if(method == 1) {
            history.pushState({ 'url': url.slice(1)}, '', url);
         }
         // Запрашиваем страницу
         GCF.AJ('', {
            'method': 'api.getPage',
            'url': url,
            'template': document.body.dataset.template
         }, function(response, e) {
            if (e && !response.reload) {
               // Если новая страница, то сохраняем в истории
               // Устанавливаем страницу
               GCAP.setResponse(response, selector);
            } else {
               location.href = url;
            }
         }, true);
         if (event) {
            event.preventDefault ? event.preventDefault() : event.returnValue = false;
         }
         return false;
      }
   },

   /*
    * Перебираем ссылки с класом link для использования ajax
    */
   parseLink:  function() {
      GCF.elemsCall('a.link', function(link){
         link.addEventListener('click', function(event) {
            return GCAP.TP(this, 1, false, event);
         }, false);
         link.removeClassName('link');
      });
   },


   // СИСТЕМНЫЕ ФУНКЦИИ

   /*
    * Устанавливаем конфигурацию
    * @param config (object) - конфигурация с информацией о структуре сайта. См. GCAP.init();
    */
   initConfig: function(config) {
      config = config || {}; // Локальная конфигурация

      GCAP.config = {
         'default_block': '#GC-window', // Блок в который загружать страницы
         'default_path': '', // Корень сайта
         'default_page': 'index' // Страница по уполчанию(корень сайта)
      }
      if(config.default_path) config.default_path = config.default_path + '/';
      GCF.forEach(config, function(value, key) {
         GCAP.config[key] = config[key];
      });
   },

   /*
    * Применяем полученую страницу
    * @param response (string) - строка с json результат GCAP.TP()
    * @param selector (string) - полный сеоектор блога для вставки страницы, еесли false, то будет GCAP.config.default_block
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
      while (response.resource[i]) {
         resource = response.resource[i];
         if (!GCF.Q(types[resource.type] + resource.url + '"]'))  {
            if (resource.type == 'js') {
               var res = document.createElement('script');
               res.type  = 'text/javascript';
               res.src   = resource.url; 
               res.onload  = function() {
                  GCAP.pageReady(resource[1]);
               };
            } else {
               var res = document.createElement('link');
               res.rel  = 'stylesheet';
               res.type  = 'text/css';
               res.href   = resource.url;
            }

            document.head.appendChild(res);
         }
         i++;
      }

      // Сообщаем о загрузке страницы
      GCF.eventCar.send('onPageReady', {pageName: GCAP.getPageName()});
   },

   /*
    * Вызываем функцию В подключенных скриптах при полной готовности страницы
    * @param pageName (string) - url загружаемой страницы
    */
   pageReady: function(pageName) {
      pageName = pageName || GCAP.config.default_page;

      pageName = 'page_' + pageName;
      
      if (window[pageName]) {
         window[pageName].init();
      }
   },

   /*
    * Возращает идентификатор текущей страницы
    * return getPageName (string) - идентификатор текущей страницы
    */
   getPageName: function() {
      return location.pathname.substring(1).slice(GCAP.config.default_path.length) || GCAP.config.default_page;
   }
}