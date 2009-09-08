<?php
/**
 * \file
 * \author Miroslav Bendík
 * \brief Nástroje na prácu s cestami a odkazmi v Shakal CMS.
 */
namespace Shakal;

/**
 * \brief Trieda poskytujúca jednotný prístup k adresárom na rôznych platformách.
 * \ingroup Shakal_Core, Shakal_Url
 * \licenses \gpl
 *
 * Táto trieda umožňuje vytváranie, zjednodušenie a kontrolu zadaných ciest.
 * Cesta sa rozdelí na jednotlivé zložky a je možné kontrolovať prístup
 * k nadredeným položkám mimo adresára s aplikáciou.
 */
class Path
{
	private $_pathArr = array();
	private $_safe    = true;

	/**
	 * Prevod cesty v unixovom zápise na cestu závislú na platforme.
	 *
	 * Oddeľovače adresárov nie sú jednotné medzi platformami. Pri vývoji
	 * \shakal som použil všade unixové cesty, ktoré sa prevádzajú na iný
	 * typ ciest podľa cieľovej platformy.
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
	 * Cesta môže byť zapísaná ako lokálna cesta (začínajúca priamo adresárom, súborom, alebo
	 * ./), alebo globálna (začínajúca sa koreňovným adresárom /).
	 *
	 * \sa setPath()
	 */
	public function __construct($path = null)
	{
		if (!is_null($path))
			$this->setPath($path);
	}

	/**
	 * Získanie zložiek cesty v podobe homogénneho poľa.
	 *
	 * Položky sú zoradené od vonkajšieho adresára po vnútorny adresár, alebo súbor.
	 * V prípade globálnej adresy je koreňový adresár (prvý provok) \e null.
	 */
	public function pathArr()
	{
		return $this->_pathArr;
	}

	/**
	 * Zistenie, či cesta bezpečne patrí základnému adresáru.
	 *
	 * Pre všetky adresy, ktoré odkazujú mimo základného adresára vráti táto funkcia
	 * \e false. V prípade, že raz bude cesta smerovať mimo základný adresár po návrate
	 * do pôvodného adresára (<tt>$cesta->push('Pôvodný adresár')</tt>) nebude označená
	 * ako bezpečná.
	 *
	 * \warning Táto funkcia nekontroluje adresárovú štruktúru.
	 * Použitím odkazov je možné dostať sa mimo základného adresára aj keď táto funkcia
	 * vracia \e true.
	 */
	public function isSafe()
	{
		return $this->_safe;
	}

	/**
	 * Funkcia zisťuje, či je cesta lokálna (nezačína koreňovým adresárom).
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
		if ($path instanceof Path)
			$directories = $path->_pathArr;
		elseif (is_array($path))
			$directories = $path;
		else
			$directories = explode('/', $path);

		$num = 0;
		foreach ($directories as $dir) {
			if ($dir === '.') {
				// Nič sa nedeje
			}
			elseif ($dir === '..') {
				// O priečinok vyššie
				$this->pop();
			}
			elseif (empty($dir) && $num === 0) {
				// Koreňový adresár
				$this->_pathArr = array();
				array_push($this->_pathArr, null);
				$this->_safe = false;
			}
			else {
				// Štandardný adresár
				array_push($this->_pathArr, $dir);
			}
			++$num;
		}
		return $this;
	}

	/**
	 * Prechod do nadradeného adresára.
	 *
	 * Funkcia odoberie jednu položku z cesty. Ak už nie je žiaden adresár,
	 * ktorý by sa dal odobrať pridá sa '..' (nadradený adresár) v prípade,
	 * že cesta je lokálna, alebo sa neudeje nič v prípade, že cesta je
	 * globálna.
	 */
	public function &pop()
	{
		if (count($this->_pathArr) === 0 || $this->_pathArr[count($this->_pathArr) - 1] === '..') {
			// Prechod do nadradeného adresára mimo základného adresára
			$this->_safe = false;
			array_push($this->_pathArr, '..');
		}
		else if (count($this->_pathArr) === 1 && is_null($this->_pathArr[0])) {
			// Žiaden nadradený adresár neexistuje
		}
		else {
			array_pop($this->_pathArr);
		}
		return $this;
	}

