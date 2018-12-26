<?php
#!/usr/bin/php
        error_reporting(E_ALL);

/* Modified script for logging a SecaGrid 1500 by using the Erhernet port and new firmware as of ~ 04-2017 
/* Original script is written by Anton Boonstra and used a different suburl to get the measurements values */ 
/* Since I didn't have that suburl I sniffed the page and found measurements.xml */
/* I modified $energy by removing the "trim" statement because well, now it works again */
/* Script is added to crontab to send data to PVoutput.org every 5 minutes */ 
/* The benefit of this script is that it can also output to Domoticz, a SQL database or Grafana since all the values can be exported */  

/* 12-6-2017 Ierlandfan */
/* Based on a SecaGrid logging script written by Anton Boonstra */
/* Modules needed php-xml and php-curl  */

$steca_ip = 'YOUR_IP'; // IP adres van de Steca omvormer
$parameter['key'] = 'YOUR_PV_OUTPUT_API_KEY'; // vul je Pvoutput-Apikey in
$parameter['sid'] = YOUR_SYSTEM_ID; // vul hier je eigen system ID in

// uitlezen Steca pagina met huidige meetwaarden

$data = file_get_contents('http://'.$steca_ip.'/measurements.xml');
$array = json_decode(json_encode((array)simplexml_load_string($data)),2);

//print_r($array);

foreach($array as $key => $value){
  foreach($value as $key1 => $final_array){
	}
}
//print_r($final_array);


$device_info  = $array['Device']['@attributes'];
$AC_Voltage   = $final_array['Measurement'][0]['@attributes']['Value'];
$AC_Current   = $final_array['Measurement'][1]['@attributes']['Value'];
$AC_Power     = $final_array['Measurement'][2]['@attributes']['Value'];
$AC_Frequency = $final_array['Measurement'][3]['@attributes']['Value'];
$DC_Voltage   = $final_array['Measurement'][4]['@attributes']['Value'];
$DC_Current   = $final_array['Measurement'][5]['@attributes']['Value'];
$Temp         = $final_array['Measurement'][6]['@attributes']['Value'];
//$GridPower    = $final_array['Measurement'][7]['@attributes']['Value']; // No Ouput value
$Derating     = $final_array['Measurement'][8]['@attributes']['Value'];

/* All above is for live logging of the SecaGrid 1500 */


// uitlezen Steca pagina met dagopbrengst
$values = file_get_contents('http://'.$steca_ip.'/gen.yield.day.chart.js');
$dag = trim(substr($values, strpos($values,'input.setAttribute("value"')+29, 10));
//print $dag;
if($dag != date("Y-m-d")) // als de dagopbrengst niet van vandaag is
{
  $temp = file_get_contents('http://'.$steca_ip.'/page.yield.day.html?DATE_SELECTED='.date("Y-m-d")); // selecteer vandaag
  $values = file_get_contents('http://'.$steca_ip.'/gen.yield.day.chart.js'); // opnieuw waarden laden
}

$energy = substr($values, strpos($values,'labelValueId')+28, 8)*1000; /* kWh -> Wh */
print_r( $energy);


$parameter['d'] =  date("Ymd"); // huidige datum
$parameter['t'] =  date("H:i"); // huidige tijd
$parameter['v1'] = $energy; // energie opwekking van vandaag in wattuur
$parameter['v2'] = $AC_Power; // vermogen opwekking in watt
$parameter['v5'] = $Temp; // Temperature of cells?
$parameter['v6'] = $DC_Voltage; // DC voltage in volt

$url = 'http://pvoutput.org/service/r2/addstatus.jsp?' . http_build_query($parameter);
$reactie = file_get_contents($url); // verstuur de gegevens naar PVOutput

//Send data to Domoticz for local monitoring
$ch1 = curl_init("http://127.0.0.1:8080/json.htm?type=command&param=udevice&idx=146&nvalue=0&svalue=$energy");
error_log("$execution_date_time -- (Solar) -- Energy : ('$energy')",0);
closelog();
curl_exec($ch1);
curl_close($ch1);

$ch2 = curl_init("http://127.0.0.1:8080/json.htm?type=command&param=udevice&idx=147&nvalue=0&svalue=$AC_Power");
error_log("$execution_date_time -- (Solar) -- AC Power (Watt/uur)  ('$AC_Power')",0);
closelog();
curl_exec($ch2);
curl_close($ch2);

$ch3 = curl_init("http://127.0.0.1:8080/json.htm?type=command&param=udevice&idx=145&nvalue=0&svalue=$Temp");
error_log("$execution_date_time -- (Solar) -- Temperature  ('$Temp')",0);
closelog();
curl_exec($ch3);
curl_close($ch3);

if($reactie == "OK 200: Added Status")
{
  echo "Waarden succesvol uitgelezen en verstuurd naar PVOutput";
error_log("Waarden succesvol uitgelezen en verstuurd naar PVOutput",0);
}

else
{
  echo "PVOutput gaf geen OK terug. Poging 2...";
  error_log("PVOutput gaf geen OK terug. Poging 2...",0);
  sleep(4); // wacht 4 seconden
  $reactie = file_get_contents($url); // probeer nog een keer de gegevens naar PVOutput te sturen
  if($reactie == "OK 200: Added Status")
  {
    echo "Waarden succesvol uitgelezen en verstuurd naar PVOutput <br>\n";
  error_log("Waarden alsnog succesvol uitgelezen en verstuurd naar PVOutput",0);
	}
  else
  {
    echo "PVOutput gaf ook de tweede keer geen OK terug. PVOutput offline?<br>\n";
error_log("PVOutput gaf ook de tweede keer geen OK terug. PVOutput offline?",0);
    echo "Reactie PVOutput was: $reactie \n";
error_log("PVOutput Error -- Reactie PVOutput was: $reactie"); 
	 }
}

?>
