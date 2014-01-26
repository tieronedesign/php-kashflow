<?php

class Kashflow
{
	public $webservice_url = 'https://securedwebapp.com/api/service.asmx';
	public $username;
	private $password;

	private $curl;
	private $headers;

	public $request;
	public $response;

	public $error_msg = array();

	function __construct($user, $pass)
	{
		// Set the username and password
		$this->username = $user;
		$this->password = $pass;

		// Hide some simple XML errors we dont want to see
		libxml_use_internal_errors(true);
	}

	private function SendRequest($xml, $task)
	{
		$result = false;

		// Build the SOAP request
		$this->request = '<?xml version="1.0" encoding="utf-8"?>';
		$this->request .= '<soap:Envelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">';
		$this->request .= '<soap:Body>';
		$this->request .= '<' . $task . ' xmlns="KashFlow">';
		$this->request .= '<UserName>' . $this->username . '</UserName>';
		$this->request .= '<Password>' . $this->password . '</Password>';
		$this->request .= $xml;
		$this->request .= '</' . $task . '></soap:Body></soap:Envelope>';

		// Build the HTTP headers
		$this->headers = array();
		$this->headers[] = 'User-Agent: KashFlowPhpKit';
		$this->headers[] = 'Host: secure.kashflow.co.uk';
		$this->headers[] = 'Content-Type: text/xml; charset=utf-8';
		$this->headers[] = 'Accept: text/xml';
		$this->headers[] = 'Content-Length: ' . strlen($this->request);
		$this->headers[] = 'SOAPAction: "KashFlow/' . $task . '"';

		// Send the request over to KashFlow
		$this->curl = curl_init();
		curl_setopt($this->curl, CURLOPT_URL, $this->webservice_url);
		curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->request);
		$output = curl_exec($this->curl);

