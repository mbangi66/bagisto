<?php

namespace App\Http\Controllers\Admin;

use App\Models\Block;
use App\Models\Branch;
use App\Models\City;
use App\Http\Controllers\Controller;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BlockController extends Controller
{
    public function index()
    {
        $getstate = State::where('is_available', '1')->where('is_deleted', '2')->get()->keyBy('id');
        $getcity = City::where('is_available', '1')
            ->where('is_deleted', '2')
            ->get()
            ->keyBy('id');
        $getbranch = Branch::where('is_available', '1')->where('is_deleted', '2')->get()->keyBy('id');
        $getblock = Block::all();

        return view('block', compact('getcity', 'getstate', 'getblock', 'getbranch'));
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'city_id' => 'required|integer|exists:city,id',
            'branch_id' => 'nullable|integer|exists:branchs,id',
            'name_en' => 'required',
            'name_ar' => 'required',
        ]);
        $error_array = [];
        $success_output = '';
        if ($validation->fails()) {
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
        } else {
            $data = $validation->validated();
            $data['state_id'] = City::find($request->city_id)->state_id;
            Block::create($data);
            $success_output = trans('messages.success');
        }
        $output = [
            'error' => $error_array,
            'success' => $success_output,
        ];
        echo json_encode($output);
    }

    public function show(Request $request)
    {
        $getblock = Block::findorFail($request->id);

        return response()->json(['ResponseCode' => 1, 'ResponseText' => trans('messages.successfull'), 'ResponseData' => $getblock], 200);
    }

    public function update(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'city_id' => 'required|integer|exists:city,id',
            'branch_id' => 'nullable|integer|exists:branchs,id',
            'name_en' => 'required',
            'name_ar' => 'required',
        ]);
        $error_array = [];
        $success_output = '';
        if ($validation->fails()) {
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
        } else {
            $data = $validation->validated();
            $data['state_id'] = City::find($request->city_id)->state_id;
            Block::find($request->id)->update($data);
            $success_output = trans('messages.update');
        }
        $output = [
            'error' => $error_array,
            'success' => $success_output,
        ];
        echo json_encode($output);
    }

    public function status(Request $request)
    {
        $block = Block::where('id', $request->id)->update(['is_available' => $request->status]);

        return $block ? 1 : 0;
    }

    public function delete(Request $request)
    {
        $block = Block::where('id', $request->id)->delete();

        return $block ? 1 : 0;
    }
}
