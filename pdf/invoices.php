<?php
define('FPDF_FONTPATH','font/');

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('lang', 'lang.php'));

$feUserObj = tslib_eidtools::initFeUser(); // Initialize FE user object    
tslib_eidtools::connectDB(); //Connect to database

$lang = t3lib_div::_GP('L');
// languages. need configuration if changed lang of site
if ($lang == '1') $lang = 'en';
if ($lang == '' || $lang == '0') $lang = 'de';
$LOCAL_LANG=t3lib_div::readLLfile('EXT:seminars/pi1/locallang.xml',$lang);

// SEMINAR INFO
$s_id = t3lib_div::_GP('seminar');
if (!$s_id)
	$s_id = t3lib_div::_GP('eventUid');	
$user_id = t3lib_div::_GP('userUid');
$GLOBALS['TYPO3_DB']->debugOutput = true;
$seminar = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
	'*', 
	'tx_seminars_seminars', 
	"uid=$s_id and hidden=0 and deleted=0"
); /**/


// font sizes 
$fs_big = 24;
$fs_common2 = 16;
$fs_common = 14;
$fs_small = 6;

require('fpdf.php');
//require('textbox.php');
class MY_PDF extends FPDF
//class MY_PDF extends PDF_TextBox
{
	function Header()
		{ global $LOCAL_LANG, $seminar, $fs_big, $fs_common, $fs_small, $s_id, $lang;
			
		displayText($this, "Bregenzer Salon
Anton Schneider Strasse 11
A6900 Bregenz

Tel: +43 (0) 699 1928 06 65
Email: info@bregenzersalon.eu
Website:  www.bregenzersalon.eu
", $fs_common, '', 2);	
//		$image1 = "fileadmin/salonhosting/Resources/Public/Images/room.jpg";
		$image1 = "fileadmin/salonhosting/Resources/Public/Images/salonhosting.png";
//		$this->Image($image1,150,10,50);
		$this->Image($image1,150,10,30);
		
		// title
		//displayText($this, $seminar['title'], $fs_big, 'B', 2);
		// subtitle
		//displayText($this, $seminar['subtitle'], $fs_common, '', 1);
		// description
//		displayText($this, $seminar['description'], $fs_common, '', 2);
		// accreditation number
//		displayText($this, $LOCAL_LANG[$lang]['label_accreditation_number'][0]['source'].": ", $fs_common, 'B', 0);
//		displayText($this, $seminar['accreditation_number'], $fs_common, '', 1);
		// date
		//displayText($this, $LOCAL_LANG[$lang]['label_date'][0]['source'].": ", $fs_common, 'B', 0);
		//displayText($this, date('d.m.Y',$seminar['begin_date']).' - ', $fs_common, '', 0);
		//displayText($this, date('d.m.Y',$seminar['end_date']), $fs_common, '', 1);
		// time
		//displayText($this, $LOCAL_LANG[$lang]['label_time'][0]['source'].": ", $fs_common, 'B', 0);
		//displayText($this, date('H:i',$seminar['begin_date']), $fs_common, '', 1);
		// number
//		displayText($this, $LOCAL_LANG[$lang]['label_uid'][0]['source'].": ", $fs_common, 'B', 0);
//		displayText($this, $s_id, $fs_common, '', 1);
		// Place
/*		displayText($this, $LOCAL_LANG[$lang]['label_place'][0]['source'].": ", $fs_common, 'B', 1);
		$places = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_seminars_sites.*',
			'tx_seminars_seminars',
            'tx_seminars_seminars_place_mm',
            'tx_seminars_sites',
            'AND tx_seminars_seminars_place_mm.uid_local = '.$s_id,
            '',
            '',
            ''
        );  
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($places)) {
			displayText($this, '   '.$row['title'], $fs_common, 'B', 1);
			displayText($this, '   '.$row['directions'], $fs_common, '', 1);
		}; /**/
		// room
//		displayText($this, $LOCAL_LANG[$lang]['label_room'][0]['source'].": ", $fs_common, 'B', 0);
//		displayText($this, $seminar['room'], $fs_common, '', 1);
		// regular price
		//displayText($this, $LOCAL_LANG[$lang]['label_price_regular'][0]['source'].": ", $fs_common, 'B', 0);
		//displayText($this, get_price($seminar['price_regular']), $fs_common, '', 1);
		// Organizers
		//displayText($this, $LOCAL_LANG[$lang]['label_organizers'][0]['source'].": ", $fs_common, 'B', 1);
		/* $organizers = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_seminars_organizers.*',
            'tx_seminars_seminars',
            'tx_seminars_seminars_organizers_mm',
            'tx_seminars_organizers',
            'AND tx_seminars_seminars_organizers_mm.uid_local = '.$s_id,
            '',
            '',
            ''
        );  
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($organizers)) {
			displayText($this, '   '.$row['title'], $fs_common, 'B', 3);
		}; /**/
		}
		
	function Footer()
		{ global $LOCAL_LANG, $seminar, $fs_big, $fs_common, $fs_small, $s_id, $lang;
		$this->SetY(-35);
		$this->Cell(110,2,' '.$participant['uid'],'B',1,'C');	
		displayText($this, "Bregenzer Salon, Peter Brattinga, Anton Schneider Straße 11, 6900 Bregenz, 
UID  ATU 67005616
Konto Raffeisenbank Bregenz - IBAN: AT453700000103803558 BIC: RVVGAT2B
", $fs_small, '', 1, 1);	
		}		
};
$pdf = new MY_PDF();


// PERSON INFO
if ($user_id > 0) // if need invoice for one user
	$participants = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
		'*', 
		'tx_seminars_attendances', 
		"seminar=$s_id and uid=$user_id and hidden=0 and deleted=0"
	); 
else   // for all user by seminar
	$participants = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
		'*', 
		'tx_seminars_attendances', 
		"seminar=$s_id and hidden=0 and deleted=0"
	); /**/

