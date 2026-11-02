<style>
.b2{--b2-bg:var(--sx-surface,#0f1626);--b2-raised:var(--sx-raised,#152036);--b2-line:var(--sx-line,rgba(196,206,235,.09));--b2-line2:var(--sx-line-2,rgba(196,206,235,.16));--b2-fg:var(--sx-fg,#e9ecf5);--b2-fg2:var(--sx-fg-2,#a3abc2);--b2-fg3:var(--sx-fg-3,#6d7690);--b2-blue:var(--sx-blue,#3b7bff);--b2-blue-bg:var(--sx-blue-bg,rgba(59,123,255,.14));--b2-ok:#22c55e;--b2-ok-bg:rgba(34,197,94,.13);--b2-warn:#f59e0b;--b2-warn-bg:rgba(245,158,11,.14);--b2-bad:#ef4444;--b2-bad-bg:rgba(239,68,68,.13);color:var(--b2-fg)}
.b2 *{box-sizing:border-box}
.b2-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.b2-head h1{font-size:22px;font-weight:700;margin:0;color:var(--b2-fg)}
.b2-head p{margin:2px 0 0;color:var(--b2-fg3);font-size:13px}
.b2-card{background:var(--b2-bg);border:1px solid var(--b2-line);border-radius:12px;padding:18px;margin-bottom:16px}
.b2-card h2{font-size:15px;font-weight:700;margin:0 0 14px;color:var(--b2-fg);display:flex;align-items:center;gap:8px}
.b2-card h2 .n{width:22px;height:22px;border-radius:50%;background:var(--b2-blue-bg);color:var(--b2-blue);display:inline-flex;align-items:center;justify-content:center;font-size:12px}
.b2-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px}
.b2-f label{display:block;font-size:12px;font-weight:600;color:var(--b2-fg2);margin-bottom:5px}
.b2-f input,.b2-f select,.b2-f textarea{width:100%;height:40px;border-radius:8px;border:1px solid var(--b2-line2);background:var(--b2-raised);color:var(--b2-fg);padding:0 11px;font-size:14px}
.b2-f input[type=file]{padding:8px 10px;height:auto}
.b2-f input:focus,.b2-f select:focus{outline:none;border-color:var(--b2-blue);box-shadow:0 0 0 3px var(--b2-blue-bg)}
.b2-f .hint{font-size:11px;color:var(--b2-fg3);margin-top:4px}
.b2-btn{display:inline-flex;align-items:center;justify-content:center;gap:6px;height:40px;padding:0 18px;border-radius:9px;border:1px solid var(--b2-blue);background:var(--b2-blue);color:#fff;font-size:14px;font-weight:600;text-decoration:none;cursor:pointer;white-space:nowrap}
.b2-btn:hover{filter:brightness(1.08);color:#fff}
.b2-btn[disabled]{opacity:.55;cursor:not-allowed}
.b2-btn.ghost{background:transparent;color:var(--b2-fg)!important;border-color:var(--b2-line2)}
.b2-btn.danger{background:transparent;color:var(--b2-bad)!important;border-color:rgba(239,68,68,.45)}
.b2-btn.sm{height:32px;padding:0 12px;font-size:13px}
.b2-alert{border-radius:10px;padding:11px 14px;font-size:14px;margin-bottom:14px;border:1px solid}
.b2-alert.ok{background:var(--b2-ok-bg);border-color:rgba(34,197,94,.35);color:var(--b2-ok)}
.b2-alert.bad{background:var(--b2-bad-bg);border-color:rgba(239,68,68,.35);color:#fca5a5}
.b2-alert.warn{background:var(--b2-warn-bg);border-color:rgba(245,158,11,.35);color:#fcd34d}
.b2-pill{display:inline-block;padding:3px 10px;border-radius:999px;font-size:12px;font-weight:600;background:var(--b2-raised);color:var(--b2-fg2)}
.b2-pill.booked{background:var(--b2-blue-bg);color:var(--b2-blue)}
.b2-pill.in_transit{background:var(--b2-warn-bg);color:var(--b2-warn)}
.b2-pill.delivered{background:var(--b2-ok-bg);color:var(--b2-ok)}
.b2-pill.rto,.b2-pill.cancelled{background:var(--b2-bad-bg);color:var(--b2-bad)}
.b2-table{width:100%;border-collapse:collapse;font-size:14px}
.b2-table th{font-size:11px;letter-spacing:.05em;text-transform:uppercase;color:var(--b2-fg3);font-weight:700;text-align:left;padding:10px 12px;border-bottom:1px solid var(--b2-line)}
.b2-table td{padding:12px;border-bottom:1px solid var(--b2-line);color:var(--b2-fg);vertical-align:middle}
.b2-table tr:hover td{background:var(--b2-raised)}
.b2-table a{color:var(--b2-blue);text-decoration:none;font-weight:600}
.b2-muted{color:var(--b2-fg3);font-size:12px}
.b2-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}
.b2-tabs a{padding:7px 14px;border-radius:999px;font-size:13px;font-weight:600;color:var(--b2-fg2);background:var(--b2-bg);border:1px solid var(--b2-line);text-decoration:none}
.b2-tabs a.on{background:var(--b2-blue-bg);color:var(--b2-blue);border-color:transparent}
.b2-box{display:grid;grid-template-columns:90px repeat(4,1fr) 36px;gap:8px;align-items:end;margin-bottom:8px}
@media (max-width:640px){.b2-box{grid-template-columns:1fr 1fr 1fr}}
.b2-x{height:40px;border-radius:8px;border:1px solid var(--b2-line2);background:transparent;color:var(--b2-fg3);cursor:pointer;font-size:18px}
.b2-sum{display:flex;gap:18px;flex-wrap:wrap;font-size:13px;color:var(--b2-fg2);margin-top:8px}
.b2-sum b{color:var(--b2-fg)}
.b2-rates{display:grid;gap:10px}
.b2-rate{display:flex;align-items:center;gap:14px;padding:14px;border:1px solid var(--b2-line2);border-radius:10px;cursor:pointer;background:var(--b2-raised)}
.b2-rate:hover{border-color:var(--b2-blue)}
.b2-rate.on{border-color:var(--b2-blue);box-shadow:0 0 0 3px var(--b2-blue-bg)}
.b2-rate input{accent-color:var(--b2-blue);width:18px;height:18px}
.b2-rate .nm{font-weight:700;font-size:15px}
.b2-rate .mt{font-size:12px;color:var(--b2-fg3)}
.b2-rate .pr{margin-left:auto;font-size:18px;font-weight:800;white-space:nowrap}
.b2-split{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:16px;align-items:start}
@media (max-width:1100px){.b2-split{grid-template-columns:1fr}}
.b2-sticky{position:sticky;top:80px}
.b2-kv{display:grid;grid-template-columns:140px 1fr;gap:8px 12px;font-size:14px}
.b2-kv dt{color:var(--b2-fg3);font-weight:500}
.b2-kv dd{margin:0;color:var(--b2-fg)}
.b2-tl{list-style:none;margin:0;padding:0}
.b2-tl li{position:relative;padding:0 0 16px 22px;border-left:2px solid var(--b2-line2);margin-left:6px}
.b2-tl li:last-child{border-left-color:transparent}
.b2-tl li::before{content:"";position:absolute;left:-7px;top:2px;width:12px;height:12px;border-radius:50%;background:var(--b2-blue)}
.b2-tl .t{font-weight:600;font-size:14px}
.b2-tl .s{font-size:12px;color:var(--b2-fg3)}
.b2-empty{text-align:center;padding:48px 16px;color:var(--b2-fg3)}
.b2-empty i{font-size:40px;color:var(--b2-fg3);display:block;margin-bottom:8px}
.b2-check{display:flex;align-items:center;gap:8px;font-size:13px;color:var(--b2-fg2)}
.b2-check input{width:16px;height:16px;accent-color:var(--b2-blue)}
.b2-seg{display:inline-flex;border:1px solid var(--b2-line2);border-radius:9px;overflow:hidden}
.b2-seg label{margin:0;padding:9px 16px;font-size:13px;font-weight:600;color:var(--b2-fg2);cursor:pointer}
.b2-seg input{display:none}
.b2-seg input:checked+span{color:var(--b2-blue)}
.b2-seg label:has(input:checked){background:var(--b2-blue-bg)}
</style>
