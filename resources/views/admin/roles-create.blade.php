@extends('admin.layout')
@section('title', 'เพิ่มบทบาท')
@section('content')
<div class="page-head">
  <div class="page-title">
    <a class="back-link icon-only" href="/admin/roles" aria-label="กลับไปหน้าทะเบียนบทบาท">
      <span class="back-icon">‹</span>
    </a>
    <div>
      <h1>เพิ่มบทบาท</h1>
      <p class="muted">สร้างบทบาทใหม่และกำหนดสิทธิ์ว่าแอดมินบทบาทนี้มองเห็นและทำอะไรได้บ้าง</p>
    </div>
  </div>
</div>

@if ($errors->any())
  <div class="card" style="margin-top: 16px; border-color: #fca5a5; background: #fff1f2;">
    <strong>บันทึกบทบาทไม่สำเร็จ</strong>
    <ul style="margin: 8px 0 0 18px;">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

@include('admin.partials.role-form', [
  'formAction' => '/admin/roles',
  'submitLabel' => 'บันทึกบทบาท',
  'selectedPermissions' => old('permissions', ['dashboard', 'farmer_users']),
  'selectedActionPermissions' => old('action_permissions', ['dashboard.view', 'farmer_users.view']),
])
@endsection
