<?php
use IMN\Logger\IMNLogger;
include_once('all_includes.inc');
connect_thea();

/**
 * @class Invoice 
 * Retrieves all data needed to create an invoice for IMN
 * conference attendees.
 * @author Andrew Willens
 */
class Invoice {
    
    // properties =============================================================
        
        // misc
        protected $request; // stores PHP $_REQUEST global

        // IDs
        protected $invoice_id;
        
        // classed info
        protected $client; // IMN Person object
        protected $conference; // IMN Conference object
        protected $registration; // IMN Registration object

        // conference builder user info
        protected $cb_user_firstname;
        protected $cb_user_lastname;
        protected $cb_user_phone;
        protected $cb_user_email;
        protected $cb_user_jobtitle;
        
        // registration info
        protected $reg_id;
        protected $reg_description;
        protected $reg_process_date;

        // client info
        protected $client_fax;
        protected $client_phone;
        protected $client_email;
        protected $client_full_address;
        protected $client_street_address;
        protected $client_city;
        protected $client_state;
        protected $client_zip;
        protected $client_country;
        
        // conference info
        protected $conference_code;
        protected $sys_code;
        protected $conference_vat_tax_id;
        protected $conference_title;
        protected $conference_full_date;
        protected $conference_full_location;
        protected $conference_description;
        protected $conference_owner_address;
        
        // $$$$
        protected $conversion_rate;
        protected $tax;
        protected $exclude_tax;
        protected $total_paid;
        protected $refund_value;
        protected $reg_fee;
        protected $reg_due; // balance
        protected $currency_abbr;
        protected $currency_char;
        protected $tax_header;
        protected $vat_tax_id;

        // invoice data
        protected $logo_file;
        protected $footer_notes;
        protected $check_footer_note;
        protected $credit_card_footer_note;
        protected $wire_transfer_footer_note;
    
    // end properties =========================================================
   


    // magic ==================================================================
        
        /**
         * Creates an instance of Invoice.
         *
         * 
         * @param Array $request 
         * A request object with possible params:
         * - @prop record_id    String An ID for the person's conference registration.
         * - @prop currency     String ('original' or 'USD') Determines output currency * of PDF.
         * - @prop tax          Boolean String  Determines whether or not to factor tax * into total amount due. (IMN sponsors such as clients aren't taxed.)
         * - @prop cb_user_name String  Specifies user that queried pdfgenerator from Conference Builder. (The interface to this class.)
         *
         * 
         */
        function __construct($request) {
            if ( empty($request['record_id']) ) {
                echo "Error: No record id. Please try your request again; make sure to include record_id in your URL parameters.";
                die();
            }

            $this->setRequest();
            
            // set classed properties
            $this->setRegistration();
            $this->setClient();
            $this->setConference();
            
            // set primitive properties
            $this->setInvoiceID();
            $this->setCBUserInfo();
            $this->setExcludeTax();
            $this->setFees();
            $this->setCurrency();
            $this->setLogo($this->conference_owner);
            $this->setProcessDate();
        }
    // end magic ==============================================================



    // setters and getters ====================================================
        
        // ID setters/getters =================================================
            public function getSysCode() {
                return $this->registration->sys_code;
            }

            function getRecordID() {
                $req = $this->getRequest();
                return $req['record_id'];
            }

            function getPersonID() {
                $reg = $this->getRegistration();
                return $reg->personid;
            }
        // end ID setters/getters =============================================
        

        // misc props =========================================================
            private function setRequest() {
                $this->request = $_REQUEST;
            }

            protected function getRequest() {
                return $this->request;
            }
        // end misc props =====================================================
        

        // cb user props ======================================================
            protected function getConferenceBuilderUserName() {
                $req = $this->getRequest();
                return $req['cb_user_name'];
            }

            protected function getCBUserFullName() {
                return $this->cb_user_firstname . " " . $this->cb_user_lastname;
            }

            protected function getCBUserPhone() {
                return $this->cb_user_phone;
            }

            protected function getCBUserEmail() {
                return $this->cb_user_email;
            }

            protected function getCBUserJobTitle() {
                return $this->cb_user_jobtitle;
            }

