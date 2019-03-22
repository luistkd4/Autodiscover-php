<?php
/********************************
 * Autodiscover responder
 ********************************
 * This PHP script is intended to respond to any request to http(s)://mydomain.com/autodiscover/autodiscover.xml.
 * If configured properly, it will send a spec-complient autodiscover XML response, pointing mail clients to the
 * appropriate mail services.
 * If you use MAPI or ActiveSync, stick with the Autodiscover service your mail server provides for you. But if 
 * you use POP/IMAP servers, this will provide autoconfiguration to Outlook, Apple Mail and mobile devices.
 *
 * To work properly, you'll need to set the service (sub)domains below in the settings section to the correct 
 * domain names, adjust ports and SSL.
 */
//get raw POST data so we can extract the email address
$request = file_get_contents("php://input");
// optional debug log
file_put_contents( 'request.log', $request, FILE_APPEND );
// retrieve email address from client request
preg_match( "/\<EMailAddress\>(.*?)\<\/EMailAddress\>/", $request, $email );
$mail = $email[1];
// retrieve response schema from client request
preg_match( "/\<AcceptableResponseSchema\>(.*?)\<\/AcceptableResponseSchema\>/", $request, $Rschema);
$Rschema = $Rschema[1];
// check for invalid mail, to prevent XSS
if (filter_var($mail, FILTER_VALIDATE_EMAIL) === false) {
	throw new Exception('Invalid E-Mail provided');
}
// get domain from email address
$domain = substr( strrchr( $mail, "@" ), 1 );
/**************************************
 *   Port and server settings below   *
 **************************************/
// POP settings
$popServer = 'pop.' . $domain; // pop.example.com
$popPort   = 110;
$popSSL    = false;
// IMAP settings
$imapServer = 'imap.' . $domain; // imap.example.com
$imapPort   = 993;
$imapSSL    = true;
// SMTP settings
$smtpServer = 'smtp.' . $domain; // smtp.example.com
$smtpPort   = 587;
$smtpSSL    = true;
//set Content-Type
header( 'Content-Type: application/xml' );
if (strpos($Rschema, 'mobilesync')){
    $xml = file_get_contents('responsemobile.xml');
    $zpushHost = "webmail977.umbler.com";
    $serverUrl = "https://" . $zpushHost . "/Microsoft-Server-ActiveSync";
    $response = new SimpleXMLElement($xml);
    $response->Response->User->DisplayName = $mail;
    $response->Response->User->EMailAddress = $mail;
    $response->Response->Action->Settings->Server->Url = $serverUrl;
    $response->Response->Action->Settings->Server->Name = $serverUrl;
    $response = $response->asXML();
    echo $response;
} elseif(strpos($Rschema, 'outlook')){
    $xml = file_get_contents('responseoutlook.xml');
    $zpushHost = "webmail977.umbler.com";
    $serverUrl = "https://" . $zpushHost . "/Microsoft-Server-ActiveSync";
    $response = new SimpleXMLElement($xml);
    $response->Response->User->DisplayName = $mail;
    $response->Response->User->EMailAddress = $mail;
    $response->Response->Action->Settings->Server->Url = $serverUrl;
    $response->Response->Action->Settings->Server->Name = $serverUrl;
    $response = $response->asXML();
    echo $response;
}
else{
    $xml = file_get_contents('responseerror.xml');
    $zpushHost = "webmail977.umbler.com";
    $serverUrl = "https://" . $zpushHost . "/Microsoft-Server-ActiveSync";
    $response = new SimpleXMLElement($xml);
    $response->Response->User->DisplayName = $mail;
    $response->Response->User->EMailAddress = $mail;
    $response->Response->Action->Settings->Server->Url = $serverUrl;
    $response->Response->Action->Settings->Server->Name = $serverUrl;
    $response = $response->asXML();
    echo $response;       
}
?>
<?php echo '<?xml version="1.0" encoding="utf-8" ?>'; ?>
<Autodiscover xmlns="http://schemas.microsoft.com/exchange/autodiscover/responseschema/2006" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" >
	<Response xmlns="http://schemas.microsoft.com/exchange/autodiscover/outlook/responseschema/2006a">
		<Account>
			<AccountType>email</AccountType>
			<Action>settings</Action>
			<Protocol>
				<Type>POP3</Type>
				<Server><?php echo $popServer; ?></Server>
				<Port><?php echo $popPort; ?></Port>
				<LoginName><?php echo $mail; ?></LoginName>
				<DomainRequired>off</DomainRequired>
				<SPA>off</SPA>
				<SSL><?php echo $popSSL ? 'on' : 'off'; ?></SSL>
				<DomainRequired>off</DomainRequired>
			</Protocol>
			<Protocol>
				<Type>IMAP</Type>
				<Server><?php echo $imapServer; ?></Server>
				<Port><?php echo $imapPort; ?></Port>
				<DomainRequired>off</DomainRequired>
				<LoginName><?php echo $mail; ?></LoginName>
				<SPA>off</SPA>
				<SSL><?php echo $imapSSL ? 'on' : 'off'; ?></SSL>
				<AuthRequired>on</AuthRequired>
			</Protocol>
			<Protocol>
				<Type>SMTP</Type>
				<Server><?php echo $smtpServer; ?></Server>
				<Port><?php echo $smtpPort; ?></Port>
				<DomainRequired>off</DomainRequired>
				<LoginName><?php echo $mail; ?></LoginName>
				<SPA>off</SPA>
				<SSL><?php echo $smtpSSL ? 'on' : 'off'; ?></SSL>
				<AuthRequired>on</AuthRequired>
				<UsePOPAuth>on</UsePOPAuth>
				<SMTPLast>on</SMTPLast>
			</Protocol>
		</Account>
	</Response>
</Autodiscover>