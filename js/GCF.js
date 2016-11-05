/**
 * Класс GCF
 * Version: 3.15 of 15.10.2016
 * 
 * Сборник функций и прототипов
 * http://gusevcore.ru
 *
 * Дополнения:
 *    GCAP - для работы с Ajax структурой сайта
 *
 * Список прототипов:
 *    Array:
 *       unique - отбирает только уникальные элементы массива
 *    Element:
 *       removeClassName - удаляет класс элемента
 *       hasClassName - проверяет есть ли класс элемента
 *       addClassName - добавляет класс элементу
 *       queryParent - поиск родителя по селектору
 *       index - определяет индекс элемента в DOM
 *       Q - упрощенный Element.document.querySelector
 *    Document:
 *       Q - упрощенный Document.document.querySelector
 *    String:
 *       format - Заменяет ключим в строке
 *
 * Список функций:
 *    init - Инициализируем GCF
 *    initPrototype - Инициализируем прототипы
 *    Q - Вызов прототипа Document.Q 
 *    elemsCall - Перебор элементов по селектору
 *    forEach - перебор элементов объекта
 *    subscribe - Подписываем элементы на события по селектору
 *    random - Получает случайное целое число в диапазона
 *    peopleDate - Преобразование timestamp в понятный формат
 *    numValue - Склонение числительных
 *    parseURL - Парсим url
 *    getParam - Получаем get параметр
 *    getXH - Получаем xmlhttp для Ajax запросов
 *    AJ - Функция выполняющая Ajax запросы
 *    codeText - Кодировка текста
 *    strTranslit - Транслитерация текста
 *    clone - Клонирование данных
 *    eventCar - Объект для работы с eventCar:
 *       send - Отправляет событие
 *       subscribe - Подписывается на событие
 *    device - Объект для работы с устройством:
 *       fs - Объект для работы с Fullscreen:
 *          init - Инициализируем функции для работы с Fullscreen
 *          isFullScreen - Проверяет активен ли полноэкранный режим
 *          fullScreenChange - Вызывается, когда происходит смена статуса Fullscreen
 *          show - Запускает полноэкранный режим
 *          close - Закрывает полноэкранный режим
 *       isTouch - Проверяет сенсорное ли устройство
 *       media - Объект для работы с медиа устройствами:
 *          init - Инициализирует работу с медиаустройствами
 *          getVideo - Получить трасляцию с камеры
 *    cookie - Объект для работы с cookie
 *       get - Получает значение cookie
 *       set - Устанавливает cookie
 *       delete - Удаляет cookie параметр
 *    scroll - Объект для работы с scroll
 *       setSpeed - Установить скорость по умолчанию
 *       to - Плавно прокрутить
 *
 * Список событий:
 *    fullScreenChange - При смене статуса полноэкранного режима
 */