            /**
             * Retrieves info on the Conference Builder user that querying the
             * pdfgenerator. A PDO query is sent to the user_windows table,
             * with $this->cb_user_name as an identifying column. 
             */
            protected function setCBUserInfo() {
                try
                {
                    $conn = new PDO(
                      'mysql:host='.UtilDB::getDB_Host().';
                      dbname='.UtilDB::getDB_Name().'',
                      UtilDB::getDB_User(),
                      UtilDB::getDB_Pass(), 
                      array(PDO::ATTR_PERSISTENT => true)
                    );
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $sql = "SELECT 
                                first_name, 
                                last_name, 
                                phone, 
                                email, 
                                job_function
                            FROM user_windows
                            WHERE auth_username = :cb_user_name";
                                
                    $statement = $conn->prepare($sql);
                    $statement->bindValue(':cb_user_name', $this->getConferenceBuilderUserName(), PDO::PARAM_STR);
                    $statement->execute();
                    
                    if ($row = $statement->fetch(PDO::FETCH_ASSOC))
                    {   
                        $this->cb_user_firstname = $row['first_name'];
                        $this->cb_user_lastname  = $row['last_name'];
                        $this->cb_user_phone     = $row['phone'];
                        $this->cb_user_email     = $row['email'];
                        $this->cb_user_jobtitle  = $row['job_function'];
                    }

                }
                catch(Exception $exc)
                {
                    IMNLogger::LogError($_SERVER['REQUEST_URI']. ' ['.__CLASS__.'.'.__METHOD__.'('.implode(',', func_get_args()).') : '.__LINE__.']', 'Exception: ' . $exc->getMessage(), array('Stack Trace: ' => $exc->getTrace()) );
                    return false;
                }
            }
        // end cb user props ==================================================
        
            
        // client props ===================================================
            
            public function getClient() {
                return $this->client;
            }

            protected function getClientFullAddress() {
$address = <<<ADDRESS
{$this->getClientFullName()}
{$this->getClientStreetAddress()}
{$this->getClientCity()}, {$this->getClientState()}
{$this->getClientZip()}
{$this->getClientCountry()}
ADDRESS;
                return $address;
            }

        // collapse client props ==============================================
            // above note needed because php HEREDOC syntax breaks codefolding.
            
            protected function getClientStreetAddress() {
                $address = "{$this->registration->address1} {$this->registration->address2}";

                return $address;
            }

            protected function getClientCity() {
                return $this->registration->city;
            }


            protected function getClientState() {
                return $this->registration->state;
            }

            protected function getClientZip() {
                return $this->registration->zip;
            }

            protected function getClientCountry() {
                return $this->registration->country;
            }

            protected function getClientAssistantEmail() {
                return $this->client->assistant_email;
            }

            protected function getClientFax() {
                return $this->client->fax;
            }

            protected function getClientPhone() {
                return $this->client->phone;
            }                

            protected function getClientEmail() {
                return $this->client->email;
            }                                

            protected function getClientFullName() {
                return "{$this->client->first_name} {$this->client->last_name}";
            }

            protected function getClientFirstName() {
                return $this->client->first_name;
            }

            protected function getClientLastName() {
                return $this->client->last_name;
            }

            protected function getClientCompanyName() {
                return $this->client->company_name_ext;
            }

            /**
             * Sets $this->client to an instance of IMN Person class; also sets
             * some client properties to individually queried table columns.
             * Utilizes $this->registration->personid as an identifying column.
             */
            protected function setClient() {
                $personid = $this->getPersonID();

                try {
                    $conn = new PDO(
                      'mysql:host='.UtilDB::getDB_Host().';
                      dbname='.UtilDB::getDB_Name().'',
                      UtilDB::getDB_User(),
                      UtilDB::getDB_Pass(), 
                      array(PDO::ATTR_PERSISTENT => true)
                    );
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $sql = "SELECT DISTINCT 
                            CONCAT(pi.first_name,' ',pi.last_name) AS clientName,
                            pi.company_name_ext AS clientCompanyName,
                            CONCAT(c.address1,' ',pi.floor) AS clientStreetAddress, 
                            c.city,
                            c.state,
                            c.zip,
                            c.country
                            FROM personal_info_db AS pi
                            LEFT JOIN company_listing_db 
                                AS c ON (pi.company_id=c.company_id)
                            WHERE pi.personid=:personid 
                            LIMIT 1
                    ";
                                
                    $statement = $conn->prepare($sql);
                    $statement->bindValue(':personid', $personid, PDO::PARAM_STR);
                    $statement->execute();
                    
                    if ($row = $statement->fetch(PDO::FETCH_ASSOC))
                    {
                        $this->client_name= $row['clientName'];
                        $this->client_company_name=$row['clientCompanyName'];
                        $this->client_street_address=$row['clientStreetAddress'];
                        $this->client_city=$row['city'];
                        $this->client_state=$row['state'];
                        $this->client_zip=$row['zip'];
                        $this->client_country=$row['country'];
                    }

                }
                catch(Exception $exc)
                {
                    IMNLogger::LogError($_SERVER['REQUEST_URI']. ' ['.__CLASS__.'.'.__METHOD__.'('.implode(',', func_get_args()).') : '.__LINE__.']', 'Exception: ' . $exc->getMessage(), array('Stack Trace: ' => $exc->getTrace()) );
                    return false;
                }

                try 
                {
                    $this->client = Person::byPersonID($personid);
                } catch (Exception $e) {
                    IMNLogger::LogError($_SERVER['REQUEST_URI']. ' ['.__CLASS__.'.'.__METHOD__.'('.implode(',', func_get_args()).') : '.__LINE__.']', 'Exception: ' . $exc->getMessage(), array('Stack Trace: ' => $exc->getTrace()) );
                    return false;
                }

            }
        // end client props ===============================================


