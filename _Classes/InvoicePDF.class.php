<?php
include_once('_Classes/Invoice.class.php');
include_once('fpdf.php');

/**
* InvoicePDF CLass. Takes data from Invoice class and renders it as an HTML
 * email form.
 *
 * @author AndrewWillens
*/
class InvoicePDF extends Invoice
{
    // FPDF object
    private $pdf; 
    
    // magic ===============================================================   
        function __construct($request)
        {
            // inherit constructor from parent
            parent::__construct($request);

            $this->pdf = new FPDF();
            $this->pdf->addPage();
            $this->pdf->Rect('8', '13', '193.6', '272');
            $this->layoutPDF();   
        }
    // magic ===============================================================


    // PDF methods ==========================================================  

       public function outputPDF($filename="invoice.pdf") {
            $this->pdf->Output($filename, 'I');
       }

       
       /**
        * Set coordinates and content of PDF element with FPDF.
        * 
        * @param String  $content
        * @param String  $font        Header, footer, or body; determines font
        * styling.
        * @param String  $order       Set X or Y coordinate first? 'xy' or'yx'.
        * @param Integer $x           X coordinate.
        * @param Integer $y           Y coordinate.
        * @param Integer $cell_width  self-explanatory.
        * @param Integer  $cell_height self-explanatory.
        * @param String  $alignment   Text alignment: center, left, or right.
        * @param Integer $border      [description]
        * @param boolean $cells       Use multi or single cell FPDF function;
        * multi enables multiple lines.
        * @param boolean $linebreak   Insert linebreak or no?
        */
       private function setPDFContent($content, $font, $order, $x, $y, $cell_width, $cell_height, $alignment='C', $border=0, $cells=true, $linebreak=true) {
            
            switch ($font) {
                case 'header':
                    $this->pdf->SetFont('Arial','B','8');
                    break;
                case 'footer':
                    $this->pdf->SetFont('Arial','','7.5');
                    break;
                case 'body':
                    $this->pdf->SetFont('Arial','','10');
                    break;
                default:
                    $this->pdf->SetFont('Arial','','9');
                    break;
            }

            if ( strtolower($order) === "xy" ) {
                $this->pdf->SetX($x);
                $this->pdf->SetY($y);
            } else {
                $this->pdf->SetY($y);
                $this->pdf->SetX($x);
            }
            
            if ($cells) {
                $this->pdf->MultiCell($cell_width,$cell_height,$content, $border, $alignment, 1);
            } else {
                $this->pdf->Cell($cell_width,$cell_height,$content,$border,$alignment,0,1);
            }

            if ($linebreak) 
                $this->pdf->Ln(); // linebreak           
       }

       /**
        * Layout cells and all content of PDF invoice.
        */
       private function layoutPDF() {
            $request = $this->getRequest();

            // logo at top
            list ($logoWidth, $logoHeight) = $this->getLogo() === "sfig_imn.jpg" ? array(50,30) : array(60,40);
            $this->pdf->Image("_Assets/images/{$this->getLogo()}",75,15,$logoWidth, $logoHeight);

            //set color of cells
            $this->pdf->SetFillColor(255,255,255);

            // header address (address of company that runs conference)
            $this->setPDFContent($this->getConferenceOwnerAddress(), 'body', 'xy', 30.5,48,185.1,5);

            // client address
            $this->setPDFContent($this->euroDecode($this->getClientFullAddress()), 'body', 'xy', 30.5, 68, 95, 3, 'L');
            
            // date cell header 
            $this->setPDFContent('DATE', 'header', 'yx', 135, 68, 30, 8, 'C', 1);

            
            // invoice number cell header
            // @ANTON: Agustin asked to change both to invoice
            // $doc_type = $this->getBalance() > "0" ? "INVOICE" : "RECEIPT";
            $this->setPDFContent("INVOICE #", 'header', 'yx', 165, 68, 28, 8, 'C', 1);
            
            // invoice date
            $this->setPDFContent($this->getRegProcessDate(), 'body', 'yx', 135, 76, 30, 8, 'C', 1);
            
            // invoice number
            $this->setPDFContent($this->getInvoiceID(), 'body', 'yx', 165, 76, 28, 8, 'C', 1);

            // vat tax code
            if ( $this->getExcludeTax() === false && $this->getVatTaxID() !== "" ) 
            {    
                $this->setPDFContent($this->getVatTaxID(), 'body', 'yx', 134.9, 84, 58.2, 8, 'C', 1);
            }

            // barcode
            // $this->makeBarCode(134.9,93,$this->getInvoiceID(), "1", "5");

            // paper fold
            $this->setPDFContent("\n\n\n-\n\n\n", 'body', 'xy', 0, 95, 95, 4, 'L');

            // conference code
            $this->setPDFContent('CODE', 'header', 'yx', 15.5, 125, 20.6, 5, 'C', 1, true);
            $this->setPDFContent("{$this->registration->code}\n\n\n", 'body', 'yx', 15.5, 130, 20.6, 5.7, 'C', 1, true, false);

            // conference description
            $this->setPDFContent('DESCRIPTION', 'header', 'yx', 36, 125, 109, 5, 'C', 1, true);
            $this->setPDFContent($this->getConferenceDescription(), 'body', 'yx', 36, 130, 109, 5.7, 'C', 1, true, false);

            if ($this->getExcludeTax() === false) 
            {    
                // tax
                $this->setPDFContent($this->getTaxHeader(), 'header', 'yx', 145, 125, 20, 5, 'C', 1);
                $this->setPDFContent( number_format($this->getTax('VAT'), 2)."\n\n\n", 'body', 'yx', 145, 130, 20, 5.7, 'C', 1);
            }

            // registration totals
            $this->setPDFContent('AMOUNT', 'header', 'yx', 164.8, 125, 28, 5, 'C', 1);
            $this->setPDFContent( number_format($this->getRegFee(), 2)."\n\n\n", 'body', 'yx', 164.8, 130, 28, 5.7, 'C', 1);

            // firm and client passes
            if ( $this->registration->firm_passes_designated > 0 ||
                 $this->registration->client_passes_designated > 0 ) {
                $this->setPDFContent('SPONSOR PASSES', 'body', 'yx', 15.5, 158.9, 50.6, 5, 'C', 1);
                $sponsor_passes = "Firm: {$this->registration->firm_passes_designated}\nClient: {$this->registration->client_passes_designated}";
                $this->setPDFContent($sponsor_passes, 'body', 'yx', 15.5, 163.9, 50.6, 5.7, 'C', 1);
            }
            
            // FPDF chokes on $this->getCurrencyChar() for euros and lbs sterling chars. needs to encoded as UTF8 w/inconv, which our version of php doesn't have.
            // invoice total
            $this->setPDFContent("INVOICE TOTAL ({$this->getCurrencyAbbr()})", 'body', 'yx', 125, 153.4, 40, 6, 'C');
            $this->setPDFContent( number_format($this->getTotalFee(), 2), 'body', 'yx', 164.8, 152.9, 28, 6, 'C', 1);

            if ( empty($request['currency']) || $request['currency'] !== 'original') {
                // total paid
                $this->setPDFContent("TOTAL PAID ({$this->getCurrencyAbbr()})", 'body', 'yx', 125, 159.4, 40, 6, 'R');
                $this->setPDFContent( number_format($this->getTotalPaid(), 2), 'body', 'yx', 164.8, 158.9, 28, 6, 'C', 1);

                // balance due
                $this->setPDFContent("BALANCE DUE ({$this->getCurrencyAbbr()})", 'body', 'yx', 125, 165.4, 40, 6, 'R');
                $this->setPDFContent( number_format($this->getBalance(), 2), 'body', 'yx', 164.8, 164.9, 28, 6, 'C', 1);
            }

            // footernotes
            $this->setPDFContent($this->getFooterNotes(), 'footer', 'yx', 15.5, 223, 185, 3.1, 'L');
            $this->setPDFContent($this->getCheckFooterNote(), 'footer', 'yx', 10.5, 249.2, 58, 3, 'L');
            $this->setPDFContent($this->getCreditCardFooterNote(), 'footer', 'yx', 65, 249.2, 60, 3, 'L');
            $this->setPDFContent($this->getWireTransferFooterNote(), 'footer', 'yx', 125, 249.2, 73, 3, 'L');
       }

