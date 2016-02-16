<?php
define('FPDF_FONTPATH','font/');

/*++++++++++++++++++++++++++++++++++++++++++*/
/*Invoices for room reservation*/


// get typoscript configuration
function loadTS($pageUid = 0) {
    $sysPageObj = t3lib_div::makeInstance('t3lib_pageSelect');
 
    $rootLine = $sysPageObj->getRootLine($pageUid);
 
    $typoscriptParser = t3lib_div::makeInstance('t3lib_tsparser_ext');
    $typoscriptParser->tt_track = 0;
    $typoscriptParser->init();
    $typoscriptParser->runThroughTemplates($rootLine);
    $typoscriptParser->generateConfig();
 
    return $typoscriptParser->setup;
};
$ts_setup = loadTS(17); // 17 - id of page

require_once(PATH_tslib.'class.tslib_pibase.php');
require_once(t3lib_extMgm::extPath('lang', 'lang.php'));

$feUserObj = tslib_eidtools::initFeUser(); // Initialize FE user object    
tslib_eidtools::connectDB(); //Connect to database

$lang = t3lib_div::_GET('L');
// languages. need configuration if changed lang of site.. now does not work.
if ($lang == '1') $lang = 'en';
if ($lang == '' || $lang == '0') $lang = 'de';
$LOCAL_LANG=t3lib_div::readLLfile('EXT:seminars/pi1/locallang.xml',$lang);

$s_id = t3lib_div::_GP('eventUid');


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

require( 'fpdf.php' );

class MY_PDF extends FPDF
{
	function Footer()
		{ global $LOCAL_LANG, $seminar, $fs_big, $fs_common, $fs_small, $s_id, $lang;
		$this->SetY(-35);
		$this->Cell(110,2,' '.$participant['uid'],'B',1,'C');	
		displayText($this, "Bregenzer Salon – Mag. Ursula Hillbrand - 6900 Bregenz
UID ATU 68498645 Konto Raiffeisenbank Bregenz IBAN AT45 3700 0000 0380 3558 BIC RVVGAT2B
", $fs_small, '', 1, 1);	
		}		
};

$pdf = new MY_PDF();


$pdf->AddPage();

		displayText($pdf, "Bregenzer Salon
Anton Schneider Strasse 11
A-6900 Bregenz

Tel: +43 (0) 676 373 87 17
Email: info@bregenzersalon.eu
Website:  www.bregenzersalon.eu
", $fs_common, '', 2);	
//		$image1 = "fileadmin/salonhosting/Resources/Public/Images/room.jpg";
		$image1 = "fileadmin/salonhosting/Resources/Public/Images/salonhosting.png";
//		$pdf->Image($image1,150,10,50);
		$pdf->Image($image1,160,10,30);


$seminar = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
	'*', 
	'tx_seminars_seminars', 
	"uid=$s_id and deleted=0"
); /**/

	$owner = $seminar['owner_feuser'];

	if (!$owner) $owner = $seminar['cruser_id'];
	$user = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
		'*', 
		'fe_users', 
		"uid=".$owner." and disable=0"
	);
	// name
	//displayText($pdf, $user['name'], $fs_common, '', 0);
	$pdf->Cell(110,$fs_small,(string)utf8_decode($user['name']),0,0);
	//$pdf->Cell(70);
	// rechnungsnummer
	
	$rn = $seminar['accreditation_number'];
	//$pdf->Cell(0,$fs_small,'Rechnungsnummer '.$rn,0,1,'C');
	$pdf->Cell(77,$fs_small,'Rechnungsnummer: '.$rn,0,1,'R');	
//	$pdf->Cell(70);
	// auftragsnummer
