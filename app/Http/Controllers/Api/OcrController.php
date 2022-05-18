<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ocr;
use Illuminate\Http\Request;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Illuminate\Support\Facades\Storage;

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

                if (preg_match($regex_number, $scanned, $match)) {
                    foreach ($match as $key => $value) {
                        $number = $value;
                    }

                    Storage::disk('local')->put('letter.txt', $letters);
                    Storage::disk('local')->put('number.txt', $number);
                }
            }

            if (preg_match($regex_iso, $scanned, $match)) {
                foreach ($match as $key => $value) {
                    $iso = $value;
                }
                Storage::disk('local')->put('iso.txt', $iso);
            }
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

        $letters = Storage::get('letter.txt');
        $number = Storage::get('number.txt');
        $iso = Storage::get('iso.txt');

        $ans = array();

        array_push($ans, $letters);
        array_push($ans, $number);
        array_push($ans, $iso);

        return $ans;
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
