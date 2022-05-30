#######################################################################################################
# Sofortsuche 2.1.0 UNINSTALL - 2022-05-30 - webchills
# NUR AUSFÜHREN FALLS SIE DAS MODUL VOLLSTÄNDIG ENTFERNEN WOLLEN!!!
########################################################################################################

DELETE FROM configuration WHERE configuration_key LIKE 'INSTANT_SEARCH_%';
DELETE FROM configuration_language WHERE configuration_key LIKE 'INSTANT_SEARCH_%';
DELETE FROM configuration_group WHERE configuration_group_title = 'Instant Search';
DELETE FROM admin_pages WHERE page_key='configInstantSearch';