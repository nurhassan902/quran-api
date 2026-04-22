<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiController extends Controller
{
    public function locationInfo(Request $request)
    {
        $lat = $request->query('latitude');
        $lon = $request->query('longitude');

        if (!$lat || !$lon) {
            return response()->json(["error"=>"latitude and longitude required"]);
        }

        $geo_url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lon";
        $context = stream_context_create([
            "http"=>["header"=>"User-Agent: MyLocationAPI/1.0\r\n"]
        ]);

        $geo_data = json_decode(@file_get_contents($geo_url,false,$context), true);

        $city="Unknown"; $country="Unknown";

        if ($geo_data && isset($geo_data['address'])) {
            $city = $geo_data['address']['city']
                ?? $geo_data['address']['town']
                ?? $geo_data['address']['village']
                ?? "Unknown";
            $country = $geo_data['address']['country'] ?? "Unknown";
        }

        $gregorian_date = date("Y-m-d");

        $today = date("d-m-Y");
        $hijri_data = json_decode(@file_get_contents("https://api.aladhan.com/v1/gToH?date=$today"), true);

        $hijri_date = "Not available";
        if (isset($hijri_data['data']['hijri'])) {
            $h = $hijri_data['data']['hijri'];
            $hijri_date = $h['month']['en']." ".$h['day'].", ".$h['year']." AH";
        }

        return response()->json([
            "city"=>$city,
            "country"=>$country,
            "hijri_date"=>$hijri_date,
            "gregorian_date"=>$gregorian_date
        ]);
    }

    public function prayerTimes(Request $request)
    {
        $lat=$request->query('latitude');
        $lon=$request->query('longitude');
        $date=$request->query('date', date("Y-m-d"));

        if(!$lat||!$lon){
            return response()->json(["error"=>"latitude and longitude required"]);
        }

        $d=date("d-m-Y",strtotime($date));
        $next=date("d-m-Y",strtotime($date.' +1 day'));

        $data=json_decode(@file_get_contents("https://api.aladhan.com/v1/timings/$d?latitude=$lat&longitude=$lon&method=1"),true);
        $next_data=json_decode(@file_get_contents("https://api.aladhan.com/v1/timings/$next?latitude=$lat&longitude=$lon&method=1"),true);

        if(!$data||!isset($data['data']['timings'])||!$next_data||!isset($next_data['data']['timings'])){
            return response()->json(["error"=>"Prayer time not available"]);
        }

        $t=$data['data']['timings'];
        $next_t=$next_data['data']['timings'];

        $f=function($time){ return date("H:i",strtotime($time)); };

        return response()->json([
            "fajr"=>["start"=>$f($t['Fajr']),"end"=>$f($t['Sunrise'])],
            "dhuhr"=>["start"=>$f($t['Dhuhr']),"end"=>$f($t['Asr'])],
            "asr"=>["start"=>$f($t['Asr']),"end"=>$f($t['Maghrib'])],
            "maghrib"=>["start"=>$f($t['Maghrib']),"end"=>$f($t['Isha'])],
            "isha"=>["start"=>$f($t['Isha']),"end"=>$f($next_t['Fajr'])]
        ]);
    }

    public function prohibitedPrayerTimes(Request $request)
    {
        date_default_timezone_set('Asia/Dhaka');

        $lat=$request->query('latitude');
        $lon=$request->query('longitude');
        $date=$request->query('date',date("Y-m-d"));

        if(!$lat||!$lon){
            return response()->json(["error"=>"latitude and longitude required"]);
        }

        $d=date("d-m-Y",strtotime($date));
        $data=json_decode(@file_get_contents("https://api.aladhan.com/v1/timings/$d?latitude=$lat&longitude=$lon&method=1"),true);

        if(!$data||!isset($data['data']['timings'])){
            return response()->json(["error"=>"Data not available"]);
        }

        $t=$data['data']['timings'];

        $to=function($time)use($date){ return strtotime($date.' '.substr($time,0,5)); };
        $f=function($ts){ return date("H:i",$ts); };

        $sunrise=$to($t['Sunrise']);
        $dhuhr=$to($t['Dhuhr']);
        $maghrib=$to($t['Maghrib']);

        return response()->json([
            "dawn"=>["start"=>$f($sunrise-900),"end"=>$f($sunrise)],
            "afternoon"=>["start"=>$f($dhuhr-360),"end"=>$f($dhuhr-120)],
            "evening"=>["start"=>$f($maghrib),"end"=>$f($maghrib+720)]
        ]);
    }

    public function qibla(Request $request)
    {
        $lat=$request->query('latitude');
        $lon=$request->query('longitude');

        if(!$lat||!$lon){
            return response()->json(["error"=>"latitude and longitude required"]);
        }

        $lat1=deg2rad($lat); $lon1=deg2rad($lon);
        $lat2=deg2rad(21.4225); $lon2=deg2rad(39.8262);

        $dLon=$lon2-$lon1;

        $x=sin($dLon)*cos($lat2);
        $y=cos($lat1)*sin($lat2)-sin($lat1)*cos($lat2)*cos($dLon);

        $bearing=rad2deg(atan2($x,$y));
        $qibla=fmod(($bearing+360),360);

        $earth=6371;
        $dLat=$lat2-$lat1;
        $a=sin($dLat/2)**2+cos($lat1)*cos($lat2)*sin($dLon/2)**2;
        $c=2*atan2(sqrt($a),sqrt(1-$a));
        $distance=$earth*$c;

        return response()->json([
            "qibla_direction"=>number_format($qibla,2),
            "distance_km"=>number_format($distance,2)
        ]);
    }

    public function ramadanTimes(Request $request)
    {
        date_default_timezone_set('Asia/Dhaka');

        $lat = $request->query('latitude');
        $lon = $request->query('longitude');

        if (!$lat || !$lon) {
            return response()->json([
                "error" => "latitude and longitude required"
            ]);
        }

        // 🌍 LOCATION (FIXED)
        $geo_url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lon";

        $context = stream_context_create([
            "http" => [
                "header" => "User-Agent: QuranApp/1.0\r\n"
            ],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ]
        ]);

        $geo_response = @file_get_contents($geo_url, false, $context);
        $geo = json_decode($geo_response, true);

        $city = $geo['address']['city']
            ?? $geo['address']['town']
            ?? $geo['address']['village']
            ?? "Unknown";

        $country = $geo['address']['country'] ?? "Unknown";

        // 📅 DATE
        $today = date("d-m-Y");
        $gregorian = date("Y-m-d");

        // 🕌 PRAYER TIME
        $data = json_decode(@file_get_contents("https://api.aladhan.com/v1/timings/$today?latitude=$lat&longitude=$lon&method=1"), true);

        if (!$data || !isset($data['data']['timings'])) {
            return response()->json(["error" => "Data not available"]);
        }

        $t = $data['data']['timings'];
        $h = $data['data']['date']['hijri'];

        // ⏰ TIMES
        $sehriTime = strtotime($t['Fajr']);
        $iftarTime = strtotime($t['Maghrib']);
        $now = time();

        // ⏳ REMAINING
        if ($now < $sehriTime) {
            $remaining = $sehriTime - $now;
            $label = "Remaining Sehri";
        } else {
            $remaining = 0;
            $label = "Sehri Time Passed";
        }

        $formatTime = function ($seconds) {
            return gmdate("H:i:s", $seconds);
        };

        // ✅ FINAL RESPONSE
        return response()->json([
            "city" => $city,
            "country" => $country,
            "gregorian_date" => $gregorian,
            "hijri_date" => $h['month']['en'] . " " . $h['day'] . ", " . $h['year'] . " AH",

            "sehri" => [
                "last_time" => date("h:i A", $sehriTime),
                "label" => "Today Sehri Last"
            ],

            "iftar" => [
                "start_time" => date("h:i A", $iftarTime),
                "label" => "Today Iftar Start"
            ],

            "remaining_time" => [
                "sehri_remaining" => $formatTime($remaining),
                "label" => $label
            ]
        ]);
    }

    public function surahList(Request $request)
    {
        $page=$request->query('page',1);
        $limit=$request->query('limit',10);

        $data=json_decode(file_get_contents("https://api.alquran.cloud/v1/surah"),true);

        $all=[];
        foreach($data['data'] as $s){
            $all[]=[
                "id"=>$s['number'],
                "name_en"=>$s['englishName'],
                "name_ar"=>$s['name'],
                "verses"=>$s['numberOfAyahs'],
                "type"=>$s['revelationType']
            ];
        }

        $offset=($page-1)*$limit;

        return response()->json([
            "page"=>$page,
            "per_page"=>$limit,
            "total"=>count($all),
            "total_pages"=>ceil(count($all)/$limit),
            "data"=>array_slice($all,$offset,$limit)
        ]);
    }
    
    public function surahDetails(Request $request)
    {
        $id = $request->query('id');
        $page = (int) $request->query('page', 1);
        $perPage = 15;

        if (!$id) {
            return response()->json(["error" => "surah id required"]);
        }

        // Arabic
        $arabic = json_decode(@file_get_contents("https://api.alquran.cloud/v1/surah/$id/quran-uthmani"), true);

        // Transliteration
        $translit = json_decode(@file_get_contents("https://api.alquran.cloud/v1/surah/$id/en.transliteration"), true);

        // Bangla
        $bangla = json_decode(@file_get_contents("https://api.alquran.cloud/v1/surah/$id/bn.bengali"), true);

        if (!$arabic || !$translit || !$bangla) {
            return response()->json(["error" => "Data not available"]);
        }

        $a = $arabic['data']['ayahs'];
        $t = $translit['data']['ayahs'];
        $b = $bangla['data']['ayahs'];

        $total = count($a);
        $totalPages = ceil($total / $perPage);

        $offset = ($page - 1) * $perPage;

        $data = [];

        for ($i = $offset; $i < min($offset + $perPage, $total); $i++) {
            $data[] = [
                "ayah" => $a[$i]['numberInSurah'],
                "arabic" => $a[$i]['text'],
                "transliteration" => $t[$i]['text'],
                "bangla" => $b[$i]['text']
            ];
        }

        return response()->json([
            "surah" => [
                "id" => $arabic['data']['number'],
                "name_en" => $arabic['data']['englishName'],
                "name_ar" => $arabic['data']['name'],
                "revelation" => $arabic['data']['revelationType'],
                "total_ayah" => $total
            ],
            "pagination" => [
                "current_page" => $page,
                "per_page" => $perPage,
                "total_pages" => $totalPages
            ],
            "data" => $data
        ]);
    }

    public function surahArabic(Request $request)
    {
        $id = $request->query('id');
        $page = (int) $request->query('page', 1);
        $perPage = 20;

        if (!$id) {
            return response()->json([
                "error" => "surah id required"
            ]);
        }

        $response = json_decode(@file_get_contents("https://api.alquran.cloud/v1/surah/$id/quran-uthmani"), true);

        if (!$response || $response['status'] != 'OK') {
            return response()->json([
                "error" => "Surah not found"
            ]);
        }

        $surah = $response['data'];
        $ayahs = $surah['ayahs'];

        $total = count($ayahs);
        $totalPages = ceil($total / $perPage);

        $offset = ($page - 1) * $perPage;

        $slice = array_slice($ayahs, $offset, $perPage);

        // 🔥 combine all ayahs in one line
        $arabicText = "";
        foreach ($slice as $ayah) {
            $arabicText .= "(" . $ayah['numberInSurah'] . ") " . $ayah['text'] . " ";
        }

        return response()->json([
            "surah_id" => $surah['number'],
            "name" => $surah['englishName'],
            "page" => $page,
            "per_page" => $perPage,
            "total_ayah" => $total,
            "total_pages" => $totalPages,
            "arabic_text" => trim($arabicText)
        ]);
    }

    public function dailyHadith()
    {
        $file = public_path('hadiths.json');
        $hadiths = json_decode(file_get_contents($file), true);

        // যদি date param থাকে use করবে, না থাকলে today
        $date = request('date') ?? now()->toDateString();

        $index = crc32($date) % count($hadiths);

        $hadith = $hadiths[$index];

        return response()->json([
            'date' => $date,
            'hadith' => $hadith['text'],
            'source' => $hadith['source']
        ]);
    }

    public function islamicEvents(Request $request)
{
    date_default_timezone_set('Asia/Dhaka');

    $today = date("Y-m-d");
    $nextYear = date("Y-m-d", strtotime("+1 year"));

    $events = [
        ["title" => "Islamic New Year", "hijri" => "01-01"],
        ["title" => "Day of Ashura", "hijri" => "10-01"],
        ["title" => "Arbaeen", "hijri" => "20-02"],
        ["title" => "Mawlid (Prophet’s Birthday)", "hijri" => "12-03"],
        ["title" => "Isra and Mi'raj", "hijri" => "27-07"],
        ["title" => "Mid-Sha'ban (Shab-e-Barat)", "hijri" => "15-08"],
        ["title" => "Ramadan Begins", "hijri" => "01-09"],
        ["title" => "Nuzul al-Quran", "hijri" => "17-09"],
        ["title" => "Laylat al-Qadr", "hijri" => "27-09"],
        ["title" => "Eid al-Fitr", "hijri" => "01-10"],
        ["title" => "Hajj Begins", "hijri" => "08-12"],
        ["title" => "Day of Arafah", "hijri" => "09-12"],
        ["title" => "Eid al-Adha", "hijri" => "10-12"],
        ["title" => "Days of Tashreeq", "hijri" => "11-12"]
    ];

    $years = [1447, 1448, 1449];
    $final = [];

    foreach ($years as $hYear) {
        foreach ($events as $event) {

            $hijriDate = $event['hijri'] . "-" . $hYear;

            $url = "https://api.aladhan.com/v1/hToG?date=$hijriDate";
            $response = @file_get_contents($url);
            $data = json_decode($response, true);

            if (isset($data['data']['gregorian']['date'])) {

                $gregorian = date("Y-m-d", strtotime($data['data']['gregorian']['date']));

                if ($gregorian >= $today && $gregorian <= $nextYear) {

                    $final[] = [
                        "title" => $event['title'],
                        "hijri_date" => $hijriDate,
                        "gregorian_date" => date("d-m-Y", strtotime($gregorian))
                    ];
                }
            }
        }
    }

    // sort by upcoming date
    usort($final, function ($a, $b) {
        return strtotime($a['gregorian_date']) - strtotime($b['gregorian_date']);
    });

    return response()->json([
        "year_range" => date("Y") . " - " . date("Y", strtotime("+1 year")),
        "total" => count($final),
        "data" => $final
    ]);
}
}
