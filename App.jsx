import { useState, useEffect, useRef, useCallback } from 'react'
import { BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer } from 'recharts'
import './App.css'

const API_BASE = import.meta.env.VITE_API_BASE ?? '/api/react'

const BG    = '#060F1E'
const NAVY  = '#0B1F3A'
const G     = '#C9963E'
const STEEL = '#4A7C9E'
const CARD  = '#0d1f3c'
const T1    = '#FFFFFF'
const T2    = '#8899AA'
const PASS  = '#2ECC71'
const FAIL  = '#E74C3C'

const MONO = "'JetBrains Mono','IBM Plex Mono','Courier New',monospace"

// ── Demo sample presets ───────────────────────────────────────────────────────
const SAMPLE_PRESETS = [
  { id: 'clean',     label: 'Clean Surface',    file: 'sample_clean_01.jpg',     expect: 'PASS'    },
  { id: 'crack',     label: 'Surface Crack',    file: 'sample_crack_01.jpg',     expect: 'FAIL'    },
  { id: 'corrosion', label: 'Corrosion',        file: 'sample_corrosion_01.jpg', expect: 'FAIL'    },
  { id: 'scratch',   label: 'Surface Scratch',  file: 'sample_scratch_01.jpg',   expect: 'FLAGGED' },
  { id: 'porosity',  label: 'Pitting/Porosity', file: 'sample_porosity_01.jpg',  expect: 'FLAGGED' },
]

const card = (extra = {}) => ({
  background: CARD,
  borderTop: `1px solid rgba(201,150,62,0.12)`,
  borderRight: 'none',
  borderBottom: 'none',
  borderLeft: 'none',
  borderRadius: 0,
  padding: 20,
  ...extra,
})

const hdr = {
  color: G,
  fontSize: 10,
  letterSpacing: 3,
  textTransform: 'uppercase',
  fontWeight: 700,
  fontFamily: MONO,
}

// ── Shield + eye mark (adapted from brand SVG) ────────────────────────────────
function ShieldMark({ size = 28 }) {
  return (
    <svg width={size} height={Math.round(size * 1.2)} viewBox="0 0 200 240" fill="none">
      <path d="M 28,32 L 172,32 L 180,132 C 160,210 140,218 100,218 C 60,218 40,210 20,132 Z"
        fill={NAVY} stroke={G} strokeWidth="4" strokeOpacity="0.5" />
      <path d="M 22,118 Q 100,66 178,118" stroke={G} strokeWidth="3" strokeLinecap="round" fill="none" opacity="0.7"/>
      <path d="M 22,118 Q 100,156 178,118" stroke={G} strokeWidth="3" strokeLinecap="round" fill="none" opacity="0.7"/>
      <circle cx="100" cy="118" r="40" stroke={G} strokeWidth="4" fill="none"/>
      <circle cx="100" cy="118" r="36" fill="#162D52"/>
      <circle cx="100" cy="118" r="18" fill={NAVY}/>
      <circle cx="109" cy="109" r="6" fill={G} opacity="0.9"/>
    </svg>
  )
}

// ── Status indicator dot ───────────────────────────────────────────────────────
function Indicator({ ok, label, detail }) {
  const color      = ok === null ? T2 : ok ? PASS : FAIL
  const statusText = ok === null ? 'CHECKING' : ok ? (detail || 'READY') : 'OFFLINE'
  return (
    <div style={{ display:'flex', alignItems:'center', gap:10, padding:'7px 0', borderBottom:`1px solid rgba(201,150,62,0.07)` }}>
      <span className={ok === true ? 'dot dot-live' : 'dot'} style={{ '--dc': color }} />
      <span style={{ flex:1, color:T2, fontSize:12, letterSpacing:0.3 }}>{label}</span>
      <span style={{ color, fontSize:10, fontWeight:700, letterSpacing:1.5 }}>{statusText}</span>
    </div>
  )
}

// ── Upload icon ────────────────────────────────────────────────────────────────
function UploadIcon() {
  return (
    <svg width="40" height="40" viewBox="0 0 40 40" fill="none" style={{ marginBottom:10 }}>
      <circle cx="20" cy="20" r="19" stroke={G} strokeWidth="1.5" strokeOpacity="0.4"/>
      <path d="M20 26V14M14 20l6-6 6 6" stroke={G} strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
      <path d="M13 30h14" stroke={G} strokeWidth="1.5" strokeLinecap="round" strokeOpacity="0.6"/>
    </svg>
  )
}

// ── Spinner ────────────────────────────────────────────────────────────────────
function Spinner({ size = 20 }) {
  return (
    <svg className="spin" width={size} height={size} viewBox="0 0 20 20" fill="none">
      <circle cx="10" cy="10" r="8" stroke={STEEL} strokeWidth="2" strokeOpacity="0.2"/>
      <path d="M10 2a8 8 0 0 1 8 8" stroke={G} strokeWidth="2" strokeLinecap="round"/>
    </svg>
  )
}

