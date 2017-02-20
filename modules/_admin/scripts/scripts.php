<?
   class _admin_scripts extends GC {
      public $API = array('act', 'getNewForm', 'edit', 'save');

      public function init($page, $params) {
         GLOBAL $GCF;

         $GCOnline = $this -> getAPI('_admin/GCOnline', 'GCOnline');
         $GCOnline -> auth('other');
         $countAll = 0;
         $countActive = 0;
         $countAborted = 0;

         $scripts = '';
         $status = array(
            -3 => 'Aborted',
            -2 => 'Pause',
            -1 => 'Reload',
            0 => 'Expectation',
            1 => 'Process'
         );
         $sql = 'SELECT * FROM `GCOnline_scripts`;';
         $result = $GCOnline -> DB -> query($sql);
         while ($script = $result -> fetch_assoc()) {
            $script['config'] = json_decode($script['config'], true);
            $script['schedule'] = $script['config']['schedule'];
            $script['status_str'] = $status[$script['status']];
            $script['statusTime'] = $script['statusTime'] > 0 ? $GCF -> peopleDate($script['statusTime']) : '';
            $script['isVisibleButtonStart'] = $script['status'] <= -2;
            $script['isVisibleButtonStop'] = $script['status'] == -1 || $script['status'] == 0;
            $script['isVisibleButtonReload'] = $script['status'] == 0;

            $scripts .= $this -> getPage('_admin/scripts?script', $script)['HTML'];

            $countAll++;
            if ($script['status'] >= -1) {
               $countActive++;
            }
            if ($script['status'] == -3) {
               $countAborted++;
            }
         }

         $countAbortedHTML = '';
         if ($countAborted > 0) {
            $countAbortedHTML = ', <span style="color: #ff0000;">aborted: ' . $countAborted . '</span>';
         }

         $page['HTML'] = $this -> getPage('_admin/scripts?scripts', array(
            'scripts' => $scripts,
            'countAll' => $countAll,
            'countActive' => $countActive,
            'countAbortedHTML' => $countAbortedHTML
         ))['HTML'];

         return $page;
      }

      public function act($data) {
         $statusByType = array(
            'start' => '0',
            'stop' => '-2',
            'reload' => '-1',
            'delete' => 'other'
         );

         if (is_numeric($data['id']) && isset($statusByType[$data['type']])) {
            $GCOnline = $this -> getAPI('_admin/GCOnline', 'GCOnline');
            $GCOnline -> auth('other');

            if ($statusByType[$data['type']] != 'other') {
               $sql = 'UPDATE `GCOnline_scripts` SET `status` = ' . $statusByType[$data['type']] . ' WHERE `id` = ' . $data['id'] . ';';
            } else if ($data['type'] == 'delete') {
               $sql = 'DELETE FROM `GCOnline_scripts` WHERE `id` = ' . $data['id'];
            }
            $GCOnline -> DB -> query($sql);
         }
      }

      public function getNewForm() {
         return $this -> getPage('_admin/scripts?new', array(
            'caption' => 'Add script',
            'terminate' => 120,
            'isImportance0' => 'checked',
            'isAfterTerminate0' => 'checked',
            'button_caption' => 'Create',
            'examplePath' => $_SERVER['SCRIPT_FILENAME']
         ), false, true)['HTML'];
      }

      public function edit($data) {
         if (is_numeric($data['id'])) {
            $GCOnline = $this -> getAPI('_admin/GCOnline', 'GCOnline');
            $GCOnline -> auth('other');
            $script =  $GCOnline -> DB -> query('SELECT * FROM `GCOnline_scripts` WHERE `id` = ' . $data['id'] . ';') -> fetch_assoc();

            if (isset($script['id'])) {
               $config = json_decode($script['config'], true);
               $schedule = explode(' ', $config['schedule']);
               if (!isset($config['afterTerminate'])) {
                  $config['afterTerminate'] = 0;
               }

               $info = array_merge($script, $config, array(
                  'caption' => 'Edit script',
                  'schedule_min' => $schedule[0],
                  'schedule_hour' => $schedule[1],
                  'schedule_day' => $schedule[2],
                  'schedule_month' => $schedule[3],
                  'schedule_year' => $schedule[4],
                  ('isImportance' . $config['importance']) => 'checked',
                  ('isAfterTerminate' . $config['afterTerminate']) => 'checked',
                  'isReply' => (isset($config['reply']) && $config['reply'] == true) ? 'checked' : '',
                  'button_caption' => 'Save',
                  'examplePath' => $_SERVER['SCRIPT_FILENAME']
               ));

               return $this -> getPage('_admin/scripts?new', $info)['HTML'];
            }
         }
      }

      public function save($data) {
         GLOBAL $GCF;

         $GCOnline = $this -> getAPI('_admin/GCOnline', 'GCOnline');
         $GCOnline -> auth('other');

         $result = $this -> validInfo($data);
         if ($result === true) {
            if (!isset($data['info']['title']) || $data['info']['title'] == '') {
               $data['info']['title'] =  $data['info']['type'];
            }

            $data['info'] = $GCF -> codeText($data['info'], 'toBase');

            if ($data['id'] == 0) {
               return $this -> create($data['info']);
            } else {
               return $this -> update($data['id'], $data['info']);
            }
         }

         return $result;
      }

      private function validInfo($data) {
         if (!(isset($data['id']) && is_numeric($data['id']))) {
            return 'Incorrect script ID';
         }
         if (!isset($data['info'])) {
            return 'Incorrect information';
         }
         $info = $data['info'];
         if (!(isset($info['type']) && $info['type'] != '')) {
            return 'Incorrect Type';
         }
         $valid = "([*]{1}|[*\/\d]{3,}|[\d,]+)";
         if (!preg_match("/^$valid\s$valid\s$valid\s$valid\s$valid$/", $info['schedule'])) {
            return 'Incorrect Schedule';
         }
         if (!(isset($info['terminate']) && is_numeric($info['terminate']) && $info['terminate'] > 0)) {
            return 'Incorrect Terminate';
         }
         $importances = array(0, 1, 2);
         if (!(isset($info['importance']) && in_array($info['importance'], $importances))) {
            return 'Incorrect Importance';
         }
         if (!(!isset($info['rate']) || $info['rate'] == '' || (isset($info['rate']) && is_numeric($info['rate']) && $info['rate'] > 0))){
            return 'Incorrect Rate';
         }
         $afterTerminates = array(0, 1, 2);
         if (!(!isset($info['afterTerminate']) || (isset($info['afterTerminate']) && in_array($info['afterTerminate'], $afterTerminates)))) {
            return 'Incorrect AfterTerminate';
         }
         if (!(!isset($info['reply']) || (isset($info['reply']) && is_bool($info['reply'])))) {
            return 'Incorrect Reply';
         }
         if (!(isset($info['url']) && $info['url'] != '')) {
            return 'Incorrect Url';
         }

         return true;
      }

      private function infoToFullData($info) {
         if (!isset($info['description'])) {
            $info['description'] = '';
         }

         $config = array(
            'schedule' => $info['schedule'],
            'terminate' => $info['terminate'],
            'importance' => $info['importance']
         );

         if (isset($info['rate']) && $info['rate'] != '') {
            $config['rate'] = $info['rate'];
         }
         if (isset($info['afterTerminate']) && $info['afterTerminate'] > 0) {
            $config['afterTerminate'] = $info['afterTerminate'];
         }
         if (isset($info['password']) && $info['password'] != '') {
            $config['password'] = $info['password'];
         }
         if (isset($info['reply']) && $info['reply'] == true) {
            $config['reply'] = true;
         }
         $info['config'] = json_encode($config);


         if (!isset($info['data'])) {
            $info['data'] = '';
         }

         return $info;
      }

      private function create($info) {
         $GCOnline = $this -> getAPI('_admin/GCOnline', 'GCOnline');
         $GCOnline -> auth('other');

         $info = $this -> infoToFullData($info);

         $sql = '
            INSERT INTO `GCOnline_scripts` (
               `type`,
               `title`,
               `description`,
               `config`,
               `url`,
               `data`
            )
            VALUES 
               (
                  "' . $info['type'] . '",
                  "' . $info['title'] . '",
                  "' . $info['description'] . '",
                  \'' . $info['config'] . '\',
                  "' . $info['url'] . '",
                  \'' . $info['data'] . '\'
               )
            ;
         ';
         $GCOnline -> DB -> query($sql);

         return 'ok';
      }

      private function update($id, $info) {
         $GCOnline = $this -> getAPI('_admin/GCOnline', 'GCOnline');
         $GCOnline -> auth('other');

         $info = $this -> infoToFullData($info);

         $sql = '
            UPDATE 
               `GCOnline_scripts` 
            SET 
               `type` = "' . $info['type'] . '",
               `title` = "' . $info['title'] . '",
               `description` = "' . $info['description'] . '",
               `config` = \'' . $info['config'] . '\',
               `url` = "' . $info['url'] . '",
               `data` = \'' . $info['data'] . '\'
            WHERE 
               `id` = ' . $id . '
            ;
         ';
         $GCOnline -> DB -> query($sql);

         return 'ok';
      }
   }
?>