        // conference props ===============================================
            protected function getConferenceDescription() {
                $string21 = (strlen($this->conference_title) > 68) ? substr($this->conference_title,0,69).'..' : $this->conference_title;

                return  $this->euroDecode( "$string21\n{$this->getConferenceLocation()}\n{$this->getConferenceDate()}" );
            }

            protected function getConference() {
                return $this->conference;
            }

            protected function getConferenceTitle() {
                return $this->conference->conference_title;
            }

            protected function getConferenceDate() {
                return $this->conference->full_date;
            }

            protected function getConferenceLocation() {
                return $this->conference->full_location;
            }


            protected function getConferenceCode() {
                return $this->conference_code;
            }

            protected function getConferenceName() {
                return $this->euroDecode($this->conferenceName);
            }

            protected function getConferenceOwner() {
                return $this->euroDecode($this->conference->event_owner);
            }

            protected function getConferenceOwnerAddress($phone=true, $fax=true) {
                
                $address = "EIMN, LLC\n225 Park Avenue South, 7th Floor - New York, N.Y. 10003, USA";

                $address = $phone === true ? $address . "\nTel: 212-768-2800" : $address;
                $address = $fax   === true ? $address . "\nFax: 212-768-2484" : $address;

                return $address;
            }

            /**
             * Sets $this->conference and $this->[conference properties] with
             * $this->registration->sys_code and $this->registration->company_id
             * as identifying columns.
             */
            protected function setConference() {
                $sys_code   = $this->getSysCode();
                $company_id = $this->registration->company_id;
                
                try {
                    $this->conference = Conference::getConferenceBySysCode($sys_code);

                    $this->conference_full_date=$this->conference->full_date;
                    $this->conference_full_location=$this->conference->full_location;
                    $this->conference_owner=$this->conference->event_owner;

                    $temp_title = $this->conference->conference_title;
                    $this->conference_title = strlen($temp_title) > 60 ? substr($temp_title, 0, 60) . ".." : $temp_title;

                    $this->conference_code = $this->conference->a_code;

                    $this->setVatTaxID($this->conference->sys_code, $this->conference->tax_amount);

                } catch (Exception $e) {
                    IMNLogger::LogError($_SERVER['REQUEST_URI']. ' ['.__CLASS__.'.'.__METHOD__.'('.implode(',', func_get_args()).') : '.__LINE__.']', 'Exception: ' . $exc->getMessage(), array('Stack Trace: ' => $exc->getTrace()) );
                    return false;
                }
            }
        // end conference props ===========================================


        // registration props =============================================
            protected function getRegID() {
                return $this->reg_id;
            }

            protected function getRegProcessDate() {
                return $this->reg_process_date;
            }

            /**
             * Sets registration to instance of IMN Registration class. Also
             * sets some primitive registration properties. 
             */
            protected function setRegistration() {
                $record_id = $this->stripQuotes($this->getRecordID());

                $this->registration = Registration::getRegistrationByRecordID($record_id);

                $this->reg_id = $this->registration->temp_id;

                // set printed registration info
                $blownup_regtype = explode("|", $this->registration->regtype);
                $this->reg_description = $blownup_regtype[0];
            }

            protected function getRegistration() {
                return $this->registration;
            }

        // end registration props =========================================
        

