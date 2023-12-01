<?php

namespace App\Http\Controllers;

use App\Models\HotelMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Hotel;
use App\Models\TBO;
use Twilio\Rest\Client;

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



    public function twilioSMS()
    {
        $sid = "AC54beb1fcdd13fcdbd8627046b4e91c87";
        $token = "abcd8c4f4af852e5c50ddce01410ca5b";
        $twilio = new Client($sid, $token);




        $verifications = $twilio->verify->v2->services("VA76b677c5da55984dbc367cf67ca5a8b1")
            ->verifications
            ->create("+94772897856", "sms");


        return $verifications->status;
    }



    public function basicHotel(Request $request)
    {
        ini_set('max_execution_time', 10000);



        $authArray['ClientId'] = "ApiIntegrationNew";
        $authArray['UserName'] = "Sharmila1";
        $authArray['Password'] = "Sharmila@1234";
        $authArray['EndUserIp'] = $request->ip();
        // $hotelOrigin = Hotel::all();
        $response = Http::withHeaders($this->getHeader())
            ->post('http://api.tektravels.com/SharedServices/SharedData.svc/rest/Authenticate', $authArray)->json();






        $requestedDataSet = [
            'ClientId' => 'ApiIntegrationNew',
            'EndUserIp' => $request->ip(),
            'TokenId' => $response['TokenId'],
            'SearchType' => 1,
            'CountryCode' => 'LK',
        ];

        $CityList = Http::withHeaders($this->getHeader())
            ->post('http://api.tektravels.com/SharedServices/StaticData.svc/rest/GetDestinationSearchStaticData', $requestedDataSet)->json();


        // $CityListStatic = [
        //     [
        //         "DestinationId" => 144745,
        //         "CityName" => "Colombo",
        //     ],
        //     [
        //         "DestinationId" => 144191,
        //         "CityName" => "Bentota",
        //     ],
        //     [
        //         "DestinationId" => 122254,
        //         "CityName" => "Kandy",
        //     ],

        // ];


        $mappedCities = [];
        $hotelResultArray = [];

        foreach ($CityList['Destinations'] as $key => $cityCode) {
            // foreach ($CityListStatic as $key => $cityCode) {
            $requestHotelInfo = [
                "CheckInDate" => "10/12/2023",
                "NoOfNights" => "1",
                "CountryCode" => "LK",
                "CityId" => $cityCode['DestinationId'],
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

            // return $getResults;

            if ($getResults['HotelSearchResult']['Error']['ErrorCode'] == 0) {
                $hotelResultArray[] = $getResults['HotelSearchResult']['HotelResults'];
                $mappedCities[] = $cityCode['DestinationId'];
            }
        }




        foreach ($hotelResultArray as $key => $hotel) {
            foreach ($hotel as $keyIndex => $hotelIndex) {
                $hotelIndex['cityCode'] = $mappedCities[$key];
                $hotelFinalResults[] = $hotelIndex;
            }
        }


        foreach ($mappedCities as $key => $value) {
            $requestedDataSet = [
                'CityId' => $value,
                'ClientId' => 'ApiIntegrationNew',
                'EndUserIp' => $request->ip(),
                'TokenId' => $response['TokenId'],
                "IsCompactData" => "true"
            ];

            $staticHotelsData = Http::withHeaders($this->getHeader())
                ->post('http://api.tektravels.com/SharedServices/StaticData.svc/rest/GetHotelStaticData', $requestedDataSet)->json();

            if ($staticHotelsData["Error"]["ErrorCode"] == 0) {

                $utf8Xml = str_replace("utf-16", "utf-8", $staticHotelsData['HotelData']);
                $staticDataXmlToJson = json_decode(json_encode(simplexml_load_string($utf8Xml)), true);
                $staticHotelData[] = $staticDataXmlToJson['BasicPropertyInfo'];
            }
        }


        // return $staticHotelData;


        foreach ($staticHotelData as $key => $staticHotel) {
            foreach ($staticHotel as $key => $hotelIndex) {
                $hotelStaticDataSet[] = $hotelIndex;
            }
        }

        HotelMeta::truncate();
        TBO::truncate();


        foreach ($hotelFinalResults as $key => $hotelResult) {
            foreach ($hotelStaticDataSet as $key => $dataSet) {
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



            HotelMeta::create([
                'hotelCode' => $hotelResult['HotelCode'],
                'ahs_HotelId' => null,
                'hotelName' => $hotelResult['HotelName'],
                'hotelDescription' => $hotelResult['HotelDescription'],
                'city_code' => $hotelResult['cityCode'],
                'country' => null,
                'countryCode' => null,
                'latitude' => $hotelResult['Latitude'], //number_format((float)$hotel['coordinates']['latitude'], 5, '.', '')
                'longitude' => $hotelResult['Longitude'], //number_format((float)$hotel['coordinates']['longitude'], 5, '.', '')
                'category' => $hotelResult['HotelCategory'],
                'boards' => null,
                'address' => $hotelResult['HotelAddress'],
                'postalCode' => null,
                'city' => null,
                'email' => null,
                'web' => null,
                'class' => null,
                'tripAdvisor' => null,
                'facilities' => null,
                'images' => $hotelResult['HotelPicture'],
                'rating' => null,
                'provider' => 'hotelTbo',
                'microLocation' => null,
                'driverAcc' => null,
                'liftStatus' => null,
                'vehicleApproach' => null,
                'accountStatus' => null,
                'published_price' => $hotelResult['Price']['PublishedPrice'],
            ]);



            TBO::create(
                [
                    'city_code' => $hotelResult['cityCode'],
                    'hotel_code' => $hotelResult['HotelCode'],
                    'hotel_name' => $hotelResult['HotelName'],
                    'hotel_category' => $hotelResult['HotelCategory'],
                    'star_rating' => $hotelResult['StarRating'],
                    'hotel_description' => $hotelResult['HotelDescription'],
                    'hotel_promotion' => $hotelResult['HotelPromotion'],
                    'hotel_policy' => $hotelResult['HotelPolicy'],
                    'published_price' => $hotelResult['Price']['PublishedPrice'],
                    'hotel_picture' => $hotelResult['HotelPicture'],
                    'hotel_address' => $hotelResult['HotelAddress'],
                    'hotel_contact_no' => $hotelResult['HotelContactNo'],
                    'hotel_map' => $hotelResult['HotelMap'],
                    'latitude' => $hotelResult['Latitude'],
                    'longitude' => $hotelResult['Longitude'],
                    'hotel_location' => $hotelResult['HotelLocation'],
                    'supplier_price' => $hotelResult['SupplierPrice'],
                    'room_details' => null,
                ]
            );
        }


        return $finalHotelDataSet;










        // return $hotelMapped;

        // $finalHotelDataSet;
    }



    public function mapHotelsAhsTbo()
    {
        $tbo = TBO::all();


        $aahaas = Hotel::all();


        $hotelMapped = [];



        foreach ($aahaas as $key => $ahsHotel) {
            $originHotelLatitude = $ahsHotel->latitude;
            $originHotelLongitude = $ahsHotel->longtitude;



            $originHotelLatitude = explode('.', $originHotelLatitude);
            $originHotelLongitude = explode('.', $originHotelLongitude);

            $originHotelLatitude = substr($originHotelLatitude[1], 0, 3);
            $originHotelLongitude = substr($originHotelLongitude[1], 0, 3);


            foreach ($tbo as $key => $tboHotel) {
                $tboHotelLatitude = $tboHotel->latitude;
                $tboHotelLongitude = $tboHotel->longitude;



                $tboHotelLatitude = explode('.', $tboHotelLatitude);
                $tboHotelLongitude = explode('.', $tboHotelLongitude);


                if (count($tboHotelLatitude) == 2 && count($tboHotelLongitude) == 2) {
                    $tboHotelLatitude = substr($tboHotelLatitude[1], 0, 3);
                    $tboHotelLongitude = substr($tboHotelLongitude[1], 0, 3);


                    if ($tboHotelLatitude == $originHotelLatitude && $tboHotelLongitude == $originHotelLongitude) {
                        if ($this->getDistance($ahsHotel->latitude, $ahsHotel->longtitude, $tboHotel->latitude, $tboHotel->longitude) < 40) {
                            $hotelMapped[] = ["tbo_code" => $tboHotel->hotel_code, "ahs_code" => $ahsHotel->id];
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

        foreach ($hotelMapped as $key) {
            // return $key['origin'];
            DB::table('aahaas_hotel_meta')
                ->where(['hotelCode' => $key['tbo_code']])
                ->update([
                    'ahs_HotelId' => $key['ahs_code'],
                ]);
        }


        return $hotelMapped;

        //    return DB::table('tbo')->get();
    }


    public function createHotelDetailsAhs()
    {
        try {

            $Query = DB::table('tbl_hotel')
                ->join('tbl_hotel_details', 'tbl_hotel.id', '=', 'tbl_hotel_details.hotel_id')
                ->join('tbl_submaincategory', 'tbl_hotel.category1', '=', 'tbl_submaincategory.id')
                // ->limit(3)
                ->select('*')->get();
            // return $Query;
            $array = array();

            if (count($Query) != 0) {

                foreach ($Query as $hotel) {

                    $count = DB::table('aahaas_hotel_meta')->where(['ahs_HotelId' => $hotel->hotel_id])->count();

                    if ($count > 0) {
                        DB::table('aahaas_hotel_meta')
                            ->where(['ahs_HotelId' => $hotel->hotel_id])
                            ->update([
                                // 'hotelName' => $hotel->hotel_name,
                                // 'hotelDescription' => $hotel->hotel_description,
                                'country' => $hotel->country,
                                'countryCode' => 'LK',
                                // 'latitude' => round((float)$hotel->latitude, 4), //number_format((float)$hotel['coordinates']['latitude'], 5, '.', '')
                                // 'longitude' => round((float)$hotel->longtitude, 4), //number_format((float)$hotel['coordinates']['longitude'], 5, '.', '')
                                'category' => $hotel->submaincat_type,
                                'boards' => null,
                                'address' => $hotel->hotel_address,
                                'postalCode' => null,
                                'city' => $hotel->city,
                                'email' => null,
                                'web' => null,
                                'class' => $hotel->hotel_level,
                                'tripAdvisor' => $hotel->trip_advisor_link,
                                'facilities' => null,
                                // 'images' => explode(',', $hotel->hotel_image)[0],
                                'rating' => null,

                                'microLocation' => $hotel->micro_location,
                                'driverAcc' => $hotel->driver_accomadation,
                                'liftStatus' => $hotel->lift_status,
                                'vehicleApproach' => $hotel->vehicle_approchable,
                                'accountStatus' => null,
                            ]);

                        // return response(['status' => 200, 'message' => 'updated']);
                    } else {

                        HotelMeta::create([
                            'hotelCode' => null,
                            'ahs_HotelId' => $hotel->hotel_id,
                            'hotelName' => $hotel->hotel_name,
                            'hotelDescription' => $hotel->hotel_description,
                            'country' => $hotel->country,
                            'countryCode' => 'LK',
                            'latitude' => round((float)$hotel->latitude, 4), //number_format((float)$hotel['coordinates']['latitude'], 5, '.', '')
                            'longitude' => round((float)$hotel->longtitude, 4), //number_format((float)$hotel['coordinates']['longitude'], 5, '.', '')
                            'category' => $hotel->submaincat_type,
                            'boards' => null,
                            'address' => $hotel->hotel_address,
                            'postalCode' => null,
                            'city' => $hotel->city,
                            'email' => null,
                            'web' => null,
                            'class' => $hotel->hotel_level,
                            'tripAdvisor' => $hotel->trip_advisor_link,
                            'facilities' => null,
                            'images' => explode(',', $hotel->hotel_image)[0],
                            'rating' => null,
                            'provider' => 'hotelAhs',
                            'microLocation' => $hotel->micro_location,
                            'driverAcc' => $hotel->driver_accomadation,
                            'liftStatus' => $hotel->lift_status,
                            'vehicleApproach' => $hotel->vehicle_approchable,
                            'accountStatus' => null,
                        ]);
                    }
                }
                return response(['status' => 200, 'message' => 'created']);
            } else {
                return response(['status' => 400]);
            }
            // return $response;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
