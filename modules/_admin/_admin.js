window.addEventListener('load', function() {
   GCAP.init();
});

GCF.eventCar.subscribe('onPageReady', function(event, page){
   if (!GCF.getParam('page') && GCF.Q('.GC-page.GC-page-_admin_main')) {
      GCF.Q('#GCFversion').innerHTML = GCF.version;
   }
})

admin = {
   auth: function() {
      var password = GCF.Q('#password');

      GCF.AJ('', {
         method: '_admin.auth',
         password: password.value
      }, function(response) {
         if (response.auth == true) {
            location.href = '_admin';
         } else {
            password.value = '';
            GCF.Q('#error').style.display = 'block';
         }
      }, true);
   },

   popup: {
      show: function(html) {
         GCF.Q('body').style.width = GCF.Q('body').clientWidth + 'px';
         GCF.Q('body').style.overflow = 'hidden';
         GCF.Q('#popup').innerHTML = html;
         GCF.Q('#popupBox').style.display = 'block';
         GCF.Q('#popupBox').scrollTop = 0;
      },

      close: function() {
         GCF.Q('body').style.width = '';
         GCF.Q('body').style.overflow = 'scroll';
         GCF.Q('#popupBox').style.display = 'none';
         GCF.Q('#popup').innerHTML = '';
      }
   },

   logout: function() {
      GCF.AJ('', {
         method: '_admin.logout'
      }, function(response) {
         location.href = '_admin';
      });
   },

   upgrade: function(service) {
      GCF.AJ('', {
         method: '_admin/update.' + service
      }, function(response) {
         location.href = '_admin';
      });
   },

   createTableLogs: function() {
      GCF.AJ('', {
         method: '_admin/update.createTableLogs'
      }, function(response) {
         location.href = '_admin';
      });
   },

   initGCOnline: function(act) {
      act = act || false;

      if (act) {
         var password = GCF.Q('#GCOnline_password');

         if (!/^[\da-z]+$/.test(password.value)) {
            password.addClassName('error');
         } else {
            GCF.AJ('', {
               method: '_admin/update.initGCOnline',
               password: password.value
            }, function(response) {
               location.href = '_admin';
            });
         }
      } else {
         GCF.AJ('', {
            method: '_admin/main.initGCOnline'
         }, function(html) {
            admin.popup.show(html);
         });
      }
   },

   logs: {
      showReply: function(id) {
         var html = '<iframe class="fullReply" src="?method=_admin/logs.reply&id=' + id + '"></iframe>';
         admin.popup.show(html);
      }
   },

   script: {
      act: function(method, id) {
         GCF.AJ('', {
            method: '_admin/scripts.act',
            id: id,
            type: method
         }, function(response) {
            GCAP.TP('_admin?page=scripts');
         });
      },

      add: function() {
         GCF.AJ('', {
            method: '_admin/scripts.getNewForm'
         }, function(html) {
            admin.popup.show(html);
         });
      },

      edit: function(id) {
         GCF.AJ('', {
            method: '_admin/scripts.edit',
            id: id
         }, function(html) {
            admin.popup.show(html);
         });
      },

      save: function(id) {
         id = id || 0;
         var 
            schedule_min = GCF.Q('#schedule_min').value,
            schedule_hour = GCF.Q('#schedule_hour').value,
            schedule_day = GCF.Q('#schedule_day').value,
            schedule_month = GCF.Q('#schedule_month').value,
            schedule_year = GCF.Q('#schedule_year').value,
            info = {
               type: GCF.Q('#type').value,
               title: GCF.Q('#title').value,
               description: GCF.Q('#description').value,
               terminate: GCF.Q('#terminate').value,
               importance: GCF.Q('[name=importance]:checked').value,
               rate: GCF.Q('#rate').value,
               afterTerminate: GCF.Q('[name=afterTerminate]:checked').value,
               password: GCF.Q('#password').value,
               reply: GCF.Q('#reply').checked,
               url: GCF.Q('#url').value,
               data: GCF.Q('#data').value
            },

            isError = false;

         GCF.Q('.newScriptBox #error').innerHTML = '';

         GCF.elemsCall('.newScriptBox .error', function(elem) {
            elem.removeClassName('error');
         });

         if (info.type == '') {
            GCF.Q('#type').addClassName('error');
            isError = true;
         }
         if (!admin.script.scheduleTest(schedule_min)) {
            GCF.Q('#schedule_min').addClassName('error');
            isError = true;
         }
         if (!admin.script.scheduleTest(schedule_hour)) {
            GCF.Q('#schedule_hour').addClassName('error');
            isError = true;
         }
         if (!admin.script.scheduleTest(schedule_day)) {
            GCF.Q('#schedule_day').addClassName('error');
            isError = true;
         }
         if (!admin.script.scheduleTest(schedule_month)) {
            GCF.Q('#schedule_month').addClassName('error');
            isError = true;
         }
         if (!admin.script.scheduleTest(schedule_year)) {
            GCF.Q('#schedule_year').addClassName('error');
            isError = true;
         }
         if (info.terminate == '' || info.terminate <= 0) {
            GCF.Q('#terminate').addClassName('error');
            isError = true;
         }
         if (info.importance == '') {
            info.importance = 0;
         }
         if (!(/^\d*$/.test(info.rate) && (info.rate > 0 || info.rate == ''))) {
            GCF.Q('#rate').addClassName('error');
            isError = true;
         }
         if (info.url == '') {
            GCF.Q('#url').addClassName('error');
            isError = true;
         }

         if (!isError) {
            info.schedule = schedule_min + ' ' + schedule_hour + ' ' + schedule_day + ' ' + schedule_month + ' ' + schedule_year;

            GCF.AJ('', {
               method: '_admin/scripts.save',
               info: info,
               id: id
            }, function(response) {
               if (response == 'ok') {
                  admin.popup.close();
                  GCAP.TP('_admin?page=scripts');
               } else {
                  GCF.Q('.newScriptBox #error').innerHTML = response;
               }
            });
         }
      },

      scheduleTest: function(timeItem) {
         var 
            schedule_all = /^[*]{1}$/,
            schedule_every = /^[*\/\d]{3,}$/,
            schedule_some = /^[\d,]+$/;

         return (schedule_all.test(timeItem) || schedule_every.test(timeItem) || schedule_some.test(timeItem));
      }
   }
}