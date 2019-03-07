<?

if (!defined('vtBoolean')) {
    define('vtBoolean', 0);
    define('vtInteger', 1);
    define('vtFloat', 2);
    define('vtString', 3);
    define('vtArray', 8);
    define('vtObject', 9);
}


	class WundergroundPWSSync extends IPSModule
	{
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			$this->RegisterPropertyInteger("SourceID", 0);
			$this->RegisterPropertyString("WU_ID", "");
			$this->RegisterPropertyString("WU_Password","");
			$this->RegisterPropertyString("WU_API","");
			$this->RegisterPropertyString("Mode","U");
			$this->RegisterPropertyString("Language","de-de");
			$this->RegisterPropertyString("Latitude","");
			$this->RegisterPropertyString("Longitude","");
			$this->RegisterPropertyInteger("ForecastShort","0");
			$this->RegisterPropertyInteger("ForecastDP","0");
			$this->RegisterPropertyBoolean("ForecastDPTemperature","0");
			$this->RegisterPropertyBoolean("ForecastDPRain","0");
			$this->RegisterPropertyBoolean("ForecastDPNarrative","0");
			$this->RegisterPropertyBoolean("ForecastDPWind","0");
			$this->RegisterPropertyBoolean("ForecastDPUV","0");
			$this->RegisterPropertyBoolean("ForecastDPThunder","0");
			$this->RegisterPropertyInteger("OutsideTemperature", 0);
			$this->RegisterPropertyInteger("Humidity", 0);
			$this->RegisterPropertyInteger("DewPoint", 0);
			$this->RegisterPropertyInteger("WindDirection", 0);
			$this->RegisterPropertyInteger("WindSpeed", 0);
			$this->RegisterPropertyInteger("WindGust", 0);
			$this->RegisterPropertyInteger("Rain_last_Hour", 0);
			$this->RegisterPropertyInteger("Rain24h", 0);
			$this->RegisterPropertyInteger("AirPressure", 0);
			$this->RegisterPropertyInteger("UVIndex", 0);
			$this->RegisterPropertyInteger("Timer", 0);
			$this->RegisterPropertyBoolean("Debug", 0);
			
			//Component sets timer, but default is OFF
			$this->RegisterTimer("UpdateTimer",0,"WUPWSS_UploadToWunderground(\$_IPS['TARGET']);");			
		}
	
		public function ApplyChanges()
		{
			
			//Never delete this line!
			parent::ApplyChanges();
			
									
		        //Timer Update - if greater than 0 = On
				
				$TimerMS = $this->ReadPropertyInteger("Timer") * 1000;
				
        		$this->SetTimerInterval("UpdateTimer",$TimerMS);
    		

			$vpos = 1;
				
				//Statics Timer Creation - On - Off
				
				$sourceID = $this->ReadPropertyInteger("SourceID");
		
				$eid = @IPS_GetObjectIDByIdent("Forecast", $this->InstanceID);
				if ($eid == 0) {
					$eid = IPS_CreateEvent(1);
					IPS_SetParent($eid, $this->InstanceID);
					IPS_SetIdent($eid, "Forecast");
					IPS_SetName($eid, "Forecast");
					IPS_SetHidden($eid, true);
					IPS_SetEventCyclic($eid, 2, 1, 0, 0, 3, 12);    //Jeden Tag
					IPS_SetEventCyclicTimeFrom($eid, 07, 00, 0);
					IPS_SetEventScript($eid, 'WUPWSS_Forecast($_IPS[\'TARGET\'], "Up");');
				}
				
				If (($this->ReadPropertyInteger("ForecastDP") > 0 OR $this->ReadPropertyInteger("ForecastShort") > 0))
				{
					$eid = @IPS_GetObjectIDByIdent("Forecast", $this->InstanceID);
					IPS_SetEventActive($eid, true);
				}
				
				ElseIf (($this->ReadPropertyInteger("ForecastDP") == 0) OR ($this->ReadPropertyInteger("ForecastShort") == 0))
				{
					$eid = @IPS_GetObjectIDByIdent("Forecast", $this->InstanceID);
					IPS_SetEventActive($eid, false);
				}

				//Variablen anlegen

				
				$vpos = 10;
				
				$this->MaintainVariable('DP0DN', $this->Translate('Daypart 0 (Current 12h) Day or Night'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 0);
				$this->MaintainVariable('DP0Name', $this->Translate('Daypart 0 (Current 12h) Part of day'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 0);
				$this->MaintainVariable('DP0Narrative', $this->Translate('Daypart 0 (Current 12h) Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 0);
				$this->MaintainVariable('DP0PrecipChance', $this->Translate('Daypart 0 (Current 12h) Precip Chance'), vtInteger, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 0);
				$this->MaintainVariable('DP0PrecipType', $this->Translate('Daypart 0 (Current 12h) Precip Type'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 0);
				$this->MaintainVariable('DP0QPF', $this->Translate('Daypart 0 (Current 12h) Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 0 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP0QPFSNOW', $this->Translate('Daypart 0 (Current 12h) Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 0 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP0Temperature', $this->Translate('Daypart 0 (Current 12h) Temperature'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 0 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP0WindChill', $this->Translate('Daypart 0 (Current 12h) Wind Chill'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 0 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP0Thunder', $this->Translate('Daypart 0 (Current 12h) Thunder'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 0 AND $this->ReadPropertyBoolean("ForecastDPThunder") == "1");
				$this->MaintainVariable('DP0UVDescription', $this->Translate('Daypart 0 (Current 12h) UV Description'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 0 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP0UVIndex', $this->Translate('Daypart 0 (Current 12h) UV Index'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 0 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1");
				$this->MaintainVariable('DP0WINDDIR', $this->Translate('Daypart 0 (Current 12h) Wind Direction'), vtFloat, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 0 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				$this->MaintainVariable('DP0WINDDIRText', $this->Translate('Daypart 0 (Current 12h) Wind Direction Text'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 0 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP0WINDDIRPhrase', $this->Translate('Daypart 0 (Current 12h) Wind Phrase'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 0 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP0WINDSpeed', $this->Translate('Daypart 0 (Current 12h) Wind Speed'), vtFloat, "~WindSpeed.ms", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 0 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				
				$vpos = 50;
				
				$this->MaintainVariable('DP1DN', $this->Translate('Daypart 1 (Next 12h) Day or Night'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 1);
				$this->MaintainVariable('DP1Name', $this->Translate('Daypart 1 (Next 12h) Part of day'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 1);
				$this->MaintainVariable('DP1Narrative', $this->Translate('Daypart 1 (Next 12h) Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 1);
				$this->MaintainVariable('DP1PrecipChance', $this->Translate('Daypart 1 (Next 12h) Precip Chance'), vtInteger, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 1);
				$this->MaintainVariable('DP1PrecipType', $this->Translate('Daypart 1 (Next 12h) Precip Type'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 1);
				$this->MaintainVariable('DP1QPF', $this->Translate('Daypart 1 (Next 12h) Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 1 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP1QPFSNOW', $this->Translate('Daypart 1 (Next 12h) Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 1 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP1Temperature', $this->Translate('Daypart 1 (Next 12h) Temperature'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 1 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP1WindChill', $this->Translate('Daypart 1 (Next 12h) Wind Chill'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 1 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP1Thunder', $this->Translate('Daypart 1 (Next 12h) Thunder'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 1 AND $this->ReadPropertyBoolean("ForecastDPThunder") == "1");
				$this->MaintainVariable('DP1UVDescription', $this->Translate('Daypart 1 (Next 12h) UV Description'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 1 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP1UVIndex', $this->Translate('Daypart 1 (Next 12h) UV Index'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 1 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1");
				$this->MaintainVariable('DP1WINDDIR', $this->Translate('Daypart 1 (Next 12h) Wind Direction'), vtFloat, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 1 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				$this->MaintainVariable('DP1WINDDIRText', $this->Translate('Daypart 1 (Next 12h) Wind Direction Text'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 1 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP1WINDDIRPhrase', $this->Translate('Daypart 1 (Next 12h) Wind Phrase'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 1 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP1WINDSpeed', $this->Translate('Daypart 1 (Next 12h) Wind Speed'), vtFloat, "~WindSpeed.ms", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 1 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");

				$vpos = 100;
				
				$this->MaintainVariable('DP2DN', $this->Translate('Daypart 2 - Day or Night'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 2);
				$this->MaintainVariable('DP2Name', $this->Translate('Daypart 2 - Part of day'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 2);
				$this->MaintainVariable('DP2Narrative', $this->Translate('Daypart 2 - Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 2);
				$this->MaintainVariable('DP2PrecipChance', $this->Translate('Daypart 2 - Precip Chance'), vtInteger, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 2);
				$this->MaintainVariable('DP2PrecipType', $this->Translate('Daypart 2 - Precip Type'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 2);
				$this->MaintainVariable('DP2QPF', $this->Translate('Daypart 2 - Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 2 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP2QPFSNOW', $this->Translate('Daypart 2 - Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 2 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP2Temperature', $this->Translate('Daypart 2 - Temperature'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 2 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP2WindChill', $this->Translate('Daypart 2 - Wind Chill'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 2 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP2Thunder', $this->Translate('Daypart 2 - Thunder'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 2 AND $this->ReadPropertyBoolean("ForecastDPThunder") == "1");
				$this->MaintainVariable('DP2UVDescription', $this->Translate('Daypart 2 - UV Description'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 2 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP2UVIndex', $this->Translate('Daypart 2 - UV Index'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 2 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1");
				$this->MaintainVariable('DP2WINDDIR', $this->Translate('Daypart 2 - Wind Direction'), vtFloat, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 2 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				$this->MaintainVariable('DP2WINDDIRText', $this->Translate('Daypart 2 - Wind Direction Text'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 2 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP2WINDDIRPhrase', $this->Translate('Daypart 2 - Wind Phrase'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 2 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP2WINDSpeed', $this->Translate('Daypart 2 - Wind Speed'), vtFloat, "~WindSpeed.ms", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 2 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");

				$vpos = 150;
				
				$this->MaintainVariable('DP3DN', $this->Translate('Daypart 3 - Day or Night'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 3);
				$this->MaintainVariable('DP3Name', $this->Translate('Daypart 3 - Part of day'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 3);
				$this->MaintainVariable('DP3Narrative', $this->Translate('Daypart 3 - Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 3);
				$this->MaintainVariable('DP3PrecipChance', $this->Translate('Daypart 3 - Precip Chance'), vtInteger, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 3);
				$this->MaintainVariable('DP3PrecipType', $this->Translate('Daypart 3 - Precip Type'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 3);
				$this->MaintainVariable('DP3QPF', $this->Translate('Daypart 3 - Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 3 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP3QPFSNOW', $this->Translate('Daypart 3 - Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 3 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP3Temperature', $this->Translate('Daypart 3 - Temperature'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 3 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP3WindChill', $this->Translate('Daypart 3 - Wind Chill'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 3 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP3Thunder', $this->Translate('Daypart 3 - Thunder'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 3 AND $this->ReadPropertyBoolean("ForecastDPThunder") == "1");
				$this->MaintainVariable('DP3UVDescription', $this->Translate('Daypart 3 - UV Description'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 3 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP3UVIndex', $this->Translate('Daypart 3 - UV Index'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 3 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1");
				$this->MaintainVariable('DP3WINDDIR', $this->Translate('Daypart 3 - Wind Direction'), vtFloat, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 3 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				$this->MaintainVariable('DP3WINDDIRText', $this->Translate('Daypart 3 - Wind Direction Text'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 3 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP3WINDDIRPhrase', $this->Translate('Daypart 3 - Wind Phrase'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 3 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP3WINDSpeed', $this->Translate('Daypart 3 - Wind Speed'), vtFloat, "~WindSpeed.ms", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 3 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");

				$vpos = 200;
				
				$this->MaintainVariable('DP4DN', $this->Translate('Daypart 4 - Day or Night'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 4);
				$this->MaintainVariable('DP4Name', $this->Translate('Daypart 4 - Part of day'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 4);
				$this->MaintainVariable('DP4Narrative', $this->Translate('Daypart 4 - Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 4);
				$this->MaintainVariable('DP4PrecipChance', $this->Translate('Daypart 4 - Precip Chance'), vtInteger, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 4);
				$this->MaintainVariable('DP4PrecipType', $this->Translate('Daypart 4 - Precip Type'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 4);
				$this->MaintainVariable('DP4QPF', $this->Translate('Daypart 4 - Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 4 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP4QPFSNOW', $this->Translate('Daypart 4 - Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 4 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP4Temperature', $this->Translate('Daypart 4 - Temperature'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 4 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP4WindChill', $this->Translate('Daypart 4 - Wind Chill'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 4 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP4Thunder', $this->Translate('Daypart 4 - Thunder'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 4 AND $this->ReadPropertyBoolean("ForecastDPThunder") == "1");
				$this->MaintainVariable('DP4UVDescription', $this->Translate('Daypart 4 - UV Description'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 4 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP4UVIndex', $this->Translate('Daypart 4 - UV Index'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 4 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1");
				$this->MaintainVariable('DP4WINDDIR', $this->Translate('Daypart 4 - Wind Direction'), vtFloat, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 4 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				$this->MaintainVariable('DP4WINDDIRText', $this->Translate('Daypart 4 - Wind Direction Text'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 4 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP4WINDDIRPhrase', $this->Translate('Daypart 4 - Wind Phrase'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 4 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP4WINDSpeed', $this->Translate('Daypart 4 - Wind Speed'), vtFloat, "~WindSpeed.ms", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 4 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");

				$vpos = 250;
				
				$this->MaintainVariable('DP5DN', $this->Translate('Daypart 5 - Day or Night'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 5);
				$this->MaintainVariable('DP5Name', $this->Translate('Daypart 5 - Part of day'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 5);
				$this->MaintainVariable('DP5Narrative', $this->Translate('Daypart 5 - Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 5);
				$this->MaintainVariable('DP5PrecipChance', $this->Translate('Daypart 5 - Precip Chance'), vtInteger, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 5);
				$this->MaintainVariable('DP5PrecipType', $this->Translate('Daypart 5 - Precip Type'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 5);
				$this->MaintainVariable('DP5QPF', $this->Translate('Daypart 5 - Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 5 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP5QPFSNOW', $this->Translate('Daypart 5 - Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 5 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP5Temperature', $this->Translate('Daypart 5 - Temperature'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 5 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP5WindChill', $this->Translate('Daypart 5 - Wind Chill'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 5 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP5Thunder', $this->Translate('Daypart 5 - Thunder'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 5 AND $this->ReadPropertyBoolean("ForecastDPThunder") == "1");
				$this->MaintainVariable('DP5UVDescription', $this->Translate('Daypart 5 - UV Description'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 5 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP5UVIndex', $this->Translate('Daypart 5 - UV Index'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 5 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1");
				$this->MaintainVariable('DP5WINDDIR', $this->Translate('Daypart 5 - Wind Direction'), vtFloat, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 5 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				$this->MaintainVariable('DP5WINDDIRText', $this->Translate('Daypart 5 - Wind Direction Text'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 5 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP5WINDDIRPhrase', $this->Translate('Daypart 5 - Wind Phrase'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 5 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP5WINDSpeed', $this->Translate('Daypart 5 - Wind Speed'), vtFloat, "~WindSpeed.ms", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 5 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				
				$vpos = 300;
				
				$this->MaintainVariable('DP6DN', $this->Translate('Daypart 6 - Day or Night'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 6);
				$this->MaintainVariable('DP6Name', $this->Translate('Daypart 6 - Part of day'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 6);
				$this->MaintainVariable('DP6Narrative', $this->Translate('Daypart 6 - Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 6);
				$this->MaintainVariable('DP6PrecipChance', $this->Translate('Daypart 6 - Precip Chance'), vtInteger, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 6);
				$this->MaintainVariable('DP6PrecipType', $this->Translate('Daypart 6 - Precip Type'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 6);
				$this->MaintainVariable('DP6QPF', $this->Translate('Daypart 6 - Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 6 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP6QPFSNOW', $this->Translate('Daypart 6 - Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 6 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP6Temperature', $this->Translate('Daypart 6 - Temperature'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 6 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP6WindChill', $this->Translate('Daypart 6 - Wind Chill'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 6 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP6Thunder', $this->Translate('Daypart 6 - Thunder'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 6 AND $this->ReadPropertyBoolean("ForecastDPThunder") == "1");
				$this->MaintainVariable('DP6UVDescription', $this->Translate('Daypart 6 - UV Description'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 6 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP6UVIndex', $this->Translate('Daypart 6 - UV Index'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 6 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1");
				$this->MaintainVariable('DP6WINDDIR', $this->Translate('Daypart 6 - Wind Direction'), vtFloat, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 6 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				$this->MaintainVariable('DP6WINDDIRText', $this->Translate('Daypart 6 - Wind Direction Text'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 6 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP6WINDDIRPhrase', $this->Translate('Daypart 6 - Wind Phrase'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 6 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP6WINDSpeed', $this->Translate('Daypart 6 - Wind Speed'), vtFloat, "~WindSpeed.ms", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 6 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				
				$vpos = 350;
				
				$this->MaintainVariable('DP7DN', $this->Translate('Daypart 7 - Day or Night'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 7);
				$this->MaintainVariable('DP7Name', $this->Translate('Daypart 7 - Part of day'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 7);
				$this->MaintainVariable('DP7Narrative', $this->Translate('Daypart 7 - Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 7);
				$this->MaintainVariable('DP7PrecipChance', $this->Translate('Daypart 7 - Precip Chance'), vtInteger, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 7);
				$this->MaintainVariable('DP7PrecipType', $this->Translate('Daypart 7 - Precip Type'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 7);
				$this->MaintainVariable('DP7QPF', $this->Translate('Daypart 7 - Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 7 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP7QPFSNOW', $this->Translate('Daypart 7 - Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 7 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP7Temperature', $this->Translate('Daypart 7 - Temperature'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 7 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP7WindChill', $this->Translate('Daypart 7 - Wind Chill'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 7 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP7Thunder', $this->Translate('Daypart 7 - Thunder'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 7 AND $this->ReadPropertyBoolean("ForecastDPThunder") == "1");
				$this->MaintainVariable('DP7UVDescription', $this->Translate('Daypart 7 - UV Description'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 7 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP7UVIndex', $this->Translate('Daypart 7 - UV Index'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 7 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1");
				$this->MaintainVariable('DP7WINDDIR', $this->Translate('Daypart 7 - Wind Direction'), vtFloat, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 7 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				$this->MaintainVariable('DP7WINDDIRText', $this->Translate('Daypart 7 - Wind Direction Text'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 7 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP7WINDDIRPhrase', $this->Translate('Daypart 7 - Wind Phrase'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 7 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP7WINDSpeed', $this->Translate('Daypart 7 - Wind Speed'), vtFloat, "~WindSpeed.ms", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 7 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				
				$vpos = 400;
				
				$this->MaintainVariable('DP8DN', $this->Translate('Daypart 8 - Day or Night'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 8);
				$this->MaintainVariable('DP8Name', $this->Translate('Daypart 8 - Part of day'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 8);
				$this->MaintainVariable('DP8Narrative', $this->Translate('Daypart 8 - Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 8);
				$this->MaintainVariable('DP8PrecipChance', $this->Translate('Daypart 8 - Precip Chance'), vtInteger, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 8);
				$this->MaintainVariable('DP8PrecipType', $this->Translate('Daypart 8 - Precip Type'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 8);
				$this->MaintainVariable('DP8QPF', $this->Translate('Daypart 8 - Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 8 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP8QPFSNOW', $this->Translate('Daypart 8 - Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 8 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP8Temperature', $this->Translate('Daypart 8 - Temperature'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 8 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP8WindChill', $this->Translate('Daypart 8 - Wind Chill'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 8 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP8Thunder', $this->Translate('Daypart 8 - Thunder'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 8 AND $this->ReadPropertyBoolean("ForecastDPThunder") == "1");
				$this->MaintainVariable('DP8UVDescription', $this->Translate('Daypart 8 - UV Description'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 8 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP8UVIndex', $this->Translate('Daypart 8 - UV Index'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 8 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1");
				$this->MaintainVariable('DP8WINDDIR', $this->Translate('Daypart 8 - Wind Direction'), vtFloat, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 8 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				$this->MaintainVariable('DP8WINDDIRText', $this->Translate('Daypart 8 - Wind Direction Text'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 8 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP8WINDDIRPhrase', $this->Translate('Daypart 8 - Wind Phrase'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 8 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP8WINDSpeed', $this->Translate('Daypart 8 - Wind Speed'), vtFloat, "~WindSpeed.ms", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 8 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				
				$vpos = 450;
				
				$this->MaintainVariable('DP9DN', $this->Translate('Daypart 9 - Day or Night'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 9);
				$this->MaintainVariable('DP9Name', $this->Translate('Daypart 9 - Part of day'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 9);
				$this->MaintainVariable('DP9Narrative', $this->Translate('Daypart 9 - Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 9);
				$this->MaintainVariable('DP9PrecipChance', $this->Translate('Daypart 9 - Precip Chance'), vtInteger, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 9);
				$this->MaintainVariable('DP9PrecipType', $this->Translate('Daypart 9 - Precip Type'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 9);
				$this->MaintainVariable('DP9QPF', $this->Translate('Daypart 9 - Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 9 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP9QPFSNOW', $this->Translate('Daypart 9 - Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 9 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP9Temperature', $this->Translate('Daypart 9 - Temperature'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 9 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP9WindChill', $this->Translate('Daypart 9 - Wind Chill'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 9 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP9Thunder', $this->Translate('Daypart 9 - Thunder'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 9 AND $this->ReadPropertyBoolean("ForecastDPThunder") == "1");
				$this->MaintainVariable('DP9UVDescription', $this->Translate('Daypart 9 - UV Description'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 9 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP9UVIndex', $this->Translate('Daypart 9 - UV Index'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 9 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1");
				$this->MaintainVariable('DP9WINDDIR', $this->Translate('Daypart 9 - Wind Direction'), vtFloat, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 9 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				$this->MaintainVariable('DP9WINDDIRText', $this->Translate('Daypart 9 - Wind Direction Text'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 9 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP9WINDDIRPhrase', $this->Translate('Daypart 9 - Wind Phrase'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 9 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP9WINDSpeed', $this->Translate('Daypart 9 - Wind Speed'), vtFloat, "~WindSpeed.ms", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 9 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				
				$vpos = 500;
				
				$this->MaintainVariable('DP10DN', $this->Translate('Daypart 10 - Day or Night'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 10);
				$this->MaintainVariable('DP10Name', $this->Translate('Daypart 10 - Part of day'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 10);
				$this->MaintainVariable('DP10Narrative', $this->Translate('Daypart 10 - Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 10);
				$this->MaintainVariable('DP10PrecipChance', $this->Translate('Daypart 10 - Precip Chance'), vtInteger, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 10);
				$this->MaintainVariable('DP10PrecipType', $this->Translate('Daypart 10 - Precip Type'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 10);
				$this->MaintainVariable('DP10QPF', $this->Translate('Daypart 10 - Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 10 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP10QPFSNOW', $this->Translate('Daypart 10 - Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 10 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP10Temperature', $this->Translate('Daypart 10 - Temperature'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 10 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP10WindChill', $this->Translate('Daypart 10 - Wind Chill'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 10 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP10Thunder', $this->Translate('Daypart 10 - Thunder'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 10 AND $this->ReadPropertyBoolean("ForecastDPThunder") == "1");
				$this->MaintainVariable('DP10UVDescription', $this->Translate('Daypart 10 - UV Description'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 10 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP10UVIndex', $this->Translate('Daypart 10 - UV Index'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 10 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1");
				$this->MaintainVariable('DP10WINDDIR', $this->Translate('Daypart 10 - Wind Direction'), vtFloat, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 10 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				$this->MaintainVariable('DP10WINDDIRText', $this->Translate('Daypart 10 - Wind Direction Text'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 10 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP10WINDDIRPhrase', $this->Translate('Daypart 10 - Wind Phrase'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 10 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP10WINDSpeed', $this->Translate('Daypart 10 - Wind Speed'), vtFloat, "~WindSpeed.ms", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 10 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				
				$vpos = 550;
				
				$this->MaintainVariable('DP11DN', $this->Translate('Daypart 11 - Day or Night'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 11);
				$this->MaintainVariable('DP11Name', $this->Translate('Daypart 11 - Part of day'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 11);
				$this->MaintainVariable('DP11Narrative', $this->Translate('Daypart 11 - Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 11);
				$this->MaintainVariable('DP11PrecipChance', $this->Translate('Daypart 11 - Precip Chance'), vtInteger, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 11);
				$this->MaintainVariable('DP11PrecipType', $this->Translate('Daypart 11 - Precip Type'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 11);
				$this->MaintainVariable('DP11QPF', $this->Translate('Daypart 11 - Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 11 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP11QPFSNOW', $this->Translate('Daypart 11 - Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 11 AND $this->ReadPropertyBoolean("ForecastDPRain") == "1");
				$this->MaintainVariable('DP11Temperature', $this->Translate('Daypart 11 - Temperature'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 11 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP11WindChill', $this->Translate('Daypart 11 - Wind Chill'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 11 AND $this->ReadPropertyBoolean("ForecastDPTemperature") == "1");
				$this->MaintainVariable('DP11Thunder', $this->Translate('Daypart 11 - Thunder'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 11 AND $this->ReadPropertyBoolean("ForecastDPThunder") == "1");
				$this->MaintainVariable('DP11UVDescription', $this->Translate('Daypart 11 - UV Description'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 11 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP11UVIndex', $this->Translate('Daypart 11 - UV Index'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 11 AND $this->ReadPropertyBoolean("ForecastDPUV") == "1");
				$this->MaintainVariable('DP11WINDDIR', $this->Translate('Daypart 11 - Wind Direction'), vtFloat, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 11 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");
				$this->MaintainVariable('DP11WINDDIRText', $this->Translate('Daypart 11 - Wind Direction Text'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 11 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP11WINDDIRPhrase', $this->Translate('Daypart 11 - Wind Phrase'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 11 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1");
				$this->MaintainVariable('DP11WINDSpeed', $this->Translate('Daypart 11 - Wind Speed'), vtFloat, "~WindSpeed.ms", $vpos++, $this->ReadPropertyInteger("ForecastDP") > 11 AND $this->ReadPropertyBoolean("ForecastDPWind") == "1");


				
				
				$vpos = 1000;
				
				$this->MaintainVariable('D1Forecast', $this->Translate('Day 1 Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "0");
				$this->MaintainVariable('D1QPF', $this->Translate('Day 1 Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "0");
				$this->MaintainVariable('D1QPFSNOW', $this->Translate('Day 1 Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "0");
				$this->MaintainVariable('D1TemperatureMax', $this->Translate('Day 1 Temperature Max'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "0");
				$this->MaintainVariable('D1TemperatureMin', $this->Translate('Day 1 Temperature Min'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "0");
				
				
				$vpos = 1050;
				$this->MaintainVariable('D2Forecast', $this->Translate('Day 2 Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "1");
				$this->MaintainVariable('D2QPF', $this->Translate('Day 2 Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "1");
				$this->MaintainVariable('D2QPFSNOW', $this->Translate('Day 2 Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "1");
				$this->MaintainVariable('D2TemperatureMax', $this->Translate('Day 2 Temperature Max'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "1");
				$this->MaintainVariable('D2TemperatureMin', $this->Translate('Day 2 Temperature Min'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "1");
				
				
				$vpos = 1100;
				$this->MaintainVariable('D3Forecast', $this->Translate('Day 3 Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "2");
				$this->MaintainVariable('D3QPF', $this->Translate('Day 3 Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "2");
				$this->MaintainVariable('D3QPFSNOW', $this->Translate('Day 3 Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "2");
				$this->MaintainVariable('D3TemperatureMax', $this->Translate('Day 3 Temperature Max'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "2");
				$this->MaintainVariable('D3TemperatureMin', $this->Translate('Day 3 Temperature Min'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "2");
				
				
				$vpos = 1250;
				$this->MaintainVariable('D4Forecast', $this->Translate('Day 4 Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "3");
				$this->MaintainVariable('D4QPF', $this->Translate('Day 4 Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "3");
				$this->MaintainVariable('D4QPFSNOW', $this->Translate('Day 4 Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "3");
				$this->MaintainVariable('D4TemperatureMax', $this->Translate('Day 4 Temperature Max'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "3");
				$this->MaintainVariable('D4TemperatureMin', $this->Translate('Day 4 Temperature Min'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "3");
				
				
				$vpos = 1300;
				$this->MaintainVariable('D5Forecast', $this->Translate('Day 5 Weather Forecast'), vtString, "", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "4");
				$this->MaintainVariable('D5QPF', $this->Translate('Day 5 Precipitation Liquid'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "4");
				$this->MaintainVariable('D5QPFSNOW', $this->Translate('Day 5 Precipitation Snow'), vtFloat, "~Rainfall", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "4");
				$this->MaintainVariable('D5TemperatureMax', $this->Translate('Day 5 Temperature Max'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "4");
				$this->MaintainVariable('D5TemperatureMin', $this->Translate('Day 5 Temperature Min'), vtFloat, "~Temperature", $vpos++, $this->ReadPropertyInteger("ForecastShort") > "4");
				
				
				
			
		}
	
	
		public function Forecast()
		{
			
			$WU_ID = $this->ReadPropertyString("WU_ID");
			$Language = $this->ReadPropertyString("Language");
			$WU_API = $this->ReadPropertyString("WU_API");
			$Longitude = $this->ReadPropertyString("Longitude");
			$Latitude = $this->ReadPropertyString("Latitude");
			
			$ch = curl_init(); 
				curl_setopt($ch, CURLOPT_URL, 'https://api.weather.com/v3/wx/forecast/daily/5day?geocode='.$Latitude.','.$Longitude.'&format=json&units=m&language='.$Language.'&apiKey='.$WU_API);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_TIMEOUT, 5);
				$RawData = curl_exec($ch);
				curl_close($ch);
				
				$this->SendDebug('Raw Data: ', $RawData,0);
				$RawJSON = json_decode($RawData);
				
				
				If ($this->ReadPropertyInteger("ForecastShort") > "0")
				{
				$Narrative1 = $RawJSON->narrative[0];
				SetValue($this->GetIDForIdent("D1Forecast"), (string)$Narrative1);
				$QPF1 = $RawJSON->qpf[0];
				SetValue($this->GetIDForIdent("D1QPF"), (string)$QPF1);
				$QPFSNOW1 = $RawJSON->qpfSnow[0];
				SetValue($this->GetIDForIdent("D1QPFSNOW"), (string)$QPFSNOW1);	
				$TemperatureMax1 = $RawJSON->temperatureMax[0];
				SetValue($this->GetIDForIdent("D1TemperatureMax"), (float)$TemperatureMax1);
				$TemperatureMin1 = $RawJSON->temperatureMin[0];
				SetValue($this->GetIDForIdent("D1TemperatureMin"), (float)$TemperatureMin1);
				}
				
				If ($this->ReadPropertyInteger("ForecastShort") > "1")
				{
				$Narrative2 = $RawJSON->narrative[1];
				SetValue($this->GetIDForIdent("D2Forecast"), (string)$Narrative2);
				$QPF2 = $RawJSON->qpf[1];
				SetValue($this->GetIDForIdent("D2QPF"), (string)$QPF2);
				$QPFSNOW2 = $RawJSON->qpfSnow[1];
				SetValue($this->GetIDForIdent("D2QPFSNOW"), (string)$QPFSNOW2);	
				$TemperatureMax2 = $RawJSON->temperatureMax[1];
				SetValue($this->GetIDForIdent("D2TemperatureMax"), (float)$TemperatureMax2);
				$TemperatureMin2 = $RawJSON->temperatureMin[1];
				SetValue($this->GetIDForIdent("D2TemperatureMin"), (float)$TemperatureMin2);
				}
				
				If ($this->ReadPropertyInteger("ForecastShort") > "2")
				{
				$Narrative3 = $RawJSON->narrative[2];
				SetValue($this->GetIDForIdent("D3Forecast"), (string)$Narrative2);
				$QPF3 = $RawJSON->qpf[2];
				SetValue($this->GetIDForIdent("D3QPF"), (string)$QPF3);
				$QPFSNOW3 = $RawJSON->qpfSnow[2];
				SetValue($this->GetIDForIdent("D3QPFSNOW"), (string)$QPFSNOW3);	
				$TemperatureMax3 = $RawJSON->temperatureMax[2];
				SetValue($this->GetIDForIdent("D3TemperatureMax"), (float)$TemperatureMax3);
				$TemperatureMin3 = $RawJSON->temperatureMin[2];
				SetValue($this->GetIDForIdent("D3TemperatureMin"), (float)$TemperatureMin3);
				}
				
				If ($this->ReadPropertyInteger("ForecastShort") > "3")
				{
				$Narrative4 = $RawJSON->narrative[3];
				SetValue($this->GetIDForIdent("D4Forecast"), (string)$Narrative4);
				$QPF4 = $RawJSON->qpf[3];
				SetValue($this->GetIDForIdent("D4QPF"), (string)$QPF4);
				$QPFSNOW4 = $RawJSON->qpfSnow[3];
				SetValue($this->GetIDForIdent("D4QPFSNOW"), (string)$QPFSNOW4);	
				$TemperatureMax4 = $RawJSON->temperatureMax[3];
				SetValue($this->GetIDForIdent("D4TemperatureMax"), (float)$TemperatureMax4);
				$TemperatureMin4 = $RawJSON->temperatureMin[3];
				SetValue($this->GetIDForIdent("D4TemperatureMin"), (float)$TemperatureMin4);
				}
				
				If ($this->ReadPropertyInteger("ForecastShort") > "4")
				{
				$Narrative5 = $RawJSON->narrative[4];
				SetValue($this->GetIDForIdent("D5Forecast"), (string)$Narrative5);
				$QPF5 = $RawJSON->qpf[4];
				SetValue($this->GetIDForIdent("D5QPF"), (string)$QPF5);
				$QPFSNOW5 = $RawJSON->qpfSnow[4];
				SetValue($this->GetIDForIdent("D5QPFSNOW"), (string)$QPFSNOW5);	
				$TemperatureMax5 = $RawJSON->temperatureMax[4];
				SetValue($this->GetIDForIdent("D5TemperatureMax"), (float)$TemperatureMax5);
				$TemperatureMin5 = $RawJSON->temperatureMin[4];
				SetValue($this->GetIDForIdent("D5TemperatureMin"), (float)$TemperatureMin5);
				}
			
			// Detail Forecast Segments
			
			
				If ($this->ReadPropertyInteger("ForecastDP") > "0")
				{
				$DP0DN = $RawJSON->daypart[0]->dayOrNight[0];
				SetValue($this->GetIDForIdent("DP0DN"), (string)$DP0DN);
				$DP0Name = $RawJSON->daypart[0]->daypartName[0];
				SetValue($this->GetIDForIdent("DP0Name"), (string)$DP0Name);					
				$DP0Narrative = $RawJSON->daypart[0]->narrative[0];
				SetValue($this->GetIDForIdent("DP0Narrative"), (string)$DP0Narrative);
				$DP0PrecipChance = $RawJSON->daypart[0]->precipChance[1];
				SetValue($this->GetIDForIdent("DP0PrecipChance"), (string)$DP0PrecipChance);
				$DP0PrecipType = $RawJSON->daypart[0]->precipType[1];
				SetValue($this->GetIDForIdent("DP0PrecipType"), (string)$DP0PrecipType);
				
				
					If ($this->ReadPropertyBoolean("ForecastDPRain") == "1")
					{
					$DP0QPF = $RawJSON->daypart[0]->qpf[0];
					SetValue($this->GetIDForIdent("DP0QPF"), (string)$DP0QPF);
					$DP0QPFSNOW = $RawJSON->daypart[0]->qpfSnow[0];
					SetValue($this->GetIDForIdent("DP0QPFSNOW"), (string)$DP0QPFSNOW);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPTemperature") == "1")
					{
					$DP0Temperature = $RawJSON->daypart[0]->temperature[0];
					SetValue($this->GetIDForIdent("DP0Temperature"), (string)$DP0Temperature);
					$DP0WindChill = $RawJSON->daypart[0]->temperatureWindChill[0];
					SetValue($this->GetIDForIdent("DP0WindChill"), (string)$DP0WindChill);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPThunder") == "1")
					{
					$DP0Thunder = $RawJSON->daypart[0]->thunderCategory[0];
					SetValue($this->GetIDForIdent("DP0Thunder"), (string)$DP0Thunder);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					
					$DP0UVDescription = $RawJSON->daypart[0]->uvDescription[0];
					SetValue($this->GetIDForIdent("DP0UVDescription"), (string)$DP0UVDescription);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1")
					{
					$DP0UVIndex = $RawJSON->daypart[0]->uvIndex[0];
					SetValue($this->GetIDForIdent("DP0UVIndex"), (string)$DP0UVIndex);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1")
					{
					$DP0WINDDIR = $RawJSON->daypart[0]->windDirection[0];
					SetValue($this->GetIDForIdent("DP0WINDDIR"), (string)$DP0WINDDIR);
					$DP0WINDSpeed = $RawJSON->daypart[0]->windSpeed[0];
					SetValue($this->GetIDForIdent("DP0WINDSpeed"), (string)$DP0WINDSpeed);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					$DP0WINDDIRText = $RawJSON->daypart[0]->windDirectionCardinal[0];
					SetValue($this->GetIDForIdent("DP0WINDDIRText"), (string)$DP0WINDDIRText);
					$DP0WINDDIRPhrase = $RawJSON->daypart[0]->windPhrase[0];
					SetValue($this->GetIDForIdent("DP0WINDDIRPhrase"), (string)$DP0WINDDIRPhrase);
					}
							
				}
				
				If ($this->ReadPropertyInteger("ForecastDP") > 1)
				{
				$DP1DN = $RawJSON->daypart[0]->dayOrNight[1];
				SetValue($this->GetIDForIdent("DP1DN"), (string)$DP1DN);
				$DP1Name = $RawJSON->daypart[0]->daypartName[1];
				SetValue($this->GetIDForIdent("DP1Name"), (string)$DP1Name);					
				$DP1Narrative = $RawJSON->daypart[0]->narrative[1];
				SetValue($this->GetIDForIdent("DP1Narrative"), (string)$DP1Narrative);
				$DP1PrecipChance = $RawJSON->daypart[0]->precipChance[1];
				SetValue($this->GetIDForIdent("DP1PrecipChance"), (string)$DP1PrecipChance);
				$DP1PrecipType = $RawJSON->daypart[0]->precipType[1];
				SetValue($this->GetIDForIdent("DP1PrecipType"), (string)$DP1PrecipType);
				
				
					If ($this->ReadPropertyBoolean("ForecastDPRain") == "1")
					{
					$DP1QPF = $RawJSON->daypart[0]->qpf[1];
					SetValue($this->GetIDForIdent("DP1QPF"), (string)$DP1QPF);
					$DP1QPFSNOW = $RawJSON->daypart[0]->qpfSnow[1];
					SetValue($this->GetIDForIdent("DP1QPFSNOW"), (string)$DP1QPFSNOW);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPTemperature") == "1")
					{
					$DP1Temperature = $RawJSON->daypart[0]->temperature[1];
					SetValue($this->GetIDForIdent("DP1Temperature"), (string)$DP1Temperature);
					$DP1WindChill = $RawJSON->daypart[0]->temperatureWindChill[1];
					SetValue($this->GetIDForIdent("DP1WindChill"), (string)$DP1WindChill);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPThunder") == "1")
					{
					$DP1Thunder = $RawJSON->daypart[0]->thunderCategory[1];
					SetValue($this->GetIDForIdent("DP1Thunder"), (string)$DP1Thunder);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					
					$DP1UVDescription = $RawJSON->daypart[0]->uvDescription[1];
					SetValue($this->GetIDForIdent("DP1UVDescription"), (string)$DP1UVDescription);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1")
					{
					$DP1UVIndex = $RawJSON->daypart[0]->uvIndex[1];
					SetValue($this->GetIDForIdent("DP1UVIndex"), (string)$DP1UVIndex);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1")
					{
					$DP1WINDDIR = $RawJSON->daypart[0]->windDirection[1];
					SetValue($this->GetIDForIdent("DP1WINDDIR"), (string)$DP1WINDDIR);
					$DP1WINDSpeed = $RawJSON->daypart[0]->windSpeed[1];
					SetValue($this->GetIDForIdent("DP1WINDSpeed"), (string)$DP1WINDSpeed);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					$DP1WINDDIRText = $RawJSON->daypart[0]->windDirectionCardinal[1];
					SetValue($this->GetIDForIdent("DP1WINDDIRText"), (string)$DP1WINDDIRText);
					$DP1WINDDIRPhrase = $RawJSON->daypart[0]->windPhrase[1];
					SetValue($this->GetIDForIdent("DP1WINDDIRPhrase"), (string)$DP1WINDDIRPhrase);
					}
							
				}

				If ($this->ReadPropertyInteger("ForecastDP") > 2)
				{
				$DP2DN = $RawJSON->daypart[0]->dayOrNight[2];
				SetValue($this->GetIDForIdent("DP2DN"), (string)$DP2DN);
				$DP2Name = $RawJSON->daypart[0]->daypartName[2];
				SetValue($this->GetIDForIdent("DP2Name"), (string)$DP2Name);					
				$DP2Narrative = $RawJSON->daypart[0]->narrative[2];
				SetValue($this->GetIDForIdent("DP2Narrative"), (string)$DP2Narrative);
				$DP2PrecipChance = $RawJSON->daypart[0]->precipChance[2];
				SetValue($this->GetIDForIdent("DP2PrecipChance"), (string)$DP2PrecipChance);
				$DP2PrecipType = $RawJSON->daypart[0]->precipType[2];
				SetValue($this->GetIDForIdent("DP2PrecipType"), (string)$DP2PrecipType);
				
				
					If ($this->ReadPropertyBoolean("ForecastDPRain") == "1")
					{
					$DP2QPF = $RawJSON->daypart[0]->qpf[2];
					SetValue($this->GetIDForIdent("DP2QPF"), (string)$DP2QPF);
					$DP2QPFSNOW = $RawJSON->daypart[0]->qpfSnow[2];
					SetValue($this->GetIDForIdent("DP2QPFSNOW"), (string)$DP2QPFSNOW);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPTemperature") == "1")
					{
					$DP2Temperature = $RawJSON->daypart[0]->temperature[2];
					SetValue($this->GetIDForIdent("DP2Temperature"), (string)$DP2Temperature);
					$DP2WindChill = $RawJSON->daypart[0]->temperatureWindChill[2];
					SetValue($this->GetIDForIdent("DP2WindChill"), (string)$DP2WindChill);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPThunder") == "1")
					{
					$DP2Thunder = $RawJSON->daypart[0]->thunderCategory[2];
					SetValue($this->GetIDForIdent("DP2Thunder"), (string)$DP2Thunder);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					
					$DP2UVDescription = $RawJSON->daypart[0]->uvDescription[2];
					SetValue($this->GetIDForIdent("DP2UVDescription"), (string)$DP2UVDescription);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1")
					{
					$DP2UVIndex = $RawJSON->daypart[0]->uvIndex[2];
					SetValue($this->GetIDForIdent("DP2UVIndex"), (string)$DP2UVIndex);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1")
					{
					$DP2WINDDIR = $RawJSON->daypart[0]->windDirection[2];
					SetValue($this->GetIDForIdent("DP2WINDDIR"), (string)$DP2WINDDIR);
					$DP2WINDSpeed = $RawJSON->daypart[0]->windSpeed[2];
					SetValue($this->GetIDForIdent("DP2WINDSpeed"), (string)$DP2WINDSpeed);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					$DP2WINDDIRText = $RawJSON->daypart[0]->windDirectionCardinal[2];
					SetValue($this->GetIDForIdent("DP2WINDDIRText"), (string)$DP2WINDDIRText);
					$DP2WINDDIRPhrase = $RawJSON->daypart[0]->windPhrase[2];
					SetValue($this->GetIDForIdent("DP2WINDDIRPhrase"), (string)$DP2WINDDIRPhrase);
					}
							
				}
				
				If ($this->ReadPropertyInteger("ForecastDP") > 3)
				{
				$DP3DN = $RawJSON->daypart[0]->dayOrNight[3];
				SetValue($this->GetIDForIdent("DP3DN"), (string)$DP3DN);
				$DP3Name = $RawJSON->daypart[0]->daypartName[3];
				SetValue($this->GetIDForIdent("DP3Name"), (string)$DP3Name);					
				$DP3Narrative = $RawJSON->daypart[0]->narrative[3];
				SetValue($this->GetIDForIdent("DP3Narrative"), (string)$DP3Narrative);
				$DP3PrecipChance = $RawJSON->daypart[0]->precipChance[3];
				SetValue($this->GetIDForIdent("DP3PrecipChance"), (string)$DP3PrecipChance);
				$DP3PrecipType = $RawJSON->daypart[0]->precipType[3];
				SetValue($this->GetIDForIdent("DP3PrecipType"), (string)$DP3PrecipType);
				
				
					If ($this->ReadPropertyBoolean("ForecastDPRain") == "1")
					{
					$DP3QPF = $RawJSON->daypart[0]->qpf[3];
					SetValue($this->GetIDForIdent("DP3QPF"), (string)$DP3QPF);
					$DP3QPFSNOW = $RawJSON->daypart[0]->qpfSnow[3];
					SetValue($this->GetIDForIdent("DP3QPFSNOW"), (string)$DP3QPFSNOW);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPTemperature") == "1")
					{
					$DP3Temperature = $RawJSON->daypart[0]->temperature[3];
					SetValue($this->GetIDForIdent("DP3Temperature"), (string)$DP3Temperature);
					$DP3WindChill = $RawJSON->daypart[0]->temperatureWindChill[3];
					SetValue($this->GetIDForIdent("DP3WindChill"), (string)$DP3WindChill);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPThunder") == "1")
					{
					$DP3Thunder = $RawJSON->daypart[0]->thunderCategory[3];
					SetValue($this->GetIDForIdent("DP3Thunder"), (string)$DP3Thunder);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					
					$DP3UVDescription = $RawJSON->daypart[0]->uvDescription[3];
					SetValue($this->GetIDForIdent("DP3UVDescription"), (string)$DP3UVDescription);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1")
					{
					$DP3UVIndex = $RawJSON->daypart[0]->uvIndex[3];
					SetValue($this->GetIDForIdent("DP3UVIndex"), (string)$DP3UVIndex);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1")
					{
					$DP3WINDDIR = $RawJSON->daypart[0]->windDirection[3];
					SetValue($this->GetIDForIdent("DP3WINDDIR"), (string)$DP3WINDDIR);
					$DP3WINDSpeed = $RawJSON->daypart[0]->windSpeed[3];
					SetValue($this->GetIDForIdent("DP3WINDSpeed"), (string)$DP3WINDSpeed);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					$DP3WINDDIRText = $RawJSON->daypart[0]->windDirectionCardinal[3];
					SetValue($this->GetIDForIdent("DP3WINDDIRText"), (string)$DP3WINDDIRText);
					$DP3WINDDIRPhrase = $RawJSON->daypart[0]->windPhrase[3];
					SetValue($this->GetIDForIdent("DP3WINDDIRPhrase"), (string)$DP3WINDDIRPhrase);
					}
							
				}
				
				
				If ($this->ReadPropertyInteger("ForecastDP") > 4)
				{
				$DP4DN = $RawJSON->daypart[0]->dayOrNight[4];
				SetValue($this->GetIDForIdent("DP4DN"), (string)$DP4DN);
				$DP4Name = $RawJSON->daypart[0]->daypartName[4];
				SetValue($this->GetIDForIdent("DP4Name"), (string)$DP4Name);					
				$DP4Narrative = $RawJSON->daypart[0]->narrative[4];
				SetValue($this->GetIDForIdent("DP4Narrative"), (string)$DP4Narrative);
				$DP4PrecipChance = $RawJSON->daypart[0]->precipChance[4];
				SetValue($this->GetIDForIdent("DP4PrecipChance"), (string)$DP4PrecipChance);
				$DP4PrecipType = $RawJSON->daypart[0]->precipType[4];
				SetValue($this->GetIDForIdent("DP4PrecipType"), (string)$DP4PrecipType);
				
				
					If ($this->ReadPropertyBoolean("ForecastDPRain") == "1")
					{
					$DP4QPF = $RawJSON->daypart[0]->qpf[4];
					SetValue($this->GetIDForIdent("DP4QPF"), (string)$DP4QPF);
					$DP4QPFSNOW = $RawJSON->daypart[0]->qpfSnow[4];
					SetValue($this->GetIDForIdent("DP4QPFSNOW"), (string)$DP4QPFSNOW);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPTemperature") == "1")
					{
					$DP4Temperature = $RawJSON->daypart[0]->temperature[4];
					SetValue($this->GetIDForIdent("DP4Temperature"), (string)$DP4Temperature);
					$DP4WindChill = $RawJSON->daypart[0]->temperatureWindChill[4];
					SetValue($this->GetIDForIdent("DP4WindChill"), (string)$DP4WindChill);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPThunder") == "1")
					{
					$DP4Thunder = $RawJSON->daypart[0]->thunderCategory[4];
					SetValue($this->GetIDForIdent("DP4Thunder"), (string)$DP4Thunder);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					
					$DP4UVDescription = $RawJSON->daypart[0]->uvDescription[4];
					SetValue($this->GetIDForIdent("DP4UVDescription"), (string)$DP4UVDescription);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1")
					{
					$DP4UVIndex = $RawJSON->daypart[0]->uvIndex[4];
					SetValue($this->GetIDForIdent("DP4UVIndex"), (string)$DP4UVIndex);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1")
					{
					$DP4WINDDIR = $RawJSON->daypart[0]->windDirection[4];
					SetValue($this->GetIDForIdent("DP4WINDDIR"), (string)$DP4WINDDIR);
					$DP4WINDSpeed = $RawJSON->daypart[0]->windSpeed[4];
					SetValue($this->GetIDForIdent("DP4WINDSpeed"), (string)$DP4WINDSpeed);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					$DP4WINDDIRText = $RawJSON->daypart[0]->windDirectionCardinal[4];
					SetValue($this->GetIDForIdent("DP4WINDDIRText"), (string)$DP4WINDDIRText);
					$DP4WINDDIRPhrase = $RawJSON->daypart[0]->windPhrase[4];
					SetValue($this->GetIDForIdent("DP4WINDDIRPhrase"), (string)$DP4WINDDIRPhrase);
					}
							
				}
				
				If ($this->ReadPropertyInteger("ForecastDP") > 5)
				{
				$DP5DN = $RawJSON->daypart[0]->dayOrNight[5];
				SetValue($this->GetIDForIdent("DP5DN"), (string)$DP5DN);
				$DP5Name = $RawJSON->daypart[0]->daypartName[5];
				SetValue($this->GetIDForIdent("DP5Name"), (string)$DP5Name);					
				$DP5Narrative = $RawJSON->daypart[0]->narrative[5];
				SetValue($this->GetIDForIdent("DP5Narrative"), (string)$DP5Narrative);
				$DP5PrecipChance = $RawJSON->daypart[0]->precipChance[5];
				SetValue($this->GetIDForIdent("DP5PrecipChance"), (string)$DP5PrecipChance);
				$DP5PrecipType = $RawJSON->daypart[0]->precipType[5];
				SetValue($this->GetIDForIdent("DP5PrecipType"), (string)$DP5PrecipType);
				
				
					If ($this->ReadPropertyBoolean("ForecastDPRain") == "1")
					{
					$DP5QPF = $RawJSON->daypart[0]->qpf[5];
					SetValue($this->GetIDForIdent("DP5QPF"), (string)$DP5QPF);
					$DP5QPFSNOW = $RawJSON->daypart[0]->qpfSnow[5];
					SetValue($this->GetIDForIdent("DP5QPFSNOW"), (string)$DP5QPFSNOW);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPTemperature") == "1")
					{
					$DP5Temperature = $RawJSON->daypart[0]->temperature[5];
					SetValue($this->GetIDForIdent("DP5Temperature"), (string)$DP5Temperature);
					$DP5WindChill = $RawJSON->daypart[0]->temperatureWindChill[5];
					SetValue($this->GetIDForIdent("DP5WindChill"), (string)$DP5WindChill);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPThunder") == "1")
					{
					$DP5Thunder = $RawJSON->daypart[0]->thunderCategory[5];
					SetValue($this->GetIDForIdent("DP5Thunder"), (string)$DP5Thunder);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					
					$DP5UVDescription = $RawJSON->daypart[0]->uvDescription[5];
					SetValue($this->GetIDForIdent("DP5UVDescription"), (string)$DP5UVDescription);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1")
					{
					$DP5UVIndex = $RawJSON->daypart[0]->uvIndex[5];
					SetValue($this->GetIDForIdent("DP5UVIndex"), (string)$DP5UVIndex);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1")
					{
					$DP5WINDDIR = $RawJSON->daypart[0]->windDirection[5];
					SetValue($this->GetIDForIdent("DP5WINDDIR"), (string)$DP5WINDDIR);
					$DP5WINDSpeed = $RawJSON->daypart[0]->windSpeed[5];
					SetValue($this->GetIDForIdent("DP5WINDSpeed"), (string)$DP5WINDSpeed);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					$DP5WINDDIRText = $RawJSON->daypart[0]->windDirectionCardinal[5];
					SetValue($this->GetIDForIdent("DP5WINDDIRText"), (string)$DP5WINDDIRText);
					$DP5WINDDIRPhrase = $RawJSON->daypart[0]->windPhrase[5];
					SetValue($this->GetIDForIdent("DP5WINDDIRPhrase"), (string)$DP5WINDDIRPhrase);
					}
							
				}
				
				If ($this->ReadPropertyInteger("ForecastDP") > 6)
				{
				$DP6DN = $RawJSON->daypart[0]->dayOrNight[6];
				SetValue($this->GetIDForIdent("DP6DN"), (string)$DP6DN);
				$DP6Name = $RawJSON->daypart[0]->daypartName[6];
				SetValue($this->GetIDForIdent("DP6Name"), (string)$DP6Name);					
				$DP6Narrative = $RawJSON->daypart[0]->narrative[6];
				SetValue($this->GetIDForIdent("DP6Narrative"), (string)$DP6Narrative);
				$DP6PrecipChance = $RawJSON->daypart[0]->precipChance[6];
				SetValue($this->GetIDForIdent("DP6PrecipChance"), (string)$DP6PrecipChance);
				$DP6PrecipType = $RawJSON->daypart[0]->precipType[6];
				SetValue($this->GetIDForIdent("DP6PrecipType"), (string)$DP6PrecipType);
				
				
					If ($this->ReadPropertyBoolean("ForecastDPRain") == "1")
					{
					$DP6QPF = $RawJSON->daypart[0]->qpf[6];
					SetValue($this->GetIDForIdent("DP6QPF"), (string)$DP6QPF);
					$DP6QPFSNOW = $RawJSON->daypart[0]->qpfSnow[6];
					SetValue($this->GetIDForIdent("DP6QPFSNOW"), (string)$DP6QPFSNOW);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPTemperature") == "1")
					{
					$DP6Temperature = $RawJSON->daypart[0]->temperature[6];
					SetValue($this->GetIDForIdent("DP6Temperature"), (string)$DP6Temperature);
					$DP6WindChill = $RawJSON->daypart[0]->temperatureWindChill[6];
					SetValue($this->GetIDForIdent("DP6WindChill"), (string)$DP6WindChill);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPThunder") == "1")
					{
					$DP6Thunder = $RawJSON->daypart[0]->thunderCategory[6];
					SetValue($this->GetIDForIdent("DP6Thunder"), (string)$DP6Thunder);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					
					$DP6UVDescription = $RawJSON->daypart[0]->uvDescription[6];
					SetValue($this->GetIDForIdent("DP6UVDescription"), (string)$DP6UVDescription);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1")
					{
					$DP6UVIndex = $RawJSON->daypart[0]->uvIndex[6];
					SetValue($this->GetIDForIdent("DP6UVIndex"), (string)$DP6UVIndex);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1")
					{
					$DP6WINDDIR = $RawJSON->daypart[0]->windDirection[6];
					SetValue($this->GetIDForIdent("DP6WINDDIR"), (string)$DP6WINDDIR);
					$DP6WINDSpeed = $RawJSON->daypart[0]->windSpeed[6];
					SetValue($this->GetIDForIdent("DP6WINDSpeed"), (string)$DP6WINDSpeed);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					$DP6WINDDIRText = $RawJSON->daypart[0]->windDirectionCardinal[6];
					SetValue($this->GetIDForIdent("DP6WINDDIRText"), (string)$DP6WINDDIRText);
					$DP6WINDDIRPhrase = $RawJSON->daypart[0]->windPhrase[6];
					SetValue($this->GetIDForIdent("DP6WINDDIRPhrase"), (string)$DP6WINDDIRPhrase);
					}
							
				}
				
				If ($this->ReadPropertyInteger("ForecastDP") > 7)
				{
				$DP7DN = $RawJSON->daypart[0]->dayOrNight[7];
				SetValue($this->GetIDForIdent("DP7DN"), (string)$DP7DN);
				$DP7Name = $RawJSON->daypart[0]->daypartName[7];
				SetValue($this->GetIDForIdent("DP7Name"), (string)$DP7Name);					
				$DP7Narrative = $RawJSON->daypart[0]->narrative[7];
				SetValue($this->GetIDForIdent("DP7Narrative"), (string)$DP7Narrative);
				$DP7PrecipChance = $RawJSON->daypart[0]->precipChance[7];
				SetValue($this->GetIDForIdent("DP7PrecipChance"), (string)$DP7PrecipChance);
				$DP7PrecipType = $RawJSON->daypart[0]->precipType[7];
				SetValue($this->GetIDForIdent("DP7PrecipType"), (string)$DP7PrecipType);
				
				
					If ($this->ReadPropertyBoolean("ForecastDPRain") == "1")
					{
					$DP7QPF = $RawJSON->daypart[0]->qpf[7];
					SetValue($this->GetIDForIdent("DP7QPF"), (string)$DP7QPF);
					$DP7QPFSNOW = $RawJSON->daypart[0]->qpfSnow[7];
					SetValue($this->GetIDForIdent("DP7QPFSNOW"), (string)$DP7QPFSNOW);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPTemperature") == "1")
					{
					$DP7Temperature = $RawJSON->daypart[0]->temperature[7];
					SetValue($this->GetIDForIdent("DP7Temperature"), (string)$DP7Temperature);
					$DP7WindChill = $RawJSON->daypart[0]->temperatureWindChill[7];
					SetValue($this->GetIDForIdent("DP7WindChill"), (string)$DP7WindChill);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPThunder") == "1")
					{
					$DP7Thunder = $RawJSON->daypart[0]->thunderCategory[7];
					SetValue($this->GetIDForIdent("DP7Thunder"), (string)$DP7Thunder);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					
					$DP7UVDescription = $RawJSON->daypart[0]->uvDescription[7];
					SetValue($this->GetIDForIdent("DP7UVDescription"), (string)$DP7UVDescription);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1")
					{
					$DP7UVIndex = $RawJSON->daypart[0]->uvIndex[7];
					SetValue($this->GetIDForIdent("DP7UVIndex"), (string)$DP7UVIndex);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1")
					{
					$DP7WINDDIR = $RawJSON->daypart[0]->windDirection[7];
					SetValue($this->GetIDForIdent("DP7WINDDIR"), (string)$DP7WINDDIR);
					$DP7WINDSpeed = $RawJSON->daypart[0]->windSpeed[7];
					SetValue($this->GetIDForIdent("DP7WINDSpeed"), (string)$DP7WINDSpeed);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					$DP7WINDDIRText = $RawJSON->daypart[0]->windDirectionCardinal[7];
					SetValue($this->GetIDForIdent("DP7WINDDIRText"), (string)$DP7WINDDIRText);
					$DP7WINDDIRPhrase = $RawJSON->daypart[0]->windPhrase[7];
					SetValue($this->GetIDForIdent("DP7WINDDIRPhrase"), (string)$DP7WINDDIRPhrase);
					}
							
				}
				
				If ($this->ReadPropertyInteger("ForecastDP") > 8)
				{
				$DP8DN = $RawJSON->daypart[0]->dayOrNight[8];
				SetValue($this->GetIDForIdent("DP8DN"), (string)$DP8DN);
				$DP8Name = $RawJSON->daypart[0]->daypartName[8];
				SetValue($this->GetIDForIdent("DP8Name"), (string)$DP8Name);					
				$DP8Narrative = $RawJSON->daypart[0]->narrative[8];
				SetValue($this->GetIDForIdent("DP8Narrative"), (string)$DP8Narrative);
				$DP8PrecipChance = $RawJSON->daypart[0]->precipChance[8];
				SetValue($this->GetIDForIdent("DP8PrecipChance"), (string)$DP8PrecipChance);
				$DP8PrecipType = $RawJSON->daypart[0]->precipType[8];
				SetValue($this->GetIDForIdent("DP8PrecipType"), (string)$DP8PrecipType);
				
				
					If ($this->ReadPropertyBoolean("ForecastDPRain") == "1")
					{
					$DP8QPF = $RawJSON->daypart[0]->qpf[8];
					SetValue($this->GetIDForIdent("DP8QPF"), (string)$DP8QPF);
					$DP8QPFSNOW = $RawJSON->daypart[0]->qpfSnow[8];
					SetValue($this->GetIDForIdent("DP8QPFSNOW"), (string)$DP8QPFSNOW);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPTemperature") == "1")
					{
					$DP8Temperature = $RawJSON->daypart[0]->temperature[8];
					SetValue($this->GetIDForIdent("DP8Temperature"), (string)$DP8Temperature);
					$DP8WindChill = $RawJSON->daypart[0]->temperatureWindChill[8];
					SetValue($this->GetIDForIdent("DP8WindChill"), (string)$DP8WindChill);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPThunder") == "1")
					{
					$DP8Thunder = $RawJSON->daypart[0]->thunderCategory[8];
					SetValue($this->GetIDForIdent("DP8Thunder"), (string)$DP8Thunder);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					
					$DP8UVDescription = $RawJSON->daypart[0]->uvDescription[8];
					SetValue($this->GetIDForIdent("DP8UVDescription"), (string)$DP8UVDescription);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1")
					{
					$DP8UVIndex = $RawJSON->daypart[0]->uvIndex[8];
					SetValue($this->GetIDForIdent("DP8UVIndex"), (string)$DP8UVIndex);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1")
					{
					$DP8WINDDIR = $RawJSON->daypart[0]->windDirection[8];
					SetValue($this->GetIDForIdent("DP8WINDDIR"), (string)$DP8WINDDIR);
					$DP8WINDSpeed = $RawJSON->daypart[0]->windSpeed[8];
					SetValue($this->GetIDForIdent("DP8WINDSpeed"), (string)$DP8WINDSpeed);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					$DP8WINDDIRText = $RawJSON->daypart[0]->windDirectionCardinal[8];
					SetValue($this->GetIDForIdent("DP8WINDDIRText"), (string)$DP8WINDDIRText);
					$DP8WINDDIRPhrase = $RawJSON->daypart[0]->windPhrase[8];
					SetValue($this->GetIDForIdent("DP8WINDDIRPhrase"), (string)$DP8WINDDIRPhrase);
					}
							
				}
				
				If ($this->ReadPropertyInteger("ForecastDP") > 9)
				{
				$DP9DN = $RawJSON->daypart[0]->dayOrNight[9];
				SetValue($this->GetIDForIdent("DP9DN"), (string)$DP9DN);
				$DP9Name = $RawJSON->daypart[0]->daypartName[9];
				SetValue($this->GetIDForIdent("DP9Name"), (string)$DP9Name);					
				$DP9Narrative = $RawJSON->daypart[0]->narrative[9];
				SetValue($this->GetIDForIdent("DP9Narrative"), (string)$DP9Narrative);
				$DP9PrecipChance = $RawJSON->daypart[0]->precipChance[9];
				SetValue($this->GetIDForIdent("DP9PrecipChance"), (string)$DP9PrecipChance);
				$DP9PrecipType = $RawJSON->daypart[0]->precipType[9];
				SetValue($this->GetIDForIdent("DP9PrecipType"), (string)$DP9PrecipType);
				
				
					If ($this->ReadPropertyBoolean("ForecastDPRain") == "1")
					{
					$DP9QPF = $RawJSON->daypart[0]->qpf[9];
					SetValue($this->GetIDForIdent("DP9QPF"), (string)$DP9QPF);
					$DP9QPFSNOW = $RawJSON->daypart[0]->qpfSnow[9];
					SetValue($this->GetIDForIdent("DP9QPFSNOW"), (string)$DP9QPFSNOW);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPTemperature") == "1")
					{
					$DP9Temperature = $RawJSON->daypart[0]->temperature[9];
					SetValue($this->GetIDForIdent("DP9Temperature"), (string)$DP9Temperature);
					$DP9WindChill = $RawJSON->daypart[0]->temperatureWindChill[9];
					SetValue($this->GetIDForIdent("DP9WindChill"), (string)$DP9WindChill);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPThunder") == "1")
					{
					$DP9Thunder = $RawJSON->daypart[0]->thunderCategory[9];
					SetValue($this->GetIDForIdent("DP9Thunder"), (string)$DP9Thunder);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					
					$DP9UVDescription = $RawJSON->daypart[0]->uvDescription[9];
					SetValue($this->GetIDForIdent("DP9UVDescription"), (string)$DP9UVDescription);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1")
					{
					$DP9UVIndex = $RawJSON->daypart[0]->uvIndex[9];
					SetValue($this->GetIDForIdent("DP9UVIndex"), (string)$DP9UVIndex);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1")
					{
					$DP9WINDDIR = $RawJSON->daypart[0]->windDirection[9];
					SetValue($this->GetIDForIdent("DP9WINDDIR"), (string)$DP9WINDDIR);
					$DP9WINDSpeed = $RawJSON->daypart[0]->windSpeed[9];
					SetValue($this->GetIDForIdent("DP9WINDSpeed"), (string)$DP9WINDSpeed);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					$DP9WINDDIRText = $RawJSON->daypart[0]->windDirectionCardinal[9];
					SetValue($this->GetIDForIdent("DP9WINDDIRText"), (string)$DP9WINDDIRText);
					$DP9WINDDIRPhrase = $RawJSON->daypart[0]->windPhrase[9];
					SetValue($this->GetIDForIdent("DP9WINDDIRPhrase"), (string)$DP9WINDDIRPhrase);
					}
							
				}
				
				If ($this->ReadPropertyInteger("ForecastDP") > 10)
				{
				$DP10DN = $RawJSON->daypart[0]->dayOrNight[10];
				SetValue($this->GetIDForIdent("DP10DN"), (string)$DP10DN);
				$DP10Name = $RawJSON->daypart[0]->daypartName[10];
				SetValue($this->GetIDForIdent("DP10Name"), (string)$DP10Name);					
				$DP10Narrative = $RawJSON->daypart[0]->narrative[10];
				SetValue($this->GetIDForIdent("DP10Narrative"), (string)$DP10Narrative);
				$DP10PrecipChance = $RawJSON->daypart[0]->precipChance[10];
				SetValue($this->GetIDForIdent("DP10PrecipChance"), (string)$DP10PrecipChance);
				$DP10PrecipType = $RawJSON->daypart[0]->precipType[10];
				SetValue($this->GetIDForIdent("DP10PrecipType"), (string)$DP10PrecipType);
				
				
					If ($this->ReadPropertyBoolean("ForecastDPRain") == "1")
					{
					$DP10QPF = $RawJSON->daypart[0]->qpf[10];
					SetValue($this->GetIDForIdent("DP10QPF"), (string)$DP10QPF);
					$DP10QPFSNOW = $RawJSON->daypart[0]->qpfSnow[10];
					SetValue($this->GetIDForIdent("DP10QPFSNOW"), (string)$DP10QPFSNOW);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPTemperature") == "1")
					{
					$DP10Temperature = $RawJSON->daypart[0]->temperature[10];
					SetValue($this->GetIDForIdent("DP10Temperature"), (string)$DP10Temperature);
					$DP10WindChill = $RawJSON->daypart[0]->temperatureWindChill[10];
					SetValue($this->GetIDForIdent("DP10WindChill"), (string)$DP10WindChill);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPThunder") == "1")
					{
					$DP10Thunder = $RawJSON->daypart[0]->thunderCategory[10];
					SetValue($this->GetIDForIdent("DP10Thunder"), (string)$DP10Thunder);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					
					$DP10UVDescription = $RawJSON->daypart[0]->uvDescription[10];
					SetValue($this->GetIDForIdent("DP10UVDescription"), (string)$DP10UVDescription);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1")
					{
					$DP10UVIndex = $RawJSON->daypart[0]->uvIndex[10];
					SetValue($this->GetIDForIdent("DP10UVIndex"), (string)$DP10UVIndex);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1")
					{
					$DP10WINDDIR = $RawJSON->daypart[0]->windDirection[10];
					SetValue($this->GetIDForIdent("DP10WINDDIR"), (string)$DP10WINDDIR);
					$DP10WINDSpeed = $RawJSON->daypart[0]->windSpeed[10];
					SetValue($this->GetIDForIdent("DP10WINDSpeed"), (string)$DP10WINDSpeed);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					$DP10WINDDIRText = $RawJSON->daypart[0]->windDirectionCardinal[10];
					SetValue($this->GetIDForIdent("DP10WINDDIRText"), (string)$DP10WINDDIRText);
					$DP10WINDDIRPhrase = $RawJSON->daypart[0]->windPhrase[10];
					SetValue($this->GetIDForIdent("DP10WINDDIRPhrase"), (string)$DP10WINDDIRPhrase);
					}
							
				}
				
				If ($this->ReadPropertyInteger("ForecastDP") > 11)
				{
				$DP11DN = $RawJSON->daypart[0]->dayOrNight[11];
				SetValue($this->GetIDForIdent("DP11DN"), (string)$DP11DN);
				$DP11Name = $RawJSON->daypart[0]->daypartName[11];
				SetValue($this->GetIDForIdent("DP11Name"), (string)$DP11Name);					
				$DP11Narrative = $RawJSON->daypart[0]->narrative[11];
				SetValue($this->GetIDForIdent("DP11Narrative"), (string)$DP11Narrative);
				$DP11PrecipChance = $RawJSON->daypart[0]->precipChance[11];
				SetValue($this->GetIDForIdent("DP11PrecipChance"), (string)$DP11PrecipChance);
				$DP11PrecipType = $RawJSON->daypart[0]->precipType[11];
				SetValue($this->GetIDForIdent("DP11PrecipType"), (string)$DP11PrecipType);
				
				
					If ($this->ReadPropertyBoolean("ForecastDPRain") == "1")
					{
					$DP11QPF = $RawJSON->daypart[0]->qpf[11];
					SetValue($this->GetIDForIdent("DP11QPF"), (string)$DP11QPF);
					$DP11QPFSNOW = $RawJSON->daypart[0]->qpfSnow[11];
					SetValue($this->GetIDForIdent("DP11QPFSNOW"), (string)$DP11QPFSNOW);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPTemperature") == "1")
					{
					$DP11Temperature = $RawJSON->daypart[0]->temperature[11];
					SetValue($this->GetIDForIdent("DP11Temperature"), (string)$DP11Temperature);
					$DP11WindChill = $RawJSON->daypart[0]->temperatureWindChill[11];
					SetValue($this->GetIDForIdent("DP11WindChill"), (string)$DP11WindChill);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPThunder") == "1")
					{
					$DP11Thunder = $RawJSON->daypart[0]->thunderCategory[11];
					SetValue($this->GetIDForIdent("DP11Thunder"), (string)$DP11Thunder);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					
					$DP11UVDescription = $RawJSON->daypart[0]->uvDescription[11];
					SetValue($this->GetIDForIdent("DP11UVDescription"), (string)$DP11UVDescription);
					}
				
					If ($this->ReadPropertyBoolean("ForecastDPUV") == "1")
					{
					$DP11UVIndex = $RawJSON->daypart[0]->uvIndex[11];
					SetValue($this->GetIDForIdent("DP11UVIndex"), (string)$DP11UVIndex);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1")
					{
					$DP11WINDDIR = $RawJSON->daypart[0]->windDirection[11];
					SetValue($this->GetIDForIdent("DP11WINDDIR"), (string)$DP11WINDDIR);
					$DP11WINDSpeed = $RawJSON->daypart[0]->windSpeed[11];
					SetValue($this->GetIDForIdent("DP11WINDSpeed"), (string)$DP11WINDSpeed);
					}
					
					If ($this->ReadPropertyBoolean("ForecastDPWind") == "1" AND $this->ReadPropertyBoolean("ForecastDPNarrative") == "1")
					{
					$DP11WINDDIRText = $RawJSON->daypart[0]->windDirectionCardinal[11];
					SetValue($this->GetIDForIdent("DP11WINDDIRText"), (string)$DP11WINDDIRText);
					$DP11WINDDIRPhrase = $RawJSON->daypart[0]->windPhrase[11];
					SetValue($this->GetIDForIdent("DP11WINDDIRPhrase"), (string)$DP11WINDDIRPhrase);
					}
							
				}
				
			
		}
	
	
		
		public function UploadToWunderground()
		{
		
		
		// Prepare Temperature for upload
		
		$Debug = $this->ReadPropertyBoolean("Debug");
		
		If ($this->ReadPropertyInteger("OutsideTemperature") != "")
		{
		$Temperature = GetValue($this->ReadPropertyInteger("OutsideTemperature"));
		$TemperatureF = str_replace(",",".",(($Temperature * 9) /5 + 32));
		$this->SendDebug("Wunderground PWS Update","Wunderground Upload Temperature F: ".$TemperatureF, 0);	
		}
		
		ElseIf ($this->ReadPropertyInteger("OutsideTemperature") == "0")
		{
		$TemperatureF = "";
		}
		
		// Prepare Dewpoint for upload
				
		If ($this->ReadPropertyInteger("DewPoint") != "")
		{
		$DewPoint = GetValue($this->ReadPropertyInteger("DewPoint"));
		$DewPointF = str_replace(",",".",(($DewPoint * 9) /5 + 32));
		$this->SendDebug("Wunderground PWS Update","Wunderground Upload Taupunkt F: ".$DewPointF, 0);
		}
		
		ElseIf ($this->ReadPropertyInteger("DewPoint") == "0")
		{
		$DewPointF = "";
		}

		// Prepare Humidity for upload
				
		If ($this->ReadPropertyInteger("Humidity") != "")
		{
		$Humidity = GetValue($this->ReadPropertyInteger("Humidity"));
		$this->SendDebug("Wunderground PWS Update","Wunderground Upload Humidity: ".$Humidity, 0);
		}
		
		ElseIf ($this->ReadPropertyInteger("Humidity") == "0")
		{
		$Humidity = "";
		}
			
		// Prepare Windirection for upload
				
		If ($this->ReadPropertyInteger("WindDirection") != "")
		{
		$WindDirection = GetValue($this->ReadPropertyInteger("WindDirection"));
		$WindDirectionU = str_replace(",",".",$WindDirection);
		$this->SendDebug("Wunderground PWS Update","Wunderground Upload Wind Direction: ".$WindDirectionU, 0);		
		}
		
		ElseIf ($this->ReadPropertyInteger("WindDirection") == "0")
		{
		$WindDirectionU = "";
		}

		// Prepare Windspeed for upload
				
		If ($this->ReadPropertyInteger("WindSpeed") != "")
		{
		$WindSpeed = GetValue($this->ReadPropertyInteger("WindSpeed"));
		$WindSpeedU = str_replace(",",".",Round(($WindSpeed * 2.2369),2));
		$this->SendDebug("Wunderground PWS Update","Wunderground Upload Windspeed: ".$WindSpeedU, 0);
		}
		
		ElseIf ($this->ReadPropertyInteger("WindSpeed") == "0")
		{
		$WindSpeedU = "";
		}
		
		// Prepare Windgust for upload
				
		If ($this->ReadPropertyInteger("WindGust") != "")
		{
		$WindGust = GetValue($this->ReadPropertyInteger("WindGust"));
		$WindGustU = str_replace(",",".",Round(($WindGust * 2.2369),2));
		$this->SendDebug("Wunderground PWS Update","Wunderground Upload Wind Gust: ".$WindGustU, 0);
		}
		
		ElseIf ($this->ReadPropertyInteger("WindGust") == "0")
		{
		$WindGustU = "";
		}

		
		// Prepare Rain last hour for upload
				
		If ($this->ReadPropertyInteger("Rain_last_Hour") != "")
		{
		$Rain_last_Hour = GetValue($this->ReadPropertyInteger("Rain_last_Hour"));
		$Rain_last_Hour = str_replace(",",".",Round(($Rain_last_Hour / 2.54),2));
		$this->SendDebug("Wunderground PWS Update","Wunderground Upload Rain Last Hour: ".$Rain_last_Hour, 0);
		}
		
		ElseIf ($this->ReadPropertyInteger("Rain_last_Hour") == "0")
		{
		$Rain_last_Hour = "";
		}

		// Prepare Rain 24h for upload
				
		If ($this->ReadPropertyInteger("Rain24h") != "")
		{
		$Rain24h = GetValue($this->ReadPropertyInteger("Rain24h"));
		$Rain24h = str_replace(",",".",Round(($Rain24h / 2.54),2));
		$this->SendDebug("Wunderground PWS Update","Wunderground Upload Rain in 24h: ".$Rain24h, 0);
		}
		
		ElseIf ($this->ReadPropertyInteger("Rain24h") == "0")
		{
		$Rain24h = "";
		}

		// Prepare Airpressure for upload
				
		If ($this->ReadPropertyInteger("AirPressure") != "")
		{
		$AirPressure = GetValue($this->ReadPropertyInteger("AirPressure"));
		$BPI = str_replace(",",".",Round(($AirPressure * 0.0295299830714),4));
		$this->SendDebug("Wunderground PWS Update","Wunderground Upload Airpressure in BPI: ".$AirPressure, 0);
		}
		
		ElseIf ($this->ReadPropertyInteger("AirPressure") == "0")
		{
		$BPI = "";
		}

		// Prepare UV Index for upload
				
		If ($this->ReadPropertyInteger("UVIndex") != "")
		{
		$UVIndex = GetValue($this->ReadPropertyInteger("UVIndex"));
		$this->SendDebug("Wunderground PWS Update","Wunderground Upload UV Index: ".$UVIndex, 0);
		}
		
		ElseIf ($this->ReadPropertyInteger("UVIndex") == "0")
		{
		$UVIndex = "";
		}

			
		// setting standard values like time and login
		
		$WU_ID = $this->ReadPropertyString("WU_ID");
		$WU_Password = $this->ReadPropertyString("WU_Password");
		
		$date = date('Y-m-d');
		$hour = date('H');
		$minute = date('i');
		$second = date('s');
		$time = $date.'+'.$hour.'%3A'.$minute.'%3A'.$second;
		
		
		// Upload to Wunderground
		$Response =file_get_contents('https://weatherstation.wunderground.com/weatherstation/updateweatherstation.php?ID='.$WU_ID."&PASSWORD=".$WU_Password."&dateutc=".$time.
		"&tempf=".$TemperatureF.
		"&dewptf=".$DewPointF.
		"&winddir=".$WindDirectionU.
		"&humidity=".$Humidity.
		"&windspeedmph=".$WindSpeedU.
		"&windgustmph=".$WindGustU.
		"&rainin=".$Rain_last_Hour.
		"&dailyrainin=".$Rain24h.
		"&baromin=".$BPI.
		"&UV=".$UVIndex);
		
		$this->SendDebug("Wunderground PWS Update","Wunderground Upload Service: ".$Response, 0);
		
		}
	
	}

?>
