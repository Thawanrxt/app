<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>ตั้งรหัสผ่านใหม่</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=20260501-reset-password">
</head>
<body>
  @php($loginShowcaseImage = asset('images/admin/login-side.jpg'))
  <div class="admin-auth admin-auth--hero">
    <div class="admin-auth__scene">
      <div class="admin-auth__showcase">
        <div class="admin-auth__gallery admin-auth__gallery--single" style="background-image: url('{{ $loginShowcaseImage }}');"></div>
      </div>

      <div class="admin-auth__panel admin-auth__panel--hero">
        <div class="admin-auth__brand">
          <h1>ตั้งรหัสผ่านใหม่</h1>
          <p class="muted">ลิงก์นี้ใช้งานได้ 60 นาที กรุณาตั้งรหัสผ่านใหม่ให้ปลอดภัย</p>
        </div>

        @if ($errors->any())
          <div class="status-banner error" style="margin-top: 16px;">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.password.update') }}" class="admin-auth__form admin-auth__form--hero">
          @csrf

          <input type="hidden" name="token" value="{{ $token }}">

          <div class="admin-auth__app-name">SRP Admin</div>

          <label class="admin-auth__field">
            <span class="admin-auth__field-icon" aria-hidden="true">&#128100;</span>
            <input class="input" type="text" name="email" value="{{ old('email', $username ?? '') }}" placeholder="ชื่อผู้ใช้ (Username)" autocomplete="username" required>
          </label>

          <label class="admin-auth__field">
            <span class="admin-auth__field-icon" aria-hidden="true">🔒</span>
            <input class="input" type="password" name="password" placeholder="รหัสผ่านใหม่" autocomplete="new-password" required>
          </label>

          <label class="admin-auth__field">
            <span class="admin-auth__field-icon" aria-hidden="true">🔒</span>
            <input class="input" type="password" name="password_confirmation" placeholder="ยืนยันรหัสผ่านใหม่" autocomplete="new-password" required>
          </label>

          <button class="btn primary admin-auth__submit admin-auth__submit--hero" type="submit">บันทึกรหัสผ่านใหม่</button>

          <a href="{{ url('/admin/login') }}" class="btn ghost" style="text-align:center;">กลับไปหน้าเข้าสู่ระบบ</a>
        </form>
      </div>
    </div>
  </div>
</body>
</html>
