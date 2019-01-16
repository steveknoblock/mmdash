<?php

	/**
	 * Mmdash
	 *
	 * Cleans up text by removing MS Word and other characters
	 * unsafe for web use.
	 * From the original Mmdash class developed for folkstreams.net to
	 * handle the conversion of text exported from MS Word on mac and
	 * Windows platforms, and other problematic text.
	 *
	 * This class required a signifcant amount of research for such a
	 * small amount of code!
	 *
	 * @package     Mmdash
	 * @subpackage  Libraries
	 * @category    Text Processing
	 * @author      Steve Knoblock
	 * @copyright   Copyright 2007-2015 Steve Knoblock
	 * @license		http://www.gnu.org/licenses/lgpl.html
	 */

namespace Steveknoblock;

	/**
	 * Readme
	 * 
	 * As far as I know, this is not Unicode safe.
	 *
	 * The purpose of this class is to convert legacy MS Word or MacRoman text to something usable. It should
	 * correctly deal with non-standard approaches to curly quotes by translating them to decimal numeric
	 * character references. This should cover MS, MacRoman and possibly Unix approaches. And correcly deal
	 * with em-dashes.
	 *
	 * Why is it called mmdash? Because it started out as an em-dash corrector. You can google em-dash
	 * to understand why they are such a hair-tearing problem.
	 *	 * One of the problems with this issue, is the question, should you treat quotes and em dash problems
	 * as separate from translating any non-ascii chars? Or should they all be handled by one function?
	 *
	 */


class Mmdash
{

	var $use_dec_refs; // do not use entities
	 
	public function __construct() {

		/** Setup arrays that define
		 * character translations
		 */
		$this->set_default_refs();
		$this->set_default_ents();
		
	}
	
	
	public function stripwdchrs ( $text ) {
	
		// strip MS Word special chars: 130-159 dec, 82-9F hex
		$text = preg_replace("/[\x82-\x9f]/","",$text);
		return $text;
	}


	public function xshy( $text ) {
		/* soft hyphen is controversial in meaning, see various w3c docs and discussions on blogs, etc. */	
		$text = preg_replace("/\xa0/","<!-- shy -->",$text);
		return $text;
	}

	/**
	 * "Smart Quotes" fixing
	 *
	 */
	
	/**
	 * Converts MacRoman curly quotes to Unicode
	 * decimal numeric references for curly quotes.
	 * Chr	Unicode
	 * 0xD2	0x201C	# LEFT DOUBLE QUOTATION MARK
	 * 0xD3	0x201D	# RIGHT DOUBLE QUOTATION MARK 
	 * 0xD4	0x2018	# LEFT SINGLE QUOTATION MARK
	 * 0xD5	0x2019	# RIGHT SINGLE QUOTATION MARK
	 * According to:
	 * http://www.unicode.org/Public/MAPPINGS/VENDORS/APPLE/ROMAN.TXT
	 */
	 
	 /** /remark
	  * Handy conversion chart
	  * Hex		Dec
	  * 0x201C	8220	# LEFT DOUBLE QUOTATION MARK
	  *	0x201D  8221	# RIGHT DOUBLE QUOTATION MARK 
	  * 0x2018	8216	# LEFT SINGLE QUOTATION MARK
	  * 0x2019	8217	# RIGHT SINGLE QUOTATION MARK
	  */
	 			
	function fixmacroman ( $text ) {
		$text = preg_replace("/[\xd2]/","&#8220;",$text); // l double
		$text = preg_replace("/[\xd3]/","&#8221;",$text); // r double
		$text = preg_replace("/[\xd4]/","&#8216;",$text); // l single
		$text = preg_replace("/[\xd5]/","&#8217;",$text); // r single
		return $text;
	}
	
	/**
	 * Translate characters used in the Microsoft Non-standard
	 * Approach to to implementing curl quotes and apostrophe.
	 * 0x93		0x201C	8220	# LEFT DOUBLE QUOTATION MARK
	 * 0x94		0x201D  8221	# RIGHT DOUBLE QUOTATION MARK 
	 * 0x91		0x2018	8216	# LEFT SINGLE QUOTATION MARK
	 * 0x92		0x2019	8217	# RIGHT SINGLE QUOTATION MARK
	 * According to:
	 * www.unicode.org/Public/MAPPINGS/VENDORS/MICSFT/WINDOWS/CP1252.TXT
	 * the cp1252 to Unicode chart.
	 */
	 
	function fixmsns ( $text ) {
		$text = preg_replace("/[\x93]/","&#8220;",$text); // l double
		$text = preg_replace("/[\x94]/","&#8221;",$text); // r double
		$text = preg_replace("/[\x91]/","&#8216;",$text); // l single
		$text = preg_replace("/[\x92]/","&#8217;",$text); // r single
		return $text;
	}
	

	// downconvert macroman to ASCII
	// Not the best thing to do, but it was neccessary for some texts.
	function macromantoascii ( $text ) {
		$text = preg_replace("/[\xd2]/",'"',$text);
		$text = preg_replace("/[\xd3]/",'"',$text);
		$text = preg_replace("/[\xd4]/","'",$text);
		$text = preg_replace("/[\xd5]/","'",$text);		
		return $text;
	}
			
	
	function stripgap ( $text ) {
		// run this before safetext
		// strip ranges in MS Word special chars that do not correspond to any Unicode chars, not sure what is in this gap
		$text = preg_replace("/[\x8d-\x90]/","",$text);
		$text = preg_replace("/[\x9d-\x9e]/","",$text);
		return $text;
	}


	/**
	 * Safetext can translate to HTML entities or
	 * or decimal numeric character references (preferred).
	 *
	 */
	 
