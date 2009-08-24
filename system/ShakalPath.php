<?php
/**
 * \file
 * \author Miroslav Bendík
 */

/**
 * \brief Trieda poskytujúca jednotný prístup k adresárom na rôznych platformách.
 * \ingroup Shakal_Core
 * \licenses \gpl
 *
 * Táto trieda umožňuje vytváranie, zjednodušenie a kontrolu zadaných ciest.
 * Cesta sa rozdelí na jednotlivé zložky a je možné kontrolovať prístup
 * k nadredeným položkám a mimo základného adresára s aplikáciou.
 */
class ShakalPath
{
	/**
	 * Prevod cesty v unixovom zápise na cestu závislú na platforme.
	 *
	 * \return Uprave
	 */
	public static function toPath($string)
	{
		if (DIRECTORY_SEPARATOR != '/') {
			$string = str_replace('/', DIRECTORY_SEPARATOR, $string);
		}
		return $string;
	}
}

?>
