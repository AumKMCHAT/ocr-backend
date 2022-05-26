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
        $containers = Container::get();
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

        $file = $request->file('image');
        $path = Storage::putFile('images', new File($file)); // path to call image

        $container = new Container();
        $container->path = $path;

        $regex_iso = "/[BMPL][0-9]|[0-9]{2}[A-Z0-9][0-9]|[PESIRT]/";
        $regex_con_num = "/[A-Z]{4}[0-9]{7}/";

        $suggester_iso = array();

        try {
            $ocr = new TesseractOCR();
            $ocr->image($file);
            $scanned = ($ocr->run());
            $container->output = $scanned;
            $scanned = preg_replace("/[^A-Z0-9 ]+/i", "", $scanned);
            $scanned = preg_replace("/\s+/", "", $scanned); // cut all whitespace

            print("Tesseract output: " . $scanned . "\n");
            if (preg_match($regex_con_num, $scanned, $match)) {
                $con_num = $match[0];
                $scanned = str_replace($con_num, "", $scanned); //cut container number
                print("scaned after found container: " . $scanned);
                $container->container_number = $con_num;
                print("\ncontainer number: " . $con_num);
            } else {
                // not found container number
                $con_num = "Not Found";
                print("Container number: " . $con_num);
                $container->container_number = $con_num;
            }
            if (preg_match($regex_iso, $scanned, $match)) {
                $iso = $match[0];
                $master_iso = MasterIso::where('code', '=', $iso)->get();
                if (count($master_iso) == 1) { //real iso
                    print("\niso: " . $iso);
                    $container->iso = $iso;
                } else {
                    $iso = "Not Found";
                    $container->iso = $iso;
                    print("\niso: " . $iso);
                    //not found iso
                    for ($i = 0; $i < strlen($scanned) - 3; $i++) {
                        //if begining 2 str start with number >> substr
                        $str_4 = substr($scanned, $i, 4);
                        print("\nsubstr: " . $str_4);
                        if ((is_numeric($str_4[0]) || $str_4[0] == "/[BMPL]/") && is_numeric($str_4[1])) {
                            $arr = $this->compareMasterIso($str_4);
                            if (!is_null($arr)) {
                               array_push($suggester_iso, ...$arr);
                            }
                        }
                    }
                }
            } else {
                $iso = "Not Found";
                $container->iso = $iso;
                print("\niso: " . $iso);
                //not found iso
                for ($i = 0; $i < strlen($scanned) - 3; $i++) {
                    //if begining 2 str start with number >> substr
                    $str_4 = substr($scanned, $i, 4);
                    print("\nsubstr: " . $str_4);
                    if ((is_numeric($str_4[0]) || $str_4[0] == "/[BMPL]/") && is_numeric($str_4[1])) {
                        $arr = $this->compareMasterIso($str_4);
                        if (!is_null($arr)) {
                         array_push($suggester_iso, ...$arr);
                        }
                    }
                }
            }

            $container->save();
            return response()->json($suggester_iso);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
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
        print("\ncompare " . $str);

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

    public function test()
    {
        $response = Http::get('https://datahub.io/core/iso-container-codes/r/iso-container-codes.json');
        $master_iso = json_decode($response);
        
        // foreach (json_decode($response) as $master_iso) {
        //     print_r("iso code: " . $master_iso->code . "\n"); //master_iso
        // } //loop put api to database
        dd($master_iso);
    }
}