	function safetext ( $text, $entity ) {
		if( $entity == 1 ) {
		
			$text = strtr($text, $this->xlate_ent);
		} else {
			$text = strtr($text, $this->xlate_ref);
		}
		return $text;
	}


	/**
	 * http://www.dwheeler.com/essays/quotes-in-html.html
	 * always use decimal numeric character references for curling single and double quote characters
	 *&#8220; and &#8221; - and for left and right single quotation marks (and apostrophes), use &#8216; and &#8217; - and you’ll be glad you did. This approach complies with all international standards, and works essentially everywhere.
	 *
	 * Positions 145 through 148 for the windows-1252 charset are
	 * non-standard and conflict with standards.
	 * Unicode and ISO 10646 reserve positions 128 to 159 as control characters
	 * Part of the problem I an encountering now, is that I chose not
	 * to translation the whole windows code page found here
	 * http://www.unicode.org/Public/MAPPINGS/VENDORS/MICSFT/WINDOWS/CP1252.TXT
	 * so the characters that are causing trouble are
	 * 0xD2	0x00D2	#LATIN CAPITAL LETTER O WITH GRAVE
	 * 0xD3	0x00D3	#LATIN CAPITAL LETTER O WITH ACUTE
	 * 0xD5	0x00D5	#LATIN CAPITAL LETTER O WITH TILDE
	 * but these can't be the intended characters by
	 * the context. But vi identifes them as this. Maybe they are Mac.
	 */
	 

	/**
	 * Setup translation arrays.
	 *
	 */


	function set_default_refs()
	{
		// Note: Not sure if \xa0 and \xad translate to correct Unicode references for nonbreakspace and softhyph
		$this->xlate_ref = array
			(
			
				// MacRoman "smart quote" quotation mark characters
				"\xd2" => "&#8220;", // left curl double quote
				"\xd3" => "&#8221;", // right curl double quote
				"\xd4" => "&#8216;", // left single curl quote
				"\xd5" => "&#8217;", // right single curl quote
				
				// unfortunately, it would take a regext to intelligently
				// see if this is an apostrophe by checking that it sits
				// between two letters and that may not be perfect.
				//"\xd5" => "&#8218;", // curl apostrophe

				"\x82" => "&#8218;",
				"\x83" => "&#402;",
				"\x84" => "&#8222;",
				"\x85" => "&#8230;",
				"\x86" => "&#8224;",
				"\x87" => "&#8225;",
				"\x88" => "&#710;",
				"\x89" => "&#8240;",
				"\x8a" => "&#352;",
				"\x8b" => "&#8249;",
				"\x8c" => "&#338;",
			
				// these are Microsoft Non-standard Approch to curl quotes (0x93..0x94, 0x91..0x92) and curl apostrophe usually appear in a browser as ? marks
				"\x91" => "&#8216;", // left curl single quote
				"\x92" => "&#8217;", // right curl single quote
				"\x93" => "&#8220;", // left curl double quote dec 147
				"\x94" => "&#8221;", // right curl double quote dec 148
				
				
				"\x95" => "&#8226;",
				"\x96" => "&#8211;",
				"\x97" => "&#8212;",
				"\x98" => "&#732;",
				"\x99" => "&#8482;",
				"\x9a" => "&#353;",
				"\x9b" => "&#8250;",
				"\x9c" => "&#339;",
				
				"\x9f" => "&#376;",
				
				"\xa0" => "&#173;",
				"\xad" => "&#160;",
				
			);	
	}
	
	/**
	 * http://www.dwheeler.com/essays/quotes-in-html.html
	 * always use decimal numeric character references for curling single and double quote characters
	 *&#8220; and &#8221; - and for left and right single quotation marks (and apostrophes), use &#8216; and &#8217; - and you’ll be glad you did. This approach complies with all international standards, and works essentially everywhere.
	 *
	 *
	 *
	 */
	 
	 /** /remark
	  * This function sets the PHP default entity table so
	  * that it translates character references to
	  * HTML named character entities. For reasons outlined
	  * by David A. Wheeler and others, non-ACII characters
	  * should always (at least during the transition to
	  * UTF8/Unicode) be translated to Unicdoe decimal numeric
	  * character references. Using numeric character references
	  * complies with all international standards, works everywhere
	  * and works well when combining texts from different
	  * character encodings, as outlined in his essay.
	  * http://www.dwheeler.com/essays/quotes-in-html.html
	  * The bottom line is that you should not use the
	  * xlate_ent translation mode of this package unless
	  * you know that your target is HTML and never want
	  * to use it anywhere else. If your desire is future proof
	  * text that can be combined freely and used and understood
	  * everywhere, use the xlate_ref mode.
	  */
	 
	 
	function set_default_ents()
	{
	
	$this->xlate_ent = array
		(
			"\x82" => "&sbquo;",
			"\x83" => "&fnof;",
			"\x84" => "&bdquo;",
			"\x85" => "&hellip;",
			"\x86" => "&dagger;",
			"\x87" => "&Dagger;",
			"\x88" => "&circ;",
			"\x89" => "&permil;",
			"\x8a" => "&Scaron;",
			"\x8b" => "&lsaquo;",
			"\x8c" => "&OElig;",
		
			"\x91" => "&lsquo;",
			"\x92" => "&rsquo;",
			"\x93" => "&ldquo;",
			"\x94" => "&rdquo;",
			"\x95" => "&bull;",
			"\x96" => "&ndash;",
			"\x97" => "&mdash;",
			"\x98" => "&tilde;",
			"\x99" => "&trade;",
			"\x9a" => "&scaron;",
			"\x9b" => "&rsaquo;",
			"\x9c" => "&oelig;",
			
			"\x9f" => "&Yuml;",
			
			"\xa0" => "&shy;",
			"\xad" => "&nbsp;",
			
		);	
	}

}
