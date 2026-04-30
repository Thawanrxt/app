$sidebar = @'
    <aside class="sidebar">
        <div class="brand">SRP ADMIN</div>
        <div class="profile">
            <div class="avatar">A</div>
            <div>
                <div>แอดมิน</div>
                <div class="sub" style="color: #dcfce7;">ระบบประเมิน SRP</div>
            </div>
        </div>
        <div class="divider"></div>
        <nav class="nav">
            <a class="nav-item" href="/admin"><span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M3 12a9 9 0 1 0 9-9v9z"></path><path d="M12 3a9 9 0 0 1 9 9h-9z"></path></svg></span>แดชบอร์ด</a>
            <a class="nav-item" href="/admin/users"><span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="12" cy="8" r="4"></circle><path d="M4 20c2.5-4 13.5-4 16 0"></path></svg></span>ผู้ใช้งาน</a>
            <a class="nav-item" href="/admin/notifications"><span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M15 17h5l-1.4-1.4a2 2 0 0 1-.6-1.4V11a6 6 0 1 0-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5"></path><path d="M10 21a2 2 0 0 0 4 0"></path></svg></span>แจ้งเตือน</a>
            <a class="nav-item has-children" href="#" data-toggle="tracking-sub"><span><span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M4 6h16v12H4z"></path><path d="M8 10h8M8 14h5"></path></svg></span>ข้อมูลการติดตาม</span><span class="caret"></span></a>
            <div class="nav-sub collapsed" data-sub="tracking-sub">
                <a href="/admin/tracking/prep">การเตรียมดิน</a>
                <a href="/admin/tracking/water">การจัดการน้ำ</a>
                <a href="/admin/tracking/fertilizer">หว่านปุ๋ย</a>
                <a href="/admin/tracking/pest">การจัดการศัตรูพืช</a>
                <a href="/admin/tracking/disease">การจัดการโรคพืช</a>
                <a href="/admin/tracking/harvest">การเก็บเกี่ยว</a>
                <a href="/admin/tracking/mill">ขายข้าวเข้าโรงสี</a>
            </div>
            <a class="nav-item" href="/admin/srp"><span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M4 5h13a3 3 0 0 1 3 3v11H7a3 3 0 0 0-3 3z"></path><path d="M7 5v14"></path></svg></span>คู่มือมาตรฐาน SRP</a>
            <a class="nav-item" href="/admin/rice"><span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M12 4v16"></path><path d="M8 8c2 2 4 2 6 0"></path><path d="M8 12c2 2 4 2 6 0"></path><path d="M8 16c2 2 4 2 6 0"></path></svg></span>พันธุ์ข้าว</a>
            <a class="nav-item has-children" href="#" data-toggle="report-sub"><span><span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2"><path d="M4 6h16v12H4z"></path><path d="M8 10l3 3 5-5"></path></svg></span>รายงานปัญหา</span><span class="caret"></span></a>
            <div class="nav-sub collapsed" data-sub="report-sub">
                <a href="/admin/report/rice">การปลูกข้าว</a>
                <a href="/admin/report/system">การใช้งานระบบ</a>
                <a href="/admin/report/rice-risk">แปลงเสี่ยง/ไม่ผ่านมาตรฐาน</a>
            </div>
            <a class="nav-item" href="/admin/settings"><span class="icon"><svg viewBox="0 0 24 24" fill="none" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19 12a7 7 0 0 0-.1-1l2.1-1.6-2-3.4-2.4 1a7 7 0 0 0-1.7-1l-.3-2.6h-4l-.3 2.6a7 7 0 0 0-1.7 1l-2.4-1-2 3.4L5.1 11a7 7 0 0 0 0 2l-2.1 1.6 2 3.4 2.4-1a7 7 0 0 0 1.7 1l.3 2.6h4l.3-2.6a7 7 0 0 0 1.7-1l2.4 1 2-3.4-2.1-1.6c.1-.3.1-.7.1-1z"></path></svg></span>ตั้งค่า</a>
        </nav>
        <div class="sidebar-card">โฟกัสรายการที่ต้องติดตามวันนี้ให้เสร็จ และเตรียมรีพอร์ตสำหรับผู้บริหาร</div>
    </aside>
'@

