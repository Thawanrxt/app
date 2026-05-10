@extends('admin.layout')

@section('title', $pageTitle ?? 'รายการ')

@section('content')
@php
  $pageTitle = $pageTitle ?? 'รายการ';
  $pageDescription = $pageDescription ?? '';
  $items = $items ?? collect();
  $emptyTitle = $emptyTitle ?? 'ยังไม่มีข้อมูล';
  $emptyDescription = $emptyDescription ?? 'เมื่อมีข้อมูล ระบบจะแสดงในหน้านี้';
  $total = $items->count();
  $statsByClass = $items->groupBy('status_class')->map->count();
  $pendingCount = $statsByClass->get('warning', 0);
  $inProgressCount = $statsByClass->get('info', 0);
  $needsFixCount = $statsByClass->get('danger', 0);
@endphp

<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="/admin" aria-label="กลับไปหน้าแดชบอร์ด">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>{{ $pageTitle }}</h1>
      @if ($pageDescription !== '')
        <p class="muted">{{ $pageDescription }}</p>
      @endif
    </div>
  </div>
</div>

<div class="focus-stats">
  <div class="focus-stat-item">
    <div class="focus-stat-value">{{ $total }}</div>
    <div class="focus-stat-label">ทั้งหมด</div>
  </div>
  <div class="focus-stat-item focus-stat-item--warn">
    <div class="focus-stat-value">{{ $pendingCount }}</div>
    <div class="focus-stat-label">รอตรวจสอบ</div>
  </div>
  <div class="focus-stat-item focus-stat-item--info">
    <div class="focus-stat-value">{{ $inProgressCount }}</div>
    <div class="focus-stat-label">กำลังตรวจ</div>
  </div>
  <div class="focus-stat-item focus-stat-item--danger">
    <div class="focus-stat-value">{{ $needsFixCount }}</div>
    <div class="focus-stat-label">ต้องแก้ไข</div>
  </div>
</div>

<form method="GET" action="{{ request()->url() }}" class="focus-search-bar" style="margin-top: 12px;">
  @foreach (request()->except('q') as $paramKey => $paramVal)
    <input type="hidden" name="{{ $paramKey }}" value="{{ $paramVal }}">
  @endforeach
  <div class="search-row">
    <div class="search-field" style="flex:1">
      <input
        type="text"
        name="q"
        class="search-input"
        placeholder="ค้นหาชื่องาน เกษตรกร หรือแปลง..."
        value="{{ request('q', '') }}"
        autocomplete="off"
      >
    </div>
    <button type="submit" class="btn ghost">ค้นหา</button>
    @if (request('q'))
      <a class="btn ghost" href="{{ request()->url() }}{{ count(request()->except('q')) ? '?' . http_build_query(request()->except('q')) : '' }}">ล้าง</a>
    @endif
  </div>
</form>

<div class="card" style="margin-top: 10px;">
  <div class="card-head">
    <h3>{{ $pageTitle }}</h3>
    <span class="muted">{{ $total > 0 ? 'แสดง ' . $total . ' รายการ' : 'ยังไม่มีรายการ' }}</span>
  </div>

  <div class="focus-list">
    @forelse ($items as $item)
      <div class="focus-item focus-item--{{ $item['dot_class'] }}">
        <div class="focus-item-body">
          <div class="focus-item-head">
            <div class="focus-item-title">{{ $item['title'] }}</div>
            <span class="chip {{ $item['status_class'] }}">{{ $item['status_label'] }}</span>
          </div>
          @if (trim($item['detail']) !== '' && $item['detail'] !== 'ยังไม่มีรายละเอียดเพิ่มเติม')
            <div class="focus-item-detail">{{ $item['detail'] }}</div>
          @endif
          <div class="focus-item-meta">
            <span class="chip info">{{ $item['badge_label'] }}</span>
            @if (($item['category_label'] ?? '') !== '')
              <span class="muted">{{ $item['category_label'] }}</span>
            @endif
            @if (($item['meta'] ?? '') !== '')
              <span class="focus-meta-text">· {{ $item['meta'] }}</span>
            @endif
          </div>
        </div>
        <a class="btn ghost btn-sm" href="{{ $item['detail_url'] }}">{{ $item['detail_label'] }}</a>
      </div>
    @empty
      <div class="focus-empty">
        <div class="focus-empty-icon">
          <svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
            <rect x="9" y="3" width="6" height="4" rx="1"/>
            <line x1="9" y1="12" x2="15" y2="12"/>
            <line x1="9" y1="16" x2="12" y2="16"/>
          </svg>
        </div>
        <div class="focus-empty-title">{{ $emptyTitle }}</div>
        <div class="focus-empty-desc muted">{{ $emptyDescription }}</div>
      </div>
    @endforelse
  </div>
</div>
@endsection
