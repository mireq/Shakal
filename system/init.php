<?php
if (!defined('SITE_PATH')) {
	return;
}

/**
 * \file
 * \author Miroslav Bendík
 * \brief Inicializácia Shakal CMS.
 */

/**
 * Načítanie všetkých súborov v adresári config končiacich sa na cfg.php.
 */
function shakalReadConfig()
{
	$configRegistry = array();

	if ($confDir = opendir(SITE_PATH.DIRECTORY_SEPARATOR.'config')) {
		while (($fileName = readdir($confDir)) !== false) {
			if (ereg('^.*\.cfg\.php$', $fileName)) {
				require(SITE_PATH.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.$fileName);
				$vars = get_defined_vars();
				unset($vars['confDir']);
				unset($vars['fileName']);
				unset($vars['configRegistry']);
				foreach ($vars as $key => $value) {
					if (isset($configRegistry[$key]) && is_array($configRegistry[$key]) && is_array($value))
						$configRegistry[$key] = array_merge($configRegistry[$key], $value);
					else
						$configRegistry[$key] = $value;
				}
			}
		}
		closedir($confDir);
	}

	ShakalRegistry::set('config', $configRegistry);
}

require(SITE_PATH.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.'ShakalLinkUtils.php');
require(ShakalPath::toSystemPath(SITE_PATH.'system/ShakalAccessors.php'));
require(ShakalPath::toSystemPath(SITE_PATH.'system/ShakalExceptions.php'));
require(ShakalPath::toSystemPath(SITE_PATH.'system/ShakalRouter.php'));
require(ShakalPath::toSystemPath(SITE_PATH.'system/ShakalTable.php'));

shakalReadConfig();

require(ShakalPath::toSystemPath(SITE_PATH.'system/ShakalSql.php'));
?>