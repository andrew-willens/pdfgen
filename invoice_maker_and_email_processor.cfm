<?php 

/**
 * Code not currently in use. Left as an example of the procedural code that
 * Invoice, InvoiceEmail and InvoicePDF classes replaced.
 *
 * @author (of note) AndrewWillens
 */

// import standard imn tools
include_once('all_includes.inc');

// import local application tools
include_once("_Classes/InvoiceMaker.php");
include_once("_Classes/RegistrationInformation.php");

// import vendor pdf-making tools
require_once('fpdf.php');

// connect to imn database
connect_thea();


// if not noted out...
// import accounting tools
// include_once("../accounting.inc");
//
// import additional standard imn tools
// include_once("added_func.inc");
//
//if ($_REQUEST['record_id'] == ""){
//    $record_id="110325EUWBYUJQK7HR";
//}


// make registration object/get reg data=======================================
if(!empty($_REQUEST['remake_invoice_date']))
{
    $inv_date = "$_REQUEST[remake_invoice_date]";
} else {
    $inv_date="";
}

$record_id=$_REQUEST['record_id'];
$print_confirm="no";
$cb_user_name=$_REQUEST['cb_user_name'];
$sponsor_only="";
$remake_invoice_date=$inv_date;

$registration_object = Registration::getRegistrationByRecordID($_REQUEST['record_id']);
$today = date('m/d/Y', strtotime($registration_object->process_date));

$add_it  = "";
$add_it2 = "";
$add_it3 = "";

$ri = new RegistrationInformation($sponsor_only, $record_id, $remake_invoice_date);
//$today = $ri->InvoiceDate;

list($regdescription, $fee) = explode("|", $ri->regtype);
$fee_num=$fee;
$fee=number_format($fee,2);
// end get reg data============================================================


// make invoice ===============================================================
$im = new InvoiceMaker($ri->personid,$ri->sys_code, $regdescription); 

#echo"$fee_num";

###################################################################################
//$clientName     = $im->clientName;
//$conferenceName   = $im->invoiceDescription;
//$conferenceOwner  = $im->eventEventOwner;

$clientName     = utf8_decode(iconv('utf-8', 'cp1252', $im->clientName));
$conferenceName   = utf8_decode(iconv('utf-8', 'cp1252', $im->invoiceDescription));
$conferenceOwner  = utf8_decode(iconv('utf-8', 'cp1252', $im->eventEventOwner)); 

$paid_before_refund     = $im->find_acc_transaction_paid_amount2($ri->record_id,$fee);
$refund_back        = $im->refund_value($record_id,$paid_before_refund);
$tax_amount             = $ri->clientTaxAmount;
if($tax_amount > 0){$headerVatText="VAT TAX";}else{$headerVatText="TAX";}
$checkFooterNote        = $im->checkFooterNote;
$creditCardFooterNote   = $im->creditCardFooterNote;
$wireTransferFooterNote = $im->wireTransferFooterNote;
$eventVatTaxId          = $im->eventVatTaxId;



$after_refund = $paid_before_refund - $refund_back;

$paid_it    = $after_refund;
 
$fee_and_tax            =  $fee_num + $tax_amount; 

$balance_it       = $im->find_acc_transaction_balance_amount2($ri->record_id,$fee_and_tax,$refund_back);    

#echo"PAID:$paid_before_refund ,REFUND:$refund_back , NUMREFUND:$paid_it , $balance_it ";

#exit;
####################################################################################
        

         
#echo "[$event_owner]";

####################################################################################

        
        


// make invoice ===============================================================
#require_once('fpdi.php');

$pdf =& new FPDF();

//++$pagecount = $pdf->setSourceFile('_Assets/pdf_templates/INVOICE_TEMP.pdf');
//++$tplidx = $pdf->importPage(1, '/BleedBox');
$pdf->addPage();
$pdf->Rect('8', '13', '193.6', '272');
//$pdf->Image("_Assets/images/$im->logoFile",75,15,60,40);
    

