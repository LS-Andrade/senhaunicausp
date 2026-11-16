<?php

Session::checkRight('config', READ);

if (isset($_POST['update'])) {
   // O token CSRF dos POSTs é verificado pelo GLPI antes de chegar aqui
   Session::checkRight('config', UPDATE);

   if (PluginSenhaunicauspConfig::saveFromInput($_POST)) {
      Session::addMessageAfterRedirect(__('Item successfully updated'), false, INFO);
   } else {
      Session::addMessageAfterRedirect(__('Nenhuma alteração salva.', 'senhaunicausp'), false, WARNING);
   }
   Html::back();
}

Html::header(__('USP OAuth (Senha Única)', 'senhaunicausp'), $_SERVER['PHP_SELF'], 'config', 'plugins');
(new PluginSenhaunicauspConfig())->showForm(PluginSenhaunicauspConfig::CONFIG_ID);
Html::footer();