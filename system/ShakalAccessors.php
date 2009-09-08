<?php
/**
 * \file
 * \author Miroslav Bendík
 * \brief Prístup k globálne dostupným dátam.
 */

namespace Shakal;

/**
 * \brief Register dát dostupných globálne v celej aplikácii.
 * \ingroup Shakal_Globals
 * \licenses \gpl
 *
 * Pomocou Registry je možné pristupovať k dátam ako je konfigurácia,
 * medzi modulmi zdieľané dáta a pod. Prístup k dátam je možný pomocou statických
 * metód, alebo získaním inštancie (getInstance) a prácou s ňou. Trieda je
 * singleton.
 *
 * Prístup cez statické metódy:
 * \code
 * Registry::set('premenna', 'hodnota');
 * $premenna = Registry::get('premenna');
 * if (Registry::isRegistred('premenna')) {
 *     // ...
 * }
 * Registry::unregister('premenna');
 * \endcode
 *
 * Prístup cez pole:
 * \code
 * $registry = Registry::getInstance();
 * $registry['premenna'] = 'hodnota';
 * $premenna = $registry['premenna'];
 * if (isset($registry['premenna'])) {
 *     // ...
 * }
 * unset($registry['premenna']);
 * \endcode
 *
 * Prístup cez objekt:
 * \code
 * $registry = Registry::getInstance();
 * $registry->premenna = 'hodnota';
 * $premenna = $registry->premenna;
 * if (isset($registry->premenna)) {
 *     // ...
 * }
 * unset($registry->premenna);
 * \endcode
 */
class Registry implements \ArrayAccess
{
	private static $instance = null;
	private $_registry = array();

	/**
	 * Získanie inštancie Registry (singleton).
	 */
	public static function &getInstance()
	{
		if (is_null(self::$instance))
			self::$instance = new self;
		return self::$instance;
	}

	private function __construct()
	{
	}

	/// \name Objektový prístup k premenným
	//@{
	/**
	 * Nastavenie premennej objektovým prístupom.
	 *
	 * \code
	 * $registry = Registry::getInstance();
	 * $registry->premenna = 'hodnota';
	 * \endcode
	 */
	public function __set($name, $value)
	{
		$this->_registry[$name] = $value;
	}

	/**
	 * Získanie premennej objektovým prístupom.
	 *
	 * \code
	 * $premenna = Registry::getInstance()->premenna;
	 * \endcode
	 */
	public function __get($name)
	{
		return $this->_registry[$name];
	}

	/**
	 * Ak je nastavená hodnota atribútu s názvom \a name vráti \e true.
	 *
	 * \code
	 * if (isset(Registry::getInstance()->premenna))
	 *     // ...
	 * \endcode
	 */
	public function __isset($name)
	{
		return isset($this->_registry[$name]);
	}

	/**
	 * Vymazanie atribútu s názvom \a name.
	 *
	 * \code
	 * unset(Registry::getInstance()->premenna);
	 * \endcode
	 */
	public function __unset($name)
	{
		unset($this->_registry[$name]);
	}
	//@}

	/// \name Prístup k premenným cez pole
	//@{
	/**
	 * Nastavenie hodnoty atribútu pomocou poľa.
	 *
	 * \code
	 * $registry = Registry::getInstance();
	 * $registry['premenna'] = 'hodnota';
	 * \endcode
	 *
	 * \sa __set
	 */
	public function offsetSet($name, $value)
	{
		$this->$name = $value;
	}

	/**
	 * Zistenie hodnoty atribútu pomocou poľa.
	 *
	 * \code
	 * $registry = Registry::getInstance();
	 * $premenna = $registry['premenna'];
	 * \endcode
	 *
	 * \sa __get
	 */
	public function offsetGet($name)
	{
		return $this->$name;
	}

	/**
	 * Zistenie existencie atribútu s indexom \a name.
	 *
	 * \code
	 * $registry = Registry::getInstance();
	 * if (isset($registry['premenna']))
	 *     // ...
	 * \endcode
	 *
	 * \sa __isset
	 */
	public function offsetExists($name)
	{
		return isset($this->$name);
	}

