<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Container;
use Illuminate\Http\Request;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Eastwest\Json\Facades\Json;
use Illuminate\Http\File;

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

        $regex_iso = "/[0-9]{2}[A-Z][0-9]/";
        $check_con = 0;
        $check_iso = 0;
        $regex_con_num = "/[A-Z]{4}[0-9]{7}/";
        $con_num = "";

        try {
            $ocr = new TesseractOCR();
            $ocr->image($file);
            $scanned = ($ocr->run());
            $scanned = preg_replace("/\s+/", "", $scanned); // cut all whitespace
            print($scanned . "\n");
            if (preg_match($regex_con_num, $scanned, $match) && $check_con == 0) {
                foreach ($match as $key => $value) {
                    $new_con_num = $value; // 1 time loop (get value)
                }

                $con_num = $new_con_num;
                $container->container_number = $con_num;
                $check_con++;
                print($con_num . "\n");
            } else {
                // not found container number
                $con_num = "Not Found";
                $container->container_number = $con_num;
            }
            if (preg_match($regex_iso, $scanned, $match) && $check_iso == 0) {
                foreach ($match as $key => $value) // 1 time loop (get value)
                {
                    $iso = $value;
                }
                print($iso);
                $container->iso = $iso;
                $check_iso++;
            } else {
                $iso = "Not Found";
                $container->iso = $iso;
                //not found iso
                // for ($i = 0; $i < strlen($scanned)-3; $i++) {
                //     //if begining 2 str start with number >> substr
                //     $str_4 = substr($scanned, $i, 4);
                //     $this->compareMasterIso($str_4);
                //     // print(" " . $str_4);
                // }

            }

            $container->save();
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
        $response = Http::get('https://datahub.io/core/iso-container-codes/r/iso-container-codes.json');
        $master_iso = json_decode($response);
        foreach (json_decode($response) as $master_iso) {
            print_r("iso code: " . $master_iso->code . "\n"); //master_iso
        }
    }
}
