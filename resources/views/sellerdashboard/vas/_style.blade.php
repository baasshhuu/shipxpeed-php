<style>
.vas{--v-bg:var(--sx-surface,#0f1626);--v-raised:var(--sx-raised,#152036);--v-line:var(--sx-line,rgba(196,206,235,.09));--v-line2:var(--sx-line-2,rgba(196,206,235,.16));--v-fg:var(--sx-fg,#e9ecf5);--v-fg2:var(--sx-fg-2,#a3abc2);--v-fg3:var(--sx-fg-3,#6d7690);--v-blue:var(--sx-blue,#3b7bff);--v-blue-bg:var(--sx-blue-bg,rgba(59,123,255,.14));--v-ok:#22c55e;--v-warn:#f59e0b;--v-bad:#ef4444;color:var(--v-fg)}
.vas *{box-sizing:border-box}
.vas-crumb{font-size:13px;color:var(--v-fg3);margin:0 0 14px;display:flex;gap:8px;align-items:center;flex-wrap:wrap}
.vas-crumb a{color:var(--v-blue);text-decoration:none}
.vas-wrap{display:grid;grid-template-columns:230px minmax(0,1fr);gap:18px;align-items:start}
@media (max-width:991px){.vas-wrap{grid-template-columns:1fr}}
.vas-side{background:var(--v-bg);border:1px solid var(--v-line);border-radius:12px;padding:14px 8px;position:sticky;top:84px}
@media (max-width:991px){.vas-side{position:static;display:flex;flex-wrap:wrap;gap:4px}.vas-side h6{width:100%}}
.vas-side h6{font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:var(--v-fg3);margin:4px 12px 10px;font-weight:700}
.vas-side a{display:flex;align-items:center;gap:9px;padding:9px 12px;border-radius:8px;font-size:14px;color:var(--v-fg2);text-decoration:none}
.vas-side a:hover{background:var(--v-raised);color:var(--v-fg)}
.vas-side a.on{background:var(--v-blue-bg);color:var(--v-blue);font-weight:600}
.vas-new{margin-left:auto;font-size:10px;font-weight:700;padding:1px 6px;border-radius:5px;background:#16a34a;color:#fff}
.vas-main{background:var(--v-bg);border:1px solid var(--v-line);border-radius:12px;padding:20px;min-width:0}
.vas-hero{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:24px;align-items:center;padding:30px;border-radius:14px;background:linear-gradient(115deg,rgba(124,58,237,.10),rgba(59,123,255,.06) 45%,rgba(37,211,102,.12));border:1px solid var(--v-line)}
@media (max-width:1279px){.vas-hero{grid-template-columns:1fr;padding:22px}}
.vas-hero h1{font-size:34px;line-height:1.15;font-weight:800;margin:0 0 12px;color:var(--v-fg)}
.vas-hero h1 span{background:linear-gradient(90deg,#6d6bff,#22c55e);-webkit-background-clip:text;background-clip:text;color:transparent}
@media (max-width:575px){.vas-hero h1{font-size:27px}}
.vas-lead{font-size:15px;color:var(--v-fg2);margin:0 0 18px;max-width:560px;line-height:1.55}
.vas-dash{border:1.5px dashed rgba(109,107,255,.55);border-radius:10px;padding:13px 16px;font-size:14.5px;line-height:1.55;max-width:560px;margin-bottom:22px;color:var(--v-fg)}
.vas-dash b{color:var(--v-ok)}
.vas-price{display:flex;align-items:center;gap:14px;max-width:470px;padding:14px 18px;border-radius:10px;background:rgba(34,197,94,.12);border:1px solid rgba(34,197,94,.25);margin-bottom:8px}
.vas-price .ic{width:42px;height:42px;border-radius:10px;background:rgba(34,197,94,.2);display:grid;place-items:center;font-size:22px;color:var(--v-ok);flex:none}
.vas-price .amt{font-size:28px;font-weight:800;color:var(--v-ok);line-height:1}
.vas-price .per{font-size:15px;color:var(--v-fg)}
.vas-note{font-size:12px;color:var(--v-fg3);margin:0 0 22px}
.vas-btns{display:flex;gap:14px;flex-wrap:wrap;align-items:center}
.vas-btn{height:48px;min-width:170px;padding:0 22px;border-radius:9px;font-size:16px;font-weight:600;display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1.5px solid transparent;cursor:pointer;text-decoration:none;transition:filter .15s,background .15s}
.vas-btn:disabled,.vas-btn.off{opacity:.55;cursor:not-allowed}
.vas-btn-ghost{background:transparent;border-color:var(--v-blue);color:var(--v-blue)}
.vas-btn-ghost:hover{background:var(--v-blue-bg);color:var(--v-blue)}
.vas-btn-go{background:#16a34a;color:#fff}
.vas-btn-go:hover{filter:brightness(1.08);color:#fff}
.vas-btn-sm{height:34px;min-width:0;padding:0 14px;font-size:13px}
.vas-link{background:none;border:0;color:var(--v-fg3);font-size:13px;text-decoration:underline;padding:0;cursor:pointer}
.vas-art{position:relative;display:flex;justify-content:center}
.vas-card{border:1px solid var(--v-line);border-radius:12px;padding:18px;margin-top:18px;background:var(--v-raised)}
.vas-card h3{font-size:16px;font-weight:700;margin:0 0 4px;color:var(--v-fg)}
.vas-card p.sub{font-size:13px;color:var(--v-fg2);margin:0 0 14px;line-height:1.5}
.vas-stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}
.vas-stat{padding:12px 14px;border-radius:10px;background:var(--v-bg);border:1px solid var(--v-line)}
.vas-stat b{display:block;font-size:20px;color:var(--v-fg)}
.vas-stat span{font-size:12px;color:var(--v-fg3)}
.vas-tbl{width:100%;border-collapse:separate;border-spacing:0;font-size:13.5px;border:1px solid var(--v-line2);border-radius:10px;overflow:hidden}
.vas-tbl th,.vas-tbl td{padding:11px 14px;border-bottom:1px solid var(--v-line);color:var(--v-fg);vertical-align:middle;text-align:left}
.vas-tbl thead th{font-weight:600;background:var(--v-bg);font-size:12.5px;color:var(--v-fg2)}
.vas-tbl tr:last-child td{border-bottom:0}
.vas-muted{color:var(--v-fg3)}
.pill{display:inline-block;padding:3px 10px;border-radius:999px;font-size:11.5px;font-weight:600;white-space:nowrap}
.pill.ok{background:rgba(34,197,94,.14);color:#4ade80}.pill.warn{background:rgba(245,158,11,.16);color:#fbbf24}
.pill.bad{background:rgba(239,68,68,.14);color:#f87171}.pill.muted{background:rgba(127,140,170,.14);color:var(--v-fg2)}.pill.blue{background:var(--v-blue-bg);color:var(--v-blue)}
.vas-banner{display:flex;gap:10px;align-items:flex-start;padding:11px 14px;border-radius:10px;font-size:13px;line-height:1.5;margin-bottom:14px;background:rgba(6,182,212,.12);color:var(--v-fg)}
.vas-banner i{font-size:18px;color:#22d3ee;flex:none}
.vas-banner.warn{background:rgba(245,158,11,.14)}.vas-banner.warn i{color:#fbbf24}
.vas-plans{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;max-width:560px;margin-bottom:18px}
@media (max-width:575px){.vas-plans{grid-template-columns:1fr}}
.vas-plan{position:relative;display:block;padding:14px;border-radius:12px;border:1.5px solid var(--v-line2);background:var(--v-bg);cursor:pointer;text-align:center}
.vas-plan input{position:absolute;opacity:0}
.vas-plan b{display:block;font-size:22px;color:var(--v-fg)}
.vas-plan span{font-size:12.5px;color:var(--v-fg2)}
.vas-plan:has(input:checked){border-color:#22c55e;background:rgba(34,197,94,.08)}
.vas-plan:has(input:focus-visible){outline:2px solid var(--v-blue);outline-offset:2px}
.vas-feat{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px}
.vas-feat div{padding:14px;border-radius:10px;background:var(--v-bg);border:1px solid var(--v-line);font-size:13px;color:var(--v-fg2);line-height:1.5}
.vas-feat i{font-size:22px;color:#6d6bff;display:block;margin-bottom:6px}
.vas-feat b{display:block;color:var(--v-fg);font-size:14px;margin-bottom:2px}
.vas-meter{width:58px;height:8px;border-radius:4px;background:var(--v-line2);overflow:hidden;display:inline-block;vertical-align:middle;margin-right:8px}
.vas-meter i{display:block;height:100%}
.vas-mock{width:270px;border-radius:16px;background:#fff;color:#111;box-shadow:0 18px 50px rgba(0,0,0,.35);overflow:hidden;font-size:12.5px}
.vas-mock .hd{padding:12px 14px;color:#fff;font-weight:700;display:flex;align-items:center;gap:8px}
.vas-mock .bd{padding:14px}
.vas-mock .row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #eee}
.vas-mock .row:last-child{border-bottom:0}
</style>
