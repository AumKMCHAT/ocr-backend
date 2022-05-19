<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ocr;
use Illuminate\Http\Request;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\Storage;

class OcrController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $con_num = Storage::get('containerNumber.txt');
        $iso = Storage::get('iso.txt');

        $ans = array();

        array_push($ans, $con_num);
        array_push($ans, $iso);

        return $ans;
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

        $regex_iso = "/[0-9]{2}[A-Z][0-9]/";
        $check_con = 0;
        $check_iso = 0;
        $regex_con_num = "/[A-Z]{4}[0-9]{6}/";
        $con_num = "";

        try {
            $ocr = new TesseractOCR();
            $ocr->image($file);
            $scanned = ($ocr->run());
            for ($i = 0; $i < strlen($scanned); $i++) {
                $con_num .= $scanned[$i];
                $new_con_num = str_replace(' ', '', $con_num);

                if (preg_match($regex_con_num, $new_con_num)) {

                    Storage::disk('local')->put('containerNumber.txt', $new_con_num);

                    print("\nContainer Number Match");
                    $check_con++;
                    break;
                }
            }

            if (preg_match($regex_iso, $scanned, $match)) {
                foreach ($match as $key => $value) {
                    $iso = $value;
                }

                Storage::disk('local')->put('iso.txt', $iso);
                $check_iso++;
                $iso = Storage::get('iso.txt');
                print("\nISO Match\n" . $iso);
            }

            if ($check_con != 1) {
                Storage::disk('local')->put('containerNumber.txt', "Not Found");
                print("\nNot Found Container number");
            }
            if ($check_iso != 1) {
                Storage::disk('local')->put('iso.txt', "Not Found");
                print("\nNot Found ISO");
            }
        } catch (\Exception $e) {
            print("Tesseract KAK!");
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
        //
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
}
