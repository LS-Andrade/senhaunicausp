<?php

use Glpi\Http\Firewall;

define('PLUGIN_SENHAUNICAUSP_VERSION', '1.0.0');
define('PLUGIN_SENHAUNICAUSP_MIN_GLPI', '12.0.0');

function plugin_init_senhaunicausp() {
   global $PLUGIN_HOOKS;

   // GLPI 11/12: scripts legados de plugin exigem usuário autenticado por padrão.
   // Sem esta linha, front/login.php cai em "sessão expirada" para quem ainda não logou.
   Firewall::addPluginStrategyForLegacyScripts(
      'senhaunicausp',
      '#^/front/login\.php$#',
      Firewall::STRATEGY_NO_CHECK
   );

   // Hook para injetar o botão na tela pública de login
   $PLUGIN_HOOKS['display_login']['senhaunicausp'] = 'plugin_senhaunicausp_display_login';

   // Ícone de configuração na lista de plugins
   $PLUGIN_HOOKS['config_page']['senhaunicausp'] = 'front/config.form.php';

   // Arquivo de CSS do plugin
   $PLUGIN_HOOKS['add_css']['senhaunicausp'] = ['css/senhaunicausp.min.css'];

   // Registro da classe de configurações no painel administrativo
   Plugin::registerClass('PluginSenhaunicauspConfig', ['addtabon' => 'Config']);
}

function plugin_version_senhaunicausp() {
   return [
      'name'           => 'Senha Única USP',
      'version'        => PLUGIN_SENHAUNICAUSP_VERSION,
      'author'         => 'Lucas Andrade',
      'license'        => 'GPLv2+',
      'homepage'       => 'https://github.com/LS-Andrade/senhaunicausp',
      'requirements'   => [
         'glpi' => [
            'min' => PLUGIN_SENHAUNICAUSP_MIN_GLPI,
         ]
      ]
   ];
}

function plugin_senhaunicausp_check_prerequisites() { return true; }
function plugin_senhaunicausp_check_config() { return true; }

function plugin_senhaunicausp_display_login() {
   global $CFG_GLPI;

   $login_url = $CFG_GLPI['root_doc'] . '/plugins/senhaunicausp/front/login.php';
   
   echo '<div id="senhaunicausp-container" class="mb-3 text-center">';
   echo '  <a href="' . htmlspecialchars($login_url) . '" class="btn btn-lg btn-outline-primary w-100">';
   echo '    <i class="fas fa-key me-2"></i>Entrar com a senha única';
   echo '  </a>';
   echo '  <div class="hr-text my-3">ou</div>';
   echo '</div>';

   // Move o container para ANTES do formulário padrão via Javascript
   echo '
   <script>
      document.addEventListener("DOMContentLoaded", function() {
         var btnBox = document.getElementById("senhaunicausp-container");
         var loginForm = document.querySelector("form[action*=\"login\"]") || document.querySelector("form");
         if (btnBox && loginForm) {
            loginForm.parentNode.insertBefore(btnBox, loginForm);
         }
      });
   </script>';
}