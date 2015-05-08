<?php
include_once('_Classes/Invoice.class.php');

/**
 * InvoiceEmail Class. Takes data from Invoice class and renders it as an HTML
 * email form.
 * @author AndrewWillens
*/
class InvoiceEmail extends Invoice
{
    
    // properties =============================================================
        // DOM big pieces
        private $html;
        private $head;
        private $body;
        private $style;

        // inputs
        private $subject;
        private $from_input;
        private $to_input;
        private $subject_input;
        private $remake_invoice_date;
        private $recordid_input;
        private $cb_username_input;
        private $cc_input;
        private $attachment_input;
        private $email_html;

        // primitives
        private $email_text;
        private $signature;
        private $payment_policy;

    // end properties =========================================================
   
    // magic ==================================================================
        function __construct($request)
        {
            // inherit constructor from Invoice
            parent::__construct($request);

            // set primitives
            $this->setPaymentPolicy();
            
            // set inputs
            $this->setFormInputs();
            
            // set DOM big pieces
            $this->setStyle();
            $this->setHead('Invoice');
            $this->setBody();
            $this->setDOM();
        }
    // end magic ==============================================================

    // big HTML piece setters and getters =====================================
        /**
         * Set all form inputs all at once.
         */
        private function setFormInputs () {
            $this->setFromInput();
            $this->setToInput();
            $this->setCCInput();
            $this->setBCCInput();
            $this->setSubjectInput();
            $this->setEmailText();
            $this->setEmailHTML();

            // hidden
            $this->setRecordIDInput();
            $this->setRemakeInvoiceDateInput();
            $this->setConferenceBuilderUserNameInput();
        }
        
        private function setHead($title) {
$this->head = <<<HEAD
    <head>
        <title>$title</title>
        <meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
        {$this->getStyle()}
    </head>
HEAD;
        }

        public function getHead() {
            return $this->head;
        }


        public function setBody() {
$this->body = <<<BODY
<body>
    <form action="invoice_maker_and_email_processor.cfm" method="post">
        <table>
            {$this->getFromInput()}
            {$this->getToInput()}
            {$this->getCCInput()}
            {$this->getBCCInput()}
            {$this->getSubjectInput()}
            {$this->getEmailHTML()}
        </table>
        {$this->getRecordIDInput()}
        {$this->getRemakeInvoiceDateInput()}
        {$this->getConferenceBuilderUserNameInput()}
    </form>
</body>
BODY;
        }

        private function getBody() {
            return $this->body;
        } 


        private function setDOM() {
            $this->html = "<html>" . $this->getHead() . $this->getBody() . "</html>";
        }

        public function getDOM() {
            return $this->html;
        }

        private function setStyle() {
$this->style = <<<STYLE
<style>
        form table {
            width: 600px;
            text-align: center;
            padding: 0;
            // border-spacing: 10px;
            // border-collapse: separate;
        }
        td {
            font-family: Verdana, Arial, Helvetica, sans-serif;
            font-size: .8em;
            vertical-align: top;
            text-align: left;
            width: 20px;
        }
        form input[type="text"] {
            width: 20em;
            font-size: 1em;
        }
        .emailText {
            width: 80em; 
            height: 60em;
        }
</style>
STYLE;
        }

     public function getStyle() {
        return $this->style;
     }

    // end  big HTML piece setters and getters ================================
    

    // form input setters/getters =============================================
        private function setEmailHTML() {
$this->email_html = <<<EMBODY
    <td colspan="2"> 
        <textarea class="emailText" name="email_body" >{$this->getEmailText()}</textarea>
    </td>
EMBODY;
        }

        private function getEmailHTML() {
            return $this->email_html;
        }

        private function setEmailText() {
            $req = $this->getRequest();

            if (!empty($req['language']) && $req['language'] ==='spanish' ) {
                $this->email_text = $this->makeSpanishEmail();
            } 
            else {
                $this->email_text = $this->makeEnglishEmail();
            }
        }