$toggle = @'
<script>
    document.querySelectorAll('[data-toggle]').forEach(function (toggle) {
        toggle.addEventListener('click', function (event) {
            event.preventDefault();
            var target = toggle.getAttribute('data-toggle');
            var sub = document.querySelector('[data-sub="' + target + '"]');
            if (sub) { sub.classList.toggle('collapsed'); }
        });
    });
</script>
'@

$template = @'
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>__TITLE__</title>
    <link rel="stylesheet" href="/css/admin.css">
</head>
<body>
<div class="layout">
__SIDEBAR__
    <main class="content">
__BODY__
    </main>
</div>
__TOGGLE__
</body>
</html>
'@

function WritePage($path, $title, $body) {
    $html = $template.Replace('__TITLE__', $title).Replace('__SIDEBAR__', $sidebar).Replace('__BODY__', $body).Replace('__TOGGLE__', $toggle)
    [IO.File]::WriteAllText($path, $html, [Text.Encoding]::UTF8)
}

WritePage 'resources/views/admin/dashboard.blade.php' 'แดชบอร์ดแอดมิน' @'
        <h1>แดชบอร์ด</h1>
        <section class="card">เนื้อหาแดชบอร์ดกำลังจัดเตรียม</section>
'@

WritePage 'resources/views/admin/settings.blade.php' 'การตั้งค่าแอดมิน' @'
        <h1>การตั้งค่าแอดมิน</h1>
        <section class="card">กำลังปรับปรุงหน้า ตั้งค่า</section>
'@

WritePage 'resources/views/admin/srp.blade.php' 'คู่มือมาตรฐาน SRP' @'
        <div class="header-bar srp-header">
            <div class="title-pill">มาตรฐาน SRP</div>
            <span class="header-spacer"></span>
            <a class="link-btn" href="https://e-book.acfs.go.th/Book_view/346" target="_blank" rel="noopener noreferrer">
                <span>↗</span>
                อ่านรายละเอียดมาตรฐาน SRP
            </a>
        </div>
        <section class="card">คู่มือมาตรฐาน SRP (เนื้อหาอยู่ระหว่างปรับปรุง)</section>
'@

$pages = @{
    'resources/views/admin/report-rice-detail.blade.php' = 'รายละเอียดรายงานการปลูกข้าว';
    'resources/views/admin/report-rice-risk.blade.php' = 'แปลงเสี่ยง/ไม่ผ่านมาตรฐาน';
    'resources/views/admin/report-rice.blade.php' = 'รายงานการปลูกข้าว';
    'resources/views/admin/report-system-detail.blade.php' = 'รายละเอียดการใช้งานระบบ';
    'resources/views/admin/report-system.blade.php' = 'รายงานการใช้งานระบบ';
    'resources/views/admin/tracking-disease.blade.php' = 'การจัดการโรคพืช';
    'resources/views/admin/tracking-fertilizer.blade.php' = 'หว่านปุ๋ย';
    'resources/views/admin/tracking-harvest.blade.php' = 'การเก็บเกี่ยว';
    'resources/views/admin/tracking-mill.blade.php' = 'ขายข้าวเข้าโรงสี';
    'resources/views/admin/tracking-pest.blade.php' = 'การจัดการศัตรูพืช';
    'resources/views/admin/tracking-prep-detail.blade.php' = 'รายละเอียดการเตรียมดิน';
    'resources/views/admin/tracking-prep.blade.php' = 'การเตรียมดิน';
    'resources/views/admin/tracking-water.blade.php' = 'การจัดการน้ำ';
    'resources/views/admin/users-account.blade.php' = 'สร้างบัญชีผู้ใช้';
    'resources/views/admin/users-create.blade.php' = 'เพิ่มผู้ใช้งาน';
    'resources/views/admin/users.blade.php' = 'ผู้ใช้งาน';
    'resources/views/admin/rice-create.blade.php' = 'เพิ่มพันธุ์ข้าว';
    'resources/views/admin/rice.blade.php' = 'พันธุ์ข้าว';
    'resources/views/admin/notifications.blade.php' = 'แจ้งเตือนจากผู้ใช้';
}

foreach ($k in $pages.Keys) {
    WritePage $k $pages[$k] @"
        <h1>$($pages[$k])</h1>
        <section class=\"card\">กำลังปรับปรุงข้อมูลหน้านี้</section>
"@
}