if($im->logoFile === "sfig_imn.jpg")
{
    $pdf->Image("_Assets/images/$im->logoFile",75,15,50,30);
}
else
{
    $pdf->Image("_Assets/images/$im->logoFile",75,15,60,40);
}

//###
//Fields Name position
$Y_Fields_Name_position = 130;
//Table position, under Fields Name
$Y_Table_Position = 26;

//First create each Field Nam
#//Gray color filling each Field Name box
$pdf->SetFillColor(255,255,255);


$pdf->SetFont('Arial','','9');
$pdf->SetX(30.5);
$pdf->SetY(48);
$pdf->MultiCell(185.1,5,"$im->coferenceOwnerAddress", 0,'C',1);
//MultiCell(float w, float h, string txt [, mixed border [, string align [, boolean fill]]]) 
$pdf->Ln();



//ClientMailing Address
$pdf->SetFont('Arial','','9');
$pdf->SetX(30.5);
$pdf->SetY(68);
$pdf->MultiCell(95,3,iconv('utf-8', 'cp1252', $im->clientInvoiceAddress), 0,'L',1);
//MultiCell(float w, float h, string txt [, mixed border [, string align [, boolean fill]]]) 
$pdf->Ln();



//print "Date"
$pdf->SetFont('Arial','','9');
$pdf->SetY(68);
$pdf->SetX(135);
$pdf->Cell(30,8,'DATE',1,0,'C',1);
$pdf->Ln();

//determine if it's Invoice or Receipt
$doc_type = $balance_it > "0" ? "INVOICE" : "RECEIPT";
$pdf->SetFont('Arial','','9');
$pdf->SetY(68);
$pdf->SetX(165);
$pdf->Cell(28,8,$doc_type,1,0,'C',1);
$pdf->Ln();

//DATE for invoice/receipt
$pdf->SetFont('Arial','','9');
$pdf->SetY(76);
$pdf->SetX(135);
$pdf->Cell(30,8,"$today",1,0,'C',1);
$pdf->Ln();

//Invoice/Receipt NUMBER
$pdf->SetFont('Arial','','9');
$pdf->SetY(76);
$pdf->SetX(165);
$pdf->Cell(28,8,"$ri->invoiceID",1,0,'C',1);
$pdf->Ln();

//VAT Tax ID staticly provided in InvoiceMaker.php scrip.
if ($eventVatTaxId != "" && $ri->sys_code !="20110614_SF_0024" && $ri->keycode != "SPONSOR"){
$pdf->SetFont('Arial','','9');
$pdf->SetY(84);
$pdf->SetX(134.9);
$pdf->Cell(58.2,8,"$eventVatTaxId",1,0,'C',1);
$pdf->Ln();
}




//barCodeMaker($xpos, $ypos, $code, $basewidth=1, $height=10)
$pdf->barCodeMaker(172,93,$ri->invoiceID, "1", "5");
$pdf->Ln();




//Paper Fold position
$pdf->SetFont('Arial','','9');
$pdf->SetX(0);
$pdf->SetY(95);
$pdf->MultiCell(95,4,"\n\n\n-\n\n\n", 0,'L',1);
//MultiCell(float w, float h, string txt [, mixed border [, string align [, boolean fill]]]) 
$pdf->Ln();



//CONFERENCE CODE
$pdf->SetFont('Arial','B','8');
$pdf->SetY(125);
$pdf->SetX(15.5);
$pdf->Cell(20.6,5,'CODE',1,0,'C',1);
$pdf->SetFont('Arial','','9');
$pdf->SetY($Y_Fields_Name_position);
$pdf->SetX(15.5);
$pdf->MultiCell(20.6,5.7,"$ri->code\n\n\n\n",1,'C',1);


//DESCRIPTION
$pdf->SetFont('Arial','B','8');
$pdf->SetY(125);
$pdf->SetX(36);
$pdf->Cell(109,5,'DESCRIPTION',1,0,'C',1);
$pdf->SetFont('Arial','','9');
$pdf->SetY($Y_Fields_Name_position);
$pdf->SetX(36);
$pdf->MultiCell(109,5.7,"$conferenceName\n\n", 1,'L',1);