//	$auf_n = $s_id;
//	$pdf->Cell(0,$fs_small,'Auftragsnummer '.$auf_n,0,1,'C');
	
	//displayText($pdf, $user['address'], $fs_common, '', 0);
	$pdf->Cell(110,$fs_small,(string)utf8_decode($user['address']),0,0);
	//$pdf->Cell(70);
	//$pdf->Cell(0,$fs_small, date('d/m/Y'),0,1,'C');
	$rn = $s_id;
	$pdf->Cell(77,$fs_small,'Auftragsnummer: '.$rn,0,1,'R');	
	
	//displayText($pdf, $user['zip'].' '.$user['city'], $fs_common, '', 1);
	$pdf->Cell(110,$fs_small,(string)utf8_decode($user['zip'].' '.$user['city']),0,0);
	$pdf->Cell(77,$fs_small,date('d.m.Y'),0,1,'R');
	
	displayText($pdf, '  ', $fs_common, '', 1);
	displayText($pdf, '  ', $fs_common, '', 1);		
	
/*	// Country
	displayText($pdf, "Country: ", $fs_common, 'B', 0);
	$country = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
		'*', 
		'static_countries', 
		"cn_iso_3='".$user['static_info_country']."'"
	);
	displayText($pdf, $country['cn_short_en'], $fs_common, '', 1); /**/
