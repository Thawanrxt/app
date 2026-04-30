@extends('admin.layout')
@section('title', 'ทะเบียนบทบาท')
@section('content')
<div class="page-head">
  <div>
    <h1>ทะเบียนบทบาท</h1>
    <p class="muted">กำหนดบทบาทและสิทธิ์ว่าแต่ละบทบาทมองเห็นเมนูและทำอะไรในระบบได้บ้าง</p>
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

@if (! $rolesAvailable)
  <div class="card" style="margin-top: 16px; border-color: #fca5a5; background: #fff1f2;">
    <strong>ยังไม่พบตารางทะเบียนบทบาท</strong>
    <div class="muted" style="margin-top: 8px;">กรุณารัน migration ของตาราง <code>roles</code>, <code>role_menu_permissions</code> และ <code>role_action_permissions</code> ก่อนใช้งานหน้านี้</div>
  </div>
@endif

<div class="card" style="margin-top: 16px;">
  <div class="card-head">
    <h3>รายการบทบาท</h3>
    <span class="tag">{{ $roles->count() }} รายการ</span>
  </div>

  <table class="table" style="margin-top: 12px;">
    <thead>
      <tr>
        <th>รหัสบทบาท</th>
        <th>ชื่อบทบาท</th>
        <th>คำอธิบาย</th>
        <th>สถานะ</th>
        <th>สิทธิ์เมนู</th>
        <th>สิทธิ์การกระทำ</th>
        <th>จัดการ</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($roles as $role)
        <tr>
          <td>{{ $role->code }}</td>
          <td>{{ $role->name_th ?: '-' }}</td>
          <td>{{ $role->description ?: '-' }}</td>
          <td>
            <span class="tag" style="{{ $role->is_active ? 'background:#ecfdf5;color:#166534;' : 'background:#fef2f2;color:#991b1b;' }}">
              {{ $role->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน' }}
            </span>
          </td>
          <td>{{ $role->visible_menu_count }}/{{ $role->total_menu_count }}</td>
          <td>{{ $role->allowed_action_count }}/{{ $role->total_action_count }}</td>
          <td>
            <div class="table-actions">
              <a class="icon-action-btn compact" href="/admin/roles/{{ $role->code }}/edit" aria-label="แก้ไขบทบาท">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M12 20h9" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                  <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
              </a>
              <form method="POST" action="/admin/roles/{{ $role->code }}/delete" data-confirm-delete="ยืนยันการลบบทบาท {{ $role->name_th ?: $role->code }} ?">
                @csrf
                <button class="icon-action-btn danger compact" type="submit" aria-label="ลบบทบาท">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/>
                  </svg>
                </button>
              </form>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="muted" style="text-align:center; padding:24px;">ยังไม่มีข้อมูลบทบาทในระบบ</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div style="display:flex; justify-content:center; margin-top:20px;">
  <a class="btn primary" href="/admin/roles/create">เพิ่มบทบาท</a>
</div>
@endsection