foreach ($participants as $i=>$participant) 
{
	$pdf->AddPage();
	//displayText($pdf, "Invoice for: ", $fs_common2, 'BU', 2);	
	$user = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
		'*', 
		'fe_users', 
		"uid=".$participant['user']." and disable=0"
	);
	
	//displayText($pdf, $user['name'], $fs_common, '', 0);
	$pdf->Cell(110,$fs_small,(string)utf8_decode($user['name']),0,0);
	// Rechnungsnummer 
	$r_n = $participant['kids'];
	$pdf->Cell(77,$fs_small,'Rechnungsnummer: '.$r_n,0,1,'R');	
	
	if(!empty($user['company'])){
		displayText($pdf, addblank($user['company']).addlabel($user['fax'],"UID: "), $fs_common, '', 1);
	}
	
	$pdf->Cell(110,$fs_small,(string)utf8_decode($user['address']),0,0);
	$auf_n = $participant['uid'];
	$pdf->Cell(77,$fs_small,'Auftragsnummer: '.$auf_n,0,1,'R');
//displayText($pdf, $user['address'], $fs_common, '', 0);
	// auftragsnummer
	//$pdf->Cell(70);
	
	//$pdf->Cell(0,$fs_small,'Auftragsnummer: '.$auf_n,0,1,'C');

displayText($pdf, addblank($user['zip']).$user['city'], $fs_common, '', 1);
if(!empty($user['county'])){
	displayText($pdf, $user['country'], $fs_common, '', 1);
}else if(!empty($user['static_info_country'])){
	
	displayText($pdf, get_countryname('cn_iso_3',$user['static_info_country']), $fs_common, '', 1);//cn_official_name_local
}
		
	displayText($pdf, ' ',$fs_common, '', 1);
	displayText($pdf, ' ',$fs_common, '', 1);