        // invoice props ==================================================
            /**
             * Set logo according to conference or conference owner. Defaults to
             * IMN logo.
             * @param String $sys_code_or_event_owner Alphanumeric string
             * representing abbreviated conference owner name or conference ID.
             */
            public function setLogo($sys_code_or_event_owner) {                
                switch ($sys_code_or_event_owner) {
                    case '20150208_SF_0070':
                        // For ABS Vegas 2014, can be removed really
                        $this->logo_file = "sfig_imn.jpg";
                        break;
                    case 'SFIG':
                        $this->logo_file = "sfig_imn.jpg";
                        break;
                    case 'II':
                        $this->logo_file = "logo_ii.jpg";
                        break;
                    default:
                        $this->logo_file = "logo_imn.jpg";
                        break;
                }
            } 

            public function getLogo() {
                return $this->logo_file;
            }

            public function setProcessDate() {
                $this->reg_process_date = date('m/d/Y', strtotime($this->registration->process_date));
            }

            public function getProcessDate() {
                return $this->reg_process_date;
            }

            public function getInvoiceID($index=0) {
                if ( gettype($this->invoice_id) === "array" ) {
                    return $this->invoice_id[$index];
                }

                return $this->invoice_id;
            }

            /**
             * Sets $this->invoice_id equal to client's temp_id, from instance
             * of Person class.
             */
            protected function setInvoiceID() {
                try
                {
                    $conn = new PDO(
                      'mysql:host='.UtilDB::getDB_Host().';
                      dbname='.UtilDB::getDB_Name().'',
                      UtilDB::getDB_User(),
                      UtilDB::getDB_Pass(), 
                      array(PDO::ATTR_PERSISTENT => true)
                    );
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $sql = "SELECT temp_id FROM person 
                            WHERE personid = :personid
                    ";
                                
                    $statement = $conn->prepare($sql);
                    $statement->bindValue(':personid', $this->getPersonid(), PDO::PARAM_STR);
                    $statement->execute();

                    if ($row = $statement->fetch(PDO::FETCH_ASSOC))
                    {
                       $this->invoice_id = $row['temp_id']; 
                    }

                }
                catch(Exception $exc)
                {
                    IMNLogger::LogError($_SERVER['REQUEST_URI']. ' ['.__CLASS__.'.'.__METHOD__.'('.implode(',', func_get_args()).') : '.__LINE__.']', 'Exception: ' . $exc->getMessage(), array('Stack Trace: ' => $exc->getTrace()) );
                    return false;
                }
            }
        // end invoice props ==============================================


        // $$$ props ======================================================
            protected function getTotalFee(){
                $total = $this->exclude_tax === true ? $this->getRegFee() : $this->getRegFee() + $this->getTax();
                return $total;
            }

            /**
             * Determines whether or not VAT or standard Tax should be included
             * in invoice printout.
             */
            protected function setExcludeTax() {
                $request = $this->getRequest();

                if ($this->registration->keycode === "SPONSOR") {
                    $this->exclude_tax = true;
                } 
                elseif ( !empty($request['tax']) && $request['tax'] === 'false' ) 
                {
                    $this->exclude_tax = true;
                } else {
                    $this->exclude_tax = false;
                }
            }

            protected function getExcludeTax() {
                return $this->exclude_tax;
            }

            /** 
             * Set $this->[$$$ props] according to 'currency' url param.
             * 'Original' causes fees to be set in original currency, such as
             * GBP or EURO, instead of USD. 
             */
            protected function setFees() {
                $request = $this->getRequest();

                if ( !empty($request['currency']) ) {
                    
                    switch ($request['currency']) {
                        
                        case 'original':
                            if ( !empty($this->registration->regrate_id) ) {
                                $regrate = RegistrationRate::getSpecificRateByTempID($this->registration->regrate_id);

                                $this->conversion_rate = $regrate->pricing / $regrate->usd_amount;

                                $this->reg_fee          = $regrate->pricing;
                                $this->reg_due          = $this->registration->regDue * $this->conversion_rate;
                                
                                $this->tax             = $this->reg_fee * $this->conference->tax_amount / 100;
                                $this->tax_header       = "VAT TAX";  
                                break;
                            }
                        
                        case 'USD':
                            $this->reg_fee = $this->registration->regFee;
                            $this->total_paid = $this->registration->totalPaid;
                            $this->reg_due = $this->registration->regDue;
                            $this->tax = $this->registration->regTax;
                            $this->tax_header = "TAX";
                            break;

                        default:
                            $this->reg_fee = $this->registration->regFee;
                            $this->total_paid = $this->registration->totalPaid;
                            $this->reg_due = $this->registration->regDue;
                            $this->tax = $this->registration->regTax;
                            $this->tax_header = "TAX";
                            break;
                    }
                }
                else {
                    $this->reg_fee = $this->registration->regFee;
                    $this->total_paid = $this->registration->totalPaid;
                    $this->reg_due = $this->registration->regDue;
                    $this->tax = $this->registration->regTax;
                    $this->tax_header = "TAX";
                }
            }

            
            /** 
             * Set $this->currency_char according to 'currency' url param.
             * 'Original' causes fees to be set in original currency, such as
             * GBP or EURO, instead of USD. 
             */
            protected function setCurrency() {
                $request = $this->getRequest();

                if ( !empty($request['currency']) && $request['currency'] === "original" ) 
                {
                    $regrate = RegistrationRate::getSpecificRateByTempID($this->registration->regrate_id);

                    $this->currency_char = $regrate->currency;
                    
                    switch ($this->currency_char) {
                        case '$':
                            $this->currency_abbr = 'USD';
                            break;
                        
                        case '&euro;':
                            $this->currency_abbr = 'EUR';
                            break;
                        
                        case '&pound;':
                            $this->currency_abbr = 'GBP';
                            break;

                        default:
                            $this->currency_abbr = 'USD';
                            break;
                    }
                }
                else {
                    $this->currency_abbr = 'USD';
                }
            }