//TAX/VAT TAX
$pdf->SetFont('Arial','B','8');
$pdf->SetY(125);
$pdf->SetX(145);
$pdf->Cell(20,5,"$headerVatText",1,0,'C',1);
$pdf->SetFont('Arial','','9');
$pdf->SetY($Y_Fields_Name_position);
$pdf->SetX(145);
#$pdf->Cell(29.7,6,"$tax_amount\n\n\n\n",1,0,'C',1);
$pdf->MultiCell(20,5.7,number_format($tax_amount, 2)."\n\n\n\n",1,'C',1);


//Registration Total
$pdf->SetFont('Arial','B','8');
$pdf->SetY(125);
$pdf->SetX(164.8);
$pdf->Cell(28,5,'AMOUNT',1,0,'C',1);

$pdf->SetFont('Arial','','9');
$pdf->SetY($Y_Fields_Name_position);
$pdf->SetX(164.8);
#$pdf->Cell(28,6,"$fee_num\n\n\n\n\n",1,0,'R',1);
$pdf->MultiCell(28,5.7,number_format($fee_num, 2)."\n\n\n\n", 1,'R',1);
$pdf->Ln();


if ($ri->clientSponsorPasses != ""){
 //CONFERENCE CODE
$pdf->SetFont('Arial','B','8');
$pdf->SetY(158.9);
$pdf->SetX(15.5);
$pdf->Cell(50.6,5,'SPONSOR ASSIGNED PASSES:',1,0,'C',1);
$pdf->SetFont('Arial','','9');
$pdf->SetY(163.9);
$pdf->SetX(15.5);
$pdf->MultiCell(50.6,5.7,"$ri->clientSponsorPasses",1,'C',1);     
}





//Invoice Total Box
$pdf->SetFont('Arial','','9');
$pdf->SetY(153.4);
$pdf->SetX(125);
$pdf->Cell(40,6,'INVOICE TOTAL ($USD)',0,0,'R',1);
$pdf->Ln();
$pdf->SetFont('Arial','','9');
$pdf->SetY(152.9);
$pdf->SetX(164.8);
$pdf->Cell(28,6,number_format($fee_and_tax, 2),1,0,'R',1);
$pdf->Ln();


//Total PAID Box
$pdf->SetFont('Arial','','9');
$pdf->SetY(159.4);
$pdf->SetX(125);
$pdf->Cell(40,6,'TOTAL PAID ($USD)',0,0,'R',1);
$pdf->Ln();
$pdf->SetFont('Arial','','9');
$pdf->SetY(158.9);
$pdf->SetX(164.8);
$pdf->Cell(28,6,number_format($paid_it, 2),1,0,'R',1);
$pdf->Ln();


//BALANCE DUE Box
$pdf->SetFont('Arial','','9');
$pdf->SetY(165.4);
$pdf->SetX(125);
$pdf->Cell(40,6,'BALANCE DUE ($USD)',0,0,'R',1);
$pdf->SetFont('Arial','','9');
$pdf->SetY(164.9);
$pdf->SetX(164.8);
$pdf->Cell(28,6,number_format($balance_it, 2),1,0,'R',1);
$pdf->Ln();




////FOOTERNOTE text
$pdf->SetFont('Arial','','7.5');
$pdf->SetX(15.5);
$pdf->SetY(223);
$pdf->MultiCell(185,3.1,"$im->footerNotes", 0,'L',1);
////MultiCell(float w, float h, string txt [, mixed border [, string align [, boolean fill]]]) 
$pdf->Ln();

//***FOOTNOTE TABLES***//
//DESCRIPTION
$pdf->SetFont('Arial','','7.5');
$pdf->SetY(249.2);
$pdf->SetX(10.5);
$pdf->MultiCell(58,3,"$checkFooterNote", 0,'L',1);

$pdf->SetFont('Arial','','7.5');
$pdf->SetY(249.2);
$pdf->SetX(65);
$pdf->MultiCell(60,3,"$creditCardFooterNote", 0,'L',1);

