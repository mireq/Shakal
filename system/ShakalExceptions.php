<?php
/**
 * \file
 * \author Miroslav Bendík
 * \brief Výnimky používane v Shakal CMS.
 */
namespace Shakal;

/**
 * \brief Základná výnimka v Shakal CMS.
 * \ingroup Shakal_Exceptions
 * \licenses \gpl
 *
 * Na rozdiel od štandardných výnimiek v PHP má táto výnimka
 * navyše ple \e data používané pre rozšírené informácie
 * o chybe, ktorá nastala.
 */
abstract class Exception extends \Exception
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
class SystemException extends Exception
{
	const OtherError = 0; /**< Nešpecifikovaná chyba. */
	const NotFound   = 1; /**< Chyba vyvolaná pri neexistujúcej stránke. */

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
class UserException extends Exception
{
	const UserNotice = 0; /**< Poznámka, ktorá má minimálny vplyv na ďalší beh aplikácie. */
	const UserWaring = 1; /**< Varovanie pri akciách ktoré je možné napriek tomu dokončiť. */
	const UserError  = 2; /**< Chyba pri vykonávaní akcie. */

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
class SqlException extends Exception
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

	/**
	 * Dotaz, pri ktorom nastala výnimka.
	 */
	public function query()
	{
		return parent::data();
	}
}
?>
