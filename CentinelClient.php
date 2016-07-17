<?php 

/////////////////////////////////////////////////////////////////////////////////////////////
//  CardinalCommerce (http://www.cardinalcommerce.com)
//  CentinelClient.php - Openssl Version
//  Version 1.2 02/17/2005
//
//	Usage
//		The CentinelClient class is defined to assist integration efforts with the Centinel
//		XML message integration. The class implements helper methods to construct, send, and
//		receive XML messages with respect to the Centinel XML Message APIs.
//
/////////////////////////////////////////////////////////////////////////////////////////////

    require("XMLParser.php");
	require("OpenSSLHttp.php");
	include "CentinelErrors.php";

    class CentinelClient {
   
		var $request ;
		var $response ;  
		var $parser;
    
		/////////////////////////////////////////////////////////////////////////////////////////////
		// Function Add(name, value)
		//
		// Add name/value pairs to the Centinel request collection. 
		/////////////////////////////////////////////////////////////////////////////////////////////

		
		function add($name, $value) {
			 $this->request[$name] = $this->escapeXML($value);
		}

		/////////////////////////////////////////////////////////////////////////////////////////////
		// Function getValue(name)
		//
		// Retrieve a specific value for the give name within the Centinel response collection. 
		/////////////////////////////////////////////////////////////////////////////////////////////


		function getValue($name) {
			return $this->response[$name];
		}
	   

		/////////////////////////////////////////////////////////////////////////////////////////////
		// Function getRequestXml(name)
		//
		// Serialize all elements of the request collection into a XML message, and format the required
		// form payload according to the Centinel XML Message APIs. The form payload is returned from  
		// the function.
		/////////////////////////////////////////////////////////////////////////////////////////////

		
		function getRequestXml($url, $timeout){
			$queryString = "<CardinalMPI>";
			foreach ($this->request as $name => $value) {
				$queryString = $queryString."<".($name).">".($value)."</".($name).">" ;
			}


            // Add custom fields
            $queryString .= "<Source>" . $this->escapeXML("PHPTC") . "</Source>";
            $queryString .= "<SourceVersion>" . $this->escapeXML("2.5") . "</SourceVersion>";

            $queryString .= "<ResolveTimeout>" . $this->escapeXML($timeout) . "</ResolveTimeout>";
            $queryString .= "<SendTimeout>" . $this->escapeXML($timeout) . "</SendTimeout>";
            $queryString .= "<ReceiveTimeout>" . $this->escapeXML($timeout) . "</ReceiveTimeout>";
            $queryString .= "<ConnectTimeout>" . $this->escapeXML($timeout) . "</ConnectTimeout>";
            $queryString .= "<TransactionUrl>" . $this->escapeXML($url) . "</TransactionUrl>";
            $queryString .= "<MerchantSystemDate>" . $this->escapeXML( gmdate('Y-m-d\TH:i:s\Z') ) . "</MerchantSystemDate>";


			$queryString = $queryString."</CardinalMPI>";
			return "cmpi_msg=".urlencode($queryString);
		}
	   
	    /////////////////////////////////////////////////////////////////////////////////////////////
		// Function sendHttp(url)
		//
		// HTTP POST the form payload to the url using cURL.
		// form payload according to the Centinel XML Message APIs. The form payload is returned from  
		// the function.
		/////////////////////////////////////////////////////////////////////////////////////////////

		function sendHttp($url, $connectionTimeout, $readTimeout) {
		   
		    // verify that the URL uses a supported protocol.

			if( (strpos($url, "http://")=== 0) || (strpos($url, "https://")=== 0) ) {
					 
				//Construct the payload to POST to the url.

				$data = $this->getRequestXml($url, $readTimeout);
			
				// create a new Http resource
                $http = new OpenSSLHttp();
               	$result = $http->fetch($url, $data, $connectionTimeout, $readTimeout);
    

				// Assert that we received an expected Centinel Message in reponse.
				if (strpos($result, "<CardinalMPI>") === false) {
					$result = $this->setErrorResponse(CENTINEL_ERROR_CODE_8010, CENTINEL_ERROR_CODE_8010_DESC);
				}
								
			} else {
				$result = $this->setErrorResponse(CENTINEL_ERROR_CODE_8000, CENTINEL_ERROR_CODE_8000_DESC);
			}
			
			$parser = new XMLParser;
			$parser->deserializeXml($result);
			
			$this->response = $parser->deserializedResponse;
			
			
			
		}
		
		/////////////////////////////////////////////////////////////////////////////////////////////
		// Function setErrorResponse(errorNo, errorDesc)
		//
		// Initialize an Error response to ensure that parsing will be handled properly.
		/////////////////////////////////////////////////////////////////////////////////////////////

		function setErrorResponse($errorNo, $errorDesc) {
		
		  $resultText  = "<CardinalMPI>";
		  $resultText = $resultText."<ErrorNo>".($errorNo)."</ErrorNo>" ;
		  $resultText = $resultText."<ErrorDesc>".($errorDesc)."</ErrorDesc>" ;
		  $resultText  = $resultText."</CardinalMPI>";
		  
		  return $resultText;
		}
		
		/////////////////////////////////////////////////////////////////////////////////////////////
		// Function escapeXML(value)
		//
		// Escaped string converting all '&' to '&amp;' and all '<' to '&lt'. Return the escaped value.
		/////////////////////////////////////////////////////////////////////////////////////////////

		function escapeXML($elementValue){
		
			$escapedValue = str_replace("&", "&amp;", $elementValue);
			$escapedValue = str_replace("<", "&lt;", $escapedValue);
			
			return $escapedValue;
		}
	}
?>
