<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\State;
use Illuminate\Http\Request;
use Validator;

class StateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $getstate = State::where('is_deleted', '2')->get();

        return view('state', compact('getstate'));
    }

    public function list()
    {
        $getstate = State::where('is_deleted', '2')->get();

        return view('theme.statetable', compact('getstate'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $s
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'state_name' => 'required',
            'state_name_ar' => 'required',
        ]);
        $error_array = [];
        $success_output = '';
        if ($validation->fails()) {
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
        } else {
            $state = new State;
            $state->state_name = $request->state_name;
            $state->state_name_ar = $request->state_name_ar;
            $state->save();
            $success_output = trans('messages.success');
        }
        $output = [
            'error' => $error_array,
            'success' => $success_output,
        ];
        echo json_encode($output);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $getstate = State::findorFail($request->id);

        return response()->json(['ResponseCode' => 1, 'ResponseText' => trans('messages.successfull'), 'ResponseData' => $getstate], 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $req)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'state_name' => 'required',
            'state_name_ar' => 'required',
        ]);
        $error_array = [];
        $success_output = '';
        if ($validation->fails()) {
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
        } else {
            $state = new State;
            $state->exists = true;
            $state->id = $request->id;
            $state->state_name = $request->state_name;
            $state->state_name_ar = $request->state_name_ar;
            $state->save();
            $success_output = trans('messages.update');
        }
        $output = [
            'error' => $error_array,
            'success' => $success_output,
        ];
        echo json_encode($output);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function status(Request $request)
    {
        $state = State::where('id', $request->id)->update(['is_available' => $request->status]);
        if ($state) {
            return 1;
        } else {
            return 0;
        }
    }

    public function delete(Request $request)
    {
        $state = State::where('id', $request->id)->update(['is_deleted' => '1']);
        if ($state) {
            return 1;
        } else {
            return 0;
        }
    }
}
