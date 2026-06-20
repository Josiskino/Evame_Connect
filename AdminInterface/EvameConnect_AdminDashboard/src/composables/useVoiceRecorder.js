/**
 * MediaRecorder wrapper for the AI chat composer.
 *
 * Two usages — same recorder, different actions on stop:
 *   - Dictation : start() → stop() returns a Blob, caller transcribes
 *   - Voice note : start() → stop() returns a Blob + duration, caller uploads
 *
 * Picks the best supported MIME (Opus WebM preferred, MP4 fallback for
 * Safari iOS).
 */
export function useVoiceRecorder() {
  const isRecording = ref(false)
  const isSupported = ref(typeof window !== 'undefined' && !!navigator.mediaDevices?.getUserMedia && typeof MediaRecorder !== 'undefined')
  const elapsedSeconds = ref(0)
  const error = ref(null)

  let recorder = null
  let chunks = []
  let stream = null
  let startTime = 0
  let tickHandle = null
  let resolveStop = null

  const pickMimeType = () => {
    const candidates = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/mpeg', 'audio/ogg;codecs=opus']
    for (const m of candidates) {
      if (MediaRecorder.isTypeSupported(m)) return m
    }
    return ''
  }

  const start = async () => {
    if (isRecording.value) return
    error.value = null
    elapsedSeconds.value = 0
    try {
      stream = await navigator.mediaDevices.getUserMedia({ audio: true })
      const mime = pickMimeType()
      recorder = mime ? new MediaRecorder(stream, { mimeType: mime }) : new MediaRecorder(stream)
      chunks = []
      recorder.ondataavailable = e => { if (e.data?.size) chunks.push(e.data) }
      recorder.onstop = () => {
        const blob = new Blob(chunks, { type: recorder.mimeType || 'audio/webm' })
        const duration = (Date.now() - startTime) / 1000
        cleanup()
        if (resolveStop) {
          resolveStop({ blob, duration, mimeType: recorder?.mimeType ?? 'audio/webm' })
          resolveStop = null
        }
      }
      recorder.start(250) // gather chunks every 250ms
      startTime = Date.now()
      tickHandle = setInterval(() => { elapsedSeconds.value = (Date.now() - startTime) / 1000 }, 250)
      isRecording.value = true
    }
    catch (e) {
      error.value = e?.message ?? 'Accès micro refusé.'
      cleanup()
      throw e
    }
  }

  const stop = () => new Promise(resolve => {
    if (!isRecording.value || !recorder) {
      resolve(null)
      return
    }
    resolveStop = resolve
    recorder.stop()
  })

  const cancel = () => {
    if (recorder && recorder.state !== 'inactive') recorder.stop()
    cleanup()
    if (resolveStop) {
      resolveStop(null)
      resolveStop = null
    }
  }

  const cleanup = () => {
    if (tickHandle) { clearInterval(tickHandle); tickHandle = null }
    if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null }
    isRecording.value = false
  }

  onScopeDispose?.(() => cleanup())

  return { isRecording, isSupported, elapsedSeconds, error, start, stop, cancel }
}
