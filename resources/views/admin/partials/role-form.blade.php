@php
  $isEdit = isset($roleRecord);
  $selectedPermissions = old('permissions', $selectedPermissions ?? []);
  $selectedActionPermissions = old('action_permissions', $selectedActionPermissions ?? []);
@endphp

@if (! $rolesAvailable)
  <div class="card" style="margin-top: 16px; border-color: #fca5a5; background: #fff1f2;">
    <strong>ยังไม่พบตารางทะเบียนบทบาท</strong>
    <div class="muted" style="margin-top: 8px;">กรุณาสร้างตาราง <code>roles</code>, <code>role_menu_permissions</code> และ <code>role_action_permissions</code> ในฐานข้อมูลก่อนใช้งานหน้านี้</div>
  </div>
@else
  <form method="POST" action="{{ $formAction }}">
    @csrf
    @if ($isEdit)
      @method('PUT')
    @endif

    <div class="card" style="margin-top: 16px;">
      <h3>ข้อมูลบทบาท</h3>
      <div class="form-grid" style="margin-top: 12px;">
        <label>รหัสบทบาท <span style="color:#dc2626">*</span>
          <input class="input" name="code" type="text" maxlength="50" value="{{ old('code', $roleRecord->code ?? '') }}" placeholder="เช่น MANAGER หรือ STAFF" {{ $isEdit ? 'readonly' : '' }}>
        </label>
        <label>ชื่อบทบาท <span style="color:#dc2626">*</span>
          <input class="input" name="name_th" type="text" maxlength="255" value="{{ old('name_th', $roleRecord->name_th ?? '') }}" placeholder="เช่น ผู้จัดการระบบ">
        </label>
        <label style="grid-column: 1 / -1;">คำอธิบาย
          <textarea class="input" name="description" rows="3" placeholder="อธิบายการใช้งานของบทบาทนี้">{{ old('description', $roleRecord->description ?? '') }}</textarea>
        </label>
        <label>ลำดับการแสดงผล
          <input class="input" name="sort_order" type="number" min="0" value="{{ old('sort_order', $roleRecord->sort_order ?? 0) }}">
        </label>
        <label style="display:flex; align-items:flex-end;">
          <span style="display:flex; align-items:center; gap:10px; margin-top: 28px;">
            <input type="checkbox" name="is_active" value="1" {{ old('is_active', $roleRecord->is_active ?? true) ? 'checked' : '' }}>
            เปิดใช้งานบทบาทนี้
          </span>
        </label>
      </div>
    </div>

    <div class="card" style="margin-top: 16px;">
      <div class="card-head">
        <h3>สิทธิ์การมองเห็นเมนู</h3>
        <span class="tag">{{ collect($selectedPermissions)->filter()->count() }} เมนู</span>
      </div>

      @if (! $permissionsAvailable)
        <div class="muted" style="margin-top: 12px;">ยังไม่พบตาราง <code>role_menu_permissions</code> ในฐานข้อมูล จึงยังไม่สามารถตั้งค่าสิทธิ์เมนูได้</div>
      @else
        <div style="display:grid; gap:16px; margin-top: 16px;">
          @foreach ($menuGroups as $group)
            <section style="border:1px solid rgba(15, 23, 42, 0.08); border-radius:18px; padding:18px 20px; background:#f8fbff;">
              <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:12px;">
                <div>
                  <h4 style="margin:0; font-size:16px;">{{ $group['label'] }}</h4>
                  <p class="muted" style="margin:6px 0 0;">เลือกเมนูที่บทบาทนี้สามารถมองเห็นได้</p>
                </div>
                <span class="tag">{{ count($group['items']) }} เมนู</span>
              </div>
              <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
                @foreach ($group['items'] as $item)
                  <label style="display:flex; align-items:flex-start; gap:10px; padding:12px 14px; border:1px solid rgba(15, 23, 42, 0.08); border-radius:14px; background:#fff;">
                    <input type="checkbox" name="permissions[]" value="{{ $item['menu_key'] }}" {{ in_array($item['menu_key'], $selectedPermissions, true) ? 'checked' : '' }} style="margin-top:3px;">
                    <span>
                                            <strong style="display:block;">{{ $item['menu_label'] }}</strong>
                    </span>
                  </label>
                @endforeach
              </div>
            </section>
          @endforeach
        </div>
      @endif
    </div>

    <div class="card" style="margin-top: 16px;">
      <div class="card-head">
        <h3>สิทธิ์การกระทำ</h3>
        <span class="tag">{{ collect($selectedActionPermissions)->filter()->count() }} สิทธิ์</span>
      </div>

      @if (! $actionPermissionsAvailable)
        <div class="muted" style="margin-top: 12px;">ยังไม่พบตาราง <code>role_action_permissions</code> ในฐานข้อมูล จึงยังไม่สามารถตั้งค่าสิทธิ์การกระทำได้</div>
      @else
        <div style="display:grid; gap:16px; margin-top:16px;">
          @foreach ($actionGroups as $group)
            <section style="border:1px solid rgba(15, 23, 42, 0.08); border-radius:18px; padding:18px 20px; background:#fffdf8;">
              <div style="display:flex; align-items:flex-start; justify-content:space-between; gap:12px; margin-bottom:14px;">
                <div>
                  <h4 style="margin:0; font-size:16px;">{{ $group['label'] }}</h4>
                  <p class="muted" style="margin:6px 0 0;">SUPERADMIN สามารถกำหนดได้ว่าแอดมินบทบาทนี้ทำอะไรได้บ้าง</p>
                </div>
                <span class="tag">{{ collect($group['resources'])->sum(fn ($resource) => count($resource['items'])) }} สิทธิ์</span>
              </div>

              <div style="display:grid; gap:14px;">
                @foreach ($group['resources'] as $resource)
                  <div style="border:1px solid rgba(15, 23, 42, 0.08); border-radius:14px; padding:14px 16px; background:#fff;">
                    <div style="font-weight:700; margin-bottom:10px;">{{ $resource['resource_label'] }}</div>
                    <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap:10px;">
                      @foreach ($resource['items'] as $item)
                        @php($permissionKey = $item['resource_key'] . '.' . $item['action_key'])
                        <label style="display:flex; align-items:flex-start; gap:10px; padding:10px 12px; border:1px solid rgba(15, 23, 42, 0.08); border-radius:12px; background:#fcfcfd;">
                          <input type="checkbox" name="action_permissions[]" value="{{ $permissionKey }}" {{ in_array($permissionKey, $selectedActionPermissions, true) ? 'checked' : '' }} style="margin-top:3px;">
                          <span>
                                            <strong style="display:block;">{{ $item['action_label'] }}</strong>
                          </span>
                        </label>
                      @endforeach
                    </div>
                  </div>
                @endforeach
              </div>
            </section>
          @endforeach
        </div>
      @endif
    </div>

    <div class="footer-actions" style="margin-top: 20px;">
      <button class="btn primary" type="submit">{{ $submitLabel }}</button>
      <a href="/admin/roles" class="btn ghost">ยกเลิก</a>
    </div>
  </form>
@endif
