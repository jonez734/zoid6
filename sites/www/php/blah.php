<?php

$string =array('abcdefghijklmnopqrstuvwxyz0123456789',
'abcdefghijklmnopqrstuvwxyz0123456789',
'abcdefghijklmnopqrstuvwxyz0123456789',
'wxyz1234abcdefg',
'abcdefghijklmnopqrstuvwxyz0123456789');
$matches = preg_grep("/^abc/", $string);
foreach ($matches as $match)
{
  print $match."<br />";
}
?>


<?php
/*
  
  $urls = array('http://w3schools.com', 'http://www.w3schools.com/php/default.asp');
  $links = array();
  foreach ($urls as $nextUrl) 
  {
    $content = file_get_contents($nextUrl);
    
    preg_match_all('/<a\s+href="([^"]*)"/', $content, $anchortags);
    print "<pre>".var_export($anchortags, True)."</pre><br /><hr />";
    
    if (preg_match('/\.asp$/', $nextUrl)) 
    {
      array_push($links, $nextUrl);
    }
  }

  foreach ($links as $link) 
  {
    echo $link, '</br>';
  }
*/

/*
$regex = '/(?:1(?:[. -])?)?(?:\((?=\d{3}\)))?([2-9]\d{2})'
    .'(?:(?<=\(\d{3})\))? ?(?:(?<=\d{3})[.-])?([2-9]\d{2})'
    .'[. -]?(\d{4})(?: (?i:ext)\.? ?(\d{1,5}))?/';
  
$urls = array("http://justinbusiness.royalwebhosting.net/testfile.html");
foreach ($urls as $url)
{
  $content = file_get_contents($url);
  print "<pre>{$content}</pre><hr />";

  $res = preg_match_all($regex, $content, $matches);
  print var_export($matches, True);
  if ($res === 0)
  {
    print "no phone numbers";
  }
  else if ($res === False)
  {
    print "preg_match_all error";
  }
  else
  {
    foreach ($matches as $match)
    {
      echo "Phone Number Matched " . var_export($match, True) . " - US Format: " . var_export(preg_replace($regex, '($1) $2-$3 ext. $4', $match), True);
      echo "<br />";
    }
  }
}
*/
?>
