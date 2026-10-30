<?php

namespace App\Services\Factus\Builders\V2;

/**
 * Construye el payload real de Factus V2 para nómina electrónica.
 * Referencia: https://developers.factus.com.co/nomina/descripcion-de-campos/
 */
class PayrollFactusBuilder
{
    /**
     * @param array $payrollData Debe incluir: reference_code, observation, numbering_range_id,
     *                           settlement_period[month,year,payroll_period_code,pay_period_half],
     *                           payment[payment_method_code,bank_name,account_type,account_number,payment_date],
     *                           worker[...], salbas, auxtra, dedsal, dedpen, salud_pct, pension_pct
     * @param array $extraAccruals   Novedades de devengo adicionales: [['amount','description','accrual_type_code']]
     * @param array $extraDeductions Novedades de deducción adicionales: [['amount','deduction_type_code']]
     */
    public function build(array $payrollData, array $extraAccruals = [], array $extraDeductions = []): array
    {
        $worker = $payrollData['worker'] ?? [];
        $settlement = $payrollData['settlement_period'] ?? [];
        $payment = $payrollData['payment'] ?? [];

        $accruals = [
            'suel' => [
                'amount' => $this->money($payrollData['salbas'] ?? 0),
            ],
        ];

        if ((float) ($payrollData['auxtra'] ?? 0) > 0) {
            $accruals['tra'] = [[
                'amount' => $this->money($payrollData['auxtra']),
                'accrual_type_code' => 1,
            ]];
        }

        if (!empty($extraAccruals)) {
            $accruals['otro'] = collect($extraAccruals)->map(fn ($item) => [
                'amount' => $this->money($item['amount'] ?? 0),
                'description' => $item['description'] ?? 'Otro devengo',
                'accrual_type_code' => $item['accrual_type_code'] ?? 1,
            ])->values()->all();
        }

        $deductions = [
            'salu' => [
                'percentage' => $this->money($payrollData['salud_pct'] ?? 4),
                'amount' => $this->money($payrollData['dedsal'] ?? 0),
            ],
            'pens' => [
                'percentage' => $this->money($payrollData['pension_pct'] ?? 4),
                'amount' => $this->money($payrollData['dedpen'] ?? 0),
            ],
        ];

        if (!empty($extraDeductions)) {
            $deductions['otra'] = collect($extraDeductions)->map(fn ($item) => [
                'amount' => $this->money($item['amount'] ?? 0),
                'deduction_type_code' => $item['deduction_type_code'] ?? 1,
            ])->values()->all();
        }

        return array_filter([
            'reference_code' => $payrollData['reference_code'] ?? '',
            'observation' => $payrollData['observation'] ?? '',
            'numbering_range_id' => $payrollData['numbering_range_id'] ?? null,
            'settlement_period' => array_filter([
                'month' => (int) ($settlement['month'] ?? now()->month),
                'year' => (int) ($settlement['year'] ?? now()->year),
                'payroll_period_code' => (string) ($settlement['payroll_period_code'] ?? '5'),
                'pay_period_half' => $settlement['pay_period_half'] ?? null,
            ], fn ($v) => $v !== null),
            'payment' => array_filter([
                'payment_method_code' => (string) ($payment['payment_method_code'] ?? '1'),
                'bank_name' => $payment['bank_name'] ?? null,
                'account_type' => $payment['account_type'] ?? null,
                'account_number' => $payment['account_number'] ?? null,
                'payment_date' => $payment['payment_date'] ?? now()->toDateString(),
            ], fn ($v) => $v !== null),
            'worker' => array_filter([
                'identification_document_code' => (string) ($worker['identification_document_code'] ?? ''),
                'identification_number' => (string) ($worker['identification_number'] ?? ''),
                'first_name' => $worker['first_name'] ?? '',
                'other_names' => $worker['other_names'] ?? null,
                'first_surname' => $worker['first_surname'] ?? '',
                'second_surname' => $worker['second_surname'] ?? '',
                'address' => $worker['address'] ?? '',
                'country_code' => $worker['country_code'] ?? 'CO',
                'municipality_code' => $worker['municipality_code'] ?? null,
                'has_integral_salary' => (bool) ($worker['has_integral_salary'] ?? false),
                'has_high_risk' => (bool) ($worker['has_high_risk'] ?? false),
                'worker_type_code' => (string) ($worker['worker_type_code'] ?? '01'),
                'worker_subtype' => (string) ($worker['worker_subtype'] ?? '00'),
                'contract_type' => (string) ($worker['contract_type'] ?? '1'),
                'employee_code' => $worker['employee_code'] ?? null,
                'salary' => $this->money($worker['salary'] ?? 0),
                'entry_date' => $worker['entry_date'] ?? null,
                'days_worked' => (string) ($worker['days_worked'] ?? '0'),
                'retirement_date' => $worker['retirement_date'] ?? null,
            ], fn ($v) => $v !== null),
            'accruals' => $accruals,
            'deductions' => $deductions,
        ], fn ($v) => $v !== null);
    }

    private function money($value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