        // this code was taken from an indeterminate external source by the original developers of this pdf generator. it was previously located in the FPDF class. it was moved here to avoid overwrites upon updates to the FPDF class. is for "i25 barcodes" according to original notes.
        protected function makeBarCode($xpos, $ypos, $code, $basewidth=1, $height=10){
            try {
                $wide = $basewidth;
                $narrow = $basewidth / 3 ;

                // wide/narrow codes for the digits
                $barChar['0'] = 'nnwwn';
                $barChar['1'] = 'wnnnw';
                $barChar['2'] = 'nwnnw';
                $barChar['3'] = 'wwnnn';
                $barChar['4'] = 'nnwnw';
                $barChar['5'] = 'wnwnn';
                $barChar['6'] = 'nwwnn';
                $barChar['7'] = 'nnnww';
                $barChar['8'] = 'wnnwn';
                $barChar['9'] = 'nwnwn';
                $barChar['A'] = 'nn';
                $barChar['Z'] = 'wn';

                // add leading zero if code-length is odd
                if(strlen($code) % 2 != 0){
                    $code = '0' . $code;
                }

                // write out the numeric version of the barcode 
                // $this->pdf->SetFont('Arial','',5);
                // $this->pdf->Text($xpos + $wide + 58, $ypos + $height, $code);

                $this->pdf->SetFillColor(0);

                // add start and stop codes
                $code = 'AA'.strtolower($code).'ZA';

                for($i=0; $i<strlen($code); $i=$i+2){
                    // choose next pair of digits
                    $charBar = $code[$i];
                    $charSpace = $code[$i+1];
                    // check whether it is a valid digit
                    if(!isset($barChar[$charBar])){
                        throw new Exception('Invalid character in barcode: '.$charBar);
                    }
                    if(!isset($barChar[$charSpace])){
                        throw new Exception('Invalid character in barcode: '.$charSpace);
                    }

                    // create a wide/narrow-sequence (first digit=bars, second digit=spaces)
                    $seq = '';
                    for($s=0; $s<strlen($barChar[$charBar]); $s++){
                        $seq .= $barChar[$charBar][$s] . $barChar[$charSpace][$s];
                    }
                    for($bar=0; $bar<strlen($seq); $bar++){
                        // set lineWidth depending on value
                        if($seq[$bar] == 'n'){
                            $lineWidth = $narrow;
                        }else{
                            $lineWidth = $wide;
                        }
                        // draw every second value, because the second digit of the pair is represented by the spaces
                        if($bar % 2 == 0){
                            $this->pdf->Rect($xpos, $ypos, $lineWidth, $height, 'F');
                        }
                        $xpos += $lineWidth;
                    }
                }

                $this->pdf->SetFillColor(255,255,255);

            } catch (Exception $exc) {
                    IMNLogger::LogError($_SERVER['REQUEST_URI']. ' ['.__CLASS__.'.'.__METHOD__.'('.implode(',', func_get_args()).') : '.__LINE__.']', 'Exception: ' . $exc->getMessage(), array('Stack Trace: ' => $exc->getTrace()) );
                    return false;   
            }
        }

    // end PDF methods ========================================================
}