/*	// email
	displayText($pdf, "Email: ", $fs_common, 'B', 0);
	displayText($pdf, $user['email'], $fs_common, '', 1);
	// Phone
	displayText($pdf, "Telephone: ", $fs_common, 'B', 0);
	displayText($pdf, $user['telephone'], $fs_common, '', 2); /**/
	//$count_days = ceil(($seminar['end_date']-$seminar['begin_date'])/86400)+1;
	$datetime1 = new DateTime(date("Y-m-d", $seminar['begin_date']));
	$datetime2 = new DateTime(date("Y-m-d", $seminar['end_date']));
	$interval = $datetime1->diff($datetime2); 
	$count_days = $interval->format('%d');
	$count_days = $count_days + 1;
	if ($count_days<=0) 
		$count_days = 1;	

	// Price for room	
	$price_room = $seminar['tx_gtnseminarsext_price_for_room'];
	if ($seminar['room_price_type'])
		$price_room += $ts_setup['plugin.']['tx_seminars_pi1.']['priceroom.'][$seminar['room_price_type'].'.']['price'];
	if ($seminar['room2_price_type'])
		$price_room += $ts_setup['plugin.']['tx_seminars_pi1.']['priceroom2.'][$seminar['room2_price_type'].'.']['price'];
	if ($seminar['kitchen_price_type'])
		$price_room += $ts_setup['plugin.']['tx_seminars_pi1.']['pricekitchen.'][$seminar['kitchen_price_type'].'.']['price'];
		
	$price_room	*= $count_days;		

	//food
	$foods = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_seminars_foods.*, tx_seminars_seminars_foods_mm.*',
			'tx_seminars_seminars',
            'tx_seminars_seminars_foods_mm',
            'tx_seminars_foods',
            'AND tx_seminars_seminars_foods_mm.uid_local = '.$s_id,
            '',
            '',
            ''
        );  
	$food_price = 0;
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($foods)) {
			$food_price += $row['tx_gtnseminarsext_price']*$row['food_amount'];
		};
	$food_price *= $count_days;
		
	//accessories
	$accessories = $GLOBALS['TYPO3_DB']->exec_SELECT_mm_query(
			'tx_seminars_accessories.*',
			'tx_seminars_seminars',
            'tx_seminars_seminars_accessories_mm',
            'tx_seminars_accessories',
            'AND tx_seminars_seminars_accessories_mm.uid_local = '.$s_id,
            '',
            '',
            ''
        );  
	$accessories_price = 0;
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($accessories)) {
			$accessories_price += $row['tx_gtnseminarsext_price'];
		};		
	$accessories_price *= $count_days;
		
	//participants
	$participants = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
		'count(*) as c_p', 
		'tx_seminars_attendances', 
		"seminar=$s_id and hidden=0 and deleted=0",
		"seminar"
	); /**/	
	$amount_pa = $participants['c_p'];
	if (!$amount_pa) $amount_pa = 0;
	// total food price
	//$total_food_price = $food_price*$amount_pa;
	$total_food_price = $food_price;
	
	$pdf->Cell(190, 100, ' ',1,0,'L');	
	
	// Prices
	$VAT = 20; // %
	
	displayText($pdf, 'Raummiete Salon', $fs_common, '', 1);
	//displayText($pdf, $seminar['title'], $fs_common, '', 0);
	$pdf->Cell(110,$fs_small,''.(string)utf8_decode($seminar['title']),0,0);
	
	//$pdf->SetX(-35);
	$price = $price_room;
	$without_vat = $price / ((100 + $VAT)/100);
	//displayText($pdf, get_price($without_vat), $fs_common, '', 1);
	$pdf->Cell(77,$fs_small,(string)(get_price($without_vat)),0,1,'R');
	//$pdf->Cell(0,$fs_common, $without_vat,0,1,'L');	
	// accessories
	//displayText($pdf, 'Zubehör ', $fs_common, '', 0);
	$pdf->Cell(110,$fs_small,''.(string)utf8_decode(Zubehör),0,0);
	//$pdf->SetX(-35);
	$without_vat_accessories = $accessories_price / ((100 + $VAT) / 100);
	//displayText($pdf, get_price($without_vat_accessories), $fs_common, '', 1);	
	$pdf->Cell(77,$fs_small,(string)(get_price($without_vat_accessories)),0,1,'R');
	// foods
	//displayText($pdf, 'Verpflegung ', $fs_common, '', 0);
	$pdf->Cell(110,$fs_small,''.(string)utf8_decode(Verpflegung),0,0);
	
	//$pdf->SetX(-35);
	$without_vat_food = $food_price / ((100 + $VAT) / 100);
	//displayText($pdf, get_price($without_vat_food), $fs_common, '', 1);	
  $pdf->Cell(77,$fs_small,(string)(get_price($without_vat_food)),0,1,'R');
	displayText($pdf, '  ', $fs_common, '', 1);
	displayText($pdf, '  ', $fs_common, '', 1);		
	
	//displayText($pdf, 'Mwst.', $fs_common, '', 0);
	$pdf->Cell(110,$fs_small,''.(string)utf8_decode('Mwst.'),0,0);
	//$pdf->SetX(-35);
	$vat_price = $price + $food_price + $accessories_price - $without_vat - $without_vat_food - $without_vat_accessories;
	//displayText($pdf, get_price($vat_price), $fs_common, '', 1);
	$pdf->Cell(77,$fs_small,(string)(get_price($vat_price)),0,1,'R');
	//$pdf->Cell(0,$fs_common,$vat_price,0,1,'L');		
	

	//displayText($pdf, 'Gesamt zu bezahlen Betrag', $fs_common, '', 0);
	 $pdf->SetFont('', 'B');
	$pdf->Cell(110,$fs_small,''.(string)utf8_decode('Gesamt zu bezahlen Betrag'),0,0);
	//$pdf->SetX(-35);
	$price_all = $price + $food_price + $accessories_price;
	//displayText($pdf, get_price($price_all), $fs_common, '', 1);
	$pdf->Cell(77,$fs_small,(string)(get_price($price_all)),0,1,'R');
	//$pdf->Cell(0,$fs_common,get_price($price),0,1,'L');	

	displayText($pdf, '  ', $fs_common, '', 1);
	displayText($pdf, '  ', $fs_common, '', 1);	
	displayText($pdf, '
Ich bitte um Überweisung des Rechnungsbetrages innerhalb von 10 Tagen auf mein Konto Raiffeisenbank Bregenz - 
IBAN AT45 3700 0000 0380 3558 
BIC RVVGAT2B		
', 	$fs_common, '', 1);	
	

$pdf->Output();/**/

function get_price($price) 
{
	$currency = chr(128); // EUR
	$pr = $currency.' '.money_format("%.2n", $price);
	return $pr;
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