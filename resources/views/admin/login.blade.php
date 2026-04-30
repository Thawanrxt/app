<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>เข้าสู่ระบบแอดมิน</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="{{ asset('css/admin.css') }}?v=20260421-login2">
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
          <h1>เข้าสู่ระบบ</h1>
        </div>

        @if (session('success'))
          <div class="status-banner success" style="margin-top: 16px;">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
          <div class="status-banner error" style="margin-top: 16px;">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ url('/admin/login') }}" class="admin-auth__form admin-auth__form--hero">
          @csrf


          <div class="admin-auth__app-name">SmartApp</div>

          <label class="admin-auth__field">
            <span class="admin-auth__field-icon" aria-hidden="true">👤</span>
            <input class="input" type="text" name="username" value="{{ old('username') }}" placeholder="Username" autocomplete="username" required>
          </label>

          <label class="admin-auth__field">
            <span class="admin-auth__field-icon" aria-hidden="true">🔒</span>
            <input class="input" type="password" name="password" placeholder="Password" autocomplete="current-password" required>
          </label>

          <button class="btn primary admin-auth__submit admin-auth__submit--hero" type="submit">Login</button>

        </form>
      </div>
    </div>
  </div>
</body>
</html>
