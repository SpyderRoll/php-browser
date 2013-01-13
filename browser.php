<?php
/*
 ******************************************************************************************************************
 *  Author:           Nam Tran, Grey Hat Apps
 *  Email Address:    nam@greyhatapps
 *  Date Created:     11/19/2010
 *
 ******************************************************************************************************************
 *  Class: Browser
 *
 *  Extracts HTML of a given url.
 *
 ******************************************************************************************************************
 */
  class Browser
  {
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +  get
  // +
  // +  Extracts the HTML content of a given URL
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    static function get($pUrl, $pCookies=false, $pCookieSuffix="")
    {
      $ch = curl_init($pUrl);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
      curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/A.B (KHTML, like Gecko) Chrome/X.Y.Z.W Safari/A.B.");

      $cookieFile = "cookie" . $pCookieSuffix . ".txt";

      if($pCookies)
      {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
      }

      $html = curl_exec($ch);
      $html = trim($html);

      curl_close($ch);

      if($html == "") { return false; }

      return $html;
    }

  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +  getLinks
  // +
  // +  Returns an array of all links on a given page provided the URL
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    static function getLinks($pUrl)
    {
      $html = self::get($pUrl);
      if(preg_match_all('/<a\s+href=["\']([^"\']+)["\']/i', $html, $links, PREG_PATTERN_ORDER))
      {
        return array_unique($links[1]);
      }

      return array();
    }

  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +  getLinksHTML
  // +
  // +  Returns an array of all links on a given page provided the HTML content
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    static function getLinksHTML($pHTML)
    {
      if(preg_match_all('/<a\s+href=["\']([^"\']+)["\']/i', $pHTML, $links, PREG_PATTERN_ORDER))
      {
        return array_unique($links[1]);
      }

      return array();
    }

  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +  stripHTML
  // +
  // +  Strip HTML content and return the content between the two given tags
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    static function stripHTML($pHTML, $pTagStart, $pTagEnd="", $pOffset=0, &$pNextOffset=null, &$pTagEndFound=false)
    {
      $pTagStartPos = strpos($pHTML, $pTagStart, $pOffset) + strlen($pTagStart);
      // If start tag not found, return entire HTML
      if($pTagStartPos == strlen($pTagStart)) { return $pHTML; }

      if($pTagEnd != "")
      {
        $pTagEndPos = strpos($pHTML, $pTagEnd, $pTagStartPos) + strlen($pTagEnd);

        // If the end tag was found
        if($pTagEndPos > strlen($pTagEnd))
          $pTagEndFound = true;
        else
          $pTagEndFound = false;

        $pNextOffset = $pTagEndPos;
      }
      else
      {
        $pTagEndPos = strlen($pHTML);
        $pTagEndFound = false;
        $pNextOffset = $pTagEndPos;
      }

      if(($pTagStartPos == "") || ($pTagEndPos <= strlen($pTagEndPos)))
      {
        // Don't do anything, just return as-is
        $pNextOffset = 0;
        return $pHTML;
      }

      if($pTagEnd != "")
      {
        $pHTML = substr($pHTML, $pTagStartPos, ($pTagEndPos - strlen($pTagEnd)) - $pTagStartPos);
      }
      else
      {
        $pHTML = substr($pHTML, $pTagStartPos, strlen($pHTML) - $pTagStartPos);
      }

      return trim($pHTML);
    }

  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +  formatPrice
  // +
  // +  Formats a string to a price value
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    static function formatPrice($pHTML)
    {
      $pHTML = str_replace("$", "", $pHTML);
      $pHTML = trim($pHTML);
      if(!is_numeric($pHTML))
      {
        if(strtoupper($pHTML) == "FREE")
          $pHTML = "0.00";
        else
      	  $pHTML = "";
      }
      else
      {
      	$pHTML = number_format($pHTML, 2);
      }

      return $pHTML;
    }

  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +  isValidCallback
  // +
  // +  Check to see if the callback is valid to prevent exploits
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    function isValidCallback($pCallback)
    {
      $identifier_syntax = '/^[$_\p{L}][$_\p{L}\p{Mn}\p{Mc}\p{Nd}\p{Pc}\x{200C}\x{200D}]*+$/u';

      $reserved_words = array("break", "do", "instanceof", "typeof", "case",
        "else", "new", "var", "catch", "finally", "return", "void", "continue",
        "for", "switch", "while", "debugger", "function", "this", "with",
        "default", "if", "throw", "delete", "in", "try", "class", "enum",
        "extends", "super", "const", "export", "import", "implements", "let",
        "private", "public", "yield", "interface", "package", "protected",
        "static", "null", "true", "false");

      return preg_match($identifier_syntax, $pCallback) && ! in_array(mb_strtolower($pCallback, "UTF-8"), $reserved_words);
    }

  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
  // +  formatHTMLText
  // +
  // +  Formats a given HTML text string to text. Removes HTML tags, trims the text,
  // +  and if the HTML text is > max characters, set value to blank
  // ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
    static function formatHTMLText($pHTML, $pMaxCharacters=200)
    {
      $pHTML = strip_tags($pHTML);
      $pHTML = trim($pHTML);

      if(strlen($pHTML) > $pMaxCharacters)
      {
        $pHTML = "";
      }

      return $pHTML;
    }

    static function formatTitle($pHTML, $pMaxCharacters=200)
    {
      return self::formatHTMLText($pHTML, $pMaxCharacters);
    }

    static function formatName($pHTML, $pMaxCharacters=200)
    {
      $name = self::formatHTMLText($pHTML, $pMaxCharacters);
      $array_name = explode(";", $name);
      $name = $array_name[0];

      return $name;
    }

    static function formatAvailability($pHTML, $pMaxCharacters=200)
    {
      $str = self::formatHTMLText($pHTML, $pMaxCharacters);
      $str = trim($str, ".");

      return $str;
    }

    static function formatImageUrl($pHTML, $pMaxCharacters=200)
    {
      return self::formatHTMLText($pHTML, $pMaxCharacters);
    }

    static function formatUPC($pHTML, $pMaxCharacters=200)
    {
      return self::formatHTMLText($pHTML, $pMaxCharacters);
    }

    static function formatEAN($pHTML, $pMaxCharacters=200)
    {
      return self::formatHTMLText($pHTML, $pMaxCharacters);
    }

    static function formatPlatform($pHTML, $pMaxCharacters=200)
    {
      return self::formatHTMLText($pHTML, $pMaxCharacters);
    }

    static function formatProductID($pHTML, $pMaxCharacters=100)
    {
      return self::formatHTMLText($pHTML, $pMaxCharacters);
    }

    static function formatUrl($pHTML, $pMaxCharacters=200)
    {
      $str = self::formatHTMLText($pHTML, $pMaxCharacters);
      $str = str_replace("\\", "", $str);

      return $str;
    }


  }
?>
