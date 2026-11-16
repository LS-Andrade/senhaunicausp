<?php

/**
 * Ponto único de entrada da Senha Única USP (OAuth1).
 *
 * Etapa 1: sem parâmetros   -> pede token temporário e redireciona para a USP
 * Etapa 2: volta da USP com oauth_token/oauth_verifier -> troca por token,
 *          obtém dados do usuário, cria/localiza no GLPI e abre a sessão.
 *
 * A liberação de acesso anônimo é feita em setup.php (Firewall::STRATEGY_NO_CHECK).
 * Não usamos Senhaunica::login() porque ele chama session_write_close() e usa
 * $_SERVER['PHP_SELF'], o que não funciona bem no GLPI 11/12 (roteamento via Symfony).
 */

use League\OAuth1\Client\Credentials\TemporaryCredentials;
use Uspdev\Senhaunica\ServerUSP;

global $CFG_GLPI;

require_once __DIR__ . '/../vendor/autoload.php';

$home = $CFG_GLPI['root_doc'] . '/index.php';

function uspoauth_fail(string $msg, string $home): void
{
   Session::addMessageAfterRedirect($msg, false, ERROR);
   Html::redirect($home);
}

// --- Configuração -----------------------------------------------------------
$config = new PluginSenhaunicauspConfig();
if (!$config->getFromDB(1) || empty($config->fields['identifier']) || empty($config->fields['secret'])) {
   uspoauth_fail('Senha Única USP não configurada.', $home);
}

$callbackId = (int) ($config->fields['callback_id'] ?? 0);
$server = new ServerUSP([
   'identifier'  => $config->fields['identifier'],
   'secret'      => $config->fields['secret'],
   'callback_id' => $callbackId,
]);

// --- Etapa 2: retorno da USP -------------------------------------------------
if (isset($_GET['oauth_token'], $_GET['oauth_verifier'])) {
   $temporary = null;
   if (!empty($_SESSION['uspoauth_temp'])) {
      $temporary = unserialize($_SESSION['uspoauth_temp'], ['allowed_classes' => [TemporaryCredentials::class]]);
   }
   unset($_SESSION['uspoauth_temp']);

   if (!($temporary instanceof TemporaryCredentials)) {
      // Se cair aqui, a sessão/cookie se perdeu entre a ida e a volta
      uspoauth_fail('Sessão perdida durante a autenticação USP. Tente novamente.', $home);
   }

   try {
      $token   = $server->getTokenCredentials($temporary, $_GET['oauth_token'], $_GET['oauth_verifier']);
      $details = $server->getUserDetails($token);
   } catch (\Throwable $e) {
      trigger_error('[uspoauth] ' . $e->getMessage(), E_USER_WARNING);
      uspoauth_fail('Falha ao validar a autenticação na USP.', $home);
   }

   $nusp = trim((string) ($details['loginUsuario'] ?? ''));
   if ($nusp === '') {
      uspoauth_fail('A USP não retornou os dados do usuário.', $home);
   }

   // --- Localiza ou cria o usuário -------------------------------------------
   $user = new User();
   if (!$user->getFromDBByCrit(['name' => $nusp])) {
      $email = $details['emailPrincipalUsuario'] ?? ($details['emailUspUsuario'] ?? '');

      $nompes = $details['nomeUsuario'] ?? ($details['nomeUsuario'] ?? '');
      $primeiroNome = '';
      $sobreNome = "";
      
      $telefone = $details['numeroTelefoneFormatado'] ?? ($details['numeroTelefoneFormatado'] ?? '');
      $telefoneUsuario = "";
      $ramalUsuario = "";

      // Expressão regular para isolar o DDD, o número do telefone e o ramal
      $pattern = '/^\(\d?xx(\d{2})\)([\d-]+)\s*-\s*ramal USP:\s*(\d+)$/i';
      if (preg_match($pattern, $telefone, $matches)) {
         $ddd      = (isset($matches[1]) ? $matches[1] : "");
         $telefoneUsuario = (isset($matches[2]) ? "({$ddd})" . $matches[2] : ""); 
         $ramalUsuario    = (isset($matches[3]) ? $matches[3] : "");
      }
      
      if ($nompes !== '') {
         $nompesParts = explode(' ', $nompes);
         $primeiroNome = $nompesParts[0];
         if (count($nompesParts) > 1) {
            array_shift($nompesParts);
            $sobreNome = implode(' ', $nompesParts);
         }
      }

      $input = [
         'name'     => $nusp,
         'realname' => $sobreNome,
         'firstname' => $primeiroNome,
         'authtype' => Auth::EXTERNAL,
         'is_active' => 1,
      ];
      if ($email !== '') {
         $input['_useremails'] = [-1 => $email];
      }

      if ($telefoneUsuario !== '') {
         $input['phone'] = $telefoneUsuario;
      }

      if ($ramalUsuario !== '') {
         $input['phone2'] = $ramalUsuario;
      }
      // Se não houver "perfil padrão" definido no GLPI, informe aqui:
      // $input['_profiles_id'] = 1; $input['_entities_id'] = 0;

      $newId = $user->add($input);
      if (!$newId || !$user->getFromDB($newId)) {
         uspoauth_fail('Não foi possível criar o usuário no GLPI.', $home);
      }
   }

   if (!$user->fields['is_active'] || $user->fields['is_deleted']) {
      uspoauth_fail('Usuário inativo no GLPI.', $home);
   }

   // --- Abre a sessão GLPI ---------------------------------------------------
   $auth = new Auth();
   $auth->user          = $user;
   $auth->auth_succeded = true;
   $auth->extauth       = 1;
   $auth->user_present  = 1;
   Session::init($auth);   // já define valid_id, glpi_currenttime, glpiID, perfis...

   Auth::redirectIfAuthenticated();
   Html::redirect($CFG_GLPI['root_doc'] . '/front/central.php');
}

// --- Etapa 1: ida para a USP -------------------------------------------------
try {
   $temporary = $server->getTemporaryCredentials();
} catch (\Throwable $e) {
   trigger_error('[uspoauth] ' . $e->getMessage(), E_USER_WARNING);
   uspoauth_fail('Não foi possível iniciar a autenticação na USP.', $home);
}

$_SESSION['uspoauth_temp'] = serialize($temporary);
$url = $server->getAuthorizationUrl($temporary) . '&callback_id=' . $callbackId;

session_write_close();           // garante que a sessão foi gravada antes de sair do site
header('Location: ' . $url);
exit;