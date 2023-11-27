<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;

class TBOController extends Controller
{

    function getHeader()
    {
        $Header = [];

        $Header['Accept'] = 'application/json';
        // $Header['Api-key'] = $this->api_key;
        // $Header['X-Signature'] = $this->getSignature();
        $Header['Content-Type'] = 'application/json';

        return $Header;
    }


    public function getDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo)
    {
        $earthRadius = 6371000; //km * 1000
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $val = (pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2));
        $res = 2 * asin(sqrt($val));

        // $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        //     cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return round($res * $earthRadius, 2);
    }

    public function basicHotel(Request $request)
    {

        $authArray['ClientId'] = "ApiIntegrationNew";
        $authArray['UserName'] = "Sharmila1";
        $authArray['Password'] = "Sharmila@1234";
        $authArray['EndUserIp'] = $request->ip();



        $hotelOrigin = DB::table('tbl_hotel')->get();

        // return $sub_array;

        $response = Http::withHeaders($this->getHeader())
            ->post('http://api.tektravels.com/SharedServices/SharedData.svc/rest/Authenticate', $authArray)->json();


        $requestedDataSet = [
            'CityId' => '144745',
            'ClientId' => 'ApiIntegrationNew',
            'EndUserIp' => $request->ip(),
            'TokenId' => $response['TokenId'],
            "IsCompactData" => "true"
        ];

        $staticHotelsData = Http::withHeaders($this->getHeader())
            ->post('http://api.tektravels.com/SharedServices/StaticData.svc/rest/GetHotelStaticData', $requestedDataSet)->json();

        $requestHotelInfo = [
            "CheckInDate" => "27/12/2023",
            "NoOfNights" => "1",
            "CountryCode" => "LK",
            "CityId" => "144745",
            "ResultCount" => null,
            "PreferredCurrency" => "INR",
            "GuestNationality" => "IN",
            "NoOfRooms" => "1",
            "RoomGuests" => [
                [
                    "NoOfAdults" => 1,
                    "NoOfChild" => 0,
                    "ChildAge" => null
                ]
            ],
            "MaxRating" => 5,
            "MinRating" => 0,
            "ReviewScore" => null,
            "IsNearBySearchAllowed" => false,
            "EndUserIp" => $request->ip(),
            "TokenId" => $response['TokenId']
        ];

        // return $requestHotelInfo;


        $getResults = Http::withHeaders($this->getHeader())
            ->post('http://api.tektravels.com/BookingEngineService_Hotel/hotelservice.svc/rest/GetHotelResult/', $requestHotelInfo)->json();

        $hotelResultArray = $getResults['HotelSearchResult']['HotelResults'];


        // $hotelResultCollection = collect($hotelResultArray);








        $utf8Xml = str_replace("utf-16", "utf-8", $staticHotelsData['HotelData']);
        $staticDataXmlToJson = json_decode(json_encode(simplexml_load_string($utf8Xml)), true);
        // return $staticDataXmlToJson;
        $hotelDataSet = [];

        foreach ($hotelResultArray as $key => $hotelResult) {
            foreach ($staticDataXmlToJson['BasicPropertyInfo'] as $key => $dataSet) {
                $hotelCode = $dataSet['@attributes']['TBOHotelCode'];

                $hotelLat = $dataSet['Position']['@attributes']['Latitude'];
                $hotelLon = $dataSet['Position']['@attributes']['Longitude'];

                // $hotelDataSet[] = ["HotelCode" => $hotelCode, "Latitude" => $hotelLat, "Longitude" => $hotelLon];

                if ($hotelResult['HotelCode'] == $hotelCode) {
                    $hotelResult['Latitude'] = $hotelLat;
                    $hotelResult['Longitude'] = $hotelLon;
                }
            }

            $finalHotelDataSet[] = $hotelResult;
        }


        // return $finalHotelDataSet;


        $hotelMapped = [];


        foreach ($hotelOrigin as $key => $ahsHotel) {
            $originHotelLatitude = $ahsHotel->latitude;
            $originHotelLongitude = $ahsHotel->longtitude;



            $originHotelLatitude = explode('.', $originHotelLatitude);
            $originHotelLongitude = explode('.', $originHotelLongitude);

            $originHotelLatitude = substr($originHotelLatitude[1], 0, 2);
            $originHotelLongitude = substr($originHotelLongitude[1], 0, 2);


            foreach ($finalHotelDataSet as $key => $tboHotel) {
                $tboHotelLatitude = $tboHotel['Latitude'];
                $tboHotelLongitude = $tboHotel['Longitude'];



                $tboHotelLatitude = explode('.', $tboHotelLatitude);
                $tboHotelLongitude = explode('.', $tboHotelLongitude);


                if (count($tboHotelLatitude) == 2 && count($tboHotelLongitude) == 2) {
                    $tboHotelLatitude = substr($tboHotelLatitude[1], 0, 2);
                    $tboHotelLongitude = substr($tboHotelLongitude[1], 0, 2);



                    if ($tboHotelLatitude == $originHotelLatitude && $tboHotelLongitude == $originHotelLongitude) {


                        if ($this->getDistance($ahsHotel->latitude, $ahsHotel->longtitude, $tboHotel['Latitude'], $tboHotel['Longitude']) < 40) {
                            $hotelMapped[] = [$ahsHotel->hotel_name, $tboHotel['HotelName'],"Mapped"];
                        } 

                    } 
                    // else {
                    //     $tboHotelLatitude = substr($tboHotelLatitude, 0, 2);
                    //     $tboHotelLongitude = substr($tboHotelLongitude, 0, 2);

                    //     $originHotelLatitude = substr($originHotelLatitude, 0, 2);
                    //     $originHotelLongitude = substr($originHotelLongitude, 0, 2);
            

                    //     if ($tboHotelLatitude == $originHotelLatitude && $tboHotelLongitude == $originHotelLongitude) {


                    //         if ($this->getDistance($ahsHotel->latitude, $ahsHotel->longtitude, $tboHotel['Latitude'], $tboHotel['Longitude']) < 40) {
                    //             $hotelMapped[] = ["Not Mapped",$ahsHotel->hotel_name, $tboHotel['HotelName'],];
                    //         } 

                    //     }
                    // }
                }

            }

        }





        return $hotelMapped;

        // $finalHotelDataSet;
    }
}
