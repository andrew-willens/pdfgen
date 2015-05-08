<?php 
/**
 * Interface to InvoicePDF class. Receives a request from client and sends back
 * the PDF.
 *
 * @author AndrewWillens
 */

include_once('_Classes/InvoicePDF.class.php');

try {
    $invoice = new InvoicePDF($_REQUEST);    
    
    // Send the pdf back with 'output_name' as the filename. If none included, defaults to 'invoice.pdf'.
    if (!empty($_REQUEST['output_name']) ) {
      $invoice->outputPDF($_REQUEST['output_name']);
    } 
    else {
      $invoice->outputPDF();
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
