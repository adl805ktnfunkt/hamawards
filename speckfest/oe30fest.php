<?php

/*
Copyright (C) 2024 Barry de Graaff PC1K

This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program. If not, see http://www.gnu.org/licenses/.

2024-04-09 Changed by Michael Linder OE8YML in order to work for OE8SPECK Special Call
*/

// curl -d "KEY=?????????????&ACTION=FETCH" -X POST https://logbook.qrz.com/api
// find syntax errors: php -l  /var/www/html/qrz.php

// Function to convert an ADIF record into an array, by ChatGPT

//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

function adifRecordToArray($adifRecord) {
    $dataArray = array();

    // Use regular expressions to match the fields in the ADIF record
    preg_match_all("/<([^:]+):(\d+)>([^<]+)/", $adifRecord, $matches, PREG_SET_ORDER);

    // Iterate through the matches and create key-value pairs in the array
    foreach ($matches as $match) {
        $fieldName = $match[1];
        $fieldLength = intval($match[2]);
        $fieldValue = $match[3];

        $dataArray[$fieldName] = $fieldValue;
    }

    return $dataArray;
}

function shortenString($inputString, $maxLength)
{
   // Check if the input string is longer than the desired maximum length
   if (strlen($inputString) > $maxLength) {
      // Trim the string to the desired maximum length and add an ellipsis
      $shortenedString = substr($inputString, 0, $maxLength - 3) . '...';
   } else {
      // If the input string is already shorter than the maximum length, return it as is
      $shortenedString = $inputString;
   }

   return $shortenedString;
}

function printQSO($record) {
   echo "<br>\n";
   foreach ($record as $key => $value) {
      echo "$key=>$value<br>\n";
   }
   echo "<br>\n";
}

function ArrayUniqueOrderToString($arr) {
   $bandArray = array_values(array_unique($arr));
   natsort($bandArray);
   return implode(', ', $bandArray);
}

function formatDateString($inputDate) {
   // Use strtotime to parse the input string and create a DateTime object
   $date = DateTime::createFromFormat('Ymd', $inputDate);

   if ($date === false) {
      // Invalid input format
      return "Invalid date format";
   }

   // Format the DateTime object as "d-m-Y"
   return $date->format('d-m-Y');
}

function createRoundedRectangle($image, $x, $y, $width, $height, $radius, $color) {
   $diameter = $radius * 2;
   $ellipse = imagecreatetruecolor($diameter, $diameter);
   $transparent = imagecolorallocatealpha($ellipse, 255, 255, 255, 127);
   imagefill($ellipse, 0, 0, $transparent);
   imagefilledellipse($ellipse, $radius, $radius, $diameter, $diameter, $color);

   // Top-left corner
   imagecopymerge($image, $ellipse, $x, $y, 0, 0, $diameter, $diameter, 100);

   // Top-right corner
   imagecopymerge($image, $ellipse, $x + $width - $diameter, $y, 0, 0, $diameter, $diameter, 100);

   // Bottom-left corner
   imagecopymerge($image, $ellipse, $x, $y + $height - $diameter, 0, 0, $diameter, $diameter, 100);

   // Bottom-right corner
   imagecopymerge($image, $ellipse, $x + $width - $diameter, $y + $height - $diameter, 0, 0, $diameter, $diameter, 100);

   // Fill the middle rectangle
   imagefilledrectangle($image, $x + $radius, $y, $x + $width - $radius, $y + $height, $color);
}