	/**
	 * Nastavenie cesty na novú hodnotu.
	 *
	 * Atribút \a path má rovnakú štruktúru ako ten, ktorý sa posiela
	 * konštruktoru.
	 *
	 * \sa __construct()
	 */
	public function setPath($path)
	{
		$this->_pathArr = array();
		$this->_safe = true;

		$this->push($path);
	}

	/**
	 * Prevod cesty na reťazec.
	 */
	public function __toString()
	{
		if (count($this->_pathArr) === 1 && is_null($this->_pathArr[0]))
			return '/';
		return implode('/', $this->_pathArr);
	}

	/**
	 * Prevod cesty na reťazec skrátený o koreňový adresár.
	 */
	public function toStringNoRoot()
	{
		$arr = $this->_pathArr;
		if (count($arr) > 0 && is_null($arr[0]))
			array_shift($arr);
		return implode('/', $arr);
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

/**
 * \brief Abstraktná trieda na tvorbu odkzov pre \shakal.
 * \ingroup Shakal_Url
 * \licenses \gpl
 *
 * Pomocou tejto triedy je možné vytvoriť adresu pre \shakal obsahujúcu cestu,
 * <em>premenné</em>, <em>dočasné premenné</em> a <em>surové premenné</em>.
 *
 * \section shakal_link_cesta Cesta
 *
 * Cesta je časť URL adresy, podľa ktorej \shakal vyberá pomocou triedy Router
 * moduly, ktoré sa načítajú. Cestu je možné bezpečne poskladať zo znakov
 * a-z,A-Z a pomlčka(-). Ostatné znaky môžu pri niektorých typoch URL fungovať,
 * ale celkovo ich fungovanie nei je zaručené.
 *
 * Cesta sa môže podľa konkrétnej implementácie v url objaviť ako premenná show
 * (<tt>index.php?show=adresar_podadresar_konecnacesta</tt>) v prípade, že nie
 * je podoporovaný rewrite engine, alebo ako skutočná cesta napodobňujúca
 * statickú štruktúru stránky (<tt>/adresar/podadresar/konecnacesta</tt>).
 *
 * \section shakal_link_premenne Premenné
 *
 * V systéme \shakal sa vyskytujú 3 druhy premenných. Štandardné premenné môžu
 * (v závislosti od implementácie) vystupovať v časti cesty. Preto pre ich názvy
 * a hodnoty platia rovnaké pravidlá ako pre cestu - povolené znaky sú a-z,A-Z a -.
 * U dočasných a "surových" premenných sú dovolené všetky znaky.
 *
 * Každá premenná môže byť lokálna pre niektorý modul (bude možný prístup k tejto
 * premennej len cez modul), alebo globálna (dostupná pre všetky moduly).
 *
 * Rozdiel medzi dočasnými a "surovými" premennými je v tom, že dočasné premenné
 * sa pri vytvorení odkazu na aktuálnu stránku (napr. pre účely podstránky)
 * nekopírujú do novej adresy.
 *
 * Prehľad typov premenných je v nasledujúcej tabuľke:
 * <table>
 *   <tr>
 *     <th>Typ premennej</th><th>Ukážka URL</th><th>Prikazy pre manipuláciu</th>
 *   </tr>
 *   <tr>
 *     <td rowspan="2">Štandardná</td>
 *     <td><tt>/index.php?show=cesta&amp;modul_nazov=hodnota</tt></td>
 *     <td rowspan="2">addVar()<br>remVar()<br>addVars()</td>
 *   </tr>
 *   <tr>
 *     <td><tt>/cesta/modul_nazov_hodnota/</tt></td>
 *   </tr>
 *   <tr>
 *     <td rowspan="2">Dočasná</td>
 *     <td><tt>/index.php?show=cesta&amp;<b>t_</b>modul_nazov=hodnota</tt></td>
 *     <td rowspan="2">addTempVar()<br>remTempVar()<br>addTempVars()</td>
 *   </tr>
 *   <tr>
 *     <td><tt>/cesta/?<b>t_</b>modul_nazov=hodnota</tt></td>
 *   </tr>
 *   <tr>
 *     <td rowspan="2">"Surová"</td>
 *     <td><tt>/index.php?show=cesta&amp;modul_nazov=hodnota</tt></td>
 *     <td rowspan="2">addRawVar()<br>remRawVar()<br>addRawVars()</td>
 *   </tr>
 *   <tr>
 *     <td><tt>/cesta/?modul_nazov=hodnota</tt></td>
 *   </tr>
 * </table>
 */
abstract class Link
{
	private $_path     = null;
	private $_vars     = array();
	private $_tempVars = array();
	private $_rawVars  = array();
	private $_defaultModule = '';

	/**
	 * Vyrenderovanie odkazu do formátu vhodného pre ďalšie použitie (napríklad reťazec).
	 */
	abstract function render();

	/**
	 * Vytvorenie noveého odkazu s cestou \a path.
	 *
	 * \sa setPath()
	 */
	public function __construct($path = null)
	{
		if (!is_null($path))
			$this->setPath($path);
	}

	/**
	 * Nastavenie cesty na novú hodnotu \a path.
	 *
	 * \sa __construct()
	 */
	public function setPath($path)
	{
		if ($path instanceof Path)
			$this->_path = $path;
		elseif (is_null($path))
			$this->_path = null;
		else
			$this->_path = new Path($path);
	}

	/**
	 * Pridanie ďalších podadresárov do cesty.
	 *
	 * Ak pridaná cesta nie je lokálna je volanie tejto funkcie ekvivalentné
	 * s volaním setPath.
	 *
	 * \note Táto funkcia sa bežne používa pri vygenerovaných odkazoch, ktoré
	 * majú prednastavenú určitú základnú cestu.
	 *
	 * \sa setPath
	 */
	public function pathAppend($path)
	{
		if (is_null($this->_path))
			$this->_path = new Path($path);
		else
			$this->_path->push($path);
	}

	/**
	 * Získanie aktuálnej cesty.
	 */
	public function path()
	{
		return $this->_path;
	}

	private function createModuleSubarray(&$array, $module)
	{
		if (is_null($module))
			$module = '';
		if (!isset($array[$module]))
			$array[$module] = array();
	}

	private function addVarToArray(&$array, $name, $var, $module)
	{
		if ($module === false)
			$module = $this->_defaultModule;
		$this->createModuleSubArray($array, $module);

		if (is_array($var)) {
			foreach ($var as $key => $value) {
				$array[$module][$key] = $value;
			}
		}
		else {
			$array[$module][$name] = $var;
		}
	}

	private function remVarFromArray(&$array, $name, $mdoule)
	{
		if ($module === false)
			$module = $this->_defaultModule;
		if (is_null($module))
			$module = '';

		if (isset($array[$mdoule][$name]))
			unset($array[$module][$name]);
	}

	/// \name Premenné
	//@{
	/**
	 * Nastavenie premennej \a name modulu \a module na hondotu \a value.
	 *
	 * \sa addTempVar(), addRawVar()
	 */
	public function addVar($name, $value, $module = false)
	{
		$this->addVarToArray($this->_vars, $name, $value, $module);
	}

	/**
	 * Zrušenie premennej \a name modulu \a module.
	 *
	 * \sa remTempVar(), remRawVar()
	 */
	public function remVar($name, $module = false)
	{
		$this->remVarFromArray($this->_vars, $name, $module);
	}

	/**
	 * Pridanie premenných \a vars modulu \a module.
	 *
	 * Premenné sú poľom, ktorého kľúčom je názov premennej. Hodnotou je hodnota
	 * premennej.
	 *
	 * \sa addTempVars(), addRawVars()
	 */
	public function addVars($vars, $module = false)
	{
		$this->addVarToArray($this->_vars, null, $vars, $module);
	}
	//@}

	/// \name Dočasné premenné
	//@{
	/**
	 * Nastavenie dočasnej premennej \a name modulu \a module na hodnotu \a value.
	 *
	 * \sa addVar(), addRawVar()
	 */
	public function addTempVar($name, $value, $module = false)
	{
		$this->addVarToArray($this->_tempVars, $name, $value, $module);
	}

	/**
	 * Zrušenie dočasnej pemennej \a name modulu \a module.
	 *
	 * \sa remVar(), remRawVar()
	 */
	public function remTempVar($name, $module = false)
	{
		$this->remVarFromArray($this->_tempVars, $name, $module);
	}

	/**
	 * Pridanie dočasných premenných \a vars modulu \a module.
	 *
	 * \sa addVars(), addRawVars()
	 */
	public function addTempVars($vars, $module = false)
	{
		$this->addVarToArray($this->_tempVars, null, $vars, $module);
	}
	//@}

	/// \name "Surové" premenné
	//@{
	/**
	 * Nastavenie premennej \a name, modulu \a module na hodnotu \a value ktorá môže obsahovať vwetky znaky (nie len a-z,A-z,-).
	 *
	 * \sa addVar(), addTempVar()
	 */
	public function addRawVar($name, $value, $module = false)
	{
		$this->addVarToArray($this->_rawVars, $name, $value, $module);
	}

	/**
	 * Zrušenie "surovej" premennej \a name modulu \a module.
	 *
	 * \sa remVar(), remTempVar()
	 */
	public function remRawVar($name, $module = false)
	{
		$this->remVarFromArray($this->_rawVars, $name, $module);
	}

	/**
	 * Pridanie "surových" premenných \a vars modulu \a module.
	 *
	 * \sa addVars(), addTempVars()
	 */
	public function addRawVars($vars, $module = false)
	{
		$this->addVarToArray($this->_rawVars, null, $vars, $module);
	}
	//@}

	/**
	 * Prevod odkazu na reťazec.
	 */
	public function __toString()
	{
		return $this->render();
	}

	/**
	 * Nastavenie východzieho modulu.
	 *
	 * Východzí modul sa použije v akejkoľvek metóde na manipuláciu
	 * s premennými v prípade, že nie je nastavená premenná module.
	 *
	 * Štandardný modul je po vytvorení inštancie nastavený na \e null.
	 * V takomto prípade premenné neplatia pre konkrétny modul, ale sú
	 * dostupné z každého modulu.
	 */
	public function setDefaultModule($module)
	{
		$this->_defaultModule = $module;
	}

	/**
	 * Zistenie hodnoty štandardného modulu.
	 */
	public function defaultModule()
	{
		return $this->_defaultModule;
	}

	/**
	 * Získanie premenných vo forme asociatívneho poľa.
	 *
	 * \sa getTempVars(), getRawVars()
	 */
	protected function getVars()
	{
		return $this->_vars;
	}

	/**
	 * Získanie dočasných premenných vo forme asociatívneho poľa.
	 *
	 * \sa getVars(), getRawVars()
	 */
	protected function getTempVars()
	{
		return $this->_tempVars;
	}

	/**
	 * Získanie "surových" premenných vo forme asociatívneho poľa.
	 *
	 * \sa getVars(), getTempVars()
	 */
	protected function getRawVars()
	{
		return $this->_rawVars;
	}
}

/**
 * \brief Štandardný HTTP odkaz používajúci premenné v URL.
 * \ingroup Shakal_Url
 * \licenses \gpl
 */
class HttpLink extends Link
{
	private function renderHttpVar($name, $value)
	{
		return urlencode($name) . '=' . urlencode($value);
	}

	private function buildVarName($module, $name, $prefix)
	{
		$nprefix = '';
		if ($prefix === '') {
			if (strlen($name) >= 2) {
				$sub = substr($name, 0, 2);
				if ($sub === 't_' || $sub === 'r_')
					$nprefix = 'r_';
			}
		}
		else
			$nprefix = $prefix;

		if (!empty($module))
			$nprefix .= $module . '_';
		return $nprefix . $name;
	}

	private function mergeVarArray($vars, $append)
	{
		foreach ($append as $name => $value) {
			if (!isset($vars[$name]))
				$vars[$name] = array();
			$vars[$name] = array_merge($vars[$name], $value);
		}
		return $vars;
	}

	/**
	 * Renderovanie premenných pre http adresu do poľa reťazcov.
	 *
	 * Jednotlivé reťazce typu ošetrenáPremenná=ošetrenáHodnota
	 * je možné spojiť pomocou &amp; a použiť v adrese.
	 */
	protected function renderHttpVars($vars, $prefix = '')
	{
		$out = array();
		foreach ($vars as $module => $varArray) {
			foreach ($varArray as $name => $value){
				$name  = urlencode($this->buildVarName($module, $name, $prefix));
				$value = urlencode($value);
				array_push($out, $name . '=' . $value);
			}
		}
		return $out;
	}

	/**
	 * Získanie základnej adresy, v ktorej sa nachádza \shakal.
	 */
	protected function getBaseAddress() {
		return ConfigRegistry::get('shakal_base_path');
	}

	public function render()
	{
		// Pri bežných odkazoch (bez rewrite) nie je rozdiel
		// medzi raw položkami a štandardnými ascii položkami.
		if (!is_null($this->path())) {
			$path = $this->path();
			$arr  = $path->pathArr();
			$show = '';
			$i = 0;
			foreach ($arr as $component) {
				if (is_null($component))
					continue;
				$show .= (($i > 0) ? '_' : '') . $component;
				++$i;
			}
			$vars = array('' => array('show' => $show));
		}
		else
			$vars = array();
		$vars = $this->mergeVarArray($vars, $this->getVars());
		$vars = $this->mergeVarArray($vars, $this->getRawVars());
		$vars = $this->renderHttpVars($vars);
		$temp = $this->getTempVars();
		$temp = $this->renderHttpVars($temp, 't_');
		$all  = array_merge($vars, $temp);
		$baseScript = $this->getBaseAddress() . ConfigRegistry::get('shakal_base_script');
		if (count($all) === 0)
			return $baseScript;
		else
			return $baseScript . '?' . implode('&', $all);
	}
}

/**
 * \brief HTTP odkaz využívajúci rewrite engine.
 * \ingroup Shakal_Url
 * \licenses \gpl
 *
 * Tieto odkazy umožňujú imitáciu odkazov na statických stránkach.
 * Odkazy tohto typu sa používajú pre zvýšenie SEO.
 */
class RewriteHttpLink extends HttpLink
{
	private function renderPath()
	{
		$path = $this->path();
		if (is_null($path))
			return '';
		return $path->toStringNoRoot() . '/';
	}

	private function renderRewriteVars()
	{
		$out = array();
		$vars = $this->getVars();
		foreach ($vars as $module => $varArray) {
			foreach ($varArray as $name => $value) {
				$text = '';
				if ($module !== '')
					$text = $moudle . '_';
				$text .= $name . '_';
				$text .= $value;
				array_push($out, $text);
			}
		}

		if (count($out) === 0)
			return '';
		else
			return implode('/', $out) . '/';
	}

	public function render()
	{
		$vars = $this->getRawVars();
		$vars = $this->renderHttpVars($vars);
		$temp = $this->getTempVars();
		$temp = $this->renderHttpVars($temp, 't_');
		$out  = $this->getBaseAddress() . $this->renderPath() . $this->renderRewriteVars();
		$all  =  array_merge($vars, $temp);
		if (count($all) > 0) {
			$out .= '?' . implode('&', $all);
		}
		return $out;
	}
}

?>
