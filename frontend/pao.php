<?php
/**
 * Tokovoucher • Ref ID Status Checker (Single PHP File)
 * ----------------------------------------------------
 * - UI ให้กรอก Ref ID และกดเช็คสถานะได้ทันที
 * - ส่ง API ไปที่ https://api.tokovoucher.net/v1/transaksi/status ด้วย cURL
 * - แสดงผลลัพธ์แบบสวยงาม: badge สี, รายละเอียด, และ JSON แบบพวกซ์กดดู/คัดลอกได้
 * - เก็บ member_code / secret ไว้ฝั่งเซิร์ฟเวอร์เท่านั้น (ห้ามใส่ใน JS ฝั่ง client)
 *
 * วิธีใช้:
 * 1) บันทึกไฟล์นี้เป็น tokovoucher_status_checker.php ไว้ในโฟลเดอร์เว็บ PHP ของคุณ (เช่น XAMPP: htdocs)
 * 2) เปิดในเบราว์เซอร์: http://localhost/tokovoucher_status_checker.php
 * 3) กรอก Ref ID แล้วกด "เช็คสถานะ"
 */

// =====================[ CONFIG ]=====================
$MEMBER_CODE = "M241013PPRB9467IF"; // <- ของคุณ
$SECRET      = "d583eba663aebc4ca7dbca51ea17a43da25044e5c1c3561221c5b1bf027e6938"; // <- ของคุณ
$API_URL     = "https://api.tokovoucher.net/v1/transaksi/status";

