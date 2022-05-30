<?php
/**
 * @package Instant Search Results
 * @copyright Copyright Ayoob G 2009-2011
 * Zen Cart German Specific 
 * @copyright Copyright 2003-2022 Zen Cart Development Team
 * Zen Cart German Version - www.zen-cart-pro.at
 * @copyright Portions Copyright 2003 osCommerce
 * @license https://www.zen-cart-pro.at/license/3_0.txt GNU General Public License V3.0
 * @version $Id: 2.1.0.php 2022-05-29 20:16:16Z webchills $
 */
 
$db->Execute(" SELECT @gid:=configuration_group_id
FROM ".TABLE_CONFIGURATION_GROUP."
WHERE configuration_group_title= 'Instant Search'
LIMIT 1;");

$db->Execute("INSERT IGNORE INTO ".TABLE_CONFIGURATION." (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added, use_function, set_function) VALUES
('Enable Instant Search', 'INSTANT_SEARCH_ENABLE', 'false', 'Set to true to enable Instant Search', @gid, 1, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
('Maximum number of results to display', 'INSTANT_SEARCH_MAX_NUMBER_OF_RESULTS', '5', 'Maximum number of results displayed in the dropdown.', @gid, 2, NOW(), NULL, NULL),
('Minimum length of input text', 'INSTANT_SEARCH_MIN_WORDSEARCH_LENGTH', '3', 'Minimum number of characters that must be entered before Instant Search is initiated.', @gid, 3, NOW(), NULL, NULL),
('Maximum length of input text', 'INSTANT_SEARCH_MAX_WORDSEARCH_LENGTH', '100', 'Maximum string length allowed for Instant Search. If the search string length exceeds this value, the instant search will not be performed.', @gid, 4, NOW(), NULL, NULL),
('Search Delay', 'INSTANT_SEARCH_INPUT_WAIT_TIME', '150', 'Delay the execution of the instant search query by this time period (in milliseconds), after a character is entered, to prevent unnecessary queries while the user is typing.', @gid, 5, NOW(), NULL, NULL),
('Display the products price', 'INSTANT_SEARCH_DISPLAY_PRODUCT_PRICE', 'true', 'Display the product/category/manufacturer image in the results.', @gid, 6, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
('Display images', 'INSTANT_SEARCH_DISPLAY_IMAGE', 'true', 'Display the products price in the results.', @gid, 7, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
('Search products model', 'INSTANT_SEARCH_INCLUDE_PRODUCT_MODEL', 'true', 'Include the products model in the search. Set to false to improve Instant Search performance.', @gid, 8, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
('Display the products model', 'INSTANT_SEARCH_DISPLAY_PRODUCT_MODEL', 'false', 'Display the products model in the results.', @gid, 9, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
('Search categories', 'INSTANT_SEARCH_INCLUDE_CATEGORIES', 'false', 'Include category names in the search/results. Set to false to improve Instant Search performance.', @gid, 10, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
('Display categories count', 'INSTANT_SEARCH_DISPLAY_CATEGORIES_COUNT', 'true', 'Display the number of products in the matched categories results (only if <em>Search Categories</em> is set to true).', @gid, 11, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
('Search manufacturers', 'INSTANT_SEARCH_INCLUDE_MANUFACTURERS', 'false', 'Include manufacturer names in the search/results.', @gid, 12, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
('Display manufacturers count', 'INSTANT_SEARCH_DISPLAY_MANUFACTURERS_COUNT', 'false', 'Display the number of products for the matched manufacturers (only if <em>Search Manufacturers</em> is set to true).', @gid, 13, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),'),
('Search products attributes values', 'INSTANT_SEARCH_INCLUDE_OPTIONS_VALUES', 'false', 'Include product attribute values in the search. Set to false to improve Instant Search performance.', @gid, 14, NOW(), NULL, 'zen_cfg_select_option(array(\'true\', \'false\'),');");

$db->Execute("REPLACE INTO ".TABLE_CONFIGURATION_LANGUAGE." (configuration_title, configuration_key, configuration_description, configuration_language_id) VALUES
('Sofortsuche aktivieren?', 'INSTANT_SEARCH_ENABLE', 'Stellen Sie auf true, wenn Sie die Sofortsuche aktivieren wollen.', 43),
('Maximale Suchergebnisse für die Anzeige', 'INSTANT_SEARCH_MAX_NUMBER_OF_RESULTS', 'Wievele Suchergebnisse sollen maximal im Dropdown angezeigt werden?', 43),
('Minimale Länge des Eingabetextes', 'INSTANT_SEARCH_MIN_WORDSEARCH_LENGTH', 'Wieviele Zeichen müssen mindestens eingetippt werden, damit die Sofortsuche startet?', 43),
('Maximale Länge des Eingabetextes', 'INSTANT_SEARCH_MAX_WORDSEARCH_LENGTH', 'Maximal zulässige Länge für die Sofortsuche. Wenn die Länge der Suchzeichenfolge diesen Wert überschreitet, wird die Sofortsuche nicht durchgeführt.', 43),
('Suchverzögerung', 'INSTANT_SEARCH_INPUT_WAIT_TIME', 'Verzögern Sie die Ausführung der sofortigen Suchabfrage um diese Zeitspanne (in Millisekunden), nachdem ein Zeichen eingegeben wurde, um unnötige Abfragen zu vermeiden, während der Benutzer tippt.', 43),
('Artikelpreis anzeigen', 'INSTANT_SEARCH_DISPLAY_PRODUCT_PRICE', 'Soll der Artikelpreis im Suchergebnis angezeigt werden?', 43),
('Artikelbild anzeigen', 'INSTANT_SEARCH_DISPLAY_IMAGE', 'Soll das Artikelbild im Suchergebnis angezeigt werden?', 43),
('Artikelnummer durchsuchen', 'INSTANT_SEARCH_INCLUDE_PRODUCT_MODEL', 'Sollen Artikelnummern in die Suche einbezogen werden? Auf false stellen um die Suchperformance zu erhöhen.', 43),
('Artikelnummer anzeigen', 'INSTANT_SEARCH_DISPLAY_PRODUCT_MODEL', 'Soll die Artikelnummer im Suchergebnis angezeigt werden?.', 43),
('Kategorien durchsuchen', 'INSTANT_SEARCH_INCLUDE_CATEGORIES', 'Sollen Kategorienamen in die Suche einbezogen werden? Auf false stellen um die Suchperformance zu erhöhen.', 43),
('Artikelanzahl in Kategorien anzeigen', 'INSTANT_SEARCH_DISPLAY_CATEGORIES_COUNT', 'Anzahl der Artikel für die passenden Kategorien anzeigen? Nur falls Kategorien durchsuchen auf true steht.', 43),
('Hersteller durchsuchen', 'INSTANT_SEARCH_INCLUDE_MANUFACTURERS', 'Sollen Herstellernamen in die Suche einbezogen werden? Auf false stellen um die Suchperformance zu erhöhen.', 43),
('Artikelanzahl in Herstellern anzeigen', 'INSTANT_SEARCH_DISPLAY_MANUFACTURERS_COUNT', 'Anzahl der Artikel für die passenden Hersteller anzeigen? Nur falls Hersteller durchsuchen auf true steht.', 43),
('Attributmerkmale durchsuchen', 'INSTANT_SEARCH_INCLUDE_OPTIONS_VALUES','Sollen Attributmerkmale in die Suche einbezogen werden? Auf false stellen um die Suchperformance zu erhöhen.', 43)");

$admin_page = 'configInstantSearch';
// delete configuration menu
$db->Execute("DELETE FROM " . TABLE_ADMIN_PAGES . " WHERE page_key = '" . $admin_page . "' LIMIT 1;");
// add configuration menu
if (!zen_page_key_exists($admin_page)) {
$db->Execute(" SELECT @gid:=configuration_group_id
FROM ".TABLE_CONFIGURATION_GROUP."
WHERE configuration_group_title= 'Instant Search'
LIMIT 1;");

$db->Execute("INSERT IGNORE INTO " . TABLE_ADMIN_PAGES . " (page_key,language_key,main_page,page_params,menu_key,display_on_menu,sort_order) VALUES 
('configInstantSearch','BOX_CONFIGURATION_INSTANT_SEARCH_OPTIONS','FILENAME_CONFIGURATION',CONCAT('gID=',@gid),'configuration','Y',@gid)");
$messageStack->add('Sofortsuche Konfiguration erfolgreich hinzugefügt.', 'success');  
}