	/**
	 * Zrušenie atribútu s indexom \a name.
	 *
	 * \code
	 * $registry = Registry::getInstance();
	 * unset($registry['premenna']);
	 * \endcode
	 *
	 * \sa __unset
	 */
	public function offsetUnset($name)
	{
		unset($this->$name);
	}
	//@}

	/// \name Prístup k premenným pomocou statického poľa
	//@{
	/**
	 * Statická metóda pre nastavenie hodnoty atribútu.
	 *
	 * \code
	 * Registry::set('premenna', 'hodnota');
	 * \endcode
	 *
	 * \sa __set
	 */
	public static function set($name, $value)
	{
		self::getInstance()->$name = $value;
	}

	/**
	 * Statická metóda pre získanie hodnoty atribútu.
	 *
	 * \code
	 * $premenna = Registry::get('premenna');
	 * \endcode
	 *
	 * \sa __get
	 */
	public static function get($name)
	{
		return self::getInstance()->$name;
	}

	/**
	 * Statická metóda pre zistenie, či je atribút nastavený.
	 *
	 * \code
	 * if (Registry::isRegistred('premenna'))
	 *     // ...
	 * \endcode
	 *
	 * \sa __isset
	 */
	public static function isRegistred($name)
	{
		return isset(self::getInstance()->$name);
	}

	/**
	 * Zrušenie atribútu s názvom \a name.
	 *
	 * \code
	 * Registry::unregister('premenna');
	 * \endcode
	 *
	 * \sa __unset
	 */
	public static function unregister($name)
	{
		unset(self::getInstance()->$name);
	}
	//@}
}

/**
 * \brief Register pre konfiguračné voľby \shakal a Modulov.
 * \ingroup ShakalGlobals
 * \licenses \gpl
 *
 * Konfigurácia modulov a systému \shakal zisťuje pomocou
 * tejto triedy.
 *
 * Každý modul má vlastné konfiguračné voľby. Všetky metódy pre
 * manipuláciu s dátami majú nepovinný parameter \a module určujúci
 * modul, s ktorého konfiguračnými voľbami chceme manipulovať.
 * Ak táto položka zostane nenastavená platia tieto dáta globálne
 * pre celú aplikáciu.
 *
 * \sa \link config \endlink
 */
class ConfigRegistry
{
	private static $instance = null;
	private $_registry = array();

	private function __construct() {
	}

	/**
	 * Získanie inštancie ConfigRegistry.
	 */
	public static function &getInstance() {
		if (is_null(self::$instance))
			self::$instance = new self;
		return self::$instance;
	}

	private function getCfg($name, $module) {
		return $this->_registry[$module][$name];
	}

	private function setCfg($name, $value, $module) {
		if (!isset($this->_registry[$module]))
			$this->_registry[$module] = array();
		$this->_registry[$module][$name] = $value;
	}

	private function registredCfg($name, $module) {
		return isset($this->_registry[$module][$name]);
	}

	private function unregisterCfg($name, $module) {
		unset($this->_registry[$module][$name]);
		if (count($this->_registry[$module]) === 0)
			unset($this->_registry[$module]);
	}

	/**
	 * Získanie hodnoty konfiguračnej voľby \a name modulu \a module.
	 */
	public static function get($name, $module = '') {
		return self::getInstance()->getCfg($name, $module);
	}

	/**
	 * Nastavenie konfiguračnej voľby \a name modulu \a module na hodnotu \a value.
	 */
	public static function set($name, $value, $module = '') {
		self::getInstance()->setCfg($name, $value, $module);
	}

	/**
	 * Ak je nastavená konfiguračná voľba \a name modulu \a module vráti funkcia \e true.
	 */
	public static function isRegistred($name, $module = '') {
		return self::getInstance()->registredCfg($name, $module);
	}

	/**
	 * Zrušenie konfiguračnej voľby \a name modulu \a module.
	 */
	public static function unregister($name, $module = '') {
		self::getInstance()->unregisterCfg($name, $module);
	}
}

?>