// ── Empty image placeholder ────────────────────────────────────────────────────
function ImagePlaceholder() {
  return (
    <div style={{ ...card(), display:'flex', flexDirection:'column', alignItems:'center', justifyContent:'center', minHeight:220, gap:12 }}>
      <svg width="48" height="48" viewBox="0 0 48 48" fill="none" style={{ opacity:0.25 }}>
        <rect x="4" y="8" width="40" height="32" stroke={G} strokeWidth="2"/>
        <circle cx="17" cy="21" r="5" stroke={G} strokeWidth="2"/>
        <path d="M4 32l12-10 8 8 6-6 14 14" stroke={G} strokeWidth="2" strokeLinejoin="round"/>
      </svg>
      <span style={{ color:`${T2}55`, fontSize:13, letterSpacing:2 }}>NO IMAGE UPLOADED</span>
    </div>
  )
}

// ── Main App ───────────────────────────────────────────────────────────────────
export default function App() {
  const [health,          setHealth]          = useState(null)
  const [backendOk,       setBackendOk]       = useState(null)
  const [now,             setNow]             = useState(new Date())
  const [result,          setResult]          = useState(null)
  const [imagePreview,    setImagePreview]    = useState(null)
  const [naturalSize,     setNaturalSize]     = useState({ w:0, h:0 })
  const [dragging,        setDragging]        = useState(false)
  const [inspecting,      setInspecting]      = useState(false)
  const [uploadError,     setUploadError]     = useState(null)
  const [stats,           setStats]           = useState(null)
  const [historyData,     setHistoryData]     = useState(null)
  const [historyLoading,  setHistoryLoading]  = useState(false)
  const [historyTick,     setHistoryTick]     = useState(0)
  const [plcLog,          setPlcLog]          = useState([])

  const inputRef   = useRef(null)
  const prevUrlRef = useRef(null)

  // ── Clock ──────────────────────────────────────────────────────────────────
  useEffect(() => {
    const id = setInterval(() => setNow(new Date()), 1000)
    return () => clearInterval(id)
  }, [])

  // ── Health ─────────────────────────────────────────────────────────────────
  const fetchHealth = useCallback(() => {
    fetch(`${API_BASE}/health`)
      .then(r => r.json())
      .then(d => { setHealth(d); setBackendOk(true) })
      .catch(() => { setHealth(null); setBackendOk(false) })
  }, [])

  useEffect(() => {
    fetchHealth()
    const id = setInterval(fetchHealth, 30000)
    return () => clearInterval(id)
  }, [fetchHealth])

  // ── Stats ──────────────────────────────────────────────────────────────────
  useEffect(() => {
    const go = () => fetch(`${API_BASE}/stats`).then(r => r.json()).then(setStats).catch(() => {})
    go()
    const id = setInterval(go, 10000)
    return () => clearInterval(id)
  }, [])

  // ── History ────────────────────────────────────────────────────────────────
  useEffect(() => {
    setHistoryLoading(true)
    fetch(`${API_BASE}/audit-log?page=1&page_size=50`)
      .then(r => r.json())
      .then(d => { setHistoryData(d); setHistoryLoading(false) })
      .catch(() => setHistoryLoading(false))
  }, [historyTick])

  // ── File submit ────────────────────────────────────────────────────────────
  const handleFile = useCallback(async (file) => {
    if (!file) return
    setUploadError(null)
    setInspecting(true)
    setResult(null)
    setNaturalSize({ w:0, h:0 })
    if (prevUrlRef.current) URL.revokeObjectURL(prevUrlRef.current)
    const url = URL.createObjectURL(file)
    prevUrlRef.current = url
    setImagePreview(url)

    const fd = new FormData()
    fd.append('file', file)
    fd.append('operator_id', 'OP-WEB-001')

    try {
      const resp = await fetch(`${API_BASE}/inspect`, { method:'POST', body:fd })
      if (!resp.ok) {
        const err = await resp.json().catch(() => ({}))
        throw new Error(err.detail || `HTTP ${resp.status}`)
      }
      const data = await resp.json()
      setResult(data)
      if (data.inspection_id) {
        setTimeout(() => { window.location.href = '/results/' + data.inspection_id }, 800)
      }
      setPlcLog(prev => [{
        time: new Date().toLocaleTimeString('en-US', { hour12:false }),
        pass_fail: data.pass_fail,
        instrument_id: data.instrument_id,
        sent: data.plc_signal_sent,
      }, ...prev].slice(0, 5))
      setHistoryTick(k => k + 1)
    } catch (e) {
      setUploadError(e.message || 'Inspection failed')
    } finally {
      setInspecting(false)
    }
  }, [])

  const onDrop = useCallback(e => {
    e.preventDefault(); setDragging(false)
    const f = e.dataTransfer.files[0]
    if (f) handleFile(f)
  }, [handleFile])

  // BUG FIX: append anchor to body before click (was detached — failed in Firefox)
  const downloadCsv = useCallback(() => {
    const a = document.createElement('a')
    a.href = `${API_BASE}/audit-log?download=true`
    a.download = 'cosmas_audit_log.csv'
    document.body.appendChild(a)
    a.click()
    document.body.removeChild(a)
  }, [])

  // ── Load sample image from EC2 public folder ──────────────────────────────
  const loadSampleImage = useCallback(async (preset) => {
    if (inspecting) return
    try {
      const url = `/demo-samples/${preset.file}`
      const resp = await fetch(url)
      if (!resp.ok) throw new Error(`Could not load sample (${resp.status})`)
      const blob = await resp.blob()
      const file = new File([blob], preset.file, { type: blob.type || 'image/jpeg' })
      handleFile(file)
    } catch (e) {
      setUploadError(`Sample load failed: ${e.message}`)
    }
  }, [inspecting, handleFile])

  // ── Derived ────────────────────────────────────────────────────────────────
  const isPASS     = result?.pass_fail === 'PASS'
  const isFLAGGED  = result?.pass_fail === 'FLAGGED'
  const verdictBg  = !result ? CARD : isPASS ? PASS : isFLAGGED ? G : FAIL
  const detections = result?.all_detections || []
  const chartData  = stats
    ? Object.entries(stats.defect_counts).map(([name, count]) => ({
        name: name.charAt(0).toUpperCase() + name.slice(1),
        count,
      }))
    : []

  // ── Helpers ──────────────────────────────────────────────────────────────────
  const fmtDefect = (d) => (!d || d === 'none' || d === 'null' || d === 'unknown') ? 'No Defect' : d

  // ── Render ─────────────────────────────────────────────────────────────────
  return (
    <div style={{ minHeight:'100vh', background:BG, color:T1, fontFamily:MONO }}>
      <style>{`
        .grid-3col { display:grid; grid-template-columns:280px 1fr 320px; gap:1px; padding:16px; align-items:start; background:rgba(201,150,62,0.1); }
        @media (max-width: 900px) {
          .grid-3col { grid-template-columns: 1fr !important; }
          .grid-3col > div { width: 100%; }
        }
        @media (max-width: 600px) {
          header { padding: 0 12px !important; flex-wrap: wrap; height: auto !important; min-height: 56px; gap: 8px; }
          header > div:first-child { flex: 1; }
        }
      `}</style>

      {/* ═══════════════════════════════════════ FULL-SCREEN ANALYSIS LOCKOUT */}
      {inspecting && (
        <div style={{
          position: 'fixed', inset: 0, zIndex: 9999,
          background: 'rgba(6,15,30,0.96)',
          display: 'flex', flexDirection: 'column',
          alignItems: 'center', justifyContent: 'center',
          gap: 0,
          backdropFilter: 'blur(4px)',
        }}>
          {/* Animated shield */}
          <div className="spin-slow" style={{ marginBottom: 28 }}>
            <ShieldMark size={72} />
          </div>

          {/* Primary label */}
          <div style={{
            color: G, fontSize: 13, fontWeight: 700,
            letterSpacing: 6, textTransform: 'uppercase',
            fontFamily: MONO, marginBottom: 10,
          }}>
            ANALYZING INSTRUMENT
          </div>

          {/* Step ticker */}
          <div style={{ color: '#4A7C9E', fontSize: 11, letterSpacing: 2, marginBottom: 32 }}>
            RUNNING 5-TOOL AGENTIC LOOP · CLAUDE + YOLO
          </div>

          {/* Progress steps */}
          {[
            'IMAGE QUALITY VERIFICATION',
            'TWO-STAGE YOLO SCAN',
            'REGULATORY CONTEXT LOOKUP',
            'COMPOSITE RISK SCORE CALCULATION',
            'FINALIZING INSPECTION REPORT',
          ].map((step, i) => (
            <div key={i} className={`analysis-step step-${i}`} style={{
              display: 'flex', alignItems: 'center', gap: 10,
              padding: '5px 0', opacity: 0,
              animation: `fadeInStep 0.4s ease ${i * 3.5 + 1}s forwards`,
            }}>
              <svg width="8" height="8" viewBox="0 0 8 8">
                <circle cx="4" cy="4" r="3" fill={G} opacity="0.7"/>
              </svg>
              <span style={{ color: '#8899AA', fontSize: 10, letterSpacing: 2 }}>{step}</span>
            </div>
          ))}

          {/* Do not refresh warning */}
          <div style={{
            marginTop: 40,
            padding: '10px 24px',
            border: '1px solid rgba(231,76,60,0.4)',
            background: 'rgba(231,76,60,0.06)',
            display: 'flex', alignItems: 'center', gap: 10,
          }}>
            <svg width="14" height="14" viewBox="0 0 14 14" fill="none">
              <path d="M7 1L13 12H1L7 1Z" stroke="#E74C3C" strokeWidth="1.5" fill="none"/>
              <line x1="7" y1="5" x2="7" y2="8.5" stroke="#E74C3C" strokeWidth="1.5" strokeLinecap="round"/>
              <circle cx="7" cy="10.5" r="0.7" fill="#E74C3C"/>
            </svg>
            <span style={{ color: '#E74C3C', fontSize: 10, fontWeight: 700, letterSpacing: 2 }}>
              DO NOT CLOSE OR REFRESH — ANALYSIS IN PROGRESS
            </span>
          </div>
        </div>
      )}

      {/* ═══════════════════════════════════════ TOP BAR */}
      <header style={{
        height: 56,
        background: NAVY,
        borderBottom: `1px solid rgba(201,150,62,0.2)`,
        display: 'flex',
        alignItems: 'center',
        padding: '0 20px',
        gap: 16,
        position: 'sticky',
        top: 0,
        zIndex: 100,
        boxShadow: 'none',
      }}>
        {/* Logo lockup */}
        <div style={{ display:'flex', alignItems:'center', gap:10 }}>
          <ShieldMark size={30} />
          <div style={{ display:'flex', alignItems:'baseline', gap:8, paddingRight:20, borderRight:`1px solid rgba(201,150,62,0.25)` }}>
            <span style={{ fontFamily:'Georgia,serif', fontSize:18, fontWeight:700, color:T1, letterSpacing:2 }}>COSMAS</span>
            <span style={{ fontSize:13, fontWeight:600, color:STEEL, letterSpacing:3 }}>SENTRY</span>
          </div>
        </div>

        {/* Center: system label with pulsing dot */}
        <div style={{ flex:1, display:'flex', alignItems:'center', justifyContent:'center', gap:10 }}>
          <span className="pulse-dot" />
          <span style={{ color:G, fontSize:10, fontWeight:700, letterSpacing:4, textTransform:'uppercase', fontFamily:MONO }}>
            AI QUALITY INSPECTION SYSTEM
          </span>
        </div>

      {/* ── Blade app nav strip ── */}
      <div style={{
        display:'flex', alignItems:'center', gap:'0',
        background:'rgba(13,21,38,0.95)', borderBottom:'1px solid rgba(201,150,62,0.2)',
        padding:'0 20px', height:'32px', flexShrink:0,
      }}>
        <span style={{fontSize:'9px',letterSpacing:'0.14em',color:'rgba(107,127,153,0.6)',textTransform:'uppercase',marginRight:'16px'}}>
          NAVIGATE →
        </span>
        {[
          {label:'HOME',     href:'/'},
          {label:'DASHBOARD',href:'/dashboard'},
          {label:'AUDIT LOG',href:'/audit-log'},
          {label:'ASK DAMIAN',href:'/damian'},
          {label:'SETTINGS', href:'/settings/threshold'},
        ].map(({label,href}) => (
          <a key={label} href={href} style={{
            fontSize:'9px', letterSpacing:'0.13em', color:'#6b7f99',
            textDecoration:'none', textTransform:'uppercase',
            padding:'0 14px', height:'100%', display:'flex', alignItems:'center',
            borderRight:'1px solid rgba(201,150,62,0.12)',
            transition:'color 0.15s, background 0.15s',
          }}
          onMouseEnter={e=>{e.currentTarget.style.color='#d4dde8';e.currentTarget.style.background='rgba(201,150,62,0.06)';}}
          onMouseLeave={e=>{e.currentTarget.style.color='#6b7f99';e.currentTarget.style.background='transparent';}}
          >{label}</a>
        ))}
      </div>

        {/* Right: status + clock + version */}
        <div style={{ display:'flex', alignItems:'center', gap:16 }}>
          <div style={{ display:'flex', alignItems:'center', gap:7 }}>
            <span style={{
              width:8, height:8, borderRadius:'50%',
              background: backendOk === null ? T2 : backendOk ? PASS : FAIL,
              display:'inline-block',
              boxShadow: backendOk ? `0 0 8px ${PASS}88` : backendOk === false ? `0 0 8px ${FAIL}88` : 'none',
            }}/>
            <span style={{ color: backendOk ? PASS : backendOk === false ? FAIL : T2, fontSize:11, fontWeight:600, letterSpacing:1 }}>
              {backendOk === null ? 'CONNECTING' : backendOk ? 'CONNECTED' : 'BACKEND OFFLINE'}
            </span>
          </div>
          <div style={{ textAlign:'right', lineHeight:1.4 }}>
            <div style={{ fontSize:14, fontVariantNumeric:'tabular-nums', fontWeight:500, letterSpacing:1 }}>
              {now.toLocaleTimeString('en-US', { hour12:false })}
            </div>
            <div style={{ fontSize:10, color:T2 }}>
              {now.toLocaleDateString('en-US', { month:'short', day:'numeric', year:'numeric' })}
            </div>
          </div>
          <span style={{ color:`${T2}88`, fontSize:10, letterSpacing:1, fontFamily:MONO }}>REV 0.1.0</span>
        </div>
      </header>

      {/* ═══════════════════════════════════════ THREE-COLUMN GRID */}
      <div className="grid-3col">

        {/* ─── LEFT COLUMN ──────────────────────────────────────────────── */}
        <div style={{ display:'flex', flexDirection:'column', gap:1, background:BG }}>

          {/* System Status */}
          <div style={card()}>
            <div style={{ ...hdr, marginBottom:14 }}>[ SYSTEM STATUS ]</div>
            <Indicator
              ok={backendOk === null ? null : backendOk && !!health?.model_loaded}
              label="AI Model"
              detail="READY"
            />
            <Indicator
              ok={backendOk === null ? null : backendOk && !!health?.plc_connected}
              label="PLC"
              detail={health?.plc_simulator_mode ? 'SIM ACTIVE' : 'CONNECTED'}
            />
            <Indicator
              ok={backendOk === null ? null : backendOk && !!health?.log_file_exists}
              label="Audit Log"
              detail="LOGGING"
            />
            <Indicator
              ok={backendOk}
              label="Backend"
              detail={health ? `UP ${Math.floor(health.uptime_seconds)}s` : 'CONNECTED'}
            />
          </div>

          {/* Upload Zone */}
          <div style={card({ padding:16 })}>
            <div style={{ ...hdr, marginBottom:14 }}>[ INSTRUMENT IMAGE ]</div>

            <div
              onDragOver={e => { e.preventDefault(); setDragging(true) }}
              onDragLeave={() => setDragging(false)}
              onDrop={onDrop}
              onClick={() => !inspecting && inputRef.current?.click()}
              style={{
                border: `1px solid ${dragging ? G : 'rgba(201,150,62,0.4)'}`,
                borderRadius: 0,
                padding: imagePreview ? 0 : '28px 16px',
                cursor: inspecting ? 'wait' : 'pointer',
                textAlign: 'center',
                transition: 'all 0.2s ease',
                background: dragging ? 'rgba(201,150,62,0.05)' : 'transparent',
                overflow: 'hidden',
                position: 'relative',
              }}
            >
              {/* Analysing overlay */}
              {inspecting && (
                <div style={{
                  position:'absolute', inset:0,
                  background:'rgba(6,15,30,0.88)',
                  display:'flex', flexDirection:'column',
                  alignItems:'center', justifyContent:'center',
                  gap:12, zIndex:10, borderRadius:0,
                }}>
                  <Spinner size={32} />
                  <span style={{ color:G, fontSize:12, letterSpacing:3, fontWeight:600 }}>ANALYZING…</span>
                </div>
              )}

              {imagePreview ? (
                <img
                  src={imagePreview}
                  alt="Uploaded instrument"
                  style={{ width:'100%', display:'block', borderRadius:0 }}
                />
              ) : (
                <div style={{ pointerEvents:'none' }}>
                  <UploadIcon />
                  <div style={{ color:T2, fontSize:13, lineHeight:1.5 }}>
                    Drop instrument image<br/>or click to upload
                  </div>
                  <div style={{ color:`${T2}77`, fontSize:11, marginTop:8, letterSpacing:1 }}>
                    JPG · PNG · BMP
                  </div>
                </div>
              )}
            </div>

            <input
              ref={inputRef}
              type="file"
              accept="image/jpeg,image/png,image/bmp"
              style={{ display:'none' }}
              onChange={e => handleFile(e.target.files[0])}
            />

            {uploadError && (
              <div style={{
                marginTop:12, padding:'9px 12px',
                background:'rgba(231,76,60,0.08)',
                border:'1px solid rgba(231,76,60,0.5)',
                borderRadius:0, color:FAIL, fontSize:12, fontFamily:MONO,
              }}>
                {uploadError}
              </div>
            )}

            {/* ── Sample presets ── */}
            <div style={{ marginTop:14 }}>
              <div style={{ ...hdr, fontSize:9, marginBottom:8, color:`${T2}99` }}>[ LOAD SAMPLE ]</div>
              <div style={{ display:'flex', flexDirection:'column', gap:3 }}>
                {SAMPLE_PRESETS.map(s => {
                  const expectColor = s.expect === 'PASS' ? PASS : s.expect === 'FAIL' ? FAIL : G
                  return (
                    <button
                      key={s.id}
                      onClick={() => loadSampleImage(s)}
                      disabled={inspecting}
                      style={{
                        display:'flex', alignItems:'center', justifyContent:'space-between',
                        background:'transparent',
                        border:'1px solid rgba(201,150,62,0.18)',
                        borderRadius:0, padding:'6px 10px',
                        cursor: inspecting ? 'wait' : 'pointer',
                        color:T1, fontFamily:MONO, fontSize:11, letterSpacing:0.5,
                        width:'100%', textAlign:'left',
                        transition:'background 0.12s, border-color 0.12s',
                        opacity: inspecting ? 0.5 : 1,
                      }}
                      onMouseEnter={e => {
                        e.currentTarget.style.background = 'rgba(201,150,62,0.06)'
                        e.currentTarget.style.borderColor = 'rgba(201,150,62,0.45)'
                      }}
                      onMouseLeave={e => {
                        e.currentTarget.style.background = 'transparent'
                        e.currentTarget.style.borderColor = 'rgba(201,150,62,0.18)'
                      }}
                    >
                      <span style={{ color:T2, fontSize:10 }}>{s.label}</span>
                      <span style={{
                        fontSize:9, fontWeight:700, letterSpacing:1.5,
                        color:expectColor, padding:'1px 6px',
                        border:`1px solid ${expectColor}44`,
                      }}>{s.expect}</span>
                    </button>
                  )
                })}
              </div>
            </div>
          </div>

          {/* Last Inspection Metadata */}
          {result && (
            <div style={card({ padding:'14px 16px' })}>
              <div style={{ ...hdr, marginBottom:12 }}>[ LAST INSPECTION ]</div>
              {[
                ['Instrument', result.instrument_id],
                ['Time',       result.timestamp?.slice(0,19).replace('T',' ')],
                ['Duration',   `${result.processing_time_ms} ms`],
                ['Detections', result.detection_count],
              ].map(([k,v]) => (
                <div key={k} style={{ display:'flex', justifyContent:'space-between', alignItems:'baseline', padding:'5px 0', borderBottom:'1px solid rgba(201,150,62,0.06)' }}>
                  <span style={{ color:T2, fontSize:11 }}>{k}</span>
                  <span style={{ color:T1, fontSize:11, fontWeight:500, maxWidth:160, overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap', textAlign:'right' }}>{v}</span>
                </div>
              ))}
            </div>
          )}
        </div>

        {/* ─── CENTER COLUMN ────────────────────────────────────────────── */}
        <div style={{ display:'flex', flexDirection:'column', gap:1, background:BG }}>

          {/* Verdict Panel */}
          <div style={{
            background: verdictBg,
            border: result ? `2px solid ${verdictBg}` : '1px solid rgba(201,150,62,0.18)',
            borderRadius: 0,
            minHeight: 300,
            display: 'flex',
            flexDirection: 'column',
            alignItems: 'center',
            justifyContent: 'center',
            padding: '36px 24px',
            gap: 6,
            transition: 'background 0.35s ease',
            textAlign: 'center',
          }}>
            <div style={{
              fontSize: 100,
              fontWeight: 900,
              color: result ? 'rgba(255,255,255,0.97)' : 'rgba(201,150,62,0.2)',
              letterSpacing: -3,
              lineHeight: 0.88,
              textShadow: result ? '0 6px 32px rgba(0,0,0,0.25)' : 'none',
              transition: 'all 0.35s ease',
            }}>
              {result ? result.pass_fail : '—'}
            </div>

            {result ? (
              <>
                <div style={{ color:'rgba(255,255,255,0.88)', fontSize:20, fontWeight:700, letterSpacing:2, marginTop:12 }}>
                  {result.defect_type.toUpperCase()}
                </div>
                <div style={{ color:'rgba(255,255,255,0.7)', fontSize:32, fontWeight:300, letterSpacing:-0.5 }}>
                  {(result.confidence * 100).toFixed(1)}%
                </div>
                <div style={{ color:'rgba(255,255,255,0.55)', fontSize:12, letterSpacing:1 }}>
                  {result.detection_count} detection{result.detection_count !== 1 ? 's' : ''}&nbsp;&nbsp;·&nbsp;&nbsp;{result.processing_time_ms} ms
                </div>
                {result.pass_fail === 'FAIL' && result.bounding_box && (
                  <div style={{ color:'rgba(255,255,255,0.4)', fontSize:11, fontFamily:'monospace', marginTop:2 }}>
                    [{Math.round(result.bounding_box.x1)},{Math.round(result.bounding_box.y1)}] → [{Math.round(result.bounding_box.x2)},{Math.round(result.bounding_box.y2)}]
                  </div>
                )}
                <div style={{
                  marginTop:10, padding:'4px 14px',
                  background:'rgba(0,0,0,0.25)',
                  borderRadius:0, fontSize:11, fontFamily:MONO,
                  color: result.plc_signal_sent ? 'rgba(255,255,255,0.8)' : 'rgba(255,180,180,0.9)',
                  letterSpacing:1.5,
                }}>
                  PLC {result.plc_signal_sent ? 'SIGNAL SENT ✓' : 'SIGNAL FAILED ✗'}
                </div>
              </>
            ) : (
              <div style={{ color:'rgba(201,150,62,0.35)', fontSize:13, letterSpacing:3, marginTop:16 }}>
                AWAITING INSPECTION
              </div>
            )}
          </div>

          {/* Image Viewer with SVG bounding box overlay */}
          {imagePreview ? (
            <div style={{
              border: `1px solid rgba(201,150,62,0.5)`,
              borderRadius: 0,
              overflow: 'hidden',
              background: CARD,
            }}>
              <div style={{
                padding:'8px 14px',
                background: NAVY,
                borderBottom:'1px solid rgba(201,150,62,0.3)',
                fontSize:10, color:T2, letterSpacing:2, fontFamily:MONO,
                display:'flex', alignItems:'center', justifyContent:'space-between',
              }}>
                <span>INSPECTED IMAGE</span>
                {result && (
                  <span style={{ color: isPASS ? PASS : FAIL, fontWeight:700, fontSize:13 }}>
                    {detections.length} DETECTION{detections.length !== 1 ? 'S' : ''}
                  </span>
                )}
              </div>
              <div style={{ position:'relative', lineHeight:0 }}>
                <img
                  src={imagePreview}
                  alt="Inspected instrument"
                  style={{ width:'100%', display:'block' }}
                  onLoad={e => setNaturalSize({ w:e.target.naturalWidth, h:e.target.naturalHeight })}
                />
                {naturalSize.w > 0 && detections.length > 0 && (
                  <svg
                    style={{ position:'absolute', top:0, left:0, width:'100%', height:'100%' }}
                    viewBox={`0 0 ${naturalSize.w} ${naturalSize.h}`}
                    preserveAspectRatio="none"
                  >
                    {detections.map((det, i) => {
                      const { x1, y1, x2, y2 } = det.bounding_box
                      const color   = det.pass_fail === 'FAIL' ? FAIL : G
                      const sw      = Math.max(naturalSize.w * 0.003, 1.5)
                      const lblH    = Math.max(naturalSize.h * 0.042, 18)
                      const fz      = Math.max(naturalSize.h * 0.028, 12)
                      return (
                        <g key={i}>
                          <rect x={x1} y={y1} width={x2-x1} height={y2-y1}
                            fill="none" stroke={color} strokeWidth={sw}/>
                          <rect x={x1} y={y1 - lblH} width={(x2-x1) * 0.8} height={lblH}
                            fill={color} opacity={0.88}/>
                          <text x={x1+4} y={y1 - lblH * 0.22}
                            fill="white" fontSize={fz} fontFamily="monospace"
                            fontWeight="700" dominantBaseline="middle">
                            {det.defect_type} {(det.confidence*100).toFixed(0)}%
                          </text>
                        </g>
                      )
                    })}
                  </svg>
                )}
              </div>
            </div>
          ) : <ImagePlaceholder /> }
        </div>

        {/* ─── RIGHT COLUMN ─────────────────────────────────────────────── */}
        <div style={{ display:'flex', flexDirection:'column', gap:1, background:BG }}>

          {/* Shift Stats */}
          <div style={card()}>
            <div style={{ ...hdr, marginBottom:16 }}>[ CURRENT SHIFT ]</div>
            {stats ? (
              <>
                <div style={{ display:'grid', gridTemplateColumns:'1fr 1fr', gap:10, marginBottom:16 }}>
                  {[
                    { count: stats.pass_count, label:'PASS', color:PASS },
                    { count: stats.fail_count, label:'FAIL', color:FAIL },
                  ].map(({ count, label, color }) => (
                    <div key={label} style={{
                      textAlign:'center', padding:'14px 8px',
                      background:`${color}11`,
                      border:`1px solid ${color}44`,
                      borderRadius:0,
                    }}>
                      <div style={{ fontSize:44, fontWeight:800, color, lineHeight:1, fontVariantNumeric:'tabular-nums' }}>
                        {count}
                      </div>
                      <div style={{ color, fontSize:10, letterSpacing:2, fontWeight:700, marginTop:4 }}>{label}</div>
                    </div>
                  ))}
                </div>
                <div style={{ display:'flex', justifyContent:'space-between', alignItems:'center', padding:'8px 0', borderTop:'1px solid rgba(201,150,62,0.1)' }}>
                  <span style={{ color:T2, fontSize:12 }}>Total Inspected</span>
                  <span style={{ color:T1, fontSize:20, fontWeight:700, fontVariantNumeric:'tabular-nums' }}>{stats.total_inspections}</span>
                </div>
              </>
            ) : (
              <div style={{ display:'flex', gap:8, alignItems:'center', color:T2, fontSize:12, padding:'8px 0' }}>
                <Spinner size={14}/> Loading…
              </div>
            )}
          </div>

          {/* Defect Breakdown Chart */}
          <div style={card()}>
            <div style={{ ...hdr, marginBottom:14, display:'flex', justifyContent:'space-between', alignItems:'center' }}>
              <span>[ DEFECT BREAKDOWN ]</span>
              <span style={{ color:`${T2}88`, fontSize:10, letterSpacing:1, fontWeight:400 }}>↻ 10s</span>
            </div>
            {chartData.length > 0 ? (
              <ResponsiveContainer width="100%" height={180}>
                <BarChart data={chartData} margin={{ top:4, right:4, left:-26, bottom:0 }}>
                  <CartesianGrid strokeDasharray="3 3" stroke="rgba(201,150,62,0.07)" vertical={false}/>
                  <XAxis
                    dataKey="name"
                    tick={{ fontSize:9, fill:T2 }}
                    tickFormatter={v => v.slice(0,5)}
                    axisLine={{ stroke:'rgba(201,150,62,0.15)' }}
                    tickLine={false}
                  />
                  <YAxis
                    tick={{ fontSize:9, fill:T2 }}
                    allowDecimals={false}
                    axisLine={false}
                    tickLine={false}
                  />
                  <Tooltip
                    contentStyle={{ background:NAVY, border:`1px solid rgba(201,150,62,0.4)`, borderRadius:0, fontSize:11, fontFamily:MONO }}
                    labelStyle={{ color:T1, fontWeight:600 }}
                    itemStyle={{ color:STEEL }}
                    cursor={{ fill:'rgba(201,150,62,0.05)' }}
                  />
                  <Bar dataKey="count" fill={STEEL} radius={0}/>
                </BarChart>
              </ResponsiveContainer>
            ) : (
              <div style={{ height:180, display:'flex', alignItems:'center', justifyContent:'center', color:`${T2}55`, fontSize:12 }}>
                No data yet
              </div>
            )}
          </div>

          {/* PLC Signal Log */}
          <div style={card()}>
            <div style={{ ...hdr, marginBottom:14 }}>[ PLC SIGNAL LOG ]</div>
            {plcLog.length === 0 ? (
              <div style={{ color:`${T2}55`, fontSize:12, textAlign:'center', padding:'18px 0' }}>
                No signals this session
              </div>
            ) : (
              <div style={{ display:'flex', flexDirection:'column', gap:6 }}>
                {plcLog.map((sig, i) => (
                  <div key={i} style={{
                    display:'flex', alignItems:'center', gap:8,
                    padding:'7px 10px', borderRadius:0,
                    background: i === 0 ? 'rgba(201,150,62,0.06)' : 'transparent',
                    border: i === 0 ? '1px solid rgba(201,150,62,0.25)' : '1px solid transparent',
                    transition:'background 0.2s',
                  }}>
                    <span style={{
                      fontSize:10, fontWeight:700, letterSpacing:1.5,
                      color: sig.pass_fail === 'PASS' ? PASS : FAIL,
                      minWidth:34,
                    }}>{sig.pass_fail}</span>
                    <span style={{ flex:1, color:T2, fontSize:11, overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap' }}>
                      {sig.instrument_id}
                    </span>
                    <span style={{ color:`${T2}88`, fontSize:10, fontVariantNumeric:'tabular-nums' }}>
                      {sig.time}
                    </span>
                    {!sig.sent && <span style={{ color:FAIL, fontSize:10 }}>⚠</span>}
                  </div>
                ))}
              </div>
            )}
          </div>
        </div>
      </div>

      {/* ═══════════════════════════════════════ BOTTOM PANEL */}
      <div style={{ padding:'1px 16px 24px', background:'rgba(201,150,62,0.08)', borderTop:'1px solid rgba(201,150,62,0.15)' }}>
        <div style={card()}>
          <div style={{ display:'flex', alignItems:'center', gap:12, marginBottom:16 }}>
            <div style={hdr}>[ INSPECTION HISTORY ]</div>
            <div style={{ flex:1 }}/>
            {historyData && (
              <span style={{ color:`${T2}88`, fontSize:11 }}>
                {historyData.records?.length ?? 0} of {historyData.total} records
              </span>
            )}
            <button
              onClick={() => setHistoryTick(k => k + 1)}
              className="btn-outline"
            >
              REFRESH
            </button>
            <button onClick={downloadCsv} className="btn-gold">
              DOWNLOAD CSV
            </button>
          </div>

          {historyLoading && (
            <div style={{ display:'flex', gap:8, alignItems:'center', color:T2, fontSize:12, padding:'14px 0' }}>
              <Spinner size={14}/> Loading history…
            </div>
          )}

          {!historyLoading && historyData?.records?.length > 0 ? (
            <div className="scrollable" style={{ overflowX:'auto', overflowY:'auto', maxHeight:340 }}>
              <table style={{ width:'100%', borderCollapse:'collapse', fontSize:12 }}>
                <thead>
                  <tr>
                    {['Timestamp','Instrument ID','Operator','Defect','Confidence','Result','ms',''].map(h => (
                      <th key={h} style={{
                        padding:'8px 12px', textAlign:'left',
                        color:T2, fontSize:10, fontWeight:600, letterSpacing:1,
                        borderBottom:`1px solid rgba(201,150,62,0.2)`,
                        whiteSpace:'nowrap',
                        position:'sticky', top:0, background:CARD, zIndex:1,
                      }}>{h}</th>
                    ))}
                  </tr>
                </thead>
                <tbody>
                  {historyData.records.map((r, i) => (
                    <tr key={i} style={{ background: i % 2 === 0 ? '#0d1f3c' : '#091628', transition:'background 0.15s' }}>
                      <td style={{ padding:'7px 12px', color:T2, whiteSpace:'nowrap', fontVariantNumeric:'tabular-nums', fontSize:11 }}>
                        {r.iso8601_timestamp?.slice(0,19).replace('T',' ')}
                      </td>
                      <td style={{ padding:'7px 12px', color:T1, maxWidth:140, overflow:'hidden', textOverflow:'ellipsis', whiteSpace:'nowrap' }}>
                        {r.instrument_id}
                      </td>
                      <td style={{ padding:'7px 12px', color:T2 }}>{r.operator_id}</td>
                      <td style={{ padding:'7px 12px', color:T1 }}>{fmtDefect(r.defect_type)}</td>
                      <td style={{ padding:'7px 12px', color:T2, fontVariantNumeric:'tabular-nums' }}>
                        {(r.confidence * 100).toFixed(1)}%
                      </td>
                      <td style={{ padding:'7px 12px' }}>
                        <span style={{
                          padding:'2px 9px', borderRadius:0,
                          fontSize:10, fontWeight:700, letterSpacing:1.5, fontFamily:MONO,
                          background: r.pass_fail === 'PASS' ? `${PASS}1a` : `${FAIL}1a`,
                          color:       r.pass_fail === 'PASS' ? PASS : FAIL,
                          border:      `1px solid ${r.pass_fail === 'PASS' ? PASS : FAIL}40`,
                        }}>
                          {r.pass_fail}
                        </span>
                      </td>
                      <td style={{ padding:'7px 12px', color:T2, fontVariantNumeric:'tabular-nums' }}>
                        {r.processing_time_ms}
                      </td>
                      <td style={{ padding:'7px 12px' }}>
                        {r.id && <a href={`/results/${r.id}`} style={{ color:STEEL, fontSize:10, fontWeight:700, letterSpacing:1, textDecoration:'none', border:`1px solid ${STEEL}66`, padding:'2px 7px' }}>VIEW</a>}
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          ) : !historyLoading ? (
            <div style={{ textAlign:'center', padding:'36px 0', color:`${T2}55`, fontSize:13, letterSpacing:2 }}>
              NO INSPECTION HISTORY YET
            </div>
          ) : null}
        </div>
      </div>
    </div>
  )
}
