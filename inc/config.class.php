<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginSenhaunicauspConfig extends CommonDBTM {

   // Sem isso o GLPI considera que ninguém pode atualizar o item
   public static string $rightname = 'config';

   // Registro único da tabela
   const CONFIG_ID = 1;

   static function getTypeName($nb = 0) {
      return __('Senha Única USP', 'senhaunicausp');
   }

   static function getFormURL($full = true) {
      global $CFG_GLPI;
      return $CFG_GLPI['root_doc'] . '/plugins/senhaunicausp/front/config.form.php';
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
      if ($item instanceof Config) {
         return self::createTabEntry(__('Senha Única USP', 'senhaunicausp'));
      }
      return '';
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
      if ($item instanceof Config) {
         (new self())->showForm(self::CONFIG_ID);
      }
      return true;
   }

   /**
    * Garante que o registro único exista e o devolve carregado.
    */
   static function getConfig(): self {
      global $DB;

      $config = new self();
      if (!$config->getFromDB(self::CONFIG_ID)) {
         $DB->insert(self::getTable(), [
            'id'          => self::CONFIG_ID,
            'identifier'  => '',
            'secret'      => '',
            'callback_id' => 0,
         ]);
         $config->getFromDB(self::CONFIG_ID);
      }
      return $config;
   }

   /**
    * Salva o registro único (só campos permitidos).
    * Secret em branco = mantém o valor atual.
    */
   static function saveFromInput(array $post): bool {
      $config = self::getConfig();

      $input = [
         'id'          => self::CONFIG_ID,
         'identifier'  => trim((string) ($post['identifier'] ?? '')),
         'callback_id' => max(0, (int) ($post['callback_id'] ?? 0)),
      ];

      $secret = trim((string) ($post['secret'] ?? ''));
      if ($secret !== '') {
         $input['secret'] = $secret;
      }

      return $config->update($input);
   }

   function showForm($id = self::CONFIG_ID, array $options = []) {
      global $CFG_GLPI;

      if (!Session::haveRight(self::$rightname, READ)) {
         return false;
      }
      $canedit = Session::haveRight(self::$rightname, UPDATE);

      $config = self::getConfig();
      $f      = $config->fields;

      $base = !empty($CFG_GLPI['url_base']) ? rtrim($CFG_GLPI['url_base'], '/') : '';
      $callback_url = $base . '/plugins/senhaunicausp/front/login.php';
      $dis = $canedit ? '' : ' disabled';

      echo '<form method="post" action="' . htmlspecialchars(self::getFormURL()) . '" autocomplete="off">';
      echo '<div class="card m-3"><div class="card-header"><h3 class="card-title">'
         . htmlspecialchars(self::getTypeName()) . '</h3></div>';
      echo '<div class="card-body">';

      echo '<div class="mb-3"><label class="form-label">' . __('Identificação (identifier)', 'senhaunicausp') . '</label>';
      echo '<input type="text" class="form-control" name="identifier" value="'
         . htmlspecialchars($f['identifier'] ?? '') . '"' . $dis . '></div>';

      echo '<div class="mb-3"><label class="form-label">' . __('Chave secreta (secret)', 'senhaunicausp') . '</label>';
      echo '<input type="password" class="form-control" name="secret" value="" autocomplete="new-password"'
         . ' placeholder="' . (!empty($f['secret']) ? '•••••••• (deixe em branco para manter)' : '') . '"' . $dis . '></div>';

      echo '<div class="mb-3"><label class="form-label">' . __('Callback ID', 'senhaunicausp') . '</label>';
      echo '<input type="number" min="0" class="form-control" name="callback_id" value="'
         . (int) ($f['callback_id'] ?? 0) . '"' . $dis . '></div>';

      echo '<div class="mb-3"><label class="form-label">' . __('URL de callback a cadastrar na USP', 'senhaunicausp') . '</label>';
      echo '<input type="text" class="form-control" readonly value="' . htmlspecialchars($callback_url) . '"></div>';

      echo '</div>';
      if ($canedit) {
         echo '<div class="card-footer text-end">';
         echo '<button type="submit" name="update" value="1" class="btn btn-primary">'
            . '<i class="ti ti-device-floppy me-1"></i>' . _x('button', 'Save') . '</button>';
         echo '</div>';
      }
      echo '</div>';
      echo Html::closeForm(false); // inclui o token CSRF

      return true;
   }
}