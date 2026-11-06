<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(): View
    {
        $expenses = Expense::query()->latest('spent_on')->latest('id')->paginate(10);

        return view('admin.expenses.index', compact('expenses'));
    }

    public function create(): View
    {
        $expense = new Expense(['spent_on' => now(config('app.shop_timezone'))->toDateString()]);

        return view('admin.expenses.create', compact('expense'));
    }

    public function store(Request $request): RedirectResponse
    {
        Expense::create($this->validatedData($request) + ['user_id' => $request->user()?->id]);

        return redirect()->route('admin.expenses.index')->with('status', 'Expense added.');
    }

    public function edit(Expense $expense): View
    {
        return view('admin.expenses.edit', compact('expense'));
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $expense->update($this->validatedData($request));

        return redirect()->route('admin.expenses.index')->with('status', 'Expense updated.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('admin.expenses.index')->with('status', 'Expense deleted.');
    }

    private function validatedData(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(Expense::CATEGORIES)],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'spent_on' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
