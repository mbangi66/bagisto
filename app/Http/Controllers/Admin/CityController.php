<?php

namespace App\Http\Controllers\Admin;

use App\Models\Branch;
use App\Models\City;
use App\Http\Controllers\Controller;
use App\Models\State;
use Illuminate\Http\Request;
use Validator;

class CityController extends Controller
{
    public function index()
    {
        $getstate = State::where('is_available', '1')->where('is_deleted', '2')->get();
        $getcity = City::with(['state', 'branch'])->where('is_deleted', '2')->get();
        $getbranch = Branch::where('is_deleted', '2')->get();

        return view('city', compact('getcity', 'getstate', 'getbranch'));
    }

    public function list()
    {
        $getcity = City::with(['state', 'branch'])->where('is_deleted', '2')->get();

        return view('theme.citytable', compact('getcity'));
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'state_id' => 'required',
            'city_name' => 'required',
            'city_name_ar' => 'required',
            'branch_id' => 'nullable|integer',
            'delivery_fee' => 'required|numeric',
            'delivery_fee_outside' => 'required|numeric',
            'min_order' => 'required',
        ]);
        $error_array = [];
        $success_output = '';
        if ($validation->fails()) {
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
        } else {
            $city = new City;
            $city->state_id = $request->state_id;
            $city->city_name = $request->city_name;
            $city->city_name_ar = $request->city_name_ar;
            $city->branch_id = $request->branch_id;
            $city->delivery_fee = $request->delivery_fee;
            $city->delivery_fee_outside = $request->delivery_fee_outside;
            $city->min_order = $request->min_order;
            $city->save();
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
        $getcity = City::findOrFail($request->id);
        // Make sure branch_id or any other hidden fields are visible
        $getcity->makeVisible($getcity->getHidden());

        return response()->json(['ResponseCode' => 1, 'ResponseText' => trans('messages.successfull'), 'ResponseData' => $getcity], 200);
    }

    public function update(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'state_id' => 'required',
            'city_name' => 'required',
            'city_name_ar' => 'required',
            'branch_id' => 'nullable|integer',
            'delivery_fee' => 'required|numeric',
            'delivery_fee_outside' => 'required|numeric',
            'min_order' => 'required',
        ]);
        $error_array = [];
        $success_output = '';
        if ($validation->fails()) {
            foreach ($validation->messages()->getMessages() as $field_name => $messages) {
                $error_array[] = $messages;
            }
        } else {
            $city = new City;
            $city->exists = true;
            $city->id = $request->id;
            $city->state_id = $request->state_id;
            $city->city_name = $request->city_name;
            $city->city_name_ar = $request->city_name_ar;
            $city->branch_id = $request->branch_id;
            $city->delivery_fee = $request->delivery_fee;
            $city->delivery_fee_outside = $request->delivery_fee_outside;
            $city->min_order = $request->min_order;
            $city->save();
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
        $city = City::where('id', $request->id)->update(['is_available' => $request->status]);
        if ($city) {
            return 1;
        } else {
            return 0;
        }
    }

    public function delete(Request $request)
    {
        $city = City::where('id', $request->id)->update(['is_deleted' => '1']);
        if ($city) {
            return 1;
        } else {
            return 0;
        }
    }
}
