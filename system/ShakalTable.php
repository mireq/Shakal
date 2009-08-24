<?php
/**
 * \file
 * \author Miroslav Bendík
 * \brief Abstraktné rozhranie pre tvorbu tabuliek.
 */

//BEGIN --- ShakalTableCell ---
/**
 * \brief Bunka tabuľky.
 * \ingroup Shakal_Table
 * \licenses \gpl
 *
 * Táto abstraktná trieda reprezentuje bunku v tabuľke.
 * Každej bunke je možné priradiť vlastné atribúty.
 */
abstract class ShakalTableCell
{
	/// Dáta bunky tabuľky.
	protected $_data;
	/// Informácia o tom, či je bunka hlavičkou.
	protected $_headerCell = false;
	/** Atribúty bunky.
	 *
	 * Kľúčom v poli atribútov je názov atribútu. Hodnota je hodnotou atribútu
	 * bunky.
	 */
	protected $_attributes = array();

	/**
	 * Renderovanie bunky tabuľky.
	 *
	 * Táto metóda musí byť implementovaná v jej potomkoch. Jej volanie
	 * spôsobí "vyrenderovanie" bunky do dátovej štruktúry používanej
	 * šablónou. Pre html šablónu to je html kód. XML šablóny používajú
	 * DOM objekt.
	 *
	 * \return Spracované dáta reprezentujúce bunku tabuľky.
	 */
	public abstract function render();

	/**
	 * Vytvorenie novej bunky tabuľky.
	 *
	 * Volanie tejto metódy s argumentom \a data je ekvivalentné
	 * volaniu bez agrumentu \a data a následnému nastaveniu dát
	 * metódou setData.
	 *
	 * \sa setData
	 */
	public function __construct($data = null)
	{
		$this->setData($data);
	}

	/**
	 * Získanie dát uložených v bunke.
	 *
	 * \sa setData
	 */
	public function data()
	{
		return $this->_data;
	}

	/**
	 * Nastavenie dát bunky.
	 *
	 * Dáta sú v takej forme, ktorá sa priamo zobrazuje klientovi.
	 * Môže teda obsahovať HTML značky. V prípade čistých textových
	 * dát je preto nutné použiť htmlspecialchars (ak je požadovaný
	 * výstup xhtml, alebo xml transformované do xhtml).
	 *
	 * \sa data
	 */
	public function setData($data)
	{
		$this->_data = $data;
	}

	/**
	 * Táto funkcia nastavuje bunku tabuľky ako hlavičku.
	 *
	 * Nastavenie tejto hodnoty na \e true spôsobí pri html
	 * zmenu tagu \e td na \e th.
	 *
	 * \sa isHeaderCell
	 */
	public function setHeaderCell($headerCell)
	{
		$this->_headerCell = $headerCell;
	}

	/**
	 * Metóda vráti true, ak bunka je hlavičkou.
	 *
	 * \sa setHeaderCell
	 */
	public function isHeaderCell()
	{
		return $this->_headerCell;
	}

	/// Nastavenie atribútu bunky.
	public function __set($name, $value)
	{
		$this->_attributes[$name] = $value;
	}

	/// Získanie atribútu bunky.
	public function __get($name)
	{
		return $this->_attributes[$name];
	}
}
//END   --- ShakalTableCell ---

//BEGIN --- ShakalTableRow ---
/**
 * \brief Riadok tabuľky.
 * \ingroup Shakal_Table
 * \licenses \gpl
 *
 * Táto abstraktná trieda reprezentuje riadok tabuľky. Jeho atribútý
 * je možné nastavovať podobne ako u ShakalTableCell.
 */
abstract class ShakalTableRow
{
	/// Pole buniek riadku tabuľky.
	protected $_cells      = array();
	/// Zoznam atribútov riadku.
	protected $_attributes = array();

	/**
	 * Renderovanie riadku tabuľky a jeho buniek.
	 *
	 * \sa ShakalTableCell::render
	 */
	public abstract function render();

	/**
	 * Vytvorenie novej bunky tabuľky.
	 *
	 * Táto funkcia vytvára novú bunku podľa typu tabušky.
	 */
	protected abstract function newTableCell($data);

	/**
	 * Vytvorenie nového riadku tabuľky.
	 *
	 * Volanie konštruktora s argumentom \a data
	 * je ekvivalentné volaniu bez argumentov a následnému
	 * nastaveniu dát pomocou setData().
	 *
	 * \sa setData
	 */
	public function __construct(array $data = array())
	{
		$this->setData($data);
	}