function overlayTextOnImage($imagePath, $text1, $text2, $text3, $text4, $text5, $fontSize1, $fontSize2, $fontSize3, $fontSize4, $fontSize5) {
   // Load the original image
   $image = imagecreatefrompng($imagePath);
   $fontAlataHam = "alata-regular.ttf";
   $fontUbuntu = "Ubuntu-R.ttf";

   // Get the image dimensions
   $imageWidth = imagesx($image);
   $imageHeight = imagesy($image);

   // Create a transparent white box with rounded corners
   $boxWidth = $imageWidth * 0.45;
   $boxHeight = $boxWidth * 0.4;
   $boxX = $imageWidth * 0.05;
   $boxY = $imageHeight - ($imageHeight * 0.15) - $boxHeight;

   $boxColor = imagecolorallocatealpha($image, 255, 255, 255, 63);
   createRoundedRectangle($image, $boxX, $boxY, $boxWidth, $boxHeight, 5, $boxColor);

   // Define black color
   $textColor = imagecolorallocate($image, 0, 0, 0);

   // Add text to the image
   imagettftext($image, $fontSize1, 0, 115, $boxY + 65, $textColor, $fontUbuntu, $text1);
   imagettftext($image, $fontSize2, 0, 115, $boxY + 120, $textColor, $fontUbuntu, $text2);
   imagettftext($image, $fontSize3, 0, 115, $boxY + 180, $textColor, $fontUbuntu, "is presented this award for having made contact with the\nspecial commemorative station OE30FEST.");
   imagettftext($image, $fontSize4, 0, 115, $boxY + 260, $textColor, $fontUbuntu, "Band: ".$text3);
   imagettftext($image, $fontSize4, 0, 115, $boxY + 300, $textColor, $fontUbuntu, "Mode: ".$text4);
   imagettftext($image, $fontSize5, 0, $boxWidth - 80, $boxY + 35, $textColor, $fontUbuntu, $text5);


   // Output the image directly to the browser
   header('Content-Type: image/png');
   if(@$_POST['forcedl']=="forcedl")
   {
      header('Content-Disposition: attachment; filename="OE30FEST.png"');
   }
   imagepng($image);

   // Free up memory
   imagedestroy($image);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
   $callsign = $_POST["callsign"];

   // Remove leading and trailing whitespace
   $callsign = trim($callsign);
   // Convert to uppercase and replace / with _
   $callsign = strtoupper(str_replace("/", "_", $callsign));

   // Check if the input matches the pattern (A-Z, 0-9, and /)
   if (preg_match('/^[A-Z0-9\_]+$/', $callsign)) {

      // You can use $callsign for further processing or validation

      // Redirect or display a success message here if needed
      //echo "Callsign: " . $callsign . "<br>\n";

      //to-do set correct KEY and date
      $postData = array(
        'KEY'=>'<yourqrzkey>',
        'ACTION'=>'FETCH',
        'OPTION'=>'CALL:'.$callsign
      );

      $ch = curl_init('https://logbook.qrz.com/api');

      // Set cURL options
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_POST, true);
      curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData)); // Format data as x-www-form-urlencoded
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 100);
      curl_setopt($ch, CURLOPT_TIMEOUT, 600); //timeout in seconds
      curl_setopt($ch, CURLOPT_HTTPHEADER, array(
         'Content-Type: application/x-www-form-urlencoded',
      ));

      $response = curl_exec($ch);
      curl_close($ch);

      $resp = explode("ADIF=", htmlspecialchars_decode(urldecode(str_replace (array("\r\n", "\n", "\r"), '',str_replace("&RESULT=OK", '', $response)))));
      $adif = explode("&COUNT=",$resp[1]);
      $qsos = explode("<eor>", $adif[0]);
      array_pop($qsos);

      $bands = [];
      $modes = [];
      $name = "";
      $date = "";

      if (sizeof($qsos) > 0) {
         // Convert each ADIF record to an array
         foreach ($qsos as $q) {
            $qso = adifRecordToArray($q);

            // Print the resulting array
            //echo $qso['call'];
            array_push($bands, strtolower($qso['band']));
            array_push($modes, strtoupper($qso['mode']));
            $name = $qso['name'];
            $date = $qso['qso_date'];
            //printQSO($qso);
         }


         overlayTextOnImage('oe30fest.png', str_replace("0", "Ø", str_replace("_", "/", $callsign)), shortenString($name, 35), shortenString(ArrayUniqueOrderToString($bands),47), shortenString(ArrayUniqueOrderToString($modes),47), formatDateString($date), 50, 33, 22, 22, 22);
      }
      else {
         echo "Sorry you need at least one QSO for the award.";
      }
   } else {
      // Input did not match the pattern, display an error message
      echo "Invalid input. Only letters, numbers, and / are allowed.";
   }
}
else
{
?><!DOCTYPE html>
<html lang="en">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta charset="utf-8">
<title>POE30FEST Special Event Amateur Radio Station</title>
<link rel="icon" href="../favicon.ico">
<link rel="stylesheet" href="award.css">
<link rel="stylesheet" href="fonts.css">
</head>
<body>
<body>
	<div id="content">
		<p>
      		<span class="h">OE30FEST Special Event Amateur Radio Station</span><br>
      	</p>
		<div class="firstcontainer">
			<p>Das <strong>Gailtaler Speckfest</strong> ist ein traditionelles Fest, das im malerischen Gailtal in Kärnten, Österreich, gefeiert wird. Es ist eine Hommage an den köstlichen <strong>Gailtaler Speck</strong>, der aus der Region stammt und für seine einzigartige Qualität bekannt ist.</p> <p><strong>30 Jahre Gailtaler Speckfest:</strong> In diesem Jahr feiern wir ein besonderes Jubiläum – 30 Jahre Gailtaler Speckfest! Seit drei Jahrzehnten kommen Menschen aus nah und fern zusammen, um die kulinarischen Köstlichkeiten und die herzliche Atmosphäre dieses Festes zu genießen. Das Speckfest ist nicht nur ein Fest für den Gaumen, sondern auch ein Treffpunkt für Freunde, Familie und alle, die die Gailtaler Kultur und Tradition schätzen.</p> <p><strong>Special Calls der ADL 805 Gailtal Landesverband OE8:</strong> Zur Feier dieses besonderen Anlasses wurden <strong>Special Calls</strong> eingerichtet: <strong>OE8SPECK</strong> und <strong>OE30FEST</strong>. Funkamateure haben die Möglichkeit, diese speziellen Rufzeichen während des Speckfests zu verwenden. Es ist eine großartige Gelegenheit, die Liebe zur Funktechnik mit der Begeisterung für gutes Essen und die Gailtaler Gastfreundschaft zu verbinden.</p> <p><strong>SPECKFEST QSL-Anfragen:</strong> Wenn Sie an diesem einzigartigen Event teilnehmen und eine Erinnerung daran behalten möchten, können Sie auf der offiziellen <a href="https://www.speckfest.at/qsl-request/" target="_blank">Speckfest-Website</a> eine <strong>QSL-Karte</strong> anfordern. Diese Karte bestätigt Ihre Funkverbindung mit den Special Calls OE8SPECK und OE30FEST. Nutzen Sie diese Gelegenheit, um Ihre Leidenschaft für Funktechnik zu dokumentieren und gleichzeitig das Gailtaler Speckfest zu feiern!</p>
		</div>
		<p>Wenn du ein QSO mit OE30FEST gemacht hast, so kannst du hier die QSL-Karte herunterladen</p>
    	<form action="?" method="post">
        	<label for="callsign">Rufzeichen:</label>
        	<input type="text" id="callsign" name="callsign" pattern="[A-Za-z0-9/]+" title="Only uppercase letters, numbers, and / are allowed." required>
        	<br>
        	<input type="checkbox" id="forcedl" name="forcedl" value="forcedl">
        	<label for="forcedl"><small>Aktiviere die Checkbox, wenn du Probleme hast den Award herunterzuladen.</small></label><br>
        	<br>
			<input type="submit" name="submit" value="QSL anfordern">
			<br>
			<br>
			<label>73 de ADL 805 Gailtal</label>
			<br>
			<br>
    	</form>
	</div>
</body>
</html><?php
}
