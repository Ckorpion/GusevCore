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
    */
   init: function(config) {
      // Устанавливаем конфигурацию
      GCAP.initConfig(config);

      // Событие перехода по истории страниц
      window.addEventListener('popstate', function(event) {
         if(event.state) {
            GCAP.TP(event.state.url, {method: 2, event: event});
         }
      }, false);

      // Подписываемся, на загрузку страниц, что бы обрабоать новые ссылки
      GCF.eventCar.subscribe('onPageReady', function() {
         GCAP.parseLink();
      });

      // Редакируем историю
      window.history.replaceState({'url': this.getUrl()}, '');

      // Сообщаем о загрузке страницы
      GCF.eventCar.send('onPageReady', {pageName: GCAP.getUrl()});
   },

   /*
    * Ajax переключение страниц
    * @param button {Element || String} - ссылка{teg <a>} или строка с url
    * @param params {Object} - параметры перехода
    *    method {Integer} - тип истории 1: новая страница, 2: переход по истории
    *    selector {String} - полный селектор блока для вставки страницы, если не указан, то будет GCAP.config.default_block
    *    event {event} - события вызывающее функцию
    *    callback {Function} - функция вызываемая после перехода
    *    data {Object} - отправляются POST запросом
    */
   TP: function(button, params) {
      params = params || {};
      params.event = params.event || event;
      params.method = params.method || 1;
      // Проверяем доступно ли Ajax функции
      if (GCF.getXH() && (!params.event || (params.event && !params.event.button) || params.method == 2)) {
         // Получаем URL
         var url = typeof button == 'string' ? GCAP.getUrl(button) : GCAP.getUrl(button.href);

         GCF.eventCar.send('onPageLoading', {url: url});
         
         if(params.method == 1) {
            history.pushState({'url': url}, '', url);
         }
         // Запрашиваем страницу
         GCF.AJ('', {
            'method': 'api.getPage',
            'url': url,
            'template': document.body.dataset.template,
            'params': params.data || {}
         }, function(response) {
            if (response && !response.reload) {
               // Если новая страница, то сохраняем в истории
               // Устанавливаем страницу
               GCAP.setResponse(response, params.selector || GCAP.config.default_block);
               scrollTo(0, 0);
               // Если есть функция обратного вызова, вызовем
               if (params.callback) {
                  params.callback(response);
               }
            } else {
               location.href = url;
            }
         }, true);
         if (params.event) {
            if (params.event.preventDefault) {
               params.event.preventDefault()
            } else {
               params.event.returnValue = false;
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
            return GCAP.TP(this, {method: 1, event: event});
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
         'default_page': 'index' // Страница по уполчанию(корень сайта)
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
      GCF.eventCar.send('onPageReady', {pageName: GCAP.getUrl()});
   },

   /*
    * Возращает идентификатор текущей страницы
    * param url {String} - url[Не обязательно]
    * return {String} - URL адрес
    */
   getUrl: function(url) {
      if (!this._a) {
         this._a = document.createElement('a');
      }
      this._a.href = url || window.location.href;
      return this._a.pathname + this._a.search + this._a.hash;
   }
}