<?php

function plugin_senhaunicausp_install() {
   global $DB;

   if (!$DB->tableExists('glpi_plugin_senhaunicausp_configs')) {
      $query = "CREATE TABLE `glpi_plugin_senhaunicausp_configs` (
         `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
         `identifier` VARCHAR(255) DEFAULT NULL,
         `secret` VARCHAR(255) DEFAULT NULL,
         `callback_id` INT(11) DEFAULT 0,
         PRIMARY KEY (`id`) USING BTREE
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

      $DB->doQuery($query);

      $DB->insert('glpi_plugin_senhaunicausp_configs', [
         'id'          => 1,
         'identifier'  => '',
         'secret'      => '',
         'callback_id' => 0
      ]);
   }

   return true;
}

function plugin_senhaunicausp_uninstall() {
   global $DB;

   if ($DB->tableExists('glpi_plugin_senhaunicausp_configs')) {
      $DB->doQuery("DROP TABLE `glpi_plugin_senhaunicausp_configs`");
   }

   return true;
}