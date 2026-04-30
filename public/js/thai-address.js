(function () {
  var provinceEl = document.getElementById('province-select');
  var districtEl = document.getElementById('district-select');
  var subdistrictEl = document.getElementById('subdistrict-select');
  var registerProvinceEl = document.getElementById('register-province-select');
  var farmProvinceEl = document.getElementById('farm-province-select');

  if (!provinceEl || !districtEl || !subdistrictEl) return;

  var DATA_URL = 'https://raw.githubusercontent.com/thailand-geography-data/thailand-geography-json/main/src/geography.json';
  var cacheKey = 'thai-geo-cache-v2';

  function setOptions(select, items, placeholder, selectedValue) {
    if (!select) return;

    select.innerHTML = '';

    var ph = document.createElement('option');
    ph.value = '';
    ph.textContent = placeholder;
    select.appendChild(ph);

    items.forEach(function (item) {
      var opt = document.createElement('option');
      opt.value = item.value;
      opt.textContent = item.label;

      if (item.code) {
        opt.dataset.code = item.code;
      }

      select.appendChild(opt);
    });

    select.disabled = items.length === 0;

    if (selectedValue) {
      select.value = selectedValue;
      if (select.value !== selectedValue) {
        var matched = Array.from(select.options).find(function (option) {
          return option.textContent === selectedValue;
        });
        if (matched) select.value = matched.value;
      }
    }
  }

  function uniqueBy(arr, keyFn) {
    var map = new Map();
    arr.forEach(function (item) {
      var key = keyFn(item);
      if (!map.has(key)) map.set(key, item);
    });
    return Array.from(map.values());
  }

  function sortThai(a, b) {
    return a.label.localeCompare(b.label, 'th');
  }

  function getSelectedCode(select) {
    if (!select) return '';
    var option = select.options[select.selectedIndex];
    return option && option.dataset.code ? option.dataset.code : '';
  }

  function loadData() {
    var cached = localStorage.getItem(cacheKey);
    if (cached) {
      try {
        return Promise.resolve(JSON.parse(cached));
      } catch (e) {
        localStorage.removeItem(cacheKey);
      }
    }

    return fetch(DATA_URL)
      .then(function (r) { return r.json(); })
      .then(function (data) {
        localStorage.setItem(cacheKey, JSON.stringify(data));
        return data;
      });
  }

  function buildProvinceOptions(rows) {
    return uniqueBy(rows.map(function (r) {
      return {
        value: r.provinceNameTh,
        label: r.provinceNameTh,
        code: String(r.provinceCode)
      };
    }), function (r) { return r.code; }).sort(sortThai);
  }

  function buildDistrictOptions(rows, provinceCode) {
    return uniqueBy(rows.filter(function (r) {
      return String(r.provinceCode) === provinceCode;
    }).map(function (r) {
      return {
        value: r.districtNameTh,
        label: r.districtNameTh,
        code: String(r.districtCode)
      };
    }), function (r) { return r.code; }).sort(sortThai);
  }

  function buildSubdistrictOptions(rows, provinceCode, districtCode) {
    return uniqueBy(rows.filter(function (r) {
      return String(r.provinceCode) === provinceCode && String(r.districtCode) === districtCode;
    }).map(function (r) {
      return {
        value: r.subdistrictNameTh,
        label: r.subdistrictNameTh,
        code: String(r.subdistrictCode)
      };
    }), function (r) { return r.code; }).sort(sortThai);
  }

  loadData().then(function (rows) {
    var provinces = buildProvinceOptions(rows);
    var provinceSelected = provinceEl.dataset.selected || '';
    var districtSelected = districtEl.dataset.selected || '';
    var subdistrictSelected = subdistrictEl.dataset.selected || '';

    setOptions(provinceEl, provinces, 'เลือกจังหวัด', provinceSelected);
    setOptions(registerProvinceEl, provinces, 'เลือกจังหวัดที่ขึ้นทะเบียน', registerProvinceEl ? registerProvinceEl.dataset.selected : '');
    setOptions(farmProvinceEl, provinces, 'เลือกจังหวัด', farmProvinceEl ? farmProvinceEl.dataset.selected : '');

    function refreshDistricts(selectedDistrict) {
      var provinceCode = getSelectedCode(provinceEl);
      setOptions(districtEl, [], 'เลือกอำเภอ/เขต', '');
      setOptions(subdistrictEl, [], 'เลือกตำบล/แขวง', '');
      if (!provinceCode) return;

      var districts = buildDistrictOptions(rows, provinceCode);
      setOptions(districtEl, districts, 'เลือกอำเภอ/เขต', selectedDistrict || '');
    }

    function refreshSubdistricts(selectedSubdistrict) {
      var provinceCode = getSelectedCode(provinceEl);
      var districtCode = getSelectedCode(districtEl);
      setOptions(subdistrictEl, [], 'เลือกตำบล/แขวง', '');
      if (!provinceCode || !districtCode) return;

      var subs = buildSubdistrictOptions(rows, provinceCode, districtCode);
      setOptions(subdistrictEl, subs, 'เลือกตำบล/แขวง', selectedSubdistrict || '');
    }

    if (provinceSelected) {
      refreshDistricts(districtSelected);
    }

    if (districtSelected) {
      refreshSubdistricts(subdistrictSelected);
    }

    provinceEl.addEventListener('change', function () {
      refreshDistricts('');
    });

    districtEl.addEventListener('change', function () {
      refreshSubdistricts('');
    });
  }).catch(function () {
    setOptions(provinceEl, [], 'โหลดข้อมูลไม่สำเร็จ', '');
    setOptions(districtEl, [], 'โหลดข้อมูลไม่สำเร็จ', '');
    setOptions(subdistrictEl, [], 'โหลดข้อมูลไม่สำเร็จ', '');
    setOptions(registerProvinceEl, [], 'โหลดข้อมูลไม่สำเร็จ', '');
    setOptions(farmProvinceEl, [], 'โหลดข้อมูลไม่สำเร็จ', '');
  });
})();