            protected function getCurrencyAbbr() {
                return $this->currency_abbr;
            }

            protected function getCurrencyChar() {   
                return $this->currency_char;
            }

            protected function getTaxHeader() {
                return $this->tax_header;
            }

            protected function getTax() {
                $tax = $this->getExcludeTax() === true ? 0 : $this->tax;
                return $tax;
            } 

            protected function getRegFee() {
                return $this->reg_fee;
            }

            protected function getVatTaxID() {
                return $this->vat_tax_id;
            }

            protected function getRefundValue() {
                return $this->refund_value;
            }

            protected function getBalance() {
                $due = $this->exclude_tax === true ? $this->reg_due - $this->getTax() : $this->reg_due;
                return $due;
            }

            protected function getTotalPaid() {
                return $this->total_paid;
            }

            protected function setVatTaxID($sys_code, $tax_amount) {
                
                if ( $tax_amount > 0) {
                    if ( strpos($sys_code, "_SF_0007") ) {
                        $this->vat_tax_id = "VAT REG. # BE0834489119";
                    }
                    elseif (
                        strpos($this->conference_title, 'Global ABS') 
                        ||
                        strpos($this->sys_code, "_RE_0077")
                    ) 
                    {
                        $this->vat_tax_id = "VAT REG. # N4007030B";
                    }
                    else 
                    {
                        $this->vat_tax_id = "VAT REG. # 922 9023 39";

                    }
                } 
                else 
                {
                   $this->vat_tax_id = ""; 
                }

            }    
        // end $$$ props ==================================================
        

        // text props =====================================================
            protected function getCheckFooterNote(){
               return "Company Check or Bank Check:\nEIMN, LLC.\n225 Park Avenue South; 7th Floor\nNew York, NY 10003\nAttn: Accounting Department\n(Please use Overnight Service)\n";
            }

            protected function getCreditCardFooterNote(){
                return "Credit Cards:\nAmerican Express, Visa, Master card, Diners Club and Discover (Please call with credit card information. See contact information above.) Note: All credit card payments will be charged in USD and may incur a processing fee\n\n\n\n";
            }

            protected function getWireTransferFooterNote(){
                return "Payment is due upon receipt of invoice.\n\nPlease be advised that advanced delegate lists will not be issued until full contract payment has been tendered.\nNOTE: If you pay by check, mailing of the check will constitute neither delivery nor payment. All invoices are deemed unpaid and due until the check has been cleared through our bank account and the relevant amount credited to our bank account.";
            }

            protected function getFooterNotes() {
                return "Payment is due upon receipt of invoice.\n\nPlease be advised that advanced delegate lists will not be issued until full contract payment has been tendered.\nNOTE: If you pay by check, mailing of the check will constitute neither delivery nor payment. All invoices are deemed unpaid and due until the check has been cleared through our bank account and the relevant amount credited to our bank account.";
            }
        // end text props =================================================

    // end setters and getters ================================================


    // save/check invoice records =============================================

