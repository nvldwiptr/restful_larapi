<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Meeting;

class MeetingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $meetings = Meeting::orderBy('id', 'ASC')->get();
        foreach($meetings as $row) {
            $row->view_meeting = [
                'href' => 'api/v1/meeting/'. $row->id,
                'method' => 'GET'
            ];
        }

        $response = [
            'msg' => 'List of All Meetings',
            'meetings' => $meetings
        ];

        return response()->json($response, 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'time' => 'required',
            'user_id' => 'required'
        ]);

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $request->input('user_id');

        $meeting = new Meeting([
            'time' => $time,
            'title' => $title,
            'description' => $description
        ]);

        if($meeting->save()){
            $meeting->users()->attach($user_id);
            $meeting->view_meeting = [
                'href' => 'api/v1/meeting/' . $meeting->id,
                'method' => 'GET'
            ];

            $message = [
                'msg' => 'Meeting Created!',
                'meeting' => $meeting
            ];

            return response()->json($message, 201);
        }

        $message = [
            'msg' => 'An error occured!'
        ];

        return response()->json($message, 404);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $meeting = Meeting::with('users')->where('id', $id)->firstOrFail();
        $meeting->view_meeting = [
            'href' => 'api/v1/meeting',
            'method' => 'GET'
        ];

        $response = [
            'msg' => 'Information of Meeting',
            'meeting' => $meeting
        ];

        return response()->json($response, 200);
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
        $this->validate($request, [
            'title' => 'required',
            'description' => 'required',
            'time' => 'required',
            'user_id' => 'required'
        ]);

        $title = $request->input('title');
        $description = $request->input('description');
        $time = $request->input('time');
        $user_id = $request->input('user_id');

        $meeting = Meeting::with('users')->findOrFail($id);

        if(!$meeting->users()->where('users.id', $user_id)->first()){
            return response()->json(['msg' => 'user not registered for meeting, update failed!'], 401);
        }

        $meeting->time = $time;
        $meeting->title = $title;
        $meeting->description = $description;

        if(!$meeting->update()) {
            return response()->json(['msg' => 'error during update'], 404);
        }

        $meeting->view_meeting = [
            'href' => 'api/v1/meeting' . $meeting->id,
            'method' => 'GET'
        ];

        $response = [
            'msg' => 'Meeting updated!',
            'meeting' => $meeting
        ];

        return response()->json($response, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $meeting = Meeting::findOrFail($id);
        $users = $meeting->users;
        $meeting->users()->detach();

        if(!$meeting->delete()) {
            foreach($users as $row) {
                $meeting->users()->attach($row);
            }

            return response()->json(['msg' => 'Failed to delete'], 404);
        }

        $response = [
            'msg' => 'Meeting deleted!',
            'create' => [
                'href' => 'api/v1/meeting',
                'method' => 'POST',
                'params' => 'title, description, time'
            ]
        ];

        return response()->json($response, 200);
    }

















}
