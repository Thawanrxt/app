@extends('admin.layout')
@section('title', 'พันธุ์ข้าว')
@section('content')
<div class="page-head">
  <div>
    <h1>พันธุ์ข้าว</h1>
    <p class="muted">จัดการข้อมูลพันธุ์ข้าวในระบบด้วยข้อมูลจริงจากฐานข้อมูล</p>
  </div>
</div>

@if (session('success'))
  <div class="card flash-auto-dismiss" style="margin-top: 16px; border-color: #86efac; background: #f0fdf4; color: #166534;">
    {{ session('success') }}
  </div>
@endif

@if (session('error'))
  <div class="card flash-auto-dismiss" style="margin-top: 16px; border-color: #fca5a5; background: #fef2f2; color: #991b1b;">
    {{ session('error') }}
  </div>
@endif

<div class="card" style="margin-top: 16px;">
  <form class="search-row" action="/admin/rice" method="get">
    <div class="search-field">
      <input
        class="search-input"
        type="text"
        name="q"
        placeholder="ค้นหาชื่อพันธุ์ข้าว ประเภทข้าว หรือความต้านทานโรค"
        value="{{ $query }}"
      >
    </div>
    <button class="search-btn search-btn--standalone" type="submit" aria-label="ค้นหา">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="11" cy="11" r="7"></circle>
        <path d="M20 20l-3.5-3.5"></path>
      </svg>
    </button>
    <a class="btn primary" href="/admin/rice/create">เพิ่มพันธุ์ข้าว</a>
  </form>
  @if ($riceSummary['has_query'])
    <p class="muted" style="margin: 8px 8px 0;">
      พบ {{ $riceSummary['filtered'] }} รายการ จากทั้งหมด {{ $riceSummary['total'] }} รายการ
    </p>
  @endif
</div>

<div class="card" style="margin-top: 16px;">
  <div class="card-head">
    <h3>รายการพันธุ์ข้าว</h3>
    <span class="tag">{{ $riceSummary['filtered'] }} รายการ</span>
  </div>
  <table class="table" style="margin-top: 12px;">
    <thead>
      <tr>
        <th>ประเภทข้าว</th>
        <th>ชื่อพันธุ์ข้าว</th>
        <th>ระยะเวลามาตรฐาน</th>
        <th>ความต้านทานโรค</th>
        <th>ความต้านทานศัตรูพืช</th>
        <th>จัดการ</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($riceVarieties as $riceVariety)
        <tr>
          <td>{{ $riceVariety->rice_type ?: '-' }}</td>
          <td>{{ $riceVariety->name ?: '-' }}</td>
          <td>{{ $riceVariety->standard_duration_days ?: ($riceVariety->grow_duration_days ? $riceVariety->grow_duration_days . ' วัน' : '-') }}</td>
          <td>{{ $riceVariety->disease_resistance ?: '-' }}</td>
          <td>{{ filled(implode(', ', $riceVariety->pest_resistances ?? [])) ? implode(', ', $riceVariety->pest_resistances ?? []) : '-' }}</td>
          <td>
            <div class="table-actions">
              <a class="icon-action-btn compact" href="/admin/rice/{{ $riceVariety->id }}/edit" aria-label="แก้ไขข้อมูลพันธุ์ข้าว">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 20h9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
              @if (($riceVariety->is_active ?? true))
                <form method="POST" action="/admin/rice/{{ $riceVariety->id }}/delete" onsubmit="return confirm('ยืนยันการยกเลิกใช้งานพันธุ์ข้าวนี้?')">
                  @csrf
                  <button class="icon-action-btn danger compact" type="submit" title="ยกเลิกใช้งานข้อมูล" aria-label="ยกเลิกใช้งานข้อมูลพันธุ์ข้าว">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                      <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/>
                    </svg>
                  </button>
                </form>
              @else
                <form method="POST" action="/admin/rice/{{ $riceVariety->id }}/restore">
                  @csrf
                  <button class="icon-action-btn compact" type="submit" title="กู้คืนข้อมูล" aria-label="กู้คืนข้อมูลพันธุ์ข้าว" style="color: #4CAF50; border-color: rgba(76, 175, 80, 0.35);">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                      <path d="M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8"/>
                      <path d="M3 3v5h5"/>
                    </svg>
                  </button>
                </form>
                <form method="POST" action="/admin/rice/{{ $riceVariety->id }}/force-delete" onsubmit="return confirm('ยืนยันการลบข้อมูลพันธุ์ข้าวนี้ออกจากฐานข้อมูลถาวร?')">
                  @csrf
                  <button class="icon-action-btn danger compact" type="submit" title="ลบออกจากฐานข้อมูลถาวร" aria-label="ลบข้อมูลพันธุ์ข้าวออกจากฐานข้อมูลถาวร">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                      <path d="M3 6h18"/>
                      <path d="M8 6V4h8v2"/>
                      <path d="M19 6l-1 14H6L5 6"/>
                      <path d="M10 11v6M14 11v6"/>
                    </svg>
                  </button>
                </form>
              @endif
            </div>
            @if (isset($riceVariety->is_active) && ! $riceVariety->is_active)
              <div class="muted" style="margin-top: 6px; font-size: 12px;">ยกเลิกแล้ว</div>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="muted" style="text-align:center; padding:24px;">
            {{ $query !== '' ? 'ไม่พบข้อมูลพันธุ์ข้าวที่ตรงกับคำค้นนี้' : 'ยังไม่มีข้อมูลพันธุ์ข้าวในระบบ' }}
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