// ================[ API HANDLER (AJAX) ]================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check') {
    header('Content-Type: application/json; charset=utf-8');

    // รับและ sanitize ref_id
    $ref_id = isset($_POST['ref_id']) ? trim($_POST['ref_id']) : '';
    if ($ref_id === '') {
        echo json_encode(['ok' => false, 'error' => 'กรุณากรอก Ref ID']);
        exit;
    }

    // สร้าง signature
    $stringToHash = $MEMBER_CODE . ":" . $SECRET . ":" . $ref_id;
    $signature = md5($stringToHash);

    // Payload
    $payload = [
        'ref_id'      => $ref_id,
        'member_code' => $MEMBER_CODE,
        'signature'   => $signature,
    ];

    // ส่ง cURL
    $ch = curl_init($API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => 30,
    ]);
    $responseRaw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErrNo = curl_errno($ch);
    $curlErr   = curl_error($ch);
    curl_close($ch);

    $responseJson = null;
    if ($responseRaw !== false) {
        $decoded = json_decode($responseRaw, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $responseJson = $decoded;
        }
    }

    echo json_encode([
        'ok' => $curlErrNo === 0,
        'payload' => $payload,
        'http_code' => $httpCode,
        'curl_errno' => $curlErrNo,
        'curl_error' => $curlErr,
        'response_raw' => $responseRaw,
        'response' => $responseJson,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// =====================[ UI PAGE ]=====================
?>
<!doctype html>
<html lang="th">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tokovoucher • เช็คสถานะ Ref ID</title>
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #0f172a; color: #e2e8f0; }
    .card { background: #111827; border: 1px solid #1f2937; }
    .form-control, .form-select { background: #0b1220; color: #e2e8f0; border-color: #1f2937; }
    .form-control:focus { background: #0b1220; color: #fff; border-color: #2563eb; box-shadow: 0 0 0 .2rem rgba(37,99,235,.25); }
    .btn-primary { background: #2563eb; border-color: #2563eb; }
    .btn-outline-light { border-color: #334155; color: #e2e8f0; }
    pre.json { background: #0b1220; color: #e2e8f0; padding: 1rem; border-radius: .5rem; max-height: 360px; overflow: auto; }
    .badge-status { font-size: .95rem; }
    .loader { width: 1.25rem; height: 1.25rem; border: 3px solid #cbd5e1; border-top-color: transparent; border-radius: 50%; animation: spin .8s linear infinite; display: inline-block; }
    @keyframes spin { to { transform: rotate(360deg); } }
    .copy-btn { cursor: pointer; }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="text-center mb-4">
          <h1 class="fw-bold">Tokovoucher • ເຊັກສະຖານະ Ref ID</h1>
          <p class="text-secondary mb-0">กรอก Ref ID ที่คุณยิงไป แล้วกดเช็คสถานะ ระบบจะเรียก API และแสดงผลอย่างละเอียด</p>
        </div>

        <div class="card shadow-sm">
          <div class="card-body">
            <form id="statusForm" class="row g-3" onsubmit="return false;">
              <div class="col-12">
                <label for="refId" class="form-label">Ref ID</label>
                <input type="text" class="form-control" id="refId" placeholder="เช่น TT1759041051656" required>
              </div>
              <div class="col-12 d-flex gap-2">
                <button id="btnCheck" class="btn btn-primary" type="button">
                  <span class="me-2" id="btnSpinner" style="display:none"><span class="loader"></span></span>
                  เช็คสถานะ
                </button>
                <button class="btn btn-outline-light" type="button" id="btnClear">ล้างค่า</button>
              </div>
            </form>
          </div>
        </div>

        <div id="resultWrap" class="mt-4" style="display:none;">
          <div class="card shadow-sm mb-3">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                <div>
                  <h5 class="card-title mb-1">ผลการตรวจสอบ</h5>
                  <div class="small text-secondary">Ref ID: <span id="resRefId" class="text-info fw-semibold"></span></div>
                </div>
                <div id="statusBadge"></div>
              </div>

              <hr class="border-secondary">

              <div class="row g-3">
                <div class="col-md-6">
                  <div class="mb-2 small text-secondary">เวลา (HTTP): <span id="resHttp"></span></div>
                  <div class="mb-2 small text-secondary">cURL: <span id="resCurl"></span></div>
                  <div class="mb-2 small text-secondary">Signature (md5): <code id="resSign"></code></div>
                </div>
                <div class="col-md-6 text-md-end">
                  <button class="btn btn-sm btn-outline-light copy-btn" data-copy="payload">คัดลอก Payload</button>
                  <button class="btn btn-sm btn-outline-light copy-btn" data-copy="json">คัดลอก JSON</button>
                </div>
              </div>
            </div>
          </div>

          <div class="accordion" id="accDetail">
            <div class="accordion-item" style="background:#111827; color:#e2e8f0; border:1px solid #1f2937;">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePayload" aria-expanded="false" aria-controls="collapsePayload" style="background:#0b1220; color:#e2e8f0;">
                  Request Payload
                </button>
              </h2>
              <div id="collapsePayload" class="accordion-collapse collapse" data-bs-parent="#accDetail">
                <div class="accordion-body">
                  <pre class="json" id="payloadPre"></pre>
                </div>
              </div>
            </div>

            <div class="accordion-item" style="background:#111827; color:#e2e8f0; border:1px solid #1f2937;">
              <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseJson" aria-expanded="false" aria-controls="collapseJson" style="background:#0b1220; color:#e2e8f0;">
                  API Response (JSON)
                </button>
              </h2>
              <div id="collapseJson" class="accordion-collapse collapse" data-bs-parent="#accDetail">
                <div class="accordion-body">
                  <pre class="json" id="jsonPre"></pre>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const el = (id) => document.getElementById(id);

    function prettify(obj) {
      try { return JSON.stringify(obj, null, 2); } catch { return String(obj); }
    }

    function badgeForStatus(status) {
      if (!status) return '<span class="badge bg-secondary badge-status">unknown</span>';
      const s = String(status).toLowerCase();
      if (['success', 'sukses', 'berhasil', 'done'].some(k => s.includes(k))) {
        return '<span class="badge bg-success badge-status">SUCCESS</span>';
      }
      if (['pending', 'process', 'proses'].some(k => s.includes(k))) {
        return '<span class="badge bg-warning text-dark badge-status">PENDING</span>';
      }
      if (['failed', 'gagal', 'error', 'cancel'].some(k => s.includes(k))) {
        return '<span class="badge bg-danger badge-status">FAILED</span>';
      }
      return `<span class="badge bg-info badge-status">${status}</span>`;
    }

    function extractStatus(resp) {
      if (!resp || typeof resp !== 'object') return null;
      // ลองหลายคีย์ที่เป็นไปได้
      return resp.status || resp.transaksi_status || resp.transaction_status || resp.data?.status || resp.result?.status || null;
    }

    function extractMessage(resp) {
      if (!resp || typeof resp !== 'object') return null;
      return resp.message || resp.msg || resp.data?.message || resp.result?.message || null;
    }

    async function doCheck() {
      const refId = el('refId').value.trim();
      if (!refId) {
        alert('กรุณากรอก Ref ID');
        return;
      }

      el('btnCheck').disabled = true; el('btnSpinner').style.display = '';

      const form = new FormData();
      form.append('action', 'check');
      form.append('ref_id', refId);

      let resJson = null;
      try {
        const res = await fetch(location.href, { method: 'POST', body: form });
        resJson = await res.json();
      } catch (e) {
        resJson = { ok:false, error: 'เชื่อมต่อไม่ได้', detail: String(e) };
      }

      el('btnCheck').disabled = false; el('btnSpinner').style.display = 'none';

      // แสดงผล
      el('resultWrap').style.display = '';
      el('resRefId').textContent = refId;

      const http = `HTTP ${resJson?.http_code ?? '-'} | ${resJson?.ok ? 'OK' : 'ERR'}`;
      const curl = `errno ${resJson?.curl_errno ?? '-'}${resJson?.curl_error ? ' : ' + resJson.curl_error : ''}`;
      el('resHttp').textContent = http;
      el('resCurl').textContent = curl;

      const sign = resJson?.payload?.signature || '-';
      el('resSign').textContent = sign;

      const payloadStr = prettify(resJson?.payload ?? {});
      el('payloadPre').textContent = payloadStr;

      let respObj = resJson?.response ?? null;
      // หาก parse ไม่ได้ ให้แสดง raw
      if (!respObj && resJson?.response_raw) {
        try { respObj = JSON.parse(resJson.response_raw); } catch {}
      }

      el('jsonPre').textContent = prettify(respObj ?? resJson);

      const status = extractStatus(respObj);
      const message = extractMessage(respObj);
      el('statusBadge').innerHTML = badgeForStatus(status) + (message ? `<div class="small text-secondary mt-1">${message}</div>` : '');
    }

    document.addEventListener('DOMContentLoaded', () => {
      el('btnCheck').addEventListener('click', doCheck);
      el('btnClear').addEventListener('click', () => {
        el('refId').value = '';
        el('resultWrap').style.display = 'none';
      });
      // Enter เพื่อเช็คเร็ว ๆ
      el('refId').addEventListener('keydown', (ev) => {
        if (ev.key === 'Enter') { doCheck(); }
      });

      // คัดลอก
      document.body.addEventListener('click', (e) => {
        const t = e.target.closest('.copy-btn');
        if (!t) return;
        const what = t.getAttribute('data-copy');
        if (what === 'payload') {
          navigator.clipboard.writeText(el('payloadPre').textContent || '');
        } else if (what === 'json') {
          navigator.clipboard.writeText(el('jsonPre').textContent || '');
        }
        t.textContent = 'คัดลอกแล้ว!';
        setTimeout(() => { t.textContent = what === 'payload' ? 'คัดลอก Payload' : 'คัดลอก JSON'; }, 1200);
      });
    });
  </script>
</body>
</html>