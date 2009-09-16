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
abstract class BaseException extends \Exception
{
	private $data;

	/**
	 * Vytvorenie novej vynimky.
	 *
	 * \param string code  Kód výnimky.
	 * \param int    msg   Správa pre užívateľa.
	 * \param mixed  data  Rozšírené informácie o výnimke.
	 */
	public function __construct($msg = '', $code = 0, $data = null)
	{
		parent::__construct($msg, $code);
		$this->data = $data;
	}

	/**
	 * Získanie rozšírených dát k výnimke.
	 *
	 * @return mixed
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
class SystemException extends BaseException
{
	const OtherError = 0; /**< Nešpecifikovaná chyba. */
	const NotFound   = 1; /**< Chyba vyvolaná pri neexistujúcej stránke. */

	/**
	 * Vytvorenie novej systémovej výnimky.
	 *
	 * \param string msg   Správa, ktorá sa zobrazí užívateľovi pri vyvolaní výnimky.
	 * \param int    code  Typ vyvolanej výnimky.
	 * \param mixed  data  Dáta spresňujúce výnimku.
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
class UserException extends BaseException
{
	const UserNotice = 0; /**< Poznámka, ktorá má minimálny vplyv na ďalší beh aplikácie. */
	const UserWaring = 1; /**< Varovanie pri akciách ktoré je možné napriek tomu dokončiť. */
	const UserError  = 2; /**< Chyba pri vykonávaní akcie. */

	/**
	 * Vytvorenie novej užívateľskej výnimky.
	 *
	 * \param string msg   Správa pre užívateľa.
	 * \param int    code  Typ výnimky.
	 * \param mixed  data  Dodatočné dáta.
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
class SqlException extends BaseException
{
	/**
	 * Vytvorenie SQL výnimky.
	 *
	 * \param string msg   Chybová správa.
	 * \param int    code  Chybový kód vrátený databázou.
	 * \param string query Dotaz, pri ktorom došlo k výnimke.
	 */
	public function __construct($msg, $code, $query)
	{
		parent::__construct($msg, $code, $query);
	}

	/**
	 * Dotaz, pri ktorom nastala výnimka.
	 *
	 * \return string
	 */
	public function query()
	{
		return parent::data();
	}
}
?>
