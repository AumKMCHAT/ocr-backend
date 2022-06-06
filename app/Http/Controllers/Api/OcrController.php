<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Container;
use App\Models\MasterIso;
use Illuminate\Http\Request;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Eastwest\Json\Facades\Json;
use Illuminate\Http\File;
use Google\Cloud\Vision\V1\Feature\Type;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Likelihood;
use Google\Cloud\Vision\VisionClient;

use function PHPUnit\Framework\isType;

class OcrController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $containers = Container::latest('id');
        return $containers;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $tesseract_output = $this->tesseract($request);
        $google_output = $this->googleAPI($request);

        $this->match($tesseract_output, $request, 'tesseract');
        $this->match($google_output, $request, 'google');

        $suggester_iso = array();

        return response()->json($suggester_iso);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $container = Container::findOrFail($id);
        return $container;
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    private function compareMasterIso($str)
    {
        // $suggester_iso = MasterIso::where('code', 'LIKE', $str[0].$str[1]. "%")->get();
        // $suggester_iso = MasterIso::where('code', 'LIKE', "%" .$str[1].$str[2]. "%")->get();
        // $suggester_iso = MasterIso::where('code', 'LIKE', "%" .$str[2].$str[3])->get();
        // $suggester_iso = MasterIso::where('code', 'LIKE', $str[0]. "%" .$str[3])->get();

        $suggester_iso1 = MasterIso::where('code', 'LIKE', $str[0] . $str[1] . "%" . $str[3])->pluck('code');

        $suggester_iso2 = MasterIso::where('code', 'LIKE', "%" . $str[1] . $str[2] . $str[3])->pluck('code');

        $suggester_iso3 = MasterIso::where('code', 'LIKE', $str[0] . "%" . $str[2] . $str[3])->pluck('code');

        $suggester_iso4 = MasterIso::where('code', 'LIKE', $str[0] . $str[1] . $str[2] . "%")->pluck('code');
        // dd($suggester_iso1, $suggester_iso2, $suggester_iso3, $suggester_iso4);
        $merge = array_merge($suggester_iso1->toArray(), $suggester_iso2->toArray(), $suggester_iso3->toArray(), $suggester_iso4->toArray());

        if (count($merge) > 0) {
            return $merge;
        }
    }

    public function googleAPI(Request $request)
    {

        $file = $request->file('image');

        try {

            $vision = new VisionClient(['keyFile' => json_decode(file_get_contents("C:\laragon\www\ocr-backend\service_account.json"), true)]);
            $img = fopen($file, 'r');
            $gg = $vision->image($img, ['TEXT_DETECTION']);
            $result = $vision->annotate($gg);
            if ($result->info()) {
                return $result->info()['fullTextAnnotation']['text'];
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function tesseract(Request $request)
    {
        $file = $request->file('image');

        try {
            $ocr = new TesseractOCR();
            $ocr->image($file);
            $scanned = ($ocr->run());

            return $scanned;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function space(Request $request)
    {
        $file = $request->file('image');

        $client = new \GuzzleHttp\Client();

        //เด็กมีปัญหา cURL error 60: SSL certificate problem: unable to get local issuer certificate
        try {
            $resp = Http::withHeaders(['apiKey' => 'K86647851888957'])->attach('attachment', file_get_contents($file))->post('https://api.ocr.space/parse/image');

            // $r = $client->request('POST', 'https://api.ocr.space/parse/image', [
            //     'headers' => ['apiKey' => 'K86647851888957'],
            //     'multipart' => [
            //         [
            //             'name' => 'file',
            //             'contents' => $file
            //         ]
            //     ]
            // ], ['file' => $file]);
            // echo $r->getStatusCode();
            // $response =  json_decode($r->getBody(), true);

            return $resp;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function match(String $input, Request $request, String $type)
    {
        $suggester_iso = array();

        $file = $request->file('image');

        $regex_iso = "/([BML][0-9]|[0-9]{2}|[0-9]C)[A-Z][0-9]/";
        $regex_con_num = "/[A-Z]{3}[UJZ][0-9]{7}/";

        $path = Storage::putFile('images', new File($file)); // path to call image

        $container = new Container();
        $container->type = $type;
        $container->output = $input;
        $container->path = $path;

        // $input = preg_replace("/[^A-Z0-9 ]+/i", "", $input); //contain only A-Z 0-9
        $input = preg_replace("/ /", "", $input); // cut space bar

        if (preg_match($regex_con_num, $input, $match)) {
            $con_num = $match[0];
            $container->container_number = $con_num;
            $input = str_replace($con_num, "", $input); //cut container number
        } else {
            $container->container_number = "NotFound";
        }

        if (preg_match($regex_iso, $input, $match)) {
            $iso = $match[0];
            $master_iso = MasterIso::where('code', '=', $iso)->get();

            if (count($master_iso) == 1) { //real iso
                $container->iso = $iso;
            } else {
                $container->iso = "NotFound";
                for ($i = 0; $i < strlen($input) - 3; $i++) {
                    //if begining 2 str start with number >> substr
                    $str_4 = substr($input, $i, 4);

                    if ((is_numeric($str_4[0]) || $str_4[0] == "/[BML]/") && is_numeric($str_4[1])) {
                        $arr = $this->compareMasterIso($str_4);
                        if (!is_null($arr)) {
                            array_push($suggester_iso, ...$arr);
                        }
                    }
                }
            }
        } else {
            $container->iso = "NotFound";
            for ($i = 0; $i < strlen($input) - 3; $i++) {
                //if begining 2 str start with number >> substr
                $str_4 = substr($input, $i, 4);
                if ((is_numeric($str_4[0]) || $str_4[0] == "/[BML]/") && is_numeric($str_4[1])) {
                    $arr = $this->compareMasterIso($str_4);
                    if (!is_null($arr)) {
                        array_push($suggester_iso, ...$arr);
                    }
                }
            }
        }
        $container->save();
        return response()->json($suggester_iso);
    }
}
