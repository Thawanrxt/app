@extends('admin.layout')
@section('title', 'การจัดการศัตรูพืช')
@section('content')
@php
  $statusOptions = [
    'pending_review' => 'รอตรวจสอบ',
    'passed' => 'ผ่านแล้ว',
    'needs_fix' => 'ต้องแก้ไข',
    'failed' => 'ไม่ผ่าน',
  ];

  $statusLabels = [
    'pending_review' => ['label' => 'รอตรวจสอบ', 'class' => 'warning'],
    'passed' => ['label' => 'ผ่านแล้ว', 'class' => 'success'],
    'needs_fix' => ['label' => 'ต้องแก้ไข', 'class' => 'danger'],
    'failed' => ['label' => 'ไม่ผ่าน', 'class' => 'danger'],
  ];
@endphp

<div class="page-head">
  <div>
    <h1>การจัดการศัตรูพืช</h1>
    <p class="muted">รายการกิจกรรมที่ส่งเข้ามาจากฝั่งยูสเซอร์สำหรับการจัดการศัตรูพืช</p>
  </div>
  <a class="btn primary" href="/admin/tracking/pest/print" target="_blank" rel="noopener">Export PDF</a>
</div>

@if (session('success'))
  <div class="status-banner success" style="margin-top: 16px;">{{ session('success') }}</div>
@endif

<div class="card" style="margin-top: 16px;">
  <form class="prep-filters" method="GET" action="/admin/tracking/pest">
    <div class="search-field">
      <input class="search-input" name="q" type="text" placeholder="ค้นหาเกษตรกรหรือชื่อแปลง" value="{{ $query }}">
      <button class="search-btn" type="submit" aria-label="ค้นหา">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="11" cy="11" r="7"></circle>
          <path d="M20 20l-3.5-3.5"></path>
        </svg>
      </button>
    </div>
    <div class="filter-group">
      <select class="input filter-round" name="round">
        <option value="">ครั้งที่</option>
        @foreach ([1, 2, 3, 4, 5] as $roundOption)
          <option value="{{ $roundOption }}" @selected((string) $roundOption === $round)>{{ $roundOption }}</option>
        @endforeach
      </select>
      <select class="input" name="status">
        <option value="">สถานะ</option>
        @foreach ($statusOptions as $statusValue => $statusLabel)
          <option value="{{ $statusValue }}" @selected($statusValue === $status)>{{ $statusLabel }}</option>
        @endforeach
      </select>
      <input class="input" name="date" type="date" value="{{ $date }}">
    </div>
  </form>
</div>

<div class="card" style="margin-top: 16px;">
  <table class="table">
    <thead>
      <tr>
        <th>เกษตรกร</th>
        <th>ชื่อแปลง</th>
        <th>ครั้งที่</th>
        <th>กิจกรรม</th>
        <th>วันที่</th>
        <th>สถานะ</th>
        <th>ดำเนินการ</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($activities as $activity)
        @php
          $statusData = $statusLabels[$activity->status] ?? ['label' => 'รอตรวจสอบ', 'class' => 'warning'];
        @endphp
        <tr>
          <td>{{ $activity->farmer_name }}</td>
          <td>{{ $activity->plot_code }}</td>
          <td>{{ $activity->round_number ?: '-' }}</td>
          <td>{{ $activity->activity_name }}</td>
          <td>{{ optional($activity->activity_date)->translatedFormat('d M Y') }}</td>
          <td><span class="chip {{ $statusData['class'] }}">{{ $statusData['label'] }}</span></td>
          <td>
            <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
              <a class="btn ghost btn-sm" href="/admin/tracking/pest/detail/{{ $activity->id }}">ดูรายละเอียด</a>
              <form method="POST" action="/admin/tracking/pest/{{ $activity->id }}/delete" data-confirm-delete="ยืนยันลบรายการติดตามนี้ใช่หรือไม่?" style="margin:0;">
                @csrf
                <button class="icon-action-btn danger compact" type="submit" title="ลบรายการติดตาม" aria-label="ลบรายการติดตาม">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M3 6h18"/>
                    <path d="M8 6V4h8v2"/>
                    <path d="M19 6l-1 14H6L5 6"/>
                    <path d="M10 11v6M14 11v6"/>
                  </svg>
                </button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="muted" style="text-align:center; padding:24px;">ยังไม่มีข้อมูลจากฝั่งยูสเซอร์</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
