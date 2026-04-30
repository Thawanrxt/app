<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? 'รายงานการติดตาม' }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      padding: 24px;
      font-family: 'Prompt', 'Sarabun', sans-serif;
      color: #111827;
      background: #fff;
    }
    .page {
      max-width: 1080px;
      margin: 0 auto;
    }
    .print-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 18px;
    }
    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .logo {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: #2f9e61;
      color: #fff;
      display: grid;
      place-items: center;
      font-weight: 700;
      letter-spacing: 0.5px;
    }
    .title h1 {
      margin: 0;
      font-size: 22px;
      font-weight: 700;
    }
    .title p {
      margin: 4px 0 0;
      color: #6b7280;
      font-size: 13px;
    }
    .print-actions button {
      border: 1px solid #e5e7eb;
      background: #111827;
      color: #fff;
      padding: 8px 16px;
      border-radius: 10px;
      font-weight: 600;
      cursor: pointer;
    }
    .summary {
      display: grid;
      grid-template-columns: repeat(3, minmax(0,1fr));
      gap: 12px;
      margin: 16px 0 20px;
    }
    .summary-card {
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      padding: 12px;
      background: #f9fafb;
    }
    .summary-card h3 {
      margin: 0 0 6px;
      font-size: 13px;
      color: #6b7280;
      font-weight: 600;
    }
    .summary-card p {
      margin: 0;
      font-size: 18px;
      font-weight: 700;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      overflow: hidden;
    }
    th, td {
      text-align: left;
      padding: 10px 12px;
      border-bottom: 1px solid #e5e7eb;
      font-size: 14px;
    }
    th {
      background: #f3f4f6;
      font-weight: 700;
    }
    tr:last-child td { border-bottom: none; }
    .status.pass { color: #16a34a; font-weight: 600; }
    .status.wait { color: #ca8a04; font-weight: 600; }
    .status.fix { color: #d97706; font-weight: 600; }
    .status.fail { color: #dc2626; font-weight: 600; }
    .foot {
      margin-top: 18px;
      color: #6b7280;
      font-size: 12px;
    }
    @media print {
      body { padding: 0; }
      .print-actions { display: none; }
      .page { max-width: none; }
    }
  </style>
</head>
<body>
  @php
    $rows = $rows ?? [];
    $countAll = count($rows);
    $countPass = count(array_filter($rows, fn($r) => ($r['status'] ?? '') === 'เสร็จสิ้นแล้ว'));
    $countWait = count(array_filter($rows, fn($r) => ($r['status'] ?? '') === 'รอตรวจสอบ'));
  @endphp
  <div class="page">
    <div class="print-head">
      <div class="brand">
        <div class="logo">SRP</div>
        <div class="title">
          <h1>{{ $title ?? 'รายงานการติดตาม' }}</h1>
          <p>ออกรายงานเมื่อ: {{ date('d/m/Y H:i') }}</p>
        </div>
      </div>
      <div class="print-actions">
        <button type="button" onclick="window.print()">พิมพ์</button>
      </div>
    </div>

    <div class="summary">
      <div class="summary-card">
        <h3>จำนวนรายการทั้งหมด</h3>
        <p>{{ $countAll }} รายการ</p>
      </div>
      <div class="summary-card">
        <h3>เสร็จสิ้นแล้ว</h3>
        <p>{{ $countPass }} รายการ</p>
      </div>
      <div class="summary-card">
        <h3>รอตรวจสอบ</h3>
        <p>{{ $countWait }} รายการ</p>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>เกษตรกร</th>
          <th>ชื่อแปลง</th>
          <th>รอบที่</th>
          <th>กิจกรรม</th>
          <th>วันที่</th>
          <th>สถานะ</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($rows as $row)
          @php
            $status = $row['status'] ?? '';
            $statusClass = $status === 'เสร็จสิ้นแล้ว' ? 'pass' : ($status === 'รอตรวจสอบ' ? 'wait' : ($status === 'ต้องแก้ไข' ? 'fix' : ($status === 'ไม่ผ่าน' ? 'fail' : '')));
          @endphp
          <tr>
            <td>{{ $row['farmer'] ?? '-' }}</td>
            <td>{{ $row['plot'] ?? '-' }}</td>
            <td>{{ $row['round'] ?? '-' }}</td>
            <td>{{ $row['activity'] ?? '-' }}</td>
            <td>{{ $row['date'] ?? '-' }}</td>
            <td class="status {{ $statusClass }}">{{ $status }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>

    <div class="foot">หมายเหตุ: รายงานนี้ดึงข้อมูลจากระบบ SRP Admin</div>
  </div>
</body>
</html>
