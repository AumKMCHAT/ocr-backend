<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        // $ocr = new TesseractOCR();
        // $ocr->image('C:\laragon\www\ocr-backend\app\Http\Controllers\Api\download.png');
        // echo ($ocr->run());
        return "This is home page.";
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

        // Storage::disk('local')->put('image.png', $file);

        $con_num = "";
        $regex_letter = "/[A-Z]{4}/";
        $regex_number = "/([0-9]{6})/";
        $regex_iso = "/([0-9]{2}[A-Z][0-9])/";

        try {
            $ocr = new TesseractOCR();
            $ocr->image($file);
            $scanned = ($ocr->run());
            print($scanned);
            if (preg_match($regex_letter, $scanned, $match)) {
                foreach ($match as $key => $value) {
                    $letters = $value;
                }
                Storage::disk('local')->put('letter.txt', $letters);
                echo "\nletter match";
                $con_num = $letters;
            }

            if (preg_match($regex_number, $scanned, $match)) {
                foreach ($match as $key => $value) {
                    $number = $value;
                }
                Storage::disk('local')->put('number.txt', $number);
                echo "\nnumber match\n";
                $con_num .= $number;
            }

            if (preg_match($regex_iso, $scanned, $match)) {
                foreach ($match as $key => $value) {
                    $iso = $value;
                }
                Storage::disk('local')->put('iso.txt', $iso);
                echo "\niso match\n";
                $con_num .= $iso;
            }
            return "Store page";
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
        $content = "";
        Storage::put('image.png', $content);

        print($content);
        $ocr = new TesseractOCR();
        $ocr->image("C:\laragon\www\ocr-backend\storage\app\image.png\V9FJkNSvJZfBndw8sIeBqRLwxFsvviVJ4RUbWOFs.png");
        $scanned = ($ocr->run());
        print($scanned);

        $con_num = "";
        $regex_letter = "/[A-Z]{4}/";
        $regex_number = "/([0-9]{6})/";
        $regex_iso = "/([0-9]{2}[A-Z][0-9])/";

        if (preg_match($regex_letter, $scanned, $match)) {
            foreach ($match as $key => $value) {
                $letters = $value;
            }
            Storage::disk('local')->put('letter.txt', $letters);
            echo "\nletter match";
            $con_num = $letters;
        }

        if (preg_match($regex_number, $scanned, $match)) {
            foreach ($match as $key => $value) {
                $number = $value;
            }
            Storage::disk('local')->put('number.txt', $number);
            echo "\nnumber match\n";
            $con_num .= $number;
        }

        if (preg_match($regex_iso, $scanned, $match)) {
            foreach ($match as $key => $value) {
                $iso = $value;
            }
            Storage::disk('local')->put('iso.txt', $iso);
            echo "\niso match\n";
            $con_num .= $iso;
        }
        return $con_num;
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
