<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Hotel;
use App\Models\AahaasMeta;
use Illuminate\Support\Facades\Log;

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

    public function basicHotel(Request $request)
    {

        $authArray['ClientId'] = "ApiIntegrationNew";
        $authArray['UserName'] = "Sharmila1";
        $authArray['Password'] = "Sharmila@1234";
        $authArray['EndUserIp'] = $request->ip();
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

        // return $hotelResultArray;
        $validHotels = [];

        $utf8Xml = str_replace("utf-16", "utf-8", $staticHotelsData['HotelData']);
        $staticDataXmlToJson = json_decode(json_encode(simplexml_load_string($utf8Xml)), true);
        // return $staticDataXmlToJson;
        $hotelDataSet = [];

        $hotelDataSet = [];
        foreach ($hotelResultArray as $hotelResult) {
            foreach ($staticDataXmlToJson['BasicPropertyInfo'] as $dataSet) {
                $hotelCode = $dataSet['@attributes']['TBOHotelCode'];
                $hotelLat = $dataSet['Position']['@attributes']['Latitude'];
                $hotelLon = $dataSet['Position']['@attributes']['Longitude'];

                if ($hotelResult['HotelCode'] == $hotelCode) {
                    $hotelResult['Latitude'] = $hotelLat;
                    $hotelResult['Longitude'] = $hotelLon;
                }
            }
            $hotelDataSet[] = $hotelResult;
        }

        foreach ($hotelDataSet as $hotel) {
            foreach (Hotel::all(['id', 'latitude', 'longitude']) as $existingHotel) {
                $hotelLatitude = $hotel['Latitude'];
                $hotelLongitude = $hotel['Longitude'];

                $latitudeParts = explode('.', $hotelLatitude);
                $longitudeParts = explode('.', $hotelLongitude);

                if (count($latitudeParts) > 1 && count($longitudeParts) > 1) {
                    $latitudePrefix = $latitudeParts[1];
                    $longitudePrefix = $longitudeParts[1];

                    $existingLatitude = $existingHotel->latitude;
                    $existingLongitude = $existingHotel->longitude;

                    $existingLatitudeParts = explode('.', $existingLatitude);
                    $existingLongitudeParts = explode('.', $existingLongitude);

                    if (count($existingLatitudeParts) > 1 && count($existingLongitudeParts) > 1) {
                        $existingLatitudePrefix = $existingLatitudeParts[0] . "." . substr($existingLatitudeParts[1], 0, 3);
                        $existingLongitudePrefix = $existingLatitudeParts[0] . "." . substr($existingLongitudeParts[1], 0, 3);

                        if ($latitudePrefix === $existingLatitudePrefix && $longitudePrefix === $existingLongitudePrefix) {
                            $hotelCode = $hotel['HotelCode'];

                            Log::info('Updating AahaasMeta for hotel code: ' . $hotelCode);

                            AahaasMeta::updateOrCreate(
                                ['hotel_code' => $hotelCode],
                                [
                                    'hotel_code' => $hotelCode,
                                    'hotel_name' => $hotel['HotelName'],
                                    // Add other fields to update in AahaasMeta table if needed
                                ]
                            );
                        }
                    }
                }
            }
        }
        return response()->json(['message' => 'Data mapped successfully', 'hotelData' => $hotelDataSet]);
    }
}
