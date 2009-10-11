<?php
/**
 * \file
 * \author Miroslav Bendík
 * \brief Prístup k databáze a základné konštanty používané %SQL modulom.
 */

namespace Shakal;

/**
 * \brief Trieda pre jednoduchý prístup k databáze
 * \ingroup Shakal_SQL
 * \licenses \gpl
 *
 * \section shakal_sql_dotazy Spôsob zápisu dotazov
 *
 * \subsection shakal_sql_identifikatory Spôsob zápisu tabuliek a stĺpcov
 *
 * Názov tabuľky sa začína reťazcom '\#__'. Táto konvencia bola zavedená kvôli
 * zjednodušniu rozpoznávania názvu tabuľky a stĺpca. Zároveň symbolizuje prefix
 * tabuľky, ktorý sa pridá pred jej názov v prípade, že bol prefix nastavený.
 *
 * Stĺpce tabuľky sa nijako neupravujú, ich zápis je teda 'nazov_stlpca'.
 *
 * Identifikátory sa zapisujú buď ako samostatný názov tabuľky (teda
 * '\#__tabulka'), alebo ako samostatný názov stĺpca tabuľky (teda
 * 'nazov_stlpca'), alebo spolu tvoria identifikátor tabuľky a jej stĺpca
 * (\#__tabulka.nazov_stlpca).
 *
 * <h2>Typy výsledkov fetchArray\anchor SQL_resultTypeDesc</h2>
 * Typ vráteného výsledku môže mať nasledujúce hodnoty:
 * <table>
 *   <tr><th>Typ</th><th>Popis</th></tr>
 *   <tr>
 *     <td>SqlResult::Assoc</td>
 *     <td>
 * Vrátenie výsledku v podobe asociatívneho poľa.
 *
 * Príklad:
 * \verbatim
pole[id]   = 1
pole[text] = 'abc'\endverbatim
 *     </td>
 *   </tr>
 *   <tr>
 *     <td>SqlResult::Num</td>
 *     <td>
 * Vrátenie výsledku vo forme homogénneho poľa.
 *
 * Príklad:
 * \verbatim
pole[0] = 1
pole[1] = 'abc'\endverbatim
 *     </td>
 *   </tr>
 *   <tr>
 *     <td>SqlResult::Both</td>
 *     <td>
 * Vrátenie výsledku ako asociatívne pole a zároveň homogénne pole.
 * \verbatim
pole[0]    = 1
pole[id]   = 1
pole[1]    = 'abc'
pole[text] = 'abc'\endverbatim
 *     </td>
 *   </tr>
 * </table>
 */
class SQL
{
	/**
	 * \name Typy premennej.
	 * \anchor SQL_dataType
	 * Tieto konštanty sa používajú na určenie spôsobu, akým sa ošetria premenné
	 * vkladané do databázy.
	 */
	/// \name Dátové typy rozpoznávané týmto rozhraním k databáze.
	///@{

	/// \brief Binárne dáta, obvykle sa kódujú rovnako ako textové dáta.
	const Blob     = 'l';
	/// \brief Číslo s plávajúcou desatinnou čiarkou.
	const Float    = 'f';
	/// \brief Celé číslo.
	const Int      = 'n';
	/// \brief Textové dáta.
	const Text     = 's';
	///@{
	/// \brief Stĺpec tabuľky.
	const Field    = 'c';
	/// \brief Názov tabuľky.
	const Table    = 'i';
	/// \brief Typ dátum.
	const Date     = 'd';
	/// \brief Čas.
	const Time     = 't';
	/// \brief Typ dátum a čas.
	const DateTime = 'x';
	/// \brief Dátum a čas v unixovom formáte.
	const UnixTime = 'u';
	///@}

	/**
	 * \name Typ výsledku.
	 * \anchor SQL_resultType
	 * Užívateľ môže pri získavaní poľa dát z databázy požadovať nasledujúce
	 * formy dát.
	 */
	///@{
	/// \brief Asociatívne pole, ktorého kľúčom je názov stĺpca a hodnotou hodnota stĺpca.
	const Assoc = 1;
	/// \brief Homogénne pole obsahujúce stĺpce tabuľky v rovnakom poradí ako v %SQL dotaze.
	const Num   = 2;
	/// \brief Vrátenie výsledkov ako asociatívne a homogénne pole zároveň.
	const Both  = 3;
	///@}
}
?>