GCF = {
  version: '3.12',
   /**
    * Полная иницилизация GCF
    *    Прототипы
    *    Функции для работы с Fullscreen
    */
   init: function(){
      GCF.initPrototype();       // Прототипы
   },

   /**
    * Инициализируем прототипы
    */
   initPrototype: function() {
      Array.prototype.unique = function() {
         var 
            obj = {},
            str;

         for (var i = 0; i < this.length; i++) {
            str = this[i];
            obj[str] = true; 
         }

         return Object.keys(obj);
      }

      // @param сlassName (String) - имя класса
      Element.prototype.removeClassName = function(сlassName) {
         var clNames = this.className.split(' ');

         if (this.hasClassName(сlassName)) {
            clNames.splice(clNames.indexOf(сlassName), 1);
            this.className = clNames.join(' ');
         }
      }

      // @param className (String) - имя класса
      Element.prototype.hasClassName = function(className) {
         return (this.className.split(' ').indexOf(className) != -1);
      }

      // @param className (String) - имя класса
      Element.prototype.addClassName = function(className) {
         if (this.className != '') {
            if (!this.hasClassName(className))
               this.className += ' ' + className;
         } else {
            this.className = className;
         }
      }

      // @param selector (String) - селектор искомого родителя
      Element.prototype.queryParent = function(selector){
         var 
            parents = GCF.Q(selector, true),
            parent = this,
            found = 0,
            i = 0;

         while (parent.parentNode && found == 0){
            i = 0;
            while (parents[i] && found == 0) {
               if(parents[i] == parent.parentNode) {
                  found = parent.parentNode;
               }

               i++;
            }
            parent = parent.parentNode;
         }
         return found;
      }

      Element.prototype.index = function() {
         var 
            element = this,
            i = 0;

         while (element = element.previousElementSibling) 
            i++;

         return i;
      }

      // @param selector (String) - селектор искомого елемента
      // @param all (Boolean) - возращать ли все эементы, иначе первый
      Element.prototype.Q = function(selector, all) {
         var 
            all = all || false,
            elements = [];

         if (all) {
            elements = this.querySelectorAll(selector);
         }else{
            elements = this.querySelector( selector );
         }

         return elements;
      }

      Element.prototype.subscribe = this.eventCar.subscribe;
      Element.prototype.send = this.eventCar.send;

      // См. Element.prototype.Q
      Document.prototype.Q = Element.prototype.Q;

      /**
       * Заменяет ключим в строке
       * @param params (Object) - Параметры
       * @param clear (Boolean) - Отчистить оставшиеся. По умолчанию
       * @return (String) - Строка с замененными параметрами
       */
      String.prototype.format = function(params, clear) {
         clear = clear || true;
         var
            keys = Object.keys(params),
            i = 0,
            str = this;

         while (keys[i]) {
            str = str.replace(new RegExp('{{' + keys[i] + '}}', 'g'), params[keys[i]]);
            i++;
         }

         if (clear) {
            str = str.replace(/\{\{[^\{\}]+\}\}/g, '');
         }

         return str;
      }
   },
   
   /**
    * Вызов прототипа Document.Q
    * @param selector (String) - селектор искомого елемента
    * @param all (Boolean) - возращать ли все эементы, иначе первый
    * @return (Array of Elements or Element) - массив найденных элементов при all = true или первый элемент
    */ 
   Q: function(selector, all){
      return document.Q(selector, all);
   },
   
   /**
    * Перебор элементов по селектору
    * @param selector (String || Array) - селектор или массив искомых елементов
    * @param callback (Function) - функция вызываемая для каждого элемента. Параметры: element
    */
   elemsCall: function(selector, callback){
      var 
         elems = typeof selector == 'object' ? selector : GCF.Q(selector, true),
         i = 0;

      while (elems[i]) {
         callback(elems[i]);
         i++;
      }
   },

   /**
    * Перебирает элементы в объекте
    * @param obj (Object) - Объект
    * @param callback (Function) - Функция вызываемая для каждого элемента, с аргментами значения и ключа
    */
   forEach: function(obj, callback) {
      var
         keys = Object.keys(obj),
         i = 0;

      while (keys[i]) {
         callback(obj[keys[i]], keys[i]);
         i++;
      }
   },

   /**
    * Подписываем элементы на события по селектору
    * @param selector (String) - селектор элементов, кторые нужно подписать
    * @param eventName (String) - название функции
    * @param callback (Function) - функция вызываемая для каждого элемента. Параметры: eventName, element
    */
   subscribe: function(selector, eventName, callback) {
      GCF.elemsCall(selector, function (elem) {
         elem.addEventListener(eventName, function() {
            callback(eventName, this);
         });
      });
   },

   /**
    * Получает случайное целое число в диапазона
    * @param min (Integer) - минимальная граница диапазона
    * @param max (Integer) - максимальная граница диапазона
    * @return (Integer) - Случайное число
    */
   random: function(min, max) {
      var rand = min - 0.5 + Math.random() * (max - min + 1);
      rand = Math.round(rand);
      return rand;
   },
   
   /**
    * Преобразование timestamp в понятный формат
    * @param timestamp (Integer) время в секундах
    * @return (String) - дата время в понятном виде
    */
   peopleDate: function( timestamp ){
      var 
         str = '',
         month = ['янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'],
         date = new Date(timestamp * 1000),
         newDate = new Date(),
         minutes = date.getMinutes();

      // Формируем день и месяц
      if (date.getDate() == newDate.getDate() && date.getMonth() == newDate.getMonth() && date.getFullYear() == newDate.getFullYear()) {
         str = 'Сегодня';
      } else if(date.getDate() == newDate.getDate() - 1 && date.getMonth() == newDate.getMonth() && date.getFullYear() == newDate.getFullYear()) {
         str = 'Вчера';
      } else {
         str = date.getDate() + ' ' + month[date.getMonth()];
      }

      // Формируем год
      if (date.getFullYear() != newDate.getFullYear()) {
         str += ' ' + date.getFullYear();
      }

      // Формируем время
      if (minutes < 10) {
         minutes = '0' + minutes;
      }

      str += ' в ' + date.getHours() + ':' + minutes;

      return str;
   },


    /**
    * Склонение числительных
    * @param number (Integer) - Число
    * @param titles (Array) - Массив строк склоненных к 1, 2, 5
    * @return (String) - Склоненная строка
    */
   numValue: function(number, titles) {
      var cases = [2, 0, 1, 1, 1, 2];
      return titles[(number % 100 > 4 && number % 100 < 20) ? 2 : cases[Math.min(number % 10, 5)]];
   },

   /**
    * Парсим url
    * @param url (String) - адрес, который нужно распарсить
    * @return (Element) - распарсенная ссылка
    */
   parseURL: function(url) {
      var a = document.createElement('a');
      a.href = url;
      return a;
   },

   /**
    * Получаем get параметр
    * @param name (String) - название параметра
    */
   getParam: function(name, url) {
      name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
      url = GCF.parseURL(url).search || location.search;

      var 
         regex = new RegExp("[\\?&]" + name + "=([^&#]*)"), 
         results = regex.exec(url);

      return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
   },

   /**
    * Получаем xmlhttp для Ajax запросов
    * @return xmlhttp (Object) - необходимый объект для ajax запросов
    */
   getXH: function(){
      var xmlhttp;

      if (window.XMLHttpRequest) {
         xmlhttp = new XMLHttpRequest();
      } else {
         xmlhttp = new ActiveXObject('Microsoft.XMLHTTP');
      }

      return xmlhttp;
   },
   
   /**
    * Функция выполняющая Ajax запросы
    * @param url (String) - адрес серрверного скрипта, для запросам к GusevCore должна быть пуста
    * @param data (Object) - обект с данными
    * @param callback (Function) - функция вызывающаяся после получения результата. Параметры: response
    * @param json (Boolean) - преобразовать ли результат в JSON формат
    */
   AJ: function(url, data, callback, json){
      var xmlhttp = GCF.getXH();

      // Поддерживаются ли Ajax функци
      if(xmlhttp) {
         json = json || false;
         if (json !== true) {
            json = false;
         }

         callback = callback || false;
         is_open = true;

         url = url || '.';

         try {
            xmlhttp.open('POST', url, true);
         } catch (e) {
            is_open = false;
         }

         // Если удачно подключились
         if (is_open) {
            xmlhttp.setRequestHeader('Content-type', 'application/json;charset=UTF-8');

            // Делаем запрос
            xmlhttp.send(JSON.stringify(data));
            xmlhttp.onreadystatechange = function() {
               if (xmlhttp.readyState == 4) {
                  if (xmlhttp.status == 200) {
                     var response = xmlhttp.responseText;
                     if (json) {
                        response = JSON.parse( response );  
                     }

                     if (callback) {
                        callback(response, is_open);
                     }
                  } else if (callback) {
                     callback({errorCode: xmlhttp.status, errorText: xmlhttp.statusText});
                  }
               }
            }
         } else if (callback) {
            callback({errorCode: -1, errorText: 'Unknown error'});
         }
      }
   },

   /**
    * Кодировка текста
    * @param data (String OR Array) - Текст или массив текста, который нужно закодировать
    * @param type (String OR Array) - Тип кодировки
    *    toHTML (String) - Экранирует HTML теги
    *    fromText (String) - Из текстового поля на экран
    *    (Array) - Набор символов и соответствующие замены
    * @param names (Array) - Список ключей для замены
    * @return (String OR Array) - Кодированная строка
    */
   codeText: function(data, type, names) {
      names = names || false;
      var 
         _data = typeof data == 'string' ? {text: data} : data;
         types = {
            toHTML: {
               '<': '&#060;',
               '>': '&#062;'
            },
            fromText: {
               "\n": '<br>'
            }
         },
         i = 0,
         chars = [],
         char = 0;

      if (typeof type == 'object') {
         types.user = type;
         type = 'user';
      }

      keys = !names ? Object.keys(_data) : names;

      while (keys[i]) {
         GCF.forEach(types[type], function(value, key) {
            _data[keys[i]] = _data[keys[i]].toString().replace(new RegExp(key, 'g'), value);
         });
         i++;
      }

      if (typeof data == 'string') _data = _data.text;

      return _data;
   },

   /**
    * Транслитерация текста
    * @param text (Sttring) - Текст
    * @return (String) - Транслит текста
    */
   strTranslit: function(text) {
      return text.replace(/([а-яё])|([\s_-])|([^a-z\d])/gi,
         function (all, ch, space, words, i) {
            if (space || words) {
               return space ? '_' : '';
            }
            var 
               code = ch.charCodeAt(0),
               index = code == 1025 || code == 1105 ? 0 : code > 1071 ? code - 1071 : code - 1039,
               t = ['yo', 'a', 'b', 'v', 'g', 'd', 'e', 'zh',
                  'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p',
                  'r', 's', 't', 'u', 'f', 'h', 'c', 'ch', 'sh',
                  'shch', '', 'y', '', 'e', 'yu', 'ya'
               ]; 
            return t[index];
         }
      );
   },

   /**
    * Клонирование данных
    * @param data (Object || Array || String..) - Данные
    * @return (Object || Array || String..) - Копия данных
    */
   clone: function(data) {
      if (data && typeof data == 'object') {
         var newdata;

         if (Array.isArray(data)) {
            newdata = [];
         } else {
            newdata = {};
         }

         GCF.forEach(data, function(value, key) {
            newdata[key] = GCF.clone(value);
         });

         return newdata
      } else {
         return data;
      }

      return newdata;
   },

   /**
    * Объект для работы с eventCar
    */
   eventCar: {
      GCF_events: {},    // События

      /**
       * Отправляет событие
       * @param eventName (String) - название события
       * @param data (Object) - передаваемая информация
       */
      send: function(eventName, data) {
         var
            i = 0,
            result = [],
            response;

         if (!this.GCF_events) {
            this.GCF_events = {};
         }

         if (this.GCF_events[eventName]) {
            while (this.GCF_events[eventName][i]) {
               if (this.tagName) {
                  response = this.GCF_events[eventName][i].bind(this)(eventName, data);
               } else {
                  response = this.GCF_events[eventName][i](eventName, data);
               }
               if (response) {
                  result.push(response);
               }
               i++;
            }
         }

         return result;
      },

      /**
       * Подписывается на событие
       * @param eventName (String) - название события
       * @param callback (Function) - вызывается при событии. Параметры: eventName, data
       */
      subscribe: function(eventName, callback) {
         if (!this.GCF_events) {
            this.GCF_events = {};
         }
         if (this.GCF_events[eventName]) {
            this.GCF_events[eventName].push(callback);
         } else {
            this.GCF_events[eventName] = [callback];
         }
      }
   },

   /**
    * Объект для работы с устройством
    */
   device: {
      /**
       * Объект для работы с Fullscreen
       */
      fs: {
         /**
          * Инициализируем функции для работы с Fullscreen
          * Подписываемся на изменения для вызова GCF.device.fs.fullScreenChange()
          */
         init: function() {
            var box = document.documentElement;

            if (box.requestFullscreen) {
               document.addEventListener('fullscreenchange', function() {GCF.device.fs.fullScreenChange()});
            } else if (box.webkitRequestFullscreen) {
               document.addEventListener('webkitfullscreenchange', function() {GCF.device.fs.fullScreenChange()});
            } else if (box.mozRequestFullScreen) {
               document.addEventListener('mozfullscreenchange', function() {GCF.device.fs.fullScreenChange()});
            } else if (box.msRequestFullscreen) {
               document.addEventListener('MSFullscreenChange', function() {GCF.device.fs.fullScreenChange()});
            }
         },

         /**
          * Проверяет активен ли полноэкранный режим
          * @return (Boolean) - активность полноэкранного режима
          */
         isFullScreen: function() {
            var status;
            if (!document.fullScreenElement && !document.mozFullScreenElement && !document.webkitIsFullScreen && !document.msFullscreenElement) {
               status = false;
            } else {
               status = true;
            }

            return status;
         },

         /**
          * Вызывается, когда происходит смена статуса Fullscreen
          * Вызывает eventCar - fullScreenChange со статусом Fullscreen'а
          */
         fullScreenChange: function() {
            GCF.eventCar.send('fullScreenChange', {'status': GCF.device.fs.isFullScreen});
         },

         /**
          * Запускает полноэкранный режим
          * @param selector (String) полный селектор элемента, который нужно открыть в Fullscreen, иначе откроется весь документ
          */
         show: function(selector) {
            var box = selector ? GCF.Q(selector) : document.documentElement;

            if (box.requestFullscreen) {
               box.requestFullscreen();
            } else if (box.webkitRequestFullscreen) {
               box.webkitRequestFullscreen();
            } else if (box.mozRequestFullScreen) {
               box.mozRequestFullScreen();
            } else if (box.msRequestFullscreen) {
               box.msRequestFullscreen();
            }
         },

         /**
          * Закрывает полноэкранный режим
          */
         close: function () {
            if(document.exitFullscreen) {
               document.exitFullscreen();
            } else if(document.mozCancelFullScreen) {
               document.mozCancelFullScreen();
            } else if(document.webkitExitFullscreen) {
               document.webkitExitFullscreen();
            } else if(document.msExitFullscreen) {
               document.msExitFullscreen();
            }
         }
      },

      /**
       * Проверяет сенсорное ли устройство
       * @return (Boolean) - true если устройство сенсорное, иначе false
       */
      isTouch: function() {
         return (navigator.maxTouchPoints > 0 || navigator.msMaxTouchPoints > 0);
      },

      /**
       * Объект для работы с медиа устройствами
       */
      media: {
         /**
          * Инициализирует работу с медиаустройствами
          */
         init: function() {
            navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;
         
            if (navigator.mediaDevices && navigator.mediaDevices.enumerateDevices) {
                navigator.enumerateDevices = function(callback) {
                    navigator.mediaDevices.enumerateDevices().then(callback);
                };
            }

            if (!navigator.enumerateDevices && window.MediaStreamTrack && window.MediaStreamTrack.getSources) {
               navigator.enumerateDevices = window.MediaStreamTrack.getSources.bind(window.MediaStreamTrack);
            }
         },

         /**
          * Получить трасляцию с камеры
          * @param id (String) - Идентификатор видео устройства
          * @param handleVideo (Function || Element) - Функция которая получит трансляцию или элемент video
          * @param handleError (Function) - Функция вызываемая при ошибки
          */
         getVideo: function(id, handleVideo, handleError) {
            if (typeof handleVideo != 'function') {
               if (window.stream) {
                  handleVideo.src = null;
                  window.stream.getTracks()[0].stop();
               }
               handleVideo = function(stream) {
                  window.stream = stream;
                  this.src = window.URL.createObjectURL(stream);
               }.bind(handleVideo);
            }
            if (navigator.getUserMedia) {    
               navigator.getUserMedia({
                  video: {
                     optional: [{
                        sourceId: id
                     }],
                     deviceId: id
                  }
               }, handleVideo, handleError ? handleError : function() {});
            }
         }
      }
   },

   /**
    * Объект для работы с cookie
    */
   cookie: {
      /**
       * Получает значение cookie
       * @param name (String) - название cookie параметра
       * @return (String) - значение cookie параметра
       */
      get: function(name) {
         var matches = document.cookie.match(new RegExp(
            "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
         ));

         return matches ? decodeURIComponent(matches[1]) : undefined;
      },

      /**
       * Устанавливает cookie
       * @param name (String) - название cookie параметра
       * @param name (String) - значение cookie параметра
       * @param name (String) - опции для cookie
       */
      set: function(name, value, options) {
         options = options || {};
         value = encodeURIComponent(value);
         var 
            expires = options.expires,
            updatedCookie = name + '=' + value;

         // Время жизни cookie
         if (typeof expires == 'number' && expires) {
            var d = new Date();
            
            d.setTime(d.getTime() + expires * 1000);
            expires = options.expires = d;
         }
         if (expires && expires.toUTCString) {
            options.expires = expires.toUTCString();
         }

         // Перебираем и устанавливаем опции
         GCF.forEach(options, function(value, keys) {
            updatedCookie += '; ' + keys;
            if (value !== true) updatedCookie += '=' + value;
         });

         document.cookie = updatedCookie;
      },

      /**
       * Удаляет cookie параметр
       * @param name (String) - название cookie параметра
       */
      delete: function(name) {
         this.set(name, '', {expires: -1});
      }
   },

   /**
    * Объект для работы с scroll
    */
   scroll: {
      speed: 0.2, // Скорость по умолчанию

      /**
       * Установить скорость по умолчанию
       * @param speed (Float) - Скорость
       */
      setSpeed: function(speed) {
         this.speed = speed || this.speed;
      },

      /**
       * Плавно прокрутить до DOM элемента
       * @param selector (String) - Селектор элемента
       * @param speed (Float) - Скорость
       */
      to: function(selector, speed) {
         speed = speed || this.speed;
         var 
            startScroll = window.pageYOffset,
            finishScroll = GCF.Q(selector).getBoundingClientRect().top,
            start = null,
            step = function (time) {
               if (start === null) {
                  start = time;
               }
               var 
                  progress = time - start,
                  nowScroll = null;

               if (finishScroll < 0) {
                  nowScroll = Math.max(startScroll - progress / speed, startScroll + finishScroll);
               } else {
                  nowScroll = Math.min(startScroll + progress / speed, startScroll + finishScroll);
               }
               window.scrollTo(0, nowScroll);
               if (nowScroll != startScroll + finishScroll) {
                  requestAnimationFrame(step);
               }
            }
         
         requestAnimationFrame(step);
      }
   }
}

// Инициализируем GCF
GCF.init();