		// Check for cURL errors
		if (curl_errno($this->curl)) {
			// Define error details
			$curl_error = curl_error($this->curl);
			$curl_errorno = curl_errno($this->curl);

			// Close the connection
			curl_close($this->curl);

			// Return the error message
			$this->error_msg['ErrorMsg'] = 'cURL encountered an error connecting to the KashFlow web service, this was reported as ' . $curl_error;

			return $this->error_msg;
		} else {
			// Close the connection
			curl_close($this->curl);

			// Return the API output
			$output = preg_replace('|<([/\w]+)(:)|m', '<$1', preg_replace('|(\w+)(:)(\w+=\")|m', '$1$3', $output));
			$this->response = $output;

			return simplexml_load_string($output);
		}
	}

	private function object_to_array($xml)
	{
		if (is_object($xml) && get_class($xml) == 'SimpleXMLElement') {
			$attributes = $xml->attributes();
			foreach ($attributes as $k => $v) {
				if ($v) {
					$a[$k] = (string)$v;
				}
			}
			$x = $xml;
			$xml = get_object_vars($xml);
		}

		if (is_array($xml)) {
			if (count($xml) == 0) {
				return (string)$x;
			} // for CDATA
			foreach ($xml as $key => $value) {
				$r[$key] = $this->object_to_array($value);
			}

			if (isset($a)) {
				$r['@'] = $a;
			} // Attributes

			return $r;
		}

		return (string)$xml;
	}

	private function clean_dataset($data)
	{
		$new_data = array();

		if (isset($data['0']) == false) {
			$new_data['0'] = $data;
		} else {
			$new_data = $data;
		}

		return $new_data;
	}

	///////////////////////////////////////////////////
	// CUSTOMERS
	///////////////////////////////////////////////////

	public function GetCustomer($data)
	{
		$xml = '<CustomerCode>' . (string)$data['CustomerCode'] . '</CustomerCode>';

		$response = $this->SendRequest($xml, 'GetCustomer');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetCustomerResponse->Status == 'OK') {
				return $this->object_to_array($response->soapBody->GetCustomerResponse->GetCustomerResult);
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomerResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetCustomerByID($data)
	{
		$xml = '<CustomerID>' . (int)$data['CustomerID'] . '</CustomerID>';

		$response = $this->SendRequest($xml, 'GetCustomerByID');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetCustomerByIDResponse->Status == 'OK') {
				return $this->object_to_array($response->soapBody->GetCustomerByIDResponse->GetCustomerByIDResult);
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomerByIDResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetCustomerByEmail($data)
	{
		$xml = '<CustomerEmail>' . (string)$data['CustomerEmail'] . '</CustomerEmail>';

		$response = $this->SendRequest($xml, 'GetCustomerByEmail');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetCustomerByEmailResponse->Status == 'OK') {
				return $this->object_to_array(
					$response->soapBody->GetCustomerByEmailResponse->GetCustomerByEmailResult
				);
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomerByEmailResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetCustomers()
	{
		$response = $this->SendRequest('', 'GetCustomers');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetCustomersResponse->Status == 'OK') {
				$array = (array)$response->soapBody->GetCustomersResponse->GetCustomersResult;

				return $this->clean_dataset($this->object_to_array($array['Customer']));
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomersResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetCustomersModifiedSince($data)
	{
		// Returns all data held in an array Customer

		$xml = '<ModifiedSince>' . (string)$data['ModifiedSince'] . '</ModifiedSince>';

		$response = $this->SendRequest($xml, 'GetCustomersModifiedSince');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetCustomersModifiedSinceResponse->Status == 'OK') {
				$array = (array)$response->soapBody->GetCustomersModifiedSinceResponse->GetCustomersModifiedSinceResult;

				return $this->clean_dataset($this->object_to_array($array['Customer']));
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomersModifiedSinceResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function InsertCustomer($data)
	{
		if ($data['ShowDiscount'] == true) {
			$data['ShowDiscount'] = 'true';
		} else {
			$data['ShowDiscount'] = 'false';
		}

		$xml = '<custr>';
		$xml .= '<CustomerID>' . (int)$data['CustomerID'] . '</CustomerID>';
		$xml .= '<Code>' . (string)$data['Code'] . '</Code>';
		$xml .= '<Name>' . (string)$data['Name'] . '</Name>';
		$xml .= '<Contact>' . (string)$data['Contact'] . '</Contact>';
		$xml .= '<Telephone>' . (string)$data['Telephone'] . '</Telephone>';
		$xml .= '<Mobile>' . (string)$data['Mobile'] . '</Mobile>';
		$xml .= '<Fax>' . (string)$data['Fax'] . '</Fax>';
		$xml .= '<Email>' . (string)$data['Email'] . '</Email>';
		$xml .= '<Address1>' . (string)$data['Address1'] . '</Address1>';
		$xml .= '<Address2>' . (string)$data['Address2'] . '</Address2>';
		$xml .= '<Address3>' . (string)$data['Address3'] . '</Address3>';
		$xml .= '<Address4>' . (string)$data['Address4'] . '</Address4>';
		$xml .= '<Postcode>' . (string)$data['Postcode'] . '</Postcode>';
		$xml .= '<Website>' . (string)$data['Website'] . '</Website>';
		$xml .= '<EC>' . (int)$data['EC'] . '</EC>';
		$xml .= '<OutsideEC>' . (int)$data['OutsideEC'] . '</OutsideEC>';
		$xml .= '<Notes>' . (string)$data['Notes'] . '</Notes>';
		$xml .= '<Source>' . (int)$data['Source'] . '</Source>';
		$xml .= '<Discount>' . (float)$data['Discount'] . '</Discount>';
		$xml .= '<ShowDiscount>' . $data['ShowDiscount'] . '</ShowDiscount>';
		$xml .= '<PaymentTerms>' . (int)$data['PaymentTerms'] . '</PaymentTerms>';
		$xml .= '<ExtraText1>' . (string)$data['ExtraText1'] . '</ExtraText1>';
		$xml .= '<ExtraText2>' . (string)$data['ExtraText2'] . '</ExtraText2>';
		$xml .= '<Checkbox1>' . (int)$data['Checkbox1'] . '</Checkbox1>';
		$xml .= '<Checkbox2>' . (int)$data['Checkbox2'] . '</Checkbox2>';
		$xml .= '<Created>' . (string)$data['Created'] . '</Created>';
		$xml .= '<Updated>' . (string)$data['Updated'] . '</Updated>';
		$xml .= '<CurrencyID>' . (int)$data['CurrencyID'] . '</CurrencyID>';
		$xml .= '</custr>';

		$response = $this->SendRequest($xml, 'InsertCustomer');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->InsertCustomerResponse->Status == 'OK') {
				return (int)$response->soapBody->InsertCustomerResponse->InsertCustomerResult;
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->InsertCustomerResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function UpdateCustomer($data)
	{
		if ($data['ShowDiscount'] == true) {
			$data['ShowDiscount'] = 'true';
		} else {
			$data['ShowDiscount'] = 'false';
		}

		$xml = '<custr>';
		$xml .= '<CustomerID>' . (int)$data['CustomerID'] . '</CustomerID>';
		$xml .= '<Code>' . (string)$data['Code'] . '</Code>';
		$xml .= '<Name>' . (string)$data['Name'] . '</Name>';
		$xml .= '<Contact>' . (string)$data['Contact'] . '</Contact>';
		$xml .= '<Telephone>' . (string)$data['Telephone'] . '</Telephone>';
		$xml .= '<Mobile>' . (string)$data['Mobile'] . '</Mobile>';
		$xml .= '<Fax>' . (string)$data['Fax'] . '</Fax>';
		$xml .= '<Email>' . (string)$data['Email'] . '</Email>';
		$xml .= '<Address1>' . (string)$data['Address1'] . '</Address1>';
		$xml .= '<Address2>' . (string)$data['Address2'] . '</Address2>';
		$xml .= '<Address3>' . (string)$data['Address3'] . '</Address3>';
		$xml .= '<Address4>' . (string)$data['Address4'] . '</Address4>';
		$xml .= '<Postcode>' . (string)$data['Postcode'] . '</Postcode>';
		$xml .= '<Website>' . (string)$data['Website'] . '</Website>';
		$xml .= '<EC>' . (int)$data['EC'] . '</EC>';
		$xml .= '<OutsideEC>' . (int)$data['OutsideEC'] . '</OutsideEC>';
		$xml .= '<Notes>' . (string)$data['Notes'] . '</Notes>';
		$xml .= '<Source>' . (int)$data['Source'] . '</Source>';
		$xml .= '<Discount>' . (float)$data['Discount'] . '</Discount>';
		$xml .= '<ShowDiscount>' . $data['ShowDiscount'] . '</ShowDiscount>';
		$xml .= '<PaymentTerms>' . (int)$data['PaymentTerms'] . '</PaymentTerms>';
		$xml .= '<ExtraText1>' . (string)$data['ExtraText1'] . '</ExtraText1>';
		$xml .= '<ExtraText2>' . (string)$data['ExtraText2'] . '</ExtraText2>';
		$xml .= '<Checkbox1>' . (int)$data['Checkbox1'] . '</Checkbox1>';
		$xml .= '<Checkbox2>' . (int)$data['Checkbox2'] . '</Checkbox2>';
		$xml .= '<Created>' . (string)$data['Created'] . '</Created>';
		$xml .= '<Updated>' . (string)$data['Updated'] . '</Updated>';
		$xml .= '<CurrencyID>' . (int)$data['CurrencyID'] . '</CurrencyID>';
		$xml .= '</custr>';

		$response = $this->SendRequest($xml, 'UpdateCustomer');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->UpdateCustomerResponse->Status == 'OK') {
				return (string)$response->soapBody->UpdateCustomerResponse->UpdateCustomerResult;
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->UpdateCustomerResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function DeleteCustomer($data)
	{
		// Returns 0 regardless of customer deleted or not

		$xml = '<CustomerID>' . $data['CustomerID'] . '</CustomerID>';

		$response = $this->SendRequest($xml, 'DeleteCustomer');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->DeleteCustomerResponse->Status == 'OK') {
				if ($response->soapBody->DeleteCustomerResponse->DeleteCustomerResult == '1') {
					return true;
				} else {
					return false;
				}
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->DeleteCustomerResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetCustomerSources()
	{
		$xml = '';

		$response = $this->SendRequest($xml, 'GetCustomerSources');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetCustomerSourcesResponse->Status == 'OK') {
				$array = (array)$response->soapBody->GetCustomerSourcesResponse->GetCustomerSourcesResult;

				return $this->clean_dataset($this->object_to_array($array['BasicDataset']));
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomerSourcesResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetCustomerVATNumber($data)
	{
		$xml = '<CustomerCode>' . $data['CustomerCode'] . '</CustomerCode>';

		$response = $this->SendRequest($xml, 'GetCustomerVATNumber');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetCustomerVATNumberResponse->Status == 'OK') {
				return $this->clean_dataset(
					(array)$response->soapBody->GetCustomerVATNumberResponse->GetCustomerVATNumberResult
				);
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomerVATNumberResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function SetCustomerVATNumber($data)
	{
		$xml = '<CustVATNumber>' . $data['CustVATNumber'] . '</CustVATNumber>';
		$xml .= '<CustomerCode>' . $data['CustomerCode'] . '</CustomerCode>';

		$response = $this->SendRequest($xml, 'SetCustomerVATNumber');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->SetCustomerVATNumberResponse->Status == 'OK') {
				return true;
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->SetCustomerVATNumberResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetCustomerCurrency($data)
	{
		$xml = '<CustomerCode>' . $data['CustomerCode'] . '</CustomerCode>';

		$response = $this->SendRequest($xml, 'GetCustomerCurrency');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetCustomerCurrencyResponse->Status == 'OK') {
				return $this->clean_dataset(
					(array)$response->soapBody->GetCustomerCurrencyResponse->GetCustomerCurrencyResult
				);
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomerCurrencyResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function SetCustomerCurrency($data)
	{
		$xml = '<CurrencyCode>' . $data['CurrencyCode'] . '</CurrencyCode>';
		$xml .= '<CustomerCode>' . $data['CustomerCode'] . '</CustomerCode>';

		$response = $this->SendRequest($xml, 'SetCustomerCurrency');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->SetCustomerCurrencyResponse->Status == 'OK') {
				return true;
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->SetCustomerCurrencyResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	///////////////////////////////////////////////////
	// QUOTES
	///////////////////////////////////////////////////


	public function GetQuotes()
	{
		$response = $this->SendRequest('', 'GetQuotes');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			$array = (array)$response->soapBody->GetQuotesResponse->GetQuotesResult;
			if (is_array($array) && !empty($array)) {
				return $this->clean_dataset($this->object_to_array($array['Invoice']));
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetQuotesResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function DeleteQuote($data)
	{
		// Returns 0 regardless of quote deleted or not

		$xml = '<QuoteNumber>' . $data['InvoiceNumber'] . '</QuoteNumber>';

		$response = $this->SendRequest($xml, 'DeleteQuote');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->DeleteQuoteResponse->Status == 'OK') {
				if ($response->soapBody->DeleteQuoteResponse->DeleteQuoteResult == '1') {
					return true;
				} else {
					return false;
				}
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->DeleteQuoteResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	///////////////////////////////////////////////////
	// INVOICES
	///////////////////////////////////////////////////

	public function DeleteInvoice($data)
	{
		$xml = '<InvoiceNumber>' . (int)$data['InvoiceNumber'] . '</InvoiceNumber>';

		$response = $this->SendRequest($xml, 'DeleteInvoice');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->DeleteInvoiceResponse->Status == 'OK') {
				return true;
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->DeleteInvoiceResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	///////////////////////////////////////////////////
	// INVOICE PAYMENTS
	///////////////////////////////////////////////////

	public function GetInvoicePayment($data)
	{
		$xml = '<InvoiceNumber>' . (string)$data['InvoiceNumber'] . '</InvoiceNumber>';

		$response = $this->SendRequest($xml, 'GetInvoicePayment');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetInvoicePaymentResponse->Status == 'OK') {
				$array = (array)$response->soapBody->GetInvoicePaymentResponse->GetInvoicePaymentResult;

				if (empty($array)) {
					return array();
				} else {
					return $this->clean_dataset($this->object_to_array($array['Payment']));
				}
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetInvoicePaymentResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetInvPayMethods()
	{
		$xml = '';

		$response = $this->SendRequest($xml, 'GetInvPayMethods');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetInvPayMethodsResponse->Status == 'OK') {
				$array = (array)$response->soapBody->GetInvPayMethodsResponse->GetInvPayMethodsResult;

				return $this->clean_dataset($this->object_to_array($array['PaymentMethod']));
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetInvPayMethodsResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function InsertInvoicePayment($data)
	{
		$xml = '<InvoicePayment>';
		$xml .= '<PayID>' . (int)$data['InvoicePayment']['PayID'] . '</PayID>';
		$xml .= '<PayInvoice>' . (int)$data['InvoicePayment']['PayInvoice'] . '</PayInvoice>';
		$xml .= '<PayDate>' . (string)$data['InvoicePayment']['PayDate'] . '</PayDate>';
		$xml .= '<PayNote>' . (string)$data['InvoicePayment']['PayNote'] . '</PayNote>';
		$xml .= '<PayMethod>' . (int)$data['InvoicePayment']['PayMethod'] . '</PayMethod>';
		$xml .= '<PayAccount>' . (int)$data['InvoicePayment']['PayAccount'] . '</PayAccount>';
		$xml .= '<PayAmount>' . (float)$data['InvoicePayment']['PayAmount'] . '</PayAmount>';
		$xml .= '</InvoicePayment>';

		$response = $this->SendRequest($xml, 'InsertInvoicePayment');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->InsertInvoicePaymentResponse->Status == 'OK') {
				return $this->clean_dataset(
					$this->object_to_array(
						$response->soapBody->InsertInvoicePaymentResponse->InsertInvoicePaymentResult
					)
				);
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->InsertInvoicePaymentResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function DeleteInvoicePayment($data)
	{
		$xml = '<InvoicePaymentNumber>' . (int)$data['PayID'] . '</InvoicePaymentNumber>';

		$response = $this->SendRequest($xml, 'DeleteInvoicePayment');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->DeleteInvoicePaymentResponse->Status == 'OK') {
				return true;
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->DeleteInvoicePaymentResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function getInvoicesByDateRange($data)
	{
		$xml = '<StartDate>' . $data['StartDate'] . '</StartDate>';
		$xml .= '<EndDate>' . $data['EndDate'] . '</EndDate>';

		$response = $this->SendRequest($xml, 'GetInvoicesByDateRange');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if (is_object($response->soapBody->GetInvoicesByDateRangeResponse)) {
				return $this->object_to_array(
					$response->soapBody->GetInvoicesByDateRangeResponse->GetInvoicesByDateRangeResult
				);
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetCustomerResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	///////////////////////////////////////////////////
	// SUPPLIERS
	///////////////////////////////////////////////////

	public function GetSuppliers()
	{
		$response = $this->SendRequest('', 'GetSuppliers');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetSuppliersResponse->Status == 'OK') {
				$array = (array)$response->soapBody->GetSuppliersResponse->GetSuppliersResult;

				return $this->clean_dataset($this->object_to_array($array['Supplier']));
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetSuppliersResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	///////////////////////////////////////////////////
	// PURCHASES
	///////////////////////////////////////////////////

	///////////////////////////////////////////////////
	// PURCHASE PAYMENTS
	///////////////////////////////////////////////////

	///////////////////////////////////////////////////
	// BANK
	///////////////////////////////////////////////////

	public function GetBankAccounts()
	{
		$xml = '';

		$response = $this->SendRequest($xml, 'GetBankAccounts');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetBankAccountsResponse->Status == 'OK') {
				$array = (array)$response->soapBody->GetBankAccountsResponse->GetBankAccountsResult;

				return $this->clean_dataset($this->object_to_array($array['BankAccount']));
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetBankAccountsResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetBankBalance($data)
	{
		$xml = '<AccountID>' . $data['AccountID'] . '</AccountID>';
		$xml .= '<BalanceDate>' . $data['BalanceDate'] . '</BalanceDate>';

		$response = $this->SendRequest($xml, 'GetBankBalance');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetBankBalanceResponse->Status == 'OK') {
				return $this->clean_dataset((array)$response->soapBody->GetBankBalanceResponse->GetBankBalanceResult);
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetBankBalanceResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetBankTxTypes()
	{
		$xml = '';

		$response = $this->SendRequest($xml, 'GetBankTxTypes');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetBankTxTypesResponse->Status == 'OK') {
				$array = (array)$response->soapBody->GetBankTxTypesResponse->GetBankTxTypesResult;

				return $this->clean_dataset($this->object_to_array($array['BankTXType']));
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetBankTxTypes->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	///////////////////////////////////////////////////
	// JOURNALS
	///////////////////////////////////////////////////

	///////////////////////////////////////////////////
	// REPORTS
	///////////////////////////////////////////////////

	public function GetAgedDebtors($data)
	{
		$xml = ' <AgedDebtorsDate>' . (string)$data['AgedDebtorsDate'] . '</AgedDebtorsDate>';

		$response = $this->SendRequest($xml, 'GetAgedDebtors');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetAgedDebtorsResponse->Status == 'OK') {
				$array = (array)$response->soapBody->GetAgedDebtorsResponse->GetAgedDebtorsResult;

				return $this->clean_dataset($this->object_to_array($array['AgedDebtorsCreditors']));
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetAgedDebtorsResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetBalanceSheet($data)
	{
		$xml = ' <Date>' . (string)$data['Date'] . '</Date>';

		$response = $this->SendRequest($xml, 'GetBalanceSheet');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetBalanceSheetResponse->Status == 'OK') {
				return $this->object_to_array($response->soapBody->GetBalanceSheetResponse->GetBalanceSheetResult);
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetBalanceSheetResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetDigitaCSVFile($data)
	{
		$xml = ' <StartDate>' . (string)$data['StartDate'] . '</StartDate>';
		$xml .= ' <EndDate>' . (string)$data['EndDate'] . '</EndDate>';

		$response = $this->SendRequest($xml, 'GetDigitaCSVFile');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetDigitaCSVFileResponse->Status == 'OK') {
				return $this->clean_dataset(
					(array)$response->soapBody->GetDigitaCSVFileResponse->GetDigitaCSVFileResult
				);
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetDigitaCSVFileResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetIncomeByCustomer($data)
	{
		$xml = ' <StartDate>' . (string)$data['StartDate'] . '</StartDate>';
		$xml .= ' <EndDate>' . (string)$data['EndDate'] . '</EndDate>';
		$xml .= ' <BasedOnInvoiceDate>' . (string)$data['BasedOnInvoiceDate'] . '</BasedOnInvoiceDate>';

		$response = $this->SendRequest($xml, 'GetIncomeByCustomer');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetIncomeByCustomerResponse->Status == 'OK') {
				$array = (array)$response->soapBody->GetIncomeByCustomerResponse->GetIncomeByCustomerResult;

				return $this->clean_dataset($this->object_to_array($array['BasicDataset']));
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetIncomeByCustomerResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetKPIs($data)
	{
		$xml = ' <AgedDebtorsDate>' . (string)$data['AgedDebtorsDate'] . '</AgedDebtorsDate>';

		$response = $this->SendRequest($xml, 'GetKPIs');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetKPIsResponse->Status == 'OK') {
				$array = (array)$response->soapBody->GetKPIsResponse->GetKPIsResult;

				return $this->clean_dataset($this->object_to_array($array['BasicDataset']));
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetKPIsResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetMonthlyProfitAndLoss($data)
	{
		$xml = ' <StartDate>' . (string)$data['StartDate'] . '</StartDate>';
		$xml .= ' <EndDate>' . (string)$data['EndDate'] . '</EndDate>';

		$response = $this->SendRequest($xml, 'GetMonthlyProfitAndLoss');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetMonthlyProfitAndLossResponse->Status == 'OK') {
				$array = (array)$response->soapBody->GetMonthlyProfitAndLossResponse->GetMonthlyProfitAndLossResult;

				return $this->clean_dataset($this->object_to_array($array['MonthlyPL']));
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetMonthlyProfitAndLossResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetNominalLedger($data)
	{
		$xml = ' <StartDate>' . (string)$data['StartDate'] . '</StartDate>';
		$xml .= ' <EndDate>' . (string)$data['EndDate'] . '</EndDate>';
		$xml .= ' <NominalID>' . (int)$data['NominalID'] . '</NominalID>';

		$response = $this->SendRequest($xml, 'GetNominalLedger');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetNominalLedgerResponse->Status == 'OK') {
				$array = (array)$response->soapBody->GetNominalLedgerResponse->GetNominalLedgerResult;

				return $this->clean_dataset($this->object_to_array($array['TransactionInformation']));
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetNominalLedgerResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetProfitAndLoss($data)
	{
		$xml = ' <StartDate>' . (string)$data['StartDate'] . '</StartDate>';
		$xml .= ' <EndDate>' . (string)$data['EndDate'] . '</EndDate>';

		$response = $this->SendRequest($xml, 'GetProfitAndLoss');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetProfitAndLossResponse->Status == 'OK') {
				return $this->object_to_array($response->soapBody->GetProfitAndLossResponse->GetProfitAndLossResult);
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetProfitAndLossResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetTrialBalance($data)
	{
		$xml = ' <StartDate>' . (string)$data['StartDate'] . '</StartDate>';
		$xml .= ' <EndDate>' . (string)$data['EndDate'] . '</EndDate>';

		$response = $this->SendRequest($xml, 'GetTrialBalance');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetTrialBalanceResponse->Status == 'OK') {
				$array = (array)$response->soapBody->GetTrialBalanceResponse->GetTrialBalanceResult;

				return $this->clean_dataset($this->object_to_array($array['NominalCode']));
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetTrialBalanceResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetVATReport($data)
	{
		$xml = ' <StartDate>' . (string)$data['StartDate'] . '</StartDate>';
		$xml .= ' <EndDate>' . (string)$data['EndDate'] . '</EndDate>';

		$response = $this->SendRequest($xml, 'GetVATReport');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetVATReportResponse->Status == 'OK') {
				return $this->object_to_array($response->soapBody->GetVATReportResponse->GetVATReportResult);
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetVATReportResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	///////////////////////////////////////////////////
	// SUPPLEMENTARY FUNCTIONS
	///////////////////////////////////////////////////

	public function GetAccountOverview()
	{
		$xml = '';

		$response = $this->SendRequest($xml, 'GetAccountOverview');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetAccountOverviewResponse->Status == 'OK') {
				return $this->object_to_array(
					$response->soapBody->GetAccountOverviewResponse->GetAccountOverviewResult
				);
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetAccountOverviewResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetRemoteLoginURL()
	{
		$xml = '';

		$response = $this->SendRequest($xml, 'GetRemoteLoginURL');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetRemoteLoginURLResponse->Status == 'OK') {
				return $this->object_to_array($response->soapBody->GetRemoteLoginURLResponse->GetRemoteLoginURLResult);
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetRemoteLoginURLResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetProducts()
	{
		$xml = '';

		$response = $this->SendRequest($xml, 'GetProducts');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetProductsResponse->Status == 'OK') {
				$array = (array)$response->soapBody->GetProductsResponse->GetProductsResult;

				return $this->clean_dataset($this->object_to_array($array['Product']));
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetProductsResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

	public function GetSubProducts($data)
	{
		// Output needs verification

		$xml = '<NominalID>' . $data['NominalID'] . '</NominalID>';

		$response = $this->SendRequest($xml, 'GetSubProducts');

		if (isset($response->soapBody->soapFault)) {
			$this->error_msg['ErrorMsg'] = (string)$response->soapBody->soapFault->faultstring;

			return $this->error_msg;
		} else {
			if ($response->soapBody->GetSubProductsResponse->Status == 'OK') {
				return $this->clean_dataset(
					$this->object_to_array($response->soapBody->GetSubProductsResponse->GetSubProductsResult)
				);
			} else {
				$this->error_msg['ErrorMsg'] = (string)$response->soapBody->GetSubProductsResponse->StatusDetail;

				return $this->error_msg;
			}
		}
	}

}

?>