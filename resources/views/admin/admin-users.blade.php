@extends('admin.layout')
@section('title', 'ผู้ดูแลระบบ')
@section('content')
<div class="page-head">
  <div>
    <h1>ผู้ดูแลระบบ</h1>
    <p class="muted">รายชื่อบัญชีแอดมินและซุปเปอร์แอดมินของระบบ</p>
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
  <form class="search-row" action="/admin/admin-users" method="get">
    <div class="search-field">
      <input class="search-input" type="text" name="q" placeholder="ค้นหาชื่อ ชื่อผู้ใช้ เบอร์โทร ตำแหน่ง หรือขอบเขตพื้นที่" value="{{ $query }}">
    </div>
    <button class="search-btn search-btn--standalone" type="submit" aria-label="ค้นหา">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="11" cy="11" r="7"></circle>
        <path d="M20 20l-3.5-3.5"></path>
      </svg>
    </button>
    <a class="btn primary" href="/admin/admin-users/create">เพิ่มผู้ดูแลระบบ</a>
  </form>
</div>

<div class="card" style="margin-top: 16px;">
  <h3>รายการผู้ดูแลระบบ</h3>
  <table class="table">
    <thead>
      <tr>
        <th>ชื่อผู้ใช้</th>
        <th>ชื่อ</th>
        <th>เบอร์โทร</th>
        <th>บทบาท</th>
        <th>ตำแหน่ง</th>
        <th>พื้นที่รับผิดชอบ</th>
        <th>ดำเนินการ</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($users as $user)
        <tr>
          <td>{{ $user->username ?: '-' }}</td>
          <td>{{ $user->display_name ?: '-' }}</td>
          <td>{{ $user->phone ?: '-' }}</td>
          <td>{{ $user->role ?: '-' }}</td>
          <td>{{ $user->admin_title ?: '-' }}</td>
          <td>{{ $user->scope_label ?: collect([$user->scope_province, $user->scope_district, $user->scope_subdistrict])->filter()->implode(' / ') ?: '-' }}</td>
          <td>
            <div class="table-actions">
              <a class="icon-action-btn compact" href="/admin/admin-users/{{ $user->id }}" aria-label="ดูข้อมูลผู้ดูแลระบบ">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                  <circle cx="11" cy="11" r="7"></circle>
                  <path d="M20 20l-3.5-3.5"></path>
                </svg>
              </a>
              <a class="icon-action-btn compact" href="/admin/admin-users/{{ $user->id }}/edit" aria-label="แก้ไขข้อมูลผู้ดูแลระบบ">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 20h9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
              <form method="POST" action="/admin/admin-users/{{ $user->id }}/delete" onsubmit="return confirm('ยืนยันการลบผู้ดูแลระบบรายนี้?')">
                @csrf
                <button class="icon-action-btn danger compact" type="submit" aria-label="ลบผู้ดูแลระบบ">
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
          <td colspan="7" class="muted" style="text-align:center; padding:24px;">ยังไม่มีผู้ดูแลระบบในฐานข้อมูล</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>
@endsection
