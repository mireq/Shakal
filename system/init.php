<?php
namespace Shakal;

if (!defined(__NAMESPACE__ . '\SITE_PATH')) {
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

	if ($confDir = opendir(SITE_PATH.DS.'config')) {
		while (($fileName = readdir($confDir)) !== false) {
			if (preg_match('/^.*\.cfg\.php$/', $fileName)) {
				require(SITE_PATH.DS.'config'.DS.$fileName);
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

	foreach ($configRegistry as $name => $value) {
		ConfigRegistry::set($name, $value);
	}
}

require(SITE_PATH.'system'.DS.'ShakalLinkUtils.php');
require(SITE_PATH.'system'.DS.'ShakalAccessors.php');
require(SITE_PATH.'system'.DS.'ShakalExceptions.php');
require(SITE_PATH.'system'.DS.'ShakalRouter.php');
require(SITE_PATH.'system'.DS.'ShakalTable.php');
require(SITE_PATH.'system'.DS.'sql'.DS.'sql.php');

shakalReadConfig();
?>
