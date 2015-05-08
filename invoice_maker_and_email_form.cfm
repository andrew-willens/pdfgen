<?php
/**
 * Interface to InvoiceEmail class. Receives a request from client and renders
 * an HTML email form.
 *
 * @author AndrewWillens
 */

include_once('_Classes/InvoiceEmail.class.php');

try {
    $invoice = new InvoiceEmail($_REQUEST); 
    echo $invoice->getDOM();

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
