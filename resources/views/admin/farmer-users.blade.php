@extends('admin.layout')
@section('title', 'ผู้ใช้งาน')
@section('content')
<div class="page-head">
  <div>
    <h1>ผู้ใช้งาน</h1>
    <p class="muted">รายชื่อเกษตรกรและบัญชีผู้ใช้งาน</p>
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
  <form class="search-row" action="/admin/farmer-users" method="get">
    <div class="search-field">
      <input class="search-input" type="text" name="q" placeholder="ค้นหาชื่อ รหัสทะเบียนเกษตรกร เบอร์โทร หรือชื่อผู้ใช้" value="{{ $query }}">
    </div>
    <button class="search-btn search-btn--standalone" type="submit" aria-label="ค้นหา">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="11" cy="11" r="7"></circle>
        <path d="M20 20l-3.5-3.5"></path>
      </svg>
    </button>
    <a class="btn primary" href="/admin/farmer-users/create">เพิ่มผู้ใช้งาน</a>
  </form>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>รายการผู้ใช้งาน</h3>
  <table class="table">
    <thead>
      <tr>
        <th>ชื่อผู้ใช้</th>
        <th>ชื่อเกษตรกร</th>
        <th>รหัสทะเบียนเกษตรกร</th>
        <th>เบอร์โทร</th>
        <th>บทบาท</th>
        <th>ดำเนินการ</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($users as $user)
        <tr>
          <td>{{ $user->username ?: '-' }}</td>
          <td>{{ $user->full_name ?: '-' }}</td>
          <td>{{ $user->farmer_code ?: '-' }}</td>
          <td>{{ $user->phone ?: '-' }}</td>
          <td>{{ $user->role ?: '-' }}</td>
          <td>
            <div class="table-actions">
              <a class="icon-action-btn compact" href="/admin/farmer-users/{{ $user->id }}" aria-label="ดูข้อมูลผู้ใช้งาน">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <circle cx="11" cy="11" r="7"></circle>
                  <path d="M20 20l-3.5-3.5"></path>
                </svg>
              </a>
              <a class="icon-action-btn compact" href="/admin/farmer-users/{{ $user->id }}/edit" aria-label="แก้ไขข้อมูลผู้ใช้งาน">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 20h9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
              <form method="POST" action="/admin/farmer-users/{{ $user->id }}/delete" onsubmit="return confirm('ยืนยันการลบผู้ใช้งานรายนี้?')">
                @csrf
                <button class="icon-action-btn danger compact" type="submit" aria-label="ลบผู้ใช้งาน">
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
          <td colspan="6" class="muted" style="text-align:center; padding:24px;">ยังไม่มีผู้ใช้งานในฐานข้อมูล</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
