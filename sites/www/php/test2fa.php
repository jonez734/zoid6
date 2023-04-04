<?php

require_once("config.php");
require_once("zoidweb2.php");
require_once("bbsengine4.php");

require_once("GoogleAuthenticator.php");

$secret = "ZOIDWEB2";

$g = new GoogleAuthenticator();


$submitted = isset($_REQUEST["submit"]) ? True : False;

if ($submitted === False)
{
  print "secret: ".var_export($secret, True)."<br />";
  print "code: ".var_export($g->getCode($secret), True)."<br />";
  print "The QR Code for this secret (to scan with the Google Authenticator App):<br />";
  $url = $g->getURL("chregu", "example.org", $secret);
  print "<img src='{$url}' alt='{$url}' />";
  print "<br />";
  print "<form>";
  print "<input type='text' name='code' />";
  print "<input type='submit' name='submit' value='submit code' />";
  print "</form>";
  exit;
}

print "secret=".var_export($secret, True);
$code = isset($_REQUEST["code"]) ? $_REQUEST["code"] : null;
var_export($code);
if ($g->checkCode($secret, $code) === True)
{
  print "PASS";
}
else
{
  print "FAIL";
}
exit;

/*
$ga = new PHPGangsta_GoogleAuthenticator();
$secret = $ga->createSecret();
echo "Secret is: ".$secret."\n\n";
$qrCodeUrl = $ga->getQRCodeGoogleUrl('Blog', $secret);
//echo "Google Charts URL for the QR-Code: ".$qrCodeUrl."\n\n";
echo "QRCode: <img src='{$qrCodeUrl}' />";
$oneCode = $ga->getCode($secret);
echo "Checking Code '$oneCode' and Secret '$secret':\n";
$checkResult = $ga->verifyCode($secret, $oneCode, 2);    // 2 = 2*30sec clock tolerance
if ($checkResult) {
    echo 'OK';
} else {
    echo 'FAILED';
}
*/

/*
$g = new GoogleAuthenticator();

// $secret = "XVQ2UIGO75XRUKJO";
$secret = $g->generateSecret();

if (isset($_REQUEST["submit"]) === True)
{
  $code = isset($_REQUEST["code"]) ? $_REQUEST["code"] : null;
  var_export($code);
  if ($g->checkCode($secret, $code) === True)
  {
    print "PASS";
  }
  else
  {
    print "FAIL";
  }
  exit;
}

$time = floor(time() / 30);
$code = $g->getcode($secret);
print "Current Code is: ";
print $g->getCode($secret);
print "\n";
print "Check if $code is valid: ";
if ($g->checkCode($secret,$code)) 
{
  print "YES \n";   
} 
else 
{
  print "NO \n";
}
*/

?>
