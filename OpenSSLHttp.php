<?php

/////////////////////////////////////////////////////////////////////////////////////////////
//  CardinalCommerce (http://www.cardinalcommerce.com)
//  OpenSSLHttp.php - Openssl Version
//  Version 1.2 02/17/2005
//
//	Usage
//		Http implementation using PHP openssl.
//
//	Notes 
//
//		IIS uses non standard SSL which will cause a warning message to be generated. To avoid 
//		this problem ignore all warnings edit the php.ini {error_reporting  = E_ERROR;} and/or
//		(display_errors = Off).
//
/////////////////////////////////////////////////////////////////////////////////////////////
	include "CentinelErrors.php";
    class OpenSSLHttp {

		function fetch( $url, $postdata = "", $connectTimeout, $readTimeout) { 
            $socketProtocol = "ssl://";
            $domain="";
            $port=80;
            $usepath="/";

			$output = ""; 
            
            #parse url
            
            # Get Protocol
            $protocolPos = strpos($url, "://");
            $protocol = substr($url, 0, $protocolPos);
            if($protocol == "https") {
                $socketProtocol = "ssl://";
                $port = 443;    #default
            }
            else if($protocol == "http") {
                $socketProtocol = "";
                $port = 80;     # default
            }
            
    
            # Get optional port and usepath position
            $portPos = strpos($url, ":", $protocolPos + 3);
            $usepathPos = strpos($url, "/", $protocolPos + 3);
            
            #Get domain, port, usepath
            if($portPos == 0 && $usepathPos == 0) {
                $domain = substr($url, $protocolPos + 3);
            }
            else if($portPos != 0 && $usepathPos == 0)  {
                $domain = substr($url, $protocolPos + 3, $portPos - ($protocolPos + 3));
                $port = (int)substr($url, $portPos + 1);
            }
            else if($portPos != 0 && $usepathPos != 0)  { 
                $domain = substr($url, $protocolPos + 3, $portPos - ($protocolPos + 3));
                $port = (int)substr($url, $portPos + 1, $usepathPos - ($portPos + 1));
                $usepath = substr($url, $usepathPos);
            }
            else { # $portPos == 0 && $usepathPos != 0
                $domain = substr($url, $protocolPos + 3, $usepathPos - ($protocolPos + 3));
                $usepath = substr($url, $usepathPos);
            }
            
            # open socket to filehandle 
         
			if( !$fp = @fsockopen( $socketProtocol.$domain, $port, $errno, $errstr, $connectTimeout )) { 

				// Unable to connect to URL
				$output =  $this->setErrorResponse(CENTINEL_ERROR_CODE_8030, CENTINEL_ERROR_CODE_8030_DESC);
          
			} else { 

                $strlength = strlen( $postdata); 
 
                fputs( $fp, "POST $usepath HTTP/1.0\n"); 
                fputs( $fp, "Host:".$domain."\n");
                fputs( $fp, "User-Agent: Centinel Thin Client OpenSSL\n"); 
                fputs( $fp, "Accept: */*\n"); 
                fputs( $fp, "Content-type: application/x-www-form-urlencoded\n"); 
                fputs( $fp, "Content-length: ".$strlength."\n\n"); 
                fputs( $fp, $postdata."\n"); 
                fputs( $fp, "\n"); 
				  
				 $data = null; 

				 // Set the Read Timeout Value on the connection

				 stream_set_timeout($fp, $readTimeout);    
				 $status = socket_get_status($fp); 
				 while( !feof($fp) && !$status['timed_out'])          
				 { 
					$data .= fgets ($fp,1024); 
					$status = socket_get_status($fp); 
				 } 
				 fclose ($fp); 
				 
				 if ($status['timed_out']) {
				 	$output =  $this->setErrorResponse(CENTINEL_ERROR_CODE_8030, CENTINEL_ERROR_CODE_8030_DESC);
				 }else {
				  
				 	// strip headers to extract the XML Payload

				 	$sData = split("\r\n\r\n", $data, 2); 

				 	// The XML Payload will be position 1 of the resulting array

				 	$output = $sData[1]; 
				 }
			  } 
 
            return $output; 
        } 
		
		function setErrorResponse($errorNo, $errorDesc) {
				
			$resultText  = "<CardinalMPI>";
			$resultText = $resultText."<ErrorNo>".($errorNo)."</ErrorNo>" ;
			$resultText = $resultText."<ErrorDesc>".($errorDesc)."</ErrorDesc>" ;
			$resultText  = $resultText."</CardinalMPI>";
				  
			return $resultText;
		}
		
    }
?>