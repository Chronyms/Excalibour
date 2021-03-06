<?php

namespace App\Http\Controllers;

use DB;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ParticipationsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     *
     * @return Application|Factory|Response|View
     */
    public function index(Request $request)
    {
        if ($request->input('search') == null) {
            $participations = DB::table('participations')
                ->leftJoin('group', 'group.id', '=', 'participations.FK_GRP')
                ->leftJoin('exer', 'exer.id', '=', 'participations.FK_EXER')
                ->select('participations.*', 'exer.exer_name', 'group.group_name')->get();
        } else {
            $search_string = $request->input('search');

            $participations = DB::table('participations')
                ->leftJoin('group', 'group.id', '=', 'participations.FK_GRP')
                ->leftJoin('exer', 'exer.id', '=', 'participations.FK_EXER')
                ->select('participations.*', 'exer.exer_name', 'group.group_name')
                ->where('scout_name', 'LIKE', "%$search_string%")
                ->orWhere('last_name', 'LIKE', "%$search_string%")
                ->orWhere('first_name', 'LIKE', "%$search_string%")
                ->orWhere('exer_name', 'LIKE', "%$search_string%")
                ->orWhere('group_name', 'LIKE', "%$search_string%")->get();
        }

        return view('participations.participations', ['participations' => $participations]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|Response|View
     */
    public function create()
    {
        $groups = DB::table('group')->select('id', 'group_name')->get();
        $exer = DB::table('exer')->select('id', 'exer_name')->get();

        return view('participations.add', ['groups' => $groups, 'exer' => $exer]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function store(Request $request)
    {
        $scout_name = $request->input('scout_name');
        $first_name = $request->input('first_name');
        $last_name = $request->input('last_name');
        $group = $request->input('group');
        $exer = $request->input('exer');

        DB::table('participations')->insert(['scout_name' => $scout_name, 'first_name' => $first_name, 'last_name' => $last_name, 'FK_GRP' => $group, 'FK_EXER' => $exer]);

        return redirect()->back()->with('message', 'Teilnehmer wurde erstellt.');
    }

    public function import(Request $request)
    {
        if ($request->file('participations_list')) {
            $participations_list = $request->file('participations_list')->move(storage_path('temp/csv'), 'participations.csv');
        } else {
            return redirect()->back()->with('error', 'Die Teilnehmer konnten nicht importiert werden, da keine entsprehende Datei gesendet wurde.');
        }

        $handle = fopen($participations_list, 'r');
        $content = mb_convert_encoding(fread($handle, filesize($participations_list)), 'UTF-8', 'Windows-1252');
        $lines = preg_split("/(\n)/", $content);

        $number_participations = count($lines);

        unset($lines[$number_participations - 1]);

        foreach ($lines as $line) {
            $contents[] = explode(';', $line);
        }

        fclose($handle);

        foreach ($contents as $content) {
            $grp = DB::table('group')->select('id')->where('name', 'LIKE', "%$content[3]%")->first();

            $content[4] = str_replace("\r", '', $content[4]);
            $exer = DB::table('exer')->select('id', 'exer_name')->where('escaped_exer_name', 'LIKE', "%$content[4]%")->first();

            DB::table('participations')->insert(['scout_name' => $content[0], 'first_name' => $content[1], 'last_name' => $content[2], 'FK_GRP' => $grp->id, 'FK_EXER' => $exer->id]);
        }

        return redirect()->back()->with('message', 'Die TN wurden importiert!');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param $pid
     *
     * @return Application|Factory|Response|View
     */
    public function edit($pid)
    {
        $participations = DB::table('participations')->where('id', '=', $pid)->first();
        $groups = DB::table('group')->select('group.id', 'group.group_name')->get();
        $exer = DB::table('exer')->select('exer.id', 'exer.exer_name')->get();

        return view('participations.edit', ['participations' => $participations, 'groups' => $groups, 'exer' => $exer]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param                          $pid
     *
     * @return RedirectResponse
     */
    public function update(Request $request, $pid)
    {
        $scout_name = $request->input('scout_name');
        $first_name = $request->input('first_name');
        $last_name = $request->input('last_name');
        $group = $request->input('group');
        $exer = $request->input('exer');

        DB::table('participations')->where('id', '=', $pid)->update(['scout_name' => $scout_name, 'first_name' => $first_name, 'last_name' => $last_name, 'FK_GRP' => $group, 'FK_EXER' => $exer]);

        return redirect()->back()->with('message', 'Teilnehmer wurde aktualisiert.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $uid
     *
     * @return RedirectResponse
     */
    public function destroy($uid)
    {
        DB::table('participations')->where('id', '=', $uid)->delete();

        return redirect()->back()->with('message', 'Teilnehmer erfolgreich gelöscht.');
    }
}
