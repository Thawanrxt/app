@extends('admin.layout')
@section('title', 'แก้ไขบทบาท')
@section('content')
<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="/admin/roles" aria-label="กลับไปหน้าทะเบียนบทบาท">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>แก้ไขบทบาท</h1>
      <p class="muted">ปรับชื่อบทบาท สถานะ สิทธิ์เมนู และสิทธิ์การกระทำของบทบาท {{ $roleRecord->name_th ?: $roleRecord->code }}</p>
    </div>
  </div>
</div>

@if ($errors->any())
  <div class="card" style="margin-top: 16px; border-color: #fca5a5; background: #fff1f2;">
    <strong>อัปเดตบทบาทไม่สำเร็จ</strong>
    <ul style="margin: 8px 0 0 18px;">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

@include('admin.partials.role-form', [
  'formAction' => '/admin/roles/' . $roleRecord->code,
  'submitLabel' => 'อัปเดตบทบาท',
  'selectedPermissions' => old('permissions', collect($permissionMap)->filter()->keys()->all()),
  'selectedActionPermissions' => old('action_permissions', collect($actionPermissionMap)->filter()->keys()->all()),
])
@endsection