$pdf->SetFont('Arial','','7.5');
$pdf->SetY(249.2);
$pdf->SetX(125);
$pdf->MultiCell(73,3,"$wireTransferFooterNote", 0,'L',1);

// end make invoice ===========================================================



// mail the invoice ===========================================================
//require_once("class.phpmailer.php");
$mail = new PHPMailer();
$mail->CharSet = "utf-8";
//$mail->IsSMTP();                                      // set mailer to use SMTP
//$mail->IsHTML("false");
$mail->IsHTML(true);

//$mail->Host = "ii-e2k3bh.insideii.com";  // specify main and backup server

$mail->From = $_REQUEST['from'];
$mail->FromName = $_REQUEST['from_full_name'];
$mail->AddAddress($_REQUEST['to'], "$clientName");   // to field, name is optional

if(isset ($_REQUEST['cc'])){
$mail->AddCC($_REQUEST['cc'],"");
}
if(isset ($_REQUEST['bcc'])){
$mail->AddBCC($_REQUEST['bcc'],"");    
}

$mail->AddReplyTo($_REQUEST['from'], $_REQUEST['from_full_name']);

$mail->WordWrap = 50;                                 // set word wrap to 50 characters
#$mail->IsHTML(true);                                  // set email format to HTML

$mail->Subject = $_REQUEST['subject'];
//$mail->Body    = txt2html($_REQUEST['email_body']);
//$mail->AltBody = $_REQUEST['email_body'];

$mail->Body = nl2br($_REQUEST['email_body']);

$doc = $pdf->Output('', 'S');
$mail->AddStringAttachment($doc, "$doc_type"."$ri->invoiceID.pdf", 'base64', 'application/pdf');
#$mail->AddStringAttachment($_FILES['attachment']['name'], "W9_Form.pdf", 'base64', 'application/pdf');



if(!$mail->Send())
{
   echo "Message could not be sent. <p>";
   echo "Mailer Error: " . $mail->ErrorInfo;
   exit;
}
echo "Message has been sent";

try
{
    $receiver = Person::byEmail($_REQUEST['to']);
    
    if(!is_null($receiver))
        PagehitAndEmailTracker::MarkSystemEmailReceived($receiver->personid, $ri->sys_code);
}
catch(Exception $exc)
{
    
}

// end mail the invoice =======================================================


exit();



function txt2html($txt) 
{
// Transforms txt in html

  //Kills double spaces and spaces inside tags.
  while( !( strpos($txt,'  ') === FALSE ) )
  {
          $txt = str_replace('  ',' ',$txt);
  }
  
  $txt = str_replace(' >','>',$txt);
  $txt = str_replace('< ','<',$txt);

  //Transforms accents in html entities.
  $txt = htmlentities($txt);

  //We need some HTML entities back!
  $txt = str_replace('&quot;','"',$txt);
  $txt = str_replace('&lt;','<',$txt);
  $txt = str_replace('&gt;','>',$txt);
  $txt = str_replace('&amp;','&',$txt);

  //Basic formatting
  $eol = ( strpos($txt,"\r") === FALSE ) ? "\n" : "\r\n";
  $html = '<p>'.str_replace("$eol$eol","</p><p>",$txt).'</p>';
  $html = str_replace("$eol","<br />\n",$html);
  $html = str_replace("</p>","</p>\n\n",$html);
  $html = str_replace("<p></p>","<p>&nbsp;</p>",$html);

  //Wipes <br> after block tags (for when the user includes some html in the text).
  $wipebr = Array("table","tr","td","blockquote","ul","ol","li");

  for($x = 0; $x < count($wipebr); $x++) {

    $tag = $wipebr[$x];
    $html = str_replace("<$tag><br />","<$tag>",$html);
    $html = str_replace("</$tag><br />","</$tag>",$html);

  }

  return "<font face=\"Verdana, Arial, Helvetica, sans-serif\" size=\"2\">".$html."</font>";
}