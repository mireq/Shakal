<?php
/**
 * \file
 * \author Miroslav Bendík
 * \brief Rozhania používané SQL modulom.
 */

namespace Shakal;

/**
 * \brief Rozhranie pre nízkoúrovňový prístup k %SQL databáze.
 * \ingroup Shakal_SQL
 * \licenses \gpl
 *
 * Rozhranie SQLDriver implementujú všetky triedy pre prístup ku konkrétnym
 * databázam (mysql, postgresql ...).
 */
interface ISQLDriver
{
	/**
	 * Pripojenie k databázovému serveru.
	 *
	 * \param array settings Nastavenie podobné ako v SQLConnection::connect() V
	 * tomto prípade nemá zmysel špecifikovať typ databázy.
	 * \throw SQLException
	 */
	public function connect($settings);

	/**
	 * Odpojenie sa od databázy.
	 * \throw SQLException
	 */
	public function disconnect();

	/**
	 * Ošetrenie dát vkladaných do databázy.
	 *
	 * \param mixed value Hodnota, ktorá sa má upraviť pre vstup do databázy.
	 * \param string type Typ dát pre ošetrenie. Ak nebude zadaný typ použije sa
	 * autodetekcia.
	 * \return string
	 */
	public function escape($value, $type = null);

	/**
	 * Získanie dát z databázy.
	 *
	 * \param resource result Výsledok nízkoúrovňového volania *sql_query.
	 * \param int      type   Typ požadovaného výsledku. Podrobnosti o type sú v
	 *     sekcii \ref SQL_resultType "Typ výsledku".
	 * \return array
	 */
	public function fetchArray($result, $type);

	/**
	 * Vytvorenie objektu z riadku tabuľky.
	 *
	 * \param resource result
	 *     Výsledok nízkoúrovňového vlania *sql_query.
	 * \param string className
	 *     Názov triedy, ktorej inštancia sa má vytvoriť. V prípade, že nie je
	 *     zadaná žiadna trieda vytvorí sa inštancia stdClass.
	 * \param array params
	 *     Pole voliteľných parametrov poslaných konštruktoru triedy.
	 *
	 * \return mixed
	 *     Objekt obsahujúci dáta riadku. V prípade neúspechu vráti \e FALSE.
	 */
	public function fetchObject($result, $className = 'stdClass', $params = null);

	/**
	 * Spustenie natívneho %SQL dotazu.
	 *
	 * \param string query Dotaz na databázu.
	 * \return resource Nízkoúrovňový výsledok volania *sql_query.
	 * \throw SQLException
	 */
	public function nativeQuery($query);

	/// \name Transakcie
	///@{
	/**
	 * Začiatok transakcie identifikovanej reťazcom \a savePoint. Nie všetky
	 * databázové servery dokážu použiť argument \a savePoint, v prípade, že
	 * nie je podporovaný bude ignorovaný.
	 * \param string savePoint
	 * \sa commit(), rollback()
	 * \throw SQLException
	 */
	public function begin($savePoint = null);

	/**
	 * Potvrdenie transakcie identifikovanej reťazcom \a savePoint.
	 * \param string savePoint
	 * \sa begin(), rollback()
	 * \throw SQLException
	 */
	public function commit($savePoint = null);

	/**
	 * Zrušenie transakcie identifikovanej reťazcom \a savePoint.
	 * \param string savePoint
	 * \sa begin(), commit()
	 * \throw SQLException
	 */
	public function rollback($savePoint = null);
	///@}

	/**
	 * Vrátenie počtu riadkov, ktoré obsahuje výsledok volania \a result. Ak
	 * výsledok neobsahuje žiadne riadky (pretože dotaz bol aktualizácia, alebo
	 * pridanie riadkov) vráti \e false.
	 * \param resource result
	 * \return int|bool
	 */
	public function numRows($result);

	/**
	 * Táto meóda vráti počet riadkov ovplyvnených predchádzajúcim dotazom.
	 * \return int
	 */
	public function affectedRows();

	/**
	 * Získanie poslendého vloženého ID. U databáz, ktoré podporujú sek uvádza
	 * názov sekvencie, ktorá sa používa na auto increment. Ak to databáza
	 * nepodporuje (napr. mysql) bude hodnota ignorovaná. Ak predchádzajúci
	 * dotaz negeneroval žiadne auto increment ID vráti funkcia 0.
	 *
	 * \param string seqName
	 * \return int
	 */
	public function lastInsertId($seqName = null);

	/**
	 * Uvoľnenie dát z výsledku select-u. V prípade úspechu vráti \e true.
	 *
	 * \param resource result Výsledok volania *sql_query.
	 * \return bool
	 */
	public function free($result);

	/**
	 * Prechod na požadovaný riadok výsledku. Pri úspechu vráti funkcia \e true.
	 * \param resource result
	 * \param int row
	 * \return bool
	 */
	public function seek($result, $row);
}

?>
