<?php

namespace App\Http\Controllers;

use App\Emotion;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EmotionsController extends Controller
{
    public function emotions_table(Request $request)
    {
        return view('emotions.table')
            ->with('emotions',
                Emotion::when(!empty($request->emotion) and $request->emotion != '---', function ($query) use ($request) {
                    $query->where('emotion', $request->emotion);
                })
                    ->when(!empty($request->day), function ($query) use ($request) {
                        $query->where('created_at', '>=', Carbon::createFromDate($request->year, $request->month, $request->day - 1))
                            ->where('created_at', '<=', Carbon::createFromDate($request->year, $request->month, $request->day + 1));
                    })
                    ->get());
    }

    public function emotions_chart(Request $request)
    {
        $period_type = $request->period_type ?? 'm';

        switch ($period_type) {
            case 'm':
                $count = 30;
                $date_from = Carbon::now()->subDays($count);
                break;
            case 'w':
                $count = 7;
                $date_from = Carbon::now()->subDays($count);
                break;
            default:
                $count = 24;
                $date_from = Carbon::now()->subHours($count);
        }

        $date_from_temp = (clone $date_from);

        $result = [[]];
        $titles = [];
        for ($i = 0; $i < $count; $i++) {
            $result['Sad'][$i] = $result['Neutral'][$i] = $result['Happy'][$i] = 0;

            switch ($period_type) {
                case 'm':
                    $titles[$i] = $date_from_temp->addDay()->day;
                    break;
                case 'w':
                    $titles[$i] = $date_from_temp->addDay()->formatLocalized('%A');
                    break;
                default:
                    $titles[$i] = $date_from_temp->addHour()->format('ga');
            }
        }

        $emotions = Emotion::where('created_at', '>', $date_from)->get();

        foreach ($emotions as $emotion) {
            switch ($period_type) {
                case 'm':
                    $index = $date_from->diffInDays(Carbon::now());
                    break;
                case 'w':
                    $index = $date_from->diffInDays(Carbon::now());
                    break;
                default:
                    $index = $date_from->diffInHours(Carbon::now());
            }

            $result[$emotion->emotion] [$index-1]++;
        }

        return view('emotions.chart')
            ->with('emotions', json_encode($result))
            ->with('titles', json_encode($titles))
            ->with('period_type', $period_type);
    }

    public function send_emotion(Request $request)
    {
        $emotion = new Emotion();

        $emotion->emotion = $request->emotion;
        $emotion->image_file = '';

        if (!empty($request->file('image_file'))) {
            $emotion->image_file = $request->emotion . date('m-d-Y_hia') . '.' .
                $request->file('image_file')->getClientOriginalExtension();

            $request->file('image_file')->storeAs('emotions', $emotion->image_file);
        }

        $emotion->save();

        return response()->json(['status' => 0]);
    }

    public function emotion_image(Request $request)
    {
        return response()->file(storage_path() . '/app/emotions/' . Emotion::find($request->id)->image_file,
            ['Content-Type' => 'image/jpg']);
    }
}
