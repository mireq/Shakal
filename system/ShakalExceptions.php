<?php
/**
 * \file
 * \author Miroslav Bendík
 * \brief Výnimky používane v Shakal CMS.
 */

/**
 * \brief Základná výnimka v Shakal CMS.
 * \ingroup Shakal_Exceptions
 * \licenses \gpl
 *
 * Na rozdiel od štandardných výnimiek v PHP má táto výnimka
 * navyše ple \e data používané pre rozšírené informácie
 * o chybe, ktorá nastala.
 */
abstract class ShakalException extends Exception
{
	private $data;

	/**
	 * Vytvorenie novej vynimky.
	 *
	 * \param code  Kód výnimky.
	 * \param msg   Správa pre užívateľa.
	 * \param data  Rozšírené informácie o výnimke.
	 */
	public function __construct($msg = '', $code = 0, $data = null)
	{
		parent::__construct($msg, $code);
		$this->data = $data;
	}

	/**
	 * Získanie rozšírených dát k výnimke.
	 */
	public final function getData()
	{
		return $this->data;
	}
}



/**
 * \brief Systémová výnimka.
 * \ingroup Shakal_Exceptions
 * \licenses \gpl
 *
 * Táto výnimka má za následok zastavenie behu aplikácie.
 * Užívateľa o chybe informuje samostatnou stránkou.
 */
class ShakalSystemException extends ShakalException
{
	/// Nešpecifikovaná chyba.
	const OtherError = 0;
	/// Chyba vyvolaná pri neexistujúcej stránke.
	const NotFound   = 1;

	/**
	 * Vytvorenie novej systémovej výnimky.
	 *
	 * \param msg   Správa, ktorá sa zobrazí užívateľovi pri vyvolaní výnimky.
	 * \param code  Typ vyvolanej výnimky.
	 * \param data  Dáta spresňujúce výnimku.
	 */
	public function __construct($msg = '', $code = self::OtherError, $data = null)
	{
		parent::__construct($msg, $code, $data);
	}
}



/**
 * \brief Užívateľská výnimka.
 * \ingroup Shakal_Exceptions
 * \licenses \gpl
 *
 * Vyvolanie tejto výnimky spôsobí jej zobrazenie
 * na stránke, ktorú navštívil užívateľ.
 */
class ShakalUserException extends ShakalException
{
	/// Poznámka, ktorá má minimálny vplyv na ďalší beh aplikácie.
	const UserNotice = 0;
	/// Varovanie pri akciách ktoré je možné napriek tomu dokončiť.
	const UserWaring = 1;
	/// Chyba pri vykonávaní akcie.
	const UserError  = 2;

	/**
	 * Vytvorenie novej užívateľskej výnimky.
	 *
	 * \param msg   Správa pre užívateľa.
	 * \param code  Typ výnimky.
	 * \param data  Dodatočné dáta.
	 */
	public function __construct($msg = '', $code = self::UserNotice, $data = null)
	{
		parent::__construct($msg, $code, $data);
	}
}




/**
 * \brief Chyba pri spúšťaní SQL príkazu.
 * \ingroup Shakal_Sql Shakal_Exceptions
 * \licenses \gpl
 *
 * Výnimka pri spúšťaní SQL príkazu.
 */
class ShakalSqlException extends ShakalException
{
	/**
	 * Vytvorenie SQL výnimky.
	 *
	 * @param code  Chybový kód vrátený databázou.
	 * @param msg   Chybová správa.
	 * @param query Dotaz, pri ktorom došlo k výnimke.
	 */
	public function __construct($code, $msg, $query)
	{
		parent::__construct($msg, $code, $query);
	}

	/// Dotaz, pri ktorom nastala výnimka.
	public function query()
	{
		return parent::data();
	}
}
?>