	/**
	 * Získanie riadkov tabuľky.
	 *
	 * \sa setData
	 */
	public function data()
	{
		return $this->_cells;
	}

	/**
	 * Nastavenie dát riadku.
	 *
	 * Dáta sú vo forme poľa. Jeho položky sú inštanciami ShakalTableCell.
	 * Chybné položky sú ignorované.
	 *
	 * \sa data
	 */
	public function setData(array $data)
	{
		foreach($data as $cell) {
			$this->addCell($cell);
		}
	}

	/// Pridanie bunky tabuľky.
	public function addCell($cell)
	{
		if (!is_object($cell) || !$cell instanceof ShakalTableCell) {
			$cell = $this->newTableCell($cell);
		}
		array_push($this->_cells, $cell);
	}

	/// Nastavenie atribútu riadku.
	public function __set($name, $value)
	{
		$this->_attributes[$name] = $value;
	}

	/// Získanie atribútu riadku.
	public function __get($name)
	{
		return $this->_attributes[$name];
	}
}
//END   --- ShakalTableRow ---

//BEGIN --- ShakalTable ---
/**
 * \brief Tabuľka.
 * \ingroup Shakal_Table
 * \licenses \gpl
 *
 * Úlohou abstraktnej triedy ShakalTable je uchovávať riadky tabuľky.
 * Pre použitie tejto triedy je nutné implementovať abstraktné funkcie
 * render a newTableRow.
 */
abstract class ShakalTable
{
	/// Zoznam riadkov tabuľky.
	protected $_rows       = array();
	/// Zoznam riadkov hlavičky tabuľky.
	protected $_headerRows = array();
	/// Titulok tabuľky.
	protected $_caption    = array();
	/// Atribúty tabuľky.
	protected $_attributes = array();

	/**
	 * Vyrenderovanie tabuľky a jej riadkov.
	 *
	 * \sa ShakalTableRow::render
	 */
	public abstract function render();
	/**
	 * Vytvorenie nového riadku tabuľky.
	 */
	protected abstract function newTableRow($data);

	/**
	 * Vytvorenie novej tabuľky.
	 *
	 * @param data       Dáta tabuľky (pri výstupe do html sú v tagu tbody).
	 * @param headerData Dáta v hlavičke tabuľky (pri výstupe do html sú v tagu thead).
	 * @param caption    Titulok tabuľky.
	 */
	public function __construct(array $data = array(), array $headerData = array(), $caption = null)
	{
		$this->setData($data);
		$this->setHeaderData($headerData);
		$this->setCaption($caption);
	}

	/// Získanie spracovaných dát tabuľky.
	public function data()
	{
		return $this->_rows;
	}

	/// Získanie spracovaných dát hlavičky tabuľky.
	public function headerData()
	{
		return $this->_headerRows;
	}

	/// Získanie titulku tabuľky.
	public function caption()
	{
		return $this->_caption;
	}

	/**
	 * Nastavenie dát tabuľky.
	 *
	 * Funkcia prechádza pole a postupne pridáva jeho položky ako riadky.
	 *
	 * \sa addRow
	 */
	public function setData(array $data)
	{
		foreach($data as $row) {
			$this->addRow($row);
		}
	}

	/**
	 * Nastavenie dát hlavičky tabuľky.
	 *
	 * \sa setData
	 */
	public function setHeaderData(array $data)
	{
		foreach($data as $row) {
			$this->addHeaderRow($row);
		}
	}

	/// Nastavenie titulku tabuľky.
	public function setCaption($caption)
	{
		$this->_caption = $caption;
	}

	/**
	 * Pridanie riadku tabuľky.
	 *
	 * V prípade, že argument \a row nie je inštanciu ShakalTableRow
	 * bude automaticky na tento typ konvertovaný.
	 *
	 * \sa addHeaderRow
	 */
	public function addRow($row)
	{
		if (!is_object($row) || !$row instanceof ShakalTableRow) {
			$row = $this->newTableRow($row);
		}
	}

	/**
	 * Pridanie riadku hlavičky tabuľky.
	 *
	 * \sa addRow
	 */
	public function addHeaderRow($row)
	{
		if (!is_object($row) || !$row instanceof ShakalTableRow) {
			$row = $this->newTableRow($row);
		}
	}

	/// Nastavenie atribútu tabuľky.
	public function __set($name, $value)
	{
		$this->_attributes[$name] = $value;
	}

	/// Získanie atribútu tabuľky.
	public function __get($name)
	{
		return $this->_attributes[$name];
	}
}
//END   --- ShakalTable ---


?>
