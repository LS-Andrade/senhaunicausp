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

   function locationBySector($codsec=0){
      switch ($codsec) {
         // FSP
         case 463: return 1;
         // CTSAUDE
         case 491: case 492: case 493: case 494: case 495: case 496: case 497: return 2;
         // DVACAD
         case 464: case 466: case 470: case 469: return 3;
         // DVADM
         case 471: case 472: case 482: case 480: case 477: return 4;
         // DVBIBDC
         case 498: case 499: case 501: case 503: return 5;
         // DVFIN
         case 474: case 475: case 476: case 484: return 6;
         // DVPDIGI
         case 4046: case 510: case 4047: return 7;
         // HEP
         case 505: return 8;
         // HNT
         case 506: return 9;
         // HSA
         case 507: return 10;
         // HSO
         case 508: return 11;
         // HSP
         case 509: return 12;
         // SESA
         case 511: case 513: case 519: case 520: case 521: case 518: case 522: case 523: return 13;
         // EXTERNOS FSP
         default: return 14;
      }
   }
}