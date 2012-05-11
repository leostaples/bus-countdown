<?php
namespace Bus;
include_once(__DIR__.'/../../vendor/simple_html_dom/simple_html_dom.php');

class Countdown {
    private $countdownUrl = 'http://countdown.tfl.gov.uk/stopBoard/';
    private $stopUrl = 'http://m.countdown.tfl.gov.uk/arrivals/';
    //private $nearestUrl = 'http://m.countdown.tfl.gov.uk/stopsNearCoordinates/';
    private $nearestUrl = 'http://countdown.tfl.gov.uk/markers/';

    
    private $curlOpts = array (
    	CURLOPT_RETURNTRANSFER => 1,
    	CURLOPT_CONNECTTIMEOUT => 10
	);

    public function getCountdownJson($stopId){
		$ch = curl_init($this->countdownUrl . $stopId);
		curl_setopt_array($ch, $this->curlOpts);
		$response = curl_exec($ch);   
		curl_close($ch);  

		return $response;
	}
	
	/*public function getNearestStops($lat, $lon){
		//scrape 3 nearest stops - e.g. m.countdown.tfl.gov.uk/stopsNearCoordinates/51.5067415,-0.2266609	
		$ch = curl_init($this->nearestUrl . $lat .','. $lon);
		curl_setopt_array($ch, $this->curlOpts);
		$response = curl_exec($ch);   
		curl_close($ch);  

		$indexHTML = str_get_html($response); 
		
		$stopRows = $indexHTML->find('tr[id^="stopPoint-"]');
		$stopRows = array_slice($stopRows, 0, 3);
		
		$stopData['stops'] = array();
		foreach ($stopRows as $stopRow){
			$id = str_replace('stopPoint-', '', $stopRow->id);
			$name = $stopRow->find('td[class="information"]',0)->children(0)->plaintext;	
			$towards = $stopRow->find('td[class="information"]',0)->children(1)->plaintext;	
			
			$stop = array('id'=>$id, 'name'=>$name, 'towards'=>$towards);
			
			array_push($stopData['stops'], $stop);
		}
		
		return json_encode($stopData);
	}*/
	
	public function getNearestStops($lat, $lng){
        $swLat = $lat - 0.005;
        $swLng = $lng - 0.005;
        $neLat = $lat + 0.005;
        $neLng = $lng + 0.005;
                
		$ch = curl_init($this->nearestUrl . 'swLat/' . $swLat . '/swLng/' . $swLng . '/neLat/' . $neLat . '/neLng/' . $neLng . '/');
		curl_setopt_array($ch, $this->curlOpts);
		$response = curl_exec($ch);   
		curl_close($ch);  
		
		$data = json_decode($response);
		
		$markers = $data->markers;
		
		foreach($data->markers as $marker)
		{
			$distance = $this->getDistance($lat, $lng, $marker->lat, $marker->lng);
			$marker->distance = $distance;
		}
		
		$this->osort($data->markers, 'distance');
		
		array_splice($data->markers, 3);
				
		return json_encode($data);
	}	
	
	public function osort(&$array, $prop)
	{
	    usort($array, function($a, $b) use ($prop) {
	        return $a->$prop > $b->$prop ? 1 : -1;
	    }); 
	}

	public function distanceSort( $a, $b ) {
	    return $a->distance == $b->distance ? 0 : ( $a->distance > $b->distance ) ? 1 : -1;
	}

	public function getDistance($latitude1, $longitude1, $latitude2, $longitude2) {
		$earth_radius = 6371;
		
		$dLat = deg2rad($latitude2 - $latitude1);
		$dLon = deg2rad($longitude2 - $longitude1);
		
		$a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) * sin($dLon/2) * sin($dLon/2);
		$c = 2 * asin(sqrt($a));
		$d = $earth_radius * $c;
		
		return $d;
	}
	
	public function getStopInfo($stop){
		$stopIds = explode(",", $stop);
		
		$stopData = array();
		
		foreach($stopIds as $stopId)
		{
			$ch = curl_init($this->stopUrl . $stopId);
			curl_setopt_array($ch, $this->curlOpts);
			$response = curl_exec($ch);   
			curl_close($ch);  

			$indexHTML = str_get_html($response); 	

			$name = $indexHTML->find('span[class="stopInfo"]',0);
			$direction = $indexHTML->find('div[class="constrainToMap"]',0)->prev_sibling();

			$stopInfo['id'] = $stopId;
			$stopInfo['name'] = $name->plaintext;
			$stopInfo['direction'] = htmlspecialchars_decode($direction->plaintext, ENT_QUOTES);
			
			array_push($stopData, $stopInfo);
		}

		return $stopData;
	}
}