        /**
         * Check if invoice has been sent to this client for this registration
         * already.
         * @return Boolean This invoice has been sent to client already, or no?
         */
        protected function invoiceIsNew() {
            try
            {
                $conn = new PDO(
                  'mysql:host='.UtilDB::getDB_Host().';
                  dbname='.UtilDB::getDB_Name().'',
                  UtilDB::getDB_User(),
                  UtilDB::getDB_Pass(), 
                  array(PDO::ATTR_PERSISTENT => true)
                );
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $sql = "SELECT * FROM acc_invoice_receipt 
                        WHERE invoice_receipt_id = :invoice_id
                        AND user = :cb_user_name
                ";
                            
                $statement = $conn->prepare($sql);
                $statement->bindValue(':invoice_id', $this->getInvoiceID(), PDO::PARAM_STR);
                $statement->bindValue(':cb_user_name', $this->getConferenceBuilderUserName(), PDO::PARAM_STR);
                $statement->execute();

                if ($row = $statement->fetch(PDO::FETCH_ASSOC))
                {
                    return false;
                }

                return true;
            }
            catch(Exception $exc)
            {
                IMNLogger::LogError($_SERVER['REQUEST_URI']. ' ['.__CLASS__.'.'.__METHOD__.'('.implode(',', func_get_args()).') : '.__LINE__.']', 'Exception: ' . $exc->getMessage(), array('Stack Trace: ' => $exc->getTrace()) );
                return false;
            }  
        }

        /** 
         * Make invoice receipt string.
         * @return String Receipt describing invoice.
         */
        protected function makeInvoiceReceiptString() {
            return "INVOICE MAILED TO:\n
                {$this->getClientFullAddress()}                   
                \n---------------------------
                CONFERENCE: {$this->getConferenceDescription()}
                CONFERENCE CODE: {$this->getConferenceCode()}
                INVOICE DATE: {$this->getRegProcessDate()}
                M-INVOICE NO: {$this->getInvoiceID()}
                REGISTRATION ID: {$this->getRecordID()}
                \n---------------------------
                Currency: {$this->getCurrencyAbbr()}
                Total Fees: {$this->getRegFee()}    
                Total Tax: {$this->getTax()}
                Total Paid: {$this->getTotalPaid()}
                Balance Due: {$this->getBalance()}
            ";
        }

        /**
         * Save invoice receipt to IMN database.
         */
        public function saveInvoiceReceipt() {

            if ( $this->invoiceIsNew($this->getRecordID()) ) 
            {
                $invoice_receipt = $this->makeInvoiceReceiptString(); 

                try
                {
                    $conn = new PDO(
                      'mysql:host='.UtilDB::getDB_Host().';
                      dbname='.UtilDB::getDB_Name().'',
                      UtilDB::getDB_User(),
                      UtilDB::getDB_Pass(), 
                      array(PDO::ATTR_PERSISTENT => true)
                    );
                    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    $sql = "INSERT INTO acc_invoice_receipt (
                                record_id,
                                invoice_receipt_id,
                                type,
                                contents,
                                user
                            )
                            VALUES (
                                :record_id,
                                :invoice_receipt_id,
                                :type,
                                :contents,
                                :cb_user_name
                            );
                    ";
                                
                    $statement = $conn->prepare($sql);
                    $statement->bindValue(':record_id', $this->getRecordID(), PDO::PARAM_STR);
                    $statement->bindValue(':invoice_receipt_id', $this->getInvoiceID(), PDO::PARAM_STR);
                    $statement->bindValue(':type', 'invoice', PDO::PARAM_STR);
                    $statement->bindValue(':contents', $invoice_receipt, PDO::PARAM_STR);
                    $statement->bindValue(':cb_user_name', $this->getConferenceBuilderUserName(), PDO::PARAM_STR);
                    
                    $statement->execute();
                }
                catch(Exception $exc)
                {
                    IMNLogger::LogError($_SERVER['REQUEST_URI']. ' ['.__CLASS__.'.'.__METHOD__.'('.implode(',', func_get_args()).') : '.__LINE__.']', 'Exception: ' . $exc->getMessage(), array('Stack Trace: ' => $exc->getTrace()) );
                    return false;
                }
            }              
        }

    // end save invoice methods ===============================================


    // helper methods =====================================================    
        /**
         * Properly encode HTML special chars, such as foreign currency chars.
         * @param  String $string
         * @return String utf-8 encoded string
         */
        protected static function euroDecode($string) {
            return utf8_decode(iconv('utf-8', 'cp1252', $string));
        }

        protected static function stripQuotes($string) {
            $string = str_replace("'", "", $string);
            $string = str_replace("\"", "", $string);

            return $string;
        }

    // end helper methods =====================================================

}
