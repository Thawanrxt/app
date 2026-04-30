<?php

namespace App\Http\Controllers;

use App\Models\RiceVariety;
use App\Support\SearchTextMatcher;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class RiceVarietyController extends Controller
{
    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));
        $riceVarieties = $this->resolveRiceVarieties($query);
        $allRiceVarieties = $query === '' ? $riceVarieties : $this->resolveRiceVarieties();

        return view('admin.rice', [
            'query' => $query,
            'riceVarieties' => $riceVarieties,
            'riceSummary' => $this->buildRiceSummary($riceVarieties, $allRiceVarieties, $query),
            'riceFieldsAvailable' => $this->riceFieldsAvailable(),
        ]);
    }

    public function create(): View
    {
        return view('admin.rice-create', [
            'riceFieldsAvailable' => $this->riceFieldsAvailable(),
        ]);
    }

    public function edit(RiceVariety $riceVariety): View
    {
        return view('admin.rice-edit', [
            'riceVariety' => $riceVariety,
            'riceFieldsAvailable' => $this->riceFieldsAvailable(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validatePayload($request);

        RiceVariety::create([
            'id' => (string) Str::uuid(),
            'is_active' => true,
            ...$this->normalizePayload($validated),
        ]);

        return redirect('/admin/rice')->with('success', 'บันทึกข้อมูลพันธุ์ข้าวเรียบร้อยแล้ว');
    }

    public function update(Request $request, RiceVariety $riceVariety): RedirectResponse
    {
        $validated = $this->validatePayload($request, $riceVariety);

        $riceVariety->update($this->normalizePayload($validated));

        return redirect('/admin/rice')->with('success', 'แก้ไขข้อมูลพันธุ์ข้าวเรียบร้อยแล้ว');
    }

    public function destroy(RiceVariety $riceVariety): RedirectResponse
    {
        if (! $this->activeStatusAvailable()) {
            return redirect('/admin/rice')->with(
                'error',
                'ยังยกเลิกใช้งานไม่ได้ เพราะฐานข้อมูลยังไม่มีคอลัมน์ is_active'
            );
        }

        $riceVariety->update(['is_active' => false]);

        return redirect('/admin/rice')->with('success', 'ยกเลิกใช้งานพันธุ์ข้าวเรียบร้อยแล้ว');
    }

    public function restore(RiceVariety $riceVariety): RedirectResponse
    {
        if (! $this->activeStatusAvailable()) {
            return redirect('/admin/rice')->with(
                'error',
                'ยังกู้คืนข้อมูลไม่ได้ เพราะฐานข้อมูลยังไม่มีคอลัมน์ is_active'
            );
        }

        $riceVariety->update(['is_active' => true]);

        return redirect('/admin/rice')->with('success', 'กู้คืนพันธุ์ข้าวเรียบร้อยแล้ว');
    }

    public function forceDestroy(RiceVariety $riceVariety): RedirectResponse
    {
        try {
            DB::transaction(function () use ($riceVariety): void {
                DB::table('activity_standards')
                    ->where('rice_variety_id', $riceVariety->id)
                    ->delete();

                DB::table('activity_templates')
                    ->where('rice_id', $riceVariety->id)
                    ->delete();

                DB::table('planting_plans')
                    ->where('rice_id', $riceVariety->id)
                    ->delete();

                $riceVariety->delete();
            });
        } catch (QueryException $exception) {
            if ($this->isForeignKeyConstraintViolation($exception)) {
                return redirect('/admin/rice')->with(
                    'error',
                    'ลบออกจากฐานข้อมูลไม่ได้ เพราะยังมีข้อมูลอื่นในระบบอ้างอิงพันธุ์ข้าวนี้อยู่'
                );
            }

            throw $exception;
        }

        return redirect('/admin/rice')->with('success', 'ลบข้อมูลพันธุ์ข้าวออกจากฐานข้อมูลเรียบร้อยแล้ว');
    }

    private function isForeignKeyConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (string) ($exception->errorInfo[1] ?? '');

        return $sqlState === '23503' || $driverCode === '23503';
    }

    private function resolveRiceVarieties(string $query = '')
    {
        try {
            $riceQuery = RiceVariety::query();

            if ($this->activeStatusAvailable()) {
                $riceQuery->orderByDesc('is_active');
            }

            $riceVarieties = $riceQuery
                ->orderBy('name')
                ->get()
                ->values();

            return SearchTextMatcher::filterByPriority($riceVarieties, [
                fn (RiceVariety $riceVariety) => $riceVariety->rice_type,
                fn (RiceVariety $riceVariety) => $riceVariety->name,
                fn (RiceVariety $riceVariety) => $riceVariety->standard_duration_days ?: $riceVariety->grow_duration_days,
                fn (RiceVariety $riceVariety) => $riceVariety->disease_resistance,
                fn (RiceVariety $riceVariety) => implode(' ', $riceVariety->pest_resistances ?? []),
            ], $query);
        } catch (Throwable) {
            return collect();
        }
    }

    private function buildRiceSummary($riceVarieties, $allRiceVarieties, string $query): array
    {
        $total = $allRiceVarieties->count();
        $filtered = $riceVarieties->count();
        $riceTypes = $allRiceVarieties
            ->pluck('rice_type')
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->count();
        $withDiseaseResistance = $allRiceVarieties
            ->filter(fn (RiceVariety $riceVariety) => filled($riceVariety->disease_resistance))
            ->count();
        $withPestResistance = $allRiceVarieties
            ->filter(function (RiceVariety $riceVariety): bool {
                return collect($riceVariety->pest_resistances ?? [])
                    ->filter(fn ($value) => filled($value))
                    ->isNotEmpty();
            })
            ->count();

        return [
            'total' => $total,
            'filtered' => $filtered,
            'rice_types' => $riceTypes,
            'with_disease_resistance' => $withDiseaseResistance,
            'with_pest_resistance' => $withPestResistance,
            'has_query' => $query !== '',
        ];
    }

    private function riceFieldsAvailable(): bool
    {
        try {
            return Schema::hasColumns('rice_varieties', [
                'rice_type',
                'standard_duration_days',
                'disease_resistance',
                'pest_resistances',
            ]);
        } catch (Throwable) {
            return false;
        }
    }

    private function activeStatusAvailable(): bool
    {
        try {
            return Schema::hasColumn('rice_varieties', 'is_active');
        } catch (Throwable) {
            return false;
        }
    }

    private function validatePayload(Request $request, ?RiceVariety $riceVariety = null): array
    {
        return $request->validate([
            'rice_type' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255', Rule::unique('rice_varieties', 'name')->ignore($riceVariety?->id)],
            'standard_duration_days' => ['required', 'string', 'max:255'],
            'disease_resistance' => ['nullable', 'string', 'max:255'],
            'pest_resistances' => ['nullable', 'array'],
            'pest_resistances.*' => ['nullable', 'string', 'max:255'],
        ]);
    }

    private function normalizePayload(array $validated): array
    {
        $pestResistances = collect($validated['pest_resistances'] ?? [])
            ->filter(fn (?string $value): bool => filled($value))
            ->values()
            ->all();

        preg_match('/\d+/', $validated['standard_duration_days'], $matches);

        return [
            'rice_type' => $validated['rice_type'],
            'name' => $validated['name'],
            'standard_duration_days' => $validated['standard_duration_days'],
            'disease_resistance' => $validated['disease_resistance'] ?? null,
            'pest_resistances' => $pestResistances,
            'grow_duration_days' => isset($matches[0]) ? (int) $matches[0] : null,
        ];
    }
}