        private function makeEnglishEmail() {

$email_text = <<<ETXT
Dear {$this->getClientFullName()},

Please find the attached PDF invoice #{$this->getInvoiceID()}

Confirmation ID: {$this->getRecordID()}
Conference: {$this->getConferenceTitle()}
Conference Date(s): {$this->getConferenceDate()}
Conference Location: {$this->getConferenceLocation()}
First Name: {$this->getClientFirstName()}
Last Name: {$this->getClientLastName()}
Company Name: {$this->getClientCompanyName()}
Fee ({$this->getCurrencyAbbr()}): {$this->getTotalFee()}
Tax ({$this->getCurrencyAbbr()}): {$this->getTax()}
Amount Paid ({$this->getCurrencyAbbr()}): {$this->getTotalPaid()}
Balance Due ({$this->getCurrencyAbbr()}): {$this->getBalance()}

{$this->getPaymentPolicy()}

The following is a copy of your record in the {$this->getConferenceOwner()} database. Please review and inform us of any inaccuracy.
First Name: {$this->getClientFirstName()}
Last Name: {$this->getClientLastName()}
Email: {$this->getClientEmail()}
Phone: {$this->getClientPhone()}
Fax: {$this->getClientFax()}
Company Name: {$this->getClientCompanyName()}
Address:
{$this->getClientFullAddress()}

We hope that you will be able to take advantage of the many benefits {$this->getConferenceOwner()} is able to provide. Your participation and support is vital to the success of our programs! Thank you for choosing {$this->getConferenceOwner()}; we look forward to serving you again.

Sincerely,
{$this->getCBUserFullName()}
{$this->getCBUserJobTitle()}
{$this->getCBUserPhone()}
{$this->getCBUserEmail()}
{$this->getConferenceOwnerAddress(false, false)}
ETXT;
            return $email_text;
        }


        private function makeSpanishEmail() {

$email_text = <<<SPETXT
Muy {$this->getClientFullName()},

Por favor, encontrar la factura PDF adjunta #{$this->getInvoiceID()}
Asunto / Subject: Confirmación de registro / Registration Confirmation

Confirmation ID: {$this->getRecordID()}          
Conferencia / Conference: {$this->getConferenceTitle()}
Fecha / Conference Date(s): {$this->getConferenceDate()}
Lugar / Conference Location: {$this->getConferenceLocation()}

Nombre de pila / First Name: {$this->getClientFirstName()}
Apellidos / Last Name: {$this->getClientLastName()}
Empresa / Company Name:  {$this->getClientCompanyName()}
Cuota de inscripción (21% IVA no incluido) / Fee (21% VAT not included): {$this->getCurrencyAbbr()} {$this->getTotalFee()}

***************************************************************************
Por favor, imprima una copia de esta confirmación para su archivo. Esta confirmación de registro no es un comprobante de pago si la cuota de inscripción (ver arriba) es superior a cero.
Please print a copy of this confirmation for your records. Note: this registration confirmation does not verify payment if your registration fee above is greater than zero.
Contacto para incidencias de registro / IMN Contact: {$this->getConferenceBuilderUserName()} 
Teléfono / Phone: {$this->getClientEmail()}
Email: {$this->getClientPhone()}
Para ser atendido en español, llame al número +34 91 781 4200

A continuación le mostramos la información registrada en la base de datos de IMN. Por favor, compruebe si existe algún error.
Following is a copy of your record in IMN database. Please review the below information for any inaccuracies.

Nombre de pila / First Name: {$this->getClientFirstName()}
Apellidos / Last Name: {$this->getClientLastName()}
E-Mail: {$this->getClientEmail()}
Teléfono / Phone: {$this->getClientPhone()}  
Empresa / Company Name: {$this->getClientCompanyName()}
Dirección / Address:
{$this->getClientFullAddress()}

Muchas gracias por registrarse. Esperamos verle muy pronto.
Thank you for registration and we look forward to seeing you. 
Los nombres de los asistentes y las empresas podrían utilizarse con fines promocionales.
Please note: Attendee names and companies may be used for promotional purposes.

Sincerely,
{$this->getCBUserFullName()}
{$this->getCBUserJobTitle()}
{$this->getCBUserPhone()}
{$this->getCBUserEmail()}
{$this->getConferenceOwnerAddress(false, false)}
SPETXT;

            return $email_text;
        }


        private function getEmailText() {
            return $this->email_text;
        }


