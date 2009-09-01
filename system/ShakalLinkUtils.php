<?php
/**
 * \file
 * \author Miroslav Bendík
 * \brief Nástroje na prácu s cestami a odkazmi v Shakal CMS.
 */
namespace Shakal;

/**
 * \brief Trieda poskytujúca jednotný prístup k adresárom na rôznych platformách.
 * \ingroup Shakal_Core
 * \licenses \gpl
 *
 * Táto trieda umožňuje vytváranie, zjednodušenie a kontrolu zadaných ciest.
 * Cesta sa rozdelí na jednotlivé zložky a je možné kontrolovať prístup
 * k nadredeným položkám a mimo základného adresára s aplikáciou.
 */
class Path
{
	private $_pathArr = array();
	private $_valid    = true;

	/**
	 * Prevod cesty v unixovom zápise na cestu závislú na platforme.
	 */
	public static function toSystemPath($string)
	{
		if (DIRECTORY_SEPARATOR != '/'){
			$string = str_replace('/', DIRECTORY_SEPARATOR, $string);
		}
		return $string;
	}

	/**
	 * Vytvorenie novej cesty.
	 *
	 * @param path Cesta, ktorá sa má vytvoriť. Može byť buď pole s jednotlivými
	 *             adresármi, alebo cesta zapísaná ako reťazec.
	 */
	public function __construct($path = null)
	{
		if (!is_null($path))
			$this->setPath($path);
	}

	/**
	 * Získanie zložiek cesty v podobe homogénneho poľa. Položky sú zoradené
	 * od vonkajšieho adresára po vnútorny adresár, alebo súbor.
	 */
	public function pathArr()
	{
		return $this->_pathArr;
	}

	/**
	 * Táto funkcia vráti \e true ak cesta nevystupuje mimo adresárovú štruktúru
	 * (tj. nezačína sa nadradeným adresárom '..', alebo koreňovým adresárom).
	 */
	public function isValid()
	{
		return $this->_valid;
	}

	/**
	 * Funkcia vráti \e true, ak je cesta lokálna (tj. nezačína v koreňovom
	 * adresári).
	 */
	public function isLocal()
	{
		if (count($this->_pathArr) > 0 && $this->_pathArr[0] === '')
			return false;
		else
			return true;
	}

	/**
	 * Pridanie ďalšej položky na koniec cesty.
	 *
	 * @param path Cesta, ktorá sa má pridať na koniec. Argumentom môže byť buď
	 *             reťazec, alebo pole položiek.
	 *
	 * \code
	 * $path->push('adresar');    // Cesta bude 'adresar'.
	 * $path->push('podadresar'); // Pridanie podadresára k adresáru ('adresar/podadresar').
	 * $path->push('.');          // Cesta sa nezmení.
	 * $path->push('../abc');     // Prechod do nadradeného adresára a z neho do
	 *                            // adresára abc ('adresar/abc').
	 * $path->push(array('..', 'xyz'));
	 *                            // Prechod do nadradeného adresára a podadresára
	 *                            // xyz ('adresar/xyz').
	 * \endcode
	 */
	public function &push($path)
	{
		if (is_array($path))
			$directories = $path;
		else
			$directories = explode('/', $path);

		foreach ($directories as $dir) {
			if ($dir === '.') {
				// Nič sa nedeje
			}
			elseif ($dir === '..') {
				// O priečinok vyššie
				$this->pop();
			}
			elseif ($dir === '' && count($this->_pathArr) === 0) {
				array_push($this->_pathArr, $dir);
				$this->_valid = false;
			}
			else {
				array_push($this->_pathArr, $dir);
			}
		}
		return $this;
	}

	/**
	 * Prechod do nadradeného adresára.
	 */
	public function &pop()
	{
		if (count($this->_pathArr) === 0 || $this->_pathArr[count($this->_pathArr) - 1] === '..') {
			$this->_valid = false;
			array_push($this->_pathArr, '..');
		}
		else {
			array_pop($this->_pathArr);
		}
		return $this;
	}

	/**
	 * Resetovanie cesty a nastavenie na novú hodnotu. Argument \a path má
	 * rovnaký formát ako u konštruktoru.
	 *
	 * \sa __construct
	 */
	public function setPath($path)
	{
		$this->_pathArr = array();
		$this->_valid = true;

		$this->push($path);
	}

	/**
	 * Prevod cesty na reťazec.
	 */
	public function __toString()
	{
		return implode('/', $this->_pathArr);
	}

	/**
	 * Prevod cesty do systémového tvaru.
	 */
	public function sysPath()
	{
		return self::toSystemPath(implode('/', $this->_pathArr));
	}

	/**
	 * Prevod cesty na absolútnu cestu v systémovom tvare.
	 */
	public function absoluteSysPath()
	{
		return self::toSystemPath(SITE_PATH.implode('/', $this->_pathArr));
	}
}

///@todo Napísať Link
class Link
{
	private $_path = null;

	public function __construct($path = null, array $vars = array(), array $tempVars = array())
	{
		if (!is_null($path))
			$this->setPath($path);
	}

	public function setPath($path)
	{
		if ($path instanceof Path)
			$this->_path = $path;
		else
			$this->_path = new Path($path);
	}
}

?>
