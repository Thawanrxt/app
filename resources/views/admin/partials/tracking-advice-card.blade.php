@php
  $adviceStatus = $advice && $advice->sent_at ? 'ส่งแล้ว' : 'ยังไม่ส่ง';
@endphp

<div class="card" style="margin-top:12px;">
  <div class="card-head">
    <h3>คำแนะนำ</h3>
    <span class="chip {{ $advice && $advice->sent_at ? 'is-active' : '' }}">{{ $adviceStatus }}</span>
  </div>

  @if (session('success'))
    <div class="status-banner success" style="margin-top: 10px;">{{ session('success') }}</div>
  @endif

  @if ($errors->any())
    <div class="status-banner error" style="margin-top: 10px;">
      {{ $errors->first() }}
    </div>
  @endif

  <form method="POST" action="/admin/tracking-advice/{{ $pageKey }}" enctype="multipart/form-data" style="margin-top: 12px;">
    @csrf
    <input type="hidden" name="page_title" value="{{ $pageTitle }}">
    <input type="hidden" name="farmer_name" value="{{ $activity->farmer_name ?? '' }}">
    <input type="hidden" name="plot_code" value="{{ $activity->plot_code ?? '' }}">
    <input type="hidden" name="round_number" value="{{ $activity->round_number ?? '' }}">
    <input type="hidden" name="detail_url" value="{{ request()->url() }}">
    <input type="hidden" name="activity_id" value="{{ $activity->id ?? '' }}">

    <textarea class="input" name="message" rows="4" placeholder="ใส่รายละเอียด">{{ old('message', $advice->message ?? '') }}</textarea>
    <div class="upload-actions">
      <label class="btn ghost">
        <input type="file" name="attachment" style="display:none;">
        เลือกไฟล์
      </label>
      <button class="btn primary" type="submit">ส่งคำแนะนำถึงเกษตรกร</button>
    </div>
  </form>
</div>
