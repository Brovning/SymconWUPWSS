## Symcon - Wunderground PWS Sync & Forecast Module (new WU API)
Based upon the Wunderground API this module allows to 

* upload weather data from a PWS (personal weather station)
* download weather data from a PWS based upon the station ID
* download a highlevel forecast
* download a detail foreast in 12h segments - max 5 days based upon geo data

# Needed things
An API key (new): https://www.wunderground.com/member/api-keys

IMPORTANT: This creation of the key will only be available post a first upload of weather data - outside temperature should be enough. 

# Requirements
IP-Symcon from Version 4.x

# Software-Installation
Via Module-Control using the following URL: https://github.com/elueckel/SymconWUPWSS

# Setup of the Instance in IP-Symcon
Add the Instance "WundergroundPWSSync" - manufacturer is "Other"

### Version 1.0 12/02/2018
* Upload of weatherdata to Wunderground
* Login via Station ID and API Key
* Picklist with local sensor data 
* Upload configurable in seconds 

### Version 2.0 17/03/2019
* Update using the new Wunderground API
* Login via new API key
* Download of a high level forecast of up to 5 days (based on API key and Geodata)
* Download of a detail weather forecast in 12h segments ((7am - 7pm day / 7pm - 7am night) - Timer runs at 7 am and 7pm 
* Download of current weather data from another PWS based upon the station ID
* Detail weather is configurable (carefull - if all is selected, over 200 variables will be created)
* Timer for Upload configurable in seconds
* Timer for Forecast runs at 7:05 and by default every 12h (configurable in hours)
* Timer for downloading data is configurable in minutes 
* Possibility to download the raw data in a JSON file 
* Test function for all 3 segments

### Version 2.1 31/03/2019
* New cloud cover is provided in the detail forecast
* New download of weather data is seperate from upload using a second station ID (allowing to enrich local data from another PWS)
* New possibility to select windspeed in m/s or km/h to e.g. support the Homematic OC3 Weather Station

### Version 2.1.1 07-04-2019
* Bugfix when downloading from another station

### Version 2.1.2 12-04-2019
* Change for timer now runs hourly internal (change required to get the module into the module store - in case the existing time does not get deleted please do so manually)
* Bugfix profile for weather download now in kmh

### Version 2.1.3 22-04-2019
* Change CURL timer set to 10 seconds

# IMPORTANT:
Sometime not all data points are fill by the WU API turning those value into NULL - in this case the module will keep the already existing data.
The maximum number of calls is 1500 per day or 30 per minute for downloading data
Komplette Doku für Weather.com API: https://docs.google.com/document/d/1eKCnKXI9xnoMGRRzOL1xPCBihNV2rOet08qpE_gArAY/edit

Complete documentation at Wunderground: http://wiki.wunderground.com/index.php/PWS_-_Upload_Protocol
