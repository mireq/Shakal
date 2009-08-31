<?php
/**
 * \file
 * \author Miroslav Bendík
 * \brief Prístup k globálne dostupným dátam.
 */

/**
 * \brief Register dát dostupných globálne v celej aplikácii.
 * \ingroup Shakal_Globals
 * \licenses \gpl
 *
 * Pomocou ShakalRegistry je možné pristupovať k dátam ako je konfigurácia,
 * medzi modulmi zdieľané dáta a pod. Prístup k dátam je možný pomocou statických
 * metód, alebo získaním inštancie (getInstance) a prácou s ňou. Trieda je
 * singleton.
 *
 * Prístup cez statické metódy:
 * \code
 * ShakalRegistry::set('premenna', 'hodnota');
 * $premenna = ShakalRegistry::get('premenna');
 * if (ShakalRegistry::isRegistred('premenna')) {
 *     // ...
 * }
 * ShakalRegistry::unregister('premenna');
 * \endcode
 *
 * Prístup cez pole:
 * \code
 * $registry = ShakalRegistry::getInstance();
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
 * $registry = ShakalRegistry::getInstance();
 * $registry->premenna = 'hodnota';
 * $premenna = $registry->premenna;
 * if (isset($registry->premenna)) {
 *     // ...
 * }
 * unset($registry->premenna);
 * \endcode
 */
class ShakalRegistry implements ArrayAccess
{
	private static $instance = null;
	private $_registry = array();

	/**
	 * Získanie inštancie ShakalRegistry (singleton).
	 */
	public static function &getInstance()
	{
		if (is_null(self::$instance)) {
			self::$instance = new self;
		}
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
	 * $registry = ShakalRegistry::getInstance();
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
	 * $premenna = ShakalRegistry::getInstance()->premenna;
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
	 * if (isset(ShakalRegistry::getInstance()->premenna))
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
	 * unset(ShakalRegistry::getInstance()->premenna);
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
	 * $registry = ShakalRegistry::getInstance();
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
	 * $registry = ShakalRegistry::getInstance();
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
	 * $registry = ShakalRegistry::getInstance();
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
	 * $registry = ShakalRegistry::getInstance();
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
	 * ShakalRegistry::set('premenna', 'hodnota');
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
	 * $premenna = ShakalRegistry::get('premenna');
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
	 * if (ShakalRegistry::isRegistred('premenna'))
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
	 * ShakalRegistry::unregister('premenna');
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

?>