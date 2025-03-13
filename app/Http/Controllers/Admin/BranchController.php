<?php

namespace App\Http\Controllers\admin;

use App\Account;
use App\Branch;
use App\Http\Controllers\Controller;
use App\PaymentGateway;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $getbranch = Branch::where('is_deleted', '2')->get();

        return view('branch', compact('getbranch'));
    }

    public function list()
    {
        $getbranch = Branch::where('is_deleted', '2')->get();

        return view('theme.branchtable', compact('getbranch'));
    }

    public function create()
    {
        $branch = new Branch;
        $accounts = Account::orderBy('description')->get();
        $gateways = PaymentGateway::all();

        return view('branch-form', compact('branch', 'gateways', 'accounts'));
    }

    public function store(Request $request)
    {
        $data = $this->validate($request, [
            'branch_name' => 'required',
            'branch_name_ar' => 'required',
            'phone' => 'nullable:digits:8',
            'longitude' => 'nullable:numeric',
            'latitude' => 'nullable:numeric',
            'city_id' => 'required:integer',
            'address' => 'nullable:string',
            'address_ar' => 'nullable:string',
            'payment_gateway_id' => 'required|integer|exists:payment_gateways,id',
            'debit_bank_id' => 'nullable|integer',
            'debit_cash_id' => 'nullable|integer',
        ]);

        Branch::create($data);

        return redirect('admin/branch');
    }

    public function edit(Branch $branch)
    {
        $gateways = PaymentGateway::all();
        $accounts = Account::orderBy('description')->get();

        return view('branch-form', compact('branch', 'gateways', 'accounts'));
    }

    public function update(Request $request, Branch $branch)
    {
        $data = $this->validate($request, [
            'branch_name' => 'required',
            'branch_name_ar' => 'required',
            'phone' => 'nullable:digits:8',
            'longitude' => 'nullable:numeric',
            'latitude' => 'nullable:numeric',
            'city_id' => 'required:integer',
            'address' => 'nullable:string',
            'address_ar' => 'nullable:string',
            'payment_gateway_id' => 'required|integer|exists:payment_gateways,id',
            'debit_bank_id' => 'nullable|integer',
            'debit_cash_id' => 'nullable|integer',
        ]);

        $branch->update($data);

        return redirect('admin/branch');
    }

    public function status(Request $request)
    {
        $branch = Branch::where('id', $request->id)->update(['is_available' => $request->status]);
        if ($branch) {
            return 1;
        } else {
            return 0;
        }
    }

    public function delete(Request $request)
    {
        $branch = Branch::where('id', $request->id)->update(['is_deleted' => '1']);
        if ($branch) {
            return 1;
        } else {
            return 0;
        }
    }
}
