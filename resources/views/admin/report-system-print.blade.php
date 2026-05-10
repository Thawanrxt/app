<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? 'รายงานปัญหาการใช้งานระบบ' }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { box-sizing: border-box; }
    body { margin: 0; padding: 24px; font-family: 'Prompt', sans-serif; color: #111827; background: #fff; }
    .page { max-width: 1080px; margin: 0 auto; }
    .header { display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-bottom: 18px; }
    .brand { display: flex; align-items: center; gap: 12px; }
    .logo { width: 44px; height: 44px; border-radius: 12px; background: #2f9e61; color: #fff; display: grid; place-items: center; font-weight: 700; }
    .header h1 { margin: 0; font-size: 22px; }
    .header p { margin: 4px 0 0; color: #6b7280; font-size: 13px; }
    .print-btn { border: 1px solid #e5e7eb; background: #111827; color: #fff; padding: 8px 16px; border-radius: 10px; font-weight: 600; cursor: pointer; }
    .summary { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 18px; }
    .summary-card { border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px; background: #f9fafb; }
    .summary-card h3 { margin: 0 0 6px; font-size: 13px; color: #6b7280; }
    .summary-card p { margin: 0; font-size: 18px; font-weight: 700; }
    table { width: 100%; border-collapse: collapse; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; }
    th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid #e5e7eb; font-size: 14px; vertical-align: top; }
    th { background: #f3f4f6; font-weight: 700; }
    tr:last-child td { border-bottom: none; }
    .status-open { color: #ca8a04; font-weight: 600; }
    .status-progress { color: #d97706; font-weight: 600; }
    .status-closed { color: #16a34a; font-weight: 600; }
    .status-rejected { color: #dc2626; font-weight: 600; }
    .foot { margin-top: 18px; color: #6b7280; font-size: 12px; }
    @media print {
      body { padding: 0; }
      .header button { display: none; }
      .page { max-width: none; }
    }
  </style>
</head>
<body>
  @php
    $tickets = $tickets ?? collect();
    $countAll = $tickets->count();
    $countOpen = $tickets->whereIn('source_status', ['OPEN', 'PENDING', 'IN_PROGRESS'])->count();
    $countResolved = $tickets->whereIn('source_status', ['RESOLVED', 'CLOSED'])->count();
  @endphp
  <div class="page">
    <div class="header">
      <div class="brand">
        <div class="logo">SRP</div>
        <div>
          <h1>{{ $title ?? 'รายงานปัญหาการใช้งานระบบ' }}</h1>
          <p>ออกรายงานเมื่อ {{ now()->format('d/m/Y H:i') }}</p>
        </div>
      </div>
      <button class="print-btn" type="button" onclick="window.print()">พิมพ์</button>
    </div>

    <div class="summary">
      <div class="summary-card">
        <h3>จำนวนรายการทั้งหมด</h3>
        <p>{{ $countAll }} รายการ</p>
      </div>
      <div class="summary-card">
        <h3>เคสที่ยังเปิดอยู่</h3>
        <p>{{ $countOpen }} รายการ</p>
      </div>
      <div class="summary-card">
        <h3>เคสที่ปิดแล้ว</h3>
        <p>{{ $countResolved }} รายการ</p>
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>ชื่อเกษตรกร</th>
          <th>หัวข้อปัญหา</th>
          <th>รายละเอียด</th>
          <th>วันที่แจ้ง</th>
          <th>สถานะ</th>
        </tr>
      </thead>
      <tbody>
        @forelse ($tickets as $ticket)
          @php
            $statusClass = match ($ticket->source_status) {
              'OPEN', 'PENDING' => 'status-open',
              'IN_PROGRESS' => 'status-progress',
              'RESOLVED', 'CLOSED' => 'status-closed',
              'REJECTED' => 'status-rejected',
              default => '',
            };
          @endphp
          <tr>
            <td>{{ $ticket->reporter_name }}</td>
            <td>{{ $ticket->subject ?: '-' }}</td>
            <td>{{ $ticket->message ?: '-' }}</td>
            <td>{{ $ticket->formatted_date_short }}</td>
            <td class="{{ $statusClass }}">{{ $ticket->status }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="5">ยังไม่มีข้อมูลปัญหาการใช้งานระบบ</td>
          </tr>
        @endforelse
      </tbody>
    </table>

    <div class="foot">รายงานนี้ดึงข้อมูลจากตาราง support_tickets ของระบบ SRP Admin</div>
  </div>
</body>
</html>
