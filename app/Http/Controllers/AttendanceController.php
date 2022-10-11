<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;

class AttendanceController extends Controller
{
    public function clock_in(Request $request)
    {
        $user_id = $request->user()->id;
        $attendance = Attendance::where('date', Carbon::today())
            ->where('user_id', $user_id)
            ->first();

        if (!$attendance) {
            $attendance = Attendance::create(
                [
                    'user_id' => $user_id,
                    'date' => Carbon::today()
                ]
            );
        }

        if (!$attendance->clock_in) {
            $attendance->clock_in = Carbon::now();
            $attendance->save();
        }
        return response()->json($attendance, 200);
    }

    public function clock_out(Request $request)
    {
        $user_id = $request->user()->id;
        $attendance = Attendance::where('date', Carbon::today())
            ->where('user_id', $user_id)
            ->first();

        if (!$attendance) {
            $attendance = Attendance::create(
                [
                    'user_id' => $user_id,
                    'date' => Carbon::today()
                ]
            );
        }

        $attendance->clock_out = Carbon::now();
        $attendance->save();
        return response()->json($attendance, 200);
    }

    public function reports(Request $request, $id)
    {
        if (Gate::denies('reports', $id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date'
        ]);

        $attendances = Attendance::where('user_id', $id)
            ->whereBetween('date', [
                $validated['start'],
                $validated['end']
            ])
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($attendances, 200);
    }

    public function all_reports(Request $request)
    {
        if (Gate::denies('reports')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'start' => 'required|date',
            'end' => 'required|date'
        ]);
        $start = $validated['start'];
        $end = $validated['end'];

        $users = User::with(['attendance' => function ($query) use ($start, $end) {
            $query
                ->whereBetween('date', [
                    $start, $end
                ])
                ->orderBy('date', 'asc');
        }])->get();

        return response()->json($users, 200);
    }
}