//	displayText($pdf, $LOCAL_LANG[$lang]['label_date'][0]['source'].": ", $fs_common, 'B', 0);
	displayText($pdf, 'Datum: '.date('d.m.Y'), $fs_common, '', 1);
	
	//$seminar['title'];
	$invoice_text = $seminar['title'];
	//$pdf->Cell(0,$fs_small,$invoice_text,1,1,'C');	

	//$pdf->drawTextBox($invoice_text, 170, 100, 'L', 'T', 1);
	//$pdf->Cell(150, 100, $invoice_text, 1, 0, 'T');
	//$pdf->SetY($pdf->GetY() - $fs_small);
	//$pdf->SetX(-35);
	//$pdf->Cell(35,5,'Price',1,0,'T');

	$pdf->Cell(190, 100, ' ',1,0,'L');	
	//$pdf->SetY($pdf->GetY() - 100);
	
	// Prices
	$VAT = 20; // %
	displayText($pdf, $seminar['title'], $fs_common, '', 0);
	$pdf->SetX(-35);
	$price = $participant['total_price'];
	$without_vat = $price / ((100 + $VAT)/100);
	displayText($pdf, get_price($without_vat), $fs_common, '', 1);
	//$pdf->Cell(0,$fs_common, $without_vat,0,1,'L');	
	
	
	displayText($pdf, 'Mwst.', $fs_common, '', 0);
	$pdf->SetX(-35);
	if ($participant['including_tax']!=1){
		$vat_price = $price - $without_vat;
	}else{
		$vat_price=0;
	}
	displayText($pdf, get_price($vat_price), $fs_common, '', 1);

	
	//$pdf->Cell(0,$fs_common,$vat_price,0,1,'L');		
	displayText($pdf, '  ', $fs_common, '', 1);
	displayText($pdf, '  ', $fs_common, '', 1);
	displayText($pdf, 'Gesamt Betrag', $fs_common, '', 0);
	$pdf->SetX(-35);
	if ($participant['including_tax']!=1){
		displayText($pdf, get_price($price), $fs_common, '', 1);
	}else{
		displayText($pdf, get_price($without_vat), $fs_common, '', 1);
	}
	//$pdf->Cell(0,$fs_common,get_price($price),0,1,'L');	

	displayText($pdf, '  ', $fs_common, '', 1);
	displayText($pdf, '  ', $fs_common, '', 1);	
	if($participant['method_of_payment']==1){
		displayText($pdf, '
Der Betrag wurde bar bezahlt. Wir danken für die Teilnahme!
', 	$fs_common, '', 1);
	}else{
		displayText($pdf, '
Ich bitte um Überweisung des Rechnungsbetrages innerhalb von  10 Tagen auf mein Konto: Raffeisenbank Bregenz - IBAN: AT453700000103803558 BIC: RVVGAT2B

Rechnungen sind inklusive Mwst. und ohne jeden Abzug und spesenfrei zahlbar.
', 	$fs_common, '', 1);
}
	
	// name
	//displayText($pdf, "Name: ", $fs_common, 'B', 0);
	//displayText($pdf, $user['name'], $fs_common, '', 1);
	// address
	//displayText($pdf, "Address: ", $fs_common, 'B', 0);
	//displayText($pdf, $user['address'], $fs_common, '', 1);
	// City
	//displayText($pdf, "City: ", $fs_common, 'B', 0);
	//displayText($pdf, $user['city'], $fs_common, '', 1);
	// Country
	//displayText($pdf, "Country: ", $fs_common, 'B', 0);
	$country = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
		'*', 
		'static_countries', 
		"cn_iso_3='".$user['static_info_country']."'"
	);
	//displayText($pdf, $country['cn_short_en'], $fs_common, '', 1);
	// Postal code
	//displayText($pdf, "Postal code: ", $fs_common, 'B', 0);
	//displayText($pdf, $user['zip'], $fs_common, '', 1);
	// email
	//displayText($pdf, "Email: ", $fs_common, 'B', 0);
	//displayText($pdf, $user['email'], $fs_common, '', 1);
	// Phone
	//displayText($pdf, "Telephone: ", $fs_common, 'B', 0);
	//displayText($pdf, $user['telephone'], $fs_common, '', 2);
	// Price
	//displayText($pdf, $LOCAL_LANG[$lang]['label_price'][0]['source'].": ", $fs_common, 'B', 0);
	//displayText($pdf, $participant['price'], $fs_common, '', 1);
	
};

$pdf->Output();/**/

function get_price($price) 
{
	$currency = "\xE2\x82\xAc"; // EUR
	$pr = $currency.' '.money_format("%.2n", $price);
	return $pr;
}
function get_countryname($field,$val){
	
	if($country = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('cn_short_local', 	'static_countries', $field.'="'.$val.'"')){
		return $country['cn_short_local'];
	}else return "";
	
}
function addblank($wert){
	if (!empty($wert)) $wert=$wert." ";
	return $wert;
}
function addlabel($wert,$str){
	if (!empty($wert)) $wert=$str.$wert;
	return $wert;
}
function displayText (&$pdf, $text, $fontsize=14, $fontstyle='', $ln=0) {
	$pdf->SetFont('Arial', $fontstyle, $fontsize);	
	//$pdf->write(5, $text);
	$pdf->write(6, iconv("UTF-8", "Windows-1252", $text));
	
	if ($ln>0)
		for ($i = 1; $i<=$ln; $i++)
			$pdf->Ln();
}
?>