        private function setAttachmentInput() {
$this->attachment_input = <<<ATCHINPUT
    <tr> 
        <td>Attachment:</td>
        <td> 
            <nobr>(Testing new function, please don't use this field)
            <input  type="file" name="attachment" size="50"> </nobr>
        </td>
    </tr>
ATCHINPUT;
        }

        private function getAttachmentInput() {
            return $this->attachment_input;
        }

        private function setSubjectInput() {
            $subject = $this->getConferenceOwner() . " Conference Invoice Attached.";
$this->subject_input = <<<SUBJINPUT
    <tr>
        <td>Subject:</td>
        <td>
            <input type="text" name="subject" size="45"  value="{$subject}">
        </td>
    </tr>
SUBJINPUT;
        }

        private function getSubjectInput() {
            return $this->subject_input;
        }

        private function setBCCInput() {
$this->bcc_input = <<<BCCINPUT
<tr> 
    <td>Bcc:</td>
    <td> 
        <input type="text" name="bcc" value="">
    </td>
</tr>
BCCINPUT;
        }

        public function getBCCInput() {
            return $this->bcc_input;
        }

        private function setCCInput() {
$this->cc_input = <<<CCINPUT
    <tr> 
        <td>Cc:</td>
        <td>
            <input type="text" name="cc" value="{$this->getClientAssistantEmail()}">
        </td>
    </tr>
CCINPUT;
        }

        private function getCCInput() {
            return $this->cc_input;
        }

        private function setToInput() {
$this->to_input = <<<TOINPUT
    <tr> 
        <td>To:</td>
        <td>
            <input type="text" name="to" id="to" value="{$this->registration->email}">
        </td>
    </tr>
TOINPUT;
        }

        private function getToInput() {
            return $this->to_input;
        }

        private function setFromInput() {
$this->from_input = <<<FROM
    <tr> 
            <td>From:</td>
            <td>
                <input type="text" name="from" value="{$this->getConferenceBuilderUserName()}">
                <input type="hidden" name="from" value="{$this->getConferenceBuilderUserName()}">
            </td>
    </tr>
FROM;
        }

        private function getFromInput() {
            return $this->from_input;
        }

        private function setConferenceBuilderUserNameInput() {
$this->cb_username_input = <<<CBUINPUT
    <input type="hidden" name="cb_username" value="{$this->getConferenceBuilderUserName()}">
CBUINPUT;
        }

        private function getConferenceBuilderUserNameInput() {
            return $this->cb_username_input;
        }

        private function setRemakeInvoiceDateInput() {
            $today = date("F j, Y, g:i a");
$this->remake_invoice_date = <<<RMIINPUT
    <input type="hidden" name="remake_invoice_date" value="$today">
RMIINPUT;
        }

        private function getRemakeInvoiceDateInput() {
            return $this->remake_invoice_date;
        }

        private function setRecordIDInput() {
$this->recordid_input = <<<RIDINPUT
    <input type="hidden" name="record_id" value="{$this->getRecordID()}">
RIDINPUT;
        }

        private function getRecordIDInput() {
            return $this->recordid_input;
        }

    // end form input setters/getters =========================================

    // primitive property setters/getters =====================================
        private function setPaymentPolicy() {
            $conference = $this->getConference();

            switch ($this->sys_code) {
                case '20150208_SF_0070':
                    $this->payment_policy = "Payments can be made by Check (USD checks must be drawn from a US bank) or wire transfer. Please make all checks payable to {$this->getConferenceOwner()}. In the memo area of the check, please write the name of the conference registrant(s) and the conference name. \n\nPayment is due upon receipt, BUT NOT BEFORE SEP 8th 2014, 09:00AM EST.\n {$this->getCheckFooterNote()} {$this->getWireTransferFooterNote()}";
                    break;
                    
                default:
                    $this->payment_policy = "Payments can be made by American Express, Visa, MasterCard, Diners Club, Discover Card, check (USD checks must be drawn on a US bank), or wire transfer. If registering 2 weeks or less prior to the start of the conference, you must pay by credit card. Please make all checks payable to {$this->getConferenceOwner()}. In the memo area of the check, please write the name of the conference registrant(s) and the conference name.\n\n{$this->getCheckFooterNote()}\n{$this->getWireTransferFooterNote()}";
                    break;
            }
        }

        public function getPaymentPolicy() {
            return $this->payment_policy;
        }

    // primitive property setters/getters ==================================== 
}
