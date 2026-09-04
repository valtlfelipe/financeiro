<?php

namespace App\Http\Requests;

use App\CategoryType;
use App\Models\Category;
use App\RecurrenceFrequency;
use App\SeriesKind;
use App\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Validator;

class StoreTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'amount_minor' => ['required', 'integer', 'min:1'],
            'description' => ['required', 'string', 'max:180'],
            'account_id' => [
                'required',
                'integer',
                $this->accountExistsRule('account_id'),
            ],
            'destination_account_id' => [
                'nullable',
                'required_if:type,'.TransactionType::Transfer->value,
                'different:account_id',
                $this->accountExistsRule('destination_account_id'),
            ],
            'category_id' => [
                'nullable',
                'required_unless:type,'.TransactionType::Transfer->value,
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('workspace_id', $this->user()?->currentWorkspaceOrFail()->id)
                    ->where('is_archived', false)),
            ],
            'due_on' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'settled' => ['sometimes', 'boolean'],
            'series_kind' => ['nullable', Rule::enum(SeriesKind::class)],
            'frequency' => [
                'nullable',
                'required_if:series_kind,'.SeriesKind::Recurring->value,
                Rule::enum(RecurrenceFrequency::class),
            ],
            'ends_on' => ['nullable', 'date', 'after_or_equal:due_on'],
            'installments' => [
                'nullable',
                'required_if:series_kind,'.SeriesKind::Installment->value,
                'integer',
                'min:2',
                'max:120',
            ],
        ];
    }

    private function accountExistsRule(string $column): Exists
    {
        $workspace = $this->user()?->currentWorkspaceOrFail();
        $currentAccountId = null;
        $transactionId = $this->route('transaction');

        if ($workspace !== null && is_numeric($transactionId)) {
            $value = $workspace->transactions()->whereKey((int) $transactionId)->value($column);
            $currentAccountId = is_numeric($value) ? (int) $value : null;
        }

        return Rule::exists('accounts', 'id')->where(fn ($query) => $query
            ->where('workspace_id', $workspace?->id)
            ->where(fn ($accountQuery) => $accountQuery
                ->where('is_archived', false)
                ->when($currentAccountId !== null, fn ($allowedQuery) => $allowedQuery->orWhere('id', $currentAccountId))));
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (
                    ! $validator->errors()->hasAny(['amount_minor', 'series_kind', 'installments'])
                    && $this->string('series_kind')->toString() === SeriesKind::Installment->value
                    && $this->integer('installments') > $this->integer('amount_minor')
                ) {
                    $validator->errors()->add('installments', __('app.transaction.installments_too_many'));
                }

                if ($validator->errors()->hasAny(['type', 'category_id'])) {
                    return;
                }

                if ($this->string('type')->toString() === TransactionType::Transfer->value) {
                    return;
                }

                $category = Category::query()
                    ->where('workspace_id', $this->user()?->currentWorkspaceOrFail()->id)
                    ->find($this->integer('category_id'));

                $expectedType = $this->string('type')->toString() === TransactionType::Income->value
                    ? CategoryType::Income
                    : CategoryType::Expense;

                if ($category !== null && ! in_array($category->type, [$expectedType, CategoryType::Both], true)) {
                    $validator->errors()->add('category_id', __('validation.in', [
                        'attribute' => __('validation.attributes.category_id'),
                    ]));
                }
            },
        ];
    }
}
